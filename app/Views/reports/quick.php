<?php $isTermMode = ($mode ?? 'exam') === 'term'; ?>
<div class="login-wrap py-4">
    <div class="login-card premium-login-card quick-card results-engine-card">
        <div class="login-side">
            <span class="eyebrow">Quick Results</span>
            <h2>Check Results Without Login</h2>
            <p>Use student code, email, or phone number to open a single exam result or a full term summary instantly.</p>
            <div class="mini-announcements mt-4">
                <div class="metric-label text-white-50 mb-2">Recent announcements</div>
                <?php foreach (($announcements ?? []) as $notice): ?>
                    <div class="mini-announce-item">
                        <strong><?= e($notice['notice_title'] ?? 'Announcement') ?></strong>
                        <div><?= e(announcement_date($notice)) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($announcements)): ?><div class="text-white-50">No recent announcements.</div><?php endif; ?>
            </div>
            <div class="mt-4 d-grid gap-2">
                <a class="btn btn-light btn-lg" href="<?= e(base_url('/login')) ?>">Back to Login</a>
            </div>
        </div>
        <div class="login-form-pane">
            <div class="login-pane-header">
                <div>
                    <h3 class="mb-1">Public Result Lookup</h3>
                    <div class="text-secondary">Integrated report engine with term summaries, best-six logic, remarks, and rankings.</div>
                </div>
                <?php if (!empty($student)): ?><button class="btn btn-primary" id="downloadPdfBtn" type="button">Download PDF</button><?php endif; ?>
            </div>

            <form method="get" action="<?= e(base_url('/results/quick')) ?>" class="row g-3 result-filter-form">
                <div class="col-12">
                    <label class="form-label">Student Code, Email, or Phone</label>
                    <input type="text" name="student_code" class="form-control" value="<?= e($studentCode ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Academic Year</label>
                    <input type="text" name="year" class="form-control" value="<?= e($year ?? current_year()) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">View Mode</label>
                    <select name="report_mode" class="form-select" id="quickReportModeSelect">
                        <option value="exam" <?= !$isTermMode ? 'selected' : '' ?>>Single Exam</option>
                        <option value="term" <?= $isTermMode ? 'selected' : '' ?>>Term Summary</option>
                    </select>
                </div>
                <div class="col-md-3 report-mode-exam" <?= $isTermMode ? 'style="display:none"' : '' ?>>
                    <label class="form-label">Exam</label>
                    <select name="exam_id" class="form-select">
                        <?php foreach (($exams ?? []) as $examOption): ?>
                            <option value="<?= e((string) $examOption['exam_id']) ?>" <?= (int) $examOption['exam_id'] === (int) (($exam['exam_id'] ?? 0)) ? 'selected' : '' ?>><?= e($examOption['name']) ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($exams)): ?><option value="">No exams available</option><?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3 report-mode-term" <?= $isTermMode ? '' : 'style="display:none"' ?>>
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select">
                        <option value="">Select term</option>
                        <?php foreach (($terms ?? []) as $termOption): ?>
                            <option value="<?= e($termOption) ?>" <?= (string) $termOption === (string) ($term ?? '') ? 'selected' : '' ?>><?= e(is_numeric((string) $termOption) ? ('Term ' . $termOption) : $termOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-primary btn-lg">Check Result</button>
                </div>
            </form>

            <?php if (!empty($student)): ?>
                <div class="mt-4"><?php require __DIR__ . '/_report-card.php'; ?></div>
            <?php else: ?>
                <div class="result-preview mt-4">
                    <div class="metric-label mb-2">How it works</div>
                    <div class="best-six-grid">
                        <div class="best-six-item"><strong>1. Identify the student</strong><span>Enter student code, email, or phone number.</span></div>
                        <div class="best-six-item"><strong>2. Choose report mode</strong><span>Select one exam or a full term summary across all exams in the chosen term.</span></div>
                        <div class="best-six-item"><strong>3. Download or print</strong><span>Generate a polished report card once the record loads.</span></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function(){
    const modeSelect = document.getElementById('quickReportModeSelect');
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
