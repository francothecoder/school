<div class="panel-card mb-4">
    <div class="panel-head d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5>Subjects Management</h5>
            <small class="text-muted">Choose a class first, then manage only the subjects attached to that class.</small>
        </div>
        <?php if (current_user()['role'] === 'admin' && !empty($selectedClassId)): ?>
            <a class="btn btn-primary" href="#add-subject-form">Add Subject to <?= e($selectedClass['name'] ?? 'Class') ?></a>
        <?php endif; ?>
    </div>

    <form method="get" action="<?= e(base_url('/subjects')) ?>" class="row g-3 align-items-end">
        <div class="col-md-6">
            <label class="form-label">Select Class</label>
            <select name="class_id" class="form-select" required>
                <option value="">-- Choose class to manage subjects --</option>
                <?php foreach (($classes ?? []) as $class): ?>
                    <option value="<?= e($class['class_id']) ?>" <?= (int)($selectedClassId ?? 0) === (int)$class['class_id'] ? 'selected' : '' ?>>
                        <?= e($class['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Academic Year</label>
            <input name="year" class="form-control" value="<?= e($year ?? current_year()) ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100">Open Class Subjects</button>
        </div>
    </form>
</div>

<?php if (empty($selectedClassId)): ?>
    <div class="panel-card text-center py-5">
        <div class="display-6 mb-2">📚</div>
        <h5>Select a class to continue</h5>
        <p class="text-muted mb-0">Subjects are now managed class-wise to make editing, reassignment, and deletion easier.</p>
    </div>
<?php else: ?>
    <?php if (current_user()['role'] === 'admin'): ?>
    <div class="panel-card mb-4" id="add-subject-form">
        <div class="panel-head">
            <h5>Add Subject(s) to <?= e($selectedClass['name'] ?? 'Selected Class') ?></h5>
            <small class="text-muted">You can add one subject, comma-separated subjects, or one subject per line.</small>
        </div>
        <form method="post" action="<?= e(base_url('/subjects/store')) ?>" class="row g-3">
            <input type="hidden" name="class_id" value="<?= e($selectedClassId) ?>">
            <div class="col-md-5">
                <label class="form-label">Subject(s)</label>
                <textarea name="name" class="form-control" rows="3" placeholder="ENG, MATHS, SCIENCE or one per line" required></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Assigned Teacher</label>
                <select name="teacher_id" class="form-select">
                    <option value="">Unassigned</option>
                    <?php foreach (($teachers ?? []) as $teacher): ?>
                        <option value="<?= e($teacher['teacher_id']) ?>"><?= e($teacher['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Academic Year</label>
                <input name="year" class="form-control" value="<?= e($year ?? current_year()) ?>">
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Save Subject(s)</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="panel-card">
        <div class="panel-head d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5><?= e($selectedClass['name'] ?? 'Class') ?> Subjects</h5>
                <small class="text-muted">Year: <?= e($year ?? current_year()) ?> • <?= count($subjects ?? []) ?> subject(s)</small>
            </div>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('/subjects')) ?>">Choose Another Class</a>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Current Teacher</th>
                        <th>Year</th>
                        <?php if (current_user()['role'] === 'admin'): ?>
                            <th style="min-width:300px;">Reassign Teacher</th>
                            <th class="text-end">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="<?= current_user()['role'] === 'admin' ? 5 : 3 ?>" class="text-center text-muted py-4">
                            No subjects found for this class and academic year.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach (($subjects ?? []) as $row): ?>
                <tr>
                    <td><strong><?= e($row['name']) ?></strong></td>
                    <td><?= e($row['teacher_name'] ?? 'Unassigned') ?></td>
                    <td><?= e($row['year']) ?></td>
                    <?php if (current_user()['role'] === 'admin'): ?>
                    <td>
                        <form method="post" action="<?= e(base_url('/subjects/reassign-teacher')) ?>" class="d-flex gap-2">
                            <input type="hidden" name="subject_id" value="<?= e($row['subject_id']) ?>">
                            <input type="hidden" name="class_id" value="<?= e($selectedClassId) ?>">
                            <input type="hidden" name="year" value="<?= e($year ?? current_year()) ?>">
                            <select name="teacher_id" class="form-select form-select-sm">
                                <option value="">Unassigned</option>
                                <?php foreach (($teachers ?? []) as $teacher): ?>
                                    <option value="<?= e($teacher['teacher_id']) ?>" <?= (int)($row['teacher_id'] ?? 0)===(int)$teacher['teacher_id'] ? 'selected' : '' ?>>
                                        <?= e($teacher['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-outline-success" onclick="return confirm('Reassign this subject to the selected teacher? Existing marks will remain intact.');">Save</button>
                        </form>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/subjects/edit?id=' . $row['subject_id'] . '&return_class_id=' . $selectedClassId)) ?>">Edit</a>
                            <form method="post" action="<?= e(base_url('/subjects/delete')) ?>" onsubmit="return confirm('Delete this subject? Subjects with existing marks are protected and will not be deleted.');">
                                <input type="hidden" name="subject_id" value="<?= e($row['subject_id']) ?>">
                                <input type="hidden" name="class_id" value="<?= e($selectedClassId) ?>">
                                <input type="hidden" name="year" value="<?= e($year ?? current_year()) ?>">
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
