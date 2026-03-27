<div class="row g-4">
    <div class="col-xl-4">
        <div class="panel-card h-100">
            <div class="panel-head"><h5>Class Summary</h5></div>
            <div class="detail-list">
                <div><span>Class</span><strong><?= e($class['name'] ?? '-') ?></strong></div>
                <div><span>Level</span><strong><?= e($class['name_numeric'] ?? '-') ?></strong></div>
                <div><span>Class Teacher</span><strong><?= e($class['teacher_name'] ?? '-') ?></strong></div>
                <div><span>Academic Year</span><strong><?= e($year) ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="panel-card h-100">
            <div class="panel-head d-flex justify-content-between align-items-center">
                <h5>Subjects in <?= e($class['name'] ?? '') ?></h5>
                <?php if (current_user()['role']==='admin'): ?><span class="badge-soft">Manage class subjects</span><?php endif; ?>
            </div>
            <div class="table-responsive mb-3">
                <table class="table table-modern">
                    <thead><tr><th>Subject</th><th>Teacher</th><th>Year</th><?php if (current_user()['role']==='admin'): ?><th></th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($subjects as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['teacher_name'] ?? '-') ?></td>
                        <td><?= e($row['year']) ?></td>
                        <?php if (current_user()['role']==='admin'): ?>
                        <td>
                            <form method="post" action="<?= e(base_url('/subjects/delete')) ?>" onsubmit="return confirm('Delete this subject from the class?')">
                                <input type="hidden" name="subject_id" value="<?= e($row['subject_id']) ?>">
                                <input type="hidden" name="class_id" value="<?= e($class['class_id']) ?>">
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$subjects): ?><tr><td colspan="<?= current_user()['role']==='admin' ? 4 : 3 ?>" class="text-center text-secondary py-4">No subjects assigned yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (current_user()['role']==='admin'): ?>
            <form method="post" action="<?= e(base_url('/subjects/store')) ?>" class="row g-3">
                <input type="hidden" name="class_id" value="<?= e($class['class_id']) ?>">
                <input type="hidden" name="year" value="<?= e($year) ?>">
                <input type="hidden" name="redirect_class_id" value="<?= e($class['class_id']) ?>">
                <div class="col-md-5">
                    <label class="form-label">Add Subject(s)</label>
                    <textarea name="name" class="form-control" rows="2" placeholder="ENG, MATHS, SCIENCE or one per line" required></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Assign Teacher</label>
                    <select name="teacher_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($teachers as $t): ?><option value="<?= e($t['teacher_id']) ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100">Add Subject(s)</button></div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="panel-card">
            <div class="panel-head d-flex justify-content-between align-items-center">
                <h5>Sections</h5>
                <?php if (current_user()['role']==='admin'): ?><a class="btn btn-sm btn-primary" href="<?= e(base_url('/sections/create')) ?>">New Section</a><?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead><tr><th>Name</th><th>Nickname</th><th>Teacher</th></tr></thead>
                    <tbody>
                    <?php foreach ($sections as $row): ?>
                    <tr><td><?= e($row['name']) ?></td><td><?= e($row['nick_name'] ?? '-') ?></td><td><?= e($row['teacher_name'] ?? '-') ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$sections): ?><tr><td colspan="3" class="text-center text-secondary py-4">No sections linked to this class.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="panel-card">
            <div class="panel-head d-flex justify-content-between align-items-center">
                <h5>Students in Class</h5>
                <div class="card-actions">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/marks?class_id=' . $class['class_id'] . '&year=' . urlencode($year))) ?>">Enter Marks</a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/analytics?class_id=' . $class['class_id'] . '&year=' . urlencode($year))) ?>">Analytics</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead><tr><th>Roll</th><th>Student Code</th><th>Name</th><th>Section</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($students as $row): ?>
                    <tr>
                        <td><?= e($row['roll'] ?? '-') ?></td>
                        <td><?= e($row['student_code']) ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['section_name'] ?? '-') ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/students/show?id=' . $row['student_id'])) ?>">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$students): ?><tr><td colspan="5" class="text-center text-secondary py-4">No enrolled students found for this class and year.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>