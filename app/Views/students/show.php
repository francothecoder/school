<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel-card">
            <div class="panel-head d-flex justify-content-between align-items-center"><h5>Student Summary</h5><?php if (current_user()['role']==='admin'): ?><a href="<?= e(base_url('/students/edit?id=' . $student['student_id'])) ?>" class="btn btn-sm btn-primary">Edit</a><?php endif; ?></div>
            <div class="detail-list">
                <div><span>Name</span><strong><?= e($student['name'] ?? '-') ?></strong></div>
                <div><span>Code</span><strong><?= e($student['student_code'] ?? '-') ?></strong></div>
                <div><span>Email</span><strong><?= e($student['email'] ?? '-') ?></strong></div>
                <div><span>Phone</span><strong><?= e($student['phone'] ?? '-') ?></strong></div>
                <div><span>Sex</span><strong><?= e($student['sex'] ?? '-') ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel-card">
            <div class="panel-head d-flex justify-content-between align-items-center"><h5>Enrollment History</h5><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/reports/result-slip?id=' . $student['student_id'])) ?>">Result Slip</a></div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead><tr><th>Year</th><th>Class</th><th>Section</th><th>Roll</th></tr></thead>
                    <tbody>
                    <?php foreach ($enrollments as $row): ?><tr><td><?= e($row['year']) ?></td><td><?= e($row['class_name'] ?? '-') ?></td><td><?= e($row['section_name'] ?? '-') ?></td><td><?= e($row['roll'] ?? '-') ?></td></tr><?php endforeach; ?>
                    <?php if (!$enrollments): ?><tr><td colspan="4" class="text-center text-secondary py-4">No enrollment records available.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>