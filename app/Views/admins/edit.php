<div class="panel-card">
    <div class="panel-head"><h5>Edit Admin Account</h5></div>
    <form method="post" action="<?= e(base_url('/admins/update')) ?>" class="row g-3">
        <input type="hidden" name="admin_id" value="<?= e($admin['admin_id']) ?>">
        <div class="col-md-6"><label class="form-label">Full Name *</label><input class="form-control" name="name" value="<?= e($admin['name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" value="<?= e($admin['email']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">New Password</label><input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= e($admin['phone'] ?? '') ?>"></div>
        <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2"><?= e($admin['address'] ?? '') ?></textarea></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save Changes</button><a class="btn btn-outline-secondary" href="<?= e(base_url('/admins')) ?>">Cancel</a></div>
    </form>
</div>