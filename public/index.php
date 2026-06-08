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

// Configure PHP Session Parameters centrally
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Set session lifetime dynamically from environment (default: 24 hours / 86400 seconds)
$sessionLifetime = 86400;
if (isset($_ENV['SESSION_LIFETIME']) && is_numeric($_ENV['SESSION_LIFETIME'])) {
    $sessionLifetime = (int)$_ENV['SESSION_LIFETIME'];
} elseif (getenv('SESSION_LIFETIME') !== false && is_numeric(getenv('SESSION_LIFETIME'))) {
    $sessionLifetime = (int)getenv('SESSION_LIFETIME');
}

ini_set('session.gc_maxlifetime', $sessionLifetime);
ini_set('session.cookie_lifetime', $sessionLifetime);

// Register the custom unified session handler (Database + Redis)
$sessionHandler = new \App\Session\UnifiedSessionHandler();
session_set_save_handler($sessionHandler, true);

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
if ($method === 'GET' && $path === 'database/migrate') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Strict security check: Only logged-in Super Admins can run migration
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/plain; charset=utf-8');
        echo "403 Forbidden: Akses ditolak. Hanya akun Super Admin yang diizinkan memicu migrasi database.";
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
    try {
        require_once __DIR__ . '/../database/update_schema.php';
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    exit;
}

if ($method === 'POST' && $path === 'auth/login') {
    (new \App\Controllers\AuthController())->login();
    exit;
} elseif ($method === 'POST' && $path === 'auth/register') {
    (new \App\Controllers\AuthController())->register();
    exit;
} elseif ($method === 'GET' && $path === 'auth/logout') {
    (new \App\Controllers\AuthController())->logout();
    exit;
} elseif ($method === 'POST' && $path === 'auth/impersonate') {
    (new \App\Controllers\AuthController())->impersonate();
    exit;
} elseif ($method === 'GET' && $path === 'auth/stop-impersonating') {
    (new \App\Controllers\AuthController())->stopImpersonating();
    exit;
} elseif ($method === 'GET' && $path === 'auth/google') {
    (new \App\Controllers\AuthController())->googleRedirect();
    exit;
} elseif ($method === 'GET' && str_starts_with($path, 'auth/google/callback')) {
    (new \App\Controllers\AuthController())->googleCallback();
    exit;
} elseif ($method === 'GET' && ($path === 'dashboard' || preg_match('/^(candidate|employee|recruiter|manager|hiring_manager|hrops|hr_ops|admin|executive|superadmin)\/dashboard$/i', $path))) {
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
} elseif ($method === 'GET' && $path === 'manager/attendance/member_monthly') {
    (new \App\Controllers\AttendanceController())->memberMonthly();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/attendance/correct') {
    (new \App\Controllers\AttendanceController())->correct();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reflection/save') {
    (new \App\Controllers\ReflectionController())->save();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reflection/save-mood') {
    (new \App\Controllers\ReflectionController())->saveMoodPulse();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reflection/save-journal') {
    (new \App\Controllers\ReflectionController())->saveJournal();
    exit;
} elseif ($method === 'POST' && $path === 'manager/reflection/feedback') {
    (new \App\Controllers\ReflectionController())->submitFeedback();
    exit;
} elseif ($method === 'GET' && $path === 'reflection/analytics') {
    (new \App\Controllers\ReflectionController())->getAnalytics();
    exit;
} elseif ($method === 'POST' && $path === 'admin/settings/save') {
    (new \App\Controllers\SettingsController())->save();
    exit;
} elseif ($method === 'POST' && $path === 'admin/holidays/add') {
    (new \App\Controllers\SettingsController())->addHoliday();
    exit;
} elseif ($method === 'POST' && $path === 'admin/holidays/delete') {
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
} elseif ($method === 'POST' && $path === 'hrops/payroll/generate') {
    (new \App\Controllers\PayrollController())->generate();
    exit;
} elseif ($method === 'GET' && $path === 'hrops/payroll/list') {
    (new \App\Controllers\PayrollController())->list();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/update-status') {
    (new \App\Controllers\PayrollController())->updateStatus();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/update-bonus') {
    (new \App\Controllers\PayrollController())->updateBonus();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/update-overtime') {
    (new \App\Controllers\PayrollController())->updateOvertime();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/update-other-deduction') {
    (new \App\Controllers\PayrollController())->updateOtherDeduction();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/delete') {
    (new \App\Controllers\PayrollController())->delete();
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
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
        $content = renderView('pages/error_404');
        break;
}

// Render Master Layout for Guest
require __DIR__ . '/../resources/views/layouts/guest.php';
