<?php $gradingLines = $settings['grading_scale_lines'] ?? grading_scale_lines_from_scale(default_grading_scale()); ?>
<div class="panel-card">
    <div class="panel-head"><h5>Admin Settings</h5></div>
    <form method="post" action="<?= e(base_url('/settings/update')) ?>" class="row g-3">
        <div class="col-md-6"><label class="form-label">System Name</label><input name="system_name" class="form-control" value="<?= e($settings['system_name'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Academic Running Year</label><input name="running_year" class="form-control" value="<?= e($settings['running_year'] ?? current_year()) ?>"></div>
        <div class="col-md-6"><label class="form-label">School Email</label><input name="system_email" class="form-control" value="<?= e($settings['system_email'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($settings['phone'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Mail Sender Address</label><input name="mail_from_address" class="form-control" value="<?= e($settings['mail_from_address'] ?? 'support@learntrackschools.online') ?>"></div>
        <div class="col-md-3"><label class="form-label">Pass Mark for Analytics</label><input type="number" min="0" max="100" name="passing_mark" class="form-control" value="<?= e($settings['passing_mark'] ?? '40') ?>"></div>
        <div class="col-md-3"><label class="form-label">Enable Result Emails</label><select name="email_results_enabled" class="form-select"><option value="0" <?= (($settings['email_results_enabled'] ?? '0') !== '1') ? 'selected' : '' ?>>Disabled</option><option value="1" <?= (($settings['email_results_enabled'] ?? '0') === '1') ? 'selected' : '' ?>>Enabled</option></select></div>
        <div class="col-md-3"><label class="form-label">Result Email From Name</label><input name="email_from_name" class="form-control" value="<?= e($settings['email_from_name'] ?? ($settings['system_name'] ?? 'LearnTrack Pro')) ?>"></div>
        <div class="col-md-6"><label class="form-label">Result Email From Address</label><input type="email" name="email_from_address" class="form-control" value="<?= e($settings['email_from_address'] ?? ($settings['mail_from_address'] ?? 'support@learntrackschools.online')) ?>"><div class="form-text">Used when emailing PDF report cards and marks summaries.</div></div>
        <div class="col-md-3"><label class="form-label">Footer Text</label><input name="footer_text" class="form-control" value="<?= e($settings['footer_text'] ?? '') ?>"></div>
        <div class="col-md-12"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= e($settings['address'] ?? '') ?>"></div>

        <div class="col-12">
            <div class="border rounded-4 p-3 bg-light-subtle">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-semibold mb-0">Report Card Branding & Identity</label>
                    <span class="badge-soft-primary">Admin controlled</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Ministry / Heading Line</label><input name="report_ministry_name" class="form-control" value="<?= e($settings['report_ministry_name'] ?? 'MINISTRY OF EDUCATION') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Report Card School Name</label><input name="report_school_name" class="form-control" value="<?= e($settings['report_school_name'] ?? ($settings['system_name'] ?? '')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Report Contacts Line</label><input name="report_contacts" class="form-control" value="<?= e($settings['report_contacts'] ?? '') ?>" placeholder="+260... | +260..."></div>
                    <div class="col-md-6"><label class="form-label">P.O Box</label><input name="po_box" class="form-control" value="<?= e($settings['po_box'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">School Motto</label><input name="motto" class="form-control" value="<?= e($settings['motto'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Signature Label</label><input name="report_head_label" class="form-control" value="<?= e($settings['report_head_label'] ?? 'Head Teacher') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Left Logo URL / Path</label><input name="report_left_logo" class="form-control" value="<?= e($settings['report_left_logo'] ?? '') ?>" placeholder="<?= e(base_url('/public/assets/img/report-coat.png')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Right Logo URL / Path</label><input name="report_right_logo" class="form-control" value="<?= e($settings['report_right_logo'] ?? '') ?>" placeholder="<?= e(base_url('/public/assets/img/report-mss.png')) ?>"></div>
                    <div class="col-md-12"><label class="form-label">Signature Image URL / Path</label><input name="head_signature" class="form-control" value="<?= e($settings['head_signature'] ?? '') ?>" placeholder="<?= e(base_url('/public/assets/img/report-signature.png')) ?>"></div>
                    <div class="col-12">
                        <div class="small text-muted">
                            Tip: you can use either full URLs or local paths such as <code><?= e(base_url('/public/uploads/logo.png')) ?></code>. These settings now drive the report card header, logos, and signature everywhere.
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-12">
            <div class="border rounded-4 p-3 bg-light-subtle">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-semibold mb-0">SMS Results Settings</label>
                    <span class="badge-soft-primary">Beem / Zamtel</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Enable SMS</label>
                        <select name="sms_enabled" class="form-select">
                            <option value="0" <?= (($settings['sms_enabled'] ?? '0') !== '1') ? 'selected' : '' ?>>Disabled</option>
                            <option value="1" <?= (($settings['sms_enabled'] ?? '0') === '1') ? 'selected' : '' ?>>Enabled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SMS Provider</label>
                        <select name="sms_provider" class="form-select">
                            <option value="beem" <?= (($settings['sms_provider'] ?? 'beem') === 'beem') ? 'selected' : '' ?>>Beem SMS</option>
                            <option value="zamtel" <?= (($settings['sms_provider'] ?? '') === 'zamtel') ? 'selected' : '' ?>>Zamtel Bulk SMS</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Default Sender ID</label><input name="sms_sender_id" class="form-control" value="<?= e($settings['sms_sender_id'] ?? '') ?>" placeholder="e.g. SCHOOL"></div>
                    <div class="col-md-3"><label class="form-label">Max SMS Characters</label><input type="number" min="120" max="320" name="sms_max_length" class="form-control" value="<?= e($settings['sms_max_length'] ?? '192') ?>"></div>
                    <div class="col-md-12"><label class="form-label">SMS Footer</label><input name="sms_footer" class="form-control" value="<?= e($settings['sms_footer'] ?? 'LearnTrack Pro') ?>" placeholder="LearnTrack Pro"></div>

                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-md-4"><label class="form-label">Beem API Key</label><input name="beem_api_key" class="form-control" value="<?= e($settings['beem_api_key'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Beem Secret Key</label><input name="beem_secret_key" class="form-control" value="<?= e($settings['beem_secret_key'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Beem Sender ID</label><input name="beem_sender_id" class="form-control" value="<?= e($settings['beem_sender_id'] ?? ($settings['sms_sender_id'] ?? '')) ?>"></div>
                    <div class="col-md-12"><label class="form-label">Beem API URL</label><input name="beem_api_url" class="form-control" value="<?= e($settings['beem_api_url'] ?? 'https://apisms.beem.africa/v1/send') ?>"></div>

                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-md-4"><label class="form-label">Zamtel API URL</label><input name="zamtel_api_url" class="form-control" value="<?= e($settings['zamtel_api_url'] ?? '') ?>" placeholder="Provided by Zamtel"></div>
                    <div class="col-md-4"><label class="form-label">Zamtel Username / API Key</label><input name="zamtel_username" class="form-control" value="<?= e($settings['zamtel_username'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Zamtel Password / Secret</label><input name="zamtel_password" class="form-control" value="<?= e($settings['zamtel_password'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Zamtel Sender ID</label><input name="zamtel_sender_id" class="form-control" value="<?= e($settings['zamtel_sender_id'] ?? ($settings['sms_sender_id'] ?? '')) ?>"></div>
                    <div class="col-12">
                        <div class="small text-muted">
                            SMS results use compact subject names to fit most providers. Beem is integrated directly. Zamtel is configurable so you can paste the official API URL and credentials supplied to the school.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="border rounded-4 p-3 bg-light-subtle">
                <label class="form-label fw-semibold">Grading System</label>
                <textarea name="grading_scale_lines" class="form-control font-monospace" rows="10"><?= e($gradingLines) ?></textarea>
                <div class="form-text mt-2">
                    Use one line per grade band in this format: <code>mark_from|mark_to|grade_point|label</code>.<br>
                    Example: <code>75|100|1|DISTINCTION</code>
                </div>
            </div>
        </div>

        <div class="col-12"><button class="btn btn-primary">Save Settings</button></div>
    </form>
</div>
