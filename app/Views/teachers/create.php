<div class="panel-card"><div class="panel-head"><h5>Add Teacher</h5></div><form method="post" action="<?= e(base_url('/teachers/store')) ?>" class="row g-3">
    
    <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="<?= e($teacher['name'] ?? '') ?>" required></div>
    <div class="col-md-6"><label class="form-label">Email</label><input name="email" class="form-control" value="<?= e($teacher['email'] ?? '') ?>" required></div>
    <div class="col-md-3"><label class="form-label">Sex</label><select name="sex" class="form-select"><option value="">Select</option><option value="male" <?= (($teacher['sex'] ?? '')==='male'?'selected':'') ?>>Male</option><option value="female" <?= (($teacher['sex'] ?? '')==='female'?'selected':'') ?>>Female</option></select></div>
    <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($teacher['phone'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Designation</label><input name="designation" class="form-control" value="<?= e($teacher['designation'] ?? '') ?>"></div>
    <div class="col-md-12"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= e($teacher['address'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label">Password <?= '(required for new account)' ?></label><input name="password" type="password" class="form-control"></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Create Teacher</button><a href="<?= e(base_url('/teachers')) ?>" class="btn btn-light">Cancel</a></div>
</form></div>