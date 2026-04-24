<div class="panel-card mb-4">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>Send Results by SMS</h5>
        <a href="<?= e(base_url('/sms/logs')) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-clock-history"></i> SMS Logs</a>
    </div>
    <form method="post" action="<?= e(base_url('/sms/results')) ?>" class="row g-3 toolbar-card">
        <div class="col-md-3">
            <label class="form-label">Academic Year</label>
            <input name="year" class="form-control" value="<?= e($year) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="class_id" class="form-select">
                <option value="">Select class</option>
                <?php foreach ($classes as $row): ?>
                    <option value="<?= e($row['class_id']) ?>" <?= ((string)$classId === (string)$row['class_id']) ? 'selected' : '' ?>><?= e($row['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Exam</label>
            <select name="exam_id" class="form-select" required>
                <option value="">Select exam</option>
                <?php foreach ($exams as $row): ?>
                    <option value="<?= e($row['exam_id']) ?>" <?= ((string)$examId === (string)$row['exam_id']) ? 'selected' : '' ?>><?= e($row['name']) ?><?= !empty($row['exam_term']) ? ' · Term ' . e($row['exam_term']) : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Send Mode</label>
            <select name="send_mode" class="form-select">
                <option value="class" <?= $sendMode === 'class' ? 'selected' : '' ?>>Whole selected class</option>
                <option value="single" <?= $sendMode === 'single' ? 'selected' : '' ?>>Individual student / number</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Individual Student</label>
            <select name="student_id" class="form-select">
                <option value="">Select student for individual SMS</option>
                <?php foreach ($studentsForSelect as $student): ?>
                    <option value="<?= e($student['student_id']) ?>" <?= ((string)$studentId === (string)$student['student_id']) ? 'selected' : '' ?>><?= e($student['name']) ?> · <?= e($student['student_code'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Select a class first, then preview to load students in that class.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Custom Phone Number</label>
            <input name="phone" class="form-control" value="<?= e($customPhone) ?>" placeholder="097xxxxxxx or 26097xxxxxxx">
            <div class="form-text">Used for individual SMS only. Leave blank to use the student's saved phone number.</div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary" name="action" value="preview"><i class="bi bi-eye"></i> Preview SMS</button>
            <button class="btn btn-primary" name="action" value="send" onclick="return confirm('Send result SMS now? This may use SMS units for every valid student number.');"><i class="bi bi-send"></i> Send SMS Results</button>
        </div>
    </form>
</div>

<?php if ($summary): ?>
<div class="panel-card mb-4">
    <div class="panel-head"><h5>SMS Batch Summary</h5></div>
    <div class="row g-3">
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= e($summary['total'] ?? 0) ?></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Ready</div><div class="stat-value"><?= e($summary['ready'] ?? 0) ?></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Sent</div><div class="stat-value"><?= e($summary['sent'] ?? 0) ?></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Skipped / Failed</div><div class="stat-value"><?= e(($summary['skipped'] ?? 0) + ($summary['failed'] ?? 0)) ?></div></div></div>
    </div>
</div>
<?php endif; ?>

<?php if ($previewRows): ?>
<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>SMS Preview</h5>
        <span class="badge-soft"><?= count($previewRows) ?> message(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Length</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($previewRows as $row): ?>
                <tr>
                    <td><strong><?= e($row['student_name']) ?></strong><br><span class="text-muted small"><?= e($row['student_code']) ?></span></td>
                    <td><?= e($row['phone'] ?: $row['raw_phone']) ?></td>
                    <td><?= e($row['message']) ?></td>
                    <td><?= e($row['length']) ?></td>
                    <td>
                        <?php if (($row['status'] ?? '') === 'ready'): ?>
                            <span class="badge bg-success">Ready</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Skipped</span><br><span class="small text-muted"><?= e($row['reason'] ?? '') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="panel-card"><div class="empty-state">Select a class and exam, then preview before sending. For individual SMS, select a student and optionally type a parent/guardian number.</div></div>
<?php endif; ?>
