<?php

namespace App\Controllers;

/**
 * Class DashboardController
 * 
 * Manages the routing, authorization checks, and rendering of all role-specific 
 * dashboard and page views in the application. Acting as the core navigation 
 * controller of the MVC pattern.
 * 
 * @package App\Controllers
 */
class DashboardController {
    
    /**
     * Renders the role-specific dashboard page.
     * 
     * - OOP: Checks user roles and manages controller-based path redirections.
     * - MVC: Acting as Controller, verifies current role session attributes,
     *   checks reflection and mood pulse compliance models, queries database settings/logs,
     *   and renders either the standalone SPA dashboard view or standard layout view.
     * - Connections: Accesses current session data, queries DB via App\Config\Database singleton,
     *   loads layouts via renderView("dashboard/index", $data) and requires layouts/app.php.
     * 
     * @return void
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION["user_id"])) {
            header("Location: /signin");
            exit;
        }

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        $role = $_SESSION["role"] ?? "employee";

        // Extract requested role from path
        $requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $path = trim($requestUri, "/");
        $parts = explode("/", $path);
        $requestedRole = $parts[0] ?? "";

        // Redirect /dashboard to the specific role dashboard
        if ($path === "dashboard" || empty($path)) {
            $roleFolder = $role;
            if ($role === "hiring_manager") $roleFolder = "manager";
            if ($role === "hr_ops") $roleFolder = "hrops";
            header("Location: /" . $roleFolder . "/dashboard");
            exit;
        }

        // Authorize path prefix
        $allowed = false;
        if ($role === "superadmin") {
            $allowed = true; // Superadmin has unrestricted access
        } else {
            $cleanRole = str_replace("_", "", $role);
            $cleanReq = str_replace("_", "", $requestedRole);
            if ($cleanRole === $cleanReq) {
                $allowed = true;
            } elseif ($role === "hiring_manager" && $requestedRole === "manager") {
                $allowed = true;
            } elseif ($role === "manager" && $requestedRole === "hiring_manager") {
                $allowed = true;
            } elseif ($role === "hr_ops" && $requestedRole === "hrops") {
                $allowed = true;
            } elseif ($role === "hrops" && $requestedRole === "hr_ops") {
                $allowed = true;
            }
        }

        if (!$allowed) {
            $isAjax = !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest";
            if ($isAjax) {
                http_response_code(403);
                echo renderView("pages/errors/403");
            } else {
                $content = renderView("pages/errors/403");
                $page = "error_403";
                require __DIR__ . "/../../resources/views/layouts/app.php";
            }
            exit;
        }

        // 1. If employee (and not superadmin impersonating), check quarterly reflection compliance
        $origRole = $_SESSION["original_role"] ?? "";
        if ($role === "employee" && $origRole !== "superadmin") {
            $db = \App\Config\Database::getInstance()->getConnection();
            $userId = $_SESSION["user_id"];

            // Check if employee is a new hire (within 60 days) to grant an onboarding grace period
            $isNewHire = false;
            try {
                $stmtHist = $db->prepare("SELECT 1 FROM employment_history WHERE user_id = :uid AND start_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 60 DAY) LIMIT 1");
                $stmtHist->execute(['uid' => $userId]);
                $isNewHire = (bool)$stmtHist->fetchColumn();

                if (!$isNewHire) {
                    $stmtUserDate = $db->prepare("SELECT 1 FROM users WHERE id = :uid AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) LIMIT 1");
                    $stmtUserDate->execute(['uid' => $userId]);
                    $isNewHire = (bool)$stmtUserDate->fetchColumn();
                }
            } catch (\Exception $e) {}

            if (!$isNewHire) {
                $currentPeriod = date("Y") . "-Q" . ceil(date("n") / 3);

                $stmt = $db->prepare("SELECT COUNT(*) FROM self_reflections WHERE user_id = :user_id AND period = :period AND status IN ('submitted', 'completed')");
                $stmt->execute(["user_id" => $userId, "period" => $currentPeriod]);
                $hasQuarterly = $stmt->fetchColumn() > 0;

                if (!$hasQuarterly) {
                    header("Location: /employee/reflection?warning=1");
                    exit;
                }
            }
        }

        // 2. Check 2-weekly mood pulse compliance for dashboard warning
        $showMoodWarning = false;
        if ($role === "employee") {
            $db = \App\Config\Database::getInstance()->getConnection();
            $userId = $_SESSION["user_id"];
            $currentBiPeriod = date("Y") . "-B" . ceil(date("W") / 2);

            $stmtMood = $db->prepare("SELECT COUNT(*) FROM mood_pulses WHERE user_id = :user_id AND period = :period");
            $stmtMood->execute(["user_id" => $userId, "period" => $currentBiPeriod]);
            $hasMoodPulse = $stmtMood->fetchColumn() > 0;
            $showMoodWarning = !$hasMoodPulse;
        }

        $db = \App\Config\Database::getInstance()->getConnection();
        $userId = $_SESSION["user_id"];
        
        // Auto-delete login logs from previous months (keep only current month)
        $db->exec("DELETE FROM login_logs WHERE login_at < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')");
        
        $stmtLogs = $db->prepare("SELECT * FROM login_logs WHERE user_id = :user_id ORDER BY login_at DESC LIMIT 100");
        $stmtLogs->execute(["user_id" => $userId]);
        $loginLogs = $stmtLogs->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch office settings
        $stmtCfg = $db->query("SELECT `key`, `value` FROM global_settings WHERE `group` = 'attendance'");
        $cfg = [];
        while ($row = $stmtCfg->fetch(\PDO::FETCH_ASSOC)) {
            $cfg[$row["key"]] = $row["value"];
        }

        $roleFolder = $role;
        if ($role === "hiring_manager") $roleFolder = "manager";
        if ($role === "hr_ops") $roleFolder = "hrops";

        $data = [
            "name" => $_SESSION["name"] ?? "User",
            "role" => $role,
            "roleFolder" => $roleFolder,
            "email" => $_SESSION["email"] ?? "",
            "showMoodWarning" => $showMoodWarning,
            "loginLogs" => $loginLogs,
            "cfg" => $cfg
        ];

        // Check if request is AJAX (SPA mode)
        $isAjax = !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest";

        if ($isAjax) {
            // Only return the content part
            echo renderView("dashboard/index", $data);
        } else {
            // Return full SPA layout
            $content = renderView("dashboard/index", $data);
            $page = "dashboard";
            require __DIR__ . "/../../resources/views/layouts/app.php";
        }
    }

    /**
     * Universally processes routing for sub-pages based on role-based authorization rules.
     * 
     * - OOP: Enforces system folder-level prefix and path access permission logic.
     * - MVC: Acting as Controller, checks quarterly reflection compliance models for employees,
     *   verifies path parameters against whitelist permissions, falls back to custom unbuilt 
     *   "coming soon" pages or 404/403 error pages, and renders requested sub-view paths.
     * - Connections: Interacts with session variables, database query singletons,
     *   and outputs view pages dynamically by requiring resources/views/layouts/app.php.
     * 
     * @param string $path The page path request from the router (e.g. "employee/profile").
     * @return void
     */
    public function genericPage($path) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION["user_id"])) {
            header("Location: /signin");
            exit;
        }

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        $role = $_SESSION["role"] ?? "employee";

        // 1. If employee (and not superadmin impersonating), enforce quarterly reflection check (except for the reflection page itself)
        $origRole = $_SESSION["original_role"] ?? "";
        if ($role === "employee" && $origRole !== "superadmin" && $path !== "employee/reflection") {
            $db = \App\Config\Database::getInstance()->getConnection();
            $userId = $_SESSION["user_id"];

            // Check if employee is a new hire (within 60 days) to grant an onboarding grace period
            $isNewHire = false;
            try {
                $stmtHist = $db->prepare("SELECT 1 FROM employment_history WHERE user_id = :uid AND start_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 60 DAY) LIMIT 1");
                $stmtHist->execute(['uid' => $userId]);
                $isNewHire = (bool)$stmtHist->fetchColumn();

                if (!$isNewHire) {
                    $stmtUserDate = $db->prepare("SELECT 1 FROM users WHERE id = :uid AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) LIMIT 1");
                    $stmtUserDate->execute(['uid' => $userId]);
                    $isNewHire = (bool)$stmtUserDate->fetchColumn();
                }
            } catch (\Exception $e) {}

            if (!$isNewHire) {
                $currentPeriod = date("Y") . "-Q" . ceil(date("n") / 3);

                $stmt = $db->prepare("SELECT COUNT(*) FROM self_reflections WHERE user_id = :user_id AND period = :period AND status IN ('submitted', 'completed')");
                $stmt->execute(["user_id" => $userId, "period" => $currentPeriod]);
                $hasQuarterly = $stmt->fetchColumn() > 0;

                if (!$hasQuarterly) {
                    header("Location: /employee/reflection?warning=1");
                    exit;
                }
            }
        }

        // Authorize path prefix
        $allowed = false;
        if ($role === "superadmin") {
            $allowed = true; // Superadmin has unrestricted access
        } else {
            if ($path === "changelogs/guide") {
                $allowed = ($role === "admin");
            } else {
                $parts = explode("/", $path);
                $prefix = $parts[0] ?? "";
                
                switch ($prefix) {
                    case "employee":
                        $allowed = ($role === "employee");
                        break;
                    case "manager":
                        $allowed = ($role === "hiring_manager" || $role === "manager");
                        break;
                    case "hrops":
                        $allowed = ($role === "hr_ops" || $role === "hrops");
                        break;
                    case "executive":
                        $allowed = ($role === "executive");
                        break;
                    case "admin":
                        $allowed = ($role === "admin");
                        break;
                    case "recruiter":
                        $allowed = ($role === "recruiter");
                        break;
                    case "candidate":
                        $allowed = ($role === "candidate" || $role === "employee");
                        break;
                    case "superadmin":
                        if ($path === "superadmin/audit") {
                            $allowed = ($role === "superadmin" || $role === "executive");
                        } else {
                            $allowed = ($role === "superadmin");
                        }
                        break;
                    default:
                        // If no matching prefix (e.g. root pages or other views), allow access
                        $allowed = true;
                        break;
                }
            }
        }

        $viewPath = "pages/" . $path;
        if ($path === "superadmin/departments") {
            $viewPath = "pages/admin/departments";
        }
        if ($path === "superadmin/approvals/manager") {
            $viewPath = "pages/manager/approvals";
        }
        if (!$allowed) {
            $viewPath = "pages/errors/403";
        }

        $fullPath = __DIR__ . "/../../resources/views/" . $viewPath . ".php";

        // Intercept apps route to point to the dedicated apps empty state
        if (preg_match('/^[a-z_]+\/apps$/', $path)) {
            $viewPath = "pages/apps/index";
            $fullPath = __DIR__ . "/../../resources/views/" . $viewPath . ".php";
        }

        // Universal fallback to beautiful Coming Soon page or 404/403 page if the view doesn't exist
        if (!file_exists($fullPath)) {
            if (!$allowed) {
                $viewPath = "pages/errors/404";
            } else {
                $validUnbuilt = [
                    "candidate/jobs", "candidate/profile", "candidate/interviews", "candidate/offerings", "candidate/onboarding",
                    "employee/profile", "employee/attendance", "employee/leaves", "employee/finance", "employee/reimbursements", "employee/reflection", "employee/apps",
                    "recruiter/jobs", "recruiter/ats", "recruiter/interviews", "recruiter/offerings", "recruiter/apps",
                    "manager/requisitions", "manager/candidates", "manager/interviews", "manager/approvals", "manager/apps",
                    "hrops/onboarding", "hrops/employees", "hrops/verifications", "hrops/payroll", "hrops/apps",
                    "admin/departments", "admin/users", "admin/settings", "admin/apps",
                    "executive/analytics", "executive/budgets", "executive/approvals", "executive/apps",
                    "superadmin/apps", "superadmin/api",
                    "superadmin/approvals/executive", "superadmin/approvals/hrops", "superadmin/approvals/recruiter"
                ];
                if (in_array($path, $validUnbuilt)) {
                    $viewPath = "pages/errors/coming_soon";
                } else {
                    $viewPath = "pages/errors/404";
                }
            }
        }

        $data = [
            "name" => $_SESSION["name"] ?? "User",
            "role" => $role,
            "email" => $_SESSION["email"] ?? "",
            "requestedPath" => $path
        ];

        $isAjax = !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest";

        if ($isAjax) {
            echo renderView($viewPath, $data);
        } else {
            $content = renderView($viewPath, $data);
            $page = str_replace("/", "_", $path);
            require __DIR__ . "/../../resources/views/layouts/app.php";
        }
    }

    /**
     * Executes a full system and database schema upgrade.
     * 
     * - OOP: Utilizes shared PDO database connection singleton.
     * - MVC: Controller action handling system update POST request events, validating
     *   security tokens, executing migrations, updates database logs, and returning JSON.
     * - Connections: Checks CSRF security tokens, requires database/update_schema.php migration script,
     *   parses and synchronizes changelog.json definitions with the database, terminates other
     *   active user sessions, and logs actions to audit_logs table.
     * 
     * @return void
     */
    public function executeUpdate() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header("Content-Type: application/json");

        // Check if user is logged in and is a superadmin
        if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "superadmin") {
            echo json_encode(["success" => false, "message" => "Akses ditolak. Hanya Super Admin yang diizinkan melakukan update."]);
            exit;
        }

        // Validate CSRF token
        $csrfToken = $_POST["csrf_token"] ?? $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
        if (empty($csrfToken) || $csrfToken !== ($_SESSION["csrf_token"] ?? "")) {
            echo json_encode(["success" => false, "message" => "Token CSRF tidak valid. Silakan muat ulang halaman."]);
            exit;
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();

            // 1. Run database migration (update_schema.php)
            // Use output buffering to prevent database echo statements from breaking JSON response
            ob_start();
            require_once __DIR__ . "/../../database/update_schema.php";
            $migrationOutput = ob_get_clean();

            // 2. Read changelog.json and sync to changelogs table
            $jsonPath = __DIR__ . "/../../changelog.json";
            if (file_exists($jsonPath)) {
                $jsonContent = file_get_contents($jsonPath);
                $releases = json_decode($jsonContent, true);

                if (is_array($releases)) {
                    $stmt = $db->prepare("
                        INSERT INTO changelogs (version, edition, repo_type, compiled_date, migration_level, alias_name, summary)
                        VALUES (:version, :edition, :repo_type, :compiled_date, :migration_level, :alias_name, :summary)
                        ON DUPLICATE KEY UPDATE 
                            edition = VALUES(edition),
                            repo_type = VALUES(repo_type),
                            compiled_date = VALUES(compiled_date),
                            migration_level = VALUES(migration_level),
                            alias_name = VALUES(alias_name),
                            summary = VALUES(summary)
                    ");

                    foreach ($releases as $rel) {
                        $stmt->execute([
                            "version" => $rel["version"],
                            "edition" => $rel["edition"],
                            "repo_type" => $rel["repo_type"] ?? "monorepo",
                            "compiled_date" => $rel["compiled_date"],
                            "migration_level" => $rel["migration_level"],
                            "alias_name" => $rel["alias_name"] ?? null,
                            "summary" => json_encode($rel["summary"])
                        ]);
                    }
                }
            }

            // 3. Reset all other role sessions
            $myId = $_SESSION["user_id"];
            $stmtSession = $db->prepare("DELETE FROM sessions WHERE user_id IS NULL OR user_id != :my_id");
            $stmtSession->execute(["my_id" => $myId]);

            // Save system update event to audit log
            $logStmt = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address)
                VALUES (:id, :user_id, :action, 'changelogs', :ip)
            ");
            $logId = sprintf("%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $logStmt->execute([
                "id" => $logId,
                "user_id" => $myId,
                "action" => "Melakukan update sistem ke versi terbaru dari JSON dan mereset session user lain.",
                "ip" => $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1"
            ]);

            echo json_encode([
                "success" => true,
                "message" => "Update sistem berhasil! Skema database telah dimigrasikan, riwayat rilis disinkronkan, dan seluruh sesi aktif pengguna lain telah direset secara aman.",
                "migration_log" => trim($migrationOutput)
            ]);
            exit;

        } catch (\Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => "Gagal melakukan update: " . $e->getMessage()
            ]);
            exit;
        }
    }

    public function saveApp() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header("Content-Type: application/json");

        $role = $_SESSION["role"] ?? "";
        if ($role !== "superadmin" && $role !== "admin") {
            echo json_encode(["success" => false, "message" => "Akses ditolak."]);
            exit;
        }

        if (!$this->validateCsrf()) {
            echo json_encode(["success" => false, "message" => "Token CSRF tidak valid atau kedaluwarsa. Silakan muat ulang halaman."]);
            exit;
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $id = $_POST["id"] ?? "";
            $name = $_POST["name"] ?? "";
            $sortOrder = (int)($_POST["sort_order"] ?? 0);
            $description = $_POST["description"] ?? "";
            $logoUrl = $_POST["logo_url"] ?? "";
            
            // Handle Logo File Upload if present
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['logo_file'];
                
                // Validate size (max 1MB)
                if ($file['size'] > 1048576) {
                    echo json_encode(["success" => false, "message" => "Kapasitas file logo maksimal adalah 1MB."]);
                    exit;
                }
                
                // Validate MIME via finfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                $allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'];
                if (in_array($mimeType, $allowedMimes)) {
                    $uploadDir = __DIR__ . '/../../public/uploads/logos/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    if (empty($ext)) {
                        $extMap = [
                            'image/png' => 'png',
                            'image/jpeg' => 'jpg',
                            'image/gif' => 'gif',
                            'image/svg+xml' => 'svg',
                            'image/webp' => 'webp'
                        ];
                        $ext = $extMap[$mimeType] ?? 'png';
                    }
                    
                    $filename = 'logo_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
                    $destination = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $logoUrl = '/uploads/logos/' . $filename;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file logo ke server.']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Format file logo tidak didukung. Gunakan PNG, JPG, GIF, SVG, atau WebP.']);
                    exit;
                }
            }

            $iconMaterial = $_POST["icon_material"] ?? "apps";
            $iconColor = $_POST["icon_color"] ?? "blue";
            $portalUrl = $_POST["portal_url"] ?? "";
            $loginUrl = $_POST["login_url"] ?? "";
            $visibilityType = $_POST["visibility_type"] ?? "global";
            
            $allowedRoles = $_POST["allowed_roles"] ?? null;
            if (is_array($allowedRoles)) {
                $allowedRoles = implode(",", $allowedRoles);
            }
            
            $allowedDepartments = $_POST["allowed_departments"] ?? null;
            if (is_array($allowedDepartments)) {
                $allowedDepartments = implode(",", $allowedDepartments);
            }

            if ($visibilityType === 'global') {
                $allowedRoles = null;
                $allowedDepartments = null;
            }

            $isActive = isset($_POST["is_active"]) && ($_POST["is_active"] == "1" || $_POST["is_active"] == "true") ? 1 : 0;

            if ($isActive === 1) {
                if (empty($id)) {
                    $checkStmt = $db->prepare("SELECT name FROM portal_applications WHERE sort_order = :sort_order AND is_active = 1 LIMIT 1");
                    $checkStmt->execute(["sort_order" => $sortOrder]);
                } else {
                    $checkStmt = $db->prepare("SELECT name FROM portal_applications WHERE sort_order = :sort_order AND is_active = 1 AND id != :id LIMIT 1");
                    $checkStmt->execute(["sort_order" => $sortOrder, "id" => $id]);
                }
                $existingApp = $checkStmt->fetch(\PDO::FETCH_ASSOC);
                if ($existingApp) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Nomor urut {$sortOrder} sudah digunakan oleh aplikasi aktif '" . $existingApp['name'] . "'. Silakan gunakan nomor urut lain."
                    ]);
                    exit;
                }
            }

            if (empty($name)) {
                echo json_encode(["success" => false, "message" => "Nama aplikasi wajib diisi."]);
                exit;
            }

            if (empty($id)) {
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $stmt = $db->prepare("
                    INSERT INTO portal_applications (id, name, sort_order, description, logo_url, icon_material, icon_color, portal_url, login_url, visibility_type, allowed_roles, allowed_departments, is_active)
                    VALUES (:id, :name, :sort_order, :description, :logo_url, :icon_material, :icon_color, :portal_url, :login_url, :visibility_type, :allowed_roles, :allowed_departments, :is_active)
                ");
            } else {
                $stmt = $db->prepare("
                    UPDATE portal_applications 
                    SET name = :name, sort_order = :sort_order, description = :description, logo_url = :logo_url, 
                        icon_material = :icon_material, icon_color = :icon_color, portal_url = :portal_url, login_url = :login_url, 
                        visibility_type = :visibility_type, allowed_roles = :allowed_roles, allowed_departments = :allowed_departments, is_active = :is_active
                    WHERE id = :id
                ");
            }

            $stmt->execute([
                "id" => $id,
                "name" => $name,
                "sort_order" => $sortOrder,
                "description" => $description,
                "logo_url" => empty($logoUrl) ? null : $logoUrl,
                "icon_material" => $iconMaterial,
                "icon_color" => $iconColor,
                "portal_url" => empty($portalUrl) ? null : $portalUrl,
                "login_url" => empty($loginUrl) ? null : $loginUrl,
                "visibility_type" => $visibilityType,
                "allowed_roles" => $allowedRoles,
                "allowed_departments" => $allowedDepartments,
                "is_active" => $isActive
            ]);

            echo json_encode(["success" => true, "message" => "Aplikasi berhasil disimpan."]);
            exit;
        } catch (\Exception $e) {
            echo json_encode(["success" => false, "message" => "Gagal menyimpan aplikasi: " . $e->getMessage()]);
            exit;
        }
    }

    public function deleteApp() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header("Content-Type: application/json");

        $role = $_SESSION["role"] ?? "";
        if ($role !== "superadmin" && $role !== "admin") {
            echo json_encode(["success" => false, "message" => "Akses ditolak."]);
            exit;
        }

        if (!$this->validateCsrf()) {
            echo json_encode(["success" => false, "message" => "Token CSRF tidak valid atau kedaluwarsa. Silakan muat ulang halaman."]);
            exit;
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $id = $_POST["id"] ?? "";

            if (empty($id)) {
                echo json_encode(["success" => false, "message" => "ID aplikasi tidak valid."]);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM portal_applications WHERE id = :id");
            $stmt->execute(["id" => $id]);

            echo json_encode(["success" => true, "message" => "Aplikasi berhasil dihapus."]);
            exit;
        } catch (\Exception $e) {
            echo json_encode(["success" => false, "message" => "Gagal menghapus aplikasi: " . $e->getMessage()]);
            exit;
        }
    }

    private function validateCsrf(): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
