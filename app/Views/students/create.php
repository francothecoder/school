<div class="panel-card"><div class="panel-head"><h5>Add Student</h5></div>
<form method="post" action="<?= e(base_url('/students/store')) ?>" class="row g-3">
    <div class="col-md-4"><label class="form-label">Student Code</label><input name="student_code" class="form-control" value="<?= e($suggestedStudentCode ?? '') ?>" required><div class="form-helper mt-2">Auto-generated uniquely by default, but you can edit it before saving.</div></div>
    <div class="col-md-8"><label class="form-label">Full Name</label><input name="name" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Email <span class="text-muted">(Optional)</span></label><input name="email" type="email" class="form-control"><div class="form-helper mt-2">You can leave this blank. Only student code, full name, and sex are required.</div></div>
    <div class="col-md-4"><label class="form-label">Password</label><input name="password" type="password" class="form-control"><div class="form-helper mt-2">Leave blank to use the final student code.</div></div>
    <div class="col-md-4"><label class="form-label">Sex</label><select name="sex" class="form-select" required><option value="">Select</option><option value="male">Male</option><option value="female">Female</option></select></div>
    <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
    <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select" data-role="class-section-driver" data-section-target="#sectionSelect" data-endpoint="<?= e(base_url('/api/class-sections')) ?>" required><option value="">Select class</option><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Section</label><select id="sectionSelect" name="section_id" class="form-select"><option value="">Select section</option><?php foreach ($sections as $row): ?><option value="<?= e($row['section_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Roll Number</label><input name="roll" class="form-control"></div>
    <div class="col-md-4"><label class="form-label">Academic Year</label><input name="year" class="form-control" value="<?= e(current_year()) ?>"></div>
    <div class="col-md-8"><label class="form-label">Address</label><input name="address" class="form-control"></div>
    <div class="col-12"><button class="btn btn-primary">Save Student</button></div>
</form></div>
