<div class="panel-card"><div class="panel-head"><h5>Edit Exam</h5></div>
<form method="post" action="<?= e(base_url('/exams/update')) ?>" class="row g-3">
<input type="hidden" name="exam_id" value="<?= e($exam['exam_id']) ?>">
<div class="col-md-4"><label class="form-label">Exam Name</label><input name="name" class="form-control" value="<?= e($exam['name']) ?>" required></div>
<div class="col-md-3"><label class="form-label">Term</label><select name="exam_term" class="form-select"><option value="Term 1" <?= (string)$exam['exam_term']==='Term 1' || (string)$exam['exam_term']==='1' ? 'selected' : '' ?>>Term 1</option><option value="Term 2" <?= (string)$exam['exam_term']==='Term 2' || (string)$exam['exam_term']==='2' ? 'selected' : '' ?>>Term 2</option><option value="Term 3" <?= (string)$exam['exam_term']==='Term 3' || (string)$exam['exam_term']==='3' ? 'selected' : '' ?>>Term 3</option></select></div>
<div class="col-md-3"><label class="form-label">Date</label><input name="date" class="form-control" value="<?= e($exam['date'] ?? '') ?>"></div>
<div class="col-md-2"><label class="form-label">Year</label><input name="year" class="form-control" value="<?= e($exam['year'] ?? current_year()) ?>"></div>
<div class="col-md-6"><label class="form-label">Comment</label><input name="comment" class="form-control" value="<?= e($exam['comment'] ?? '') ?>"></div>
<div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save Changes</button><a class="btn btn-outline-secondary" href="<?= e(base_url('/exams')) ?>">Cancel</a></div>
</form></div>
