<div class="panel-card mb-4">
    <div class="panel-head"><h5>Promotion Setup</h5></div>
    <form method="get" class="row g-3">
        <div class="col-md-4"><label class="form-label">From Academic Year</label><input name="from_year" class="form-control" value="<?= e($fromYear) ?>"></div>
        <div class="col-md-4"><label class="form-label">To Academic Year</label><input name="to_year" class="form-control" value="<?= e($toYear) ?>"></div>
        <div class="col-md-4"><label class="form-label">Current Class</label><select name="class_id" class="form-select"><option value="">Select class</option><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>" <?= ((string)$classId===(string)$row['class_id']?'selected':'') ?>><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><button class="btn btn-outline-primary">Load Students</button></div>
    </form>
</div>

<?php if ($students): ?>
<div class="panel-card">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <h5>Students Ready for Promotion</h5>
        <span class="badge-soft"><?= count($students) ?> loaded</span>
    </div>
    <form method="post" action="<?= e(base_url('/promotion/process')) ?>">
        <input type="hidden" name="from_year" value="<?= e($fromYear) ?>">
        <input type="hidden" name="to_year" value="<?= e($toYear) ?>">
        <input type="hidden" name="from_class_id" value="<?= e($classId) ?>">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Promote To Class</label>
                <select name="to_class_id" class="form-select" required>
                    <option value="">Select class</option>
                    <?php foreach ($classes as $row): ?>
                    <option value="<?= e($row['class_id']) ?>" <?= ($recommendedClass && (string)$recommendedClass['class_id']===(string)$row['class_id']?'selected':'') ?>><?= e($row['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($recommendedClass): ?><div class="form-helper mt-2">Suggested next class: <?= e($recommendedClass['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Destination Section</label>
                <select name="to_section_id" class="form-select">
                    <option value="">Keep section empty / set later</option>
                </select>
                <div class="form-helper mt-2">You can assign section later if needed.</div>
            </div>
        </div>
        <div class="table-responsive mb-3">
            <table class="table table-modern">
                <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.promote-check').forEach(el=>el.checked=this.checked)"></th><th>Roll</th><th>Student Code</th><th>Name</th><th>Section</th></tr></thead>
                <tbody>
                <?php foreach ($students as $row): ?>
                <tr>
                    <td><input class="promote-check" type="checkbox" name="student_ids[]" value="<?= e($row['student_id']) ?>" checked></td>
                    <td><?= e($row['roll'] ?? '-') ?></td>
                    <td><?= e($row['student_code']) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['section_name'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary">Promote Selected Students</button>
    </form>
</div>
<?php endif; ?>