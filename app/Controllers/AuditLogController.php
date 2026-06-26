<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class AuditLogController {
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Check if current user has access to audit logs (superadmin or executive).
     */
    private function authorize() {
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['superadmin', 'executive'])) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak: Hanya Superadmin dan Executive yang diizinkan mengakses audit log.']);
            exit;
        }
    }

    /**
     * GET /superadmin/audit/data
     * Returns paginated, filterable audit log entries.
     */
    public function getData() {
        $this->authorize();
        header('Content-Type: application/json; charset=utf-8');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $search = trim($_GET['search'] ?? '');
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $tableName = trim($_GET['table_name'] ?? '');

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(al.action LIKE :search OR u.first_name LIKE :search2 OR u.last_name LIKE :search3 OR u.email LIKE :search4)";
            $params['search'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "%$search%";
            $params['search4'] = "%$search%";
        }

        if ($dateFrom !== '') {
            $where[] = "al.created_at >= :date_from";
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[] = "al.created_at <= :date_to";
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        if ($tableName !== '') {
            $where[] = "al.table_name = :table_name";
            $params['table_name'] = $tableName;
        }

        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countSql = "SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch data
        $dataSql = "
            SELECT al.id, al.user_id, al.action, al.table_name, al.ip_address, al.created_at,
                   COALESCE(CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')), 'Sistem') AS user_name,
                   u.email AS user_email,
                   u.role AS user_role
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $key => $val) {
            $dataStmt->bindValue(":$key", $val);
        }
        $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $logs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get distinct table names for filter dropdown
        $tableNames = $this->db->query("SELECT DISTINCT table_name FROM audit_logs WHERE table_name IS NOT NULL ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'data' => $logs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
            'table_names' => $tableNames
        ]);
        exit;
    }

    /**
     * GET /superadmin/audit/stats
     * Returns summary statistics for the audit log dashboard.
     */
    public function getStats() {
        $this->authorize();
        header('Content-Type: application/json; charset=utf-8');

        $totalLogs = $this->db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
        $todayLogs = $this->db->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $weekLogs = $this->db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
        $activeUsers = $this->db->query("SELECT COUNT(DISTINCT user_id) FROM audit_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => (int)$totalLogs,
                'today' => (int)$todayLogs,
                'week' => (int)$weekLogs,
                'active_users' => (int)$activeUsers
            ]
        ]);
        exit;
    }

    /**
     * POST /superadmin/audit/clear
     * TRUNCATE audit_logs + insert witness log. Superadmin ONLY.
     */
    public function clear() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json; charset=utf-8');

        // Only superadmin can clear logs
        if (($_SESSION['role'] ?? '') !== 'superadmin') {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak: Hanya Superadmin yang dapat membersihkan log.']);
            exit;
        }

        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
            exit;
        }

        try {
            // TRUNCATE all logs
            $this->db->exec("TRUNCATE TABLE audit_logs");

            // Generate UUID for the witness log entry
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $userName = $_SESSION['name'] ?? 'Unknown';
            $userId = $_SESSION['user_id'] ?? '';
            $timestamp = date('Y-m-d H:i:s');

            // Insert witness log entry (ATURAN MUTLAK from guide-for-ide.md)
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address)
                VALUES (:id, :user_id, :action, 'audit_logs', :ip)
            ");
            $stmt->execute([
                'id' => $logId,
                'user_id' => $userId,
                'action' => "Superadmin $userName telah menghapus seluruh log sistem pada $timestamp.",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Seluruh log audit berhasil dihapus. Satu entri kesaksian baru telah ditambahkan secara otomatis.'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal membersihkan log: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * GET /superadmin/settings/global
     * Returns all global settings for the configuration page.
     */
    public function getGlobalSettings() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json; charset=utf-8');

        if (($_SESSION['role'] ?? '') !== 'superadmin') {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }

        $stmt = $this->db->query("SELECT `key`, `value` FROM global_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }

        echo json_encode(['success' => true, 'settings' => $settings]);
        exit;
    }

    /**
     * POST /superadmin/settings/global/save
     * Saves global settings (SMTP, security policies, upload limits).
     */
    public function saveGlobalSettings() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json; charset=utf-8');

        if (($_SESSION['role'] ?? '') !== 'superadmin') {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }

        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input) || !is_array($input)) {
            // Fallback to POST data
            $input = $_POST;
        }

        // Allowed keys for global settings (whitelist)
        $allowedKeys = [
            'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
            'smtp_from_address', 'smtp_from_name',
            'max_upload_size_mb',
            'min_password_length', 'require_uppercase', 'require_number', 'require_special_char'
        ];

        $stmt = $this->db->prepare("
            INSERT INTO global_settings (`key`, `value`, `label`, `group`)
            VALUES (:key, :value, :label, :grp)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
        ");

        $saved = 0;
        foreach ($input as $key => $value) {
            if ($key === 'csrf_token') continue;
            if (!in_array($key, $allowedKeys)) continue;

            $label = $this->getSettingLabel($key);
            $group = $this->getSettingGroup($key);

            $stmt->execute([
                'key' => $key,
                'value' => $value,
                'label' => $label,
                'grp' => $group
            ]);
            $saved++;
        }

        // Log the settings change
        $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $logStmt = $this->db->prepare("
            INSERT INTO audit_logs (id, user_id, action, table_name, ip_address)
            VALUES (:id, :user_id, :action, 'global_settings', :ip)
        ");
        $logStmt->execute([
            'id' => $logId,
            'user_id' => $_SESSION['user_id'] ?? '',
            'action' => "Superadmin memperbarui $saved konfigurasi global.",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Berhasil menyimpan $saved pengaturan konfigurasi global."
        ]);
        exit;
    }

    /**
     * POST /superadmin/settings/test-email
     * Sends a test email via configured SMTP.
     */
    public function testEmail() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json; charset=utf-8');

        if (($_SESSION['role'] ?? '') !== 'superadmin') {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }

        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
            exit;
        }

        // Read SMTP settings from DB
        $settings = [];
        $stmt = $this->db->query("SELECT `key`, `value` FROM global_settings WHERE `key` LIKE 'smtp_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }

        $host = $settings['smtp_host'] ?? '';
        $port = $settings['smtp_port'] ?? '587';

        if (empty($host)) {
            echo json_encode(['success' => false, 'message' => 'Konfigurasi SMTP belum diatur. Silakan isi dan simpan terlebih dahulu.']);
            exit;
        }

        // Attempt to connect to SMTP server as a basic test
        $errno = 0;
        $errstr = '';
        $timeout = 5;
        $connection = @fsockopen($host, (int)$port, $errno, $errstr, $timeout);

        if ($connection) {
            $response = fgets($connection, 512);
            fclose($connection);
            echo json_encode([
                'success' => true,
                'message' => "Koneksi ke server SMTP $host:$port berhasil! Respons server: " . trim($response)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Gagal terhubung ke server SMTP $host:$port. Error: $errstr (Code: $errno)"
            ]);
        }
        exit;
    }

    /**
     * Helper to generate human-readable labels for settings keys.
     */
    private function getSettingLabel($key) {
        $labels = [
            'smtp_host' => 'Host SMTP Server',
            'smtp_port' => 'Port SMTP',
            'smtp_encryption' => 'Enkripsi SMTP',
            'smtp_username' => 'Username SMTP',
            'smtp_password' => 'Password SMTP',
            'smtp_from_address' => 'Alamat Email Pengirim',
            'smtp_from_name' => 'Nama Pengirim',
            'max_upload_size_mb' => 'Batas Ukuran Upload (MB)',
            'min_password_length' => 'Panjang Minimal Kata Sandi',
            'require_uppercase' => 'Wajib Huruf Kapital',
            'require_number' => 'Wajib Mengandung Angka',
            'require_special_char' => 'Wajib Karakter Spesial'
        ];
        return $labels[$key] ?? $key;
    }

    /**
     * Helper to determine settings group.
     */
    private function getSettingGroup($key) {
        if (str_starts_with($key, 'smtp_')) return 'smtp';
        if (str_starts_with($key, 'max_upload')) return 'security';
        if (str_starts_with($key, 'min_password') || str_starts_with($key, 'require_')) return 'security';
        return 'general';
    }
}
