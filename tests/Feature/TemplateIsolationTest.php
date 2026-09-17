<?php

namespace Tests\Feature;

use App\Models\{Attach, Project, Templates, User};
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherUser;
    private Project $project;
    private Templates $template;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->owner = User::query()->create(['name' => 'Owner', 'login' => 'attachment-owner', 'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'password']);
        $this->otherUser = User::query()->create(['name' => 'Other user', 'login' => 'attachment-other', 'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'password']);
        $this->project = Project::query()->create(['name' => 'Attachment project', 'status' => 1, 'owner_id' => $this->owner->id]);
        $this->template = Templates::query()->create(['name' => 'Private template', 'body' => '<p>Hello</p>', 'prior' => 0, 'project_id' => $this->project->id]);
    }

    public function test_uploads_are_private_and_downloads_require_access_to_the_template_project(): void
    {
        config(['filesystems.default' => 'public']);
        $this->actingAs($this->owner)->post(route('admin.templates.store'), [
            'project_id' => $this->project->id,
            'name' => 'Template with private file',
            'body' => '<p>Body</p>',
            'prior' => 0,
            'attachfile' => [UploadedFile::fake()->createWithContent('example.php', '<?php echo "private";')],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.templates.index'));
        $attachment = Attach::query()->firstOrFail();
        Storage::disk('local')->assertExists(Attach::DIRECTORY.'/'.$attachment->file_name);
        Storage::disk('local')->assertMissing('public/attach/'.$attachment->file_name);
        Storage::disk('public')->assertMissing(Attach::DIRECTORY.'/'.$attachment->file_name);

        $this->get(route('admin.templates.attachment', $attachment->id))->assertOk()->assertDownload('example.php')
            ->assertHeader('Content-Type', 'application/octet-stream')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->otherUser)->get(route('admin.templates.attachment', $attachment->id))->assertNotFound();
        auth()->logout();
        $this->get(route('admin.templates.attachment', $attachment->id))->assertRedirect(route('login'));
    }

    public function test_template_html_is_rendered_only_inside_a_scriptless_sandbox(): void
    {
        $payload = '<script>document.body.dataset.unsafe = "yes"</script><img src=x onerror="alert(1)"><table style="color:red"><tr><td>%NAME%</td></tr></table>';
        $this->template->update(['body' => $payload]);
        $response = $this->actingAs($this->owner)->get(route('admin.templates.show', $this->template->id))->assertOk();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $frames = $xpath->query('//iframe[@srcdoc]');
        $this->assertSame(1, $frames->length);
        $frame = $frames->item(0);
        $this->assertTrue($frame->hasAttribute('sandbox'));
        $this->assertSame('', $frame->getAttribute('sandbox'));
        $this->assertSame($payload, $frame->getAttribute('srcdoc'));
        $this->assertSame(0, $xpath->query('//*[@onerror]')->length);
        $this->assertStringNotContainsString('dataset.unsafe', $xpath->evaluate('string(//script[contains(text(), "dataset.unsafe")])'));
    }

}
