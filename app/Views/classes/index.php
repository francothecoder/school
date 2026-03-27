<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>Classes</h5>
        <?php if (current_user()['role']==='admin'): ?><a class="btn btn-primary" href="<?= e(base_url('/classes/create')) ?>">Add Class</a><?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Class</th><th>Level</th><th>Teacher</th><th>Sections</th><th>Subjects</th><th>Students</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($classes as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['name_numeric']) ?></td>
                <td><?= e($row['teacher_name'] ?? '-') ?></td>
                <td><?= e($row['sections_count']) ?></td>
                <td><?= e($row['subjects_count']) ?></td>
                <td><?= e($row['students_count']) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/classes/show?id=' . $row['class_id'])) ?>">Open</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>