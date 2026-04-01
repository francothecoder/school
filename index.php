<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Core/helpers.php';

spl_autoload_register(function ($class) {
    $prefixes = [
        'Core\\' => __DIR__ . '/app/Core/',
        'Controllers\\' => __DIR__ . '/app/Controllers/',
        'Services\\' => __DIR__ . '/app/Services/',
    ];
    foreach ($prefixes as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $dir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
});

use Core\Router;
use Core\Database;

$GLOBALS['db'] = new Database($config['db']);
ensure_support_tables();

$router = new Router();

$router->get('/', 'DashboardController@loginRedirect');
$router->get('/login', 'DashboardController@login');
$router->post('/login', 'DashboardController@loginSubmit');
$router->get('/logout', 'DashboardController@logout');

$router->get('/terms-and-conditions', 'PolicyController@terms');
$router->get('/privacy-policy', 'PolicyController@privacy');

$router->get('/dashboard', 'DashboardController@index');
$router->get('/api/global-search', 'SearchController@suggest');
$router->get('/activity-logs', 'ActivityController@index');
$router->get('/backups', 'BackupController@index');
$router->post('/backups/create-database', 'BackupController@createDatabase');
$router->post('/backups/create-full', 'BackupController@createFull');
$router->get('/backups/download', 'BackupController@download');
$router->post('/backups/restore', 'BackupController@restore');

$router->get('/students', 'StudentController@index');
$router->get('/students/create', 'StudentController@create');
$router->post('/students/store', 'StudentController@store');
$router->get('/students/show', 'StudentController@show');
$router->get('/students/edit', 'StudentController@edit');
$router->post('/students/update', 'StudentController@update');
$router->post('/students/delete', 'StudentController@delete');
$router->get('/students/bulk-admission', 'StudentController@bulkAdmission');
$router->get('/students/bulk-template.csv', 'StudentController@downloadBulkTemplate');
$router->post('/students/bulk-store', 'StudentController@bulkStore');

$router->get('/teachers', 'TeacherController@index');
$router->get('/teachers/create', 'TeacherController@create');
$router->post('/teachers/store', 'TeacherController@store');
$router->get('/teachers/show', 'TeacherController@show');
$router->get('/teachers/edit', 'TeacherController@edit');
$router->post('/teachers/update', 'TeacherController@update');

$router->get('/classes', 'ClassController@index');
$router->get('/classes/create', 'ClassController@create');
$router->post('/classes/store', 'ClassController@store');
$router->get('/classes/show', 'ClassController@show');

$router->get('/sections', 'SectionController@index');
$router->get('/sections/create', 'SectionController@create');
$router->post('/sections/store', 'SectionController@store');
$router->get('/sections/edit', 'SectionController@edit');
$router->post('/sections/update', 'SectionController@update');
$router->post('/sections/delete', 'SectionController@delete');

$router->get('/subjects', 'SubjectController@index');
$router->get('/subjects/create', 'SubjectController@create');
$router->post('/subjects/store', 'SubjectController@store');
$router->post('/subjects/delete', 'SubjectController@delete');

$router->get('/enrollments', 'EnrollmentController@index');
$router->get('/enrollments/create', 'EnrollmentController@create');
$router->post('/enrollments/store', 'EnrollmentController@store');

$router->get('/exams', 'ExamController@index');
$router->get('/exams/create', 'ExamController@create');
$router->post('/exams/store', 'ExamController@store');

$router->get('/marks', 'MarkController@index');
$router->post('/marks/save', 'MarkController@save');
$router->get('/analytics', 'MarkController@analytics');
$router->get('/api/class-subjects', 'MarkController@classSubjectsApi');
$router->get('/api/class-sections', 'SectionController@classSectionsApi');

$router->get('/attendance', 'AttendanceController@index');
$router->post('/attendance/save', 'AttendanceController@save');

$router->get('/reports/student', 'ReportController@student');
$router->get('/reports/result-slip', 'ReportController@resultSlip');

$router->get('/results/quick', 'ReportController@quick');
$router->get('/reports/class-sheet', 'ReportController@classSheet');

$router->get('/promotion', 'PromotionController@index');
$router->post('/promotion/process', 'PromotionController@process');

$router->get('/settings', 'SettingsController@index');
$router->post('/settings/update', 'SettingsController@update');

$router->get('/announcements', 'AnnouncementController@index');
$router->get('/announcements/create', 'AnnouncementController@create');
$router->post('/announcements/store', 'AnnouncementController@store');
$router->get('/announcements/edit', 'AnnouncementController@edit');
$router->post('/announcements/update', 'AnnouncementController@update');
$router->post('/announcements/delete', 'AnnouncementController@delete');

$router->get('/profile', 'ProfileController@index');
$router->post('/profile/update', 'ProfileController@update');

$router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
