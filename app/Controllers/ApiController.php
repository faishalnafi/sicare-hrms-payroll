<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class ApiController {

    private function getDb() {
        return Database::getInstance()->getConnection();
    }

    /**
     * Validate X-API-Key header against system global_settings or fallback default.
     */
    private function validateApiKey(): bool {
        $apiKey = '';
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';
        }
        if (empty($apiKey)) {
            $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? $_POST['api_key'] ?? '';
        }

        if (empty($apiKey)) {
            return false;
        }

        try {
            $db = $this->getDb();
            $stmt = $db->prepare("SELECT `value` FROM global_settings WHERE `key` IN ('system_api_key', 'api_key') LIMIT 1");
            $stmt->execute();
            $validKey = $stmt->fetchColumn();

            if (empty($validKey)) {
                $validKey = 'siCare_API_Secret_Key_2026';
            }

            return hash_equals($validKey, $apiKey);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Public REST API Endpoint: Synchronize biometric attendance logs via JSON POST.
     * Path: /api/v1/attendance
     */
    public function syncAttendance() {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->validateApiKey()) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode([
                'success' => false,
                'message' => 'Akses API Ditolak: X-API-Key header tidak valid atau tidak disediakan.'
            ]);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);

        if (empty($json) || !is_array($json)) {
            echo json_encode([
                'success' => false,
                'message' => 'Format JSON payload tidak valid. Harap sertakan array objek absensi.'
            ]);
            return;
        }

        // Support both single object and array of objects
        $records = isset($json['records']) ? $json['records'] : (isset($json[0]) ? $json : [$json]);

        $syncedCount = 0;
        $errors = [];

        try {
            $db = $this->getDb();
            $db->beginTransaction();

            $stmtFindUser = $db->prepare("SELECT id FROM users WHERE employee_id = :emp_id OR email = :emp_id OR id = :emp_id LIMIT 1");
            
            $stmtUpsert = $db->prepare("
                INSERT INTO employee_attendance 
                (id, user_id, attendance_date, clock_in, clock_out, status, work_mode, location_method, ip_address, created_at)
                VALUES (UUID(), :user_id, :att_date, :clock_in, :clock_out, :status, :work_mode, 'API_Biometric', :ip, NOW())
                ON DUPLICATE KEY UPDATE
                    clock_in = COALESCE(VALUES(clock_in), clock_in),
                    clock_out = COALESCE(VALUES(clock_out), clock_out),
                    status = VALUES(status),
                    work_mode = VALUES(work_mode),
                    updated_at = NOW()
            ");

            foreach ($records as $idx => $rec) {
                $identifier = trim($rec['user_id'] ?? $rec['employee_id'] ?? $rec['staff_id'] ?? '');
                $attDate = trim($rec['attendance_date'] ?? $rec['date'] ?? date('Y-m-d'));
                $clockIn = trim($rec['clock_in'] ?? '') ?: null;
                $clockOut = trim($rec['clock_out'] ?? '') ?: null;
                $status = trim($rec['status'] ?? 'hadir');
                $workMode = trim($rec['work_mode'] ?? 'WFO');

                if (empty($identifier)) {
                    $errors[] = "Record #$idx: Identitas karyawan (user_id/employee_id) wajib diisi.";
                    continue;
                }

                // Resolve user_id
                $stmtFindUser->execute(['emp_id' => $identifier]);
                $targetUserId = $stmtFindUser->fetchColumn();

                if (!$targetUserId) {
                    $errors[] = "Record #$idx: Karyawan dengan ID '$identifier' tidak ditemukan.";
                    continue;
                }

                $stmtUpsert->execute([
                    'user_id' => $targetUserId,
                    'att_date' => $attDate,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'status' => $status,
                    'work_mode' => $workMode,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);

                $syncedCount++;
            }

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Sinkronisasi presensi biometrik berhasil. $syncedCount rekaman diperbarui.",
                'synced_count' => $syncedCount,
                'errors' => $errors
            ]);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyinkronkan API presensi: ' . $e->getMessage()
            ]);
        }
    }
}
