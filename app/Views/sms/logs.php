<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>SMS Logs</h5>
        <a href="<?= e(base_url('/sms/results')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-send"></i> Send Results SMS</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Exam</th>
                    <th>Phone</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['created_at'] ?? '') ?></td>
                    <td><?= e($log['student_name'] ?? ('#' . ($log['student_id'] ?? ''))) ?><br><span class="text-muted small"><?= e($log['class_name'] ?? '') ?></span></td>
                    <td><?= e($log['exam_name'] ?? '') ?><br><span class="text-muted small"><?= e($log['year'] ?? '') ?></span></td>
                    <td><?= e($log['phone'] ?? '') ?></td>
                    <td><?= e(strtoupper((string)($log['provider'] ?? ''))) ?></td>
                    <td><span class="badge <?= ($log['status'] ?? '') === 'sent' ? 'bg-success' : (($log['status'] ?? '') === 'failed' ? 'bg-danger' : 'bg-warning text-dark') ?>"><?= e($log['status'] ?? '') ?></span></td>
                    <td class="small"><?= e($log['message'] ?? '') ?></td>
                    <td class="small text-muted"><?= e($log['error_message'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="8"><div class="empty-state">No SMS logs yet.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
