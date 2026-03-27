<div class="panel-card">
    <div class="panel-head">
        <h5>Edit Announcement</h5>
        <div class="form-helper">Update the title or message. This changes what users see after login.</div>
    </div>
    <form method="post" action="<?= e(base_url('/announcements/update')) ?>" class="row g-3">
        <input type="hidden" name="notice_id" value="<?= (int) ($announcement['notice_id'] ?? 0) ?>">
        <div class="col-12">
            <label class="form-label">Announcement Title</label>
            <input type="text" name="notice_title" class="form-control" required value="<?= e($announcement['notice_title'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Message</label>
            <textarea name="notice" class="form-control" rows="8" required><?= e($announcement['notice'] ?? '') ?></textarea>
        </div>
        <div class="col-12 d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" type="submit">Save Changes</button>
            <a class="btn btn-outline-primary" href="<?= e(base_url('/announcements')) ?>">Back</a>
        </div>
    </form>
</div>
