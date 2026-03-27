<div class="panel-card"><div class="panel-head"><h5>Add Section</h5></div>
<form method="post" action="<?= e(base_url('/sections/store')) ?>" class="row g-3">
    <div class="col-md-4"><label class="form-label">Section Name</label><input name="name" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Nickname</label><input name="nick_name" class="form-control"></div>
    <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select" required><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Section Teacher</label><select name="teacher_id" class="form-select"><option value="">None</option><?php foreach ($teachers as $row): ?><option value="<?= e($row['teacher_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><button class="btn btn-primary">Create Section</button></div>
</form></div>