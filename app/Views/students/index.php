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

    <?php if (current_user()['role']==='admin' && (int)($classId ?? 0) > 0 && $students): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><strong>Bulk deletion enabled:</strong> select students in this class and delete them together with enrollments, marks, attendance and related academic data.</div>
        <small>This action cannot be undone.</small>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('/students/bulk-delete')) ?>" onsubmit="return confirm('Delete selected students and all their related academic records? This cannot be undone.');">
        <input type="hidden" name="class_id" value="<?= e($classId ?? '') ?>">
        <div class="table-responsive">
            <table class="table table-modern">
                <thead><tr><?php if (current_user()['role']==='admin' && (int)($classId ?? 0) > 0): ?><th style="width:40px"><input type="checkbox" onclick="document.querySelectorAll('.student-check').forEach(cb => cb.checked = this.checked)"></th><?php endif; ?><th>Student</th><th>Code</th><th>Class</th><th>Section</th><th>Roll</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($students as $row): ?>
                <tr>
                    <?php if (current_user()['role']==='admin' && (int)($classId ?? 0) > 0): ?><td><input class="form-check-input student-check" type="checkbox" name="student_ids[]" value="<?= e($row['student_id']) ?>"></td><?php endif; ?>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['student_code'] ?? '-') ?></td>
                    <td><?= e($row['class_name'] ?? '-') ?></td>
                    <td><?= e($row['section_name'] ?? '-') ?></td>
                    <td><?= e($row['roll'] ?? '-') ?></td>
                    <td>
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/students/show?id=' . $row['student_id'])) ?>">View</a>
                            <?php if (current_user()['role']==='admin'): ?>
                            <button class="btn btn-sm btn-outline-danger" formaction="<?= e(base_url('/students/delete')) ?>" name="student_id" value="<?= e($row['student_id']) ?>" onclick="return confirm('Delete this student and all related academic records?');">Delete</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$students): ?><tr><td colspan="<?= current_user()['role']==='admin' && (int)($classId ?? 0) > 0 ? '7' : '6' ?>" class="empty-state">No students found for the selected filters.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (current_user()['role']==='admin' && (int)($classId ?? 0) > 0 && $students): ?>
        <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-danger">Delete Selected Students</button>
        </div>
        <?php endif; ?>
    </form>
</div>
