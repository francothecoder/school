<?php
$studentId = (int) ($student['student_id'] ?? request('id'));
$isTermMode = ($mode ?? 'exam') === 'term';
?>
<div class="panel-card mb-4 no-print">
    <div class="panel-head d-flex justify-content-between align-items-start">
        <div>
            <h5>Result Report Card</h5>
            <div class="form-helper">Choose a single exam result or combine all exams under Term 1, Term 2, or Term 3 into one report card.</div>
        </div>
        <div class="card-actions">
            <button class="btn btn-outline-primary" onclick="window.print()">Print</button>
            <button class="btn btn-primary" id="downloadPdfBtn">Download PDF</button>
        </div>
    </div>

    <form method="get" action="<?= e(base_url('/reports/result-slip')) ?>" class="row g-3 mobile-stack align-items-end result-filter-form">
        <input type="hidden" name="id" value="<?= e((string) $studentId) ?>">
        <div class="col-12 col-md-3">
            <label class="form-label">Academic Year</label>
            <input type="text" name="year" value="<?= e($year) ?>" class="form-control" placeholder="e.g. 2026">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">View Mode</label>
            <select name="report_mode" class="form-select" id="reportModeSelect" data-report-mode="true">
                <option value="exam" <?= !$isTermMode ? 'selected' : '' ?>>Single Exam</option>
                <option value="term" <?= $isTermMode ? 'selected' : '' ?>>Term Summary</option>
            </select>
        </div>
        <div class="col-12 col-md-3 report-mode-exam" <?= $isTermMode ? 'style="display:none"' : '' ?>>
            <label class="form-label">Exam</label>
            <select name="exam_id" class="form-select">
                <?php foreach (($exams ?? []) as $examOption): ?>
                    <option value="<?= e((string) $examOption['exam_id']) ?>" <?= (int) $examOption['exam_id'] === (int) ($exam['exam_id'] ?? 0) ? 'selected' : '' ?>>
                        <?= e($examOption['name']) ?>
                    </option>
                <?php endforeach; ?>
                <?php if (empty($exams)): ?>
                    <option value="">No exams found for <?= e($year) ?></option>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-12 col-md-3 report-mode-term" <?= $isTermMode ? '' : 'style="display:none"' ?>>
            <label class="form-label">Term</label>
            <select name="term" class="form-select">
                <option value="">Select term</option>
                <?php foreach (($terms ?? []) as $termOption): ?>
                    <option value="<?= e($termOption) ?>" <?= (string) $termOption === (string) ($term ?? '') ? 'selected' : '' ?>><?= e(is_numeric((string) $termOption) ? ('Term ' . $termOption) : $termOption) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3 d-grid">
            <button class="btn btn-primary" type="submit">View Results</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/_report-card.php'; ?>

<script>
(function(){
    const modeSelect = document.getElementById('reportModeSelect');
    if (!modeSelect) return;
    const examBlocks = document.querySelectorAll('.report-mode-exam');
    const termBlocks = document.querySelectorAll('.report-mode-term');
    const syncMode = () => {
        const termMode = modeSelect.value === 'term';
        examBlocks.forEach(el => el.style.display = termMode ? 'none' : '');
        termBlocks.forEach(el => el.style.display = termMode ? '' : 'none');
    };
    modeSelect.addEventListener('change', syncMode);
    syncMode();
})();
</script>
