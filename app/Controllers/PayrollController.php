<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class PayrollController {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Check if user is logged in and has HR Ops, Superadmin, or Admin role.
     */
    private function checkAccess() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
            exit;
        }
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['hr_ops', 'superadmin', 'admin'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Otoritas manajemen diperlukan.']);
            exit;
        }
    }

    /**
     * Helper to read a global setting.
     */
    private function getSetting(string $key, string $default): string {
        try {
            $stmt = $this->db->prepare("SELECT `value` FROM global_settings WHERE `key` = :key LIMIT 1");
            $stmt->execute(['key' => $key]);
            $val = $stmt->fetchColumn();
            return ($val !== false) ? $val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Calculate actual working days in a month dynamically.
     * Excludes weekends (based on global_settings.weekly_holidays) and company holidays.
     */
    private function getWorkingDaysCount(string $monthYear): int {
        $parts = explode('-', $monthYear);
        if (count($parts) !== 2) {
            return 22; // fallback
        }
        $month = intval($parts[0]);
        $year = intval($parts[1]);

        // Fetch weekly holidays configuration (default: Sat,Sun)
        $weeklyHolidays = ['Sat', 'Sun'];
        $val = $this->getSetting('weekly_holidays', 'Sat,Sun');
        if (!empty($val)) {
            $weeklyHolidays = array_filter(array_map('trim', explode(',', $val)));
        }

        // Fetch company holidays for the month
        $stmtHolidays = $this->db->prepare("
            SELECT holiday_date 
            FROM company_holidays 
            WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ?
        ");
        $stmtHolidays->execute([$month, $year]);
        $companyHolidays = $stmtHolidays->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $daysInMonth = (int)date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
        $workingDays = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $ts = strtotime($dateStr);
            $dayName = date('D', $ts);

            $isWeeklyHoliday = in_array($dayName, $weeklyHolidays);
            $isCompanyHoliday = in_array($dateStr, $companyHolidays);

            if (!$isWeeklyHoliday && !$isCompanyHoliday) {
                $workingDays++;
            }
        }

        return $workingDays > 0 ? $workingDays : 22;
    }

    /**
     * Calculate unpaid leave days for a user in a target month.
     * Only counts days that are actual working days.
     */
    private function getUnpaidLeaveDays(string $userId, string $monthYear): int {
        $parts = explode('-', $monthYear);
        if (count($parts) !== 2) {
            return 0;
        }
        $month = intval($parts[0]);
        $year = intval($parts[1]);

        $firstDayOfMonth = sprintf('%04d-%02d-01', $year, $month);
        $lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));

        // Get approved unpaid leaves overlapping this month
        $stmt = $this->db->prepare("
            SELECT start_date, end_date 
            FROM employee_leave_requests
            WHERE user_id = :user_id
              AND status = 'approved'
              AND LOWER(leave_type) = 'cuti tidak dibayar'
              AND start_date <= :last_day
              AND end_date >= :first_day
        ");
        $stmt->execute([
            'user_id' => $userId,
            'first_day' => $firstDayOfMonth,
            'last_day' => $lastDayOfMonth
        ]);
        $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $unpaidDays = 0;

        // Fetch weekly holidays configuration
        $weeklyHolidays = ['Sat', 'Sun'];
        $val = $this->getSetting('weekly_holidays', 'Sat,Sun');
        if (!empty($val)) {
            $weeklyHolidays = array_filter(array_map('trim', explode(',', $val)));
        }

        // Fetch company holidays
        $stmtHolidays = $this->db->prepare("
            SELECT holiday_date 
            FROM company_holidays 
            WHERE MONTH(holiday_date) = ? AND YEAR(holiday_date) = ?
        ");
        $stmtHolidays->execute([$month, $year]);
        $companyHolidays = $stmtHolidays->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($leaves as $l) {
            $start = max(strtotime($firstDayOfMonth), strtotime($l['start_date']));
            $end = min(strtotime($lastDayOfMonth), strtotime($l['end_date']));

            for ($curr = $start; $curr <= $end; $curr = strtotime('+1 day', $curr)) {
                $currDateStr = date('Y-m-d', $curr);
                $dayName = date('D', $curr);

                $isWeeklyHoliday = in_array($dayName, $weeklyHolidays);
                $isCompanyHoliday = in_array($currDateStr, $companyHolidays);

                if (!$isWeeklyHoliday && !$isCompanyHoliday) {
                    $unpaidDays++;
                }
            }
        }

        return $unpaidDays;
    }

    /**
     * Calculate absent (alpa) days for a user in a target month.
     */
    private function getAlpaDays(string $userId, string $monthYear): int {
        $parts = explode('-', $monthYear);
        if (count($parts) !== 2) {
            return 0;
        }
        $month = intval($parts[0]);
        $year = intval($parts[1]);

        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM employee_attendance
            WHERE user_id = :user_id
              AND status = 'alpa'
              AND MONTH(attendance_date) = :month
              AND YEAR(attendance_date) = :year
        ");
        $stmt->execute([
            'user_id' => $userId,
            'month' => $month,
            'year' => $year
        ]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Calculate late (terlambat) days for a user in a target month.
     */
    private function getLateDays(string $userId, string $monthYear): int {
        $parts = explode('-', $monthYear);
        if (count($parts) !== 2) {
            return 0;
        }
        $month = intval($parts[0]);
        $year = intval($parts[1]);

        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM employee_attendance
            WHERE user_id = :user_id
              AND status = 'terlambat'
              AND MONTH(attendance_date) = :month
              AND YEAR(attendance_date) = :year
        ");
        $stmt->execute([
            'user_id' => $userId,
            'month' => $month,
            'year' => $year
        ]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Centralized calculator for payroll components.
     */
    private function calculateComponents(string $userId, string $monthYear, float $baseSalary, float $reimbursement, float $bonus, float $overtime = 0.0, float $otherDeduction = 0.0): array {
        $pct = (float)$this->getSetting('payroll_tunj_jabatan_pct', '15');
        $cap = (float)$this->getSetting('payroll_tunj_jabatan_cap', '2500000');
        $transport = (float)$this->getSetting('payroll_tunj_transport', '1500000');
        $komunikasi = (float)$this->getSetting('payroll_tunj_komunikasi', '500000');

        $workingDays = $this->getWorkingDaysCount($monthYear);
        $unpaidDays = $this->getUnpaidLeaveDays($userId, $monthYear);
        $alpaDays = $this->getAlpaDays($userId, $monthYear);
        $lateDays = $this->getLateDays($userId, $monthYear);
        
        $unpaidDeduction = 0.0;
        if ($workingDays > 0 && $unpaidDays > 0) {
            $unpaidDeduction = ($baseSalary / $workingDays) * $unpaidDays;
        }

        $alpaDeduction = 0.0;
        if ($workingDays > 0 && $alpaDays > 0) {
            $alpaDeduction = ($baseSalary / $workingDays) * $alpaDays;
        }

        $latePenalty = (float)$this->getSetting('payroll_late_deduction', '50000');
        $lateDeduction = $lateDays * $latePenalty;

        $tunj_jabatan = min($baseSalary * ($pct / 100), $cap);
        $tunj_transport = $transport;
        $tunj_komunikasi = $komunikasi;

        $bpjs_tk_pct = (float)$this->getSetting('payroll_bpjs_tk_pct', '2');
        $bpjs_kes_pct = (float)$this->getSetting('payroll_bpjs_kes_pct', '1');
        $pph21_pct = (float)$this->getSetting('payroll_pph21_pct', '2.5');

        $bpjs_tk = $baseSalary * ($bpjs_tk_pct / 100);
        $bpjs_kes = $baseSalary * ($bpjs_kes_pct / 100);

        // PPh21 calculation based on Gross Income before tax and employee BPJS deduction
        $gross = ($baseSalary - $unpaidDeduction - $alpaDeduction - $lateDeduction) + $tunj_jabatan + $tunj_transport + $tunj_komunikasi + $reimbursement + $bonus + $overtime;
        $gross = max(0.0, $gross);

        $pph21 = $gross * ($pph21_pct / 100);
        $net_salary = $gross - ($bpjs_tk + $bpjs_kes + $pph21 + $otherDeduction);

        return [
            'base_salary' => $baseSalary,
            'tunj_jabatan' => $tunj_jabatan,
            'tunj_transport_makan' => $tunj_transport,
            'tunj_komunikasi' => $tunj_komunikasi,
            'bonus' => $bonus,
            'reimbursement' => $reimbursement,
            'overtime' => $overtime,
            'bpjs_tk' => $bpjs_tk,
            'bpjs_kes' => $bpjs_kes,
            'pph21' => $pph21,
            'other_deduction' => $otherDeduction,
            'net_salary' => $net_salary,
            'unpaid_leave_days' => $unpaidDays,
            'unpaid_leave_deduction' => $unpaidDeduction,
            'alpa_days' => $alpaDays,
            'alpa_deduction' => $alpaDeduction,
            'late_days' => $lateDays,
            'late_deduction' => $lateDeduction,
            'working_days' => $workingDays
        ];
    }


    /**
     * GET /hrops/payroll/list
     */
    public function list() {
        $this->checkAccess();
        header('Content-Type: application/json');

        $monthYear = $_GET['month_year'] ?? date('m-Y');

        try {
            $stmt = $this->db->prepare("
                SELECT p.*, 
                       u.first_name, u.last_name, u.employee_id, u.job_title,
                       u.bank_name, u.bank_account_number, u.npwp_number,
                       d.name AS department_name
                FROM employee_payroll p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE p.month_year = :month_year
                ORDER BY u.first_name ASC
            ");
            $stmt->execute(['month_year' => $monthYear]);
            $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Compute dynamic details (like unpaid leaves) on the fly for UI slip preview
            foreach ($payrolls as &$p) {
                $workingDays = $this->getWorkingDaysCount($p['month_year']);
                $unpaidDays = $this->getUnpaidLeaveDays($p['user_id'], $p['month_year']);
                $alpaDays = $this->getAlpaDays($p['user_id'], $p['month_year']);
                $lateDays = $this->getLateDays($p['user_id'], $p['month_year']);

                $parts = explode('-', $p['month_year']);
                $month = intval($parts[0] ?? date('m'));
                $year = intval($parts[1] ?? date('Y'));
                $stmtPresent = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM employee_attendance 
                    WHERE user_id = :user_id 
                      AND status IN ('tepat waktu', 'awal', 'terlambat')
                      AND MONTH(attendance_date) = :month 
                      AND YEAR(attendance_date) = :year
                ");
                $stmtPresent->execute([
                    'user_id' => $p['user_id'],
                    'month' => $month,
                    'year' => $year
                ]);
                $presentDays = (int)$stmtPresent->fetchColumn();

                $unpaidDeduction = 0.0;
                if ($workingDays > 0 && $unpaidDays > 0) {
                    $unpaidDeduction = ($p['base_salary'] / $workingDays) * $unpaidDays;
                }

                $alpaDeduction = 0.0;
                if ($workingDays > 0 && $alpaDays > 0) {
                    $alpaDeduction = ($p['base_salary'] / $workingDays) * $alpaDays;
                }

                $latePenalty = (float)$this->getSetting('payroll_late_deduction', '50000');
                $lateDeduction = $lateDays * $latePenalty;

                $p['working_days'] = $workingDays;
                $p['present_days'] = $presentDays;
                $p['unpaid_leave_days'] = $unpaidDays;
                $p['unpaid_leave_deduction'] = $unpaidDeduction;
                $p['alpa_days'] = $alpaDays;
                $p['alpa_deduction'] = $alpaDeduction;
                $p['late_days'] = $lateDays;
                $p['late_deduction'] = $lateDeduction;
            }

            echo json_encode(['success' => true, 'data' => $payrolls]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memuat data: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /hrops/payroll/generate
     */
    public function generate() {
        $this->checkAccess();
        header('Content-Type: application/json');

        $monthYear = $_POST['month_year'] ?? '';
        if (empty($monthYear)) {
            echo json_encode(['success' => false, 'message' => 'Bulan dan tahun wajib dipilih.']);
            return;
        }

        try {
            // Get all active employees (excluding superadmin, admin, recruiter, executive, candidate)
            $stmtUsers = $this->db->query("
                SELECT id, base_salary 
                FROM users 
                WHERE role = 'employee' AND is_deleted = 0
            ");
            $employees = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

            if (empty($employees)) {
                echo json_encode(['success' => false, 'message' => 'Tidak ditemukan karyawan dengan role employee untuk diproses.']);
                return;
            }

            $this->db->beginTransaction();

            $insertStmt = $this->db->prepare("
                INSERT INTO employee_payroll (
                    id, user_id, month_year, base_salary, tunj_jabatan, 
                    tunj_transport_makan, tunj_komunikasi, bonus, reimbursement, overtime,
                    bpjs_tk, bpjs_kes, pph21, other_deduction, net_salary, status
                ) VALUES (
                    :id, :user_id, :month_year, :base_salary, :tunj_jabatan, 
                    :tunj_transport_makan, :tunj_komunikasi, :bonus, :reimbursement, :overtime, 
                    :bpjs_tk, :bpjs_kes, :pph21, :other_deduction, :net_salary, 'Draft'
                )
            ");

            $updateStmt = $this->db->prepare("
                UPDATE employee_payroll 
                SET base_salary = :base_salary,
                    tunj_jabatan = :tunj_jabatan,
                    tunj_transport_makan = :tunj_transport_makan,
                    tunj_komunikasi = :tunj_komunikasi,
                    reimbursement = :reimbursement,
                    bonus = :bonus,
                    overtime = :overtime,
                    bpjs_tk = :bpjs_tk,
                    bpjs_kes = :bpjs_kes,
                    pph21 = :pph21,
                    other_deduction = :other_deduction,
                    net_salary = :net_salary,
                    updated_at = NOW()
                WHERE id = :id
            ");

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;

            foreach ($employees as $emp) {
                $userId = $emp['id'];
                $baseSalary = (float)$emp['base_salary'];

                // Calculate approved reimbursement claims for the target month
                $stmtReimb = $this->db->prepare("
                    SELECT SUM(amount) 
                    FROM employee_reimbursement_claims
                    WHERE user_id = :user_id
                      AND status = 'approved'
                      AND DATE_FORMAT(created_at, '%m-%Y') = :month_year
                ");
                $stmtReimb->execute([
                    'user_id' => $userId,
                    'month_year' => $monthYear
                ]);
                $reimbursement = (float)($stmtReimb->fetchColumn() ?: 0.0);

                // Check if payroll already exists for this employee in this month
                $stmtExisting = $this->db->prepare("SELECT id, status, bonus, overtime, other_deduction FROM employee_payroll WHERE user_id = ? AND month_year = ? LIMIT 1");
                $stmtExisting->execute([$userId, $monthYear]);
                $existing = $stmtExisting->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    if ($existing['status'] !== 'Draft') {
                        $skippedCount++;
                        continue; // Do not touch Approved or Paid records
                    }
                    
                    // Keep the existing manual edits so we don't overwrite them
                    $bonus = (float)$existing['bonus'];
                    $overtime = (float)($existing['overtime'] ?? 0.0);
                    $otherDeduction = (float)($existing['other_deduction'] ?? 0.0);

                    // Recalculate components
                    $calc = $this->calculateComponents($userId, $monthYear, $baseSalary, $reimbursement, $bonus, $overtime, $otherDeduction);

                    $updateStmt->execute([
                        'id' => $existing['id'],
                        'base_salary' => $calc['base_salary'],
                        'tunj_jabatan' => $calc['tunj_jabatan'],
                        'tunj_transport_makan' => $calc['tunj_transport_makan'],
                        'tunj_komunikasi' => $calc['tunj_komunikasi'],
                        'reimbursement' => $calc['reimbursement'],
                        'bonus' => $calc['bonus'],
                        'overtime' => $calc['overtime'],
                        'bpjs_tk' => $calc['bpjs_tk'],
                        'bpjs_kes' => $calc['bpjs_kes'],
                        'pph21' => $calc['pph21'],
                        'other_deduction' => $calc['other_deduction'],
                        'net_salary' => $calc['net_salary']
                    ]);
                    $updatedCount++;
                } else {
                    // Calculate components with 0 bonus, 0 overtime, 0 other deductions
                    $calc = $this->calculateComponents($userId, $monthYear, $baseSalary, $reimbursement, 0.0, 0.0, 0.0);

                    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );

                    $insertStmt->execute([
                        'id' => $uuid,
                        'user_id' => $userId,
                        'month_year' => $monthYear,
                        'base_salary' => $calc['base_salary'],
                        'tunj_jabatan' => $calc['tunj_jabatan'],
                        'tunj_transport_makan' => $calc['tunj_transport_makan'],
                        'tunj_komunikasi' => $calc['tunj_komunikasi'],
                        'bonus' => $calc['bonus'],
                        'reimbursement' => $calc['reimbursement'],
                        'overtime' => $calc['overtime'],
                        'bpjs_tk' => $calc['bpjs_tk'],
                        'bpjs_kes' => $calc['bpjs_kes'],
                        'pph21' => $calc['pph21'],
                        'other_deduction' => $calc['other_deduction'],
                        'net_salary' => $calc['net_salary']
                    ]);
                    $createdCount++;
                }
            }

            $this->db->commit();

            $msg = "Proses selesai.";
            if ($createdCount > 0) $msg .= " Berhasil membuat {$createdCount} draf payroll baru.";
            if ($updatedCount > 0) $msg .= " Berhasil memperbarui {$updatedCount} draf payroll dengan kebijakan terbaru (nilai bonus dipertahankan).";
            if ($skippedCount > 0) $msg .= " Mengabaikan {$skippedCount} data yang sudah berstatus Approved/Paid.";

            echo json_encode([
                'success' => true, 
                'message' => $msg
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /hrops/payroll/update-bonus
     */
    public function updateBonus() {
        $this->checkAccess();
        header('Content-Type: application/json');

        $payrollId = $_POST['id'] ?? '';
        $bonus = $_POST['bonus'] ?? '0';

        if (empty($payrollId)) {
            echo json_encode(['success' => false, 'message' => 'ID payroll tidak boleh kosong.']);
            return;
        }

        // sanitize bonus input
        $bonus = (float)str_replace([',', '.'], '', $bonus);
        if ($bonus < 0) {
            echo json_encode(['success' => false, 'message' => 'Bonus harus bernilai positif atau nol.']);
            return;
        }

        try {
            // Load existing payroll
            $stmt = $this->db->prepare("SELECT * FROM employee_payroll WHERE id = ?");
            $stmt->execute([$payrollId]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$p) {
                echo json_encode(['success' => false, 'message' => 'Catatan payroll tidak ditemukan.']);
                return;
            }

            if ($p['status'] !== 'Draft') {
                echo json_encode(['success' => false, 'message' => 'Bonus hanya dapat diedit pada draf payroll berstatus Draft.']);
                return;
            }

            // Recalculate components
            $calc = $this->calculateComponents(
                $p['user_id'], 
                $p['month_year'], 
                (float)$p['base_salary'], 
                (float)$p['reimbursement'], 
                $bonus,
                (float)($p['overtime'] ?? 0.0),
                (float)($p['other_deduction'] ?? 0.0)
            );

            $this->db->beginTransaction();

            $updateStmt = $this->db->prepare("
                UPDATE employee_payroll 
                SET bonus = :bonus,
                    pph21 = :pph21,
                    net_salary = :net_salary,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([
                'id' => $payrollId,
                'bonus' => $calc['bonus'],
                'pph21' => $calc['pph21'],
                'net_salary' => $calc['net_salary']
            ]);

            $this->db->commit();

            echo json_encode([
                'success' => true, 
                'message' => 'Nilai bonus berhasil diperbarui dan payroll dihitung ulang.',
                'pph21_formatted' => 'Rp ' . number_format($calc['pph21'], 0, ',', '.'),
                'net_salary_formatted' => 'Rp ' . number_format($calc['net_salary'], 0, ',', '.')
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan basis data: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /hrops/payroll/update-status
     */
    public function updateStatus() {
        $this->checkAccess();
        header('Content-Type: application/json');

        $ids = $_POST['ids'] ?? [];
        $status = $_POST['status'] ?? ''; // Approved or Paid

        if (empty($ids) || !is_array($ids)) {
            $singleId = $_POST['id'] ?? '';
            if (!empty($singleId)) {
                $ids = [$singleId];
            }
        }

        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'ID payroll tidak boleh kosong.']);
            return;
        }

        if (!in_array($status, ['Approved', 'Paid'])) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid. Hanya Approved atau Paid yang diperbolehkan.']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $query = "UPDATE employee_payroll SET status = ?, updated_at = NOW()";
            $params = [$status];

            if ($status === 'Paid') {
                $query = "UPDATE employee_payroll SET status = ?, payment_date = NOW(), updated_at = NOW()";
            }

            $query .= " WHERE id IN ($placeholders)";
            $params = array_merge($params, $ids);

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            $this->db->commit();

            $msg = $status === 'Paid' ? 'Status payroll berhasil diubah menjadi Paid (Telah Dibayar)!' : 'Status payroll berhasil diubah menjadi Approved (Disetujui)!';
            echo json_encode(['success' => true, 'message' => $msg]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan basis data: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /hrops/payroll/delete
     */
    public function delete() {
        $this->checkAccess();
        header('Content-Type: application/json');

        $payrollId = $_POST['id'] ?? '';

        if (empty($payrollId)) {
            echo json_encode(['success' => false, 'message' => 'ID draf payroll tidak boleh kosong.']);
            return;
        }

        try {
            // Verify status
            $stmtCheck = $this->db->prepare("SELECT status FROM employee_payroll WHERE id = ?");
            $stmtCheck->execute([$payrollId]);
            $status = $stmtCheck->fetchColumn();

            if ($status === false) {
                echo json_encode(['success' => false, 'message' => 'Data draf payroll tidak ditemukan.']);
                return;
            }

            if ($status !== 'Draft') {
                echo json_encode(['success' => false, 'message' => 'Hanya draf payroll berstatus Draft yang dapat dihapus.']);
                return;
            }

            $this->db->beginTransaction();
            $stmt = $this->db->prepare("DELETE FROM employee_payroll WHERE id = ?");
            $stmt->execute([$payrollId]);
            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Draf payroll berhasil dihapus.']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus draf payroll: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /hrops/payroll/update-overtime
     */
    public function updateOvertime() {
        $this->checkAccess();
        header('Content-Type: application/json');

        $payrollId = $_POST['id'] ?? '';
        $overtime = $_POST['overtime'] ?? '0';

        if (empty($payrollId)) {
            echo json_encode(['success' => false, 'message' => 'ID payroll tidak boleh kosong.']);
            return;
        }

        // sanitize overtime input
        $overtime = (float)str_replace([',', '.'], '', $overtime);
        if ($overtime < 0) {
            echo json_encode(['success' => false, 'message' => 'Lemburan harus bernilai positif atau nol.']);
            return;
        }

        try {
            // Load existing payroll
            $stmt = $this->db->prepare("SELECT * FROM employee_payroll WHERE id = ?");
            $stmt->execute([$payrollId]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$p) {
                echo json_encode(['success' => false, 'message' => 'Catatan payroll tidak ditemukan.']);
                return;
            }

            if ($p['status'] !== 'Draft') {
                echo json_encode(['success' => false, 'message' => 'Lemburan hanya dapat diedit pada draf payroll berstatus Draft.']);
                return;
            }

            // Recalculate components
            $calc = $this->calculateComponents(
                $p['user_id'], 
                $p['month_year'], 
                (float)$p['base_salary'], 
                (float)$p['reimbursement'], 
                (float)$p['bonus'], 
                $overtime, 
                (float)($p['other_deduction'] ?? 0.0)
            );

            $this->db->beginTransaction();

            $updateStmt = $this->db->prepare("
                UPDATE employee_payroll 
                SET overtime = :overtime,
                    pph21 = :pph21,
                    net_salary = :net_salary,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([
                'id' => $payrollId,
                'overtime' => $calc['overtime'],
                'pph21' => $calc['pph21'],
                'net_salary' => $calc['net_salary']
            ]);

            $this->db->commit();

            echo json_encode([
                'success' => true, 
                'message' => 'Nilai lemburan berhasil diperbarui dan payroll dihitung ulang.',
                'pph21_formatted' => 'Rp ' . number_format($calc['pph21'], 0, ',', '.'),
                'net_salary_formatted' => 'Rp ' . number_format($calc['net_salary'], 0, ',', '.')
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan basis data: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /hrops/payroll/update-other-deduction
     */
    public function updateOtherDeduction() {
        $this->checkAccess();
        header('Content-Type: application/json');

        $payrollId = $_POST['id'] ?? '';
        $otherDeduction = $_POST['other_deduction'] ?? '0';

        if (empty($payrollId)) {
            echo json_encode(['success' => false, 'message' => 'ID payroll tidak boleh kosong.']);
            return;
        }

        // sanitize other deduction input
        $otherDeduction = (float)str_replace([',', '.'], '', $otherDeduction);
        if ($otherDeduction < 0) {
            echo json_encode(['success' => false, 'message' => 'Potongan lainnya harus bernilai positif atau nol.']);
            return;
        }

        try {
            // Load existing payroll
            $stmt = $this->db->prepare("SELECT * FROM employee_payroll WHERE id = ?");
            $stmt->execute([$payrollId]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$p) {
                echo json_encode(['success' => false, 'message' => 'Catatan payroll tidak ditemukan.']);
                return;
            }

            if ($p['status'] !== 'Draft') {
                echo json_encode(['success' => false, 'message' => 'Potongan lainnya hanya dapat diedit pada draf payroll berstatus Draft.']);
                return;
            }

            // Recalculate components
            $calc = $this->calculateComponents(
                $p['user_id'], 
                $p['month_year'], 
                (float)$p['base_salary'], 
                (float)$p['reimbursement'], 
                (float)$p['bonus'], 
                (float)($p['overtime'] ?? 0.0), 
                $otherDeduction
            );

            $this->db->beginTransaction();

            $updateStmt = $this->db->prepare("
                UPDATE employee_payroll 
                SET other_deduction = :other_deduction,
                    pph21 = :pph21,
                    net_salary = :net_salary,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([
                'id' => $payrollId,
                'other_deduction' => $calc['other_deduction'],
                'pph21' => $calc['pph21'],
                'net_salary' => $calc['net_salary']
            ]);

            $this->db->commit();

            echo json_encode([
                'success' => true, 
                'message' => 'Nilai potongan lainnya berhasil diperbarui dan payroll dihitung ulang.',
                'pph21_formatted' => 'Rp ' . number_format($calc['pph21'], 0, ',', '.'),
                'net_salary_formatted' => 'Rp ' . number_format($calc['net_salary'], 0, ',', '.')
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan basis data: ' . $e->getMessage()]);
        }
    }
}
