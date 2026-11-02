<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class ImportExportController {

    private function getDb() {
        return Database::getInstance()->getConnection();
    }

    private function checkAuth($allowedRoles = ['admin', 'hr_ops', 'superadmin']) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sesi kedaluwarsa. Silakan masuk kembali.']);
            exit;
        }

        $role = $_SESSION['role'] ?? '';
        $origRole = $_SESSION['original_role'] ?? '';
        $effectiveRole = ($origRole === 'superadmin') ? 'superadmin' : $role;

        if (!in_array($effectiveRole, $allowedRoles) && $effectiveRole !== 'superadmin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
            exit;
        }
    }

    /**
     * Import employee data mass from CSV/Excel (UPSERT logic)
     */
    public function importEmployeesUPSERT() {
        $this->checkAuth(['admin', 'hr_ops', 'superadmin']);
        header('Content-Type: application/json');

        if (!isset($_FILES['file_import']) || $_FILES['file_import']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Mohon pilih berkas CSV/Excel yang akan diimpor.']);
            return;
        }

        $fileTmp = $_FILES['file_import']['tmp_name'];
        $fileName = $_FILES['file_import']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'txt'])) {
            echo json_encode(['success' => false, 'message' => 'Format file yang didukung saat ini adalah CSV (Comma Separated Values).']);
            return;
        }

        $handle = fopen($fileTmp, 'r');
        if (!$handle) {
            echo json_encode(['success' => false, 'message' => 'Gagal membaca isi berkas impor.']);
            return;
        }

        // Read header
        $header = fgetcsv($handle, 2000, ',');
        if (!$header) {
            fclose($handle);
            echo json_encode(['success' => false, 'message' => 'Berkas impor kosong atau format tidak valid.']);
            return;
        }

        // Normalize header columns
        $colMap = [];
        foreach ($header as $idx => $colName) {
            $cleaned = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $colName)));
            $colMap[$cleaned] = $idx;
        }

        $insertedCount = 0;
        $updatedCount = 0;
        $errors = [];
        $lineNum = 1;

        try {
            $db = $this->getDb();
            $db->beginTransaction();

            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                $lineNum++;
                if (count($row) < 2) continue;

                $empId = trim($row[$colMap['employee_id'] ?? $colMap['staff_id'] ?? 0] ?? '');
                $email = trim($row[$colMap['email'] ?? 1] ?? '');
                $firstName = trim($row[$colMap['first_name'] ?? $colMap['nama_depan'] ?? 2] ?? '');
                $lastName = trim($row[$colMap['last_name'] ?? $colMap['nama_belakang'] ?? 3] ?? '');
                $ktpNik = trim($row[$colMap['ktp_nik'] ?? $colMap['nik'] ?? 4] ?? '');
                $deptId = trim($row[$colMap['department_id'] ?? $colMap['divisi'] ?? 5] ?? '');
                $jobTitle = trim($row[$colMap['job_title'] ?? $colMap['jabatan'] ?? 6] ?? '');
                $salary = (float)($row[$colMap['base_salary'] ?? $colMap['gaji'] ?? 7] ?? 0);

                if (empty($email) || empty($firstName)) {
                    $errors[] = "Baris $lineNum: Email & Nama Depan wajib diisi.";
                    continue;
                }

                // Check existing user by employee_id or email
                $stmtCheck = $db->prepare("SELECT id FROM users WHERE (employee_id = :emp_id AND :emp_id != '') OR email = :email LIMIT 1");
                $stmtCheck->execute(['emp_id' => $empId, 'email' => $email]);
                $existingId = $stmtCheck->fetchColumn();

                if ($existingId) {
                    // UPDATE existing user (UPSERT)
                    $stmtUp = $db->prepare("
                        UPDATE users SET
                            first_name = :fname,
                            last_name = :lname,
                            ktp_nik = COALESCE(NULLIF(:nik, ''), ktp_nik),
                            department_id = COALESCE(NULLIF(:dept_id, ''), department_id),
                            job_title = COALESCE(NULLIF(:job_title, ''), job_title),
                            base_salary = CASE WHEN :salary > 0 THEN :salary ELSE base_salary END,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmtUp->execute([
                        'fname' => $firstName,
                        'lname' => $lastName,
                        'nik' => $ktpNik,
                        'dept_id' => $deptId,
                        'job_title' => $jobTitle,
                        'salary' => $salary,
                        'id' => $existingId
                    ]);
                    $updatedCount++;
                } else {
                    // INSERT new employee (UPSERT)
                    $newId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    $dummyPass = password_hash('siCare@2026', PASSWORD_BCRYPT);

                    $stmtIn = $db->prepare("
                        INSERT INTO users (id, employee_id, email, password, first_name, last_name, ktp_nik, role, role_id, department_id, job_title, base_salary, onboarding_status, created_at)
                        VALUES (:id, NULLIF(:emp_id, ''), :email, :pass, :fname, :lname, NULLIF(:nik, ''), 'employee', '3', NULLIF(:dept_id, ''), NULLIF(:job_title, ''), :salary, 'completed', NOW())
                    ");
                    $stmtIn->execute([
                        'id' => $newId,
                        'emp_id' => $empId,
                        'email' => $email,
                        'pass' => $dummyPass,
                        'fname' => $firstName,
                        'lname' => $lastName,
                        'nik' => $ktpNik,
                        'dept_id' => $deptId,
                        'job_title' => $jobTitle,
                        'salary' => $salary
                    ]);
                    $insertedCount++;
                }
            }

            fclose($handle);
            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Import Massal Berhasil! Data Ditambahkan: $insertedCount, Data Diperbarui (UPSERT): $updatedCount.",
                'details' => [
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'errors' => $errors
                ]
            ]);
        } catch (Exception $e) {
            if (isset($handle) && is_resource($handle)) fclose($handle);
            if ($db->inTransaction()) $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal mengimpor data massal: ' . $e->getMessage()]);
        }
    }

    /**
     * Export all active employees to CSV
     */
    public function exportEmployees() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.1 403 Forbidden');
            echo "Access denied.";
            exit;
        }

        try {
            $db = $this->getDb();
            $stmt = $db->query("
                SELECT u.employee_id, u.first_name, u.last_name, u.email, u.ktp_nik, u.no_telepon, 
                       d.name AS department_name, u.job_title, u.base_salary, u.role, u.created_at
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE u.role NOT IN ('candidate') AND (u.is_deleted = 0 OR u.is_deleted IS NULL)
                ORDER BY u.created_at DESC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="siCare_Export_Employees_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Employee ID', 'First Name', 'Last Name', 'Email', 'NIK KTP', 'Phone', 'Department', 'Job Title', 'Base Salary', 'Role', 'Created At']);

            foreach ($rows as $r) {
                fputcsv($output, [
                    $r['employee_id'] ?? '',
                    $r['first_name'] ?? '',
                    $r['last_name'] ?? '',
                    $r['email'] ?? '',
                    $r['ktp_nik'] ?? '',
                    $r['no_telepon'] ?? '',
                    $r['department_name'] ?? '',
                    $r['job_title'] ?? '',
                    $r['base_salary'] ?? 0,
                    $r['role'] ?? '',
                    $r['created_at'] ?? ''
                ]);
            }
            fclose($output);
            exit;
        } catch (Exception $e) {
            echo "Export error: " . $e->getMessage();
            exit;
        }
    }
}
