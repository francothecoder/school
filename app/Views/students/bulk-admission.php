<div class="panel-card">
    <div class="panel-head"><h5>Bulk Admission</h5></div>
    <form method="post" action="<?= e(base_url('/students/bulk-store')) ?>" class="row g-3" enctype="multipart/form-data">
        <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select" data-role="class-section-driver" data-section-target="#bulkSection" data-endpoint="<?= e(base_url('/api/class-sections')) ?>" required><option value="">Select class</option><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Section</label><select id="bulkSection" name="section_id" class="form-select"><option value="">Select section</option></select></div>
        <div class="col-md-4"><label class="form-label">Academic Year</label><input name="year" class="form-control" value="<?= e(current_year()) ?>"></div>
        <div class="col-md-4"><label class="form-label">Default Password</label><input name="default_password" class="form-control" value="123456"></div>
        <div class="col-md-12">
            <label class="form-label">Upload CSV File</label>
            <input type="file" name="students_csv" class="form-control" accept=".csv,text/csv">
            <div class="form-helper mt-2">CSV format: student_code, full_name, sex, email, phone, roll. Email and phone can be blank.</div>
        </div>
        <div class="col-md-12">
            <label class="form-label">Or Paste Students CSV Lines</label>
            <textarea name="students_blob" class="form-control" rows="12" placeholder="ST001, Mary Banda, female, mary@example.com, 097..., 1&#10;ST002, John Phiri, male, , 096..., 2"></textarea>
            <div class="form-helper mt-2">Use either upload or paste. Header row is allowed and will be skipped automatically.</div>
        </div>
        <div class="col-12"><button class="btn btn-primary">Import Students</button></div>
    </form>
</div>