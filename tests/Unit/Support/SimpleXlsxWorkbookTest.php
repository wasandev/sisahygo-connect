<?php

namespace Tests\Unit\Support;

use App\Support\Excel\SimpleXlsxWorkbook;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SimpleXlsxWorkbookTest extends TestCase
{
    public function test_workbook_zip_xml_unicode_escaping_and_formula_safety(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx-test-').'.xlsx';
        (new SimpleXlsxWorkbook)->save($path, [
            ['title' => 'Summary:/?*[]', 'rows' => [['Field', 'Value'], ['ไทย & <tag> "quote"', '=SUM(A1:A2)'], ['negative text', '-cmd'], ['number', '123.45'], ['empty', null]]],
            ['title' => 'Shipment Details', 'rows' => [['Order', 'Amount'], ['OH-1', '10.00']]],
            ['title' => 'Payment Details', 'rows' => [['Order', 'Token'], ['OH-2', 'safe value']]],
        ]);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml', 'xl/worksheets/sheet2.xml', 'xl/worksheets/sheet3.xml'] as $entry) {
            $this->assertNotFalse($zip->locateName($entry), $entry);
            $xml = $zip->getFromName($entry);
            $this->assertNotFalse($xml);
            $this->assertNotFalse(simplexml_load_string($xml), $entry);
        }

        $workbook = $zip->getFromName('xl/workbook.xml');
        $sheet1 = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('Summary', $workbook);
        $this->assertStringContainsString('ไทย &amp; &lt;tag&gt; "quote"', $sheet1);
        $this->assertStringContainsString("'=SUM(A1:A2)", $sheet1);
        $this->assertStringContainsString("'-cmd", $sheet1);
        $this->assertStringContainsString('<v>123.45</v>', $sheet1);
        $this->assertStringNotContainsString('secret-api-key', $sheet1);
        $this->assertStringNotContainsString('credential', $sheet1);
        $zip->close();
        @unlink($path);
    }
}
