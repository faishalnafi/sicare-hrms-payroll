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

// Start session early to process remember-me
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-login via remember_me_token cookie
\App\Controllers\AuthController::processRememberMe();

// Synchronize session role with database if user is logged in
if (isset($_SESSION['user_id']) && empty($_SESSION['original_role'])) {
    try {
        $syncDb = \App\Config\Database::getInstance()->getConnection();
        $syncStmt = $syncDb->prepare("SELECT role, role_id, department_id, is_suspended, is_deleted FROM users WHERE id = :uid LIMIT 1");
        $syncStmt->execute(['uid' => $_SESSION['user_id']]);
        $syncUser = $syncStmt->fetch(\PDO::FETCH_ASSOC);
        if ($syncUser) {
            if ((int)($syncUser['is_deleted'] ?? 0) === 1 || (int)($syncUser['is_suspended'] ?? 0) === 1) {
                session_unset();
                session_destroy();
                header("Location: /signin?error=" . urlencode("Akun Anda telah dinonaktifkan atau ditangguhkan."));
                exit;
            }
            if (($syncUser['role'] ?? '') !== ($_SESSION['role'] ?? '')) {
                $_SESSION['role'] = $syncUser['role'];
                $_SESSION['role_id'] = $syncUser['role_id'];
                $_SESSION['department_id'] = $syncUser['department_id'];
            }
        }
    } catch (\Exception $e) {}
}

// CSRF Protection Helpers are now loaded globally via app/helpers.php

// Simple Router
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($requestUri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Central JWT Token verification for all CRUD write operations (POST) when logged in
if ($method === 'POST') {
    $excludedRoutes = ['auth/login', 'auth/register', 'auth/record-login-location'];
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id']) && !in_array($path, $excludedRoutes)) {
        $token = '';
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $token = $headers['X-JWT-Token'] ?? $headers['x-jwt-token'] ?? '';
        }
        if (empty($token)) {
            $token = $_SERVER['HTTP_X_JWT_TOKEN'] ?? $_POST['jwt_token'] ?? $_COOKIE['jwt_token'] ?? '';
        }
        
        if (empty($token) || !\App\Config\JwtHelper::validateToken($token)) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Token JWT tidak valid atau kedaluwarsa. Silakan muat ulang halaman dan coba lagi.'
            ]);
            exit;
        }
    }
}

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
    app(\App\Controllers\AuthController::class)->login();
    exit;
} elseif ($method === 'POST' && $path === 'auth/record-login-location') {
    app(\App\Controllers\AuthController::class)->recordLoginLocation();
    exit;
} elseif ($method === 'POST' && $path === 'auth/register') {
    app(\App\Controllers\AuthController::class)->register();
    exit;
} elseif ($method === 'GET' && $path === 'auth/logout') {
    app(\App\Controllers\AuthController::class)->logout();
    exit;
} elseif ($method === 'POST' && $path === 'auth/impersonate') {
    app(\App\Controllers\AuthController::class)->impersonate();
    exit;
} elseif ($method === 'GET' && $path === 'auth/stop-impersonating') {
    app(\App\Controllers\AuthController::class)->stopImpersonating();
    exit;
} elseif ($method === 'GET' && $path === 'auth/google') {
    app(\App\Controllers\AuthController::class)->googleRedirect();
    exit;
} elseif ($method === 'GET' && str_starts_with($path, 'auth/google/callback')) {
    app(\App\Controllers\AuthController::class)->googleCallback();
    exit;
} elseif ($method === 'GET' && ($path === 'dashboard' || preg_match('/^(candidate|employee|recruiter|manager|hiring_manager|hrops|hr_ops|admin|executive|superadmin)\/dashboard$/i', $path))) {
    app(\App\Controllers\DashboardController::class)->index();
    exit;
} elseif ($method === 'POST' && $path === 'employee/profile/correction') {
    app(\App\Controllers\CorrectionController::class)->submit();
    exit;
} elseif ($method === 'POST' && $path === 'employee/profile/save') {
    app(\App\Controllers\AuthController::class)->saveProfile();
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
    $valResult = \App\Controllers\AuthController::validateProfileResetToken($token);
    if (!$valResult['valid']) {
        $content = renderView('pages/errors/invalid_token', ['message' => $valResult['error']]);
        $page = 'error_token';
        require __DIR__ . '/../resources/views/layouts/app.php';
        exit;
    }
    $content = renderView('pages/employee/profile_fill', ['token' => $token]);
    $page = 'employee_profile_fill';
    require __DIR__ . '/../resources/views/layouts/app.php';
    exit;
} elseif ($method === 'POST' && $path === 'employee/profile/save-fill') {
    app(\App\Controllers\EmployeeMasterController::class)->saveProfileFill();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/verifications/approve' || $path === 'hrops/correction/approve')) {
    app(\App\Controllers\CorrectionController::class)->approve();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/verifications/reject' || $path === 'hrops/correction/reject')) {
    app(\App\Controllers\CorrectionController::class)->reject();
    exit;
} elseif ($method === 'GET' && ($path === 'hrops/verifications/view_file' || $path === 'hrops/correction/view_file')) {
    app(\App\Controllers\CorrectionController::class)->viewFile();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reimbursements/submit') {
    app(\App\Controllers\ReimbursementController::class)->submit();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reimbursements/cancel') {
    app(\App\Controllers\ReimbursementController::class)->cancel();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/reimbursements/approve' || $path === 'manager/reimbursements/approve')) {
    app(\App\Controllers\ReimbursementController::class)->approve();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/reimbursements/reject' || $path === 'manager/reimbursements/reject')) {
    app(\App\Controllers\ReimbursementController::class)->reject();
    exit;
} elseif ($method === 'GET' && ($path === 'hrops/reimbursements/view_receipt' || $path === 'manager/reimbursements/view_receipt')) {
    app(\App\Controllers\ReimbursementController::class)->viewReceipt();
    exit;
} elseif ($method === 'POST' && $path === 'employee/leaves/submit') {
    app(\App\Controllers\LeaveController::class)->submit();
    exit;
} elseif ($method === 'POST' && $path === 'employee/leaves/cancel') {
    app(\App\Controllers\LeaveController::class)->cancel();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/leaves/approve' || $path === 'manager/leaves/approve')) {
    app(\App\Controllers\LeaveController::class)->approve();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/leaves/reject' || $path === 'manager/leaves/reject')) {
    app(\App\Controllers\LeaveController::class)->reject();
    exit;
} elseif ($method === 'GET' && ($path === 'hrops/leaves/view_attachment' || $path === 'manager/leaves/view_attachment')) {
    app(\App\Controllers\LeaveController::class)->viewAttachment();
    exit;
} elseif ($method === 'POST' && $path === 'employee/attendance/clockin') {
    app(\App\Controllers\AttendanceController::class)->clockIn();
    exit;
} elseif ($method === 'POST' && $path === 'employee/attendance/clockout') {
    app(\App\Controllers\AttendanceController::class)->clockOut();
    exit;
} elseif ($method === 'GET' && $path === 'hrops/attendance/logs') {
    app(\App\Controllers\AttendanceController::class)->hrLogs();
    exit;
} elseif ($method === 'GET' && $path === 'manager/attendance/member_monthly') {
    app(\App\Controllers\AttendanceController::class)->memberMonthly();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/attendance/correct') {
    app(\App\Controllers\AttendanceController::class)->correct();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reflection/save') {
    app(\App\Controllers\ReflectionController::class)->save();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reflection/save-mood') {
    app(\App\Controllers\ReflectionController::class)->saveMoodPulse();
    exit;
} elseif ($method === 'POST' && $path === 'employee/reflection/save-journal') {
    app(\App\Controllers\ReflectionController::class)->saveJournal();
    exit;
} elseif ($method === 'POST' && $path === 'manager/reflection/feedback') {
    app(\App\Controllers\ReflectionController::class)->submitFeedback();
    exit;
} elseif ($method === 'GET' && ($path === 'reflection/analytics' || $path === 'employee/reflection/analytics' || $path === 'executive/reflection/analytics' || $path === 'hrops/reflection/analytics')) {
    app(\App\Controllers\ReflectionController::class)->getAnalytics();
    exit;
} elseif ($method === 'GET' && $path === 'sse/notifications') {
    app(\App\Controllers\NotificationController::class)->stream();
    exit;
} elseif ($method === 'GET' && ($path === 'admin/settings/fetch-google-holidays' || $path === 'hrops/settings/fetch-google-holidays' || $path === 'superadmin/system-settings/settings/fetch-google-holidays')) {
    app(\App\Controllers\SettingsController::class)->fetchGoogleHolidays();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/settings/import-google-holidays' || $path === 'hrops/settings/import-google-holidays' || $path === 'superadmin/system-settings/settings/import-google-holidays')) {
    app(\App\Controllers\SettingsController::class)->importGoogleHolidays();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/settings/save' || $path === 'hrops/settings/save' || $path === 'superadmin/system-settings/settings/save')) {
    app(\App\Controllers\SettingsController::class)->save();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/holidays/add' || $path === 'hrops/holidays/add' || $path === 'superadmin/system-settings/holidays/add')) {
    app(\App\Controllers\SettingsController::class)->addHoliday();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/holidays/delete' || $path === 'hrops/holidays/delete' || $path === 'superadmin/system-settings/holidays/delete')) {
    app(\App\Controllers\SettingsController::class)->deleteHoliday();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/holidays/update' || $path === 'hrops/holidays/update' || $path === 'superadmin/system-settings/holidays/update')) {
    app(\App\Controllers\SettingsController::class)->updateHoliday();
    exit;
} elseif ($method === 'GET' && ($path === 'hrops/employees/list' || $path === 'superadmin/employees/list' || $path === 'admin/employees/list')) {
    app(\App\Controllers\EmployeeMasterController::class)->list();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/employees/save' || $path === 'superadmin/employees/save' || $path === 'admin/employees/save')) {
    app(\App\Controllers\EmployeeMasterController::class)->save();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/employees/delete' || $path === 'superadmin/employees/delete' || $path === 'admin/employees/delete')) {
    app(\App\Controllers\EmployeeMasterController::class)->delete();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/employees/toggle-suspend' || $path === 'superadmin/employees/toggle-suspend' || $path === 'admin/employees/toggle-suspend')) {
    app(\App\Controllers\EmployeeMasterController::class)->toggleSuspend();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/employees/empty-trash' || $path === 'superadmin/employees/empty-trash' || $path === 'admin/employees/empty-trash')) {
    app(\App\Controllers\EmployeeMasterController::class)->emptyTrash();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/employees/delete-permanent' || $path === 'superadmin/employees/delete-permanent' || $path === 'admin/employees/delete-permanent')) {
    app(\App\Controllers\EmployeeMasterController::class)->deletePermanent();
    exit;
} elseif ($method === 'POST' && ($path === 'hrops/employees/restore' || $path === 'superadmin/employees/restore' || $path === 'admin/employees/restore')) {
    app(\App\Controllers\EmployeeMasterController::class)->restore();
    exit;
} elseif ($method === 'POST' && $path === 'superadmin/users/reset-profile') {
    app(\App\Controllers\EmployeeMasterController::class)->resetProfileToken();
    exit;
} elseif ($method === 'POST' && ($path === 'superadmin/users/reset-lockout' || $path === 'admin/users/reset-lockout')) {
    app(\App\Controllers\EmployeeMasterController::class)->resetLockout();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/generate') {
    app(\App\Controllers\PayrollController::class)->generate();
    exit;
} elseif ($method === 'GET' && $path === 'hrops/payroll/list') {
    app(\App\Controllers\PayrollController::class)->list();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/update-status') {
    app(\App\Controllers\PayrollController::class)->updateStatus();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/update-bonus') {
    app(\App\Controllers\PayrollController::class)->updateBonus();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/update-overtime') {
    app(\App\Controllers\PayrollController::class)->updateOvertime();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/update-other-deduction') {
    app(\App\Controllers\PayrollController::class)->updateOtherDeduction();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/payroll/delete') {
    app(\App\Controllers\PayrollController::class)->delete();
    exit;
} elseif ($method === 'POST' && $path === 'admin/departments/save') {
    app(\App\Controllers\DepartmentController::class)->save();
    exit;
} elseif ($method === 'POST' && $path === 'admin/departments/move') {
    app(\App\Controllers\DepartmentController::class)->move();
    exit;
} elseif ($method === 'POST' && $path === 'admin/departments/delete') {
    app(\App\Controllers\DepartmentController::class)->delete();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/apps/save' || $path === 'superadmin/apps/save')) {
    app(\App\Controllers\DashboardController::class)->saveApp();
    exit;
} elseif ($method === 'POST' && ($path === 'admin/apps/delete' || $path === 'superadmin/apps/delete')) {
    app(\App\Controllers\DashboardController::class)->deleteApp();
    exit;
} elseif ($method === 'GET' && $path === 'executive/approvals/list') {
    app(\App\Controllers\ApprovalController::class)->list();
    exit;
} elseif ($method === 'POST' && $path === 'executive/approvals/approve') {
    app(\App\Controllers\ApprovalController::class)->approve();
    exit;
} elseif ($method === 'POST' && $path === 'executive/approvals/reject') {
    app(\App\Controllers\ApprovalController::class)->reject();
    exit;
} elseif ($method === 'POST' && $path === 'superadmin/update/execute') {
    app(\App\Controllers\DashboardController::class)->executeUpdate();
    exit;

// --- Job Requisitions Routes ---
} elseif ($method === 'POST' && ($path === 'requisitions/store' || $path === 'manager/requisitions/store')) {
    app(\App\Controllers\RequisitionController::class)->store();
    exit;
} elseif ($method === 'POST' && ($path === 'requisitions/approve' || $path === 'executive/requisitions/approve')) {
    app(\App\Controllers\RequisitionController::class)->approve();
    exit;
} elseif ($method === 'POST' && ($path === 'requisitions/reject' || $path === 'executive/requisitions/reject')) {
    app(\App\Controllers\RequisitionController::class)->reject();
    exit;
} elseif ($method === 'POST' && ($path === 'requisitions/revoke' || $path === 'executive/requisitions/revoke')) {
    app(\App\Controllers\RequisitionController::class)->revoke();
    exit;
} elseif ($method === 'POST' && ($path === 'requisitions/publish' || $path === 'recruiter/requisitions/publish')) {
    app(\App\Controllers\RequisitionController::class)->publish();
    exit;
} elseif ($method === 'POST' && ($path === 'requisitions/unpublish' || $path === 'recruiter/requisitions/unpublish')) {
    app(\App\Controllers\RequisitionController::class)->unpublishJob();
    exit;
} elseif ($method === 'POST' && ($path === 'candidate/apply' || $path === 'job/apply')) {
    app(\App\Controllers\RequisitionController::class)->applyJob();
    exit;
} elseif ($method === 'POST' && ($path === 'recruiter/ats/delete-application' || $path === 'recruiter/requisitions/delete-application')) {
    app(\App\Controllers\RequisitionController::class)->deleteApplication();
    exit;
} elseif ($method === 'POST' && ($path === 'recruiter/ats/update-status' || $path === 'recruiter/requisitions/update-status')) {
    app(\App\Controllers\RequisitionController::class)->updateApplicationStatus();
    exit;
} elseif ($method === 'POST' && $path === 'recruiter/interviews/store') {
    app(\App\Controllers\RecruitmentController::class)->storeInterview();
    exit;
} elseif ($method === 'POST' && $path === 'candidate/interviews/respond') {
    app(\App\Controllers\RecruitmentController::class)->respondInterview();
    exit;
} elseif ($method === 'POST' && $path === 'manager/interviews/evaluate') {
    app(\App\Controllers\RecruitmentController::class)->submitEvaluation();
    exit;
} elseif ($method === 'POST' && $path === 'recruiter/offerings/send') {
    app(\App\Controllers\RecruitmentController::class)->sendOffer();
    exit;
} elseif ($method === 'POST' && $path === 'candidate/offerings/respond') {
    app(\App\Controllers\RecruitmentController::class)->respondOffer();
    exit;
} elseif ($method === 'POST' && $path === 'candidate/onboarding/submit') {
    app(\App\Controllers\RecruitmentController::class)->submitOnboardingWizard();
    exit;
} elseif ($method === 'POST' && $path === 'hrops/onboarding/approve') {
    app(\App\Controllers\RecruitmentController::class)->approveOnboarding();
    exit;
} elseif ($method === 'POST' && $path === 'candidate/profile/update') {
    app(\App\Controllers\AuthController::class)->updateCandidateProfile();
    exit;
} elseif ($method === 'GET' && $path === 'document/view') {
    app(\App\Controllers\AuthController::class)->serveInlineDocument();
    exit;
} elseif ($method === 'POST' && $path === 'admin/import/employees') {
    app(\App\Controllers\ImportExportController::class)->importEmployeesUPSERT();
    exit;
} elseif ($method === 'GET' && $path === 'admin/export/employees') {
    app(\App\Controllers\ImportExportController::class)->exportEmployees();
    exit;
} elseif ($method === 'POST' && $path === 'api/v1/attendance') {
    app(\App\Controllers\ApiController::class)->syncAttendance();
    exit;

// --- Audit Log Routes (Bab 2) ---
} elseif ($method === 'GET' && $path === 'superadmin/audit/data') {
    app(\App\Controllers\AuditLogController::class)->getData();
    exit;
} elseif ($method === 'GET' && $path === 'superadmin/audit/stats') {
    app(\App\Controllers\AuditLogController::class)->getStats();
    exit;
} elseif ($method === 'POST' && $path === 'superadmin/audit/clear') {
    app(\App\Controllers\AuditLogController::class)->clear();
    exit;

// --- Global Settings Routes (Bab 2) ---
} elseif ($method === 'GET' && $path === 'superadmin/settings/global') {
    app(\App\Controllers\AuditLogController::class)->getGlobalSettings();
    exit;
} elseif ($method === 'POST' && $path === 'superadmin/settings/global/save') {
    app(\App\Controllers\AuditLogController::class)->saveGlobalSettings();
    exit;
} elseif ($method === 'POST' && $path === 'superadmin/settings/test-email') {
    app(\App\Controllers\AuditLogController::class)->testEmail();
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
    app(\App\Controllers\DashboardController::class)->genericPage($path);
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
    case 'careers':
    case 'jobs':
        $content = renderView('pages/careers');
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
