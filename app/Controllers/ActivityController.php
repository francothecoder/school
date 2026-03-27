<?php
declare(strict_types=1);

namespace Controllers;

class ActivityController extends BaseController
{
    public function index(): void
    {
        require_auth(['admin']);
        ensure_support_tables();

        $module = trim((string) request('module', ''));
        $action = trim((string) request('action', ''));
        $role = trim((string) request('role', ''));
        $q = trim((string) request('q', ''));
        $params = [];
        $sql = "SELECT * FROM activity_logs WHERE 1=1";

        if ($module !== '') {
            $sql .= " AND module_name = :module";
            $params['module'] = $module;
        }
        if ($action !== '') {
            $sql .= " AND action = :action";
            $params['action'] = $action;
        }
        if ($role !== '') {
            $sql .= " AND user_role = :role";
            $params['role'] = $role;
        }
        if ($q !== '') {
            $sql .= " AND (description LIKE :q OR user_name LIKE :q OR module_name LIKE :q)";
            $params['q'] = '%' . $q . '%';
        }

        $sql .= " ORDER BY id DESC LIMIT 500";
        $logs = db()->fetchAll($sql, $params);
        $modules = db()->fetchAll("SELECT DISTINCT module_name FROM activity_logs ORDER BY module_name");
        $actions = db()->fetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");
        $roles = db()->fetchAll("SELECT DISTINCT user_role FROM activity_logs ORDER BY user_role");

        $title = 'Activity Logs';
        $this->render('admin/activity-logs', compact('title', 'logs', 'module', 'action', 'role', 'q', 'modules', 'actions', 'roles'));
    }
}
