<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use App\Services\DownloadService;
use App\Services\SubscriberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SpreadsheetCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = storage_path('app/tests/spreadsheets-'.bin2hex(random_bytes(8)));
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

    #[DataProvider('spreadsheetFormats')]
    public function test_import_preserves_subscriber_values_and_category_memberships(string $extension, ?string $lineEnding): void
    {
        $oldCategory = Category::query()->create(['name' => 'Previous category']);
        $firstCategory = Category::query()->create(['name' => 'First import category']);
        $secondCategory = Category::query()->create(['name' => 'Second import category']);
        $categoryIds = [$firstCategory->id, $secondCategory->id];
        $existing = $this->createSubscriber('existing@example.test', 'Original name');
        $this->subscribe($existing, $oldCategory);

        $request = Request::create('/import', 'POST', ['categoryId' => $categoryIds], [], [
            'import' => $this->createImportFile($extension, $lineEnding),
        ]);
        $progress = [];

        $count = app(SubscriberService::class)->importFromExcel(
            $request,
            function (int $processed) use (&$progress): void {
                $progress[] = $processed;
            }
        );

        $this->assertSame(3, $count);
        $this->assertSame(3, end($progress));
        $this->assertDatabaseCount('subscribers', 3);
        $this->assertDatabaseHas('subscribers', [
            'email' => 'new@example.test',
            'name' => 'Анна, "Тест" & компания',
            'active' => 1,
        ]);
        $this->assertDatabaseHas('subscribers', [
            'email' => 'other@example.test',
            'name' => 'Другой подписчик',
            'active' => 1,
        ]);
        $this->assertSame('Original name', $existing->fresh()->name);
        $this->assertDatabaseCount('subscriptions', 6);
        $this->assertDatabaseMissing('subscriptions', ['category_id' => $oldCategory->id]);

        foreach (Subscribers::query()->get() as $subscriber) {
            $this->assertEqualsCanonicalizing(
                $categoryIds,
                $subscriber->subscriptions()->pluck('category_id')->all()
            );
        }
    }

    public static function spreadsheetFormats(): array
    {
        return [
            'CSV with Unix line endings' => ['csv', "\n"],
            'CSV with Windows line endings' => ['csv', "\r\n"],
            'Excel XLS' => ['xls', null],
            'OpenDocument ODS' => ['ods', null],
            'Excel XLSX' => ['xlsx', null],
        ];
    }

    public function test_exported_xlsx_is_readable_and_contains_only_active_selected_subscribers(): void
    {
        $category = Category::query()->create(['name' => 'Export category']);
        $first = $this->createSubscriber('anna@example.test', 'Анна, "Тест" & компания');
        $second = $this->createSubscriber('second@example.test', 'Second subscriber');
        $inactive = $this->createSubscriber('inactive@example.test', 'Inactive subscriber', 0);
        $this->createSubscriber('outside@example.test', 'Outside the selected category');

        foreach ([$first, $second, $inactive] as $subscriber) {
            $this->subscribe($subscriber, $category);
        }

        $response = app(DownloadService::class)->exportSubscribers(Request::create('/export', 'POST', [
            'export_type' => 'excel',
            'categoryId' => [$category->id],
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        ob_start();
        try {
            $response->sendContent();
            $contents = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $path = $this->temporaryDirectory.'/export.xlsx';
        file_put_contents($path, $contents);
        $spreadsheet = IOFactory::load($path);

        try {
            $this->assertSame([
                ['Email', 'Name'],
                ['anna@example.test', 'Анна, "Тест" & компания'],
                ['second@example.test', 'Second subscriber'],
            ], $spreadsheet->getActiveSheet()->toArray());
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function createImportFile(string $extension, ?string $lineEnding): UploadedFile
    {
        $path = $this->temporaryDirectory.'/import.'.$extension;
        $rows = [
            ['Email', 'Name'],
            ['  NEW@EXAMPLE.TEST  ', 'First occurrence'],
            ['  EXISTING@EXAMPLE.TEST  ', 'Imported replacement'],
            ['other@example.test', '  Другой подписчик  '],
            ['New@example.test', '  Анна, "Тест" & компания  '],
            ['not an email', 'Invalid subscriber'],
            ['', 'Missing email'],
        ];

        if ($extension === 'csv') {
            $handle = fopen($path, 'wb');

            try {
                foreach ($rows as $row) {
                    fputcsv($handle, $row, ',', '"', '', $lineEnding);
                }
            } finally {
                fclose($handle);
            }
        } else {
            $spreadsheet = new Spreadsheet;

            try {
                $spreadsheet->getActiveSheet()->fromArray($rows);
                IOFactory::createWriter($spreadsheet, ucfirst($extension))->save($path);
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }

        return new UploadedFile($path, 'subscribers.'.$extension, null, null, true);
    }

    private function createSubscriber(string $email, string $name, int $active = 1): Subscribers
    {
        return Subscribers::query()->create([
            'email' => $email,
            'name' => $name,
            'active' => $active,
            'token' => md5($email),
        ]);
    }

    private function subscribe(Subscribers $subscriber, Category $category): void
    {
        Subscriptions::query()->create([
            'subscriber_id' => $subscriber->id,
            'category_id' => $category->id,
        ]);
    }
}
