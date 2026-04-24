<div class="panel-card mb-4">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <div>
            <h5>Send Results by Email</h5>
            <div class="text-muted small">Send marks summaries or full report cards as PDF attachments.</div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/email-results/logs')) ?>"><i class="bi bi-clock-history"></i> Email Logs</a>
    </div>
    <form method="post" action="<?= e(base_url('/email-results')) ?>" class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Academic Year</label>
            <input name="year" class="form-control" value="<?= e($year) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="class_id" class="form-select" onchange="this.form.submit()">
                <option value="0">Select class</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= e($class['class_id']) ?>" <?= (int)$classId === (int)$class['class_id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Send Mode</label>
            <select name="send_mode" class="form-select">
                <option value="class" <?= $sendMode === 'class' ? 'selected' : '' ?>>Whole Class</option>
                <option value="single" <?= $sendMode === 'single' ? 'selected' : '' ?>>Single Student / Custom Email</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Email Content</label>
            <select name="email_type" class="form-select">
                <option value="report_card" <?= $emailType === 'report_card' ? 'selected' : '' ?>>Full Report Card PDF</option>
                <option value="marks" <?= $emailType === 'marks' ? 'selected' : '' ?>>Marks Summary PDF</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Report Mode</label>
            <select name="view_mode" class="form-select">
                <option value="exam" <?= $viewMode !== 'term' ? 'selected' : '' ?>>Single Exam</option>
                <option value="term" <?= $viewMode === 'term' ? 'selected' : '' ?>>Term Summary</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Exam</label>
            <select name="exam_id" class="form-select">
                <option value="0">Select exam</option>
                <?php foreach ($exams as $exam): ?>
                    <option value="<?= e($exam['exam_id']) ?>" <?= (int)$examId === (int)$exam['exam_id'] ? 'selected' : '' ?>><?= e($exam['name']) ?><?= !empty($exam['exam_term']) ? ' · Term ' . e($exam['exam_term']) : '' ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Used when Report Mode is Single Exam.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Term</label>
            <select name="term" class="form-select">
                <option value="">Select term</option>
                <?php foreach ($terms as $t): ?>
                    <option value="<?= e($t) ?>" <?= (string)$term === (string)$t ? 'selected' : '' ?>>Term <?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Used when Report Mode is Term Summary.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Student</label>
            <select name="student_id" class="form-select">
                <option value="0">Select student</option>
                <?php foreach ($studentsForSelect as $student): ?>
                    <option value="<?= e($student['student_id']) ?>" <?= (int)$studentId === (int)$student['student_id'] ? 'selected' : '' ?>><?= e($student['name']) ?><?= !empty($student['student_code']) ? ' · ' . e($student['student_code']) : '' ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Used for single-student email.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Custom Recipient Email</label>
            <input name="email" type="email" class="form-control" value="<?= e($customEmail) ?>" placeholder="parent@example.com">
            <div class="form-text">Used for individual email only. Leave blank to use the student's saved email.</div>
        </div>
        <div class="col-md-6 d-flex align-items-end gap-2 flex-wrap">
            <button class="btn btn-outline-primary" name="action" value="preview"><i class="bi bi-eye"></i> Preview Emails</button>
            <button class="btn btn-primary" name="action" value="send" onclick="return confirm('Send result emails with PDF attachments now?');"><i class="bi bi-send"></i> Send Email Results</button>
        </div>
    </form>
</div>

<?php if ($summary): ?>
<div class="panel-card mb-4">
    <div class="panel-head"><h5>Email Batch Summary</h5></div>
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
        <h5>Email Preview</h5>
        <span class="badge-soft"><?= count($previewRows) ?> email(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Attachment</th>
                    <th>Preview</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($previewRows as $row): ?>
                <tr>
                    <td><strong><?= e($row['student_name']) ?></strong><br><span class="text-muted small"><?= e($row['student_code']) ?></span></td>
                    <td><?= e($row['recipient_email']) ?></td>
                    <td><?= e($row['attachment_name']) ?><br><span class="small text-muted"><?= e($row['email_type'] === 'marks' ? 'Marks Summary' : 'Report Card') ?></span></td>
                    <td><?= e($row['preview']) ?></td>
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
<div class="panel-card"><div class="empty-state">Select class, report mode, and email type, then preview before sending. For individual email, select a student and optionally type a parent/guardian email.</div></div>
<?php endif; ?>
