<?php

namespace App\Controllers;

use App\Config\Database;
use Exception;
use PDO;

class CorrectionController {

    private $db;
    private $uploadDir;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDir = __DIR__ . '/../../storage/secured_storage/';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Submit a correction request (Employee Portal)
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
        $field = $_POST['field'] ?? '';
        $newValue = $_POST['new_value'] ?? '';
        $reason = $_POST['reason'] ?? '';

        // Whitelist of columns to prevent unauthorized DB updates
        $allowedFields = [
            'ktp_nik', 'nama_sesuai_ktp', 'alamat_ktp',
            'bank_name', 'bank_account_number',
            'npwp_number', 'bpjs_tk', 'bpjs_kes',
            'tanggal_lahir', 'status_pernikahan', 'jenis_kelamin'
        ];

        if (!in_array($field, $allowedFields)) {
            echo json_encode(['success' => false, 'message' => 'Kolom data tidak valid atau dikunci.']);
            return;
        }

        if (empty($newValue) || empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi.']);
            return;
        }

        // File upload validation
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Berkas bukti pendukung wajib diunggah.']);
            return;
        }

        $file = $_FILES['file'];

        // 1. Boundary check: 10MB maximum
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Ukuran berkas melebihi batas maksimal 10MB.']);
            return;
        }

        // 2. Server-side MIME validation via finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            echo json_encode(['success' => false, 'message' => 'Format berkas tidak didukung. Unggah scan PDF, JPG, atau PNG asli.']);
            return;
        }

        // 3. Save file securely (privat storage)
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $this->uploadDir . $safeFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan berkas ke server.']);
            return;
        }

        try {
            // Fetch old value
            $stmt = $this->db->prepare("SELECT $field FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $oldValue = $stmt->fetchColumn();

            // Insert correction request
            $requestId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmt = $this->db->prepare("
                INSERT INTO employee_data_correction_requests (id, user_id, category, field, old_value, new_value, reason, file_path, status)
                VALUES (:id, :user_id, :category, :field, :old_value, :new_value, :reason, :file_path, 'pending')
            ");
            $stmt->execute([
                'id' => $requestId,
                'user_id' => $userId,
                'category' => $category,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'reason' => $reason,
                'file_path' => $safeFilename
            ]);

            echo json_encode([
                'success' => true, 
                'message' => 'Pengajuan perbaikan data berhasil dikirim! Status: PENDING.',
                'field_label' => $this->getFieldLabel($field),
                'new_value' => $newValue
            ]);

        } catch (Exception $e) {
            // Clean up uploaded file if DB fails
            if (file_exists($destination)) {
                unlink($destination);
            }
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve a correction request (HR Operations)
     */
    public function approve() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr_ops') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda harus login sebagai HR Operations.']);
            return;
        }

        $requestId = $_POST['request_id'] ?? '';

        if (empty($requestId)) {
            echo json_encode(['success' => false, 'message' => 'ID pengajuan tidak boleh kosong.']);
            return;
        }

        try {
            // Fetch request details
            $stmt = $this->db->prepare("SELECT * FROM employee_data_correction_requests WHERE id = :id");
            $stmt->execute(['id' => $requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Pengajuan tidak ditemukan.']);
                return;
            }

            if ($request['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Pengajuan ini sudah diproses sebelumnya.']);
                return;
            }

            $field = $request['field'];
            $allowedFields = [
                'ktp_nik', 'nama_sesuai_ktp', 'alamat_ktp',
                'bank_name', 'bank_account_number',
                'npwp_number', 'bpjs_tk', 'bpjs_kes',
                'tanggal_lahir', 'status_pernikahan', 'jenis_kelamin'
            ];

            if (!in_array($field, $allowedFields)) {
                echo json_encode(['success' => false, 'message' => 'Kolom data pengajuan tidak valid.']);
                return;
            }

            // Begin Transaction - Atomic Database updates
            $this->db->beginTransaction();

            // 1. Update user locked field
            $updateUser = $this->db->prepare("UPDATE users SET $field = :new_value WHERE id = :user_id");
            $updateUser->execute([
                'new_value' => $request['new_value'],
                'user_id' => $request['user_id']
            ]);

            // Auto-split nama_sesuai_ktp into first_name and last_name
            if ($field === 'nama_sesuai_ktp') {
                $fullName = trim($request['new_value']);
                $parts = explode(' ', $fullName);
                $lastName = null;
                $firstName = $fullName;

                if (count($parts) > 1) {
                    $lastName = array_pop($parts);
                    $firstName = implode(' ', $parts);
                }

                $updateNames = $this->db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name WHERE id = :user_id");
                $updateNames->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'user_id' => $request['user_id']
                ]);
            }

            // 2. Set status of request to approved
            $updateRequest = $this->db->prepare("UPDATE employee_data_correction_requests SET status = 'approved' WHERE id = :id");
            $updateRequest->execute(['id' => $requestId]);

            // Commit transaction
            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Koreksi data disetujui dan berhasil diperbarui secara permanen di database.']);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal memproses persetujuan: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a correction request (HR Operations)
     */
    public function reject() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr_ops') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda harus login sebagai HR Operations.']);
            return;
        }

        $requestId = $_POST['request_id'] ?? '';
        $rejectionReason = $_POST['rejection_reason'] ?? '';

        if (empty($requestId)) {
            echo json_encode(['success' => false, 'message' => 'ID pengajuan tidak boleh kosong.']);
            return;
        }

        if (empty($rejectionReason)) {
            echo json_encode(['success' => false, 'message' => 'Alasan penolakan wajib diisi.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM employee_data_correction_requests WHERE id = :id");
            $stmt->execute(['id' => $requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Pengajuan tidak ditemukan.']);
                return;
            }

            if ($request['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Pengajuan ini sudah diproses sebelumnya.']);
                return;
            }

            // Begin Transaction - Atomic rejection update
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE employee_data_correction_requests 
                SET status = 'rejected', rejection_reason = :rejection_reason 
                WHERE id = :id
            ");
            $stmt->execute([
                'rejection_reason' => $rejectionReason,
                'id' => $requestId
            ]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Pengajuan koreksi data ditolak. Penjelasan penolakan dikirim ke karyawan.']);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Gagal memproses penolakan: ' . $e->getMessage()]);
        }
    }

    /**
     * Serve uploaded documents securely with authentication
     */
    public function viewFile() {
        session_start();

        if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Akses Ditolak: Silakan masuk terlebih dahulu.';
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
            // Check in public/dummy folder or fallback if it's seeded dummy
            $fallbackDir = __DIR__ . '/../../public/dummy/';
            if (file_exists($fallbackDir . $fileName)) {
                $filePath = $fallbackDir . $fileName;
            } else {
                header('HTTP/1.1 404 Not Found');
                echo 'Berkas tidak ditemukan.';
                return;
            }
        }

        // Enforce role-based access control or owner check
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT user_id FROM employee_data_correction_requests WHERE file_path = :file_path");
        $stmt->execute(['file_path' => $fileName]);
        $ownerId = $stmt->fetchColumn();

        if ($_SESSION['role'] !== 'hr_ops' && $_SESSION['user_id'] !== $ownerId) {
            // Fallback check: if the requested file doesn't have an owner in the table (could be static/dummy),
            // let employee see it for illustration, but restrict strict files
            if ($ownerId !== false) {
                header('HTTP/1.1 403 Forbidden');
                echo 'Akses Ditolak: Anda tidak memiliki izin untuk melihat berkas ini.';
                return;
            }
        }

        // Get MIME Type using server finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=86400');
        if (isset($_GET['download']) && $_GET['download'] === '1') {
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
        } else {
            header('Content-Disposition: inline; filename="' . $fileName . '"');
        }
        readfile($filePath);
        exit;
    }

    private function getFieldLabel($field) {
        $labels = [
            'ktp_nik' => 'NIK (Nomor Induk Kependudukan)',
            'nama_sesuai_ktp' => 'Nama Lengkap Sesuai KTP',
            'alamat_ktp' => 'Alamat Lengkap Sesuai KTP',
            'bank_name' => 'Nama Bank Penerima',
            'bank_account_number' => 'Nomor Rekening',
            'npwp_number' => 'NPWP (Nomor Pokok Wajib Pajak)',
            'bpjs_tk' => 'BPJS Ketenagakerjaan',
            'bpjs_kes' => 'BPJS Kesehatan',
            'tanggal_lahir' => 'Tanggal Lahir',
            'status_pernikahan' => 'Status Pernikahan',
            'jenis_kelamin' => 'Jenis Kelamin'
        ];
        return $labels[$field] ?? $field;
    }
}
