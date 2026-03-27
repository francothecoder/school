<div class="panel-card mb-4">
    <div class="panel-head"><h5>Attendance Register</h5></div>
    <form method="get" class="row g-3">
        <div class="col-md-4"><label class="form-label">Year</label><input name="year" class="form-control" value="<?= e($year) ?>"></div>
        <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="<?= e($date) ?>"></div>
        <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select"><option value="">Select Class</option><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>" <?= ((string)$classId===(string)$row['class_id']?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><button class="btn btn-outline-primary">Load Register</button></div>
    </form>
</div>
<?php if ($students): ?>
<div class="panel-card">
    <form method="post" action="<?= e(base_url('/attendance/save')) ?>">
        <input type="hidden" name="year" value="<?= e($year) ?>">
        <input type="hidden" name="date" value="<?= e($date) ?>">
        <input type="hidden" name="class_id" value="<?= e($classId) ?>">
        <div class="table-responsive"><table class="table table-modern"><thead><tr><th>Student</th><th>Code</th><th>Present</th><th>Absent</th></tr></thead><tbody><?php foreach ($students as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e($row['student_code']) ?></td><td><input type="radio" name="attendance[<?= e($row['student_id']) ?>]" value="1" <?= ((string)($row['status'] ?? '1')==='1'?'checked':'') ?>></td><td><input type="radio" name="attendance[<?= e($row['student_id']) ?>]" value="0" <?= ((string)($row['status'] ?? '')==='0'?'checked':'') ?>></td></tr><?php endforeach; ?></tbody></table></div>
        <button class="btn btn-primary">Save Attendance</button>
    </form>
</div>
<?php endif; ?>