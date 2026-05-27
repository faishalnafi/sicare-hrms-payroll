<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\AuthHelper;
use App\Helpers\ViewHelper;

class AuditLogController {
    public function index() {
        // Only superadmin and executive can view audit logs
        if (!AuthHelper::hasRole('superadmin') && !AuthHelper::hasRole('executive')) {
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

        ViewHelper::render('pages/superadmin/audit_logs', ['logs' => $logs, 'title' => 'Audit Logs & Security Trail']);
    }

    public function clearLogs() {
        if (!AuthHelper::hasRole('superadmin')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $user = AuthHelper::getUser();
        
        try {
            $db->beginTransaction();

            // Clear the logs
            $db->exec("TRUNCATE TABLE audit_logs");

            // Absolute requirement: Insert the testimony log
            $actorName = $user['first_name'] . ' ' . ($user['last_name'] ?? '');
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
                $user['id'],
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
