<?php
declare(strict_types=1);

namespace Services;

/**
 * Email-only report card PDF generator.
 *
 * Important: this service is used by EmailResultController only. It does NOT modify
 * or depend on the browser/quick-result report-card views, so existing working PDF
 * and public report-card screens remain untouched.
 */
class ReportPdfService
{
    public function generate(array $payload): string
    {
        return (new EmailReportCardPdf())->render($payload);
    }

    public function fileName(array $payload): string
    {
        $student = $payload['student'] ?? [];
        $code = (string) ($student['student_code'] ?? 'student');
        $label = (string) ($payload['mode'] ?? 'exam');
        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '-', $code . '-' . $label) ?: 'report-card';
        return 'report-card-' . $safe . '.pdf';
    }

    public function emailBody(array $payload): string
    {
        $student = $payload['student'] ?? [];
        $label = (string) ($payload['reportLabel'] ?? 'Report Card');
        $school = \school_meta('report_school_name', \school_meta('system_name', 'LearnTrack Pro'));
        return '<div style="font-family:Arial,sans-serif;font-size:14px;color:#222">'
            . '<p>Dear Parent/Guardian,</p>'
            . '<p>Please find attached the official report card for <strong>' . htmlspecialchars((string) ($student['name'] ?? 'Student'), ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
            . '<p><strong>Report:</strong> ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Regards,<br>' . htmlspecialchars($school, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p style="font-size:12px;color:#777">Powered by LearnTrack Pro.</p>'
            . '</div>';
    }

    /**
     * Kept for backward compatibility with any older email code that may call it.
     */
    public function buildLines(array $payload): array
    {
        $student = $payload['student'] ?? [];
        $enroll = $payload['enroll'] ?? [];
        $rows = $payload['rows'] ?? [];
        $lines = [];
        $school = strtoupper(\school_meta('report_school_name', \school_meta('system_name', 'LEARNTRACK PRO')));
        $lines[] = \school_meta('report_ministry_name', 'MINISTRY OF EDUCATION');
        $lines[] = $school;
        $lines[] = 'Contacts: ' . \school_meta('report_contacts', \school_meta('phone', ''));
        $lines[] = 'P.O Box: ' . \school_meta('po_box', '');
        $lines[] = 'Motto: ' . \school_meta('motto', '');
        $lines[] = str_repeat('-', 75);
        $lines[] = 'Student Name: ' . (string) ($student['name'] ?? '-');
        $lines[] = 'Student Code: ' . (string) ($student['student_code'] ?? '-');
        $lines[] = 'Class: ' . (string) ($enroll['class_name'] ?? '-');
        $lines[] = 'Section: ' . (string) ($enroll['section_name'] ?? '-');
        $lines[] = 'Academic Year: ' . (string) ($payload['year'] ?? '-');
        $lines[] = 'Report: ' . (string) ($payload['reportLabel'] ?? '-');
        $lines[] = 'Position in Class: ' . (($payload['position'] ?? null) ? (string) $payload['position'] : '-');
        $lines[] = str_repeat('-', 75);
        $lines[] = str_pad('SUBJECT', 30) . str_pad('MARK', 10) . str_pad('POINTS', 10) . 'STANDARD';
        $lines[] = str_repeat('-', 75);
        foreach ($rows as $row) {
            $lines[] = str_pad(substr((string) ($row['subject_name'] ?? '-'), 0, 28), 30)
                . str_pad(\format_mark($row['score'] ?? null), 10)
                . str_pad((string) ($row['grade_point'] ?? '-'), 10)
                . (string) ($row['grade_name'] ?? '-');
        }
        if (!$rows) {
            $lines[] = 'No results found for this selection.';
        }
        $lines[] = str_repeat('-', 75);
        $lines[] = (string) ($payload['bestSixMetricLabel'] ?? 'Total Points In Best Six') . ': ' . (string) ($payload['bestSixMetricValue'] ?? '-');
        $lines[] = \school_meta('report_head_label', 'Head Teacher') . "'s Remarks: " . (string) ($payload['remarks'] ?? '');
        $lines[] = \school_meta('report_head_label', 'Head Teacher') . "'s Signature: __________________________";
        $lines[] = '';
        $lines[] = 'Powered by LearnTrack Pro';
        return $lines;
    }
}

class EmailReportCardPdf
{
    private array $objects = [];
    private array $pages = [];
    private array $pageXObjects = [];
    private array $imageCache = [];
    private int $fontRegular = 0;
    private int $fontBold = 0;
    private string $content = '';
    private float $y = 800.0;
    private int $pageNo = 0;
    private const W = 595.0;
    private const H = 842.0;
    private const M = 34.0;

    public function render(array $payload): string
    {
        $this->objects = [];
        $this->pages = [];
        $this->pageXObjects = [];
        $this->imageCache = [];
        $this->fontRegular = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
        $this->fontBold = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');
        $this->pageNo = 0;
        $this->beginPage();
        $this->drawReport($payload);
        $this->finishPage();
        return $this->finalizePdf((string) ($payload['reportLabel'] ?? 'Report Card'));
    }

    private function drawReport(array $payload): void
    {
        $student = $payload['student'] ?? [];
        $enroll = $payload['enroll'] ?? [];
        $rows = $payload['rows'] ?? [];
        $isTermMode = (($payload['mode'] ?? 'exam') === 'term');
        $examHeaders = $payload['examHeaders'] ?? [];
        $exam = $payload['exam'] ?? [];
        $schoolName = \school_meta('report_school_name', \school_meta('system_name', 'MOOMBA SECONDARY SCHOOL'));
        $contacts = \school_meta('report_contacts', \school_meta('phone', ''));
        $poBox = \school_meta('po_box', '');
        $motto = \school_meta('motto', '');
        $headLabel = \school_meta('report_head_label', 'Head Teacher');
        $positionText = ($payload['position'] ?? null) ? (string) $payload['position'] : '-';
        $totalStudents = is_array($payload['rankings'] ?? null) ? count($payload['rankings']) : 0;
        $reportLabel = (string) ($payload['reportLabel'] ?? 'Report Card');

        $this->drawHeader($schoolName, $contacts, $poBox, $motto);

        $this->box(self::M, $this->y - 58, self::W - (self::M * 2), 50, 0.7);
        $leftX = self::M + 10;
        $rightX = 328;
        $this->text($leftX, $this->y - 18, 'Student Name: ', 9, true);
        $this->text($leftX + 70, $this->y - 18, (string) ($student['name'] ?? '-'), 9);
        $this->text($leftX, $this->y - 34, 'Class: ', 9, true);
        $this->text($leftX + 70, $this->y - 34, (string) ($enroll['class_name'] ?? '-'), 9);
        $this->text($leftX, $this->y - 50, 'Student Code: ', 9, true);
        $this->text($leftX + 70, $this->y - 50, (string) ($student['student_code'] ?? '-'), 9);

        $this->text($rightX, $this->y - 18, 'Term/Report: ', 9, true);
        $this->text($rightX + 76, $this->y - 18, $this->short($this->reportTermLabel($payload, $exam), 34), 9);
        $this->text($rightX, $this->y - 34, 'Total Students: ', 9, true);
        $this->text($rightX + 76, $this->y - 34, (string) $totalStudents, 9);
        $this->text($rightX, $this->y - 50, 'Position: ', 9, true);
        $this->text($rightX + 76, $this->y - 50, $positionText, 9);
        $this->y -= 76;

        $this->text(self::M, $this->y, strtoupper($reportLabel), 10, true);
        $this->y -= 12;

        $columns = [];
        $columns[] = ['label' => 'Subject', 'key' => 'subject_name', 'w' => 158];
        if ($isTermMode && !empty($examHeaders)) {
            $availableForExams = 216;
            $examW = max(36, min(70, $availableForExams / max(1, count($examHeaders))));
            foreach ($examHeaders as $header) {
                $columns[] = ['label' => $this->short((string) $header, 10), 'key' => 'exam:' . (string) $header, 'w' => $examW];
            }
            $columns[] = ['label' => 'Term Score', 'key' => 'score', 'w' => 60];
        } else {
            $columns[] = ['label' => $this->short((string) ($exam['name'] ?? 'Mark'), 18), 'key' => 'score', 'w' => 86];
        }
        $columns[] = ['label' => 'Points', 'key' => 'grade_point', 'w' => 52];
        $columns[] = ['label' => 'Standard', 'key' => 'standard', 'w' => 86];
        $this->drawResultsTable($rows, $columns, $isTermMode);

        $this->ensureSpace(92);
        $summaryY = $this->y;
        $this->box(self::M, $summaryY - 62, self::W - (self::M * 2), 54, 0.7);
        $this->text(self::M + 10, $summaryY - 20, (string) ($payload['bestSixMetricLabel'] ?? 'Total Points In Best Six') . ': ', 9, true);
        $this->text(self::M + 170, $summaryY - 20, (string) ($payload['bestSixMetricValue'] ?? '-'), 9);
        $this->text(self::M + 10, $summaryY - 38, $headLabel . "'s Remarks: ", 9, true);
        $this->text(self::M + 120, $summaryY - 38, $this->short((string) (($payload['remarks'] ?? '') ?: 'No remarks available.'), 65), 9);
        $this->text(self::M + 10, $summaryY - 56, $headLabel . "'s sign:", 9, true);
        $signaturePath = $this->settingImagePath("head_signature", "/public/assets/img/report-signature.png");
        if ($signaturePath && $this->image($signaturePath, self::M + 120, $summaryY - 61, 118, 30)) {
            $this->line(self::M + 120, $summaryY - 62, self::M + 300, $summaryY - 62, 0.35);
        } else {
            $this->line(self::M + 120, $summaryY - 56, self::M + 300, $summaryY - 56, 0.6);
        }
        $this->y -= 78;

        $this->drawGradingTable();
        $this->drawFooter();
    }

    private function drawHeader(string $schoolName, string $contacts, string $poBox, string $motto): void
    {
        $top = $this->y;
        $this->box(self::M, $top - 92, 78, 78, 0.8);
        $this->box(self::W - self::M - 78, $top - 92, 78, 78, 0.8);

        $leftLogo = $this->settingImagePath("report_left_logo", "/public/assets/img/report-coat.png");
        $rightLogo = $this->settingImagePath("report_right_logo", "/public/assets/img/report-mss.png");

        if (!$leftLogo || !$this->image($leftLogo, self::M + 8, $top - 88, 62, 62)) {
            $this->text(self::M + 14, $top - 54, "LOGO", 10, true);
        }
        if (!$rightLogo || !$this->image($rightLogo, self::W - self::M - 70, $top - 88, 62, 62)) {
            $this->text(self::W - self::M - 64, $top - 54, "LOGO", 10, true);
        }

        $this->center($top - 20, \school_meta('report_ministry_name', 'MINISTRY OF EDUCATION'), 11, true);
        $this->center($top - 38, strtoupper($schoolName), 13, true);
        if ($contacts !== '') {
            $this->center($top - 55, 'Contacts: ' . $contacts, 8.5);
        }
        if ($poBox !== '') {
            $this->center($top - 69, 'P.O Box: ' . $poBox, 8.5);
        }
        if ($motto !== '') {
            $this->center($top - 83, 'Motto: ' . $motto, 8.5);
        }
        $this->line(self::M, $top - 104, self::W - self::M, $top - 104, 0.8);
        $this->y = $top - 120;
    }

    private function drawResultsTable(array $rows, array $columns, bool $isTermMode): void
    {
        $x = self::M;
        $wTotal = 0;
        foreach ($columns as $column) {
            $wTotal += (float) $column['w'];
        }
        $scale = min(1.0, (self::W - (self::M * 2)) / max(1.0, $wTotal));
        foreach ($columns as &$column) {
            $column['w'] = (float) $column['w'] * $scale;
        }
        unset($column);
        $rowH = 20;
        $this->drawTableHeader($columns);
        if (!$rows) {
            $this->box($x, $this->y - $rowH, self::W - (self::M * 2), $rowH, 0.6);
            $this->text($x + 8, $this->y - 14, 'No results found for this selection.', 8.5);
            $this->y -= $rowH;
            return;
        }
        foreach ($rows as $row) {
            $this->ensureSpace($rowH + 18, function () use ($columns): void {
                $this->drawTableHeader($columns);
            });
            $cx = $x;
            foreach ($columns as $column) {
                $cw = (float) $column['w'];
                $this->box($cx, $this->y - $rowH, $cw, $rowH, 0.35);
                $value = $this->columnValue($row, (string) $column['key'], $isTermMode);
                $this->text($cx + 4, $this->y - 13, $this->short($value, max(4, (int) floor($cw / 5.1))), 8.2);
                $cx += $cw;
            }
            $this->y -= $rowH;
        }
        $this->y -= 10;
    }

    private function drawTableHeader(array $columns): void
    {
        $x = self::M;
        $h = 22;
        foreach ($columns as $column) {
            $cw = (float) $column['w'];
            $this->box($x, $this->y - $h, $cw, $h, 0.7);
            $this->text($x + 4, $this->y - 14, (string) $column['label'], 8.2, true);
            $x += $cw;
        }
        $this->y -= $h;
    }

    private function drawGradingTable(): void
    {
        $scale = \grading_scale();
        if (!$scale) {
            return;
        }
        $this->ensureSpace(74);
        $this->text(self::M, $this->y, 'Grading System', 9, true);
        $this->y -= 14;

        $maxBands = min(9, count($scale));
        $labelW = 76.0;
        $bandW = (self::W - (self::M * 2) - $labelW) / max(1, $maxBands);
        $rowH = 18.0;
        $x = self::M;

        $this->box($x, $this->y - $rowH, $labelW, $rowH, 0.45);
        $this->text($x + 4, $this->y - 12, 'Range', 7.2, true);
        $x += $labelW;
        for ($i = 0; $i < $maxBands; $i++) {
            $band = $scale[$i];
            $from = $band['from'] ?? $band['min'] ?? $band['mark_from'] ?? '';
            $to = $band['to'] ?? $band['max'] ?? $band['mark_to'] ?? '';
            $this->box($x, $this->y - $rowH, $bandW, $rowH, 0.45);
            $this->text($x + 3, $this->y - 12, $this->short((string) $from . '-' . (string) $to, max(4, (int) floor($bandW / 4.2))), 6.8, true);
            $x += $bandW;
        }
        $this->y -= $rowH;

        $x = self::M;
        $this->box($x, $this->y - $rowH, $labelW, $rowH, 0.45);
        $this->text($x + 4, $this->y - 12, 'Description', 7.2, true);
        $x += $labelW;
        for ($i = 0; $i < $maxBands; $i++) {
            $band = $scale[$i];
            $point = $band['point'] ?? $band['grade_point'] ?? '';
            $label = (string) ($band['label'] ?? $band['name'] ?? '');
            $description = trim((string) $point . ($label !== '' ? ' ' . $label : ''));
            $this->box($x, $this->y - $rowH, $bandW, $rowH, 0.45);
            $this->text($x + 3, $this->y - 12, $this->short($description, max(4, (int) floor($bandW / 4.2))), 6.6);
            $x += $bandW;
        }
        $this->y -= ($rowH + 10);
    }

    private function drawFooter(): void
    {
        $this->line(self::M, 38, self::W - self::M, 38, 0.45);
        $this->center(24, 'Powered by LearnTrack Pro', 8);
    }

    private function columnValue(array $row, string $key, bool $isTermMode): string
    {
        if ($key === 'subject_name') {
            return (string) ($row['subject_name'] ?? '-');
        }
        if ($key === 'score') {
            return ($row['score'] ?? null) !== null ? \format_mark($row['score']) : 'N/A';
        }
        if ($key === 'grade_point') {
            return ($row['grade_point'] ?? null) !== null ? (string) $row['grade_point'] : '-';
        }
        if ($key === 'standard') {
            return $this->standardLabel($row['grade_point'] ?? null, (string) ($row['grade_name'] ?? ''));
        }
        if (substr($key, 0, 5) === 'exam:') {
            $examName = substr($key, 5);
            $value = $row['marks'][$examName] ?? null;
            return ($value !== null && $value !== '') ? \format_mark($value) : 'N/A';
        }
        return '-';
    }

    private function standardLabel($point, string $gradeName): string
    {
        $gradingScale = \grading_scale();
        $point = is_numeric($point) ? (int) $point : null;
        foreach ($gradingScale as $band) {
            if ($point !== null && isset($band['point']) && (int) $band['point'] === $point) {
                return (string) $band['point'];
            }
        }
        $gradeName = strtoupper(trim($gradeName));
        return $gradeName !== '' ? $gradeName : '-';
    }

    private function reportTermLabel(array $payload, array $exam): string
    {
        if (($payload['mode'] ?? 'exam') === 'term') {
            return (string) (($payload['termLabel'] ?? '') ?: ($payload['term'] ?? '-'));
        }
        if (isset($exam['exam_term']) && trim((string) $exam['exam_term']) !== '') {
            $clean = preg_replace('/[^0-9A-Za-z ]+/', '', (string) $exam['exam_term']);
            return 'Term ' . $clean;
        }
        return (string) ($payload['reportLabel'] ?? '-');
    }

    private function ensureSpace(float $needed, ?callable $afterNewPage = null): void
    {
        if ($this->y - $needed < 54) {
            $this->finishPage();
            $this->beginPage();
            $this->center(812, 'LearnTrack Pro Report Card - continued', 9, true);
            $this->y = 785;
            if ($afterNewPage) {
                $afterNewPage();
            }
        }
    }

    private function beginPage(): void
    {
        $this->content = '';
        $this->y = 800;
        $this->pageNo++;
    }

    private function finishPage(): void
    {
        if ($this->content === '') {
            return;
        }
        $stream = $this->content;
        $contentObj = $this->addObject('<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream");
        $xObjects = '';
        foreach (($this->pageXObjects[$this->pageNo] ?? []) as $name => $objectId) {
            $xObjects .= ' /' . $name . ' ' . $objectId . ' 0 R';
        }
        $xObjectResource = $xObjects !== '' ? ' /XObject <<' . $xObjects . ' >>' : '';
        $pageObj = $this->addObject('<< /Type /Page /Parent __PAGES__ /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $this->fontRegular . ' 0 R /F2 ' . $this->fontBold . ' 0 R >>' . $xObjectResource . ' >> /Contents ' . $contentObj . ' 0 R >>');
        $this->pages[] = $pageObj;
        $this->content = '';
    }

    private function finalizePdf(string $title): string
    {
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
        $pdf .= "trailer\n<< /Size " . (count($this->objects) + 1) . " /Root " . $catalogObj . " 0 R /Info << /Title (" . $this->esc($title) . ") >> >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private function settingImagePath(string $settingKey, string $fallback): ?string
    {
        $configured = trim(\school_meta($settingKey, ''));
        $path = $this->resolveImagePath($configured);
        if ($path) {
            return $path;
        }
        return $this->resolveImagePath($fallback);
    }

    private function resolveImagePath(string $value): ?string
    {
        $value = trim($value);
        $root = dirname(__DIR__, 2);
        $candidates = [];

        if ($value !== '') {
            $path = $value;
            if (preg_match('#^https?://#i', $path)) {
                $urlPath = (string) (parse_url($path, PHP_URL_PATH) ?: '');
                $basePath = (string) (parse_url((string) \config('app.base_url', ''), PHP_URL_PATH) ?: '');
                if ($basePath !== '' && strpos($urlPath, $basePath) === 0) {
                    $urlPath = substr($urlPath, strlen($basePath));
                }
                if (($pos = strpos($urlPath, '/public/')) !== false) {
                    $urlPath = substr($urlPath, $pos);
                }
                $path = $urlPath;
            }
            $path = str_replace('\\', '/', $path);
            $candidates[] = $path;
            $candidates[] = $root . '/' . ltrim($path, '/');
            if (($pos = strpos($path, '/public/')) !== false) {
                $candidates[] = $root . substr($path, $pos);
            }
            $candidates[] = $root . '/public/' . ltrim($path, '/');
        }

        foreach ($candidates as $candidate) {
            $candidate = str_replace('\\', '/', $candidate);
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    private function image(string $path, float $x, float $y, float $w, float $h): bool
    {
        $image = $this->loadImageObject($path);
        if (!$image) {
            return false;
        }
        $name = 'Im' . count($this->pageXObjects[$this->pageNo] ?? []);
        $this->pageXObjects[$this->pageNo][$name] = $image['object_id'];
        $this->content .= 'q ' . $this->num($w) . ' 0 0 ' . $this->num($h) . ' ' . $this->num($x) . ' ' . $this->num($y) . ' cm /' . $name . " Do Q\n";
        return true;
    }

    private function decodePngRgb(string $path): ?array
    {
        $data = @file_get_contents($path);
        if ($data === false || substr($data, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return null;
        }

        $pos = 8;
        $width = 0;
        $height = 0;
        $bitDepth = 0;
        $colorType = 0;
        $interlace = 0;
        $idat = '';
        $len = strlen($data);

        while ($pos + 8 <= $len) {
            $chunkLen = unpack('N', substr($data, $pos, 4))[1];
            $type = substr($data, $pos + 4, 4);
            $chunk = substr($data, $pos + 8, $chunkLen);
            $pos += 12 + $chunkLen;

            if ($type === 'IHDR') {
                $ihdr = unpack('Nwidth/Nheight/CbitDepth/CcolorType/Ccompression/Cfilter/Cinterlace', $chunk);
                $width = (int) $ihdr['width'];
                $height = (int) $ihdr['height'];
                $bitDepth = (int) $ihdr['bitDepth'];
                $colorType = (int) $ihdr['colorType'];
                $interlace = (int) $ihdr['interlace'];
            } elseif ($type === 'IDAT') {
                $idat .= $chunk;
            } elseif ($type === 'IEND') {
                break;
            }
        }

        if ($width <= 0 || $height <= 0 || $bitDepth !== 8 || $interlace !== 0 || !in_array($colorType, [2, 6], true)) {
            return null;
        }

        $raw = @gzuncompress($idat);
        if ($raw === false) {
            return null;
        }

        $channels = $colorType === 6 ? 4 : 3;
        $stride = $width * $channels;
        $out = '';
        $prev = array_fill(0, $stride, 0);
        $offset = 0;
        $rawLen = strlen($raw);

        for ($y = 0; $y < $height; $y++) {
            if ($offset >= $rawLen) {
                return null;
            }
            $filter = ord($raw[$offset++]);
            $scan = [];
            for ($i = 0; $i < $stride; $i++) {
                $x = $offset + $i;
                $val = $x < $rawLen ? ord($raw[$x]) : 0;
                $left = $i >= $channels ? $scan[$i - $channels] : 0;
                $up = $prev[$i] ?? 0;
                $upLeft = $i >= $channels ? ($prev[$i - $channels] ?? 0) : 0;
                if ($filter === 1) {
                    $val = ($val + $left) & 0xFF;
                } elseif ($filter === 2) {
                    $val = ($val + $up) & 0xFF;
                } elseif ($filter === 3) {
                    $val = ($val + intdiv($left + $up, 2)) & 0xFF;
                } elseif ($filter === 4) {
                    $p = $left + $up - $upLeft;
                    $pa = abs($p - $left);
                    $pb = abs($p - $up);
                    $pc = abs($p - $upLeft);
                    $pr = ($pa <= $pb && $pa <= $pc) ? $left : (($pb <= $pc) ? $up : $upLeft);
                    $val = ($val + $pr) & 0xFF;
                } elseif ($filter !== 0) {
                    return null;
                }
                $scan[$i] = $val;
            }
            $offset += $stride;
            for ($i = 0; $i < $stride; $i += $channels) {
                $out .= chr($scan[$i]) . chr($scan[$i + 1]) . chr($scan[$i + 2]);
            }
            $prev = $scan;
        }

        return ['width' => $width, 'height' => $height, 'data' => $out];
    }

    private function loadImageObject(string $path): ?array
    {
        $cacheKey = realpath($path) ?: $path;
        if (isset($this->imageCache[$cacheKey])) {
            return $this->imageCache[$cacheKey];
        }
        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }
        [$width, $height] = $info;
        $mime = strtolower((string) ($info['mime'] ?? ''));

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $data = @file_get_contents($path);
            if ($data === false) {
                return null;
            }
            $objectId = $this->addObject('<< /Type /XObject /Subtype /Image /Width ' . (int) $width . ' /Height ' . (int) $height . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($data) . " >>\nstream\n" . $data . "\nendstream");
            return $this->imageCache[$cacheKey] = ['object_id' => $objectId, 'width' => $width, 'height' => $height];
        }

        if ($mime === 'image/png') {
            $png = $this->decodePngRgb($path);
            if ($png) {
                $compressed = gzcompress($png['data'], 9);
                if ($compressed !== false) {
                    $objectId = $this->addObject('<< /Type /XObject /Subtype /Image /Width ' . (int) $png['width'] . ' /Height ' . (int) $png['height'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /FlateDecode /Length ' . strlen($compressed) . " >>\nstream\n" . $compressed . "\nendstream");
                    return $this->imageCache[$cacheKey] = ['object_id' => $objectId, 'width' => $png['width'], 'height' => $png['height']];
                }
            }
        }

        return null;
    }

    private function text(float $x, float $y, string $text, float $size = 9, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->content .= "BT /{$font} " . $this->num($size) . " Tf " . $this->num($x) . ' ' . $this->num($y) . ' Td (' . $this->esc($text) . ") Tj ET\n";
    }

    private function center(float $y, string $text, float $size = 9, bool $bold = false): void
    {
        $approxWidth = strlen($this->ascii($text)) * $size * 0.5;
        $x = max(self::M, (self::W - $approxWidth) / 2);
        $this->text($x, $y, $text, $size, $bold);
    }

    private function line(float $x1, float $y1, float $x2, float $y2, float $width = 0.5): void
    {
        $this->content .= $this->num($width) . ' w ' . $this->num($x1) . ' ' . $this->num($y1) . ' m ' . $this->num($x2) . ' ' . $this->num($y2) . " l S\n";
    }

    private function box(float $x, float $y, float $w, float $h, float $width = 0.5): void
    {
        $this->content .= $this->num($width) . ' w ' . $this->num($x) . ' ' . $this->num($y) . ' ' . $this->num($w) . ' ' . $this->num($h) . " re S\n";
    }

    private function addObject(string $content): int
    {
        $id = count($this->objects) + 1;
        $this->objects[$id] = $content;
        return $id;
    }

    private function esc(string $text): string
    {
        $text = $this->ascii($text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function ascii(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], [' ', ' ', ' '], $text);
        return preg_replace('/[^\x20-\x7E]/', '', $text) ?? $text;
    }

    private function short(string $text, int $max): string
    {
        $text = trim($this->ascii($text));
        if ($max <= 0 || strlen($text) <= $max) {
            return $text;
        }
        return substr($text, 0, max(1, $max - 1)) . '...';
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(sprintf('%.3F', $value), '0'), '.');
    }
}
