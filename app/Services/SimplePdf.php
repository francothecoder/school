<?php
declare(strict_types=1);

namespace Services;

class SimplePdf
{
    private array $objects = [];
    private array $pages = [];
    private int $fontObj = 0;

    public function output(array $lines, string $title = 'Document'): string
    {
        $this->objects = [];
        $this->pages = [];
        $this->fontObj = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');

        $chunks = array_chunk($lines, 44);
        if (!$chunks) {
            $chunks = [[]];
        }
        foreach ($chunks as $chunk) {
            $this->addPage($chunk);
        }

        $kids = implode(' ', array_map(fn($id) => $id . ' 0 R', $this->pages));
        $pagesObj = $this->addObject('<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($this->pages) . ' >>');
        foreach ($this->pages as $pageObjId) {
            $this->objects[$pageObjId] = str_replace('__PAGES__', $pagesObj . ' 0 R', $this->objects[$pageObjId]);
        }
        $catalogObj = $this->addObject('<< /Type /Catalog /Pages ' . $pagesObj . ' 0 R >>');

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($this->objects as $id => $content) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $content . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($this->objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($this->objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i] ?? 0) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . (count($this->objects) + 1) . " /Root " . $catalogObj . " 0 R /Info << /Title (" . $this->escape($title) . ") >> >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private function addPage(array $lines): void
    {
        $content = "BT\n/F1 10 Tf\n50 790 Td\n14 TL\n";
        foreach ($lines as $line) {
            $content .= '(' . $this->escape((string) $line) . ") Tj\nT*\n";
        }
        $content .= "ET";
        $contentObj = $this->addObject('<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream");
        $pageObj = $this->addObject('<< /Type /Page /Parent __PAGES__ /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $this->fontObj . ' 0 R >> >> /Contents ' . $contentObj . ' 0 R >>');
        $this->pages[] = $pageObj;
    }

    private function addObject(string $content): int
    {
        $id = count($this->objects) + 1;
        $this->objects[$id] = $content;
        return $id;
    }

    private function escape(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], [' ', ' ', ' '], $text);
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
