<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <div>
            <h5>Classes</h5>
            <div class="form-helper">Manage classes, sections, subjects, and class-level student records.</div>
        </div>
        <?php if (current_user()['role']==='admin'): ?><a class="btn btn-primary" href="<?= e(base_url('/classes/create')) ?>">Add Class</a><?php endif; ?>
    </div>
    <div class="alert alert-warning border-0 shadow-sm small">
        <strong>Careful:</strong> deleting a class removes linked students, enrollments, marks, attendance records, sections, and subjects connected to that class.
    </div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Class</th><th>Level</th><th>Teacher</th><th>Sections</th><th>Subjects</th><th>Students</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($classes as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['name_numeric']) ?></td>
                <td><?= e($row['teacher_name'] ?? '-') ?></td>
                <td><?= e($row['sections_count']) ?></td>
                <td><?= e($row['subjects_count']) ?></td>
                <td><?= e($row['students_count']) ?></td>
                <td>
                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/classes/show?id=' . $row['class_id'])) ?>">Open</a>
                        <?php if (current_user()['role']==='admin'): ?>
                        <form method="post" action="<?= e(base_url('/classes/delete')) ?>" onsubmit="return confirm('Delete this class permanently? All linked students, enrollments, marks, attendance, sections, and subjects will also be deleted. This cannot be undone.');">
                            <input type="hidden" name="class_id" value="<?= e($row['class_id']) ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>