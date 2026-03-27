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
    <div class="col-md-4"><div class="metric-card"><div class="metric-label">Assigned Subjects</div><div class="metric-value"><?= count($assignedSubjects) ?></div></div></div>
    <div class="col-md-4"><div class="metric-card"><div class="metric-label">Reachable Students</div><div class="metric-value"><?= number_format($studentCount) ?></div></div></div>
    <div class="col-md-4"><div class="metric-card"><div class="metric-label">Current Year</div><div class="metric-value"><?= e($year) ?></div></div></div>
</div>
<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>Your Subject Allocation</h5>
        <a class="btn btn-primary btn-sm" href="<?= e(base_url('/marks')) ?>">Open Marks Entry</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Subject</th><th>Class</th><th>Year</th></tr></thead>
            <tbody>
            <?php foreach ($assignedSubjects as $row): ?>
            <tr><td><?= e($row['name']) ?></td><td><?= e($row['class_name']) ?></td><td><?= e($row['year']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$assignedSubjects): ?><tr><td colspan="3" class="text-center text-secondary py-4">No subjects assigned yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
