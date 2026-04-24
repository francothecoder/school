<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <div>
            <h5>Email Logs</h5>
            <div class="text-muted small">Recent result emails and PDF attachment delivery attempts.</div>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="<?= e(base_url('/email-results')) ?>"><i class="bi bi-send"></i> Send Results</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['created_at'] ?? '') ?></td>
                    <td><strong><?= e($log['student_name'] ?? '-') ?></strong><br><span class="small text-muted"><?= e($log['subject'] ?? '') ?></span></td>
                    <td><?= e($log['class_name'] ?? '-') ?></td>
                    <td><?= e($log['recipient_email'] ?? '') ?></td>
                    <td><?= e(($log['email_type'] ?? '') === 'marks' ? 'Marks Summary' : 'Report Card') ?></td>
                    <td>
                        <?php $status = (string)($log['status'] ?? ''); ?>
                        <span class="badge <?= $status === 'sent' ? 'bg-success' : ($status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') ?>"><?= e(ucfirst($status)) ?></span>
                    </td>
                    <td class="small text-muted"><?= e($log['error_message'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="7" class="empty-row">No email logs yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
