<div class="panel-card"><div class="panel-head"><h5>Assign Subject</h5></div>
<form method="post" action="<?= e(base_url('/subjects/store')) ?>" class="row g-3">
    <div class="col-md-5"><label class="form-label">Subject(s)</label><textarea name="name" class="form-control" rows="3" placeholder="ENG, MATHS, SCIENCE or one per line" required></textarea></div>
    <div class="col-md-3"><label class="form-label">Class</label><select name="class_id" class="form-select" required><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Teacher</label><select name="teacher_id" class="form-select"><option value="">None</option><?php foreach ($teachers as $row): ?><option value="<?= e($row['teacher_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Academic Year</label><input name="year" class="form-control" value="<?= e(current_year()) ?>"></div>
    <div class="col-12"><button class="btn btn-primary">Save Subject(s)</button></div>
</form></div>