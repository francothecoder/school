<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>Students</h5>
        <?php if (current_user()['role']==='admin'): ?>
        <div class="card-actions">
            <a class="btn btn-outline-primary" href="<?= e(base_url('/students/bulk-admission')) ?>">Bulk Admission</a>
            <a class="btn btn-primary" href="<?= e(base_url('/students/create')) ?>">Add Student</a>
        </div>
        <?php endif; ?>
    </div>
    <form class="row g-3 mb-3">
        <div class="col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name, code or email"></div>
        <div class="col-md-3"><select class="form-select" name="class_id"><option value="">All classes</option><?php foreach (($classes ?? []) as $classItem): ?><option value="<?= e($classItem['class_id']) ?>" <?= (int)($classId ?? 0) === (int)$classItem['class_id'] ? 'selected' : '' ?>><?= e($classItem['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><input class="form-control" name="year" value="<?= e($year) ?>" placeholder="Academic year"></div>
        <div class="col-md-2 d-flex gap-2 flex-wrap"><button class="btn btn-outline-primary">Filter</button><?php if ((int)($classId ?? 0) > 0): ?><a class="btn btn-outline-secondary" data-loader-click="Preparing student export..." href="<?= e(base_url('/students?q=' . urlencode((string)$q) . "&year=" . urlencode((string)$year) . "&class_id=" . (int)$classId . "&download=csv")) ?>">Export CSV</a><button type="button" class="btn btn-outline-dark" onclick="window.print()">Print</button><?php endif; ?></div>
    </form>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Student</th><th>Code</th><th>Class</th><th>Section</th><th>Roll</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($students as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['student_code'] ?? '-') ?></td>
                <td><?= e($row['class_name'] ?? '-') ?></td>
                <td><?= e($row['section_name'] ?? '-') ?></td>
                <td><?= e($row['roll'] ?? '-') ?></td>
                <td>
                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/students/show?id=' . $row['student_id'])) ?>">View</a>
                        <?php if (current_user()['role']==='admin'): ?>
                        <form method="post" action="<?= e(base_url('/students/delete')) ?>" onsubmit="return confirm('Delete this student and all related academic records?');" class="d-inline">
                            <input type="hidden" name="student_id" value="<?= e($row['student_id']) ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$students): ?><tr><td colspan="6" class="empty-state">No students found for the selected filters.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>