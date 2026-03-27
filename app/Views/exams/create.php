<div class="panel-card"><div class="panel-head"><h5>Create Exam</h5></div>
<form method="post" action="<?= e(base_url('/exams/store')) ?>" class="row g-3">
<div class="col-md-4"><label class="form-label">Exam Name</label><input name="name" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Term</label><input name="exam_term" class="form-control" placeholder="Term 1"></div>
<div class="col-md-3"><label class="form-label">Date</label><input name="date" class="form-control" placeholder="04/20/2025"></div>
<div class="col-md-2"><label class="form-label">Year</label><input name="year" class="form-control" value="<?= e(current_year()) ?>"></div>
<div class="col-md-6"><label class="form-label">Comment</label><input name="comment" class="form-control" placeholder="EXAM"></div>
<div class="col-12"><button class="btn btn-primary">Save Exam</button></div>
</form></div>