<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form class="row g-3 mb-4" method="get" action="<?= e(base_url('/activity-logs')) ?>">
            <div class="col-md-3">
                <label class="form-label">Module</label>
                <select class="form-select" name="module">
                    <option value="">All modules</option>
                    <?php foreach ($modules as $row): ?>
                        <option value="<?= e($row['module_name']) ?>" <?= $module === ($row['module_name'] ?? '') ? 'selected' : '' ?>><?= e(ucfirst((string) $row['module_name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Action</label>
                <select class="form-select" name="action">
                    <option value="">All actions</option>
                    <?php foreach ($actions as $row): ?>
                        <option value="<?= e($row['action']) ?>" <?= $action === ($row['action'] ?? '') ? 'selected' : '' ?>><?= e(ucfirst((string) $row['action'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="">All roles</option>
                    <?php foreach ($roles as $row): ?>
                        <option value="<?= e($row['user_role']) ?>" <?= $role === ($row['user_role'] ?? '') ? 'selected' : '' ?>><?= e(ucfirst((string) $row['user_role'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input class="form-control" type="text" name="q" value="<?= e($q) ?>" placeholder="Description or user">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-primary w-100">Go</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Audit Trail</h5>
                <p class="text-secondary small mb-0">Track who entered marks, edits made, backups, restores, and key admin actions.</p>
            </div>
            <div class="badge text-bg-light border"><?= count($logs) ?> records</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$logs): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-4">No activity found.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="small"><?= e((string) ($log['created_at'] ?? '')) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e((string) ($log['user_name'] ?? 'System')) ?></div>
                            <div class="small text-secondary text-capitalize"><?= e((string) ($log['user_role'] ?? '')) ?> #<?= e((string) ($log['user_id'] ?? '0')) ?></div>
                        </td>
                        <td><span class="badge text-bg-light border"><?= e((string) ($log['module_name'] ?? '')) ?></span></td>
                        <td><span class="badge text-bg-dark"><?= e((string) ($log['action'] ?? '')) ?></span></td>
                        <td><?= e((string) ($log['description'] ?? '')) ?></td>
                        <td class="small">
                            <?php if (!empty($log['new_values'])): ?>
                                <details>
                                    <summary class="text-primary">View</summary>
                                    <pre class="mt-2 bg-light rounded p-2 small mb-0" style="white-space: pre-wrap;"><?= e((string) ($log['new_values'] ?? '')) ?></pre>
                                </details>
                            <?php else: ?>
                                <span class="text-secondary">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
