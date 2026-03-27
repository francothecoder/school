<?php
$flashSuccess = flash('success');
$flashError = flash('error');
$isGuest = !auth_check();
$user = current_user();
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$currentBasePath = parse_url(config('app.base_url', ''), PHP_URL_PATH) ?: '';
if ($currentBasePath && str_starts_with($currentPath, $currentBasePath)) {
    $currentPath = substr($currentPath, strlen($currentBasePath)) ?: '/';
}
$navSections = [];
if (!$isGuest) {
    $role = $user['role'] ?? '';
    $navSections[] = [
        'label' => 'Overview',
        'items' => [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'url' => base_url('/dashboard')],
        ],
    ];

    if (in_array($role, ['admin', 'teacher'], true)) {
        $navSections[] = [
            'label' => 'Academic',
            'items' => array_values(array_filter([
                ['label' => 'Students', 'icon' => 'bi-people', 'url' => base_url('/students')],
                $role === 'admin' ? ['label' => 'Bulk Admission', 'icon' => 'bi-person-plus', 'url' => base_url('/students/bulk-admission')] : null,
                $role === 'admin' ? ['label' => 'Teachers', 'icon' => 'bi-person-badge', 'url' => base_url('/teachers')] : null,
                ['label' => 'Classes', 'icon' => 'bi-diagram-3', 'url' => base_url('/classes')],
                ['label' => 'Sections', 'icon' => 'bi-collection', 'url' => base_url('/sections')],
                ['label' => 'Subjects', 'icon' => 'bi-journal-bookmark', 'url' => base_url('/subjects')],
                ['label' => 'Enrollments', 'icon' => 'bi-person-lines-fill', 'url' => base_url('/enrollments')],
                ['label' => 'Exams', 'icon' => 'bi-clipboard-check', 'url' => base_url('/exams')],
                ['label' => 'Attendance', 'icon' => 'bi-calendar2-check', 'url' => base_url('/attendance')],
            ])),
        ];
        $navSections[] = [
            'label' => 'Performance',
            'items' => [
                ['label' => 'Marks Entry', 'icon' => 'bi-bar-chart-line', 'url' => base_url('/marks')],
                ['label' => 'Analytics', 'icon' => 'bi-graph-up-arrow', 'url' => base_url('/analytics')],
                ['label' => 'Class Exam Sheet', 'icon' => 'bi-table', 'url' => base_url('/reports/class-sheet')],
            ],
        ];
    }

    if ($role === 'admin') {
        $navSections[] = [
            'label' => 'Administration',
            'items' => [
                ['label' => 'Promotion', 'icon' => 'bi-arrow-up-circle', 'url' => base_url('/promotion')],
                ['label' => 'Announcements', 'icon' => 'bi-megaphone', 'url' => base_url('/announcements')],
                ['label' => 'Activity Logs', 'icon' => 'bi-clock-history', 'url' => base_url('/activity-logs')],
                ['label' => 'Backup & Restore', 'icon' => 'bi-hdd-stack', 'url' => base_url('/backups')],
                ['label' => 'Settings', 'icon' => 'bi-sliders', 'url' => base_url('/settings')],
            ],
        ];
    }

    if ($role === 'student') {
        $navSections[] = [
            'label' => 'Student Portal',
            'items' => [
                ['label' => 'My Profile', 'icon' => 'bi-person-vcard', 'url' => base_url('/students/show')],
                ['label' => 'My Results', 'icon' => 'bi-file-earmark-text', 'url' => base_url('/reports/result-slip')],
            ],
        ];
    }

    $navSections[] = [
        'label' => 'Account',
        'items' => [
            ['label' => 'Account', 'icon' => 'bi-person-circle', 'url' => base_url('/profile')],
            ['label' => 'Logout', 'icon' => 'bi-box-arrow-right', 'url' => base_url('/logout')],
        ],
    ];
}
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'App') . ' | ' . school_meta('system_name', config('app.name'))) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js/dist/driver.css">
    <link href="<?= e(base_url('/public/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="<?= $isGuest ? 'auth-body' : '' ?>">
<div class="app-loader" id="appLoader" aria-hidden="true" hidden style="display:none;">
    <div class="app-loader-card">
        <div class="loader-spinner"></div>
        <div class="app-loader-text" id="appLoaderText">Processing...</div>
    </div>
</div>
<?php if (!$isGuest): ?>
<div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>
<div class="app-shell">
    <aside class="sidebar" id="appSidebar">
        <div class="brand-wrap">
            <div class="brand">
                <div class="brand-mark">M</div>
                <div>
                    <div class="brand-title"><?= e(school_meta('system_name', 'Moomba School')) ?></div>
                    <div class="brand-subtitle">Academic Suite V5</div>
                </div>
            </div>
            <button class="sidebar-close d-lg-none" type="button" id="sidebarClose" aria-label="Close menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="sidebar-user d-lg-none">
            <div class="avatar"><?= strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1)) ?></div>
            <div>
                <div class="fw-semibold text-white"><?= e($user['name'] ?? '') ?></div>
                <div class="small text-white-50 text-capitalize"><?= e($user['role'] ?? '') ?></div>
            </div>
        </div>

        <div class="nav-group-list mt-4">
            <?php foreach ($navSections as $section): ?>
                <div class="nav-group">
                    <div class="nav-group-title"><?= e($section['label']) ?></div>
                    <nav class="nav flex-column nav-group-links">
                        <?php foreach ($section['items'] as $item):
                            $itemPath = parse_url($item['url'], PHP_URL_PATH) ?: '';
                            $isActive = $itemPath === $currentPath || ($itemPath !== '/' && str_starts_with($currentPath, $itemPath));
                            $tourSlug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $item['label'] ?? 'item'));
                            $tourSlug = trim($tourSlug, '-');
                        ?>
                        <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= e($item['url']) ?>" id="tour-nav-<?= e($tourSlug) ?>" data-tour-label="<?= e($item['label']) ?>">
                            <i class="bi <?= e($item['icon']) ?>"></i>
                            <span><?= e($item['label']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div class="topbar-start">
                <button class="menu-toggle d-lg-none mobile-visible-toggle" type="button" id="sidebarToggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="page-title mb-1"><?= e($title ?? 'Dashboard') ?></h1>
                    <div class="text-secondary small"><?= e(date('l, d M Y')) ?> · Academic year <?= e(current_year()) ?></div>
                </div>
            </div>
            <div class="topbar-actions">
                <button class="tour-trigger" type="button" id="startTourBtn"><i class="bi bi-signpost-split"></i><span class="d-none d-sm-inline">Start Tour</span></button>
                <button class="theme-toggle" type="button" id="themeToggle"><i class="bi bi-moon-stars"></i><span class="d-none d-sm-inline">Theme</span></button>
                <div class="user-badge d-none d-md-flex" id="tour-user-badge">
                    <div class="avatar"><?= strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1)) ?></div>
                    <div>
                        <div class="fw-semibold"><?= e($user['name'] ?? '') ?></div>
                        <div class="small text-secondary text-capitalize"><?= e($user['role'] ?? '') ?></div>
                    </div>
                </div>
            </div>
        </header>

        <?php if ($flashSuccess): ?><div class="alert alert-success shadow-sm border-0 auto-dismiss-alert" data-auto-dismiss="1"><?= e($flashSuccess) ?></div><?php endif; ?>
        <?php if ($flashError): ?><div class="alert alert-danger shadow-sm border-0 auto-dismiss-alert" data-auto-dismiss="1"><?= e($flashError) ?></div><?php endif; ?>

        <?php require $viewFile; ?>
    </main>
</div>
<button class="tour-fab no-print" type="button" id="tourFab" aria-label="Start tour"><i class="bi bi-question-lg"></i></button>
<?php else: ?>
    <?php if ($flashSuccess): ?><div class="container mt-3"><div class="alert alert-success shadow-sm border-0 auto-dismiss-alert" data-auto-dismiss="1"><?= e($flashSuccess) ?></div></div><?php endif; ?>
    <?php if ($flashError): ?><div class="container mt-3"><div class="alert alert-danger shadow-sm border-0 auto-dismiss-alert" data-auto-dismiss="1"><?= e($flashError) ?></div></div><?php endif; ?>
    <?php require $viewFile; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/driver.js/dist/driver.js.iife.js"></script>
<script>window.__APP_TOUR = { enabled: <?= $isGuest ? 'false' : 'true' ?>, role: <?= json_encode($user['role'] ?? '') ?>, userId: <?= json_encode((string)($user['id'] ?? $user['admin_id'] ?? $user['teacher_id'] ?? $user['student_id'] ?? $user['student_code'] ?? $user['name'] ?? 'guest')) ?>, userName: <?= json_encode($user['name'] ?? 'User') ?> };</script>
<script src="<?= e(base_url('/public/assets/js/app.js')) ?>"></script>
</body>
</html>
