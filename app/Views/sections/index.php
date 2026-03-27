<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>Sections</h5>
        <?php if (current_user()['role']==='admin'): ?><a class="btn btn-primary" href="<?= e(base_url('/sections/create')) ?>">Add Section</a><?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Section</th><th>Nickname</th><th>Class</th><th>Teacher</th><?php if (current_user()['role']==='admin'): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($sections as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['nick_name'] ?? '-') ?></td>
                <td><?= e($row['class_name'] ?? '-') ?></td>
                <td><?= e($row['teacher_name'] ?? '-') ?></td>
                <?php if (current_user()['role']==='admin'): ?>
                <td class="card-actions">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/sections/edit?id=' . $row['section_id'])) ?>">Edit</a>
                    <form method="post" action="<?= e(base_url('/sections/delete')) ?>" onsubmit="return confirm('Delete this section?')">
                        <input type="hidden" name="section_id" value="<?= e($row['section_id']) ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>