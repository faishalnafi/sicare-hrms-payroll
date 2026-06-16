<?php

namespace App\Controllers;

use App\Config\Database;
use Exception;
use PDO;

class ReimbursementController {

    private $db;
    private $uploadDir;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDir = __DIR__ . '/../../storage/secured_storage/receipts/';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Submit a new reimbursement claim (Employee ESS Portal)
     */
    public function submit() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda harus login sebagai Karyawan.']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $category = $_POST['category'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $description = $_POST['description'] ?? '';

        $allowedCategories = ['medis', 'transport', 'operasional', 'makan'];
        if (!in_array($category, $allowedCategories)) {
            echo json_encode(['success' => false, 'message' => 'Kategori reimbursement tidak valid.']);
            return;
        }

        $amount = floatval($amount);
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Nominal klaim harus bernilai positif.']);
            return;
        }

        if (empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Keterangan/Deskripsi klaim wajib diisi.']);
            return;
        }

        // Fetch user's department details first (including total and category-specific overrides)
        try {
            $stmtDept = $this->db->prepare("
                SELECT d.id, d.name, d.reimbursement_limit, d.limit_medis, d.limit_transport, d.limit_operasional, d.limit_makan
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE u.id = :user_id
            ");
            $stmtDept->execute(['user_id' => $userId]);
            $deptInfo = $stmtDept->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $deptInfo = null;
        }

        // Fetch limits dynamically from global_settings
        try {
            $settingsQuery = $this->db->query("SELECT `key`, `value` FROM global_settings WHERE `key` LIKE 'reimbursement_limit_%'");
            $settings = $settingsQuery->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settings = [];
        }

        // Determine category limit: check department override first, then global settings, then hardcoded fallbacks
        $maxLimit = null;
        if ($deptInfo) {
            $deptCatKey = 'limit_' . $category; // e.g. limit_medis, limit_transport, limit_operasional, limit_makan
            if (isset($deptInfo[$deptCatKey]) && $deptInfo[$deptCatKey] !== null) {
                $maxLimit = floatval($deptInfo[$deptCatKey]);
            }
        }

        if ($maxLimit === null) {
            $maxLimit = isset($settings['reimbursement_limit_' . $category]) ? floatval($settings['reimbursement_limit_' . $category]) : null;
        }

        if ($maxLimit === null) {
            // fallback defaults
            $fallbacks = [
                'medis' => 5000000.00,
                'transport' => 3000000.00,
                'operasional' => 4000000.00,
                'makan' => 2500000.00
            ];
            $maxLimit = $fallbacks[$category] ?? 0.0;
        }

        if ($maxLimit <= 0.0) {
            echo json_encode(['success' => false, 'message' => 'Plafon reimbursement untuk kategori ini dinonaktifkan atau bernilai Rp 0.']);
            return;
        }

        // Fetch current month's usage (approved + pending) to validate category limit
        $stmtUsage = $this->db->prepare("
            SELECT SUM(amount) FROM employee_reimbursement_claims
            WHERE user_id = :user_id 
              AND category = :category 
              AND status IN ('approved', 'pending')
              AND MONTH(created_at) = MONTH(CURRENT_DATE())
              AND YEAR(created_at) = YEAR(CURRENT_DATE())
        ");
        $stmtUsage->execute([
            'user_id' => $userId, 
            'category' => $category
        ]);
        $currentUsage = floatval($stmtUsage->fetchColumn());

        if (($currentUsage + $amount) > $maxLimit) {
            $sisa = $maxLimit - $currentUsage;
            echo json_encode(['success' => false, 'message' => 'Sisa plafon Anda bulan ini tidak mencukupi untuk nominal tersebut. Sisa: Rp ' . number_format(max(0, $sisa), 0, ',', '.')]);
            return;
        }

        if ($deptInfo) {
            $deptLimit = null;
            if ($deptInfo['reimbursement_limit'] !== null) {
                $deptLimit = floatval($deptInfo['reimbursement_limit']);
            } else {
                // Fallback to default department limit from global settings
                if (isset($settings['reimbursement_limit_department_default'])) {
                    $deptLimit = floatval($settings['reimbursement_limit_department_default']);
                }
            }

            if ($deptLimit !== null) {
                // Fetch current month's department usage (approved + pending)
                $stmtDeptUsage = $this->db->prepare("
                    SELECT SUM(c.amount) 
                    FROM employee_reimbursement_claims c
                    JOIN users u ON c.user_id = u.id
                    WHERE u.department_id = :dept_id 
                      AND c.status IN ('approved', 'pending')
                      AND MONTH(c.created_at) = MONTH(CURRENT_DATE())
                      AND YEAR(c.created_at) = YEAR(CURRENT_DATE())
                ");
                $stmtDeptUsage->execute(['dept_id' => $deptInfo['id']]);
                $currentDeptUsage = floatval($stmtDeptUsage->fetchColumn());

                if (($currentDeptUsage + $amount) > $deptLimit || $deptLimit <= 0.0) {
                    $sisaDept = $deptLimit - $currentDeptUsage;
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Sisa plafon total departemen Anda bulan ini tidak mencukupi. Sisa plafon departemen: Rp ' . number_format(max(0, $sisaDept), 0, ',', '.')
                    ]);
                    return;
                }
            }
        }

        // File upload validation
        if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Berkas bukti nota/struk wajib diunggah.']);
            return;
        }

        $file = $_FILES['receipt'];

        // 1. Size boundary limit: 10MB max
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Ukuran berkas nota melebihi batas maksimal 10MB.']);
            return;
        }

        // 2. Server-side MIME validation using PHP finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            echo json_encode(['success' => false, 'message' => 'Format berkas tidak valid. Hanya JPG, PNG, atau PDF asli yang diperbolehkan.']);
            return;
        }

        // 3. Save physical file securely in secure receipts directory
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (empty($ext)) {
            $ext = ($mimeType === 'application/pdf') ? 'pdf' : (($mimeType === 'image/png') ? 'png' : 'jpg');
        }
        $safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $this->uploadDir . $safeFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan berkas nota di server.']);
            return;
        }

        try {
            // Generate UUID
            $claimId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmt = $this->db->prepare("
                INSERT INTO employee_reimbursement_claims (id, user_id, category, amount, description, receipt_path, status)
                VALUES (:id, :user_id, :category, :amount, :description, :receipt_path, 'pending')
            ");
            $stmt->execute([
                'id' => $claimId,
                'user_id' => $userId,
                'category' => $category,
                'amount' => $amount,
                'description' => $description,
                'receipt_path' => $safeFilename
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Pengajuan reimbursement berhasil dikirim! Status: PENDING.',
                'amount_formatted' => 'Rp ' . number_format($amount, 0, ',', '.')
            ]);

        } catch (Exception $e) {
            // Clean up uploaded file if DB insert fails
            if (file_exists($destination)) {
                unlink($destination);
            }
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan basis data: ' . $e->getMessage()]);
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
     * Approve a reimbursement claim (HR Operations & Hiring Manager)
     */
    public function approve() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr_ops', 'hiring_manager', 'admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Otoritas manajemen diperlukan.']);
            return;
        }

        $claimId = $_POST['claim_id'] ?? '';

        if (empty($claimId)) {
            echo json_encode(['success' => false, 'message' => 'ID klaim tidak boleh kosong.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM employee_reimbursement_claims WHERE id = :id");
            $stmt->execute(['id' => $claimId]);
            $claim = $stmt->fetch();

            if (!$claim) {
                echo json_encode(['success' => false, 'message' => 'Klaim reimbursement tidak ditemukan.']);
                return;
            }

            if ($claim['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Klaim reimbursement ini sudah diproses sebelumnya.']);
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
                $stmtTarget->execute(['id' => $claim['user_id']]);
                $targetDeptId = $stmtTarget->fetchColumn();

                $allowedDepts = $this->getDescendantDepartmentIds($this->db, $managerDeptId);
                if (!in_array($targetDeptId, $allowedDepts)) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda hanya dapat memproses pengajuan dari staf fungsional departemen Anda sendiri.']);
                    return;
                }
            }

            // Begin Atomic Transaction
            $this->db->beginTransaction();

            $update = $this->db->prepare("UPDATE employee_reimbursement_claims SET status = 'approved' WHERE id = :id");
            $update->execute(['id' => $claimId]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Klaim reimbursement disetujui dan siap dicairkan pada siklus penggajian payroll berikutnya.']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal menyetujui klaim: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a reimbursement claim with a custom reason (HR Operations & Hiring Manager)
     */
    public function reject() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr_ops', 'hiring_manager', 'admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Otoritas manajemen diperlukan.']);
            return;
        }

        $claimId = $_POST['claim_id'] ?? '';
        $rejectionReason = $_POST['rejection_reason'] ?? '';

        if (empty($claimId)) {
            echo json_encode(['success' => false, 'message' => 'ID klaim tidak boleh kosong.']);
            return;
        }

        if (empty($rejectionReason)) {
            echo json_encode(['success' => false, 'message' => 'Alasan penolakan reimbursement wajib diisi.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM employee_reimbursement_claims WHERE id = :id");
            $stmt->execute(['id' => $claimId]);
            $claim = $stmt->fetch();

            if (!$claim) {
                echo json_encode(['success' => false, 'message' => 'Klaim reimbursement tidak ditemukan.']);
                return;
            }

            if ($claim['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Klaim reimbursement ini sudah diproses sebelumnya.']);
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
                $stmtTarget->execute(['id' => $claim['user_id']]);
                $targetDeptId = $stmtTarget->fetchColumn();

                $allowedDepts = $this->getDescendantDepartmentIds($this->db, $managerDeptId);
                if (!in_array($targetDeptId, $allowedDepts)) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda hanya dapat memproses pengajuan dari staf fungsional departemen Anda sendiri.']);
                    return;
                }
            }

            // Begin Atomic Transaction
            $this->db->beginTransaction();

            $update = $this->db->prepare("
                UPDATE employee_reimbursement_claims 
                SET status = 'rejected', rejection_reason = :reason 
                WHERE id = :id
            ");
            $update->execute([
                'id' => $claimId,
                'reason' => $rejectionReason
            ]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Klaim reimbursement ditolak. Alasan penolakan dikirimkan ke portal karyawan.']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal menolak klaim: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel and delete a pending reimbursement claim (Employee ESS Portal)
     */
    public function cancel() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda harus login sebagai Karyawan.']);
            return;
        }

        $claimId = $_POST['claim_id'] ?? '';
        $userId = $_SESSION['user_id'];

        if (empty($claimId)) {
            echo json_encode(['success' => false, 'message' => 'ID klaim tidak boleh kosong.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM employee_reimbursement_claims WHERE id = :id");
            $stmt->execute(['id' => $claimId]);
            $claim = $stmt->fetch();

            if (!$claim) {
                echo json_encode(['success' => false, 'message' => 'Klaim tidak ditemukan.']);
                return;
            }

            if ($claim['user_id'] !== $userId) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki hak untuk membatalkan pengajuan ini.']);
                return;
            }

            if ($claim['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Pengajuan sudah diproses sehingga tidak bisa dibatalkan.']);
                return;
            }

            // Begin Atomic Transaction
            $this->db->beginTransaction();

            $delete = $this->db->prepare("DELETE FROM employee_reimbursement_claims WHERE id = :id AND user_id = :user_id");
            $delete->execute([
                'id' => $claimId,
                'user_id' => $userId
            ]);

            $this->db->commit();

            // Clear physical receipt file
            $filePath = $this->uploadDir . $claim['receipt_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            echo json_encode(['success' => true, 'message' => 'Klaim reimbursement berhasil dibatalkan.']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan basis data: ' . $e->getMessage()]);
        }
    }

    /**
     * Stream uploaded receipt documents securely with ownership & role validation
     */
    public function viewReceipt() {
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
            echo 'Berkas bukti nota tidak ditemukan di server.';
            return;
        }

        try {
            // Find owner user ID from database
            $stmt = $this->db->prepare("SELECT user_id FROM employee_reimbursement_claims WHERE receipt_path = :path");
            $stmt->execute(['path' => $fileName]);
            $ownerId = $stmt->fetchColumn();

            // Whitelist standard/seeded dummy claims or standard illustrations
            $seededFiles = [
                '2fa9e6c2ac88d49481052926b2bd6abd.jpg',
                '3f4f3f57a86cfb9eda545d583da2ea7d.jpg',
                'f525b68cc28e65eadf0dd8d36d99e262.jpg',
                'keychron.jpg',
                'kacamata.jpg',
                'tol.jpg',
                'makan.jpg',
                'mata.jpg'
            ];
            $isSeeded = in_array($fileName, $seededFiles);

            // Authorization: Only the owner employee OR hr_ops OR superadmin OR admin OR manager fungsional is allowed to view
            $userRole = $_SESSION['role'] ?? '';
            $isAuthorized = ($userRole === 'hr_ops' || $userRole === 'superadmin' || $userRole === 'admin' || $_SESSION['user_id'] === $ownerId || $isSeeded);

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
                header('HTTP/1.1 403 Forbidden');
                $content = renderView('pages/errors/403');
                $page = 'error_403';
                require __DIR__ . '/../../resources/views/layouts/guest.php';
                return;
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
