<div class="panel-card">
    <div class="panel-head"><h5>Create Admin Account</h5></div>
    <form method="post" action="<?= e(base_url('/admins/store')) ?>" class="row g-3">
        <div class="col-md-6"><label class="form-label">Full Name *</label><input class="form-control" name="name" required></div>
        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" required></div>
        <div class="col-md-6"><label class="form-label">Password *</label><input type="password" class="form-control" name="password" required></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
        <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2"></textarea></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Create Admin</button><a class="btn btn-outline-secondary" href="<?= e(base_url('/admins')) ?>">Cancel</a></div>
    </form>
</div>