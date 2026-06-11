<?php

namespace App\Controllers;

class DashboardController {
    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /signin');
            exit;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $role = $_SESSION['role'] ?? 'employee';

        // Extract requested role from path
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim($requestUri, '/');
        $parts = explode('/', $path);
        $requestedRole = $parts[0] ?? '';

        // Redirect /dashboard to the specific role dashboard
        if ($path === 'dashboard' || empty($path)) {
            $roleFolder = $role;
            if ($role === 'hiring_manager') $roleFolder = 'manager';
            if ($role === 'hr_ops') $roleFolder = 'hrops';
            header('Location: /' . $roleFolder . '/dashboard');
            exit;
        }

        // Authorize path prefix
        $allowed = false;
        if ($role === 'superadmin') {
            $allowed = true; // Superadmin has unrestricted access
        } else {
            $cleanRole = str_replace('_', '', $role);
            $cleanReq = str_replace('_', '', $requestedRole);
            if ($cleanRole === $cleanReq) {
                $allowed = true;
            } elseif ($role === 'hiring_manager' && $requestedRole === 'manager') {
                $allowed = true;
            } elseif ($role === 'manager' && $requestedRole === 'hiring_manager') {
                $allowed = true;
            } elseif ($role === 'hr_ops' && $requestedRole === 'hrops') {
                $allowed = true;
            } elseif ($role === 'hrops' && $requestedRole === 'hr_ops') {
                $allowed = true;
            }
        }

        if (!$allowed) {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            if ($isAjax) {
                http_response_code(403);
                echo renderView('pages/error_403');
            } else {
                $content = renderView('pages/error_403');
                $page = 'error_403';
                require __DIR__ . '/../../resources/views/layouts/app.php';
            }
            exit;
        }

        // 1. If employee, check quarterly reflection compliance
        if ($role === 'employee') {
            $db = \App\Config\Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'];
            $currentPeriod = date('Y') . '-Q' . ceil(date('n') / 3);

            $stmt = $db->prepare("SELECT COUNT(*) FROM self_reflections WHERE user_id = :user_id AND period = :period AND status IN ('submitted', 'completed')");
            $stmt->execute(['user_id' => $userId, 'period' => $currentPeriod]);
            $hasQuarterly = $stmt->fetchColumn() > 0;

            if (!$hasQuarterly) {
                header('Location: /employee/reflection?warning=1');
                exit;
            }
        }

        // 2. Check 2-weekly mood pulse compliance for dashboard warning
        $showMoodWarning = false;
        if ($role === 'employee') {
            $db = \App\Config\Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'];
            $currentBiPeriod = date('Y') . '-B' . ceil(date('W') / 2);

            $stmtMood = $db->prepare("SELECT COUNT(*) FROM mood_pulses WHERE user_id = :user_id AND period = :period");
            $stmtMood->execute(['user_id' => $userId, 'period' => $currentBiPeriod]);
            $hasMoodPulse = $stmtMood->fetchColumn() > 0;
            $showMoodWarning = !$hasMoodPulse;
        }

        $db = \App\Config\Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];
        
        $stmtLogs = $db->prepare("SELECT * FROM login_logs WHERE user_id = :user_id ORDER BY login_at DESC LIMIT 100");
        $stmtLogs->execute(['user_id' => $userId]);
        $loginLogs = $stmtLogs->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch office settings
        $stmtCfg = $db->query("SELECT `key`, `value` FROM global_settings WHERE `group` = 'attendance'");
        $cfg = [];
        while ($row = $stmtCfg->fetch(\PDO::FETCH_ASSOC)) {
            $cfg[$row['key']] = $row['value'];
        }

        $roleFolder = $role;
        if ($role === 'hiring_manager') $roleFolder = 'manager';
        if ($role === 'hr_ops') $roleFolder = 'hrops';

        $data = [
            'name' => $_SESSION['name'] ?? 'User',
            'role' => $role,
            'roleFolder' => $roleFolder,
            'email' => $_SESSION['email'] ?? '',
            'showMoodWarning' => $showMoodWarning,
            'loginLogs' => $loginLogs,
            'cfg' => $cfg
        ];

        // Check if request is AJAX (SPA mode)
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($isAjax) {
            // Only return the content part
            echo renderView('dashboard/index', $data);
        } else {
            // Return full SPA layout
            $content = renderView('dashboard/index', $data);
            $page = 'dashboard';
            require __DIR__ . '/../../resources/views/layouts/app.php';
        }
    }
    public function genericPage($path) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /signin');
            exit;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $role = $_SESSION['role'] ?? 'employee';

        // 1. If employee, enforce quarterly reflection check (except for the reflection page itself)
        if ($role === 'employee' && $path !== 'employee/reflection') {
            $db = \App\Config\Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'];
            $currentPeriod = date('Y') . '-Q' . ceil(date('n') / 3);

            $stmt = $db->prepare("SELECT COUNT(*) FROM self_reflections WHERE user_id = :user_id AND period = :period AND status IN ('submitted', 'completed')");
            $stmt->execute(['user_id' => $userId, 'period' => $currentPeriod]);
            $hasQuarterly = $stmt->fetchColumn() > 0;

            if (!$hasQuarterly) {
                header('Location: /employee/reflection?warning=1');
                exit;
            }
        }

        // Authorize path prefix
        $allowed = false;
        if ($role === 'superadmin') {
            $allowed = true; // Superadmin has unrestricted access
        } else {
            $parts = explode('/', $path);
            $prefix = $parts[0] ?? '';
            
            switch ($prefix) {
                case 'employee':
                    $allowed = ($role === 'employee');
                    break;
                case 'manager':
                    $allowed = ($role === 'hiring_manager' || $role === 'manager');
                    break;
                case 'hrops':
                    $allowed = ($role === 'hr_ops' || $role === 'hrops');
                    break;
                case 'executive':
                    $allowed = ($role === 'executive');
                    break;
                case 'admin':
                    $allowed = ($role === 'admin');
                    break;
                case 'recruiter':
                    $allowed = ($role === 'recruiter');
                    break;
                case 'candidate':
                    $allowed = ($role === 'candidate');
                    break;
                case 'superadmin':
                    $allowed = ($role === 'superadmin');
                    break;
                default:
                    // If no matching prefix (e.g. root pages or other views), allow access
                    $allowed = true;
                    break;
            }
        }

        $viewPath = 'pages/' . $path;
        if (!$allowed) {
            $viewPath = 'pages/error_403';
        }

        $fullPath = __DIR__ . '/../../resources/views/' . $viewPath . '.php';

        // Universal fallback to beautiful Coming Soon page or 404/403 page if the view doesn't exist
        if (!file_exists($fullPath)) {
            if (!$allowed) {
                $viewPath = 'pages/error_404';
            } else {
                $validUnbuilt = [
                    'candidate/jobs', 'candidate/interviews', 'candidate/offerings', 'candidate/onboarding',
                    'employee/profile', 'employee/attendance', 'employee/leaves', 'employee/finance', 'employee/reimbursements', 'employee/reflection',
                    'recruiter/jobs', 'recruiter/ats', 'recruiter/interviews', 'recruiter/offerings',
                    'manager/requisitions', 'manager/candidates', 'manager/interviews', 'manager/approvals',
                    'hrops/onboarding', 'hrops/employees', 'hrops/verifications', 'hrops/payroll',
                    'admin/departments', 'admin/users', 'admin/settings',
                    'executive/analytics', 'executive/budgets', 'executive/approvals',
                    'superadmin/users', 'superadmin/settings', 'superadmin/audit'
                ];
                if (in_array($path, $validUnbuilt)) {
                    $viewPath = 'pages/profile_coming_soon';
                } else {
                    $viewPath = 'pages/error_404';
                }
            }
        }

        $data = [
            'name' => $_SESSION['name'] ?? 'User',
            'role' => $role,
            'email' => $_SESSION['email'] ?? '',
            'requestedPath' => $path
        ];

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($isAjax) {
            echo renderView($viewPath, $data);
        } else {
            $content = renderView($viewPath, $data);
            $page = str_replace('/', '_', $path);
            require __DIR__ . '/../../resources/views/layouts/app.php';
        }
    }
}
