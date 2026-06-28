<?php

namespace App\Controllers;

use App\Config\Database;
use Exception;
use PDO;

class LeaveController {

    private $db;
    private $uploadDir;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDir = __DIR__ . '/../../storage/secured_storage/leaves/';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Validate CSRF token from POST body or HTTP header.
     */
    private function validateCsrf(): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    /**
     * Submit a new leave request (ESS Portal)
     */
    public function submit() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda harus login sebagai Karyawan.']);
            return;
        }

        // CSRF Token Validation
        if (!$this->validateCsrf()) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau kedaluwarsa. Silakan muat ulang halaman.']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $leaveType = $_POST['leave_type'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $reason = $_POST['reason'] ?? '';

        $allowedTypes = ['cuti tahunan', 'cuti sakit', 'cuti melahirkan', 'izin khusus'];
        if (!in_array($leaveType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Tipe pengajuan cuti tidak valid.']);
            return;
        }

        if (empty($startDate) || empty($endDate)) {
            echo json_encode(['success' => false, 'message' => 'Tanggal mulai dan tanggal selesai wajib ditentukan.']);
            return;
        }

        $startTs = strtotime($startDate);
        if (!$startTs) {
            echo json_encode(['success' => false, 'message' => 'Tanggal mulai tidak valid.']);
            return;
        }

        if ($leaveType === 'cuti melahirkan') {
            $endTs = strtotime($startDate . ' + 89 days');
            $endDate = date('Y-m-d', $endTs);
            $duration = 90;
        } else {
            $endTs = strtotime($endDate);
            if (!$endTs || $startTs > $endTs) {
                echo json_encode(['success' => false, 'message' => 'Urutan tanggal tidak valid. Tanggal mulai harus sebelum atau sama dengan tanggal selesai.']);
                return;
            }
            $duration = floor(($endTs - $startTs) / 86400) + 1;
        }

        if ($duration <= 0) {
            echo json_encode(['success' => false, 'message' => 'Durasi cuti minimal 1 hari.']);
            return;
        }

        if (empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Alasan pengajuan cuti wajib diisi.']);
            return;
        }

        try {
            // Validate remaining annual leave quota if applicable
            if ($leaveType === 'cuti tahunan') {
                $stmt = $this->db->prepare("SELECT annual_leave_quota FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $quota = intval($stmt->fetchColumn());

                if ($duration > $quota) {
                    echo json_encode(['success' => false, 'message' => "Jatah cuti tahunan Anda tidak mencukupi. Sisa kuota Anda: {$quota} hari, sedangkan pengajuan ini berdurasi: {$duration} hari."]);
                    return;
                }

                // Also validate monthly quota for cuti tahunan (max 12 days/month, resets every month)
                $stmt = $this->db->prepare("
                    SELECT SUM(duration) FROM employee_leave_requests 
                    WHERE user_id = :user_id 
                      AND leave_type = 'cuti tahunan' 
                      AND status = 'approved' 
                      AND MONTH(created_at) = MONTH(CURRENT_DATE) 
                      AND YEAR(created_at) = YEAR(CURRENT_DATE)
                ");
                $stmt->execute(['user_id' => $userId]);
                $monthlyAnnualLeaveApproved = intval($stmt->fetchColumn() ?? 0);
                $dropdownAnnualLeaveRemaining = max(0, 12 - $monthlyAnnualLeaveApproved);

                if ($duration > $dropdownAnnualLeaveRemaining) {
                    echo json_encode(['success' => false, 'message' => "Jatah cuti tahunan Anda untuk bulan ini tidak mencukupi. Sisa kuota bulan ini: {$dropdownAnnualLeaveRemaining} hari, sedangkan pengajuan Anda berdurasi {$duration} hari."]);
                    return;
                }
            }

            // Validate remaining maternity leave quota if applicable (max 90 days/month, resets every month)
            if ($leaveType === 'cuti melahirkan') {
                $stmt = $this->db->prepare("
                    SELECT SUM(duration) FROM employee_leave_requests 
                    WHERE user_id = :user_id 
                      AND leave_type = 'cuti melahirkan' 
                      AND status = 'approved' 
                      AND MONTH(created_at) = MONTH(CURRENT_DATE) 
                      AND YEAR(created_at) = YEAR(CURRENT_DATE)
                ");
                $stmt->execute(['user_id' => $userId]);
                $monthlyMaternityLeaveApproved = intval($stmt->fetchColumn() ?? 0);
                $dropdownMaternityLeaveRemaining = max(0, 90 - $monthlyMaternityLeaveApproved);

                if ($duration > $dropdownMaternityLeaveRemaining) {
                    echo json_encode(['success' => false, 'message' => "Jatah cuti melahirkan Anda untuk bulan ini tidak mencukupi. Sisa kuota bulan ini: {$dropdownMaternityLeaveRemaining} hari, sedangkan pengajuan Anda berdurasi {$duration} hari."]);
                    return;
                }
            }

            $hasAttachment = isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE;
            
            // Validate that attachment is provided for Cuti Sakit and Cuti Melahirkan
            if (($leaveType === 'cuti sakit' || $leaveType === 'cuti melahirkan') && !$hasAttachment) {
                echo json_encode(['success' => false, 'message' => 'Berkas bukti pendukung wajib diunggah untuk tipe pengajuan ' . ($leaveType === 'cuti sakit' ? 'Cuti Sakit' : 'Cuti Melahirkan') . '.']);
                return;
            }
            
            $safeFilename = null;
            if ($hasAttachment) {
                $file = $_FILES['attachment'];

                // 10MB maximum limit
                $maxSize = 10 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    echo json_encode(['success' => false, 'message' => 'Ukuran berkas melebihi batas maksimal 10MB.']);
                    return;
                }

                // Server-side MIME validation via PHP finfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!in_array($mimeType, $allowedMimeTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Format berkas tidak valid. Hanya JPG, PNG, atau PDF asli yang diperbolehkan.']);
                    return;
                }

                // Save file securely
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (empty($ext)) {
                    $ext = ($mimeType === 'application/pdf') ? 'pdf' : (($mimeType === 'image/png') ? 'png' : 'jpg');
                }
                $safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
                $destination = $this->uploadDir . $safeFilename;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan berkas bukti di server.']);
                    return;
                }
            }

            // Generate UUID
            $requestId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmt = $this->db->prepare("
                INSERT INTO employee_leave_requests (id, user_id, leave_type, start_date, end_date, duration, reason, attachment_path, status)
                VALUES (:id, :user_id, :leave_type, :start_date, :end_date, :duration, :reason, :attachment_path, 'pending')
            ");

            $stmt->execute([
                'id' => $requestId,
                'user_id' => $userId,
                'leave_type' => $leaveType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration' => $duration,
                'reason' => $reason,
                'attachment_path' => $safeFilename
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Permohonan cuti/izin berhasil diajukan dan sedang menunggu tinjauan HR Operations.'
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel and delete a pending leave request (ESS Portal)
     */
    public function cancel() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda harus login sebagai Karyawan.']);
            return;
        }

        // CSRF Token Validation
        if (!$this->validateCsrf()) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau kedaluwarsa. Silakan muat ulang halaman.']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $requestId = $_POST['id'] ?? '';

        if (empty($requestId)) {
            echo json_encode(['success' => false, 'message' => 'ID pengajuan tidak boleh kosong.']);
            return;
        }

        try {
            // Retrieve leave request to see if it belongs to the user and is still pending
            $stmt = $this->db->prepare("SELECT attachment_path FROM employee_leave_requests WHERE id = :id AND user_id = :user_id AND status = 'pending'");
            $stmt->execute(['id' => $requestId, 'user_id' => $userId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Pengajuan tidak ditemukan atau tidak dapat dibatalkan karena sudah diproses.']);
                return;
            }

            // Begin transaction
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("DELETE FROM employee_leave_requests WHERE id = :id AND user_id = :user_id AND status = 'pending'");
            $stmt->execute(['id' => $requestId, 'user_id' => $userId]);

            $this->db->commit();

            // Clean up attachment physical file if it exists
            if (!empty($request['attachment_path'])) {
                $filePath = $this->uploadDir . $request['attachment_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Pengajuan cuti berhasil dibatalkan dan dihapus secara aman.']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal membatalkan pengajuan cuti: ' . $e->getMessage()]);
        }
    }

    private function getDescendantDepartmentIds($db, $deptId) {
        $ids = [$deptId];
        $stmt = $db->prepare("SELECT id FROM departments WHERE parent_id = ?");
        $stmt->execute([$deptId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getDescendantDepartmentIds($db, $childId));
        }
        return $ids;
    }

    /**
     * Approve leave request (HR Operations & Hiring Manager)
     */
    public function approve() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr_ops', 'hiring_manager', 'admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Otoritas manajemen diperlukan.']);
            return;
        }

        // CSRF Token Validation
        if (!$this->validateCsrf()) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau kedaluwarsa. Silakan muat ulang halaman.']);
            return;
        }

        $requestId = $_POST['id'] ?? '';
        if (empty($requestId)) {
            echo json_encode(['success' => false, 'message' => 'ID permohonan cuti tidak boleh kosong.']);
            return;
        }

        try {
            // Retrieve details of the leave request
            $stmt = $this->db->prepare("SELECT user_id, leave_type, duration, status FROM employee_leave_requests WHERE id = :id");
            $stmt->execute(['id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Pengajuan cuti tidak ditemukan.']);
                return;
            }

            if ($request['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Pengajuan cuti ini sudah diproses sebelumnya.']);
                return;
            }

            // Hierarchical validation for hiring_manager
            if ($_SESSION['role'] === 'hiring_manager') {
                $stmtManager = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
                $stmtManager->execute(['id' => $_SESSION['user_id']]);
                $managerDeptId = $stmtManager->fetchColumn();

                if (empty($managerDeptId)) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda adalah manajer tetapi tidak memiliki alokasi departemen.']);
                    return;
                }

                $stmtTarget = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
                $stmtTarget->execute(['id' => $request['user_id']]);
                $targetDeptId = $stmtTarget->fetchColumn();

                $allowedDepts = $this->getDescendantDepartmentIds($this->db, $managerDeptId);
                if (!in_array($targetDeptId, $allowedDepts)) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda hanya dapat memproses pengajuan dari staf fungsional departemen Anda sendiri.']);
                    return;
                }
            }

            $this->db->beginTransaction();

            // Deduct quota if it's annual leave (cuti tahunan)
            if ($request['leave_type'] === 'cuti tahunan') {
                // Fetch current user quota
                $stmt = $this->db->prepare("SELECT annual_leave_quota FROM users WHERE id = :user_id");
                $stmt->execute(['user_id' => $request['user_id']]);
                $quota = intval($stmt->fetchColumn());

                if ($request['duration'] > $quota) {
                    $this->db->rollBack();
                    echo json_encode(['success' => false, 'message' => "Gagal menyetujui. Sisa jatah cuti tahunan karyawan tidak mencukupi. (Sisa: {$quota} hari, dibutuhkan: {$request['duration']} hari)."]);
                    return;
                }

                $stmt = $this->db->prepare("UPDATE users SET annual_leave_quota = annual_leave_quota - :duration WHERE id = :user_id");
                $stmt->execute([
                    'duration' => $request['duration'],
                    'user_id' => $request['user_id']
                ]);
            }

            // Update request status
            $stmt = $this->db->prepare("UPDATE employee_leave_requests SET status = 'approved', rejection_reason = NULL WHERE id = :id");
            $stmt->execute(['id' => $requestId]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Permohonan cuti karyawan berhasil disetujui. Jatah cuti tahunan dipotong secara otomatis.']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject leave request (HR Operations & Hiring Manager)
     */
    public function reject() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr_ops', 'hiring_manager', 'admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Otoritas manajemen diperlukan.']);
            return;
        }

        // CSRF Token Validation
        if (!$this->validateCsrf()) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau kedaluwarsa. Silakan muat ulang halaman.']);
            return;
        }

        $requestId = $_POST['id'] ?? '';
        $rejectionReason = $_POST['rejection_reason'] ?? '';

        if (empty($requestId)) {
            echo json_encode(['success' => false, 'message' => 'ID permohonan cuti tidak boleh kosong.']);
            return;
        }

        if (empty($rejectionReason)) {
            echo json_encode(['success' => false, 'message' => 'Alasan penolakan pengajuan wajib diisi agar karyawan mendapat penjelasan.']);
            return;
        }

        try {
            // Retrieve details of the leave request
            $stmt = $this->db->prepare("SELECT user_id, status FROM employee_leave_requests WHERE id = :id");
            $stmt->execute(['id' => $requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Pengajuan cuti tidak ditemukan.']);
                return;
            }

            if ($request['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Pengajuan cuti ini sudah diproses sebelumnya.']);
                return;
            }

            // Hierarchical validation for hiring_manager
            if ($_SESSION['role'] === 'hiring_manager') {
                $stmtManager = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
                $stmtManager->execute(['id' => $_SESSION['user_id']]);
                $managerDeptId = $stmtManager->fetchColumn();

                if (empty($managerDeptId)) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda adalah manajer tetapi tidak memiliki alokasi departemen.']);
                    return;
                }

                $stmtTarget = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
                $stmtTarget->execute(['id' => $request['user_id']]);
                $targetDeptId = $stmtTarget->fetchColumn();

                $allowedDepts = $this->getDescendantDepartmentIds($this->db, $managerDeptId);
                if (!in_array($targetDeptId, $allowedDepts)) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda hanya dapat memproses pengajuan dari staf fungsional departemen Anda sendiri.']);
                    return;
                }
            }

            $stmt = $this->db->prepare("UPDATE employee_leave_requests SET status = 'rejected', rejection_reason = :rejection_reason WHERE id = :id");
            $stmt->execute([
                'id' => $requestId,
                'rejection_reason' => $rejectionReason
            ]);

            echo json_encode(['success' => true, 'message' => 'Permohonan cuti karyawan berhasil ditolak dengan alasan tercatat.']);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    /**
     * Stream uploaded attachment documents securely with owner & manager validation
     */
    public function viewAttachment() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.1 403 Forbidden');
            $content = renderView('pages/errors/403');
            $page = 'error_403';
            require __DIR__ . '/../../resources/views/layouts/guest.php';
            return;
        }

        // Secure Referer & Sec-Fetch-Site checks to prevent direct access / copy-paste URL
        $allowedOrigin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $secFetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $secFetchDest = $_SERVER['HTTP_SEC_FETCH_DEST'] ?? '';
        $isDownload = (isset($_GET['download']) && $_GET['download'] === '1');
        
        $isValidRequest = true;
        
        // Block direct URL navigation (open in new tab / copy-paste URL)
        if ($secFetchDest === 'document' && !$isDownload) {
            $isValidRequest = false;
        }
        
        if ($isValidRequest) {
            if (!empty($secFetchSite)) {
                if ($secFetchSite !== 'same-origin' && $secFetchSite !== 'same-site') {
                    // Check if it's localhost / loopback mismatch
                    $currentHost = parse_url($allowedOrigin, PHP_URL_HOST);
                    $isLocalCurrent = in_array(strtolower($currentHost), ['localhost', '127.0.0.1', '::1']);
                    
                    if ($isLocalCurrent && !empty($referer)) {
                        $refererHost = parse_url($referer, PHP_URL_HOST);
                        if ($refererHost && in_array(strtolower($refererHost), ['localhost', '127.0.0.1', '::1'])) {
                            // Allow local requests
                        } else {
                            $isValidRequest = false;
                        }
                    } else {
                        $isValidRequest = false;
                    }
                }
            } else {
            if (empty($referer)) {
                $isValidRequest = false;
            } else {
                $refererHost = parse_url($referer, PHP_URL_HOST);
                $currentHost = parse_url($allowedOrigin, PHP_URL_HOST);
                
                if ($refererHost && $currentHost) {
                    $localHosts = ['localhost', '127.0.0.1', '::1'];
                    $isLocalReferer = in_array(strtolower($refererHost), $localHosts);
                    $isLocalCurrent = in_array(strtolower($currentHost), $localHosts);
                    
                    if ($isLocalReferer && $isLocalCurrent) {
                        // Allow localhost / 127.0.0.1 cross-access
                    } elseif (strcasecmp($refererHost, $currentHost) !== 0) {
                        $currentHostParts = explode('.', $currentHost);
                        $refererHostParts = explode('.', $refererHost);
                        $cCount = count($currentHostParts);
                        $rCount = count($refererHostParts);
                        
                        $shareRoot = false;
                        if ($cCount >= 2 && $rCount >= 2) {
                            $currentBase = implode('.', array_slice($currentHostParts, -2));
                            $refererBase = implode('.', array_slice($refererHostParts, -2));
                            if (strcasecmp($currentBase, $refererBase) === 0) {
                                $shareRoot = true;
                            }
                        }
                        if (!$shareRoot) {
                            $isValidRequest = false;
                        }
                    }
                } else {
                    $isValidRequest = false;
                }
            }
        }
        }
        
        if (!$isValidRequest) {
            header('HTTP/1.1 403 Forbidden');
            $content = renderView('pages/errors/403');
            $page = 'error_403';
            require __DIR__ . '/../../resources/views/layouts/guest.php';
            return;
        }

        $fileName = $_GET['file'] ?? '';
        if (empty($fileName)) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Nama berkas tidak boleh kosong.';
            return;
        }

        // Prevent directory traversal attacks
        $fileName = basename($fileName);
        $filePath = $this->uploadDir . $fileName;

        if (!file_exists($filePath)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Berkas bukti pendukung cuti tidak ditemukan di server.';
            return;
        }

        try {
            // Find owner user ID from database
            $stmt = $this->db->prepare("SELECT user_id FROM employee_leave_requests WHERE attachment_path = :path");
            $stmt->execute(['path' => $fileName]);
            $ownerId = $stmt->fetchColumn();

            // Authorization: Only the owner employee OR hr_ops OR superadmin OR admin OR manager fungsional is allowed to view
            $userRole = $_SESSION['role'] ?? '';
            $isAuthorized = ($userRole === 'hr_ops' || $userRole === 'superadmin' || $userRole === 'admin' || $_SESSION['user_id'] === $ownerId);

            if (!$isAuthorized && $userRole === 'hiring_manager') {
                // Fetch manager's department_id
                $stmtManager = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
                $stmtManager->execute(['id' => $_SESSION['user_id']]);
                $managerDeptId = $stmtManager->fetchColumn();

                if (!empty($managerDeptId)) {
                    // Fetch target owner's department_id
                    $stmtOwner = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
                    $stmtOwner->execute(['id' => $ownerId]);
                    $ownerDeptId = $stmtOwner->fetchColumn();

                    $allowedDepts = $this->getDescendantDepartmentIds($this->db, $managerDeptId);
                    if (in_array($ownerDeptId, $allowedDepts)) {
                        $isAuthorized = true;
                    }
                }
            }

            if (!$isAuthorized) {
                // Fallback check standard seeded dummy file
                $seededDummies = ['surat_dokter_rian.pdf', 'rujukan_hpl.pdf'];
                if (!in_array($fileName, $seededDummies)) {
                    header('HTTP/1.1 403 Forbidden');
                    $content = renderView('pages/errors/403');
                    $page = 'error_403';
                    require __DIR__ . '/../../resources/views/layouts/guest.php';
                    return;
                }
            }

            // Read file MIME type securely using server finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            // CORS & Security Headers
            header('Access-Control-Allow-Origin: ' . $allowedOrigin);
            header('Access-Control-Allow-Methods: GET');
            header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
            header('Access-Control-Max-Age: 86400');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');

            // Stream file contents
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            if ($isDownload) {
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
            }
            readfile($filePath);
            exit;

        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Terjadi kesalahan internal server: ' . $e->getMessage();
        }
    }
}
