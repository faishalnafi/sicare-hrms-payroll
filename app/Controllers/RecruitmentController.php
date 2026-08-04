<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class RecruitmentController {

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
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            exit;
        }

        if ($requireCsrf) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid. Silakan muat ulang halaman.']);
                exit;
            }
        }

        return $effectiveRole;
    }

    /**
     * Store interview schedule (Recruiter only)
     */
    public function storeInterview() {
        $this->checkAuth(['recruiter', 'superadmin'], true);
        header('Content-Type: application/json');

        $appId = trim($_POST['application_id'] ?? '');
        $interviewerId = trim($_POST['interviewer_id'] ?? '');
        $type = trim($_POST['interview_type'] ?? 'online');
        $date = trim($_POST['interview_date'] ?? '');
        $time = trim($_POST['interview_time'] ?? '');
        $link = trim($_POST['location_or_link'] ?? '');

        if (empty($appId) || empty($interviewerId) || empty($date) || empty($time) || empty($link)) {
            echo json_encode(['success' => false, 'message' => 'Mohon lengkapi semua kolom jadwal wawancara.']);
            return;
        }

        try {
            $db = $this->getDb();
            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // Insert schedule
            $stmt = $db->prepare("
                INSERT INTO job_interviews (id, application_id, interviewer_id, interview_type, interview_date, interview_time, location_or_link, candidate_status, manager_decision)
                VALUES (:id, :app_id, :interviewer_id, :type, :date, :time, :link, 'pending', 'pending')
            ");
            $stmt->execute([
                'id' => $id,
                'app_id' => $appId,
                'interviewer_id' => $interviewerId,
                'type' => $type,
                'date' => $date,
                'time' => $time,
                'link' => $link
            ]);

            // Update application status
            $stmtApp = $db->prepare("UPDATE job_applications SET status = 'interview', updated_at = NOW() WHERE id = :app_id");
            $stmtApp->execute(['app_id' => $appId]);

            echo json_encode(['success' => true, 'message' => 'Jadwal wawancara berhasil dikirim ke kandidat!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Respond to interview invitation (Candidate only)
     */
    public function respondInterview() {
        $this->checkAuth(['candidate', 'superadmin'], true);
        header('Content-Type: application/json');

        $interviewId = trim($_POST['interview_id'] ?? '');
        $action = trim($_POST['action'] ?? '');
        $notes = trim($_POST['candidate_notes'] ?? '');

        if (empty($interviewId) || !in_array($action, ['accept', 'reschedule'])) {
            echo json_encode(['success' => false, 'message' => 'Aksi respon tidak valid.']);
            return;
        }

        try {
            $db = $this->getDb();
            $status = ($action === 'accept') ? 'accepted' : 'reschedule_requested';

            $stmt = $db->prepare("
                UPDATE job_interviews SET
                    candidate_status = :status,
                    candidate_notes = :notes,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'status' => $status,
                'notes' => $notes !== '' ? $notes : null,
                'id' => $interviewId
            ]);

            $msg = ($action === 'accept') ? 'Jadwal wawancara berhasil dikonfirmasi!' : 'Permintaan reschedule berhasil dikirim.';
            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Submit interview evaluation (Hiring Manager / Manager only)
     */
    public function submitEvaluation() {
        $this->checkAuth(['manager', 'hiring_manager', 'superadmin'], true);
        header('Content-Type: application/json');

        $interviewId = trim($_POST['interview_id'] ?? '');
        $scoreTech = (int)($_POST['score_technical'] ?? 0);
        $scoreProb = (int)($_POST['score_problem_solving'] ?? 0);
        $scoreComm = (int)($_POST['score_communication'] ?? 0);
        $scoreCult = (int)($_POST['score_cultural_fit'] ?? 0);
        
        $feedback = trim($_POST['feedback'] ?? '');
        $decision = trim($_POST['decision'] ?? '');

        // Calculate average score if individual scores provided
        $score = (int)($_POST['score'] ?? 0);
        if ($score <= 0 && ($scoreTech > 0 || $scoreProb > 0 || $scoreComm > 0 || $scoreCult > 0)) {
            $score = (int)round(($scoreTech + $scoreProb + $scoreComm + $scoreCult) / 4 * 20); // convert 1-5 to 20-100 scale
        }

        if (empty($interviewId) || $score < 1 || $score > 100 || empty($feedback) || !in_array($decision, ['pass', 'fail', 'hold'])) {
            echo json_encode(['success' => false, 'message' => 'Mohon isi nilai rubrik (1-5/1-100), umpan balik, dan rekomendasi keputusan secara valid.']);
            return;
        }

        try {
            $db = $this->getDb();
            
            // Get interview details to know the application_id
            $stmtInt = $db->prepare("SELECT application_id FROM job_interviews WHERE id = :id");
            $stmtInt->execute(['id' => $interviewId]);
            $appId = $stmtInt->fetchColumn();

            if (!$appId) {
                echo json_encode(['success' => false, 'message' => 'Sesi wawancara tidak ditemukan.']);
                return;
            }

            // Update interview evaluation
            $stmt = $db->prepare("
                UPDATE job_interviews SET
                    manager_score = :score,
                    score_technical = NULLIF(:score_tech, 0),
                    score_problem_solving = NULLIF(:score_prob, 0),
                    score_communication = NULLIF(:score_comm, 0),
                    score_cultural_fit = NULLIF(:score_cult, 0),
                    manager_feedback = :feedback,
                    manager_decision = :decision,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'score' => $score,
                'score_tech' => $scoreTech,
                'score_prob' => $scoreProb,
                'score_comm' => $scoreComm,
                'score_cult' => $scoreCult,
                'feedback' => $feedback,
                'decision' => $decision,
                'id' => $interviewId
            ]);

            // Update application stage based on decision
            if ($decision === 'pass') {
                $appStatus = 'offering';
                $msg = 'Penilaian disimpan. Kandidat lulus & masuk tahap Offering!';
            } elseif ($decision === 'fail') {
                $appStatus = 'rejected';
                $msg = 'Penilaian disimpan. Kandidat tidak lulus seleksi wawancara.';
            } else {
                $appStatus = 'interview';
                $msg = 'Penilaian disimpan. Kandidat dalam pertimbangan (Hold).';
            }

            $stmtApp = $db->prepare("UPDATE job_applications SET status = :status, updated_at = NOW() WHERE id = :app_id");
            $stmtApp->execute([
                'status' => $appStatus,
                'app_id' => $appId
            ]);

            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Send job offer / contract (Recruiter only)
     */
    public function sendOffer() {
        $this->checkAuth(['recruiter', 'superadmin'], true);
        header('Content-Type: application/json');

        $appId = trim($_POST['application_id'] ?? '');
        $salary = (float)($_POST['offered_salary'] ?? 0);

        if (empty($appId) || $salary <= 0) {
            echo json_encode(['success' => false, 'message' => 'Masukkan nominal gaji penawaran yang valid (> 0).']);
            return;
        }

        $letterPath = null;
        if (isset($_FILES['offering_letter_file']) && $_FILES['offering_letter_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['offering_letter_file'];
            if ($file['size'] > 10485760) { // 10MB
                echo json_encode(['success' => false, 'message' => 'Ukuran file Offering Letter maksimal 10MB.']);
                return;
            }
            $uploadDir = __DIR__ . '/../../public/uploads/offerings/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (empty($ext)) $ext = 'pdf';
            $filename = 'offer_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $letterPath = '/uploads/offerings/' . $filename;
            }
        }

        if (empty($letterPath)) {
            echo json_encode(['success' => false, 'message' => 'File Offering Letter (PDF/DOCX) wajib diunggah.']);
            return;
        }

        try {
            $db = $this->getDb();
            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // Check if offer already exists for this application, if so update/replace it
            $stmtCheck = $db->prepare("SELECT id FROM job_offers WHERE application_id = :app_id");
            $stmtCheck->execute(['app_id' => $appId]);
            $existingOfferId = $stmtCheck->fetchColumn();

            if ($existingOfferId) {
                $stmt = $db->prepare("
                    UPDATE job_offers SET
                        offering_letter_path = :letter,
                        offered_salary = :salary,
                        status = 'sent',
                        candidate_signature = NULL,
                        signed_at = NULL,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'letter' => $letterPath,
                    'salary' => $salary,
                    'id' => $existingOfferId
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO job_offers (id, application_id, offering_letter_path, offered_salary, status)
                    VALUES (:id, :app_id, :letter, :salary, 'sent')
                ");
                $stmt->execute([
                    'id' => $id,
                    'app_id' => $appId,
                    'letter' => $letterPath,
                    'salary' => $salary
                ]);
            }

            // Ensure application is set to offering stage
            $stmtApp = $db->prepare("UPDATE job_applications SET status = 'offering', updated_at = NOW() WHERE id = :app_id");
            $stmtApp->execute(['app_id' => $appId]);

            echo json_encode(['success' => true, 'message' => 'Offering Letter berhasil diterbitkan & dikirim ke kandidat!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Respond to job offer (Candidate only)
     */
    public function respondOffer() {
        $this->checkAuth(['candidate', 'superadmin'], true);
        header('Content-Type: application/json');

        $offerId = trim($_POST['offer_id'] ?? '');
        $action = trim($_POST['action'] ?? '');
        $signature = trim($_POST['signature'] ?? '');

        if (empty($offerId) || !in_array($action, ['sign', 'reject'])) {
            echo json_encode(['success' => false, 'message' => 'Aksi respon tidak valid.']);
            return;
        }

        if ($action === 'sign' && empty($signature)) {
            echo json_encode(['success' => false, 'message' => 'Tanda tangan digital wajib diisi.']);
            return;
        }

        try {
            $db = $this->getDb();

            // Get offer details
            $stmtOffer = $db->prepare("SELECT application_id FROM job_offers WHERE id = :id");
            $stmtOffer->execute(['id' => $offerId]);
            $appId = $stmtOffer->fetchColumn();

            if (!$appId) {
                echo json_encode(['success' => false, 'message' => 'Data penawaran tidak ditemukan.']);
                return;
            }

            if ($action === 'sign') {
                // Update offer to signed
                $stmt = $db->prepare("
                    UPDATE job_offers SET
                        status = 'signed',
                        candidate_signature = :sig,
                        signed_at = NOW(),
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'sig' => $signature,
                    'id' => $offerId
                ]);

                // Update application to hired
                $stmtApp = $db->prepare("UPDATE job_applications SET status = 'hired', updated_at = NOW() WHERE id = :app_id");
                $stmtApp->execute(['app_id' => $appId]);

                echo json_encode(['success' => true, 'message' => 'Kontrak berhasil ditandatangani! Selamat bergabung dengan tim kami.']);
            } else {
                // Update offer to rejected
                $stmt = $db->prepare("
                    UPDATE job_offers SET
                        status = 'rejected',
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $offerId]);

                echo json_encode(['success' => true, 'message' => 'Penawaran kerja berhasil ditolak.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Submit candidate onboarding wizard form data and files (Candidate & Employee)
     */
    public function submitOnboardingWizard() {
        $this->checkAuth(['candidate', 'employee', 'superadmin'], true);
        header('Content-Type: application/json');

        $userId = $_SESSION['user_id'] ?? '';
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'Identitas pengguna tidak ditemukan.']);
            return;
        }

        try {
            $db = $this->getDb();

            $storageDir = __DIR__ . '/../../storage/secured_storage/onboarding/';
            if (!file_exists($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
            $fileFields = ['ktp_file', 'kk_file', 'npwp_file', 'bank_book_file'];
            $uploadedPaths = [];

            foreach ($fileFields as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $fileTmp = $_FILES[$field]['tmp_name'];
                    $fileSize = $_FILES[$field]['size'];

                    if ($fileSize > 10 * 1024 * 1024) {
                        echo json_encode(['success' => false, 'message' => "Ukuran berkas $field melebihi batas maksimal 10MB."]);
                        return;
                    }

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $fileTmp);
                    finfo_close($finfo);

                    if (!in_array($mime, $allowedMimes)) {
                        echo json_encode(['success' => false, 'message' => "Format berkas $field harus berupa PDF, JPG, atau PNG."]);
                        return;
                    }

                    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                    $newFilename = $field . '_' . bin2hex(random_bytes(12)) . '.' . $ext;
                    $destination = $storageDir . $newFilename;

                    if (move_uploaded_file($fileTmp, $destination)) {
                        $uploadedPaths[$field] = $newFilename;
                    }
                }
            }

            // Extract form inputs
            $ktpNik = trim($_POST['ktp_nik'] ?? '');
            $namaKtp = trim($_POST['nama_sesuai_ktp'] ?? '');
            $alamatKtp = trim($_POST['alamat_ktp'] ?? '');
            $alamatDomisili = trim($_POST['alamat_domisili'] ?? '');
            $noTelepon = trim($_POST['no_telepon'] ?? '');
            $bankName = trim($_POST['bank_name'] ?? '');
            $bankAccount = trim($_POST['bank_account_number'] ?? '');
            $npwpNumber = trim($_POST['npwp_number'] ?? '');
            $bpjsTk = trim($_POST['bpjs_tk'] ?? '');
            $bpjsKes = trim($_POST['bpjs_kes'] ?? '');
            $tanggalLahir = trim($_POST['tanggal_lahir'] ?? '');
            $statusPernikahan = trim($_POST['status_pernikahan'] ?? '');
            $jenisKelamin = trim($_POST['jenis_kelamin'] ?? '');
            $emergName = trim($_POST['emergency_contact_name'] ?? '');
            $emergPhone = trim($_POST['emergency_contact_phone'] ?? '');
            $emergRel = trim($_POST['emergency_contact_relation'] ?? '');

            // Build dynamic update query
            $sql = "UPDATE users SET 
                        ktp_nik = :ktp_nik,
                        nama_sesuai_ktp = :nama_ktp,
                        alamat_ktp = :alamat_ktp,
                        alamat_domisili = :alamat_domisili,
                        no_telepon = :no_telepon,
                        bank_name = :bank_name,
                        bank_account_number = :bank_account,
                        npwp_number = :npwp_number,
                        bpjs_tk = :bpjs_tk,
                        bpjs_kes = :bpjs_kes,
                        tanggal_lahir = :tanggal_lahir,
                        status_pernikahan = :status_pernikahan,
                        jenis_kelamin = :jenis_kelamin,
                        emergency_contact_name = :emerg_name,
                        emergency_contact_phone = :emerg_phone,
                        emergency_contact_relation = :emerg_rel,
                        onboarding_status = 'submitted',
                        updated_at = NOW()";

            $params = [
                'ktp_nik' => $ktpNik,
                'nama_ktp' => $namaKtp,
                'alamat_ktp' => $alamatKtp,
                'alamat_domisili' => $alamatDomisili,
                'no_telepon' => $noTelepon,
                'bank_name' => $bankName,
                'bank_account' => $bankAccount,
                'npwp_number' => $npwpNumber,
                'bpjs_tk' => $bpjsTk,
                'bpjs_kes' => $bpjsKes,
                'tanggal_lahir' => $tanggalLahir,
                'status_pernikahan' => $statusPernikahan,
                'jenis_kelamin' => $jenisKelamin,
                'emerg_name' => $emergName,
                'emerg_phone' => $emergPhone,
                'emerg_rel' => $emergRel,
                'id' => $userId
            ];

            foreach ($fileFields as $field) {
                if (isset($uploadedPaths[$field])) {
                    $sql .= ", $field = :$field";
                    $params[$field] = $uploadedPaths[$field];
                }
            }

            $sql .= " WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode([
                'success' => true,
                'message' => 'Wizard Onboarding berhasil dikirim! Berkas Anda sedang dalam proses verifikasi oleh tim HR Operations.'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data onboarding: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve candidate onboarding & activate employee status (HR Ops, Admin, Superadmin)
     */
    public function approveOnboarding() {
        $this->checkAuth(['hr_ops', 'admin', 'superadmin'], true);
        header('Content-Type: application/json');

        $targetUserId = trim($_POST['target_user_id'] ?? '');
        $departmentId = trim($_POST['department_id'] ?? '');
        $jobTitle = trim($_POST['job_title'] ?? '');
        $baseSalary = (float)($_POST['base_salary'] ?? 0);
        $employeeId = trim($_POST['employee_id'] ?? '');

        if (empty($targetUserId)) {
            echo json_encode(['success' => false, 'message' => 'ID kandidat tidak boleh kosong.']);
            return;
        }

        try {
            $db = $this->getDb();
            $db->beginTransaction();

            // Fetch target user
            $stmtUser = $db->prepare("SELECT first_name, last_name, email, employee_id FROM users WHERE id = :id FOR UPDATE");
            $stmtUser->execute(['id' => $targetUserId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Data pengguna kandidat tidak ditemukan.']);
                return;
            }

            // Generate Staff ID if empty
            if (empty($employeeId)) {
                if (!empty($user['employee_id'])) {
                    $employeeId = $user['employee_id'];
                } else {
                    $year = date('Y');
                    $randNum = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $employeeId = "EM-$year-$randNum";
                }
            }

            // Get Employee Role ID (or fallback to '3' / 'employee')
            $roleId = '3';
            $stmtRoleId = $db->query("SELECT id FROM roles WHERE role_name = 'employee' LIMIT 1");
            $rId = $stmtRoleId->fetchColumn();
            if ($rId) {
                $roleId = $rId;
            }

            // Update user to employee
            $stmtUpdate = $db->prepare("
                UPDATE users SET
                    role = 'employee',
                    role_id = :role_id,
                    employee_id = :emp_id,
                    department_id = COALESCE(NULLIF(:dept_id, ''), department_id),
                    job_title = COALESCE(NULLIF(:job_title, ''), job_title),
                    base_salary = CASE WHEN :base_salary > 0 THEN :base_salary ELSE base_salary END,
                    onboarding_status = 'completed',
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                'role_id' => $roleId,
                'emp_id' => $employeeId,
                'dept_id' => $departmentId,
                'job_title' => $jobTitle,
                'base_salary' => $baseSalary,
                'id' => $targetUserId
            ]);

            // Add to employment history
            $stmtHist = $db->prepare("
                INSERT INTO employment_history (id, user_id, department_id, job_title, status, start_date, created_at)
                VALUES (UUID(), :user_id, NULLIF(:dept_id, ''), NULLIF(:job_title, ''), 'ACTIVE', CURRENT_DATE(), NOW())
            ");
            $stmtHist->execute([
                'user_id' => $targetUserId,
                'dept_id' => $departmentId,
                'job_title' => $jobTitle
            ]);

            // Insert audit log
            $actorName = $_SESSION['name'] ?? 'HR Ops';
            $candidateName = trim($user['first_name'] . ' ' . $user['last_name']);
            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, details, ip_address, created_at)
                VALUES (UUID(), :actor_id, 'APPROVE_ONBOARDING', :details, :ip, NOW())
            ");
            $stmtAudit->execute([
                'actor_id' => $_SESSION['user_id'],
                'details' => "$actorName menyetujui onboarding kandidat $candidateName ($employeeId) dan mengaktifkan status karyawan.",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Onboarding $candidateName berhasil disetujui! Akun telah resmi menjadi Karyawan Aktif dengan Staff ID $employeeId."
            ]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal memproses verifikasi onboarding: ' . $e->getMessage()]);
        }
    }
}

