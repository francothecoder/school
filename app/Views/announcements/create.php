<div class="panel-card">
    <div class="panel-head">
        <h5>Publish Announcement</h5>
        <div class="form-helper">This will be visible inside the portal after users log in. You can also broadcast it to available email addresses using PHP mail.</div>
    </div>
    <form method="post" action="<?= e(base_url('/announcements/store')) ?>" class="row g-3">
        <div class="col-12">
            <label class="form-label">Announcement Title</label>
            <input type="text" name="notice_title" class="form-control" required placeholder="e.g. Term 2 Opening Date">
        </div>
        <div class="col-12">
            <label class="form-label">Message</label>
            <textarea name="notice" class="form-control" rows="7" required placeholder="Write the announcement here..."></textarea>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="send_email" id="sendEmail" value="1">
                <label class="form-check-label" for="sendEmail">Also send by email using PHP mail()</label>
            </div>
            <div class="form-helper mt-2">Sender email will use <strong><?= e(app_mail_from()) ?></strong>. Configure your XAMPP or hosting mail settings if emails do not leave the server.</div>
        </div>
        <div class="col-12 d-flex gap-2 flex-wrap">
            <button class="btn btn-primary">Publish Announcement</button>
            <a class="btn btn-outline-primary" href="<?= e(base_url('/announcements')) ?>">Back</a>
        </div>
    </form>
</div>
