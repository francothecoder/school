<div class="panel-card mb-4">
    <div class="panel-head"><h5>Marks Filters</h5></div>
    <form method="get" class="row g-3 toolbar-card">
        <div class="col-md-3"><label class="form-label">Academic Year</label><input id="markYear" name="year" class="form-control" value="<?= e($year) ?>"></div>
        <div class="col-md-3"><label class="form-label">Class</label>
            <select name="class_id" class="form-select" data-role="class-subject-driver" data-subject-target="#subjectSelect" data-year-target="#markYear" data-endpoint="<?= e(base_url('/api/class-subjects')) ?>" data-selected-subject="<?= e((string)$filters['subject_id']) ?>">
                <option value="">Select class</option>
                <?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>" <?= ((string)$filters['class_id']===(string)$row['class_id']?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Subject</label>
            <select id="subjectSelect" name="subject_id" class="form-select" data-selected-value="<?= e((string)$filters['subject_id']) ?>">
                <option value="">Select subject</option>
                <?php foreach ($subjects as $row): ?><option value="<?= e($row['subject_id']) ?>" <?= ((string)$filters['subject_id']===(string)$row['subject_id']?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Exam</label><select name="exam_id" class="form-select"><option value="">Select exam</option><?php foreach ($exams as $row): ?><option value="<?= e($row['exam_id']) ?>" <?= ((string)$filters['exam_id']===(string)$row['exam_id']?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><button class="btn btn-outline-primary">Load Mark Sheet</button></div>
    </form>
</div>

<?php if ($students): ?>
<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5><?= e($subject['name'] ?? 'Subject') ?> · <?= e($exam['name'] ?? 'Exam') ?></h5>
        <div class="card-actions">
            <span class="badge-soft"><?= count($students) ?> students</span>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/analytics?class_id=' . $filters['class_id'] . '&exam_id=' . $filters['exam_id'] . '&subject_id=' . $filters['subject_id'] . '&year=' . urlencode($year))) ?>">View Analytics</a>
        </div>
    </div>
    <form method="post" action="<?= e(base_url('/marks/save')) ?>">
        <input type="hidden" name="year" value="<?= e($year) ?>">
        <input type="hidden" name="class_id" value="<?= e($filters['class_id']) ?>">
        <input type="hidden" name="subject_id" value="<?= e($filters['subject_id']) ?>">
        <input type="hidden" name="exam_id" value="<?= e($filters['exam_id']) ?>">
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead><tr><th>Roll</th><th>Student</th><th>Code</th><th>Mark Obtained</th><th>Total</th><th>Comment</th></tr></thead>
                <tbody>
                <?php foreach ($students as $row): ?>
                <tr>
                    <td><?= e($row['roll'] ?? '-') ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['student_code']) ?></td>
                    <td><input type="number" min="0" max="100" name="marks[<?= e($row['student_id']) ?>][mark_obtained]" class="form-control" value="<?= e($row['mark_obtained'] ?? '') ?>"></td>
                    <td><input type="number" min="1" name="marks[<?= e($row['student_id']) ?>][mark_total]" class="form-control" value="<?= e($row['mark_total'] ?? 100) ?>"></td>
                    <td><input name="marks[<?= e($row['student_id']) ?>][comment]" class="form-control" value="<?= e($row['comment'] ?? '') ?>"></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary">Save Marks</button>
    </form>
</div>
<?php else: ?>
<div class="panel-card"><div class="empty-state">Select class, subject and exam to load the mark sheet. Teachers only see the subjects assigned to them.</div></div>
<?php endif; ?>