<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class ApprovalController {
    
    private function checkAccess() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        $role = $_SESSION['role'] ?? '';
        // Only executive and superadmin can approve/reject
        $allowed = in_array($role, ['executive', 'superadmin']);
        if (!$allowed) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied: Executive or Superadmin role required.']);
            exit;
        }
    }

    public function list() {
        $this->checkAccess();
        header('Content-Type: application/json');

        try {
            $db = Database::getInstance()->getConnection();
            
            // Fetch requests
            $stmt = $db->query("
                SELECT ar.id, ar.requester_id, ar.user_id, ar.action_type, ar.new_data, ar.status, 
                       ar.rejection_reason, ar.created_at, ar.updated_at,
                       req.first_name AS req_first_name, req.last_name AS req_last_name,
                       tar.first_name AS tar_first_name, tar.last_name AS tar_last_name, tar.employee_id AS tar_employee_id,
                       app.first_name AS app_first_name, app.last_name AS app_last_name
                FROM approval_requests ar
                LEFT JOIN users req ON ar.requester_id = req.id
                LEFT JOIN users tar ON ar.user_id = tar.id
                LEFT JOIN users app ON ar.approver_id = app.id
                ORDER BY ar.created_at DESC
            ");
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Parse JSON new_data
            foreach ($requests as &$r) {
                if ($r['new_data']) {
                    $r['parsed_data'] = json_decode($r['new_data'], true);
                } else {
                    $r['parsed_data'] = null;
                }
            }

            echo json_encode(['success' => true, 'data' => $requests]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function approve() {
        $this->checkAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            // Get the request details
            $stmt = $db->prepare("SELECT * FROM approval_requests WHERE id = ?");
            $stmt->execute([$id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                return;
            }

            if ($request['status'] !== 'PENDING') {
                echo json_encode(['success' => false, 'message' => 'Request is already processed']);
                return;
            }

            $targetUserId = $request['user_id'];
            $parsedData = json_decode($request['new_data'], true);
            $new = $parsedData['new'] ?? null;

            if (!$new) {
                echo json_encode(['success' => false, 'message' => 'Invalid request data structure']);
                return;
            }

            $newRole = $new['role'] ?? 'employee';
            $newDeptId = !empty($new['department_id']) ? $new['department_id'] : null;
            $newJobTitle = $new['job_title'] ?? null;

            // Prevent self-approval (no peer-to-peer or self-approval)
            if ($targetUserId === $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Tidak dapat menyetujui mutasi atau perubahan role diri sendiri!']);
                return;
            }

            $db->beginTransaction();

            // 1. Update user
            $stmtUpdate = $db->prepare("
                UPDATE users 
                SET role = ?, department_id = ?, job_title = ? 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$newRole, $newDeptId, $newJobTitle, $targetUserId]);

            // 2. Insert into employment_history
            $historyId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmtHistory = $db->prepare("
                INSERT INTO employment_history (id, user_id, department_id, job_title, status, start_date) 
                VALUES (?, ?, ?, ?, 'ACTIVE', CURRENT_DATE)
            ");
            $stmtHistory->execute([$historyId, $targetUserId, $newDeptId, $newJobTitle]);

            // 3. Update request status
            $stmtReq = $db->prepare("
                UPDATE approval_requests 
                SET status = 'APPROVED', approver_id = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmtReq->execute([$_SESSION['user_id'], $id]);

            // 4. Log to audit_logs
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $actionText = "Menyetujui mutasi request #$id untuk karyawan #$targetUserId ke role '$newRole', divisi '" . ($newDeptId ?? 'Tanpa Divisi') . "', jabatan '$newJobTitle'";
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address) 
                VALUES (?, ?, ?, 'users', ?)
            ");
            $stmtLog->execute([$logId, $_SESSION['user_id'], $actionText, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Mutasi jabatan berhasil disetujui dan diterapkan.']);

        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal memproses persetujuan: ' . $e->getMessage()]);
        }
    }

    public function reject() {
        $this->checkAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $id = $_POST['id'] ?? '';
        $reason = $_POST['rejection_reason'] ?? '';

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            // Get the request details
            $stmt = $db->prepare("SELECT * FROM approval_requests WHERE id = ?");
            $stmt->execute([$id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                return;
            }

            if ($request['status'] !== 'PENDING') {
                echo json_encode(['success' => false, 'message' => 'Request is already processed']);
                return;
            }

            $db->beginTransaction();

            // Update request status to REJECTED
            $stmtReq = $db->prepare("
                UPDATE approval_requests 
                SET status = 'REJECTED', rejection_reason = ?, approver_id = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmtReq->execute([$reason, $_SESSION['user_id'], $id]);

            // Log to audit_logs
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $actionText = "Menolak mutasi request #$id dengan alasan: $reason";
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address) 
                VALUES (?, ?, ?, 'users', ?)
            ");
            $stmtLog->execute([$logId, $_SESSION['user_id'], $actionText, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Mutasi jabatan berhasil ditolak.']);

        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal menolak persetujuan: ' . $e->getMessage()]);
        }
    }
}
