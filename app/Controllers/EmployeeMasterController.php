<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class EmployeeMasterController {
    
    private function checkAccess(bool $requireCsrf = false) {
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
        if ($requireCsrf) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau kedaluwarsa. Silakan muat ulang halaman.']);
                exit;
            }
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

    private function writeAuditLog(PDO $db, string $action, string $tableName = 'users') {
        $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $stmtLog = $db->prepare("
            INSERT INTO audit_logs (id, user_id, action, table_name, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtLog->execute([$logId, $_SESSION['user_id'] ?? null, $action, $tableName, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
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
                    SELECT u.*, d.name AS department_name,
                           la.attempts AS failed_attempts, la.locked_until AS lockout_time
                    FROM users u
                    LEFT JOIN departments d ON u.department_id = d.id
                    LEFT JOIN login_attempts la ON u.email = la.email
                    WHERE u.department_id IN ($placeholders)
                      AND u.role NOT IN ('superadmin', 'admin', 'executive', 'candidate')
                    ORDER BY u.first_name ASC
                ");
                $stmt->execute($deptIds);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            } else {
                // admin, hr_ops, superadmin: see ALL users (no division restriction)
                $stmt = $db->query("
                    SELECT u.*, d.name AS department_name,
                           la.attempts AS failed_attempts, la.locked_until AS lockout_time
                    FROM users u
                    LEFT JOIN departments d ON u.department_id = d.id
                    LEFT JOIN login_attempts la ON u.email = la.email
                    ORDER BY u.created_at DESC
                ");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Format empty profile pictures to use dynamic cascade logic
            foreach ($users as &$u) {
                if (empty($u['profile_picture'])) {
                    $u['profile_picture'] = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($u['email']))) . "?d=identicon";
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
        $this->checkAccess(true);
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
        
        $base_salary        = $this->cleanAmount($_POST['base_salary'] ?? '0');
        $annual_leave_quota = (int)($_POST['annual_leave_quota'] ?? 12);
        $password          = $_POST['password'] ?? '';

        // Extract new profile fields
        $no_telepon                  = !empty($_POST['no_telepon']) ? $_POST['no_telepon'] : null;
        $alamat_domisili             = !empty($_POST['alamat_domisili']) ? $_POST['alamat_domisili'] : null;
        $ktp_nik                     = !empty($_POST['ktp_nik']) ? $_POST['ktp_nik'] : null;
        $nama_sesuai_ktp             = !empty($_POST['nama_sesuai_ktp']) ? $_POST['nama_sesuai_ktp'] : null;
        $alamat_ktp                  = !empty($_POST['alamat_ktp']) ? $_POST['alamat_ktp'] : null;
        $bank_name                   = !empty($_POST['bank_name']) ? $_POST['bank_name'] : null;
        $bank_account_number         = !empty($_POST['bank_account_number']) ? $_POST['bank_account_number'] : null;
        $npwp_number                 = !empty($_POST['npwp_number']) ? $_POST['npwp_number'] : null;
        $bpjs_tk                     = !empty($_POST['bpjs_tk']) ? $_POST['bpjs_tk'] : null;
        $bpjs_kes                    = !empty($_POST['bpjs_kes']) ? $_POST['bpjs_kes'] : null;
        $tanggal_lahir               = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null;
        $status_pernikahan           = !empty($_POST['status_pernikahan']) ? $_POST['status_pernikahan'] : null;
        $jenis_kelamin               = !empty($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : null;
        $uniform_size                = !empty($_POST['uniform_size']) ? $_POST['uniform_size'] : null;
        $emergency_name              = !empty($_POST['emergency_name']) ? $_POST['emergency_name'] : null;
        $emergency_relation          = !empty($_POST['emergency_relation']) ? $_POST['emergency_relation'] : null;
        $emergency_phone             = !empty($_POST['emergency_phone']) ? $_POST['emergency_phone'] : null;
        $id_kartu                    = !empty($_POST['id_kartu']) ? $_POST['id_kartu'] : null;
        $id_qrcode                   = !empty($_POST['id_qrcode']) ? $_POST['id_qrcode'] : null;

        $reimburse_plafon_medis       = $this->cleanAmount($_POST['reimburse_plafon_medis'] ?? '0');
        $reimburse_plafon_transport   = $this->cleanAmount($_POST['reimburse_plafon_transport'] ?? '0');
        $reimburse_plafon_operasional = $this->cleanAmount($_POST['reimburse_plafon_operasional'] ?? '0');
        $reimburse_plafon_makan       = $this->cleanAmount($_POST['reimburse_plafon_makan'] ?? '0');

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

                $resolvedPic = resolveProfilePicture($email);

                $stmt = $db->prepare("INSERT INTO users (
                    id, employee_id, first_name, last_name, email, role, job_title, base_salary, annual_leave_quota, password_hash, department_id,
                    no_telepon, alamat_domisili, ktp_nik, nama_sesuai_ktp, alamat_ktp, bank_name, bank_account_number, npwp_number, bpjs_tk, bpjs_kes,
                    tanggal_lahir, status_pernikahan, jenis_kelamin, reimburse_plafon_medis, reimburse_plafon_transport, reimburse_plafon_operasional, reimburse_plafon_makan,
                    uniform_size, emergency_name, emergency_relation, emergency_phone, id_kartu, id_qrcode, profile_picture
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?
                )");
                
                $stmt->execute([
                    $uuid, $employee_id, $first_name, $last_name, $email, $role, $job_title, $base_salary, $annual_leave_quota, $password_hash, $department_id,
                    $no_telepon, $alamat_domisili, $ktp_nik, $nama_sesuai_ktp, $alamat_ktp, $bank_name, $bank_account_number, $npwp_number, $bpjs_tk, $bpjs_kes,
                    $tanggal_lahir, $status_pernikahan, $jenis_kelamin, $reimburse_plafon_medis, $reimburse_plafon_transport, $reimburse_plafon_operasional, $reimburse_plafon_makan,
                    $uniform_size, $emergency_name, $emergency_relation, $emergency_phone, $id_kartu, $id_qrcode, $resolvedPic
                ]);
                $this->writeAuditLog($db, "Menambah pengguna baru: '" . trim($first_name . ' ' . $last_name) . "' ($email) dengan role '$role'");
            } else {
                // UPDATE EXISTING
                try {
                    $stmtOld = $db->prepare("SELECT email, profile_picture FROM users WHERE id = ?");
                    $stmtOld->execute([$id]);
                    $oldUser = $stmtOld->fetch(PDO::FETCH_ASSOC);
                    if ($oldUser) {
                        $currentPic = $oldUser['profile_picture'];
                        $isGoogle = $currentPic && str_contains($currentPic, 'googleusercontent.com');
                        if (!$isGoogle) {
                            $newPic = resolveProfilePicture($email);
                            if ($newPic !== $currentPic) {
                                $stmtPic = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                                $stmtPic->execute([$newPic, $id]);
                            }
                        }
                    }
                } catch (\Exception $e) {}

                $currentUserRole = $_SESSION['role'] ?? 'hr_ops';
                
                if ($currentUserRole === 'superadmin') {
                    // superadmin updates directly
                    $query  = "UPDATE users SET 
                        employee_id = ?, first_name = ?, last_name = ?, email = ?, role = ?, job_title = ?, base_salary = ?, annual_leave_quota = ?, department_id = ?,
                        no_telepon = ?, alamat_domisili = ?, ktp_nik = ?, nama_sesuai_ktp = ?, alamat_ktp = ?, bank_name = ?, bank_account_number = ?, npwp_number = ?, bpjs_tk = ?, bpjs_kes = ?, 
                        tanggal_lahir = ?, status_pernikahan = ?, jenis_kelamin = ?, reimburse_plafon_medis = ?, reimburse_plafon_transport = ?, reimburse_plafon_operasional = ?, reimburse_plafon_makan = ?,
                        uniform_size = ?, emergency_name = ?, emergency_relation = ?, emergency_phone = ?, id_kartu = ?, id_qrcode = ?
                        WHERE id = ?";
                    $params = [
                        $employee_id, $first_name, $last_name, $email, $role, $job_title, $base_salary, $annual_leave_quota, $department_id,
                        $no_telepon, $alamat_domisili, $ktp_nik, $nama_sesuai_ktp, $alamat_ktp, $bank_name, $bank_account_number, $npwp_number, $bpjs_tk, $bpjs_kes,
                        $tanggal_lahir, $status_pernikahan, $jenis_kelamin, $reimburse_plafon_medis, $reimburse_plafon_transport, $reimburse_plafon_operasional, $reimburse_plafon_makan,
                        $uniform_size, $emergency_name, $emergency_relation, $emergency_phone, $id_kartu, $id_qrcode,
                        $id
                    ];

                    if (!empty($password)) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $query  = "UPDATE users SET 
                            employee_id = ?, first_name = ?, last_name = ?, email = ?, role = ?, job_title = ?, base_salary = ?, annual_leave_quota = ?, department_id = ?, password_hash = ?,
                            no_telepon = ?, alamat_domisili = ?, ktp_nik = ?, nama_sesuai_ktp = ?, alamat_ktp = ?, bank_name = ?, bank_account_number = ?, npwp_number = ?, bpjs_tk = ?, bpjs_kes = ?, 
                            tanggal_lahir = ?, status_pernikahan = ?, jenis_kelamin = ?, reimburse_plafon_medis = ?, reimburse_plafon_transport = ?, reimburse_plafon_operasional = ?, reimburse_plafon_makan = ?,
                            uniform_size = ?, emergency_name = ?, emergency_relation = ?, emergency_phone = ?, id_kartu = ?, id_qrcode = ?
                            WHERE id = ?";
                        $params = [
                            $employee_id, $first_name, $last_name, $email, $role, $job_title, $base_salary, $annual_leave_quota, $department_id, $password_hash,
                            $no_telepon, $alamat_domisili, $ktp_nik, $nama_sesuai_ktp, $alamat_ktp, $bank_name, $bank_account_number, $npwp_number, $bpjs_tk, $bpjs_kes,
                            $tanggal_lahir, $status_pernikahan, $jenis_kelamin, $reimburse_plafon_medis, $reimburse_plafon_transport, $reimburse_plafon_operasional, $reimburse_plafon_makan,
                            $uniform_size, $emergency_name, $emergency_relation, $emergency_phone, $id_kartu, $id_qrcode,
                            $id
                        ];
                    }

                    $stmt = $db->prepare($query);
                    $stmt->execute($params);
                    $this->writeAuditLog($db, "Mengubah data pengguna: '" . trim($first_name . ' ' . $last_name) . "' ($email) (ID: $id)");
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

                        // Save other fields directly
                        $query  = "UPDATE users SET 
                            employee_id = ?, first_name = ?, last_name = ?, email = ?, base_salary = ?, annual_leave_quota = ?,
                            no_telepon = ?, alamat_domisili = ?, ktp_nik = ?, nama_sesuai_ktp = ?, alamat_ktp = ?, bank_name = ?, bank_account_number = ?, npwp_number = ?, bpjs_tk = ?, bpjs_kes = ?, 
                            tanggal_lahir = ?, status_pernikahan = ?, jenis_kelamin = ?, reimburse_plafon_medis = ?, reimburse_plafon_transport = ?, reimburse_plafon_operasional = ?, reimburse_plafon_makan = ?,
                            uniform_size = ?, emergency_name = ?, emergency_relation = ?, emergency_phone = ?, id_kartu = ?, id_qrcode = ?
                            WHERE id = ?";
                        $params = [
                            $employee_id, $first_name, $last_name, $email, $base_salary, $annual_leave_quota,
                            $no_telepon, $alamat_domisili, $ktp_nik, $nama_sesuai_ktp, $alamat_ktp, $bank_name, $bank_account_number, $npwp_number, $bpjs_tk, $bpjs_kes,
                            $tanggal_lahir, $status_pernikahan, $jenis_kelamin, $reimburse_plafon_medis, $reimburse_plafon_transport, $reimburse_plafon_operasional, $reimburse_plafon_makan,
                            $uniform_size, $emergency_name, $emergency_relation, $emergency_phone, $id_kartu, $id_qrcode,
                            $id
                        ];

                        if (!empty($password)) {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $query  = "UPDATE users SET 
                                employee_id = ?, first_name = ?, last_name = ?, email = ?, base_salary = ?, annual_leave_quota = ?, password_hash = ?,
                                no_telepon = ?, alamat_domisili = ?, ktp_nik = ?, nama_sesuai_ktp = ?, alamat_ktp = ?, bank_name = ?, bank_account_number = ?, npwp_number = ?, bpjs_tk = ?, bpjs_kes = ?, 
                                tanggal_lahir = ?, status_pernikahan = ?, jenis_kelamin = ?, reimburse_plafon_medis = ?, reimburse_plafon_transport = ?, reimburse_plafon_operasional = ?, reimburse_plafon_makan = ?,
                                uniform_size = ?, emergency_name = ?, emergency_relation = ?, emergency_phone = ?, id_kartu = ?, id_qrcode = ?
                                WHERE id = ?";
                            $params = [
                                $employee_id, $first_name, $last_name, $email, $base_salary, $annual_leave_quota, $password_hash,
                                $no_telepon, $alamat_domisili, $ktp_nik, $nama_sesuai_ktp, $alamat_ktp, $bank_name, $bank_account_number, $npwp_number, $bpjs_tk, $bpjs_kes,
                                $tanggal_lahir, $status_pernikahan, $jenis_kelamin, $reimburse_plafon_medis, $reimburse_plafon_transport, $reimburse_plafon_operasional, $reimburse_plafon_makan,
                                $uniform_size, $emergency_name, $emergency_relation, $emergency_phone, $id_kartu, $id_qrcode,
                                $id
                            ];
                        }

                        $stmt = $db->prepare($query);
                        $stmt->execute($params);
                        $this->writeAuditLog($db, "Mengajukan mutasi jabatan/role untuk pengguna: '" . trim($first_name . ' ' . $last_name) . "' ($email) (ID: $id) dan memperbarui profil dasar");
                        $message = 'Profil dasar berhasil disimpan. Pengajuan mutasi jabatan/role dikirim ke Eksekutif untuk persetujuan.';
                    } else {
                        // No changes to position, save normally
                        $query  = "UPDATE users SET 
                            employee_id = ?, first_name = ?, last_name = ?, email = ?, base_salary = ?, annual_leave_quota = ?,
                            no_telepon = ?, alamat_domisili = ?, ktp_nik = ?, nama_sesuai_ktp = ?, alamat_ktp = ?, bank_name = ?, bank_account_number = ?, npwp_number = ?, bpjs_tk = ?, bpjs_kes = ?, 
                            tanggal_lahir = ?, status_pernikahan = ?, jenis_kelamin = ?, reimburse_plafon_medis = ?, reimburse_plafon_transport = ?, reimburse_plafon_operasional = ?, reimburse_plafon_makan = ?,
                            uniform_size = ?, emergency_name = ?, emergency_relation = ?, emergency_phone = ?, id_kartu = ?, id_qrcode = ?
                            WHERE id = ?";
                        $params = [
                            $employee_id, $first_name, $last_name, $email, $base_salary, $annual_leave_quota,
                            $no_telepon, $alamat_domisili, $ktp_nik, $nama_sesuai_ktp, $alamat_ktp, $bank_name, $bank_account_number, $npwp_number, $bpjs_tk, $bpjs_kes,
                            $tanggal_lahir, $status_pernikahan, $jenis_kelamin, $reimburse_plafon_medis, $reimburse_plafon_transport, $reimburse_plafon_operasional, $reimburse_plafon_makan,
                            $uniform_size, $emergency_name, $emergency_relation, $emergency_phone, $id_kartu, $id_qrcode,
                            $id
                        ];

                        if (!empty($password)) {
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $query  = "UPDATE users SET 
                                employee_id = ?, first_name = ?, last_name = ?, email = ?, base_salary = ?, annual_leave_quota = ?, password_hash = ?,
                                no_telepon = ?, alamat_domisili = ?, ktp_nik = ?, nama_sesuai_ktp = ?, alamat_ktp = ?, bank_name = ?, bank_account_number = ?, npwp_number = ?, bpjs_tk = ?, bpjs_kes = ?, 
                                tanggal_lahir = ?, status_pernikahan = ?, jenis_kelamin = ?, reimburse_plafon_medis = ?, reimburse_plafon_transport = ?, reimburse_plafon_operasional = ?, reimburse_plafon_makan = ?,
                                uniform_size = ?, emergency_name = ?, emergency_relation = ?, emergency_phone = ?, id_kartu = ?, id_qrcode = ?
                                WHERE id = ?";
                            $params = [
                                $employee_id, $first_name, $last_name, $email, $base_salary, $annual_leave_quota, $password_hash,
                                $no_telepon, $alamat_domisili, $ktp_nik, $nama_sesuai_ktp, $alamat_ktp, $bank_name, $bank_account_number, $npwp_number, $bpjs_tk, $bpjs_kes,
                                $tanggal_lahir, $status_pernikahan, $jenis_kelamin, $reimburse_plafon_medis, $reimburse_plafon_transport, $reimburse_plafon_operasional, $reimburse_plafon_makan,
                                $uniform_size, $emergency_name, $emergency_relation, $emergency_phone, $id_kartu, $id_qrcode,
                                $id
                            ];
                        }

                        $stmt = $db->prepare($query);
                        $stmt->execute($params);
                        $this->writeAuditLog($db, "Mengubah data profil dasar pengguna: '" . trim($first_name . ' ' . $last_name) . "' ($email) (ID: $id)");
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
        $this->checkAccess(true);
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID is required']);
            return;
        }

        // Prevent self deletion
        if ($id === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.']);
            return;
        }

        // Protect superadmin from deletion by non-superadmin
        if ($_SESSION['role'] !== 'superadmin') {
            $db = Database::getInstance()->getConnection();
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
            
            // Fetch name & email for log
            $stmtUser = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
            $stmtUser->execute([$id]);
            $uinfo = $stmtUser->fetch(PDO::FETCH_ASSOC);
            $targetName = $uinfo ? trim($uinfo['first_name'] . ' ' . $uinfo['last_name']) : 'Tidak Dikenal';
            $targetEmail = $uinfo ? $uinfo['email'] : '';

            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            $actionText = "Memindahkan pengguna ke tempat sampah (Soft Delete): '$targetName' ($targetEmail) (ID: $id)";
            $this->writeAuditLog($db, $actionText);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dipindahkan ke tempat sampah.']);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
    }

    public function deletePermanent() {
        $this->checkAccess(true);
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID is required']);
            return;
        }

        // Prevent self deletion
        if ($id === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
            return;
        }

        // Protect superadmin from deletion by non-superadmin
        if ($_SESSION['role'] !== 'superadmin') {
            $db = Database::getInstance()->getConnection();
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
            
            // Fetch name & email for log
            $stmtUser = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
            $stmtUser->execute([$id]);
            $uinfo = $stmtUser->fetch(PDO::FETCH_ASSOC);
            $targetName = $uinfo ? trim($uinfo['first_name'] . ' ' . $uinfo['last_name']) : 'Tidak Dikenal';
            $targetEmail = $uinfo ? $uinfo['email'] : '';

            $db->beginTransaction();
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            $actionText = "Menghapus pengguna secara permanen dari database: '$targetName' ($targetEmail) (ID: $id)";
            $this->writeAuditLog($db, $actionText);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Akun berhasil dihapus secara permanen.']);
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus data permanen: ' . $e->getMessage()]);
        }
    }

    public function restore() {
        $this->checkAccess(true);
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID is required']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            // Fetch name & email for log
            $stmtUser = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
            $stmtUser->execute([$id]);
            $uinfo = $stmtUser->fetch(PDO::FETCH_ASSOC);
            $targetName = $uinfo ? trim($uinfo['first_name'] . ' ' . $uinfo['last_name']) : 'Tidak Dikenal';
            $targetEmail = $uinfo ? $uinfo['email'] : '';

            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE users SET is_deleted = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            $actionText = "Memulihkan pengguna dari tempat sampah: '$targetName' ($targetEmail) (ID: $id)";
            $this->writeAuditLog($db, $actionText);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Akun berhasil dipulihkan.']);
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal memulihkan data: ' . $e->getMessage()]);
        }
    }

    public function toggleSuspend() {
        $this->checkAccess(true);
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID is required']);
            return;
        }

        // Prevent self suspension
        if ($id === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak dapat menangguhkan (suspend) akun Anda sendiri.']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            // Get current user details
            $stmt = $db->prepare("SELECT role, is_suspended, first_name, last_name, email FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
                return;
            }

            // Protect superadmin
            if ($user['role'] === 'superadmin' && $_SESSION['role'] !== 'superadmin') {
                echo json_encode(['success' => false, 'message' => 'Hanya Superadmin yang dapat menangguhkan sesama Superadmin.']);
                return;
            }

            $newStatus = $user['is_suspended'] ? 0 : 1;
            
            $db->beginTransaction();
            $stmtUpdate = $db->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $id]);

            $fullName = trim($user['first_name'] . ' ' . $user['last_name']);
            $actionText = $newStatus ? 'ditangguhkan (suspend)' : 'diaktifkan kembali';
            $logAction = ($newStatus ? "Menangguhkan (suspend) akun" : "Mengaktifkan kembali akun") . ": '$fullName' ({$user['email']}) (ID: $id)";
            $this->writeAuditLog($db, $logAction);
            $db->commit();

            $message = "Akun \"{$fullName}\" berhasil {$actionText}.";

            echo json_encode(['success' => true, 'message' => $message, 'is_suspended' => $newStatus]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengubah status akses: ' . $e->getMessage()]);
        }
    }

    public function resetLockout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['role'] ?? '';
        if (!isset($_SESSION['user_id']) || !in_array($role, ['superadmin', 'admin'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Admin atau Super Admin yang diizinkan melakukan tindakan ini.']);
            exit;
        }

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid. Mohon muat ulang halaman.']);
            return;
        }

        $userId = $_POST['user_id'] ?? '';
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'ID pengguna wajib diisi.']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if user exists and get email
            $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $email = $stmt->fetchColumn();
            
            if (!$email) {
                echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
                return;
            }

            $db->beginTransaction();
            // Delete failed attempts record
            $stmtDel = $db->prepare("DELETE FROM login_attempts WHERE email = ?");
            $stmtDel->execute([$email]);

            // Fetch name of the user
            $stmtName = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
            $stmtName->execute([$userId]);
            $uinfo = $stmtName->fetch(PDO::FETCH_ASSOC);
            $fullName = $uinfo ? trim($uinfo['first_name'] . ' ' . $uinfo['last_name']) : 'Tidak Dikenal';

            $actionText = "Mereset blokir login (failed attempts) untuk pengguna: '$fullName' ($email) (ID: $userId)";
            $this->writeAuditLog($db, $actionText);
            $db->commit();

            echo json_encode(['success' => true, 'message' => 'Blokir login berhasil direset.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mereset blokir: ' . $e->getMessage()]);
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

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
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
            $db->beginTransaction();
            $stmtReset->execute([$token, $userId]);

            $targetName = trim($user['first_name'] . ' ' . $user['last_name']);
            $actionText = "Mereset kolom administratif dan membuat tautan pembaruan profil mandiri untuk: '$targetName' ({$user['email']}) (ID: $userId)";
            $this->writeAuditLog($db, $actionText);
            $db->commit();

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

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
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

    private function cleanAmount($val): float {
        $val = preg_replace('/^[Rr][Pp]\.?\s*/', '', trim($val ?? '0'));
        if (strpos($val, '.') !== false && strpos($val, ',') !== false) {
            if (strrpos($val, ',') > strrpos($val, '.')) {
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
            } else {
                $val = str_replace(',', '', $val);
            }
        } else {
            if (strpos($val, ',') !== false) {
                if (preg_match('/,\d{2}$/', $val)) {
                    $val = str_replace(',', '.', $val);
                } else {
                    $val = str_replace(',', '', $val);
                }
            }
            if (strpos($val, '.') !== false) {
                if (substr_count($val, '.') === 1 && preg_match('/\.\d{1,2}$/', $val)) {
                    // keep dot as decimal
                } else {
                    $val = str_replace('.', '', $val);
                }
            }
        }
        return (float)$val;
    }

    public function emptyTrash() {
        $this->checkAccess(true);
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            // Get list of all soft-deleted users for logging
            $stmt = $db->query("SELECT id, first_name, last_name, email FROM users WHERE is_deleted = 1");
            $suspendedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($suspendedUsers)) {
                echo json_encode(['success' => true, 'message' => 'Trash sudah kosong.']);
                return;
            }
            
            $db->beginTransaction();
            
            // Delete them
            $stmtDel = $db->prepare("DELETE FROM users WHERE is_deleted = 1");
            $stmtDel->execute();
            
            // Log all of them
            foreach ($suspendedUsers as $su) {
                $targetName = trim($su['first_name'] . ' ' . $su['last_name']);
                $actionText = "Mengosongkan tempat sampah (Hapus permanen): '$targetName' ({$su['email']}) (ID: {$su['id']})";
                $this->writeAuditLog($db, $actionText);
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Berhasil mengosongkan tempat sampah.']);
        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal mengosongkan tempat sampah: ' . $e->getMessage()]);
        }
    }
}

