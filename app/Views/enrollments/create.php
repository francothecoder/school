<div class="panel-card"><div class="panel-head"><h5>Enroll Student</h5></div>
<form method="post" action="<?= e(base_url('/enrollments/store')) ?>" class="row g-3">
<div class="col-md-4"><label class="form-label">Student</label><select name="student_id" class="form-select" required><?php foreach ($students as $row): ?><option value="<?= e($row['student_id']) ?>"><?= e($row['name'] . ' (' . $row['student_code'] . ')') ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Class</label><select name="class_id" class="form-select" required><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Section</label><select name="section_id" class="form-select"><option value="">None</option><?php foreach ($sections as $row): ?><option value="<?= e($row['section_id']) ?>"><?= e(($row['class_id']) . ' - ' . $row['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><label class="form-label">Roll</label><input name="roll" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Year</label><input name="year" class="form-control" value="<?= e(current_year()) ?>"></div>
<div class="col-12"><button class="btn btn-primary">Save Enrollment</button></div>
</form></div>