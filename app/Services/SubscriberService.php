<?php

namespace App\Services;


use App\Helpers\StringHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class SubscriberService
{
    private const SPREADSHEET_CHUNK_SIZE = 10000;
    private const DATABASE_CHUNK_SIZE = 1000;

    /**
     * Import subscribers from a spreadsheet in memory-bounded chunks.
     *
     * @param Request $request
     * @param callable|null $onChunkProcessed
     * @return bool|int
     */
    public function importFromExcel(Request $request, ?callable $onChunkProcessed = null): bool|int
    {
        $extension = strtolower($request->file('import')->getClientOriginalExtension());
        $file = $request->file('import')->getRealPath();
        $projectIds = $this->authorizedProjectIds((array) $request->input('project_ids', []));

        if ($file === false) {
            return false;
        }

        if ($extension === 'xlsx') {
            return $this->importFromXlsx($file, (array) ($request->categoryId ?? []), $projectIds, $onChunkProcessed);
        }

        $reader = $this->createSpreadsheetReader($extension);
        $categoryIds = (array) ($request->categoryId ?? []);
        $count = 0;

        foreach ($reader->listWorksheetInfo($file) as $worksheetInfo) {
            $sheetName = $worksheetInfo['worksheetName'];
            $totalRows = (int) $worksheetInfo['totalRows'];

            for ($startRow = 2; $startRow <= $totalRows; $startRow += self::SPREADSHEET_CHUNK_SIZE) {
                $endRow = min($startRow + self::SPREADSHEET_CHUNK_SIZE - 1, $totalRows);

                $chunkReader = $this->createSpreadsheetReader($extension);
                $chunkReader->setLoadSheetsOnly([$sheetName]);
                $chunkReader->setReadFilter(new SubscriberImportReadFilter($startRow, $endRow));

                $spreadsheet = $chunkReader->load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = [];

                for ($row = $startRow; $row <= $endRow; $row++) {
                    $email = strtolower(trim((string) $worksheet->getCell('A' . $row)->getValue()));
                    $name = trim((string) $worksheet->getCell('B' . $row)->getValue());

                    if ($email === '') {
                        continue;
                    }

                    $rows[] = [
                        'email' => $email,
                        'name' => $name,
                    ];
                }

                $count += $this->importSubscriberRows($rows, $categoryIds, $projectIds);
                $this->reportImportProgress($onChunkProcessed, $count);

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $worksheet, $rows);
                gc_collect_cycles();
            }
        }

        return $count;
    }

    /**
     * Import XLSX rows by reading worksheet XML directly instead of loading the workbook.
     *
     * @param string $file
     * @param array $categoryIds
     * @param callable|null $onChunkProcessed
     * @return bool|int
     */
    private function importFromXlsx(string $file, array $categoryIds, array $projectIds, ?callable $onChunkProcessed = null): bool|int
    {
        $zip = new \ZipArchive();

        if ($zip->open($file) !== true) {
            return false;
        }

        $worksheetPath = $this->getFirstWorksheetPath($zip);
        $sharedStrings = SubscriberSharedStringStore::create($file);
        $reader = new \XMLReader();
        $rows = [];
        $count = 0;

        try {
            if (!$reader->open($this->zipStreamPath($file, $worksheetPath))) {
                return false;
            }

            while ($reader->read()) {
                if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $rowNumber = (int) $reader->getAttribute('r');

                if ($rowNumber <= 1) {
                    continue;
                }

                $row = $this->readXlsxRow($reader->readOuterXml(), $sharedStrings);

                if ($row['email'] === '') {
                    continue;
                }

                $rows[] = $row;

                if (count($rows) >= self::SPREADSHEET_CHUNK_SIZE) {
                    $count += $this->importSubscriberRows($rows, $categoryIds, $projectIds);
                    $rows = [];
                    $this->reportImportProgress($onChunkProcessed, $count);
                }
            }
        } finally {
            $reader->close();
            $zip->close();
            $sharedStrings->close();
        }

        $count += $this->importSubscriberRows($rows, $categoryIds, $projectIds);
        $this->reportImportProgress($onChunkProcessed, $count);

        return $count;
    }

    /**
     * Import subscribers from UTF-8 text in chunks while extracting email and display name.
     *
     * @param object $f
     * @param callable|null $onChunkProcessed
     * @return bool|int
     */
    public function importFromText(object $f, ?callable $onChunkProcessed = null): bool|int
    {
        $projectIds = $this->authorizedProjectIds((array) $f->input('project_ids', []));

        if (!($fp = @fopen($f->file('import'), "rb"))) {
            return false;
        }

        $count = 0;
        $rows = [];
        $categoryIds = (array) ($f->categoryId ?? []);
        $firstLine = true;

        while (($line = fgets($fp)) !== false) {
            if ($firstLine && str_starts_with($line, "\xEF\xBB\xBF")) {
                $line = substr($line, 3);
            }
            $firstLine = false;
            $str = trim($line);

            preg_match('/([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/uis', $str, $out);

            $email = strtolower($out[0] ?? '');
            $name = trim(str_replace($email, '', $str));

            if (mb_strlen($name) > 250) {
                $name = '';
            }

            $rows[] = [
                'email' => $email,
                'name' => $name,
            ];

            if (count($rows) >= self::SPREADSHEET_CHUNK_SIZE) {
                $count += $this->importSubscriberRows($rows, $categoryIds, $projectIds);
                $rows = [];
                $this->reportImportProgress($onChunkProcessed, $count);
            }
        }

        fclose($fp);

        $count += $this->importSubscriberRows($rows, $categoryIds, $projectIds);
        $this->reportImportProgress($onChunkProcessed, $count);

        return $count;
    }

    /**
     * Resolve the projects an import may attach, allowing unassigned contacts for administrators.
     *
     * @return array<int, int>
     */
    private function authorizedProjectIds(array $projectIds): array
    {
        $projectIds = array_values(array_unique(array_map('intval', $projectIds)));
        abort_if($projectIds === [] && !auth()->user()?->isAdmin(), 403);

        foreach ($projectIds as $projectId) {
            ProjectAccess::authorizeProject($projectId);
        }

        return $projectIds;
    }

    /**
     * Create a configured spreadsheet reader by file extension.
     *
     * @param string $extension
     * @return \PhpOffice\PhpSpreadsheet\Reader\IReader
     */
    private function createSpreadsheetReader(string $extension): \PhpOffice\PhpSpreadsheet\Reader\IReader
    {
        $inputFileType = match ($extension) {
            'xlsx' => 'Xlsx',
            'xls' => 'Xls',
            'csv' => 'Csv',
            'ods' => 'Ods',
            default => throw new \InvalidArgumentException('Unsupported spreadsheet extension.'),
        };

        $reader = IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);

        if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
            $reader->setInputEncoding('UTF-8');
        }

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        return $reader;
    }

    /**
     * Notify the caller that another import chunk has been processed.
     *
     * @param callable|null $callback
     * @param int $count
     * @return void
     */
    private function reportImportProgress(?callable $callback, int $count): void
    {
        if ($callback !== null) {
            $callback($count);
        }
    }

    /**
     * Resolve the first worksheet XML path inside an XLSX archive.
     *
     * @param \ZipArchive $zip
     * @return string
     */
    private function getFirstWorksheetPath(\ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relationshipsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $relationships = simplexml_load_string($relationshipsXml);

        if ($workbook === false || $relationships === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $sheet = $workbook->sheets->sheet[0] ?? null;

        if ($sheet === null) {
            return 'xl/worksheets/sheet1.xml';
        }

        $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId = (string) ($attributes['id'] ?? '');

        foreach ($relationships->Relationship as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = (string) $relationship['Target'];

            if (str_starts_with($target, '/')) {
                return ltrim($target, '/');
            }

            return str_starts_with($target, 'xl/')
                ? $target
                : 'xl/' . $target;
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * Build a zip stream URI for XMLReader.
     *
     * @param string $file
     * @param string $entry
     * @return string
     */
    private function zipStreamPath(string $file, string $entry): string
    {
        return 'zip://' . $file . '#' . $entry;
    }

    /**
     * Read email and name from one XLSX worksheet row XML.
     *
     * @param string $rowXml
     * @param SubscriberSharedStringStore $sharedStrings
     * @return array{email: string, name: string}
     */
    private function readXlsxRow(string $rowXml, SubscriberSharedStringStore $sharedStrings): array
    {
        $email = '';
        $name = '';
        $row = simplexml_load_string($rowXml);

        if ($row === false) {
            return ['email' => '', 'name' => ''];
        }

        foreach ($row->c as $cell) {
            $column = preg_replace('/\d+/', '', (string) $cell['r']);

            if ($column !== 'A' && $column !== 'B') {
                continue;
            }

            $value = $this->readXlsxCellValue($cell, $sharedStrings);

            if ($column === 'A') {
                $email = strtolower(trim($value));
            } else {
                $name = trim($value);
            }
        }

        return [
            'email' => $email,
            'name' => $name,
        ];
    }

    /**
     * Read one XLSX cell value from XML.
     *
     * @param \SimpleXMLElement $cell
     * @param SubscriberSharedStringStore $sharedStrings
     * @return string
     */
    private function readXlsxCellValue(\SimpleXMLElement $cell, SubscriberSharedStringStore $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            return $sharedStrings->get((int) $cell->v);
        }

        if ($type === 'inlineStr') {
            $value = '';

            foreach ($cell->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
                $value .= (string) $textNode;
            }

            return $value;
        }

        return (string) ($cell->v ?? '');
    }

    /**
     * Create or update imported subscribers using bulk database operations.
     *
     * @param array $rows
     * @param array $categoryIds
     * @return int
     */
    private function importSubscriberRows(array $rows, array $categoryIds, array $projectIds): int
    {
        if ($rows === []) {
            return 0;
        }

        abort_unless(DB::table('categories')->whereIn('project_id', $projectIds)->whereIn('id', $categoryIds)->count() === count(array_unique($categoryIds)), 422);

        $normalizedRows = [];

        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $name = trim((string) ($row['name'] ?? ''));

            if (!StringHelper::isEmail($email) || mb_strlen($email) > 255) {
                continue;
            }

            $normalizedRows[$email] = [
                'email' => $email,
                'name' => mb_substr($name, 0, 100),
            ];
        }

        if ($normalizedRows === []) {
            return 0;
        }

        $emails = array_keys($normalizedRows);
        $existingSubscriberIds = DB::table('subscribers')
            ->whereIn('email', $emails)
            ->pluck('id', 'email')
            ->all();

        $newSubscribers = [];
        $now = date('Y-m-d H:i:s');

        foreach ($normalizedRows as $email => $row) {
            if (isset($existingSubscriberIds[$email])) {
                continue;
            }

            $newSubscribers[] = [
                'name' => $row['name'],
                'email' => $email,
                'active' => 1,
                'token' => StringHelper::token(),
                'timeSent' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($newSubscribers, $emails, $categoryIds, $projectIds, $now): void {
            foreach (array_chunk($newSubscribers, self::DATABASE_CHUNK_SIZE) as $chunk) {
                DB::table('subscribers')->insertOrIgnore($chunk);
            }

            $subscriberIds = DB::table('subscribers')
                ->whereIn('email', $emails)
                ->pluck('id')
                ->all();

            foreach ($projectIds as $projectId) {
                foreach (array_chunk($subscriberIds, self::DATABASE_CHUNK_SIZE) as $chunk) {
                    DB::table('project_subscriber')->insertOrIgnore(array_map(fn ($subscriberId) => [
                        'project_id' => $projectId,
                        'subscriber_id' => $subscriberId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $chunk));
                }
            }

            $this->syncSubscriptions($subscriberIds, $categoryIds, $projectIds);
        });

        return count($normalizedRows);
    }

    /**
     * Replace categories in the selected projects while preserving other memberships.
     *
     * @param array $subscriberIds
     * @param array $categoryIds
     * @return void
     */
    private function syncSubscriptions(array $subscriberIds, array $categoryIds, array $projectIds): void
    {
        $subscriberIds = array_values(array_unique(array_filter($subscriberIds)));
        $categoryIds = array_values(array_unique(array_filter($categoryIds, 'is_numeric')));

        if ($subscriberIds === []) {
            return;
        }

        DB::table('subscriptions')
            ->whereIn('subscriber_id', $subscriberIds)
            ->whereIn('category_id', DB::table('categories')->whereIn('project_id', $projectIds)->select('id'))
            ->delete();

        if ($categoryIds === []) {
            return;
        }

        $rows = [];

        foreach ($subscriberIds as $subscriberId) {
            foreach ($categoryIds as $categoryId) {
                $rows[] = [
                    'subscriber_id' => $subscriberId,
                    'category_id' => (int) $categoryId,
                ];
            }
        }

        foreach (array_chunk($rows, self::DATABASE_CHUNK_SIZE) as $chunk) {
            DB::table('subscriptions')->insertOrIgnore($chunk);
        }
    }
}

final class SubscriberImportReadFilter implements IReadFilter
{
    /**
     * Initialize a spreadsheet filter for one inclusive row range.
     */
    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow,
    ) {
    }

    /**
     * Read only the email and name columns for the current chunk.
     *
     * @param string $columnAddress
     * @param int $row
     * @param string $worksheetName
     * @return bool
     */
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return in_array($columnAddress, ['A', 'B'], true)
            && $row >= $this->startRow
            && $row <= $this->endRow;
    }
}

final class SubscriberSharedStringStore
{
    private const INDEX_RECORD_SIZE = 8;
    private const MAX_MEMORY_STRINGS = 200000;

    private ?string $indexFile = null;
    private ?string $dataFile = null;

    /** @var array<int, string>|null */
    private ?array $strings = null;

    /** @var resource|null */
    private $indexHandle = null;

    /** @var resource|null */
    private $dataHandle = null;

    /**
     * Initialize an in-memory or disk-backed shared-string store.
     */
    private function __construct(bool $useMemory)
    {
        if ($useMemory) {
            $this->strings = [];

            return;
        }

        $this->indexFile = (string) tempnam(sys_get_temp_dir(), 'xlsx_sst_index');
        $this->dataFile = (string) tempnam(sys_get_temp_dir(), 'xlsx_sst_data');
        $this->indexHandle = fopen($this->indexFile, 'w+b');
        $this->dataHandle = fopen($this->dataFile, 'w+b');

        if ($this->indexHandle === false || $this->dataHandle === false) {
            $this->close();
            throw new \RuntimeException('Failed to create shared string temporary files.');
        }
    }

    /**
     * Build a disk-backed shared string lookup for an XLSX file.
     *
     * @param string $file
     * @return self
     */
    public static function create(string $file): self
    {
        $store = null;
        $reader = new \XMLReader();
        $zip = new \ZipArchive();

        if ($zip->open($file) !== true) {
            return new self(true);
        }

        $hasSharedStrings = $zip->locateName('xl/sharedStrings.xml') !== false;
        $zip->close();

        if (!$hasSharedStrings) {
            return new self(true);
        }

        if (!@$reader->open('zip://' . $file . '#xl/sharedStrings.xml')) {
            return new self(true);
        }

        try {
            while ($reader->read()) {
                if ($reader->nodeType === \XMLReader::ELEMENT && $reader->localName === 'sst' && $store === null) {
                    $uniqueCount = (int) ($reader->getAttribute('uniqueCount') ?: $reader->getAttribute('count'));
                    $store = new self($uniqueCount <= self::MAX_MEMORY_STRINGS);
                }

                if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'si') {
                    continue;
                }

                if ($store === null) {
                    $store = new self(true);
                }

                $store->append($store->readSharedString($reader->readOuterXml()));
            }
        } finally {
            $reader->close();
        }

        return $store ?? new self(true);
    }

    /**
     * Return a shared string by its zero-based index.
     *
     * @param int $index
     * @return string
     */
    public function get(int $index): string
    {
        if ($this->strings !== null) {
            return $this->strings[$index] ?? '';
        }

        if ($this->indexHandle === null || $this->dataHandle === null) {
            return '';
        }

        if (fseek($this->indexHandle, $index * self::INDEX_RECORD_SIZE) !== 0) {
            return '';
        }

        $offsetBytes = fread($this->indexHandle, self::INDEX_RECORD_SIZE);

        if ($offsetBytes === false || strlen($offsetBytes) !== self::INDEX_RECORD_SIZE) {
            return '';
        }

        $parts = unpack('Vlow/Vhigh', $offsetBytes);
        $offset = (int) $parts['low'] + ((int) $parts['high'] * 4294967296);

        if (fseek($this->dataHandle, $offset) !== 0) {
            return '';
        }

        $lengthBytes = fread($this->dataHandle, 4);

        if ($lengthBytes === false || strlen($lengthBytes) !== 4) {
            return '';
        }

        $length = (int) unpack('Vlength', $lengthBytes)['length'];

        if ($length === 0) {
            return '';
        }

        return (string) fread($this->dataHandle, $length);
    }

    /**
     * Close handles and remove temporary files.
     *
     * @return void
     */
    public function close(): void
    {
        if (is_resource($this->indexHandle)) {
            fclose($this->indexHandle);
        }

        if (is_resource($this->dataHandle)) {
            fclose($this->dataHandle);
        }

        $this->indexHandle = null;
        $this->dataHandle = null;

        if (isset($this->indexFile)) {
            @unlink($this->indexFile);
        }

        if (isset($this->dataFile)) {
            @unlink($this->dataFile);
        }
    }

    /**
     * Append one shared string to the disk-backed store.
     *
     * @param string $value
     * @return void
     */
    private function append(string $value): void
    {
        if ($this->strings !== null) {
            $this->strings[] = $value;

            return;
        }

        $offset = ftell($this->dataHandle);

        if ($offset === false) {
            return;
        }

        $low = $offset & 0xffffffff;
        $high = intdiv($offset, 4294967296);

        fwrite($this->indexHandle, pack('V2', $low, $high));
        fwrite($this->dataHandle, pack('V', strlen($value)) . $value);
    }

    /**
     * Extract text from one shared string XML fragment.
     *
     * @param string $xml
     * @return string
     */
    private function readSharedString(string $xml): string
    {
        $element = simplexml_load_string($xml);

        if ($element === false) {
            return '';
        }

        $value = '';

        foreach ($element->xpath('.//*[local-name()="t"]') ?: [] as $textNode) {
            $value .= (string) $textNode;
        }

        return $value;
    }
}
