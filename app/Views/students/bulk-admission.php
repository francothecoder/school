<div class="panel-card">
    <div class="panel-head d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Bulk Admission</h5>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(base_url('/students/bulk-template.csv')) ?>">Download CSV Template</a>
    </div>
    <form method="post" action="<?= e(base_url('/students/bulk-store')) ?>" class="row g-3" enctype="multipart/form-data">
        <div class="col-md-4"><label class="form-label">Class</label><select name="class_id" class="form-select" data-role="class-section-driver" data-section-target="#bulkSection" data-endpoint="<?= e(base_url('/api/class-sections')) ?>" required><option value="">Select class</option><?php foreach ($classes as $row): ?><option value="<?= e($row['class_id']) ?>"><?= e($row['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Section</label><select id="bulkSection" name="section_id" class="form-select"><option value="">Select section</option></select></div>
        <div class="col-md-4"><label class="form-label">Academic Year</label><input name="year" class="form-control" value="<?= e(current_year()) ?>"></div>
        <div class="col-md-4"><label class="form-label">Default Password</label><input name="default_password" class="form-control" value="123456"></div>
        <div class="col-md-8">
            <label class="form-label">Upload CSV File</label>
            <input type="file" name="students_csv" class="form-control" accept=".csv">
            <div class="form-helper mt-2">Accepted columns: <strong>student_code, full_name, sex, email, phone, roll</strong>. Header row is optional.</div>
        </div>
        <div class="col-md-12">
            <label class="form-label">Or Paste CSV Lines</label>
            <textarea name="students_blob" class="form-control" rows="10" placeholder="ST001, Mary Banda, female, mary@example.com, 0970000000, 1&#10;ST002, John Phiri, male, john@example.com, 0960000000, 2"></textarea>
            <div class="form-helper mt-2">You can use either a CSV upload or pasted lines. If both are provided, the uploaded CSV is used first.</div>
        </div>
        <div class="col-12"><button class="btn btn-primary">Import Students</button></div>
    </form>
</div>
