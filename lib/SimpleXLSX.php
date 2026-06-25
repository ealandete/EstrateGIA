<?php

class SimpleXLSX {

    private array $rows = [];
    private array $widths = [];
    private array $numCols = []; // column indices that are numeric
    private int $headerRows = 1;

    /**
     * @param array $rows Data rows (first row = headers)
     * @param array $widths Column widths
     * @param int $headerRows Number of header rows to freeze and style
     * @param array $numCols Indices of columns that should be numeric (not string)
     */
    public function setData(array $rows, array $widths = [], int $headerRows = 1, array $numCols = []): void {
        $this->rows = $rows;
        $this->widths = $widths;
        $this->headerRows = $headerRows;
        $this->numCols = $numCols;
    }

    public function download(string $filename): void {
        $data = $this->rows;
        if (empty($data)) $data = [['Sin datos']];

        // Shared strings only for text columns
        $strings = [];
        $stringMap = [];
        foreach ($data as $ri => $row) {
            foreach ($row as $ci => $val) {
                if (in_array($ci, $this->numCols)) continue;
                $s = (string)$val;
                if (!isset($stringMap[$s])) { $stringMap[$s] = count($strings); $strings[] = $s; }
            }
        }

        // Sheet 1: Mediciones
        $sheetXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            . '<pane ySplit="' . $this->headerRows . '" topLeftCell="A' . ($this->headerRows + 1) . '" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<cols>';
        $headers = $data[0] ?? [];
        foreach ($headers as $ci => $cv) {
            $w = isset($this->widths[$ci]) ? $this->widths[$ci] : min(max(mb_strlen((string)$cv) * 1.3 + 2, 10), 45);
            $sheetXml .= '<col min="' . ($ci + 1) . '" max="' . ($ci + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $sheetXml .= '</cols><sheetData>';
        foreach ($data as $ri => $row) {
            $sheetXml .= '<row r="' . ($ri + 1) . '">';
            foreach ($row as $ci => $val) {
                $ref = self::colLetter($ci) . ($ri + 1);
                $isHeader = $ri < $this->headerRows;
                $isNum = in_array($ci, $this->numCols) && !$isHeader;
                if ($isNum) {
                    $v = is_numeric($val) ? (float)$val : 0;
                    $sheetXml .= '<c r="' . $ref . '"><v>' . $v . '</v></c>';
                } else {
                    $si = $stringMap[(string)$val];
                    $style = $isHeader ? ' s="1"' : '';
                    $sheetXml .= '<c r="' . $ref . '" t="s"' . $style . '><v>' . $si . '</v></c>';
                }
            }
            $sheetXml .= '</row>';
        }
        $sheetXml .= '</sheetData></worksheet>';

        // Shared strings
        $ssXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
        foreach ($strings as $s) {
            $ssXml .= '<si><t>' . self::xmlEscape($s) . '</t></si>';
        }
        $ssXml .= '</sst>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Mediciones" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        // Styles with number format for Colombian decimals (#.##0,00)
        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2">'
            . '<numFmt numFmtId="165" formatCode="#.##0,00"/>'
            . '<numFmt numFmtId="166" formatCode="#.##0,0"/>'
            . '</numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="1A73E8"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '</styleSheet>');

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->addFromString('xl/sharedStrings.xml', $ssXml);
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '.xlsx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    private static function colLetter(int $n): string {
        $l = '';
        while ($n >= 0) {
            $l = chr(($n % 26) + 65) . $l;
            $n = intdiv($n, 26) - 1;
        }
        return $l;
    }

    private static function xmlEscape(string $s): string {
        return str_replace(['&', '"', "'", '<', '>'], ['&amp;', '&quot;', '&apos;', '&lt;', '&gt;'], $s);
    }
}
