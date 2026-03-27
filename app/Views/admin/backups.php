<div class="row g-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="mb-3">Create Backup</h5>
                <div class="d-grid gap-3">
                    <form method="post" action="<?= e(base_url('/backups/create-database')) ?>">
                        <button class="btn btn-primary w-100"><i class="bi bi-database-down me-2"></i>Create Database Backup</button>
                    </form>
                    <form method="post" action="<?= e(base_url('/backups/create-full')) ?>">
                        <button class="btn btn-outline-primary w-100"><i class="bi bi-box-seam me-2"></i>Create Full Backup</button>
                    </form>
                </div>
                <div class="small text-secondary mt-3">
                    Full backup includes the SQL database export and the uploads folder containing logos, signatures, and stored assets.
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Restore Backup</h5>
                <form method="post" action="<?= e(base_url('/backups/restore')) ?>" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Backup file (.sql or .zip)</label>
                        <input class="form-control" type="file" name="backup_file" accept=".sql,.zip" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm action</label>
                        <input class="form-control" type="text" name="confirm_phrase" placeholder="Type RESTORE" required>
                    </div>
                    <div class="alert alert-warning small">
                        Restore will overwrite the current database. The system creates an automatic pre-restore database backup first.
                    </div>
                    <button class="btn btn-danger w-100"><i class="bi bi-arrow-clockwise me-2"></i>Run Restore</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Available Backups</h5>
                        <p class="text-secondary small mb-0">Download snapshots for safety or migration.</p>
                    </div>
                    <div class="badge text-bg-light border"><?= count($backups) ?> items</div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead>
                            <tr>
                                <th>Backup</th>
                                <th>Type</th>
                                <th>Created By</th>
                                <th>When</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$backups): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">No backups available yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e((string) ($backup['file_name'] ?? '')) ?></div>
                                    <div class="small text-secondary"><?= e((string) ($backup['notes'] ?? '')) ?></div>
                                </td>
                                <td><span class="badge text-bg-light border text-uppercase"><?= e((string) ($backup['backup_type'] ?? '')) ?></span></td>
                                <td>
                                    <div><?= e((string) ($backup['created_by_name'] ?? 'System')) ?></div>
                                    <div class="small text-secondary text-capitalize"><?= e((string) ($backup['created_by_role'] ?? '')) ?></div>
                                </td>
                                <td class="small"><?= e((string) ($backup['created_at'] ?? '')) ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-primary" href="<?= e(base_url('/backups/download?id=' . (int) ($backup['id'] ?? 0))) ?>">
                                        <i class="bi bi-download me-1"></i>Download
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
