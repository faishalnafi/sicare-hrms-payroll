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

        $data = [
            'name' => $_SESSION['name'] ?? 'User',
            'role' => $_SESSION['role'] ?? 'employee',
            'email' => $_SESSION['email'] ?? ''
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

        $data = [
            'name' => $_SESSION['name'] ?? 'User',
            'role' => $_SESSION['role'] ?? 'employee',
            'email' => $_SESSION['email'] ?? '',
            'requestedPath' => $path
        ];

        $viewPath = 'pages/' . $path;
        $fullPath = __DIR__ . '/../../resources/views/' . $viewPath . '.php';

        // Universal fallback to beautiful Coming Soon page or 404 page if the view doesn't exist
        if (!file_exists($fullPath)) {
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
