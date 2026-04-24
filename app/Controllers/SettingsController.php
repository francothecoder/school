<?php
declare(strict_types=1);

namespace Controllers;

class SettingsController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin']);
        $settingsRows = db()->fetchAll("SELECT type, description FROM settings ORDER BY type");
        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[$row['type']] = $row['description'];
        }
        $title = 'Admin Settings';
        $this->render('settings/index', compact('title', 'settings'));
    }

    public function update(): void
    {
        require_auth(['admin']);

        $gradingLines = trim((string) request('grading_scale_lines', ''));
        $validatedScale = parse_grading_scale_lines($gradingLines);
        $pairs = [
            'system_name' => request('system_name', ''),
            'address' => request('address', ''),
            'phone' => request('phone', ''),
            'system_email' => request('system_email', ''),
            'footer_text' => request('footer_text', ''),
            'running_year' => request('running_year', current_year()),
            'mail_from_address' => request('mail_from_address', 'support@learntrackschools.online'),
            'report_ministry_name' => request('report_ministry_name', 'MINISTRY OF EDUCATION'),
            'report_school_name' => request('report_school_name', request('system_name', '')),
            'report_contacts' => request('report_contacts', ''),
            'po_box' => request('po_box', ''),
            'motto' => request('motto', ''),
            'report_head_label' => request('report_head_label', 'Head Teacher'),
            'report_left_logo' => request('report_left_logo', ''),
            'report_right_logo' => request('report_right_logo', ''),
            'head_signature' => request('head_signature', ''),
            'grading_scale_lines' => grading_scale_lines_from_scale($validatedScale),
            'passing_mark' => request('passing_mark', '40'),
            'sms_enabled' => request('sms_enabled', '0') === '1' ? '1' : '0',
            'sms_provider' => request('sms_provider', 'beem'),
            'sms_sender_id' => request('sms_sender_id', ''),
            'sms_max_length' => request('sms_max_length', '192'),
            'sms_footer' => request('sms_footer', 'LearnTrack Pro'),
            'beem_api_key' => request('beem_api_key', ''),
            'beem_secret_key' => request('beem_secret_key', ''),
            'beem_sender_id' => request('beem_sender_id', request('sms_sender_id', '')),
            'beem_api_url' => request('beem_api_url', 'https://apisms.beem.africa/v1/send'),
            'zamtel_api_url' => request('zamtel_api_url', ''),
            'zamtel_username' => request('zamtel_username', ''),
            'zamtel_password' => request('zamtel_password', ''),
            'zamtel_sender_id' => request('zamtel_sender_id', request('sms_sender_id', '')),
            'email_results_enabled' => request('email_results_enabled', '0') === '1' ? '1' : '0',
            'email_from_address' => request('email_from_address', request('mail_from_address', 'support@learntrackschools.online')),
            'email_from_name' => request('email_from_name', request('system_name', 'LearnTrack Pro')),
        ];
        foreach ($pairs as $type => $description) {
            settings_upsert($type, trim((string) $description));
        }
        flash('success', 'Settings updated successfully.');
        redirect('/settings');
    }
}
