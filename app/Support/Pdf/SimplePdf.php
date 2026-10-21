<?php

namespace App\Support\Pdf;

/**
 * PDF mínimo sin dependencias.
 *
 * - 1 página A4
 * - Helvetica (built-in)
 * - Texto / líneas / rectángulos
 */
final class SimplePdf
{
    private int $w;
    private int $h;

    /** @var string[] */
    private array $ops = [];

    public function __construct(int $width = 595, int $height = 842)
    {
        $this->w = $width;
        $this->h = $height;
    }

    private function yFromTop(float $yTop): float
    {
        return (float)$this->h - $yTop;
    }

    /**
     * Color RGB en 0..255
     */
    private function fmtRgb(?array $rgb): string
    {
        if (!$rgb || count($rgb) < 3) return '0 0 0';
        $r = max(0, min(255, (int)$rgb[0])) / 255;
        $g = max(0, min(255, (int)$rgb[1])) / 255;
        $b = max(0, min(255, (int)$rgb[2])) / 255;
        return sprintf('%.3F %.3F %.3F', $r, $g, $b);
    }

    private function pdfText(string $text): string
    {
        // Convertimos a ISO-8859-1 (WinAnsi) cuando se pueda.
        $t = $text;
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
            if (is_string($conv) && $conv !== '') {
                $t = $conv;
            }
        }
        // Escapes PDF
        $t = str_replace('\\', '\\\\', $t);
        $t = str_replace('(', '\\(', $t);
        $t = str_replace(')', '\\)', $t);
        return $t;
    }

    public function rect(float $x, float $yTop, float $w, float $h, ?array $fillRgb = null, ?array $strokeRgb = null, float $lineWidth = 1.0, bool $fill = true): self
    {
        $y = $this->yFromTop($yTop + $h);
        $this->ops[] = sprintf('%.2F w', $lineWidth);

        if ($strokeRgb) {
            $this->ops[] = $this->fmtRgb($strokeRgb) . ' RG';
        }
        if ($fill && $fillRgb) {
            $this->ops[] = $this->fmtRgb($fillRgb) . ' rg';
        }

        $this->ops[] = sprintf('%.2F %.2F %.2F %.2F re', $x, $y, $w, $h);

        if ($fill && $fillRgb && $strokeRgb) {
            $this->ops[] = 'B';
        } elseif ($fill && $fillRgb) {
            $this->ops[] = 'f';
        } else {
            $this->ops[] = 'S';
        }

        return $this;
    }

    public function line(float $x1, float $yTop1, float $x2, float $yTop2, ?array $strokeRgb = null, float $lineWidth = 1.0): self
    {
        $y1 = $this->yFromTop($yTop1);
        $y2 = $this->yFromTop($yTop2);
        $this->ops[] = sprintf('%.2F w', $lineWidth);
        if ($strokeRgb) {
            $this->ops[] = $this->fmtRgb($strokeRgb) . ' RG';
        }
        $this->ops[] = sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $y1, $x2, $y2);
        return $this;
    }

    /**
     * Escribe texto. Acepta 2 formas:
     * 1) text(x, yTop, "texto", size, rgb)
     * 2) text(x, yTop, size, "texto", rgb, bold)
     */
    public function text(float $x, float $yTop, mixed $textOrSize, mixed $sizeOrText = 10, ?array $rgb = null, bool $bold = false): self
    {
        // Normalizar firma
        if (is_int($textOrSize) || is_float($textOrSize)) {
            $size = (int)$textOrSize;
            $text = (string)$sizeOrText;
        } else {
            $text = (string)$textOrSize;
            $size = (int)$sizeOrText;
        }

        $y = $this->yFromTop($yTop);
        if ($rgb) {
            $this->ops[] = $this->fmtRgb($rgb) . ' rg';
        }
        $txt = $this->pdfText($text);
        $this->ops[] = sprintf('BT /F1 %d Tf %.2F %.2F Td (%s) Tj ET', $size, $x, $y, $txt);
        return $this;
    }

    /**
     * Texto alineado a la derecha (aproximación simple, sin métricas reales).
     */
    public function textRight(float $xRight, float $yTop, mixed $textOrSize, mixed $sizeOrText = 10, ?array $rgb = null, bool $bold = false): self
    {
        if (is_int($textOrSize) || is_float($textOrSize)) {
            $size = (int)$textOrSize;
            $text = (string)$sizeOrText;
        } else {
            $text = (string)$textOrSize;
            $size = (int)$sizeOrText;
        }

        // Estimación: ancho â‰ˆ 0.55 * size * chars (Helvetica promedio)
        $chars = max(0, mb_strlen($text, 'UTF-8'));
        $w = 0.55 * $size * $chars;
        $x = max(0, $xRight - $w);
        return $this->text($x, $yTop, $text, $size, $rgb, $bold);
    }

    /** Alias */
    public function output(): string
    {
        return $this->build();
    }

    public function build(): string
    {
        $content = implode("\n", $this->ops) . "\n";

        $objects = [];

        // 1: Catalog
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        // 2: Pages
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        // 3: Page
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->w} {$this->h}] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        // 4: Font
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        // 5: Content stream
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";

        $pdf = "%PDF-1.4\n";
        $xref = [];
        $offset = strlen($pdf);

        foreach ($objects as $i => $obj) {
            $objNum = $i + 1;
            $xref[$objNum] = $offset;
            $chunk = $objNum . " 0 obj\n" . $obj . "\nendobj\n";
            $pdf .= $chunk;
            $offset = strlen($pdf);
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $xref[$i]) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

        return $pdf;
    }
}

