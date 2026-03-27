
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="panel-card compact-announcements-card">
            <div class="panel-head d-flex justify-content-between align-items-center">
                <div>
                    <h5>Latest Announcements</h5>
                    <div class="form-helper">Recent updates only, to keep the dashboard clean and mobile-friendly.</div>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span class="badge-soft"><?= count($announcements ?? []) ?> updates</span>
                    <?php if ((current_user()['role'] ?? '') === 'admin'): ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/announcements')) ?>">Manage</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($announcements)): ?>
                <div class="announcement-list-compact">
                    <?php foreach (array_slice($announcements, 0, 3) as $notice): ?>
                        <div class="announcement-inline compact">
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <strong><?= e($notice['notice_title'] ?? 'Announcement') ?></strong>
                                <span class="text-secondary small"><?= e(announcement_date($notice)) ?></span>
                            </div>
                            <div class="text-secondary mt-2 announcement-excerpt"><?= e(announcement_excerpt((string) ($notice['notice'] ?? ''), 160)) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-secondary">No announcements available yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="row g-4 mb-4">
    <?php foreach ($stats as $label => $value): ?>
        <div class="col-md-4 col-xl-2">
            <div class="metric-card">
                <div class="metric-label"><?= e(ucfirst($label)) ?></div>
                <div class="metric-value"><?= number_format((int) $value) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel-card">
            <div class="panel-head"><h5>Recent Enrollments</h5></div>
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead><tr><th>Student</th><th>Code</th><th>Class</th><th>Section</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentEnrollments as $row): ?>
                        <tr>
                            <td><?= e($row['student_name'] ?? 'Unknown') ?></td>
                            <td><?= e($row['student_code'] ?? '-') ?></td>
                            <td><?= e($row['class_name'] ?? '-') ?></td>
                            <td><?= e($row['section_name'] ?? '-') ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/students/show?id=' . $row['student_id'])) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentEnrollments): ?><tr><td colspan="5" class="text-center text-secondary py-4">No records found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel-card">
            <div class="panel-head"><h5>Top Class Population</h5></div>
            <div class="list-group list-group-flush">
                <?php foreach ($topClasses as $row): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span><?= e($row['name']) ?></span>
                    <span class="badge text-bg-primary rounded-pill"><?= e($row['total_students']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
