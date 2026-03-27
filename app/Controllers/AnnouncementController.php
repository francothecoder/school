<?php
declare(strict_types=1);

namespace Controllers;

class AnnouncementController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin']);
        $announcements = db()->fetchAll("SELECT * FROM noticeboard ORDER BY CAST(COALESCE(create_timestamp,'0') AS UNSIGNED) DESC, notice_id DESC LIMIT 100");
        $title = 'Announcements';
        $this->render('announcements/index', compact('title', 'announcements'));
    }

    public function create(): void
    {
        require_auth(['admin']);
        $title = 'Create Announcement';
        $this->render('announcements/create', compact('title'));
    }

    public function store(): void
    {
        require_auth(['admin']);
        $title = trim((string) request('notice_title'));
        $body = trim((string) request('notice'));
        $sendEmail = (int) request('send_email', 0) === 1;

        if ($title === '' || $body === '') {
            flash('error', 'Announcement title and message are required.');
            redirect('/announcements/create');
        }

        db()->execute(
            "INSERT INTO noticeboard (notice_title, notice, create_timestamp, status, show_on_website) VALUES (:notice_title, :notice, :create_timestamp, 1, 1)",
            [
                'notice_title' => $title,
                'notice' => $body,
                'create_timestamp' => (string) time(),
            ]
        );

        $sent = 0;
        $failed = 0;
        if ($sendEmail) {
            $emails = db()->fetchAll(
                "SELECT email FROM admin WHERE email IS NOT NULL AND email <> ''
                 UNION SELECT email FROM teacher WHERE email IS NOT NULL AND email <> ''
                 UNION SELECT email FROM student WHERE email IS NOT NULL AND email <> ''"
            );
            $subject = '[' . school_meta('system_name', 'LearnTrack Schools') . '] ' . $title;
            $html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#0f172a">'
                . '<h2 style="margin:0 0 12px">' . e($title) . '</h2>'
                . '<div style="white-space:pre-line;line-height:1.6">' . nl2br(e($body)) . '</div>'
                . '<p style="margin-top:18px;color:#64748b">Sender: ' . e(app_mail_from()) . '</p>'
                . '</div>';
            foreach ($emails as $row) {
                $ok = send_portal_email((string) ($row['email'] ?? ''), $subject, $html);
                if ($ok) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        }

        $message = 'Announcement published successfully.';
        if ($sendEmail) {
            $message .= ' Email sent: ' . $sent . '. Failed: ' . $failed . '.';
        }
        flash('success', $message);
        redirect('/announcements');
    }


    public function edit(): void
    {
        require_auth(['admin']);
        $id = (int) request('id');
        $announcement = db()->fetch("SELECT * FROM noticeboard WHERE notice_id = :id LIMIT 1", ['id' => $id]);
        if (!$announcement) {
            flash('error', 'Announcement not found.');
            redirect('/announcements');
        }
        $title = 'Edit Announcement';
        $this->render('announcements/edit', compact('title', 'announcement'));
    }

    public function update(): void
    {
        require_auth(['admin']);
        $id = (int) request('notice_id');
        $title = trim((string) request('notice_title'));
        $body = trim((string) request('notice'));

        if ($id <= 0 || $title === '' || $body === '') {
            flash('error', 'Announcement title and message are required.');
            redirect('/announcements/edit?id=' . $id);
        }

        $exists = db()->fetch("SELECT notice_id FROM noticeboard WHERE notice_id = :id LIMIT 1", ['id' => $id]);
        if (!$exists) {
            flash('error', 'Announcement not found.');
            redirect('/announcements');
        }

        db()->execute(
            "UPDATE noticeboard SET notice_title = :notice_title, notice = :notice WHERE notice_id = :notice_id",
            [
                'notice_title' => $title,
                'notice' => $body,
                'notice_id' => $id,
            ]
        );

        flash('success', 'Announcement updated successfully.');
        redirect('/announcements');
    }

    public function delete(): void
    {
        require_auth(['admin']);
        $id = (int) request('notice_id');
        if ($id <= 0) {
            flash('error', 'Invalid announcement selected.');
            redirect('/announcements');
        }

        $exists = db()->fetch("SELECT notice_id FROM noticeboard WHERE notice_id = :id LIMIT 1", ['id' => $id]);
        if (!$exists) {
            flash('error', 'Announcement not found.');
            redirect('/announcements');
        }

        db()->execute("DELETE FROM noticeboard WHERE notice_id = :id", ['id' => $id]);
        flash('success', 'Announcement deleted successfully.');
        redirect('/announcements');
    }

}
