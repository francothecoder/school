<?php
$schoolName = school_meta('report_school_name', school_meta('system_name', 'MOOMBA SECONDARY SCHOOL'));
$contacts = school_meta('report_contacts', school_meta('phone', '+2609777703675 | +260966624748'));
$poBox = school_meta('po_box', '390001');
$motto = school_meta('motto', 'With Perseverance Comes Success');
$studentName = $student['name'] ?? '-';
$className = $enroll['class_name'] ?? '-';
$termLabel = trim((string) (($termLabel ?? '') ?: ($term ?? '')));
$isTermMode = ($mode ?? 'exam') === 'term';
$positionText = ($position ?? null) ? (string) $position : '-';
$totalStudents = count($rankings ?? []);
$studentCodeValue = $student['student_code'] ?? ($student['email'] ?? 'student');
$signaturePath = school_meta('head_signature', '') ?: base_url('/public/assets/img/report-signature.png');
$leftLogo = school_meta('report_left_logo', school_meta('coat_logo', '')) ?: base_url('/public/assets/img/report-coat.png');
$rightLogo = school_meta('report_right_logo', school_meta('school_logo', '')) ?: base_url('/public/assets/img/report-mss.png');
$gradingScale = grading_scale();
$standardMap = [];
foreach ($gradingScale as $band) {
    if (isset($band['point'])) {
        $standardMap[(int) $band['point']] = (string) $band['point'];
    }
}

$reportColumns = [];
if ($isTermMode && !empty($examHeaders)) {
    foreach ($examHeaders as $header) {
        $reportColumns[] = $header;
    }
} else {
    $reportColumns[] = $exam['name'] ?? $reportLabel ?? 'Exam Result';
}


if (!function_exists('report_standard_label_exact')) {
    function report_standard_label_exact($point, $gradeName, array $standardMap): string {
        $point = is_numeric($point) ? (int) $point : null;
        if ($point !== null && isset($standardMap[$point])) {
            return $standardMap[$point];
        }
        $gradeName = strtoupper(trim((string) $gradeName));
        return $gradeName !== '' ? $gradeName : '-';
    }
}
?>
<div class="report-card-source-wrap">
    <div class="report-card-source-template" id="reportCard">
        <div class="report-source-header">
            <div class="report-source-logo-block report-source-logo-left">
                <div class="report-source-logo-circle">
                    <img src="<?= e($leftLogo) ?>" alt="Left Logo" class="report-source-logo">
                </div>
            </div>
            <div class="report-source-heading">
                <h3><?= e(school_meta('report_ministry_name', 'MINISTRY OF EDUCATION')) ?></h3>
                <h5><?= e(strtoupper($schoolName)) ?></h5>
                <h5>Contacts: <?= e($contacts) ?></h5>
                <h5>P.O Box: <?= e($poBox) ?></h5>
                <h5>Motto: <?= e($motto) ?></h5>
                <hr>
            </div>
            <div class="report-source-logo-block report-source-logo-right">
                <div class="report-source-logo-circle">
                    <img src="<?= e($rightLogo) ?>" alt="Right Logo" class="report-source-logo">
                </div>
            </div>
        </div>

        <div class="report-source-meta-lines">
            <p>Student Name: <?= e($studentName) ?></p>
            <p>Class: <?= e($className) ?></p>
            <p>Term: <?= e($isTermMode ? ($termLabel ?: '-') : ((isset($exam['exam_term']) && trim((string) $exam['exam_term']) !== '' ? ('Term ' . preg_replace('/[^0-9A-Za-z ]+/', '', (string) $exam['exam_term'])) : ($reportLabel ?? '-'))) ) ?></p>
            <p>Total Students in Class: <?= e((string) $totalStudents) ?></p>
            <p>Position in Class: <?= e($positionText) ?></p>
        </div>

        <table class="report-source-table" id="results-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <?php foreach ($reportColumns as $column): ?>
                        <th><?= e($column) ?></th>
                    <?php endforeach; ?>
                    <?php if ($isTermMode): ?><th>Term Score</th><?php endif; ?>
                    <th>Points</th>
                    <th>Standard</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($rows ?? []) as $row): ?>
                    <tr>
                        <td><?= e($row['subject_name'] ?? '-') ?></td>
                        <?php foreach ($reportColumns as $column): ?>
                            <?php $value = $isTermMode ? ($row['marks'][$column] ?? null) : ($row['score'] ?? null); ?>
                            <td><?= e($value !== null && $value !== '' ? (string) $value : 'N/A') ?></td>
                        <?php endforeach; ?>
                        <?php if ($isTermMode): ?><td><?= e(($row['score'] ?? null) !== null ? (string) $row['score'] : 'N/A') ?></td><?php endif; ?>
                        <td><?= e(($row['grade_point'] ?? null) !== null ? (string) $row['grade_point'] : '-') ?></td>
                        <td><?= e(report_standard_label_exact($row['grade_point'] ?? null, $row['grade_name'] ?? '', $standardMap)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= 3 + count($reportColumns) + ($isTermMode ? 1 : 0) ?>" class="empty-row">No results found for this selection.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="report-source-summary">
            <p><?= e($bestSixMetricLabel ?: 'Total Points In Best Six') ?>: <?= e((string) ($bestSixMetricValue ?? 0)) ?></p>
            <p><?= e(school_meta('report_head_label', 'Head Teacher')) ?>'s Remarks: <?= e($remarks ?: 'No remarks available.') ?></p>
            <p><?= e(school_meta('report_head_label', 'Head Teacher')) ?>'s sign:</p>
            <div class="report-source-signature-wrap">
                <img src="<?= e($signaturePath) ?>" alt="Signature" class="report-source-signature">
            </div>
        </div>

        <div id="grading-container">
            <table class="report-source-grade-table" id="grading-table">
                <thead>
                    <tr>
                        <th>Range</th>
                        <?php foreach ($gradingScale as $band): ?>
                            <th><?= e((string) $band['from']) ?> - <?= e((string) $band['to']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Description</td>
                        <?php foreach ($gradingScale as $band): ?>
                            <td><?= e((string) $band['point']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('downloadPdfBtn');
    if (!button) return;

    const { jsPDF } = window.jspdf;
    const reportData = {
        studentName: <?= json_encode($studentName) ?>,
        className: <?= json_encode($className) ?>,
        term: <?= json_encode($isTermMode ? ($termLabel ?: '-') : ($exam['exam_term'] ?? $reportLabel ?? '-')) ?>,
        totalStudents: <?= json_encode((string) $totalStudents) ?>,
        rank: <?= json_encode((string) $positionText) ?>,
        totalPoints: <?= json_encode((string) ($bestSixMetricValue ?? 0)) ?>,
        totalPointsLabel: <?= json_encode($bestSixMetricLabel ?: 'Total Points In Best Six') ?>,
        remarks: <?= json_encode($remarks ?: 'No remarks available.') ?>,
        schoolName: <?= json_encode(strtoupper($schoolName)) ?>,
        signatureLabel: <?= json_encode(school_meta('report_head_label', 'Head Teacher')) ?>,
        contacts: <?= json_encode('Contacts: ' . $contacts) ?>,
        poBox: <?= json_encode('P.O Box: ' . $poBox) ?>,
        motto: <?= json_encode('Motto: ' . $motto) ?>,
        leftLogo: <?= json_encode($leftLogo) ?>,
        rightLogo: <?= json_encode($rightLogo) ?>,
        signature: <?= json_encode($signaturePath) ?>,
        fileName: <?= json_encode('report-card-' . $studentCodeValue . '.pdf') ?>
    };

    const getBase64ImageFromURL = async (url) => {
        if (!url) return null;
        return new Promise((resolve) => {
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                resolve(canvas.toDataURL('image/png'));
            };
            img.onerror = () => resolve(null);
            img.src = url;
        });
    };

    const addCenteredText = (doc, text, y) => {
        const pageWidth = doc.internal.pageSize.width;
        const textWidth = doc.getTextWidth(text);
        doc.text(text, (pageWidth - textWidth) / 2, y);
    };

    const generatePDF = async () => {
        const doc = new jsPDF('p', 'mm', 'a4');
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();

        const leftLogo = await getBase64ImageFromURL(reportData.leftLogo);
        const rightLogo = await getBase64ImageFromURL(reportData.rightLogo);
        const signature = await getBase64ImageFromURL(reportData.signature);

        if (leftLogo) {
            doc.addImage(leftLogo, 'PNG', 10, 8, 16, 12);
        }
        if (rightLogo) {
            doc.addImage(rightLogo, 'PNG', pageWidth - 26, 8, 16, 12);
        }

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(11);
        addCenteredText(doc, <?= json_encode(school_meta('report_ministry_name', 'MINISTRY OF EDUCATION')) ?>, 16);
        addCenteredText(doc, reportData.schoolName, 22);
        doc.setFontSize(8.5);
        addCenteredText(doc, reportData.contacts, 27);
        addCenteredText(doc, reportData.poBox, 31);
        addCenteredText(doc, reportData.motto, 35);

        doc.setLineWidth(0.3);
        doc.line(10, 38, pageWidth - 10, 38);

        doc.setFontSize(8.5);
        doc.text(`Student Name: ${reportData.studentName}`, 10, 44);
        doc.text(`Class: ${reportData.className}`, 10, 49);
        doc.text(`Term: ${reportData.term}`, 10, 54);
        doc.text(`Total Students in Class: ${reportData.totalStudents}`, 10, 59);
        doc.text(`Position in Class: ${reportData.rank}`, 10, 64);

        const resultsTable = document.getElementById('results-table');
        const gradingTable = document.getElementById('grading-table');
        const rowCount = resultsTable ? resultsTable.querySelectorAll('tbody tr').length : 0;
        const columnCount = resultsTable && resultsTable.querySelector('thead tr')
            ? resultsTable.querySelector('thead tr').children.length
            : 4;

        let bodyFontSize = 8;
        let headFontSize = 8;
        let cellPadding = 1.2;

        if (columnCount >= 6) {
            bodyFontSize = 7.2;
            headFontSize = 7.4;
            cellPadding = 0.9;
        }
        if (rowCount >= 10) {
            bodyFontSize = Math.min(bodyFontSize, 7);
            headFontSize = Math.min(headFontSize, 7.2);
            cellPadding = Math.min(cellPadding, 0.8);
        }
        if (rowCount >= 13 || columnCount >= 7) {
            bodyFontSize = 6.4;
            headFontSize = 6.7;
            cellPadding = 0.65;
        }
        if (rowCount >= 16 || columnCount >= 8) {
            bodyFontSize = 5.8;
            headFontSize = 6.1;
            cellPadding = 0.5;
        }

        const lastIndex = columnCount - 1;
        const secondLastIndex = columnCount - 2;
        const autoColumns = {
            0: { cellWidth: columnCount >= 7 ? 60 : 78 },
        };
        if (secondLastIndex > 0) {
            autoColumns[secondLastIndex] = { cellWidth: 18, halign: 'center' };
        }
        if (lastIndex > 0) {
            autoColumns[lastIndex] = { cellWidth: 26, halign: 'center' };
        }

        doc.autoTable({
            startY: 68,
            html: resultsTable,
            theme: 'grid',
            pageBreak: 'avoid',
            rowPageBreak: 'avoid',
            margin: { left: 10, right: 10 },
            tableWidth: 'auto',
            headStyles: {
                fillColor: [76, 175, 80],
                textColor: [255, 255, 255],
                fontSize: headFontSize,
                halign: 'center',
                valign: 'middle',
                lineColor: [0, 0, 0],
                lineWidth: 0.1
            },
            bodyStyles: {
                fontSize: bodyFontSize,
                textColor: [0, 0, 0],
                valign: 'middle'
            },
            styles: {
                font: 'helvetica',
                cellPadding: cellPadding,
                lineColor: [0, 0, 0],
                lineWidth: 0.1,
                overflow: 'linebreak'
            },
            columnStyles: autoColumns,
            didParseCell: function (data) {
                if (data.section === 'body' && data.column.index === 0) {
                    data.cell.styles.halign = 'left';
                    data.cell.styles.fontStyle = 'bold';
                }
            }
        });

        let y = doc.lastAutoTable.finalY + 4;

        doc.setFontSize(8);
        doc.text(`${reportData.totalPointsLabel}: ${reportData.totalPoints}`, 10, y);
        y += 5;

        const splitRemarks = doc.splitTextToSize(
            `Head Teacher's Remarks: ${reportData.remarks}`,
            pageWidth - 20
        );
        doc.text(splitRemarks, 10, y);
        y += (splitRemarks.length * 4) + 1;

        doc.text(`${reportData.signatureLabel}'s sign:`, 10, y);
        if (signature) {
            doc.addImage(signature, 'PNG', 40, y - 3.5, 24, 9);
        }

        let gradingStartY = Math.max(y + 8, pageHeight - 24);
        if (gradingStartY > 268) gradingStartY = 268;
        if (gradingStartY < y + 6) gradingStartY = y + 6;

        doc.autoTable({
            startY: gradingStartY,
            html: gradingTable,
            theme: 'grid',
            pageBreak: 'avoid',
            rowPageBreak: 'avoid',
            margin: { left: 10, right: 10, bottom: 8 },
            headStyles: {
                fillColor: [76, 175, 80],
                textColor: [255, 255, 255],
                fontSize: 7.2,
                halign: 'center',
                lineColor: [0, 0, 0],
                lineWidth: 0.1
            },
            bodyStyles: {
                fontSize: 7,
                halign: 'center',
                textColor: [0, 0, 0]
            },
            styles: {
                font: 'helvetica',
                cellPadding: 0.7,
                lineColor: [0, 0, 0],
                lineWidth: 0.1
            }
        });

        if (doc.internal.getNumberOfPages() > 1) {
            const pages = doc.internal.getNumberOfPages();
            for (let i = pages; i > 1; i--) {
                doc.deletePage(i);
            }
        }

        doc.save(reportData.fileName);
    };

    button.addEventListener('click', generatePDF);
});
</script>
