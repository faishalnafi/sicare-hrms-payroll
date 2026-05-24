<?php
/**
 * siCare - Custom MVC Framework
 */

$start = microtime(true);

// Register the Composer autoloader
require __DIR__.'/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Set Default Timezone to Asia/Jakarta to ensure accurate dates and times
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta');

// Simple Router
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($requestUri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Base helper to render views
function renderView($viewPath, $data = []) {
    extract($data);
    $fullPath = __DIR__ . '/../resources/views/' . $viewPath . '.php';
    if (file_exists($fullPath)) {
        ob_start();
        require $fullPath;
        return ob_get_clean();
    }
    return "View not found: " . $viewPath;
}

// Route mapping
if ($method === 'POST' && $path === 'auth/login') {
    (new \App\Controllers\AuthController())->login();
    exit;
} elseif ($method === 'POST' && $path === 'auth/register') {
    (new \App\Controllers\AuthController())->register();
    exit;
} elseif ($method === 'GET' && $path === 'auth/logout') {
    (new \App\Controllers\AuthController())->logout();
    exit;
} elseif ($method === 'GET' && $path === 'auth/google') {
    (new \App\Controllers\AuthController())->googleRedirect();
    exit;
} elseif ($method === 'GET' && str_starts_with($path, 'auth/google/callback')) {
    (new \App\Controllers\AuthController())->googleCallback();
    exit;
} elseif ($method === 'GET' && $path === 'dashboard') {
    (new \App\Controllers\DashboardController())->index();
    exit;
} elseif ($method === 'POST' && $path === 'employee/profile/correction') {
    (new \App\Controllers\CorrectionController())->submit();
    exit;
} elseif ($method === 'POST' && $path === 'employee/profile/save') {
    (new \App\Controllers\AuthController())->saveProfile();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/verifications/approve') {
    (new \App\Controllers\CorrectionController())->approve();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/verifications/reject') {
    (new \App\Controllers\CorrectionController())->reject();
    exit;
} elseif ($method === 'GET' && $path === 'hrops/verifications/view_file') {
    (new \App\Controllers\CorrectionController())->viewFile();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reimbursements/submit') {
    (new \App\Controllers\ReimbursementController())->submit();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reimbursements/cancel') {
    (new \App\Controllers\ReimbursementController())->cancel();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/reimbursements/approve') {
    (new \App\Controllers\ReimbursementController())->approve();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/reimbursements/reject') {
    (new \App\Controllers\ReimbursementController())->reject();
    exit;
} elseif ($method === 'GET' && $path === 'hrops/reimbursements/view_receipt') {
    (new \App\Controllers\ReimbursementController())->viewReceipt();
    exit;
} elseif ($method === 'POST' && $path === 'employee/leaves/submit') {
    (new \App\Controllers\LeaveController())->submit();
    exit;
} elseif ($method === 'POST' && $path === 'employee/leaves/cancel') {
    (new \App\Controllers\LeaveController())->cancel();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/leaves/approve') {
    (new \App\Controllers\LeaveController())->approve();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/leaves/reject') {
    (new \App\Controllers\LeaveController())->reject();
    exit;
} elseif ($method === 'GET' && $path === 'hrops/leaves/view_attachment') {
    (new \App\Controllers\LeaveController())->viewAttachment();
    exit;
} elseif ($method === 'POST' && $path === 'employee/attendance/clockin') {
    (new \App\Controllers\AttendanceController())->clockIn();
    exit;
} elseif ($method === 'POST' && $path === 'employee/attendance/clockout') {
    (new \App\Controllers\AttendanceController())->clockOut();
    exit;
} elseif ($method === 'GET' && $path === 'hrops/attendance/logs') {
    (new \App\Controllers\AttendanceController())->hrLogs();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/attendance/correct') {
    (new \App\Controllers\AttendanceController())->correct();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/settings/save') {
    (new \App\Controllers\SettingsController())->save();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/holidays/add') {
    (new \App\Controllers\SettingsController())->addHoliday();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/holidays/delete') {
    (new \App\Controllers\SettingsController())->deleteHoliday();
    exit;
} elseif ($method === 'GET' && $path === 'hrops/employees/list') {
    (new \App\Controllers\EmployeeMasterController())->list();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/employees/save') {
    (new \App\Controllers\EmployeeMasterController())->save();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/employees/delete') {
    (new \App\Controllers\EmployeeMasterController())->delete();
    exit;
} elseif ($method === 'POST' && $path === 'admin/departments/save') {
    (new \App\Controllers\DepartmentController())->save();
    exit;
} elseif ($method === 'POST' && $path === 'admin/departments/delete') {
    (new \App\Controllers\DepartmentController())->delete();
    exit;
} elseif ($method === 'GET' && $path === 'executive/approvals/list') {
    (new \App\Controllers\ApprovalController())->list();
    exit;
} elseif ($method === 'POST' && $path === 'executive/approvals/approve') {
    (new \App\Controllers\ApprovalController())->approve();
    exit;
} elseif ($method === 'POST' && $path === 'executive/approvals/reject') {
    (new \App\Controllers\ApprovalController())->reject();
    exit;
} elseif ($method === 'GET' && (
    str_starts_with($path, 'candidate/') || 
    str_starts_with($path, 'employee/') || 
    str_starts_with($path, 'recruiter/') || 
    str_starts_with($path, 'manager/') || 
    str_starts_with($path, 'hrops/') || 
    str_starts_with($path, 'executive/') || 
    str_starts_with($path, 'superadmin/') ||
    str_starts_with($path, 'admin/')
)) {
    (new \App\Controllers\DashboardController())->genericPage($path);
    exit;
}

// Guest Routes
$page = '';
if (isset($_GET['page'])) {
    $page = $_GET['page'];
} elseif ($path === '' || $path === 'index.php') {
    $page = 'landing';
} else {
    $page = $path;
}

$content = '';
switch ($page) {
    case 'landing':
        $content = renderView('pages/landing');
        break;
    case 'signin':
        $content = renderView('auth/signin', [
            'error' => $_GET['error'] ?? null,
            'info' => $_GET['info'] ?? null
        ]);
        break;
    case 'signup':
        $content = renderView('auth/signup', [
            'error' => $_GET['error'] ?? null,
            'info' => $_GET['info'] ?? null
        ]);
        break;
    case 'privacy':
        $content = renderView('pages/privacy');
        break;
    case 'terms':
        $content = renderView('pages/terms');
        break;
    case 'security':
        $content = renderView('pages/security');
        break;
    case 'support':
        $content = renderView('pages/support');
        break;
    default:
        $content = "<h1>404 Not Found</h1>";
        break;
}

// Render Master Layout for Guest
require __DIR__ . '/../resources/views/layouts/guest.php';
