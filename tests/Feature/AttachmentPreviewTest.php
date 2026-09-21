<?php

namespace Tests\Feature;

use App\Models\{Attach, Project, Templates, User};
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttachmentPreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Project $project;
    private Templates $template;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        $this->owner = $this->user('preview-owner', User::ROLE_PROJECT_ADMIN);
        $this->project = Project::query()->create([
            'name' => 'Private preview project',
            'status' => 1,
            'owner_id' => $this->owner->id,
        ]);
        $this->template = Templates::query()->create([
            'name' => 'Template with previews',
            'body' => '<p>Hello</p>',
            'prior' => 0,
            'project_id' => $this->project->id,
        ]);
    }

    #[DataProvider('imageSizes')]
    public function test_raster_previews_are_private_pngs_that_preserve_aspect_ratio_without_upscaling(
        string $format,
        int $width,
        int $height,
        int $previewWidth,
        int $previewHeight,
    ): void {
        config(['filesystems.default' => 'public']);
        $original = $this->imageBytes($format, $width, $height);
        $attachment = $this->attachment('photo.'.$format, $original);

        $this->assertTrue($attachment->isPreviewableImage());
        $response = $this->actingAs($this->owner)
            ->get(route('admin.templates.attachment.preview', $attachment->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $size = getimagesizefromstring($response->getContent());
        $this->assertNotFalse($size);
        $this->assertSame([$previewWidth, $previewHeight, IMAGETYPE_PNG], array_slice($size, 0, 3));
        $preview = imagecreatefromstring($response->getContent());
        $this->assertNotFalse($preview);
        $topLeft = imagecolorsforindex($preview, imagecolorat($preview, 0, 0));
        $bottomRight = imagecolorsforindex($preview, imagecolorat($preview, $previewWidth - 1, $previewHeight - 1));
        foreach (['red' => 240, 'green' => 190, 'blue' => 30] as $channel => $value) {
            $this->assertEqualsWithDelta($value, $topLeft[$channel], 10);
        }
        foreach (['red' => 25, 'green' => 110, 'blue' => 180] as $channel => $value) {
            $this->assertEqualsWithDelta($value, $bottomRight[$channel], 10);
        }

        $path = Attach::DIRECTORY.'/'.$attachment->file_name;
        $this->assertSame($original, Storage::disk('local')->get($path));
        $this->assertSame([$path], Storage::disk('local')->allFiles());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public static function imageSizes(): array
    {
        return [
            'landscape JPEG' => ['jpeg', 320, 160, 160, 80],
            'portrait PNG' => ['png', 160, 320, 60, 120],
            'WebP' => ['webp', 400, 300, 160, 120],
            'square GIF' => ['gif', 240, 240, 120, 120],
            'small image remains small' => ['png', 32, 24, 32, 24],
        ];
    }

    public function test_preview_uses_file_contents_instead_of_the_filename_extension(): void
    {
        $attachment = $this->attachment('picture.txt', $this->imageBytes('png', 80, 40));

        $this->assertTrue($attachment->isPreviewableImage());
        $this->actingAs($this->owner)
            ->get(route('admin.templates.attachment.preview', $attachment->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    #[DataProvider('nonImages')]
    public function test_non_images_and_corrupt_images_cannot_be_previewed(string $name, string $contents): void
    {
        $attachment = $this->attachment($name, $contents);

        $this->assertFalse($attachment->isPreviewableImage());
        $this->actingAs($this->owner)
            ->get(route('admin.templates.attachment.preview', $attachment->id))
            ->assertNotFound();
    }

    public static function nonImages(): array
    {
        return [
            'ordinary text' => ['report.txt', 'Private report contents'],
            'HTML disguised as PNG' => ['photo.png', '<html><script>alert(1)</script></html>'],
            'SVG' => ['vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'SVG disguised as JPEG' => ['vector.jpg', '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>'],
            'corrupt PNG' => ['broken.png', "\x89PNG\r\n\x1a\nnot a complete image"],
        ];
    }

    public function test_missing_files_and_unknown_attachments_return_not_found(): void
    {
        $attachment = $this->attachment('missing.png', $this->imageBytes('png', 40, 40));
        Storage::disk('local')->delete(Attach::DIRECTORY.'/'.$attachment->file_name);

        $this->assertFalse($attachment->isPreviewableImage());
        $this->actingAs($this->owner)
            ->get(route('admin.templates.attachment.preview', $attachment->id))
            ->assertNotFound();
        $this->get(route('admin.templates.attachment.preview', 999999))->assertNotFound();
    }

    public function test_oversized_files_and_excessive_image_dimensions_are_not_previewed(): void
    {
        $images = [
            'too-large.png' => str_pad($this->imageBytes('png', 1, 1), 10 * 1024 * 1024 + 1, "\0"),
            'too-wide.png' => $this->imageBytes('png', 8193, 1),
        ];

        foreach ($images as $name => $contents) {
            $attachment = $this->attachment($name, $contents);
            $this->assertFalse($attachment->isPreviewableImage(), $name);
            $this->actingAs($this->owner)
                ->get(route('admin.templates.attachment.preview', $attachment->id))
                ->assertNotFound();
        }
    }

    public function test_preview_requires_manage_access_to_the_attachment_project(): void
    {
        $attachment = $this->attachment('private.png', $this->imageBytes('png', 80, 40));
        $url = route('admin.templates.attachment.preview', $attachment->id);

        $this->get($url)->assertRedirect(route('login'));

        $otherOwner = $this->user('preview-other-owner', User::ROLE_PROJECT_ADMIN);
        Project::query()->create(['name' => 'Other project', 'status' => 1, 'owner_id' => $otherOwner->id]);
        $this->actingAs($otherOwner)->get($url)->assertNotFound();

        $moderator = $this->user('preview-moderator', User::ROLE_MODERATOR);
        $this->project->members()->attach($moderator->id, ['role' => User::ROLE_MODERATOR]);
        $this->actingAs($moderator)->get($url)->assertForbidden();

        $manager = $this->user('preview-manager', User::ROLE_PROJECT_ADMIN);
        $this->project->members()->attach($manager->id, ['role' => User::ROLE_PROJECT_ADMIN]);
        $this->actingAs($manager)->get($url)->assertOk();

        $this->actingAs($this->user('preview-admin', User::ROLE_ADMIN))->get($url)->assertOk();
    }

    public function test_preview_reencodes_image_while_download_preserves_original_bytes_and_filename(): void
    {
        $marker = 'private-original-attachment-marker';
        $original = $this->imageBytes('jpeg', 320, 160).$marker;
        $attachment = $this->attachment('original-photo.jpg', $original);

        $preview = $this->actingAs($this->owner)
            ->get(route('admin.templates.attachment.preview', $attachment->id))
            ->assertOk();
        $this->assertStringNotContainsString($marker, $preview->getContent());

        $download = $this->get(route('admin.templates.attachment', $attachment->id))
            ->assertOk()
            ->assertDownload('original-photo.jpg')
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($original, $download->streamedContent());
    }

    public function test_show_and_edit_render_only_valid_image_previews_and_keep_attachment_controls(): void
    {
        $images = [
            $this->attachment('landscape.jpg', $this->imageBytes('jpeg', 320, 160)),
            $this->attachment('picture.txt', $this->imageBytes('png', 80, 40)),
        ];
        $nonImages = [
            $this->attachment('report.txt', 'Private report'),
            $this->attachment('fake.png', '<script>alert(1)</script>'),
            $this->attachment('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
            $missing = $this->attachment('missing.png', $this->imageBytes('png', 40, 40)),
        ];
        Storage::disk('local')->delete(Attach::DIRECTORY.'/'.$missing->file_name);

        foreach (['admin.templates.show', 'admin.templates.edit'] as $route) {
            $response = $this->actingAs($this->owner)->get(route($route, $this->template->id))->assertOk();
            $xpath = $this->htmlXPath($response->getContent());

            foreach ($images as $image) {
                $previewUrl = route('admin.templates.attachment.preview', $image->id);
                $this->assertSame(1, $xpath->query('//img[@src="'.$previewUrl.'"]')->length, $route);
            }

            foreach ($nonImages as $attachment) {
                $previewUrl = route('admin.templates.attachment.preview', $attachment->id);
                $downloadUrl = route('admin.templates.attachment', $attachment->id);
                $this->assertSame(0, $xpath->query('//img[@src="'.$previewUrl.'" or @src="'.$downloadUrl.'"]')->length, $route);
                $entry = $route === 'admin.templates.edit'
                    ? '//*[@id="attach_'.$attachment->id.'"]'
                    : '//li[.//a[@href="'.$downloadUrl.'"]]';
                $this->assertSame(0, $xpath->query($entry.'//img')->length, $route);
                $this->assertGreaterThan(0, $xpath->query($entry.'//i[contains(concat(" ", normalize-space(@class), " "), " fa-file ")]')->length, $route);
            }

            foreach ([...$images, ...$nonImages] as $attachment) {
                $downloadUrl = route('admin.templates.attachment', $attachment->id);
                $this->assertGreaterThan(0, $xpath->query('//a[@href="'.$downloadUrl.'"]')->length, $route);
                if ($route === 'admin.templates.edit') {
                    $this->assertSame(1, $xpath->query('//*[@id="attach_'.$attachment->id.'"]//*[@data-num="'.$attachment->id.'" and contains(@class, "remove_attach")]')->length);
                }
            }
        }
    }

    private function user(string $login, string $role): User
    {
        return User::query()->create([
            'name' => $login,
            'login' => $login,
            'role' => $role,
            'password' => 'password',
        ]);
    }

    private function attachment(string $name, string $contents): Attach
    {
        $attachment = Attach::query()->create([
            'name' => $name,
            'file_name' => Str::uuid().'.'.pathinfo($name, PATHINFO_EXTENSION),
            'template_id' => $this->template->id,
        ]);
        Storage::disk('local')->put(Attach::DIRECTORY.'/'.$attachment->file_name, $contents);

        return $attachment;
    }

    private function imageBytes(string $format, int $width, int $height): string
    {
        $encoder = 'image'.$format;
        if (!function_exists($encoder)) {
            $this->markTestSkipped('GD '.$format.' support is required for this image fixture.');
        }

        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 25, 110, 180));
        imagefilledrectangle($image, 0, 0, intdiv($width, 2), intdiv($height, 2), imagecolorallocate($image, 240, 190, 30));

        ob_start();
        try {
            $this->assertTrue($encoder($image));

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    private function htmlXPath(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new DOMXPath($document);
    }
}
