<div class="panel-card">
    <div class="panel-head">
        <h5>Assign Subject</h5>
        <small class="text-muted">Subjects are managed by class. Select the class, then add one or multiple subjects.</small>
    </div>
    <form method="post" action="<?= e(base_url('/subjects/store')) ?>" class="row g-3">
        <div class="col-md-5">
            <label class="form-label">Subject(s)</label>
            <textarea name="name" class="form-control" rows="3" placeholder="ENG, MATHS, SCIENCE or one per line" required></textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="class_id" class="form-select" required>
                <option value="">-- Select Class --</option>
                <?php foreach ($classes as $row): ?>
                    <option value="<?= e($row['class_id']) ?>" <?= (int)($selectedClassId ?? 0)===(int)$row['class_id'] ? 'selected' : '' ?>><?= e($row['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Teacher</label>
            <select name="teacher_id" class="form-select">
                <option value="">Unassigned</option>
                <?php foreach ($teachers as $row): ?><option value="<?= e($row['teacher_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Academic Year</label>
            <input name="year" class="form-control" value="<?= e(current_year()) ?>">
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Save Subject(s)</button>
            <a class="btn btn-outline-secondary" href="<?= e(base_url('/subjects' . (!empty($selectedClassId) ? '?class_id=' . $selectedClassId : ''))) ?>">Cancel</a>
        </div>
    </form>
</div>
