<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>Subjects</h5>
        <?php if (current_user()['role']==='admin'): ?><a class="btn btn-primary" href="<?= e(base_url('/subjects/create')) ?>">Assign Subject</a><?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Subject</th><th>Class</th><th>Teacher</th><th>Year</th><?php if (current_user()['role']==='admin'): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($subjects as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['class_name'] ?? '-') ?></td>
                <td><?= e($row['teacher_name'] ?? 'Unassigned') ?></td>
                <td><?= e($row['year']) ?></td>
                <?php if (current_user()['role']==='admin'): ?>
                <td><div class="d-flex gap-2 justify-content-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/subjects/edit?id=' . $row['subject_id'])) ?>">Edit</a><a class="btn btn-sm btn-outline-secondary" href="<?= e(base_url('/classes/show?id=' . $row['class_id'])) ?>">Manage in class</a></div></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
