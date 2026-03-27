<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center"><h5>Teacher Directory</h5><a class="btn btn-primary" href="<?= e(base_url('/teachers/create')) ?>">Add Teacher</a></div>
    <form class="row g-3 mb-3"><div class="col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search teacher"></div><div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div></form>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Designation</th><th>Subjects</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($teachers as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e($row['email']) ?></td><td><?= e($row['phone'] ?? '-') ?></td><td><?= e($row['designation'] ?? '-') ?></td><td><?= e($row['assigned_subjects']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/teachers/show?id=' . $row['teacher_id'])) ?>">View</a></td></tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>