<div class="panel-card"><div class="panel-head"><h5>Edit Subject Assignment</h5></div>
<form method="post" action="<?= e(base_url('/subjects/update')) ?>" class="row g-3">
    <input type="hidden" name="subject_id" value="<?= e($subject['subject_id']) ?>">
    <div class="col-md-4"><label class="form-label">Subject Name</label><input name="name" class="form-control" value="<?= e($subject['name']) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Class</label><select name="class_id" class="form-select" required><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>" <?= (int)$subject['class_id']===(int)$row['class_id'] ? 'selected' : '' ?>><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Assigned Teacher</label><select name="teacher_id" class="form-select"><option value="">Unassigned</option><?php foreach ($teachers as $row): ?><option value="<?= e($row['teacher_id']) ?>" <?= (int)($subject['teacher_id'] ?? 0)===(int)$row['teacher_id'] ? 'selected' : '' ?>><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3"><label class="form-label">Academic Year</label><input name="year" class="form-control" value="<?= e($subject['year'] ?? current_year()) ?>"></div>
    <div class="col-12"><div class="alert alert-info">Changing the assigned teacher does not affect marks already entered for this subject.</div></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save Changes</button><a class="btn btn-outline-secondary" href="<?= e(base_url('/subjects')) ?>">Cancel</a></div>
</form></div>
