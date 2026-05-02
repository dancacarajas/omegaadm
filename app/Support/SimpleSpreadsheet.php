<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class SimpleSpreadsheet
{
    public static function xlsx(string $filename, array $headers, array $example = [])
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();

        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Nao foi possivel criar a planilha.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::worksheet([$headers, $example]));
        $zip->close();

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public static function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            return self::readCsv($file->getRealPath());
        }

        if (! in_array($extension, ['xlsx', 'xlsm'], true)) {
            throw new RuntimeException('Use uma planilha .xlsx ou .csv.');
        }

        return self::readXlsx($file->getRealPath());
    }

    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $rows = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) === 1) {
                $row = str_getcsv($row[0], ',');
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private static function readXlsx(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Nao foi possivel abrir a planilha.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = new SimpleXMLElement($sharedXml);
            $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            foreach ($xml->xpath('//m:si') ?: [] as $si) {
                $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $texts = collect($si->xpath('.//m:t') ?: [])->map(fn ($text) => (string) $text)->implode('');
                $sharedStrings[] = trim($texts);
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('A primeira aba da planilha nao foi encontrada.');
        }

        $xml = new SimpleXMLElement($sheetXml);
        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rowNodes = $xml->xpath('//m:sheetData/m:row') ?: [];
        $rows = [];

        foreach ($rowNodes as $row) {
            $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $values = [];

            foreach ($row->xpath('m:c') ?: [] as $cell) {
                $ref = (string) $cell['r'];
                $index = $ref !== ''
                    ? self::columnIndex(preg_replace('/\d+/', '', $ref))
                    : count($values);
                $type = (string) $cell['t'];
                $value = '';

                if ($type === 's') {
                    $value = $sharedStrings[(int) self::cellValue($cell)] ?? '';
                } elseif ($type === 'inlineStr') {
                    $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    $value = collect($cell->xpath('.//m:t') ?: [])->map(fn ($text) => (string) $text)->implode('');
                } else {
                    $value = self::cellValue($cell);
                }

                $values[$index] = trim($value);
            }

            if ($values !== []) {
                ksort($values);
                $rows[] = array_replace(array_fill(0, max(array_keys($values)) + 1, ''), $values);
            }
        }

        return $rows;
    }

    private static function cellValue(SimpleXMLElement $cell): string
    {
        $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $values = $cell->xpath('m:v') ?: [];

        return isset($values[0]) ? (string) $values[0] : '';
    }

    private static function worksheet(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="'.($rowIndex + 1).'">';

            foreach (array_values($row) as $columnIndex => $value) {
                $cell = self::columnName($columnIndex + 1).($rowIndex + 1);
                $style = $rowIndex === 0 ? ' s="1"' : '';
                $xml .= '<c r="'.$cell.'" t="inlineStr"'.$style.'><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private static function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + ord(strtoupper($letter)) - 64;
        }

        return $index - 1;
    }

    private static function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Efetivo" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs></styleSheet>';
    }
}
