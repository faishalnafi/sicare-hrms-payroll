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

        // Universal fallback to beautiful Coming Soon page if the view doesn't exist
        if (!file_exists($fullPath)) {
            $viewPath = 'pages/profile_coming_soon';
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
