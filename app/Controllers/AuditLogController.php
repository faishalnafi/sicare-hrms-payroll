<?php
namespace App\Controllers;

use App\Config\Database;


class AuditLogController {
    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $role = $_SESSION['role'] ?? '';
        
        // Only superadmin and executive can view audit logs
        if ($role !== 'superadmin' && $role !== 'executive') {
            header('HTTP/1.1 403 Forbidden');
            die('Access Denied: Only Superadmin and Executive can view audit logs.');
        }

        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("
            SELECT al.*, CONCAT(u.first_name, ' ', IFNULL(u.last_name, '')) as actor_name, u.role
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 1000
        ");
        $logs = $stmt->fetchAll();

        $data = ['logs' => $logs, 'title' => 'Audit Logs & Security Trail'];
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            echo renderView('pages/superadmin/audit_logs', $data);
        } else {
            $content = renderView('pages/superadmin/audit_logs', $data);
            $page = 'superadmin_audit_list';
            require __DIR__ . '/../../resources/views/layouts/app.php';
        }
    }

    public function clearLogs() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $role = $_SESSION['role'] ?? '';
        
        if ($role !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();

            // Clear the logs
            $db->exec("TRUNCATE TABLE audit_logs");

            // Absolute requirement: Insert the testimony log
            $actorName = $_SESSION['name'] ?? 'Superadmin';
            $userId = $_SESSION['user_id'] ?? 'unknown';
            $timestamp = date('Y-m-d H:i:s');
            $desc = "Superadmin [{$actorName}] telah menghapus seluruh log sistem pada [{$timestamp}]";
            
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmt = $db->prepare("INSERT INTO audit_logs (id, user_id, action, table_name, ip_address, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $logId,
                $userId,
                'TRUNCATE',
                'audit_logs',
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $desc,
                $timestamp
            ]);

            $db->commit();
            
            echo json_encode(['success' => true, 'message' => 'All logs have been cleared securely. Trial log created.']);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to clear logs: ' . $e->getMessage()]);
        }
    }
}
