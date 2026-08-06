<?php

namespace App\Support\Excel;

use RuntimeException;
use ZipArchive;

class SimpleXlsxWorkbook
{
    /** @param array<int, array{title:string, rows:array<int, array<int, mixed>>}> $sheets */
    public function save(string $path, array $sheets): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create workbook.');
        }
        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->rels());
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->styles());
        foreach (array_values($sheets) as $index => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $this->worksheet($sheet['rows']));
        }
        $zip->close();
    }

    private function worksheet(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetData>';
        foreach (array_values($rows) as $r => $row) {
            $xml .= '<row r="'.($r + 1).'">';
            foreach (array_values($row) as $c => $value) {
                $ref = $this->column($c + 1).($r + 1);
                $style = $r === 0 ? ' s="1"' : '';
                if ($this->isNumericCell($value)) {
                    $xml .= '<c r="'.$ref.'"'.$style.'><v>'.htmlspecialchars((string) $value, ENT_XML1).'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'" t="inlineStr"'.$style.'><is><t>'.htmlspecialchars($this->safeText($value), ENT_XML1).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        return $xml.'</sheetData><autoFilter ref="A1:Z'.max(1, count($rows)).'"/></worksheet>';
    }

    private function column(int $number): string
    {
        $name = '';
        while ($number > 0) { $mod = ($number - 1) % 26; $name = chr(65 + $mod).$name; $number = intdiv($number - $mod, 26) - 1; }
        return $name;
    }

    private function workbook(array $sheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach (array_values($sheets) as $i => $sheet) {
            $xml .= '<sheet name="'.htmlspecialchars($this->sheetName($sheet['title']), ENT_XML1).'" sheetId="'.($i + 1).'" r:id="rId'.($i + 1).'"/>';
        }
        return $xml.'</sheets></workbook>';
    }

    private function workbookRels(int $count): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $count; $i++) $xml .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        return $xml.'<Relationship Id="rId'.($count + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function contentTypes(int $count): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $count; $i++) $xml .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        return $xml.'</Types>';
    }

    private function isNumericCell(mixed $value): bool
    {
        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value) && ! preg_match('/^0\d+/', $value));
    }

    private function safeText(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^[=+@]/', $text) || preg_match('/^-(?!\d+(\.\d+)?$)/', $text)
            ? "'".$text
            : $text;
    }

    private function sheetName(string $name): string
    {
        $safe = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $name);
        $safe = trim($safe) ?: 'Sheet';

        return mb_substr($safe, 0, 31);
    }

    private function rels(): string { return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>'; }
    private function styles(): string { return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font/><font><b/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs></styleSheet>'; }
}
