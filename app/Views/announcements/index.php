<div class="panel-card mb-4">
    <div class="panel-head d-flex justify-content-between align-items-center">
        <div>
            <h5>School Announcements</h5>
            <div class="form-helper">Create announcements, edit mistakes later, delete old notices, and optionally send them by email to all admins, teachers, and students.</div>
        </div>
        <a class="btn btn-primary" href="<?= e(base_url('/announcements/create')) ?>"><i class="bi bi-megaphone"></i> New Announcement</a>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($announcements as $row): ?>
    <div class="col-12">
        <div class="panel-card announcement-card announcement-manage-card">
            <div class="d-flex justify-content-between gap-3 align-items-start flex-wrap">
                <div>
                    <div class="metric-label mb-1">Announcement</div>
                    <h5 class="mb-2"><?= e($row['notice_title'] ?? 'Untitled') ?></h5>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span class="badge-soft"><?= e(announcement_date($row)) ?></span>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('/announcements/edit?id=' . (int) $row['notice_id'])) ?>">
                        <i class="bi bi-pencil-square"></i> Edit
                    </a>
                    <form method="post" action="<?= e(base_url('/announcements/delete')) ?>" onsubmit="return confirm('Delete this announcement?');">
                        <input type="hidden" name="notice_id" value="<?= (int) $row['notice_id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                            <i class="bi bi-trash3"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="announcement-body mt-3"><?= nl2br(e($row['notice'] ?? '')) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$announcements): ?>
    <div class="col-12"><div class="panel-card text-secondary">No announcements have been posted yet.</div></div>
    <?php endif; ?>
</div>
