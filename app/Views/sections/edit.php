<div class="panel-card"><div class="panel-head"><h5>Edit Section</h5></div>
<form method="post" action="<?= e(base_url('/sections/update')) ?>" class="row g-3">
    <input type="hidden" name="section_id" value="<?= e($section['section_id']) ?>">
    <div class="col-md-4"><label class="form-label">Section Name</label><input name="name" class="form-control" value="<?= e($section['name']) ?>" required></div>
    <div class="col-md-4"><label class="form-label">Nickname</label><input name="nick_name" class="form-control" value="<?= e($section['nick_name'] ?? '') ?>"></div>
    <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select" required><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>" <?= ((string)$row['class_id']===(string)$section['class_id']?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Section Teacher</label><select name="teacher_id" class="form-select"><option value="">None</option><?php foreach ($teachers as $row): ?><option value="<?= e($row['teacher_id']) ?>" <?= ((string)$row['teacher_id']===(string)($section['teacher_id'] ?? '')?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><button class="btn btn-primary">Save Changes</button></div>
</form></div>