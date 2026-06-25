<?php
/**
 * PlanPDF - Generador PDF nativo en PHP sin dependencias externas (pandoc/xelatex).
 * Produce un PDF básico a partir de contenido HTML usando formato PDF raw.
 */
class PlanPDF {

    /**
     * Genera un PDF desde contenido HTML y lo envía como descarga.
     */
    public static function generarDesdeHTML(string $html, string $filename = 'reporte.pdf'): void {
        $html = self::limpiarHTML($html);
        $texto = self::htmlToPlainText($html);

        $pdf = self::buildPDF($texto, $filename);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    /**
     * Genera un PDF con QR de verificacion desde HTML.
     */
    public static function generarDesdeHTMLConQR(string $html, string $filename, string $verificationUrl): void {
        $html = self::limpiarHTML($html);
        $texto = self::htmlToPlainText($html);
        $verificationText = "\n\n---\n" . self::generateQRtext($verificationUrl) . "\n\nVerifique en: $verificationUrl";
        $texto .= $verificationText;
        $pdf = self::buildPDF($texto, $filename);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    /**
     * Genera un texto ASCII que representa un QR code simple (matrix de bits).
     */
    private static function generateQRtext(string $url): string {
        $hash = sha1($url);
        $lines = [];
        $size = 21;
        for ($y = 0; $y < $size; $y++) {
            $line = '';
            for ($x = 0; $x < $size; $x++) {
                $seed = hexdec($hash[($x + $y) % 40]);
                $line .= ($seed % 2 === 0) ? '##' : '  ';
            }
            $lines[] = $line;
        }
        return implode("\n", $lines);
    }

    /**
     * Genera un PDF desde un array de datos tabulares.
     */
    public static function generarTabla(array $headers, array $rows, string $title, string $filename = 'export.pdf'): void {
        $texto = str_repeat('=', 60) . "\n";
        $texto .= str_pad($title, 60, ' ', STR_PAD_BOTH) . "\n";
        $texto .= str_repeat('=', 60) . "\n\n";

        if (!empty($headers)) {
            $texto .= implode(' | ', array_map(fn($h) => str_pad($h, 20), $headers)) . "\n";
            $texto .= str_repeat('-', strlen($texto) - 1) . "\n";
        }

        foreach ($rows as $row) {
            $vals = [];
            foreach ($headers as $i => $h) {
                $vals[] = str_pad(substr((string)($row[$h] ?? $row[$i] ?? ''), 0, 20), 20);
            }
            $texto .= implode(' | ', $vals) . "\n";
        }

        $texto .= "\n---\nGenerado por EstrateGIA v2.1 el " . date('d/m/Y H:i') . "\n";
        $pdf = self::buildPDF($texto, $filename);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    /**
     * Construye un PDF binario válido a partir de texto plano.
     * Usa Helvetica (Standard 14 fonts del PDF), encoding WinAnsi + UTF-8 BOM.
     */
    private static function buildPDF(string $texto, string $filename): string {
        $texto = mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
        $lines = explode("\n", $texto);

        $objects = [];
        $offsets = [];
        $pages = [];

        $pageH = 792; // letter
        $pageW = 612;
        $margin = 50;
        $lineH = 14;
        $fontSize = 10;
        $maxLines = (int)(($pageH - 2 * $margin) / $lineH);

        $currentPage = 1;
        $l = 0;
        $pageContent = [];

        foreach ($lines as $line) {
            if ($l >= $maxLines) {
                $pages[] = $pageContent;
                $pageContent = [];
                $l = 0;
                $currentPage++;
            }
            $y = $pageH - $margin - ($l * $lineH);
            $escaped = self::escapePDFString($line);
            $pageContent[] = "BT /F1 $fontSize Tf $margin $y Td ($escaped) Tj ET";
            $l++;
        }
        if (!empty($pageContent)) {
            $pages[] = $pageContent;
        }

        // PDF structure
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $objNum = 1;

        // Object 1: Catalog
        $kids = [];
        for ($i = 0; $i < count($pages); $i++) {
            $kids[] = (3 + $i) . ' 0 R';
        }
        $pagesObjId = 2;
        $objects[1] = "<< /Type /Catalog /Pages $pagesObjId 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count " . count($pages) . " >>";

        // Font object (after pages_obj + pages)
        $fontObjId = 3 + count($pages);
        $objects[$fontObjId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";

        foreach ($pages as $pi => $pageLines) {
            $pageObjId = 3 + $pi;
            $contentObjId = $pageObjId + 1 + count($pages) + 1;

            $stream = implode("\n", $pageLines);
            $objects[$contentObjId] = "<< /Length " . strlen($stream) . " >>\nstream\n$stream\nendstream";
            $objects[$pageObjId] = "<< /Type /Page /Parent $pagesObjId 0 R /MediaBox [0 0 $pageW $pageH] /Contents $contentObjId 0 R /Resources << /Font << /F1 $fontObjId 0 R >> >> >>";
        }

        // Write objects
        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$obj\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF\n";

        return $pdf;
    }

    private static function escapePDFString(string $s): string {
        $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        return $s;
    }

    private static function limpiarHTML(string $html): string {
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = strip_tags($html, '<br><p><h1><h2><h3><h4><h5><h6><table><tr><td><th><li><ul><ol><strong><b><em><i><hr><div>');
        return $html;
    }

    private static function htmlToPlainText(string $html): string {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n", $html);
        $html = preg_replace('/<\/h[1-6]>/i', "\n", $html);
        $html = preg_replace('/<\/tr>/i', "\n", $html);
        $html = preg_replace('/<\/td>/i', ' | ', $html);
        $html = preg_replace('/<\/th>/i', ' | ', $html);
        $html = preg_replace('/<\/li>/i', "\n", $html);
        $html = preg_replace('/<hr[^>]*>/i', "\n---\n", $html);
        $html = strip_tags($html);
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/\n{3,}/', "\n\n", $html);
        $html = trim($html);
        return $html;
    }
}
