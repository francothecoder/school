<div class="panel-card">
    <div class="panel-head"><h5>My Profile</h5></div>
    <form method="post" action="<?= e(base_url('/profile/update')) ?>" class="row g-3">
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="<?= e($profile['name'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input name="email" class="form-control" value="<?= e($profile['email'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($profile['phone'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= e($profile['address'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">New Password</label><input type="password" name="password" class="form-control"></div>
        <div class="col-12"><button class="btn btn-primary">Save Profile</button></div>
    </form>
</div>