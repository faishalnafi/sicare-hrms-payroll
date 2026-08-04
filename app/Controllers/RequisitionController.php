<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class RequisitionController {

    private function getDb() {
        return Database::getInstance()->getConnection();
    }

    private function checkAuth($allowedRoles = [], bool $requireCsrf = false) {
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

        if (!empty($allowedRoles) && !in_array($effectiveRole, $allowedRoles) && $effectiveRole !== 'superadmin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda tidak memiliki wewenang untuk aksi ini.']);
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

        return $effectiveRole;
    }

    /**
     * Store new Job Requisition (Submitted by Hiring Manager)
     */
    public function store() {
        $role = $this->checkAuth(['hiring_manager', 'superadmin', 'executive'], true);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $userId = $_SESSION['user_id'];
        
        $title = trim($_POST['title'] ?? '');
        $departmentId = trim($_POST['department_id'] ?? '');
        $headcount = (int)($_POST['headcount'] ?? 1);
        $employmentType = trim($_POST['employment_type'] ?? 'full_time');
        $minSalary = (float)($_POST['min_salary'] ?? 0);
        $maxSalary = (float)($_POST['max_salary'] ?? 0);
        $jobDescription = trim($_POST['job_description'] ?? '');
        $requirements = trim($_POST['requirements'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if (empty($title) || empty($jobDescription) || $headcount < 1) {
            echo json_encode(['success' => false, 'message' => 'Judul posisi, deskripsi pekerjaan, dan jumlah staf wajib diisi.']);
            return;
        }

        // If not superadmin, strictly enforce user's current department
        if ($role !== 'superadmin' && !empty($_SESSION['department_id'])) {
            $departmentId = $_SESSION['department_id'];
        } elseif (empty($departmentId)) {
            $departmentId = $_SESSION['department_id'] ?? null;
        }

        if (empty($departmentId)) {
            echo json_encode(['success' => false, 'message' => 'Departemen wajib dipilih.']);
            return;
        }

        try {
            // Generate UUID and Requisition Number (REQ-YYYYMM-XXX)
            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $datePrefix = 'REQ-' . date('Ym') . '-';
            $stmtSeq = $db->query("SELECT COUNT(*) FROM job_requisitions WHERE req_number LIKE '$datePrefix%'");
            $seq = ((int)$stmtSeq->fetchColumn()) + 1;
            $reqNumber = $datePrefix . sprintf('%03d', $seq);

            $stmt = $db->prepare("
                INSERT INTO job_requisitions (
                    id, req_number, title, department_id, requested_by, headcount,
                    employment_type, min_salary, max_salary, job_description, requirements,
                    reason, status
                ) VALUES (
                    :id, :req_number, :title, :department_id, :requested_by, :headcount,
                    :employment_type, :min_salary, :max_salary, :job_description, :requirements,
                    :reason, 'pending_dept_clevel'
                )
            ");
            $stmt->execute([
                'id' => $id,
                'req_number' => $reqNumber,
                'title' => $title,
                'department_id' => $departmentId,
                'requested_by' => $userId,
                'headcount' => $headcount,
                'employment_type' => $employmentType,
                'min_salary' => $minSalary,
                'max_salary' => $maxSalary,
                'job_description' => $jobDescription,
                'requirements' => $requirements,
                'reason' => $reason
            ]);

            // Add Audit Log
            $auditId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmtLog = $db->prepare("
                INSERT INTO job_requisition_approvals (id, requisition_id, action_by, action_role, stage, decision, notes)
                VALUES (:id, :req_id, :user_id, :role, 'submit', 'approved', 'Pengajuan Permintaan Karyawan Baru (FPTK) berhasil dibuat.')
            ");
            $stmtLog->execute([
                'id' => $auditId,
                'req_id' => $id,
                'user_id' => $userId,
                'role' => $role
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Permintaan Karyawan Baru ($reqNumber) berhasil dibuat dan diajukan ke C-Level Divisi!",
                'requisition_id' => $id
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve Job Requisition at the current stage
     */
    public function approve() {
        $role = $this->checkAuth(['executive', 'superadmin', 'hiring_manager'], true);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $userId = $_SESSION['user_id'];
        $reqId = trim($_POST['requisition_id'] ?? '');
        $notes = trim($_POST['notes'] ?? 'Disetujui');
        $approverType = trim($_POST['approver_type'] ?? ''); // 'dept_clevel', 'cfo', 'coo', 'ceo'

        if (empty($reqId)) {
            echo json_encode(['success' => false, 'message' => 'ID Permintaan wajib diisi.']);
            return;
        }

        try {
            $stmt = $db->prepare("SELECT * FROM job_requisitions WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $reqId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$req) {
                echo json_encode(['success' => false, 'message' => 'Data permintaan tidak ditemukan.']);
                return;
            }

            $currentStatus = $req['status'];
            $newStatus = $currentStatus;
            $stageName = '';

            $userEmail = strtolower($_SESSION['email'] ?? '');
            $userJob = strtolower($_SESSION['job_title'] ?? '');
            $isCEO = (str_contains($userEmail, 'ceo') || str_contains($userJob, 'ceo') || str_contains($userJob, 'chief executive'));
            $isCFO = (str_contains($userEmail, 'cfo') || str_contains($userJob, 'cfo') || str_contains($userJob, 'chief financial'));
            $isCOO = (str_contains($userEmail, 'coo') || str_contains($userJob, 'coo') || str_contains($userJob, 'chief operating'));
            $isSuperAdmin = ($role === 'superadmin');

            // Handle approval stages with strict sequential enforcement
            if ($currentStatus === 'pending_dept_clevel') {
                if (($isCEO || $isCFO || $isCOO) && !$isSuperAdmin) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Pengajuan masih berada di Level 1 (Pending C-Level Divisi). CEO/CFO/COO tidak dapat melakukan bypass sebelum C-Level Divisi terkait memberikan persetujuan.'
                    ]);
                    return;
                }

                // Check if C-Level user belongs to the requested department
                $userDeptId = $_SESSION['department_id'] ?? null;
                if (!$isSuperAdmin && (!empty($userDeptId) && $userDeptId !== $req['department_id'])) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Persetujuan Level 1 HANYA dapat dilakukan oleh C-Level dari departemen terkait yang bersangkutan.'
                    ]);
                    return;
                }

                $newStatus = 'pending_cfo_chro';
                $stageName = 'dept_clevel';
                
                $stmtUpdate = $db->prepare("
                    UPDATE job_requisitions SET
                        status = :new_status,
                        dept_clevel_approved_at = NOW(),
                        dept_clevel_approved_by = :user_id,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    'new_status' => $newStatus,
                    'user_id' => $userId,
                    'id' => $reqId
                ]);

            } elseif ($currentStatus === 'pending_cfo_chro') {
                if ($isCEO && !$isSuperAdmin && empty($approverType)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Pengajuan masih berada di Level 2 (Pending CFO & COO). CEO baru dapat melakukan approval final setelah tahap CFO & COO disetujui.'
                    ]);
                    return;
                }
                $cfoApproved = !empty($req['cfo_approved_at']) || $approverType === 'cfo';
                $cooApproved = !empty($req['chro_approved_at']) || $approverType === 'coo';
                $stageName = $approverType ?: 'cfo_chro';

                $cfoTime = $req['cfo_approved_at'];
                $cfoBy = $req['cfo_approved_by'];
                $cooTime = $req['chro_approved_at'];
                $cooBy = $req['chro_approved_by'];

                if ($approverType === 'cfo' || (empty($approverType) && empty($cfoTime))) {
                    $cfoTime = date('Y-m-d H:i:s');
                    $cfoBy = $userId;
                    $cfoApproved = true;
                }
                
                if ($approverType === 'coo' || (empty($approverType) && !empty($cfoTime) && empty($cooTime))) {
                    $cooTime = date('Y-m-d H:i:s');
                    $cooBy = $userId;
                    $cooApproved = true;
                }

                // If superadmin or direct approve with no specific type, approve both
                if ($role === 'superadmin' && empty($approverType)) {
                    $cfoTime = date('Y-m-d H:i:s');
                    $cfoBy = $userId;
                    $cooTime = date('Y-m-d H:i:s');
                    $cooBy = $userId;
                    $cfoApproved = true;
                    $cooApproved = true;
                }

                if ($cfoApproved && $cooApproved) {
                    $newStatus = 'pending_ceo';
                }

                $stmtUpdate = $db->prepare("
                    UPDATE job_requisitions SET
                        status = :new_status,
                        cfo_approved_at = :cfo_at,
                        cfo_approved_by = :cfo_by,
                        chro_approved_at = :coo_at,
                        chro_approved_by = :coo_by,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    'new_status' => $newStatus,
                    'cfo_at' => $cfoTime,
                    'cfo_by' => $cfoBy,
                    'coo_at' => $cooTime,
                    'coo_by' => $cooBy,
                    'id' => $reqId
                ]);

            } elseif ($currentStatus === 'pending_ceo') {
                $newStatus = 'approved';
                $stageName = 'ceo';

                $stmtUpdate = $db->prepare("
                    UPDATE job_requisitions SET
                        status = :new_status,
                        ceo_approved_at = NOW(),
                        ceo_approved_by = :user_id,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    'new_status' => $newStatus,
                    'user_id' => $userId,
                    'id' => $reqId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Permintaan sudah berada di tahap akhir atau telah ditolak.']);
                return;
            }

            // Log approval
            $auditId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmtLog = $db->prepare("
                INSERT INTO job_requisition_approvals (id, requisition_id, action_by, action_role, stage, decision, notes)
                VALUES (:id, :req_id, :user_id, :role, :stage, 'approved', :notes)
            ");
            $stmtLog->execute([
                'id' => $auditId,
                'req_id' => $reqId,
                'user_id' => $userId,
                'role' => $role,
                'stage' => $stageName,
                'notes' => $notes
            ]);

            $msg = ($newStatus === 'approved') 
                ? 'Persetujuan Final CEO selesai! Permintaan telah diteruskan ke Recruiter untuk dipublikasikan.'
                : (($newStatus === 'pending_ceo') 
                    ? 'Persetujuan CFO & CHRO selesai! Pengajuan diteruskan ke CEO.'
                    : 'Persetujuan berhasil diproses!');

            echo json_encode([
                'success' => true,
                'message' => $msg,
                'new_status' => $newStatus
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject Job Requisition
     */
    public function reject() {
        $role = $this->checkAuth(['executive', 'superadmin'], true);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $userId = $_SESSION['user_id'];
        $reqId = trim($_POST['requisition_id'] ?? '');
        $reason = trim($_POST['rejection_reason'] ?? 'Permintaan tidak disetujui');

        if (empty($reqId)) {
            echo json_encode(['success' => false, 'message' => 'ID Permintaan wajib diisi.']);
            return;
        }

        try {
            $stmt = $db->prepare("SELECT * FROM job_requisitions WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $reqId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$req) {
                echo json_encode(['success' => false, 'message' => 'Data permintaan tidak ditemukan.']);
                return;
            }

            $stmtUpdate = $db->prepare("
                UPDATE job_requisitions SET
                    status = 'rejected',
                    rejected_at = NOW(),
                    rejected_by = :user_id,
                    rejection_reason = :reason,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                'user_id' => $userId,
                'reason' => $reason,
                'id' => $reqId
            ]);

            // Log audit
            $auditId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmtLog = $db->prepare("
                INSERT INTO job_requisition_approvals (id, requisition_id, action_by, action_role, stage, decision, notes)
                VALUES (:id, :req_id, :user_id, :role, 'reject', 'rejected', :notes)
            ");
            $stmtLog->execute([
                'id' => $auditId,
                'req_id' => $reqId,
                'user_id' => $userId,
                'role' => $role,
                'notes' => $reason
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Permintaan karyawan baru telah ditolak.'
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Revoke (Un-approve) Level 2 approval before final CEO approval
     */
    public function revoke() {
        $role = $this->checkAuth(['executive', 'superadmin'], true);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $userId = $_SESSION['user_id'];
        $reqId = trim($_POST['requisition_id'] ?? '');
        $approverType = trim($_POST['approver_type'] ?? ''); // 'cfo' or 'coo'

        if (empty($reqId)) {
            echo json_encode(['success' => false, 'message' => 'ID Permintaan wajib diisi.']);
            return;
        }

        try {
            $stmt = $db->prepare("SELECT * FROM job_requisitions WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $reqId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$req) {
                echo json_encode(['success' => false, 'message' => 'Data permintaan tidak ditemukan.']);
                return;
            }

            if ($req['status'] !== 'pending_cfo_chro') {
                echo json_encode(['success' => false, 'message' => 'Persetujuan hanya dapat dibatalkan saat alur berada di Level 2 (sebelum keduanya menyetujui).']);
                return;
            }

            $userEmail = strtolower($_SESSION['email'] ?? '');
            $userJob = strtolower($_SESSION['job_title'] ?? '');
            $isCFO = (str_contains($userEmail, 'cfo') || str_contains($userJob, 'cfo') || str_contains($userJob, 'chief financial'));
            $isCOO = (str_contains($userEmail, 'coo') || str_contains($userJob, 'coo') || str_contains($userJob, 'chief operating'));
            $isSuperAdmin = ($role === 'superadmin');

            if (($approverType === 'cfo' || empty($approverType)) && ($isCFO || $isSuperAdmin)) {
                $stmtUpdate = $db->prepare("UPDATE job_requisitions SET cfo_approved_at = NULL, cfo_approved_by = NULL, updated_at = NOW() WHERE id = :id");
                $stmtUpdate->execute(['id' => $reqId]);
            } elseif (($approverType === 'coo' || empty($approverType)) && ($isCOO || $isSuperAdmin)) {
                $stmtUpdate = $db->prepare("UPDATE job_requisitions SET chro_approved_at = NULL, chro_approved_by = NULL, updated_at = NOW() WHERE id = :id");
                $stmtUpdate->execute(['id' => $reqId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki wewenang untuk membatalkan persetujuan ini.']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Persetujuan berhasil dibatalkan. Status dikembalikan ke pending.'
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Publish Job Requisition (Executed by Recruiter)
     */
    public function publish() {
        $role = $this->checkAuth(['recruiter', 'superadmin'], true);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $userId = $_SESSION['user_id'];
        $reqId = trim($_POST['requisition_id'] ?? '');
        $customTitle = trim($_POST['title'] ?? '');
        $customDesc = trim($_POST['job_description'] ?? '');
        $customIcon = trim($_POST['icon'] ?? '');
        $customColor = trim($_POST['card_color'] ?? '');
        $customLogoUrl = trim($_POST['logo_url'] ?? '');

        // Handle logo file upload
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo_file'];
            if ($file['size'] <= 1048576) {
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
                    if (empty($ext)) $ext = 'png';
                    $filename = 'logo_req_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $customLogoUrl = '/uploads/logos/' . $filename;
                    }
                }
            }
        }

        if (empty($reqId)) {
            echo json_encode(['success' => false, 'message' => 'ID Permintaan wajib diisi.']);
            return;
        }

        try {
            // Ensure icon, card_color, and logo_url columns exist
            try {
                $db->exec("ALTER TABLE job_requisitions ADD COLUMN IF NOT EXISTS icon VARCHAR(50) DEFAULT 'work' AFTER job_description");
                $db->exec("ALTER TABLE job_requisitions ADD COLUMN IF NOT EXISTS card_color VARCHAR(50) DEFAULT '#0c145e' AFTER icon");
                $db->exec("ALTER TABLE job_requisitions ADD COLUMN IF NOT EXISTS logo_url VARCHAR(255) DEFAULT NULL AFTER card_color");
            } catch (\Exception $e) {}

            $stmt = $db->prepare("SELECT * FROM job_requisitions WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $reqId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$req) {
                echo json_encode(['success' => false, 'message' => 'Data permintaan tidak ditemukan.']);
                return;
            }

            if ($req['status'] !== 'approved' && $req['status'] !== 'published' && $req['status'] !== 'closed') {
                echo json_encode(['success' => false, 'message' => 'Hanya permintaan yang telah disetujui penuh oleh CEO yang dapat dipublikasikan.']);
                return;
            }

            $updateFields = [
                "status = 'published'",
                "published_at = NOW()",
                "published_by = :user_id",
                "updated_at = NOW()"
            ];
            $params = [
                'user_id' => $userId,
                'id' => $reqId
            ];

            if ($customTitle !== '') {
                $updateFields[] = "title = :title";
                $params['title'] = $customTitle;
            }

            if ($customDesc !== '') {
                $updateFields[] = "job_description = :desc";
                $params['desc'] = $customDesc;
            }

            if ($customIcon !== '') {
                $updateFields[] = "icon = :icon";
                $params['icon'] = $customIcon;
            }

            if ($customColor !== '') {
                $updateFields[] = "card_color = :color";
                $params['color'] = $customColor;
            }

            if ($customLogoUrl !== '') {
                $updateFields[] = "logo_url = :logo_url";
                $params['logo_url'] = $customLogoUrl;
            }

            $stmtUpdate = $db->prepare("
                UPDATE job_requisitions SET
                    " . implode(", ", $updateFields) . "
                WHERE id = :id
            ");
            $stmtUpdate->execute($params);

            $auditId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmtLog = $db->prepare("
                INSERT INTO job_requisition_approvals (id, requisition_id, action_by, action_role, stage, decision, notes)
                VALUES (:id, :req_id, :user_id, :role, 'publish', 'published', 'Lowongan resmi dipublikasikan ke Portal Karir.')
            ");
            $stmtLog->execute([
                'id' => $auditId,
                'req_id' => $reqId,
                'user_id' => $userId,
                'role' => $role
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Lowongan pekerjaan resmi dipublikasikan dan tayang di Portal Karir!'
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    public function unpublishJob() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? '';

        if (!$userId || !in_array($role, ['superadmin', 'recruiter', 'hr_ops', 'c_level'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $reqId = trim($_POST['requisition_id'] ?? '');
        if (empty($reqId)) {
            echo json_encode(['success' => false, 'message' => 'ID permintaan tidak valid.']);
            return;
        }

        try {
            $db = $this->getDb();
            $stmtUpdate = $db->prepare("
                UPDATE job_requisitions SET
                    status = 'closed',
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmtUpdate->execute(['id' => $reqId]);

            $auditId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmtLog = $db->prepare("
                INSERT INTO job_requisition_approvals (id, requisition_id, action_by, action_role, stage, decision, notes)
                VALUES (:id, :req_id, :user_id, :role, 'unpublish', 'closed', 'Lowongan dinonaktifkan / ditutup dari publik oleh Rekruter.')
            ");
            $stmtLog->execute([
                'id' => $auditId,
                'req_id' => $reqId,
                'user_id' => $userId,
                'role' => $role
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Lowongan pekerjaan berhasil dinonaktifkan / ditutup dari publik!'
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    public function applyJob() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $reqId = trim($_POST['requisition_id'] ?? '');
        $name = trim($_POST['candidate_name'] ?? '');
        $email = trim($_POST['candidate_email'] ?? '');
        $phone = trim($_POST['candidate_phone'] ?? '');
        $portfolioUrl = trim($_POST['candidate_portfolio'] ?? '');
        $coverLetter = trim($_POST['cover_letter'] ?? '');
        $candidateId = $_SESSION['user_id'] ?? null;

        if (empty($reqId) || empty($name) || empty($email) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Mohon lengkapi semua kolom wajib (Nama, Email, Telepon).']);
            return;
        }

        $resumePath = null;
        if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['resume_file'];
            if ($file['size'] > 5242880) {
                echo json_encode(['success' => false, 'message' => 'Ukuran file CV/Resume maksimal 5MB.']);
                return;
            }
            $uploadDir = __DIR__ . '/../../public/uploads/resumes/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (empty($ext)) $ext = 'pdf';
            $filename = 'cv_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $resumePath = '/uploads/resumes/' . $filename;
            }
        }

        if (empty($resumePath) && !empty($candidateId)) {
            try {
                $db = $this->getDb();
                $stmtU = $db->prepare("SELECT resume_file FROM users WHERE id = :id");
                $stmtU->execute(['id' => $candidateId]);
                $resumePath = $stmtU->fetchColumn() ?: null;
            } catch (\Exception $e) {}
        }

        if (empty($coverLetter) && !empty($candidateId)) {
            try {
                $db = $this->getDb();
                $stmtC = $db->prepare("SELECT cover_letter_file FROM users WHERE id = :id");
                $stmtC->execute(['id' => $candidateId]);
                $coverLetter = $stmtC->fetchColumn() ?: null;
            } catch (\Exception $e) {}
        }

        try {
            $db = $this->getDb();
            $appId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmt = $db->prepare("
                INSERT INTO job_applications (id, requisition_id, candidate_id, candidate_name, candidate_email, candidate_phone, portfolio_url, resume_path, cover_letter, status)
                VALUES (:id, :req_id, :cand_id, :name, :email, :phone, :portfolio, :resume, :cover, 'submitted')
            ");
            $stmt->execute([
                'id' => $appId,
                'req_id' => $reqId,
                'cand_id' => $candidateId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'portfolio' => $portfolioUrl,
                'resume' => $resumePath,
                'cover' => $coverLetter
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Lamaran pekerjaan Anda telah berhasil dikirim! Tim rekrutmen kami akan segera meninjau berkas Anda.'
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan lamaran: ' . $e->getMessage()]);
        }
    }

    public function updateApplicationStatus() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? '';

        if (!$userId || !in_array($role, ['superadmin', 'recruiter', 'hr_ops', 'c_level'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $appId = trim($_POST['application_id'] ?? '');
        $status = trim($_POST['status'] ?? '');

        if (empty($appId) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap.']);
            return;
        }

        $allowedStatuses = ['submitted', 'screening', 'shortlisted', 'interview', 'offering', 'hired', 'rejected'];
        if (!in_array($status, $allowedStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
            return;
        }

        try {
            $db = $this->getDb();
            $stmtUpdate = $db->prepare("
                UPDATE job_applications SET
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                'status' => $status,
                'id' => $appId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Status pelamar berhasil diperbarui menjadi ' . ucfirst($status) . '.'
            ]);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }
}

