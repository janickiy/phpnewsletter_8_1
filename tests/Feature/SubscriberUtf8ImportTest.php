<?php

namespace Tests\Feature;

use App\Http\Requests\Admin\Subscribers\ImportRequest;
use App\Models\Category;
use App\Models\Subscribers;
use App\Models\User;
use App\Services\SubscriberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubscriberUtf8ImportTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = storage_path('app/tests/utf8-import-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->temporaryDirectory)) {
                File::deleteDirectory($this->temporaryDirectory);
            }
        } finally {
            parent::tearDown();
        }
    }

    #[DataProvider('utf8Files')]
    public function test_utf8_import_preserves_names_and_categories_and_ignores_legacy_charset(string $extension, bool $withBom): void
    {
        $category = Category::query()->create(['name' => 'Unicode subscribers']);
        $names = [
            'anna@example.test' => 'Анна Петрова',
            'yuki@example.test' => '山田 ゆき',
            'emoji@example.test' => 'Happy reader 🌍 ✉️',
        ];
        $contents = $extension === 'csv' ? "Email,Name\r\n" : '';

        foreach ($names as $email => $name) {
            $contents .= $extension === 'csv'
                ? $email.',"'.$name.'"'."\r\n"
                : $name.' '.$email."\r\n";
        }

        $file = $this->uploadedFile($extension, ($withBom ? "\xEF\xBB\xBF" : '').$contents);
        $input = ['categoryId' => [$category->id], 'charset' => 'Windows-1251'];
        $validator = Validator::make($input + ['import' => $file], (new ImportRequest)->rules());

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
        $this->assertArrayNotHasKey('charset', $validator->validated());

        $request = Request::create('/import', 'POST', $input, [], ['import' => $file]);
        $service = app(SubscriberService::class);
        $count = $extension === 'csv'
            ? $service->importFromExcel($request)
            : $service->importFromText($request);

        $this->assertSame(count($names), $count);
        $this->assertDatabaseCount('subscribers', count($names));
        $this->assertDatabaseCount('subscriptions', count($names));

        foreach ($names as $email => $name) {
            $subscriber = Subscribers::query()->where('email', $email)->firstOrFail();
            $this->assertSame($name, $subscriber->name);
            $this->assertSame(1, (int) $subscriber->active);
            $this->assertEquals([$category->id], $subscriber->subscriptions()->pluck('category_id')->all());
        }
    }

    public static function utf8Files(): array
    {
        return [
            'TXT' => ['txt', false],
            'TXT with UTF-8 BOM' => ['txt', true],
            'CSV' => ['csv', false],
            'CSV with UTF-8 BOM' => ['csv', true],
        ];
    }

    #[DataProvider('invalidEncodings')]
    public function test_non_utf8_text_import_is_rejected_before_any_subscribers_are_written(string $extension, string $encoding, bool $withBom): void
    {
        $this->actingAs(User::query()->create([
            'name' => 'Import administrator',
            'login' => 'utf8-import-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'password',
        ]));
        $category = Category::query()->create(['name' => 'Rejected import']);
        $contents = $extension === 'csv'
            ? "Email,Name\nfirst@example.test,First reader\nsecond@example.test,Анна\n"
            : "First reader first@example.test\nАнна second@example.test\n";

        if ($encoding !== 'Windows-1251' && ! $withBom) {
            // ASCII-only UTF-16 contains NUL bytes but can pass a UTF-8 byte-sequence check.
            $contents = str_replace('Анна', 'Second reader', $contents);
        }

        $contents = mb_convert_encoding($contents, $encoding, 'UTF-8');

        if ($withBom) {
            $contents = ($encoding === 'UTF-16LE' ? "\xFF\xFE" : "\xFE\xFF").$contents;
        }

        $this->from(route('admin.subscribers.import'))
            ->post(route('admin.subscribers.import_subscribers'), [
                'import' => $this->uploadedFile($extension, $contents),
                'categoryId' => [$category->id],
                'charset' => $encoding,
            ])
            ->assertRedirect(route('admin.subscribers.import'))
            ->assertSessionHasErrors('import');

        $this->assertDatabaseCount('subscribers', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public static function invalidEncodings(): array
    {
        $cases = [];

        foreach (['txt', 'csv'] as $extension) {
            $cases[$extension.' Windows-1251'] = [$extension, 'Windows-1251', false];

            foreach (['UTF-16LE', 'UTF-16BE'] as $encoding) {
                $cases[$extension.' '.$encoding.' with BOM'] = [$extension, $encoding, true];
                $cases[$extension.' '.$encoding.' without BOM'] = [$extension, $encoding, false];
            }
        }

        return $cases;
    }

    public function test_validation_preserves_split_unicode_sequences_and_detects_invalid_bytes_at_the_end(): void
    {
        foreach ([65533, 65534, 65535] as $prefixLength) {
            $contents = str_repeat(' ', $prefixLength)."🌍 reader@example.test\n";
            $validator = Validator::make(
                ['import' => $this->uploadedFile('txt', $contents)],
                (new ImportRequest)->rules()
            );

            $this->assertTrue($validator->passes(), $validator->errors()->toJson());

            $validator = Validator::make(
                ['import' => $this->uploadedFile('txt', $contents."\xFF")],
                (new ImportRequest)->rules()
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('import'));
        }
    }

    private function uploadedFile(string $extension, string $contents): UploadedFile
    {
        $path = $this->temporaryDirectory.'/subscribers.'.$extension;
        file_put_contents($path, $contents);

        return new UploadedFile($path, 'subscribers.'.$extension, null, null, true);
    }
}
