<?php

namespace App\Services;


use App\Helpers\StringHelper;
use App\Models\ReadySent;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DownloadService
{
    private const XLSX_EXT = 'xlsx';
    private const TXT_EXT = 'txt';
    private const EXPORT_CHUNK_SIZE = 10000;
    private const HEADER_FILL_COLOR = 'EE7171';
    private const SUMMARY_FILL_COLOR = 'EEEEEE';

    /**
     * Stream an XLSX delivery report for the specified mailing log.
     *
     * @param int $id
     * @return Response|StreamedResponse
     */
    public function log(int $id): Response|StreamedResponse
    {
        $rowsExist = ReadySent::query()
            ->where('log_id', $id)
            ->exists();

        abort_if(!$rowsExist, 404);

        $stats = $this->buildLogStats($id);
        $filename = 'log' . date('d_m_Y') . '.xlsx';

        return $this->streamLogExcel($filename, $id, $stats);
    }

    /**
     * Stream an XLSX report of visits to the specified tracked URL.
     *
     * @param string $url
     * @return StreamedResponse
     */
    public function redirect(string $url): StreamedResponse
    {
        $decodedUrl = $this->decodeRouteBase64($url);
        $rowsExist = Redirect::query()
            ->where('url', $decodedUrl)
            ->exists();

        abort_if(!$rowsExist, 404);

        $filename = 'redirect_' . date('d_m_Y') . '.xlsx';

        return $this->streamXlsxFile($filename, fn (): string => $this->buildRedirectXlsxFile($decodedUrl));
    }

    /**
     * Export active subscribers in the requested text or spreadsheet format, optionally zipped.
     *
     * @param Request $request
     * @return Response|StreamedResponse
     */
    public function exportSubscribers(Request $request): Response|StreamedResponse
    {
        $this->disableExecutionLimit();

        if ($request->export_type === 'excel') {
            $filename = 'exportEmail_' . date('d_m_Y') . '.' . self::XLSX_EXT;

            if ($request->compress === 'zip') {
                return $this->zipFileResponse(
                    $filename,
                    fn (): string => $this->buildSubscribersXlsxFile($request->categoryId)
                );
            }

            return $this->streamXlsxFile(
                $filename,
                fn (): string => $this->buildSubscribersXlsxFile($request->categoryId)
            );
        }

        if ($request->export_type === 'text') {
            $filename = 'exportEmail_' . date('d_m_Y') . '.' . self::TXT_EXT;

            if ($request->compress === 'zip') {
                return $this->zipFileResponse(
                    $filename,
                    fn (): string => $this->buildSubscribersTextFile($request->categoryId)
                );
            }

            return $this->streamSubscribersTextFile($filename, $request->categoryId);
        }

        throw new InvalidArgumentException('Invalid export type');
    }

    /**
     * Calculate totals, read counts, elapsed time, and success percentage for a mailing log.
     *
     * @param int $logId
     * @return array
     */
    private function buildLogStats(int $logId): array
    {
        $total = ReadySent::query()
            ->where('log_id', $logId)
            ->count();

        $failedCount = ReadySent::query()
            ->where('log_id', $logId)
            ->where('success', 0)
            ->count();

        $readCount = ReadySent::query()
            ->where('log_id', $logId)
            ->where('readMail', 1)
            ->count();

        $timeInfo = ReadySent::query()
            ->selectRaw('sec_to_time(UNIX_TIMESTAMP(max(created_at)) - UNIX_TIMESTAMP(min(created_at))) as totaltime')
            ->where('log_id', $logId)
            ->first();

        $successCount = max($total - $failedCount, 0);
        $successPercent = $total > 0 ? (100 * $successCount / $total) : 0;

        return [
            'total' => $total,
            'read' => $readCount,
            'spent_time' => $timeInfo->totaltime ?? '',
            'success_percent' => $successPercent,
        ];
    }

    /**
     * Stream a memory-safe XLSX delivery report for large mailing logs.
     *
     * @param string $filename
     * @param int $logId
     * @param array $stats
     * @return StreamedResponse
     */
    private function streamLogExcel(string $filename, int $logId, array $stats): StreamedResponse
    {
        return $this->streamXlsxFile($filename, fn (): string => $this->buildLogXlsxFile($logId, $stats));
    }

    /**
     * Stream a generated XLSX file and remove the temporary file afterwards.
     *
     * @param string $filename
     * @param callable $buildFile
     * @return StreamedResponse
     */
    private function streamXlsxFile(string $filename, callable $buildFile): StreamedResponse
    {
        return response()->streamDownload(function () use ($buildFile): void {
            $this->disableExecutionLimit();

            $xlsxFile = $buildFile();

            try {
                readfile($xlsxFile);
            } finally {
                @unlink($xlsxFile);
            }
        }, $filename, [
            'Cache-Control' => 'max-age=0',
            'Content-Type' => StringHelper::getMimeType(self::XLSX_EXT),
        ]);
    }

    /**
     * Build an XLSX file by writing worksheet XML incrementally instead of keeping cells in memory.
     *
     * @param int $logId
     * @param array $stats
     * @return string
     */
    private function buildLogXlsxFile(int $logId, array $stats): string
    {
        return $this->buildXlsxFile(
            function ($sheet) use ($logId, $stats): void {
                $this->writeLogWorksheet($sheet, $logId, $stats);
            },
            'Log'
        );
    }

    /**
     * Build a memory-safe XLSX redirect report.
     *
     * @param string $url
     * @return string
     */
    private function buildRedirectXlsxFile(string $url): string
    {
        return $this->buildXlsxFile(
            function ($sheet) use ($url): void {
                $this->writeRedirectWorksheet($sheet, $url);
            },
            'Redirect'
        );
    }

    /**
     * Build a memory-safe XLSX subscriber export.
     *
     * @param array|null $categoryIds
     * @return string
     */
    private function buildSubscribersXlsxFile(?array $categoryIds): string
    {
        return $this->buildXlsxFile(
            function ($sheet) use ($categoryIds): void {
                $this->writeSubscribersWorksheet($sheet, $categoryIds);
            },
            'Subscribers'
        );
    }

    /**
     * Build an XLSX file by writing worksheet XML incrementally instead of keeping cells in memory.
     *
     * @param callable $writeWorksheet
     * @param string $sheetName
     * @return string
     */
    private function buildXlsxFile(callable $writeWorksheet, string $sheetName): string
    {
        $this->disableExecutionLimit();

        $sheetFile = $this->createTempFile('sheet');
        $xlsxFile = $this->createTempFile('xlsx');

        $sheet = fopen($sheetFile, 'wb');

        if ($sheet === false) {
            @unlink($sheetFile);
            @unlink($xlsxFile);
            throw new \RuntimeException('Failed to create temporary worksheet file.');
        }

        try {
            $writeWorksheet($sheet);
        } finally {
            fclose($sheet);
        }

        $zip = new ZipArchive();

        if ($zip->open($xlsxFile, ZipArchive::OVERWRITE) !== true) {
            @unlink($sheetFile);
            @unlink($xlsxFile);
            throw new \RuntimeException('Failed to create XLSX archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypesXml());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRelsXml());
        $zip->addFromString('docProps/core.xml', $this->xlsxCorePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->xlsxAppPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
        $zip->addFile($sheetFile, 'xl/worksheets/sheet1.xml');
        $this->setFastZipCompression($zip, 'xl/worksheets/sheet1.xml');
        $zip->close();

        @unlink($sheetFile);

        return $xlsxFile;
    }

    /**
     * Write the delivery report worksheet XML one row at a time.
     *
     * @param resource $sheet
     * @param int $logId
     * @param array $stats
     * @return void
     */
    private function writeLogWorksheet($sheet, int $logId, array $stats): void
    {
        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        fwrite($sheet, '<dimension ref="A1:F' . ($stats['total'] + 2) . '"/>');
        fwrite($sheet, '<cols><col min="1" max="1" width="30" customWidth="1"/><col min="2" max="2" width="25" customWidth="1"/><col min="3" max="3" width="15" customWidth="1"/><col min="4" max="4" width="15" customWidth="1"/><col min="5" max="5" width="10" customWidth="1"/><col min="6" max="6" width="35" customWidth="1"/></cols>');
        fwrite($sheet, '<sheetData>');

        $summary = __('frontend.str.total') . ': ' . $stats['total'] . "\n"
            . __('frontend.str.sent') . ': ' . $stats['success_percent'] . "%\n"
            . __('frontend.str.spent_time') . ': ' . $stats['spent_time'] . "\n"
            . __('frontend.str.read') . ': ' . $stats['read'];

        fwrite($sheet, '<row r="1" ht="70" customHeight="1">');
        $this->writeInlineCell($sheet, 'A', 1, $summary, 1);
        fwrite($sheet, '</row>');

        fwrite($sheet, '<row r="2">');
        $this->writeInlineCell($sheet, 'A', 2, __('frontend.str.newsletter'), 2);
        $this->writeInlineCell($sheet, 'B', 2, __('frontend.str.email'), 2);
        $this->writeInlineCell($sheet, 'C', 2, __('frontend.str.time'), 2);
        $this->writeInlineCell($sheet, 'D', 2, __('frontend.str.status'), 2);
        $this->writeInlineCell($sheet, 'E', 2, __('frontend.str.read'), 2);
        $this->writeInlineCell($sheet, 'F', 2, __('frontend.str.error'), 2);
        fwrite($sheet, '</row>');

        $rowIndex = 2;

        ReadySent::query()
            ->select(['id', 'template', 'email', 'created_at', 'success', 'readMail', 'errorMsg'])
            ->where('log_id', $logId)
            ->orderBy('id')
            ->chunkById(2000, function ($rows) use ($sheet, &$rowIndex): void {
                foreach ($rows as $row) {
                    $rowIndex++;

                    fwrite($sheet, '<row r="' . $rowIndex . '">');
                    $this->writeInlineCell($sheet, 'A', $rowIndex, (string) $row->template);
                    $this->writeInlineCell($sheet, 'B', $rowIndex, (string) $row->email);
                    $this->writeInlineCell($sheet, 'C', $rowIndex, (string) $row->created_at);
                    $this->writeInlineCell(
                        $sheet,
                        'D',
                        $rowIndex,
                        $row->success == 1 ? __('frontend.str.send_status_yes') : __('frontend.str.send_status_no'),
                        3
                    );
                    $this->writeInlineCell(
                        $sheet,
                        'E',
                        $rowIndex,
                        $row->readMail == 1 ? __('frontend.str.yes') : __('frontend.str.no'),
                        3
                    );
                    $this->writeInlineCell($sheet, 'F', $rowIndex, (string) $row->errorMsg);
                    fwrite($sheet, '</row>');
                }
            });

        fwrite($sheet, '</sheetData>');
        fwrite($sheet, '<mergeCells count="1"><mergeCell ref="A1:F1"/></mergeCells>');
        fwrite($sheet, '</worksheet>');
    }

    /**
     * Write redirect tracking rows to worksheet XML in chunks.
     *
     * @param resource $sheet
     * @param string $url
     * @return void
     */
    private function writeRedirectWorksheet($sheet, string $url): void
    {
        $total = Redirect::query()
            ->where('url', $url)
            ->count();

        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        fwrite($sheet, '<dimension ref="A1:B' . ($total + 1) . '"/>');
        fwrite($sheet, '<cols><col min="1" max="1" width="35" customWidth="1"/><col min="2" max="2" width="22" customWidth="1"/></cols>');
        fwrite($sheet, '<sheetData>');

        fwrite($sheet, '<row r="1">');
        $this->writeInlineCell($sheet, 'A', 1, 'Email', 2);
        $this->writeInlineCell($sheet, 'B', 1, 'Time', 2);
        fwrite($sheet, '</row>');

        $rowIndex = 1;

        Redirect::query()
            ->select(['id', 'email', 'created_at'])
            ->where('url', $url)
            ->orderBy('id')
            ->chunkById(2000, function ($rows) use ($sheet, &$rowIndex): void {
                foreach ($rows as $row) {
                    $rowIndex++;

                    fwrite($sheet, '<row r="' . $rowIndex . '">');
                    $this->writeInlineCell($sheet, 'A', $rowIndex, (string) $row->email);
                    $this->writeInlineCell($sheet, 'B', $rowIndex, (string) $row->created_at);
                    fwrite($sheet, '</row>');
                }
            });

        fwrite($sheet, '</sheetData>');
        fwrite($sheet, '</worksheet>');
    }

    /**
     * Write subscriber export rows to worksheet XML in chunks.
     *
     * @param resource $sheet
     * @param array|null $categoryIds
     * @return void
     */
    private function writeSubscribersWorksheet($sheet, ?array $categoryIds): void
    {
        $this->disableExecutionLimit();

        $query = $this->getSubscribersQuery($categoryIds);
        $total = (clone $query)->count();

        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($sheet, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        fwrite($sheet, '<dimension ref="A1:B' . ($total + 1) . '"/>');
        fwrite($sheet, '<cols><col min="1" max="1" width="35" customWidth="1"/><col min="2" max="2" width="30" customWidth="1"/></cols>');
        fwrite($sheet, '<sheetData>');

        fwrite($sheet, '<row r="1">');
        $this->writeInlineCell($sheet, 'A', 1, 'Email', 2);
        $this->writeInlineCell($sheet, 'B', 1, 'Name', 2);
        fwrite($sheet, '</row>');

        $rowIndex = 1;

        (clone $query)
            ->orderBy('subscribers.id')
            ->chunkById(10000, function ($subscribers) use ($sheet, &$rowIndex): void {
                $buffer = '';

                foreach ($subscribers as $subscriber) {
                    $rowIndex++;

                    $buffer .= '<row r="' . $rowIndex . '">'
                        . $this->inlineCellXml('A', $rowIndex, (string) $subscriber->email)
                        . $this->inlineCellXml('B', $rowIndex, (string) $subscriber->name)
                        . '</row>';
                }

                fwrite($sheet, $buffer);
            }, 'subscribers.id', 'id');

        fwrite($sheet, '</sheetData>');
        fwrite($sheet, '</worksheet>');
    }

    /**
     * Write one inline string cell to the worksheet XML.
     *
     * @param resource $sheet
     * @param string $column
     * @param int $row
     * @param string $value
     * @param int|null $style
     * @return void
     */
    private function writeInlineCell($sheet, string $column, int $row, string $value, ?int $style = null): void
    {
        fwrite($sheet, $this->inlineCellXml($column, $row, $value, $style));
    }

    /**
     * Build one inline string cell as worksheet XML.
     *
     * @param string $column
     * @param int $row
     * @param string $value
     * @param int|null $style
     * @return string
     */
    private function inlineCellXml(string $column, int $row, string $value, ?int $style = null): string
    {
        $styleAttribute = $style !== null ? ' s="' . $style . '"' : '';
        $spaceAttribute = preg_match('/^\s|\s$/u', $value) ? ' xml:space="preserve"' : '';

        return '<c r="' . $column . $row . '"' . $styleAttribute . ' t="inlineStr"><is><t' . $spaceAttribute . '>'
            . $this->xmlEscape($value)
            . '</t></is></c>';
    }

    /**
     * Create a temporary file path and reserve it for writing.
     *
     * @param string $prefix
     * @return string
     */
    private function createTempFile(string $prefix): string
    {
        $file = tempnam(sys_get_temp_dir(), $prefix);

        if ($file === false) {
            throw new \RuntimeException('Failed to create temporary file.');
        }

        return $file;
    }

    /**
     * Disable PHP execution timeout for large export streams.
     *
     * @return void
     */
    private function disableExecutionLimit(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
    }

    /**
     * Prefer faster compression for large worksheet XML files.
     *
     * @param ZipArchive $zip
     * @param string $name
     * @return void
     */
    private function setFastZipCompression(ZipArchive $zip, string $name): void
    {
        if (method_exists($zip, 'setCompressionName')) {
            $zip->setCompressionName($name, ZipArchive::CM_DEFLATE, 1);
        }
    }

    /**
     * Escape text for safe XML output.
     *
     * @param string $value
     * @return string
     */
    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * Decode a URL-safe or regular base64 route parameter.
     *
     * @param string $value
     * @return string
     */
    private function decodeRouteBase64(string $value): string
    {
        $normalized = strtr($value, '-_', '+/');
        $padding = strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($normalized, true) ?: '';
    }

    /**
     * Return the XLSX content type manifest.
     *
     * @return string
     */
    private function xlsxContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    /**
     * Return root package relationships for the XLSX archive.
     *
     * @return string
     */
    private function xlsxRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    /**
     * Return workbook XML for a single-sheet XLSX archive.
     *
     * @return string
     */
    private function xlsxWorkbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->xmlEscape($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    /**
     * Return workbook relationships for the worksheet and styles.
     *
     * @return string
     */
    private function xlsxWorkbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /**
     * Return minimal XLSX style definitions used by the log report.
     *
     * @return string
     */
    private function xlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEEEEEE"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEE7171"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="0" fillId="2" borderId="0" xfId="0" applyFill="1" applyAlignment="1"><alignment wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="3" borderId="0" xfId="0" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center"/></xf></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /**
     * Return core document properties for the XLSX archive.
     *
     * @return string
     */
    private function xlsxCorePropertiesXml(): string
    {
        $created = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Alexander Yanitsky</dc:creator>'
            . '<cp:lastModifiedBy>PHP Newsletter</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    /**
     * Return extended document properties for the XLSX archive.
     *
     * @return string
     */
    private function xlsxAppPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>PHP Newsletter</Application>'
            . '</Properties>';
    }

    /**
     * Configure spreadsheet metadata for a generated mailing-log workbook.
     *
     * @param Spreadsheet $spreadsheet
     * @return void
     */
    private function configureLogProperties(Spreadsheet $spreadsheet): void
    {
        $spreadsheet->getProperties()
            ->setCreator('Alexander Yanitsky')
            ->setLastModifiedBy('PHP Newsletter')
            ->setTitle(__('frontend.str.log'))
            ->setSubject('Office 2007 XLSX Document')
            ->setDescription('Document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Log file');
    }

    /**
     * Populate the report summary and column headings on a mailing-log worksheet.
     *
     * @param $sheet
     * @param array $stats
     * @return void
     */
    private function fillLogHeader($sheet, array $stats): void
    {
        $summary = __('frontend.str.total') . ': ' . $stats['total'] . "\n"
            . __('frontend.str.sent') . ': ' . $stats['success_percent'] . "%\n"
            . __('frontend.str.spent_time') . ': ' . $stats['spent_time'] . "\n"
            . __('frontend.str.read') . ': ' . $stats['read'];

        $sheet
            ->setCellValue('A1', $summary)
            ->setCellValue('A2', __('frontend.str.newsletter'))
            ->setCellValue('B2', __('frontend.str.email'))
            ->setCellValue('C2', __('frontend.str.time'))
            ->setCellValue('D2', __('frontend.str.status'))
            ->setCellValue('E2', __('frontend.str.read'))
            ->setCellValue('F2', __('frontend.str.error'));

        $sheet->mergeCells('A1:F1');
    }

    /**
     * Append delivery-detail rows to a mailing-log worksheet.
     *
     * @param $sheet
     * @param Collection $rows
     * @return void
     */
    private function fillLogRows($sheet, Collection $rows): void
    {
        $rowIndex = 2;

        foreach ($rows as $row) {
            $rowIndex++;

            $sheet
                ->setCellValue('A' . $rowIndex, $row->template)
                ->setCellValue('B' . $rowIndex, $row->email)
                ->setCellValue('C' . $rowIndex, $row->created_at)
                ->setCellValue(
                    'D' . $rowIndex,
                    $row->success == 1
                        ? __('frontend.str.send_status_yes')
                        : __('frontend.str.send_status_no')
                )
                ->setCellValue(
                    'E' . $rowIndex,
                    $row->readMail == 1
                        ? __('frontend.str.yes')
                        : __('frontend.str.no')
                )
                ->setCellValue('F' . $rowIndex, $row->errorMsg);

            $this->setHorizontalCenter($sheet, ['D' . $rowIndex, 'E' . $rowIndex]);
        }
    }

    /**
     * Apply dimensions, fills, wrapping, and alignment to a mailing-log worksheet.
     *
     * @param $sheet
     * @return void
     */
    private function formatLogSheet($sheet): void
    {
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $this->applyFill($sheet, 'A1', self::SUMMARY_FILL_COLOR);

        $headerCells = ['A2', 'B2', 'C2', 'D2', 'E2', 'F2'];

        foreach ($headerCells as $cell) {
            $this->applyFill($sheet, $cell, self::HEADER_FILL_COLOR);
        }

        $this->setHorizontalCenter($sheet, $headerCells);

        $sheet->getRowDimension(1)->setRowHeight(70);
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(35);
    }

    /**
     * Apply a solid background color to one spreadsheet cell.
     *
     * @param $sheet
     * @param string $cell
     * @param string $rgb
     * @return void
     */
    private function applyFill($sheet, string $cell, string $rgb): void
    {
        $sheet->getStyle($cell)->getFill()->applyFromArray([
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => $rgb],
        ]);
    }

    /**
     * Center the contents of the supplied spreadsheet cells horizontally.
     *
     * @param $sheet
     * @param array $cells
     * @return void
     */
    private function setHorizontalCenter($sheet, array $cells): void
    {
        foreach ($cells as $cell) {
            $sheet->getStyle($cell)->getAlignment()->applyFromArray([
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ]);
        }
    }

    /**
     * Render a subscriber collection into in-memory text or XLSX contents.
     *
     * @param Collection $subscribers
     * @param string $type
     * @return array|string[]
     */
    private function buildSubscribersFile(Collection $subscribers, string $type): array
    {
        if ($type === 'text') {
            $contents = '';

            foreach ($subscribers as $subscriber) {
                $contents .= "{$subscriber->email} {$subscriber->name}\n";
            }

            return [$contents, 'txt'];
        }

        if ($type === 'excel') {
            $contents = $this->renderExcel(function (Spreadsheet $spreadsheet) use ($subscribers): void {
                $sheet = $spreadsheet->getActiveSheet();

                $sheet
                    ->setCellValue('A1', 'Email')
                    ->setCellValue('B1', 'Name');

                $rowIndex = 1;

                foreach ($subscribers as $subscriber) {
                    $rowIndex++;

                    $sheet
                        ->setCellValue('A' . $rowIndex, $subscriber->email)
                        ->setCellValue('B' . $rowIndex, $subscriber->name);
                }
            });

            return [$contents, self::XLSX_EXT];
        }

        throw new InvalidArgumentException('Invalid export type');
    }

    /**
     * Stream subscribers as a plain text file without loading all rows into memory.
     *
     * @param string $filename
     * @param array|null $ids
     * @return StreamedResponse
     */
    private function streamSubscribersTextFile(string $filename, ?array $ids): StreamedResponse
    {
        return response()->streamDownload(function () use ($ids): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                throw new \RuntimeException('Failed to open output stream.');
            }

            $this->writeSubscribersTextRows($handle, $ids);
        }, $filename, [
            'Content-Type' => StringHelper::getMimeType(self::TXT_EXT),
        ]);
    }

    /**
     * Build a temporary text file for ZIP exports.
     *
     * @param array|null $ids
     * @return string
     */
    private function buildSubscribersTextFile(?array $ids): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'subscribers_txt_');

        if ($tmpFile === false) {
            throw new \RuntimeException('Failed to create temporary file.');
        }

        $handle = fopen($tmpFile, 'wb');

        if ($handle === false) {
            @unlink($tmpFile);
            throw new \RuntimeException('Failed to open temporary file.');
        }

        try {
            $this->writeSubscribersTextRows($handle, $ids);
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($tmpFile);

            throw $e;
        }

        fclose($handle);

        return $tmpFile;
    }

    /**
     * Write active subscribers in chunks to the provided stream handle.
     *
     * @param resource $handle
     * @param array|null $ids
     * @return void
     */
    private function writeSubscribersTextRows($handle, ?array $ids): void
    {
        $this->getSubscribersQuery($ids)
            ->chunkById(self::EXPORT_CHUNK_SIZE, function (Collection $subscribers) use ($handle): void {
                foreach ($subscribers as $subscriber) {
                    fwrite($handle, $this->formatSubscriberTextLine($subscriber));
                }

                if (ob_get_level() > 0) {
                    @ob_flush();
                }

                flush();
            }, 'subscribers.id', 'id');
    }

    /**
     * Format one subscriber line for text export.
     *
     * @param object $subscriber
     * @return string
     */
    private function formatSubscriberTextLine(object $subscriber): string
    {
        $email = trim((string) $subscriber->email);
        $name = trim(str_replace(["\r", "\n"], ' ', (string) $subscriber->name));

        return rtrim($email . ' ' . $name) . "\n";
    }

    /**
     * Stream a ZIP archive containing the supplied in-memory export contents.
     *
     * @param string $contents
     * @param string $innerFilename
     * @return StreamedResponse
     */
    private function zipResponse(string $contents, string $innerFilename): StreamedResponse
    {
        $zipFilename = pathinfo($innerFilename, PATHINFO_FILENAME) . '.zip';

        return response()->streamDownload(function () use ($contents, $innerFilename): void {
            $zip = new ZipArchive();
            $tmpFile = tempnam(sys_get_temp_dir(), 'zip');

            if ($tmpFile === false) {
                throw new \RuntimeException('Failed to create temporary file.');
            }

            if ($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
                @unlink($tmpFile);
                throw new \RuntimeException('Failed to create zip archive.');
            }

            $zip->addFromString($innerFilename, $contents);
            $zip->close();

            readfile($tmpFile);
            @unlink($tmpFile);
        }, $zipFilename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Stream a zip archive containing a generated temporary file.
     *
     * @param string $innerFilename
     * @param callable $buildFile
     * @return StreamedResponse
     */
    private function zipFileResponse(string $innerFilename, callable $buildFile): StreamedResponse
    {
        $zipFilename = pathinfo($innerFilename, PATHINFO_FILENAME) . '.zip';

        return response()->streamDownload(function () use ($innerFilename, $buildFile): void {
            $sourceFile = $buildFile();
            $zip = new ZipArchive();
            $tmpFile = tempnam(sys_get_temp_dir(), 'zip');

            if ($tmpFile === false) {
                @unlink($sourceFile);
                throw new \RuntimeException('Failed to create temporary file.');
            }

            if ($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
                @unlink($sourceFile);
                @unlink($tmpFile);
                throw new \RuntimeException('Failed to create zip archive.');
            }

            $zip->addFile($sourceFile, $innerFilename);
            $zip->close();

            try {
                readfile($tmpFile);
            } finally {
                @unlink($sourceFile);
                @unlink($tmpFile);
            }
        }, $zipFilename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Stream an XLSX workbook generated by the supplied spreadsheet callback.
     *
     * @param string $filename
     * @param callable $callback
     * @return StreamedResponse
     */
    private function streamExcel(string $filename, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($callback): void {
            $spreadsheet = new Spreadsheet();
            $callback($spreadsheet);

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => StringHelper::getMimeType(self::XLSX_EXT),
        ]);
    }

    /**
     * Return an XLSX download response generated by the supplied spreadsheet callback.
     *
     * @param string $filename
     * @param callable $callback
     * @return Response
     */
    private function excelResponse(string $filename, callable $callback): Response
    {
        $contents = $this->renderExcel($callback);

        return response($contents, 200, [
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Cache-Control' => 'max-age=0',
            'Content-Type' => StringHelper::getMimeType(self::XLSX_EXT),
        ]);
    }

    /**
     * Render an XLSX workbook to a binary string.
     *
     * @param callable $callback
     * @return string
     */
    private function renderExcel(callable $callback): string
    {
        $spreadsheet = new Spreadsheet();
        $callback($spreadsheet);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * Materialize the active subscriber export query as a collection.
     *
     * @param array|null $ids
     * @return Collection
     */
    private function getSubscribersList(?array $ids): Collection
    {
        return $this->getSubscribersQuery($ids)->get();
    }

    /**
     * Build the active subscribers query used by text and XLSX exports.
     *
     * @param array|null $ids
     * @return QueryBuilder
     */
    private function getSubscribersQuery(?array $ids): QueryBuilder
    {
        $query = DB::table('subscribers')
            ->select('subscribers.id', 'subscribers.name', 'subscribers.email')
            ->where('subscribers.active', 1);

        if ($ids) {
            $query->whereExists(function (QueryBuilder $subquery) use ($ids): void {
                $subquery
                    ->selectRaw('1')
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.subscriber_id', 'subscribers.id')
                    ->whereIn('subscriptions.category_id', $ids);
            });
        }

        return $query;
    }
}
