<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <div>
            <h5>Admin Accounts</h5>
            <div class="form-helper">Create and manage users who have full administration access.</div>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('/admins/create')) ?>"><i class="bi bi-person-plus"></i> Add Admin</a>
    </div>
    <form class="row g-3 mb-3">
        <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q ?? '') ?>" placeholder="Search admin by name, email, or phone"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($admins as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td><?= e($row['phone'] ?? '-') ?></td>
                    <td><?= e($row['address'] ?? '-') ?></td>
                    <td>
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/admins/edit?id=' . $row['admin_id'])) ?>">Edit</a>
                            <form method="post" action="<?= e(base_url('/admins/delete')) ?>" onsubmit="return confirm('Delete this admin account?');">
                                <input type="hidden" name="admin_id" value="<?= e($row['admin_id']) ?>">
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($admins)): ?><tr><td colspan="5" class="text-center text-secondary py-4">No admin accounts found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>