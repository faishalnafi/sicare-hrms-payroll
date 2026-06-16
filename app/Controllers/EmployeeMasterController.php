<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class EmployeeMasterController {
    
    private function checkAccess() {
        // Safe session start: only start if not already active
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
        $allowed = in_array($role, ['hr_ops', 'superadmin', 'admin', 'hiring_manager']);
        if (!$allowed) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
    }

    /**
     * Get all descendant department IDs for a given department (inclusive)
     */
    private function getDepartmentDescendants(PDO $db, string $deptId): array {
        $stmt = $db->query("SELECT id, parent_id FROM departments");
        $allDeps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build children map
        $childrenMap = [];
        foreach ($allDeps as $d) {
            if ($d['parent_id']) {
                $childrenMap[$d['parent_id']][] = $d['id'];
            }
        }
        
        // BFS to get all descendants
        $result = [$deptId];
        $queue  = [$deptId];
        while (!empty($queue)) {
            $current = array_shift($queue);
            $children = $childrenMap[$current] ?? [];
            foreach ($children as $child) {
                $result[] = $child;
                $queue[]   = $child;
            }
        }
        return $result;
    }

    public function list() {
        $this->checkAccess();
        header('Content-Type: application/json');
        
        try {
            $db   = Database::getInstance()->getConnection();
            $role = $_SESSION['role'];

            if ($role === 'hiring_manager') {
                // hiring_manager: ONLY see users in their division + all descendants
                $myDeptId = null;
                $stmt     = $db->prepare("SELECT department_id FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $myDeptId = $row['department_id'] ?? null;

                if (!$myDeptId) {
                    // hiring_manager without a department cannot see anyone
                    echo json_encode(['success' => true, 'data' => [], 'departments' => []]);
                    return;
                }

                // Get all descendant dept IDs (inclusive)
                $deptIds = $this->getDepartmentDescendants($db, $myDeptId);
                $placeholders = implode(',', array_fill(0, count($deptIds), '?'));

                // Get users in those departments (excluding superadmin and admin for privacy)
                $stmt = $db->prepare("
                    SELECT u.id, u.employee_id, u.first_name, u.last_name, u.email, u.role,
                           u.job_title, u.base_salary, u.annual_leave_quota, u.profile_picture,
                           u.department_id, d.name AS department_name
                    FROM users u
                    LEFT JOIN departments d ON u.department_id = d.id
                    WHERE u.department_id IN ($placeholders)
                      AND u.role NOT IN ('superadmin', 'admin', 'executive', 'candidate')
                    ORDER BY u.first_name ASC
                ");
                $stmt->execute($deptIds);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            } else {
                // admin, hr_ops, superadmin: see ALL users (no division restriction)
                $stmt = $db->query("
                    SELECT u.id, u.employee_id, u.first_name, u.last_name, u.email, u.role,
                           u.job_title, u.base_salary, u.annual_leave_quota, u.profile_picture,
                           u.department_id, d.name AS department_name
                    FROM users u
                    LEFT JOIN departments d ON u.department_id = d.id
                    ORDER BY u.created_at DESC
                ");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Format empty profile pictures to use direct Gravatar 404 for fallback
            foreach ($users as &$u) {
                if (empty($u['profile_picture'])) {
                    $h = md5(strtolower(trim($u['email'])));
                    $u['profile_picture'] = "https://www.gravatar.com/avatar/{$h}?d=404&s=200";
                }
            }
            
            // Fetch departments for dropdown usage
            $stmtDep = $db->query("SELECT id, name, level, parent_id, reimbursement_limit, limit_medis, limit_transport, limit_operasional, limit_makan FROM departments ORDER BY level ASC, name ASC");
            $departments = $stmtDep->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $users, 'departments' => $departments]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function save() {
        $this->checkAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $id                = $_POST['id'] ?? '';
        $employee_id       = $_POST['employee_id'] ?? null;
        $first_name        = $_POST['first_name'] ?? '';
        $last_name         = $_POST['last_name'] ?? '';
        $email             = $_POST['email'] ?? '';
        $role              = $_POST['role'] ?? 'employee';
        $job_title         = $_POST['job_title'] ?? null;
        $department_id     = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
        $rawSalary = $_POST['base_salary'] ?? '0';
        $rawSalary = preg_replace('/^[Rr][Pp]\.?\s*/', '', trim($rawSalary));
        if (strpos($rawSalary, '.') !== false && strpos($rawSalary, ',') !== false) {
            if (strrpos($rawSalary, ',') > strrpos($rawSalary, '.')) {
                $rawSalary = str_replace('.', '', $rawSalary);
                $rawSalary = str_replace(',', '.', $rawSalary);
            } else {
                $rawSalary = str_replace(',', '', $rawSalary);
            }
        } else {
            if (strpos($rawSalary, ',') !== false) {
                if (preg_match('/,\d{2}$/', $rawSalary)) {
                    $rawSalary = str_replace(',', '.', $rawSalary);
                } else {
                    $rawSalary = str_replace(',', '', $rawSalary);
                }
            }
            if (strpos($rawSalary, '.') !== false) {
                if (substr_count($rawSalary, '.') === 1 && preg_match('/\.\d{1,2}$/', $rawSalary)) {
                    // keep dot as decimal
                } else {
                    $rawSalary = str_replace('.', '', $rawSalary);
                }
            }
        }
        $base_salary        = (float)$rawSalary;
        $annual_leave_quota = (int)($_POST['annual_leave_quota'] ?? 12);
        $password          = $_POST['password'] ?? '';

        if (!empty($password)) {
            $pwError = '';
            if (!validate_password_strength($password, $pwError)) {
                echo json_encode(['success' => false, 'message' => $pwError]);
                return;
            }
        }

        // admin cannot promote to superadmin unless they are superadmin
        $currentRole = $_SESSION['role'];
        if ($role === 'superadmin' && $currentRole !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Tidak memiliki izin mengatur role superadmin.']);
            return;
        }

        if (empty($first_name) || empty($email) || empty($role)) {
            echo json_encode(['success' => false, 'message' => 'Nama depan, email, dan role wajib diisi.']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            if (empty($id)) {
                // INSERT NEW
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $password_hash = password_hash(!empty($password) ? $password : 'SiCare@2026!', PASSWORD_DEFAULT);

                $stmt = $db->prepare("INSERT INTO users (id, employee_id, first_name, last_name, email, role, job_title, base_salary, annual_leave_quota, password_hash, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$uuid, $employee_id, $first_name, $last_name, $email, $role, $job_title, $base_salary, $annual_leave_quota, $password_hash, $department_id]);
                $message = 'Data karyawan baru berhasil disimpan.';
            } else {
                // UPDATE EXISTING
                $currentUserRole = $_SESSION['role'] ?? 'hr_ops';
                
                if ($currentUserRole === 'superadmin') {
                    // superadmin updates directly
                    $query  = "UPDATE users SET employee_id = ?, first_name = ?, last_name = ?, email = ?, role = ?, job_title = ?, base_salary = ?, annual_leave_quota = ?, department_id = ? WHERE id = ?";
                    $params = [$employee_id, $first_name, $last_name, $email, $role, $job_title, $base_salary, $annual_leave_quota, $department_id, $id];

                    if (!empty($password)) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $query  = "UPDATE users SET employee_id = ?, first_name = ?, last_name = ?, email = ?, role = ?, job_title = ?, base_salary = ?, annual_leave_quota = ?, password_hash = ?, department_id = ? WHERE id = ?";
                        $params = [$employee_id, $first_name, $last_name, $email, $role, $job_title, $base_salary, $annual_leave_quota, $password_hash, $department_id, $id];
                    }

                    $stmt = $db->prepare($query);
                    $stmt->execute($params);
                    $message = 'Data karyawan berhasil disimpan.';
                } else {
                    // admin or hr_ops: check if role, department_id, or job_title changed
                    $stmtCheck = $db->prepare("SELECT role, department_id, job_title FROM users WHERE id = ?");
                    $stmtCheck->execute([$id]);
                    $existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                    if (!$existingUser) {
                        throw new \Exception("Karyawan tidak ditemukan.");
                    }

                    $roleChanged = $role !== $existingUser['role'];
                    
                    $existingDept = $existingUser['department_id'] !== null ? (string)$existingUser['department_id'] : '';
                    $newDept = $department_id !== null ? (string)$department_id : '';
                    $deptChanged = $newDept !== $existingDept;

                    $existingTitle = $existingUser['job_title'] !== null ? (string)$existingUser['job_title'] : '';
                    $newTitle = $job_title !== null ? (string)$job_title : '';
                    $titleChanged = $newTitle !== $existingTitle;

                    if ($roleChanged || $deptChanged || $titleChanged) {
                        // Position or system role has changed, create approval request
                        $currDeptName = 'Tanpa Departemen';
                        if (!empty($existingUser['department_id'])) {
                            $s = $db->prepare("SELECT name FROM departments WHERE id = ?");
                            $s->execute([$existingUser['department_id']]);
                            $currDeptName = $s->fetchColumn() ?: 'Tanpa Departemen';
                        }

                        $newDeptName = 'Tanpa Departemen';
                        if (!empty($department_id)) {
                            $s = $db->prepare("SELECT name FROM departments WHERE id = ?");
                            $s->execute([$department_id]);
                            $newDeptName = $s->fetchColumn() ?: 'Tanpa Departemen';
                        }

                        $newDataJson = json_encode([
                            'old' => [
                                'role' => $existingUser['role'],
                                'department_id' => $existingUser['department_id'],
                                'department_name' => $currDeptName,
                                'job_title' => $existingUser['job_title']
                            ],
                            'new' => [
                                'role' => $role,
                                'department_id' => $department_id,
                                'department_name' => $newDeptName,
                                'job_title' => $job_title
                            ]
                        ]);

                        $reqUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );

                        $stmtInsertReq = $db->prepare("
                            INSERT INTO approval_requests (id, requester_id, target_user_id, action_type, new_data, status) 
                            VALUES (?, ?, ?, 'MUTATION', ?, 'PENDING')
                        ");
                        $stmtInsertReq->execute([$reqUuid, $_SESSION['user_id'], $id, $newDataJson]);

                        // Save other fields
                        $query  = "UPDATE users SET employee_id = ?, first_name = ?, last_name = ?, email = ?, base_salary = ?, annual_leave_quota = ? WHERE id = ?";
                        $params = [$employee_id, $first_name, $last_name, $email, $base_salary, $annual_leave_quota, $id];

                        if (!empty($password)) {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $query  = "UPDATE users SET employee_id = ?, first_name = ?, last_name = ?, email = ?, base_salary = ?, annual_leave_quota = ?, password_hash = ? WHERE id = ?";
                            $params = [$employee_id, $first_name, $last_name, $email, $base_salary, $annual_leave_quota, $password_hash, $id];
                        }

                        $stmt = $db->prepare($query);
                        $stmt->execute($params);
                        $message = 'Profil dasar berhasil disimpan. Pengajuan mutasi jabatan/role dikirim ke Eksekutif untuk persetujuan.';
                    } else {
                        // No changes to position, save normally
                        $query  = "UPDATE users SET employee_id = ?, first_name = ?, last_name = ?, email = ?, base_salary = ?, annual_leave_quota = ? WHERE id = ?";
                        $params = [$employee_id, $first_name, $last_name, $email, $base_salary, $annual_leave_quota, $id];

                        if (!empty($password)) {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $query  = "UPDATE users SET employee_id = ?, first_name = ?, last_name = ?, email = ?, base_salary = ?, annual_leave_quota = ?, password_hash = ? WHERE id = ?";
                            $params = [$employee_id, $first_name, $last_name, $email, $base_salary, $annual_leave_quota, $password_hash, $id];
                        }

                        $stmt = $db->prepare($query);
                        $stmt->execute($params);
                        $message = 'Data karyawan berhasil disimpan.';
                    }
                }
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }

    public function delete() {
        $this->checkAccess();
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID is required']);
            return;
        }

        // Protect superadmin from deletion by non-superadmin
        if ($_SESSION['role'] !== 'superadmin') {
            $stmt = $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($target && $target['role'] === 'superadmin') {
                echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus akun superadmin.']);
                return;
            }
        }

        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Data karyawan berhasil dihapus.']);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
    }

    public function resetProfileToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'superadmin') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Super Admin yang diizinkan melakukan tindakan ini.']);
            exit;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'CSRF Token tidak valid. Mohon muat ulang halaman.']);
            return;
        }

        $userId = $_POST['user_id'] ?? '';
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'ID pengguna wajib diisi.']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if user exists
            $stmt = $db->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
                return;
            }

            // Generate secure token
            $token = bin2hex(random_bytes(32));

            // Reset the locked administrative fields
            $stmtReset = $db->prepare("
                UPDATE users 
                SET nama_sesuai_ktp = NULL, 
                    ktp_nik = NULL, 
                    alamat_ktp = NULL, 
                    bank_name = NULL, 
                    bank_account_number = NULL, 
                    npwp_number = NULL, 
                    bpjs_tk = NULL, 
                    bpjs_kes = NULL, 
                    tanggal_lahir = NULL, 
                    status_pernikahan = NULL, 
                    jenis_kelamin = NULL,
                    profile_reset_token = ?
                WHERE id = ?
            ");
            $stmtReset->execute([$token, $userId]);

            // Construct link
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
            $resetLink = "{$protocol}://{$host}/employee/profile/fill?token={$token}";

            echo json_encode([
                'success' => true, 
                'message' => 'Profil berhasil di-reset. Bagikan tautan berikut ke pengguna.',
                'link' => $resetLink
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function saveProfileFill() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $token = $_POST['token'] ?? '';

        if (empty($token)) {
            echo json_encode(['success' => false, 'message' => 'Token pengisian tidak valid.']);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'CSRF Token tidak valid. Mohon muat ulang halaman.']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            // Verify token
            $stmt = $db->prepare("SELECT id FROM users WHERE profile_reset_token = :token LIMIT 1");
            $stmt->execute(['token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['id'] !== $userId) {
                echo json_encode(['success' => false, 'message' => 'Token pengisian tidak valid atau tidak cocok dengan akun Anda.']);
                return;
            }

            // Extract form inputs
            $nama_sesuai_ktp     = $_POST['nama_sesuai_ktp'] ?? '';
            $ktp_nik             = $_POST['ktp_nik'] ?? '';
            $alamat_ktp          = $_POST['alamat_ktp'] ?? '';
            $tanggal_lahir       = $_POST['tanggal_lahir'] ?? '';
            $jenis_kelamin       = $_POST['jenis_kelamin'] ?? '';
            $status_pernikahan   = $_POST['status_pernikahan'] ?? '';
            $bank_name           = $_POST['bank_name'] ?? '';
            $bank_account_number = $_POST['bank_account_number'] ?? '';
            $npwp_number         = $_POST['npwp_number'] ?? '';
            $bpjs_tk             = $_POST['bpjs_tk'] ?? '';
            $bpjs_kes            = $_POST['bpjs_kes'] ?? '';
            $no_telepon          = $_POST['no_telepon'] ?? '';
            $alamat_domisili     = $_POST['alamat_domisili'] ?? '';

            // Validation of mandatory fields
            if (empty($nama_sesuai_ktp) || empty($ktp_nik) || empty($alamat_ktp) || empty($tanggal_lahir) || empty($jenis_kelamin) || empty($status_pernikahan) || empty($bank_name) || empty($bank_account_number) || empty($no_telepon) || empty($alamat_domisili)) {
                echo json_encode(['success' => false, 'message' => 'Mohon lengkapi semua kolom bertanda bintang (*).']);
                return;
            }

            // Update user profile and clear token
            $stmtUpdate = $db->prepare("
                UPDATE users 
                SET nama_sesuai_ktp = ?,
                    ktp_nik = ?,
                    alamat_ktp = ?,
                    tanggal_lahir = ?,
                    jenis_kelamin = ?,
                    status_pernikahan = ?,
                    bank_name = ?,
                    bank_account_number = ?,
                    npwp_number = ?,
                    bpjs_tk = ?,
                    bpjs_kes = ?,
                    no_telepon = ?,
                    alamat_domisili = ?,
                    profile_reset_token = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmtUpdate->execute([
                $nama_sesuai_ktp,
                $ktp_nik,
                $alamat_ktp,
                $tanggal_lahir,
                $jenis_kelamin,
                $status_pernikahan,
                $bank_name,
                $bank_account_number,
                $npwp_number,
                $bpjs_tk,
                $bpjs_kes,
                $no_telepon,
                $alamat_domisili,
                $userId
            ]);

            echo json_encode(['success' => true, 'message' => 'Identitas lengkap Anda berhasil disimpan secara permanen.']);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }
}
