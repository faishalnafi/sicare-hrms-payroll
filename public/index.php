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

// CSRF Protection Helpers are now loaded globally via app/helpers.php

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
if ($method === 'GET' && str_starts_with($path, 'storage/secured_storage/onboarding/')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('HTTP/1.1 403 Forbidden');
        $content = renderView('pages/errors/403');
        $page = 'error_403';
        require __DIR__ . '/../resources/views/layouts/guest.php';
        exit;
    }

    // Secure Referer & Sec-Fetch-Site checks to prevent direct access / copy-paste URL
    $allowedOrigin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $secFetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $secFetchDest = $_SERVER['HTTP_SEC_FETCH_DEST'] ?? '';
    $isDownload = (isset($_GET['download']) && $_GET['download'] === '1');
    
    $isValidRequest = true;
    
    // Block direct URL navigation (open in new tab / copy-paste URL)
    if ($secFetchDest === 'document' && !$isDownload) {
        $isValidRequest = false;
    }
    
    if ($isValidRequest) {
        if (!empty($secFetchSite)) {
            if ($secFetchSite !== 'same-origin' && $secFetchSite !== 'same-site') {
                // Check if it's localhost / loopback mismatch
                $currentHost = parse_url($allowedOrigin, PHP_URL_HOST);
                $isLocalCurrent = in_array(strtolower($currentHost), ['localhost', '127.0.0.1', '::1']);
                
                if ($isLocalCurrent && !empty($referer)) {
                    $refererHost = parse_url($referer, PHP_URL_HOST);
                    if ($refererHost && in_array(strtolower($refererHost), ['localhost', '127.0.0.1', '::1'])) {
                        // Allow local requests
                    } else {
                        $isValidRequest = false;
                    }
                } else {
                    $isValidRequest = false;
                }
            }
        } else {
            if (empty($referer)) {
                $isValidRequest = false;
            } else {
                $refererHost = parse_url($referer, PHP_URL_HOST);
                $currentHost = parse_url($allowedOrigin, PHP_URL_HOST);
                
                if ($refererHost && $currentHost) {
                    $localHosts = ['localhost', '127.0.0.1', '::1'];
                    $isLocalReferer = in_array(strtolower($refererHost), $localHosts);
                    $isLocalCurrent = in_array(strtolower($currentHost), $localHosts);
                    
                    if ($isLocalReferer && $isLocalCurrent) {
                        // Allow localhost / 127.0.0.1 cross-access
                    } elseif (strcasecmp($refererHost, $currentHost) !== 0) {
                        $currentHostParts = explode('.', $currentHost);
                        $refererHostParts = explode('.', $refererHost);
                        $cCount = count($currentHostParts);
                        $rCount = count($refererHostParts);
                        
                        $shareRoot = false;
                        if ($cCount >= 2 && $rCount >= 2) {
                            $currentBase = implode('.', array_slice($currentHostParts, -2));
                            $refererBase = implode('.', array_slice($refererHostParts, -2));
                            if (strcasecmp($currentBase, $refererBase) === 0) {
                                $shareRoot = true;
                            }
                        }
                        if (!$shareRoot) {
                            $isValidRequest = false;
                        }
                    }
                } else {
                    $isValidRequest = false;
                }
            }
        }
    }
    
    if (!$isValidRequest) {
        header('HTTP/1.1 403 Forbidden');
        $content = renderView('pages/errors/403');
        $page = 'error_403';
        require __DIR__ . '/../resources/views/layouts/guest.php';
        exit;
    }

    // CORS & Security Headers
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');

    $fileName = basename($path);
    $filePath = __DIR__ . '/../storage/secured_storage/onboarding/' . $fileName;
    if (file_exists($filePath)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        if ($isDownload) {
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
        }
        readfile($filePath);
        exit;
    } else {
        header('HTTP/1.1 404 Not Found');
        echo 'Berkas tidak ditemukan.';
        exit;
    }
}

if ($method === 'POST' && $path === 'auth/login') {
    (new \App\Controllers\AuthController())->login();
    exit;
} elseif ($method === 'POST' && $path === 'auth/record-login-location') {
    (new \App\Controllers\AuthController())->recordLoginLocation();
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
} elseif ($method === 'GET' && $path === 'employee/profile/fill') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        $token = $_GET['token'] ?? '';
        header("Location: /signin?redirect=" . urlencode("/employee/profile/fill?token=" . $token));
        exit;
    }
    $token = $_GET['token'] ?? '';
    try {
        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE profile_reset_token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        if (!$user) {
            $error = "Tautan pengisian identitas tidak valid, sudah digunakan, atau kedaluwarsa.";
            $content = renderView('pages/errors/invalid_token', ['message' => $error]);
            $page = 'error_token';
            require __DIR__ . '/../resources/views/layouts/app.php';
            exit;
        }
        if ($user['id'] !== $_SESSION['user_id']) {
            $error = "Tautan ini diperuntukkan bagi akun lain. Silakan keluar (logout) dan masuk dengan akun yang sesuai.";
            $content = renderView('pages/errors/invalid_token', ['message' => $error]);
            $page = 'error_token';
            require __DIR__ . '/../resources/views/layouts/app.php';
            exit;
        }
    } catch (\Exception $e) {
        $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        $content = renderView('pages/errors/invalid_token', ['message' => $error]);
        $page = 'error_token';
        require __DIR__ . '/../resources/views/layouts/app.php';
        exit;
    }
    $content = renderView('pages/employee/profile_fill', ['token' => $token]);
    $page = 'employee_profile_fill';
    require __DIR__ . '/../resources/views/layouts/app.php';
    exit;
} elseif ($method === 'POST' && $path === 'employee/profile/save-fill') {
    (new \App\Controllers\EmployeeMasterController())->saveProfileFill();
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
} elseif ($method === 'GET' && ($path === 'admin/settings/fetch-google-holidays' || $path === 'hrops/settings/fetch-google-holidays' || $path === 'superadmin/system-settings/settings/fetch-google-holidays')) {
    (new \App\Controllers\SettingsController())->fetchGoogleHolidays();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/settings/import-google-holidays' || $path === 'hrops/settings/import-google-holidays' || $path === 'superadmin/system-settings/settings/import-google-holidays')) {
    (new \App\Controllers\SettingsController())->importGoogleHolidays();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/settings/save' || $path === 'hrops/settings/save' || $path === 'superadmin/system-settings/settings/save')) {
    (new \App\Controllers\SettingsController())->save();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/holidays/add' || $path === 'hrops/holidays/add' || $path === 'superadmin/system-settings/holidays/add')) {
    (new \App\Controllers\SettingsController())->addHoliday();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/holidays/delete' || $path === 'hrops/holidays/delete' || $path === 'superadmin/system-settings/holidays/delete')) {
    (new \App\Controllers\SettingsController())->deleteHoliday();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/holidays/update' || $path === 'hrops/holidays/update' || $path === 'superadmin/system-settings/holidays/update')) {
    (new \App\Controllers\SettingsController())->updateHoliday();
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
} elseif ($method === 'POST' && $path === 'superadmin/users/reset-profile') {
    (new \App\Controllers\EmployeeMasterController())->resetProfileToken();
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
} elseif ($method === 'POST' && $path === 'superadmin/update/execute') {
    (new \App\Controllers\DashboardController())->executeUpdate();
    exit;

// --- Audit Log Routes (Bab 2) ---
} elseif ($method === 'GET' && $path === 'superadmin/audit/data') {
    (new \App\Controllers\AuditLogController())->getData();
    exit;
} elseif ($method === 'GET' && $path === 'superadmin/audit/stats') {
    (new \App\Controllers\AuditLogController())->getStats();
    exit;
} elseif ($method === 'POST' && $path === 'superadmin/audit/clear') {
    (new \App\Controllers\AuditLogController())->clear();
    exit;

// --- Global Settings Routes (Bab 2) ---
} elseif ($method === 'GET' && $path === 'superadmin/settings/global') {
    (new \App\Controllers\AuditLogController())->getGlobalSettings();
    exit;
} elseif ($method === 'POST' && $path === 'superadmin/settings/global/save') {
    (new \App\Controllers\AuditLogController())->saveGlobalSettings();
    exit;
} elseif ($method === 'POST' && $path === 'superadmin/settings/test-email') {
    (new \App\Controllers\AuditLogController())->testEmail();
    exit;
} elseif ($method === 'GET' && (
    $path === 'changelogs' ||
    $path === 'changelogs/guide' ||
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
        $content = renderView('pages/errors/404');
        break;
}

// Render Master Layout for Guest
require __DIR__ . '/../resources/views/layouts/guest.php';
