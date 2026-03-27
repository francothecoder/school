<div class="panel-card mb-4 no-print">
    <div class="panel-head d-flex justify-content-between align-items-start">
        <div>
            <h5>Class Exam Download Report</h5>
            <div class="form-helper">View or download a full class mark sheet for a selected exam. Each subject in the class appears as a column.</div>
        </div>
        <?php if ($class && $exam): ?>
        <div class="card-actions">
            <a class="btn btn-outline-primary" href="<?= e(base_url('/reports/class-sheet?class_id=' . $classId . '&exam_id=' . $examId . '&year=' . urlencode($year) . '&download=csv')) ?>">Download CSV</a>
            <button class="btn btn-primary" onclick="window.print()">Print</button>
        </div>
        <?php endif; ?>
    </div>
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-4"><label class="form-label">Academic Year</label><input type="text" name="year" class="form-control" value="<?= e($year) ?>"></div>
        <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select"><?php foreach ($classes as $item): ?><option value="<?= e($item['class_id']) ?>" <?= (int)$classId === (int)$item['class_id'] ? 'selected' : '' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Exam</label><select name="exam_id" class="form-select"><?php foreach ($exams as $item): ?><option value="<?= e($item['exam_id']) ?>" <?= (int)$examId === (int)$item['exam_id'] ? 'selected' : '' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><button class="btn btn-primary">Load Sheet</button></div>
    </form>
</div>

<?php if ($class && $exam): ?>
<div class="panel-card report-sheet-wrap">
    <div class="panel-head d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5><?= e($class['name']) ?> · <?= e($exam['name']) ?></h5>
            <div class="form-helper">Academic year <?= e($year) ?> · <?= count($students) ?> students · <?= count($subjects) ?> subjects</div>
        </div>
    </div>
    <div class="table-responsive wide-sheet">
        <table class="table table-modern table-bordered align-middle sheet-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Code</th>
                    <?php foreach ($subjects as $subject): ?><th><?= e($subject['name']) ?></th><?php endforeach; ?>
                    <th>Total</th>
                    <th>Average</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): $total = 0; $count = 0; ?>
                <tr>
                    <td><?= e($student['name']) ?></td>
                    <td><?= e($student['student_code']) ?></td>
                    <?php foreach ($subjects as $subject): $score = $grid[(int)$student['student_id']][(int)$subject['subject_id']]['mark_obtained'] ?? null; if ($score !== null && $score !== '') { $total += (int) $score; $count++; } ?>
                        <td><?= e($score !== null && $score !== '' ? (string) $score : '-') ?></td>
                    <?php endforeach; ?>
                    <td><?= e((string) $total) ?></td>
                    <td><?= e($count ? number_format($total / $count, 2) : '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$students): ?><tr><td colspan="<?= 4 + count($subjects) ?>" class="text-center text-secondary py-4">No enrollment records found for this class and year.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="panel-card text-secondary">Choose a class and exam to generate the full downloadable class report.</div>
<?php endif; ?>

<?php if ($class && $exam): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('downloadClassSheetPdf');
  if (!btn) return;
  btn.addEventListener('click', function(){
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4');
    const title = <?= json_encode(($class['name'] ?? '') . ' - ' . ($exam['name'] ?? '') . ' (' . ($year ?? '') . ')') ?>;
    doc.setFontSize(14);
    doc.text(title, 14, 12);
    doc.setFontSize(9);
    doc.text('Class Exam Sheet', 14, 18);
    doc.autoTable({
      startY: 24,
      html: document.querySelector('.sheet-table'),
      theme: 'grid',
      styles: { fontSize: 7, cellPadding: 1.5, lineWidth: 0.1 },
      headStyles: { fillColor: [76, 175, 80], textColor: [255,255,255], fontSize: 7 },
      margin: { left: 8, right: 8 },
      didDrawPage: function(data){
        doc.setFontSize(8);
        doc.text(title, data.settings.margin.left, 10);
      }
    });
    doc.save((title.replace(/[^A-Za-z0-9\-_]+/g,'-') || 'class-sheet') + '.pdf');
  });
});
</script>
<?php endif; ?>
