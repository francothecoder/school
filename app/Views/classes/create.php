<div class="panel-card"><div class="panel-head"><h5>Add Class</h5></div>
<form method="post" action="<?= e(base_url('/classes/store')) ?>" class="row g-3">
<div class="col-md-4"><label class="form-label">Class Name</label><input name="name" class="form-control" placeholder="e.g. 10F" required></div>
<div class="col-md-3"><label class="form-label">Numeric Level</label><input name="name_numeric" class="form-control" placeholder="e.g. 10" required></div>
<div class="col-md-5"><label class="form-label">Class Teacher</label><select name="teacher_id" class="form-select"><option value="">None</option><?php foreach ($teachers as $row): ?><option value="<?= e($row['teacher_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Section Name</label><input name="section_name" class="form-control" placeholder="e.g. SENIOR SECONDARY"></div>
<div class="col-md-6"><label class="form-label">Section Nickname</label><input name="section_nick_name" class="form-control"></div>
<div class="col-12"><button class="btn btn-primary">Create Class</button></div>
</form></div>