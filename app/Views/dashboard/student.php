<div class="panel-card mb-4">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>Latest Announcements</h5>
        <span class="badge-soft"><?= count($announcements ?? []) ?> updates</span>
    </div>
    <?php if (!empty($announcements)): foreach ($announcements as $notice): ?>
        <div class="announcement-inline">
            <div class="d-flex justify-content-between gap-3 flex-wrap">
                <strong><?= e($notice['notice_title'] ?? 'Announcement') ?></strong>
                <span class="text-secondary small"><?= e(announcement_date($notice)) ?></span>
            </div>
            <div class="text-secondary mt-2"><?= nl2br(e($notice['notice'] ?? '')) ?></div>
        </div>
    <?php endforeach; else: ?>
        <div class="text-secondary">No announcements available yet.</div>
    <?php endif; ?>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-4"><div class="metric-card"><div class="metric-label">Student Code</div><div class="metric-value fs-3"><?= e($student['student_code'] ?? '-') ?></div></div></div>
    <div class="col-md-4"><div class="metric-card"><div class="metric-label">Current Class</div><div class="metric-value fs-3"><?= e($enrollment['class_name'] ?? '-') ?></div></div></div>
    <div class="col-md-4"><div class="metric-card"><div class="metric-label">Section</div><div class="metric-value fs-3"><?= e($enrollment['section_name'] ?? '-') ?></div></div></div>
</div>
<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>Recent Marks</h5>
        <a class="btn btn-primary btn-sm" href="<?= e(base_url('/reports/result-slip')) ?>">Open Result Slip</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Exam</th><th>Subject</th><th>Mark</th></tr></thead>
            <tbody>
            <?php foreach ($recentMarks as $row): ?>
            <tr><td><?= e($row['exam_name'] ?? '-') ?></td><td><?= e($row['subject_name'] ?? '-') ?></td><td><?= e($row['mark_obtained'] ?? '-') ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$recentMarks): ?><tr><td colspan="3" class="text-center text-secondary py-4">No marks found yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
