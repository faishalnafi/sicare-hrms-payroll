<?php

namespace App\Controllers;

use App\Config\Database;
use Exception;
use PDO;

/**
 * AttendanceController
 * Clock-In / Clock-Out / History (Employee ESS) and HR Ops log views / corrections.
 * All office config (location, hours, WFA) is read live from global_settings table.
 */
class AttendanceController {

    private $db;
    private $cfg;  // associative array of global_settings

    // Fallback defaults if global_settings table doesn't exist yet
    private const DEFAULTS = [
        'office_lat'         => -6.2297,
        'office_lng'         => 106.8164,
        'office_radius_m'    => 150,
        'home_radius_m'      => 100,
        'work_start_time'    => '08:00',
        'work_end_time'      => '17:00',
        'grace_period_min'   => 10,
        'office_wifi_prefix' => '192.168.10.',
        'wfa_allowed'        => 'true',
        'wfa_days'           => '',
    ];

    public function __construct() {
        $this->db  = Database::getInstance()->getConnection();
        $this->autoMigrate();
        $this->cfg = $this->loadSettings();
    }

    private function autoMigrate() {
        try {
            // Users table columns
            $stmtCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'home_latitude'");
            if (!$stmtCol->fetch()) {
                $this->db->exec("ALTER TABLE users ADD COLUMN home_latitude DECIMAL(10,7) DEFAULT NULL AFTER alamat_domisili");
                $this->db->exec("ALTER TABLE users ADD COLUMN home_longitude DECIMAL(10,7) DEFAULT NULL AFTER home_latitude");
            }
            
            // home_radius_m
            $stmtRad = $this->db->query("SELECT 1 FROM global_settings WHERE `key` = 'home_radius_m'");
            if (!$stmtRad->fetch()) {
                $this->db->exec("INSERT INTO global_settings (`key`, `value`, `label`, `group`) VALUES ('home_radius_m', '100', 'Radius WFH (meter)', 'attendance')");
            }

            // company_holidays table
            $this->db->exec("CREATE TABLE IF NOT EXISTS `company_holidays` (
                `id` CHAR(36) NOT NULL,
                `holiday_date` DATE NOT NULL,
                `description` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_holiday_date` (`holiday_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // employment_contracts table
            try {
                $this->db->exec("CREATE TABLE IF NOT EXISTS `employment_contracts` (
                    `id` CHAR(36) NOT NULL,
                    `user_id` CHAR(36) NOT NULL,
                    `contract_number` VARCHAR(50) NOT NULL,
                    `contract_type` VARCHAR(50) NOT NULL COMMENT 'PKWT, PKWTT, INTERN, PROBATION',
                    `start_date` DATE NOT NULL,
                    `end_date` DATE DEFAULT NULL COMMENT 'NULL for permanent / PKWTT',
                    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' COMMENT 'ACTIVE, EXPIRED, TERMINATED',
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `contract_number` (`contract_number`),
                    KEY `user_id` (`user_id`),
                    CONSTRAINT `employment_contracts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch (Exception $ex) {
                $this->db->exec("CREATE TABLE IF NOT EXISTS `employment_contracts` (
                    `id` CHAR(36) NOT NULL,
                    `user_id` CHAR(36) NOT NULL,
                    `contract_number` VARCHAR(50) NOT NULL,
                    `contract_type` VARCHAR(50) NOT NULL COMMENT 'PKWT, PKWTT, INTERN, PROBATION',
                    `start_date` DATE NOT NULL,
                    `end_date` DATE DEFAULT NULL COMMENT 'NULL for permanent / PKWTT',
                    `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' COMMENT 'ACTIVE, EXPIRED, TERMINATED',
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `contract_number` (`contract_number`),
                    KEY `user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }

            // weekly_holidays default
            $stmtWk = $this->db->query("SELECT 1 FROM global_settings WHERE `key` = 'weekly_holidays'");
            if (!$stmtWk->fetch()) {
                $this->db->exec("INSERT INTO global_settings (`key`, `value`, `label`, `group`) VALUES ('weekly_holidays', 'Sat,Sun', 'Hari Libur Mingguan', 'attendance')");
            }

            // employee_attendance table columns
            $stmtAttCol = $this->db->query("SHOW COLUMNS FROM employee_attendance LIKE 'clock_out_status'");
            if (!$stmtAttCol->fetch()) {
                $this->db->exec("ALTER TABLE employee_attendance ADD COLUMN clock_out_status VARCHAR(30) DEFAULT NULL AFTER status");
            }

            // work_mode_out column
            $stmtWmOut = $this->db->query("SHOW COLUMNS FROM employee_attendance LIKE 'work_mode_out'");
            if (!$stmtWmOut->fetch()) {
                $this->db->exec("ALTER TABLE employee_attendance ADD COLUMN work_mode_out VARCHAR(10) DEFAULT NULL AFTER work_mode");
            }

            // checkout_grace_period_min default
            $stmtCoGrace = $this->db->query("SELECT 1 FROM global_settings WHERE `key` = 'checkout_grace_period_min'");
            if (!$stmtCoGrace->fetch()) {
                $this->db->exec("INSERT INTO global_settings (`key`, `value`, `label`, `group`) VALUES ('checkout_grace_period_min', '15', 'Toleransi Pulang Lambat (menit)', 'attendance')");
            }
        } catch (Exception $e) {
            // fail silently
        }
    }

    // ── load settings from DB ─────────────────────────────────────────────
    private function loadSettings(): array {
        try {
            $rows = $this->db->query("SELECT `key`, `value` FROM global_settings")->fetchAll();
            $s = self::DEFAULTS;
            foreach ($rows as $r) { $s[$r['key']] = $r['value']; }
            return $s;
        } catch (Exception $e) {
            return self::DEFAULTS;
        }
    }

    // Shorthand getters
    private function officeLat(): float   { return (float)$this->cfg['office_lat']; }
    private function officeLng(): float   { return (float)$this->cfg['office_lng']; }
    private function radiusM(): int       { return (int)$this->cfg['office_radius_m']; }
    private function homeRadiusM(): int   { return (int)($this->cfg['home_radius_m'] ?? 100); }
    private function workStart(): string  { return $this->cfg['work_start_time'] ?? '08:00'; }
    private function workEnd(): string    { return $this->cfg['work_end_time']   ?? '17:00'; }
    private function graceMins(): int     { return (int)($this->cfg['grace_period_min'] ?? 10); }
    private function wifiPrefix(): string { return $this->cfg['office_wifi_prefix'] ?? '192.168.10.'; }
    private function wfaAllowed(): bool   { return strtolower($this->cfg['wfa_allowed'] ?? 'true') === 'true'; }
    private function wfaDays(): array {
        $d = trim($this->cfg['wfa_days'] ?? '');
        return $d !== '' ? explode(',', $d) : [];
    }

    // =========================================================================
    // EMPLOYEE: Clock-In  POST /employee/attendance/clockin
    // =========================================================================
    public function clockIn() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $userId  = $_SESSION['user_id'];
        $today   = date('Y-m-d');
        $nowTime = date('H:i:s');

        // Fetch user home coordinates
        $userStmt = $this->db->prepare("SELECT home_latitude, home_longitude FROM users WHERE id = :uid LIMIT 1");
        $userStmt->execute(['uid' => $userId]);
        $user = $userStmt->fetch();
        $homeLat = $user && $user['home_latitude'] !== null ? (float)$user['home_latitude'] : null;
        $homeLng = $user && $user['home_longitude'] !== null ? (float)$user['home_longitude'] : null;

        // Already clocked in today?
        $stmt = $this->db->prepare("SELECT id, clock_in, clock_out, status FROM employee_attendance WHERE user_id = :uid AND attendance_date = :date LIMIT 1");
        $stmt->execute(['uid' => $userId, 'date' => $today]);
        $existing = $stmt->fetch();

        if ($existing && $existing['status'] === 'sakit/izin') {
            echo json_encode(['success' => false, 'message' => 'Anda sedang dalam masa Sakit/Izin hari ini. Tidak dapat melakukan Clock-In.']);
            return;
        }

        if ($existing && $existing['clock_in'] !== null) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah Clock-In hari ini pada ' . date('H:i', strtotime($existing['clock_in'])) . '. Tidak bisa Clock-In dua kali.']);
            return;
        }

        // ── Location detection ────────────────────────────────────────────
        $lat    = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
        $lng    = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
        $ipAddr = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $distM  = null;
        $distHomeM = null;

        // Enforce GPS check for everyone
        if ($lat === null || $lng === null) {
            echo json_encode(['success' => false, 'message' => 'Gagal mendeteksi lokasi. Presensi wajib menggunakan GPS. Pastikan izin lokasi aktif pada perangkat Anda.']);
            return;
        }

        $method = str_starts_with($ipAddr, $this->wifiPrefix()) ? 'WIFI' : 'GPS';
        $distM  = $this->haversine($this->officeLat(), $this->officeLng(), $lat, $lng);
        if ($homeLat !== null && $homeLng !== null) {
            $distHomeM = $this->haversine($homeLat, $homeLng, $lat, $lng);
        }

        // Overlap Priority: WFH takes precedence over WFO
        if ($distHomeM !== null && $distHomeM <= $this->homeRadiusM()) {
            $workMode = 'WFH';
        } elseif ($distM <= $this->radiusM()) {
            $workMode = 'WFO';
        } else {
            // Outside both office and home radius — check WFA eligibility
            if (!$this->wfaAllowed()) {
                $homeMsg = $distHomeM !== null ? " dan luar radius rumah Anda (" . $this->formatDistance($distHomeM) . ", batas WFH: " . $this->formatDistance($this->homeRadiusM()) . ")" : "";
                echo json_encode([
                    'success' => false,
                    'message' => "Anda berada di luar radius kantor (" . $this->formatDistance($distM) . ", batas WFO: " . $this->formatDistance($this->radiusM()) . "){$homeMsg}, sedangkan Work From Anywhere/Cabin/Home (WFA/WFC/WFH) tidak diizinkan saat ini. Clock-In ditolak."
                ]);
                return;
            }
            // Check WFA day restriction
            $wfaDays = $this->wfaDays();
            if (!empty($wfaDays)) {
                $todayCode = date('D'); // Mon, Tue, Wed…
                if (!in_array($todayCode, $wfaDays)) {
                    $dayTranslations = [
                        'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu',
                        'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu', 'Sun' => 'Minggu'
                    ];
                    $allowedIndo = array_map(fn($d) => $dayTranslations[$d] ?? $d, $wfaDays);
                    $allowed = implode(', ', $allowedIndo);
                    $todayIndo = $dayTranslations[$todayCode] ?? $todayCode;
                    $homeMsg = $distHomeM !== null ? " dan rumah Anda (" . $this->formatDistance($distHomeM) . ")" : "";
                    echo json_encode([
                        'success' => false,
                        'message' => "WFA/WFC/WFH hanya diizinkan pada hari: {$allowed}. Hari ini ({$todayIndo}) Anda berada di luar radius kantor (" . $this->formatDistance($distM) . "){$homeMsg}, sehingga Anda wajib bekerja dari Kantor (WFO) atau Rumah (WFH)."
                    ]);
                    return;
                }
            }
            $workMode = 'WFA';
        }

        // ── Determine attendance status with grace period ─────────────────
        [$startH, $startM] = array_map('intval', explode(':', $this->workStart()));
        $startMin    = $startH * 60 + $startM;
        $deadlineMin = $startMin + $this->graceMins();
        $nowMin      = (int)date('H') * 60 + (int)date('i');
        
        if ($nowMin < $startMin) {
            $status = 'awal';
        } else {
            $status = ($nowMin <= $deadlineMin) ? 'tepat waktu' : 'terlambat';
        }

        try {
            if ($existing) {
                $stmt = $this->db->prepare("
                    UPDATE employee_attendance
                    SET clock_in = :ci, status = :status, work_mode = :wm,
                        clock_in_latitude = :lat, clock_in_longitude = :lng,
                        location_method = :method, ip_address = :ip, updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute(['ci' => $nowTime, 'status' => $status, 'wm' => $workMode, 'lat' => $lat, 'lng' => $lng, 'method' => $method, 'ip' => $ipAddr, 'id' => $existing['id']]);
            } else {
                $id = $this->generateUuid();
                $stmt = $this->db->prepare("
                    INSERT INTO employee_attendance
                        (id, user_id, attendance_date, clock_in, status, work_mode,
                         clock_in_latitude, clock_in_longitude, location_method, ip_address)
                    VALUES (:id, :uid, :date, :ci, :status, :wm, :lat, :lng, :method, :ip)
                ");
                $stmt->execute(['id' => $id, 'uid' => $userId, 'date' => $today, 'ci' => $nowTime, 'status' => $status, 'wm' => $workMode, 'lat' => $lat, 'lng' => $lng, 'method' => $method, 'ip' => $ipAddr]);
            }

            $lateMsg = '';
            if ($status === 'terlambat') {
                $lateMin = $nowMin - ($startH * 60 + $startM);
                $lateMsg = " Terlambat {$lateMin} menit.";
            }
            
            $locationMsg = '';
            if ($workMode === 'WFA') {
                $locationMsg = " Mode WFA/WFC/WFH aktif (jarak " . $this->formatDistance($distM) . " dari kantor).";
            } elseif ($workMode === 'WFH') {
                $locationMsg = " Mode WFH aktif (jarak " . $this->formatDistance($distHomeM ?? 0) . " dari rumah).";
            } else {
                $locationMsg = " Mode WFO" . ($distM !== null ? " (jarak " . $this->formatDistance($distM) . ")" : " (WIFI Kantor)") . ".";
            }

            echo json_encode([
                'success'   => true,
                'message'   => 'Clock-In berhasil pada ' . date('H:i') . '.' . $lateMsg . $locationMsg,
                'status'    => $status,
                'work_mode' => $workMode,
                'dist_m'    => $workMode === 'WFH' ? $distHomeM : $distM,
                'clock_in'  => date('H:i'),
                'method'    => $method,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    // EMPLOYEE: Clock-Out  POST /employee/attendance/clockout
    // =========================================================================
    public function clockOut() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $userId  = $_SESSION['user_id'];
        $today   = date('Y-m-d');
        $nowTime = date('H:i:s');

        $stmt = $this->db->prepare("SELECT id, clock_in, clock_out, work_mode, clock_in_latitude, clock_in_longitude FROM employee_attendance WHERE user_id = :uid AND attendance_date = :date LIMIT 1");
        $stmt->execute(['uid' => $userId, 'date' => $today]);
        $existing = $stmt->fetch();

        if (!$existing || $existing['clock_in'] === null) {
            echo json_encode(['success' => false, 'message' => 'Belum ada Clock-In hari ini.']);
            return;
        }
        if ($existing['clock_out'] !== null) {
            echo json_encode(['success' => false, 'message' => 'Sudah Clock-Out pada ' . date('H:i', strtotime($existing['clock_out'])) . '.']);
            return;
        }

        $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
        $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
        $ipAddr = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Enforce GPS check for everyone at clock-out
        if ($lat === null || $lng === null) {
            echo json_encode(['success' => false, 'message' => 'Gagal mendeteksi lokasi. Clock-Out wajib menggunakan GPS. Pastikan izin lokasi aktif pada perangkat Anda.']);
            return;
        }

        // Fetch user home coordinates
        $userStmt = $this->db->prepare("SELECT home_latitude, home_longitude FROM users WHERE id = :uid LIMIT 1");
        $userStmt->execute(['uid' => $userId]);
        $user = $userStmt->fetch();
        $homeLat = $user && $user['home_latitude'] !== null ? (float)$user['home_latitude'] : null;
        $homeLng = $user && $user['home_longitude'] !== null ? (float)$user['home_longitude'] : null;

        $distM = $this->haversine($this->officeLat(), $this->officeLng(), $lat, $lng);
        $distHomeM = null;
        if ($homeLat !== null && $homeLng !== null) {
            $distHomeM = $this->haversine($homeLat, $homeLng, $lat, $lng);
        }

        // Determine clock-out mode with exact same precedence/rules
        $workModeOut = 'WFA';
        if ($distHomeM !== null && $distHomeM <= $this->homeRadiusM()) {
            $workModeOut = 'WFH';
        } elseif ($distM <= $this->radiusM()) {
            $workModeOut = 'WFO';
        }

        $workModeIn = $existing['work_mode'];
        $clockInLat = $existing['clock_in_latitude'] !== null ? (float)$existing['clock_in_latitude'] : null;
        $clockInLng = $existing['clock_in_longitude'] !== null ? (float)$existing['clock_in_longitude'] : null;

        // Perform location match validation! (Bypassed by request: allow clock-out from anywhere and record actual location)
        /*
        if ($workModeIn === 'WFO') {
            if ($workModeOut !== 'WFO') {
                echo json_encode([
                    'success' => false,
                    'message' => "Clock-Out ditolak. Anda melakukan Clock-In sebagai WFO (Kantor), tetapi lokasi Clock-Out Anda berada di luar radius kantor (" . $this->formatDistance($distM) . ", batas WFO: " . $this->formatDistance($this->radiusM()) . "). Anda harus Clock-Out di area Kantor."
                ]);
                return;
            }
        } elseif ($workModeIn === 'WFH') {
            if ($workModeOut !== 'WFH') {
                $distHomeStr = $distHomeM !== null ? " (" . $this->formatDistance($distHomeM) . ", batas WFH: " . $this->formatDistance($this->homeRadiusM()) . ")" : "";
                echo json_encode([
                    'success' => false,
                    'message' => "Clock-Out ditolak. Anda melakukan Clock-In sebagai WFH (Rumah), tetapi lokasi Clock-Out Anda berada di luar radius rumah Anda{$distHomeStr}. Anda harus Clock-Out di area Rumah."
                ]);
                return;
            }
        } elseif ($workModeIn === 'WFA') {
            // For WFA, they must stay in the same place! Let's check distance to their clock-in point
            if ($clockInLat !== null && $clockInLng !== null) {
                $distChangeM = $this->haversine($clockInLat, $clockInLng, $lat, $lng);
                if ($distChangeM > $this->radiusM()) { // limit using WFO radius (default 150m)
                    echo json_encode([
                        'success' => false,
                        'message' => "Clock-Out ditolak. Lokasi Clock-Out Anda berjarak " . $this->formatDistance($distChangeM) . " dari lokasi Clock-In Anda (maksimal perpindahan: " . $this->formatDistance($this->radiusM()) . "). Anda harus melakukan Clock-Out dari area tempat yang sama saat Anda Clock-In."
                    ]);
                    return;
                }
            }
        }
        */

        // Hitung status pulang (checkout status) via helper
        $coStatus = $this->resolveClockOutStatus($nowTime);

        try {
            $this->db->prepare("
                UPDATE employee_attendance
                SET clock_out = :co, clock_out_latitude = :lat, clock_out_longitude = :lng,
                    clock_out_status = :coStatus, work_mode_out = :wmo, ip_address = :ip, updated_at = NOW()
                WHERE id = :id
            ")->execute(['co' => $nowTime, 'lat' => $lat, 'lng' => $lng, 'coStatus' => $coStatus, 'wmo' => $workModeOut, 'ip' => $ipAddr, 'id' => $existing['id']]);

            $diffSec = strtotime($nowTime) - strtotime($existing['clock_in']);
            $diffH   = floor($diffSec / 3600);
            $diffM   = floor(($diffSec % 3600) / 60);

            $statusMsg = '';
            if ($coStatus === 'pulang lambat') {
                $statusMsg = " Pulang Lambat tercatat.";
            } elseif ($coStatus === 'pulang cepat') {
                $statusMsg = " Pulang Cepat tercatat.";
            }

            echo json_encode([
                'success'    => true,
                'message'    => "Clock-Out berhasil pada " . date('H:i') . ". Total jam kerja: {$diffH}j {$diffM}m." . $statusMsg,
                'clock_out'  => date('H:i'),
                'work_hours' => "{$diffH}j {$diffM}m",
                'work_mode_out' => $workModeOut,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    private function backfillAlpa(string $userId, ?string $targetMonth = null) {
        $today = date('Y-m-d');
        
        // 1. Update past days where user clocked in but never clocked out to 'tidak presensi pulang'
        try {
            $updateNoCheckoutStmt = $this->db->prepare("
                UPDATE employee_attendance 
                SET clock_out_status = 'tidak presensi pulang'
                WHERE user_id = :uid 
                  AND attendance_date < :today
                  AND clock_in IS NOT NULL 
                  AND clock_out IS NULL 
                  AND (clock_out_status IS NULL OR clock_out_status != 'tidak presensi pulang')
            ");
            $updateNoCheckoutStmt->execute(['uid' => $userId, 'today' => $today]);
        } catch (Exception $ex) {
            // fail silently
        }

        // Determine the employee's join/start date
        $userStmt = $this->db->prepare("SELECT DATE(created_at) as created_date FROM users WHERE id = :uid LIMIT 1");
        $userStmt->execute(['uid' => $userId]);
        $userCreatedDate = $userStmt->fetchColumn() ?: date('Y-m-d');
        
        $contractStmt = $this->db->prepare("SELECT start_date FROM employment_contracts WHERE user_id = :uid AND status = 'active' ORDER BY start_date ASC LIMIT 1");
        $contractStmt->execute(['uid' => $userId]);
        $contractStartDate = $contractStmt->fetchColumn();
        
        $joinDate = $contractStartDate ?: $userCreatedDate;
        
        // Effective start date is the maximum of the start of the target/current month and the employee's start/join date
        $startOfMonth = $targetMonth ? $targetMonth . '-01' : date('Y-m-01');
        $startDate = ($joinDate > $startOfMonth) ? $joinDate : $startOfMonth;
        
        // Get work end time
        $workEndTime = $this->workEnd(); // e.g. '17:00'
        $currentTime = date('H:i:s');
        
        // If current time is past work end time today, they are considered Alpa today (lewat dari jam clock-out/kerja dianggap alpa)
        $currentYearMonth = date('Y-m');
        if ($targetMonth && $targetMonth !== $currentYearMonth) {
            $endDate = date('Y-m-t', strtotime($startOfMonth));
            $maxEndDate = ($currentTime >= $workEndTime . ':00') ? $today : date('Y-m-d', strtotime('-1 day'));
            if ($endDate > $maxEndDate) {
                $endDate = $maxEndDate;
            }
        } else {
            $endDate = ($currentTime >= $workEndTime . ':00') ? $today : date('Y-m-d', strtotime('-1 day'));
        }
        
        // If effective start date is in the future relative to endDate, do nothing
        if ($startDate > $endDate) {
            return;
        }

        // Clean up any historical auto-generated alpa records that are before the effective start date (self-healing)
        $cleanupStmt = $this->db->prepare("
            DELETE FROM employee_attendance 
            WHERE user_id = :uid 
              AND status = 'alpa' 
              AND clock_in IS NULL 
              AND clock_out IS NULL 
              AND attendance_date < :start
        ");
        $cleanupStmt->execute(['uid' => $userId, 'start' => $startDate]);
        
        $weeklyHolidays = explode(',', $this->cfg['weekly_holidays'] ?? 'Sat,Sun');
        
        // Fetch all existing attendance records for this user in this range
        $stmt = $this->db->prepare("SELECT attendance_date, status, clock_in, clock_out FROM employee_attendance WHERE user_id = :uid AND attendance_date BETWEEN :s AND :e");
        $stmt->execute(['uid' => $userId, 's' => $startDate, 'e' => $endDate]);
        $existingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to a quick lookup array
        $existingMap = [];
        foreach ($existingRecords as $r) {
            $existingMap[$r['attendance_date']] = $r;
        }
        
        // Iterate through each date from $startDate to $endDate
        $current = strtotime($startDate);
        $last = strtotime($endDate);
        
        while ($current <= $last) {
            $dateStr = date('Y-m-d', $current);
            
            // Check if it's a holiday
            $dayName = date('D', $current);
            $isWeeklyHoliday = in_array($dayName, $weeklyHolidays);
            
            // Check company holidays
            $holidayStmt = $this->db->prepare("SELECT 1 FROM company_holidays WHERE holiday_date = :date LIMIT 1");
            $holidayStmt->execute(['date' => $dateStr]);
            $isCompanyHoliday = (bool)$holidayStmt->fetchColumn();
            
            $isHoliday = $isWeeklyHoliday || $isCompanyHoliday;
            
            if ($isHoliday) {
                // If it is a holiday, and there is an existing auto-generated alpa record, delete it!
                if (isset($existingMap[$dateStr])) {
                    $rec = $existingMap[$dateStr];
                    if ($rec['status'] === 'alpa' && $rec['clock_in'] === null && $rec['clock_out'] === null) {
                        $deleteStmt = $this->db->prepare("DELETE FROM employee_attendance WHERE user_id = :uid AND attendance_date = :date");
                        $deleteStmt->execute(['uid' => $userId, 'date' => $dateStr]);
                    }
                }
            } else {
                // It's a working day
                // Check if user has an approved leave request for this day
                $leaveStmt = $this->db->prepare("
                    SELECT leave_type FROM employee_leave_requests 
                    WHERE user_id = :uid AND status = 'approved' AND :date BETWEEN start_date AND end_date
                    LIMIT 1
                ");
                $leaveStmt->execute(['uid' => $userId, 'date' => $dateStr]);
                $approvedLeave = $leaveStmt->fetch();
                
                $status = $approvedLeave ? 'sakit/izin' : 'alpa';

                if (!isset($existingMap[$dateStr])) {
                    // Insert an auto-generated record
                    $id = $this->generateUuid();
                    $insertStmt = $this->db->prepare("
                        INSERT INTO employee_attendance 
                            (id, user_id, attendance_date, clock_in, clock_out, status, work_mode, location_method)
                        VALUES 
                            (:id, :uid, :date, NULL, NULL, :status, NULL, NULL)
                    ");
                    $insertStmt->execute([
                        'id' => $id,
                        'uid' => $userId,
                        'date' => $dateStr,
                        'status' => $status
                    ]);
                } else {
                    // If a record exists but has no clock_in (i.e. auto-generated or empty)
                    $rec = $existingMap[$dateStr];
                    if ($rec['clock_in'] === null) {
                        if ($rec['status'] !== $status) {
                            $updateStmt = $this->db->prepare("
                                UPDATE employee_attendance 
                                SET status = :status 
                                WHERE user_id = :uid AND attendance_date = :date AND clock_in IS NULL
                            ");
                            $updateStmt->execute(['status' => $status, 'uid' => $userId, 'date' => $dateStr]);
                        }
                    }
                }
            }
            
            $current = strtotime('+1 day', $current);
        }
    }

    private function backfillAllEmployees() {
        try {
            // Get all user IDs of role 'employee'
            $stmt = $this->db->query("SELECT id FROM users WHERE role = 'employee'");
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($userIds as $userId) {
                $this->backfillAlpa($userId);
            }
        } catch (Exception $e) {
            // fail silently
        }
    }

    // =========================================================================
    // EMPLOYEE: Get today's status and monthly history (called from view)
    // =========================================================================
    public function getEmployeeData(string $userId): array {
        // Backfill missing attendance records first
        $this->backfillAlpa($userId);

        $today      = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        $stmt = $this->db->prepare("SELECT * FROM employee_attendance WHERE user_id = :uid AND attendance_date = :date LIMIT 1");
        $stmt->execute(['uid' => $userId, 'date' => $today]);
        $todayRecord = $stmt->fetch() ?: null;

        $stmt = $this->db->prepare("SELECT * FROM employee_attendance WHERE user_id = :uid AND attendance_date BETWEEN :s AND :e ORDER BY attendance_date DESC");
        $stmt->execute(['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]);
        $monthlyRecords = $stmt->fetchAll();

        $countHadir = $countTerlambat = $totalLateMin = $countAlpa = 0;
        [$startH, $startM] = array_map('intval', explode(':', $this->workStart()));

        foreach ($monthlyRecords as $r) {
            if (in_array($r['status'], ['tepat waktu', 'terlambat', 'awal'])) $countHadir++;
            if ($r['status'] === 'terlambat') {
                $countTerlambat++;
                if ($r['clock_in']) {
                    $cinH = (int)date('H', strtotime($r['clock_in']));
                    $cinM = (int)date('i', strtotime($r['clock_in']));
                    $totalLateMin += max(0, ($cinH * 60 + $cinM) - ($startH * 60 + $startM));
                }
            }
            if ($r['status'] === 'alpa') $countAlpa++;
        }

        $workHoursToday = '-';
        if ($todayRecord && $todayRecord['clock_in'] && $todayRecord['clock_out']) {
            $diff = strtotime($todayRecord['clock_out']) - strtotime($todayRecord['clock_in']);
            $workHoursToday = floor($diff/3600) . 'j ' . floor(($diff%3600)/60) . 'm';
        } elseif ($todayRecord && $todayRecord['clock_in']) {
            $diff = time() - strtotime(date('Y-m-d') . ' ' . $todayRecord['clock_in']);
            $workHoursToday = floor($diff/3600) . 'j ' . floor(($diff%3600)/60) . 'm (live)';
        }

        return [
            'today'          => $todayRecord,
            'monthly'        => $monthlyRecords,
            'countHadir'     => $countHadir,
            'countTerlambat' => $countTerlambat,
            'countAlpa'      => $countAlpa,
            'totalLateMin'   => $totalLateMin,
            'workHoursToday' => $workHoursToday,
            'settings'       => $this->cfg,
        ];
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

    // =========================================================================
    // HR & MANAGER: Attendance logs  GET /hrops/attendance/logs or /manager/attendance/logs
    // =========================================================================
    public function hrLogs() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr_ops', 'hiring_manager', 'admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Otoritas manajemen diperlukan.']);
            return;
        }

        // Backfill all employee alpa records up to date before querying
        $this->backfillAllEmployees();

        $date = $_GET['date'] ?? date('Y-m-d');
        $role = $_SESSION['role'];
        $whereSql = "";
        $queryParams = ['date' => $date];

        if ($role === 'hiring_manager') {
            // Get manager's department
            $stmtManager = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
            $stmtManager->execute(['id' => $_SESSION['user_id']]);
            $managerDeptId = $stmtManager->fetchColumn();

            if (empty($managerDeptId)) {
                echo json_encode(['success' => true, 'rows' => [], 'stats' => ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'absent' => 0]]);
                return;
            }

            $allowedDepts = $this->getDescendantDepartmentIds($this->db, $managerDeptId);
            $inClause = implode(',', array_map(fn($id) => $this->db->quote($id), $allowedDepts));
            $whereSql = " AND u.department_id IN ($inClause) ";
        }

        $stmt = $this->db->prepare("
            SELECT a.id, a.user_id, a.attendance_date, a.clock_in, a.clock_out, a.status, a.work_mode, a.work_mode_out,
                   a.clock_in_latitude, a.clock_in_longitude, a.location_method, a.ip_address, a.correction_reason,
                   a.clock_out_status,
                   u.first_name, u.last_name, u.email, u.employee_id, u.profile_picture,
                   COALESCE(u.job_title, u.role) AS position
            FROM employee_attendance a
            JOIN users u ON a.user_id = u.id
            WHERE a.attendance_date = :date $whereSql ORDER BY a.clock_in ASC
        ");
        $stmt->execute($queryParams);
        $rows = $stmt->fetchAll();

        $all       = $rows;
        $hadir     = count(array_filter($all, fn($r) => in_array($r['status'], ['tepat waktu','terlambat','awal'])));
        $terlambat = count(array_filter($all, fn($r) => $r['status'] === 'terlambat'));
        $absent    = count(array_filter($all, fn($r) => in_array($r['status'], ['alpa','sakit/izin'])));

        echo json_encode(['success' => true, 'rows' => $all,
            'stats' => ['total' => count($all), 'hadir' => $hadir, 'terlambat' => $terlambat, 'absent' => $absent],
        ]);
    }

    // =========================================================================
    // HR & MANAGER: Correct attendance  POST /hrops/attendance/correct
    // =========================================================================
    public function correct() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr_ops', 'hiring_manager', 'admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Otoritas manajemen diperlukan.']);
            return;
        }

        $hrId     = $_SESSION['user_id'];
        $attId    = $_POST['attendance_id'] ?? '';
        $userId   = $_POST['user_id']       ?? '';
        $date     = $_POST['date']          ?? '';
        $clockIn  = $_POST['clock_in']      ?? null;
        $clockOut = $_POST['clock_out']     ?? null;
        $reason   = trim($_POST['reason']   ?? '');

        if (empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'Alasan koreksi wajib diisi.']);
            return;
        }

        try {
            $targetUserId = $userId;
            if (empty($targetUserId) && !empty($attId)) {
                // Find target user from attendance record
                $stmtAtt = $this->db->prepare("SELECT user_id FROM employee_attendance WHERE id = :id");
                $stmtAtt->execute(['id' => $attId]);
                $targetUserId = $stmtAtt->fetchColumn();
            }

            if (!empty($targetUserId) && $_SESSION['role'] === 'hiring_manager') {
                // Get manager's department
                $stmtManager = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
                $stmtManager->execute(['id' => $_SESSION['user_id']]);
                $managerDeptId = $stmtManager->fetchColumn();

                if (empty($managerDeptId)) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda tidak memiliki alokasi departemen.']);
                    return;
                }

                // Fetch target user's department
                $stmtTarget = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
                $stmtTarget->execute(['id' => $targetUserId]);
                $targetDeptId = $stmtTarget->fetchColumn();

                $allowedDepts = $this->getDescendantDepartmentIds($this->db, $managerDeptId);
                if (!in_array($targetDeptId, $allowedDepts)) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda hanya dapat mengoreksi data karyawan di bawah departemen Anda sendiri.']);
                    return;
                }
            }

            $inTime  = $clockIn  ? $clockIn  . ':00' : null;
            $outTime = $clockOut ? $clockOut . ':00' : null;
            $status  = $this->resolveStatus($inTime);
            $coStatus = $this->resolveClockOutStatus($outTime);

            if (empty($attId) && !empty($userId) && !empty($date)) {
                $newId = $this->generateUuid();
                $this->db->prepare("
                    INSERT INTO employee_attendance
                        (id, user_id, attendance_date, clock_in, clock_out, status, clock_out_status, work_mode, work_mode_out,
                         correction_reason, corrected_by, corrected_at, location_method)
                    VALUES (:id, :uid, :date, :ci, :co, :status, :coStatus, 'WFO', 'WFO', :reason, :by, NOW(), 'Koreksi Manajemen')
                ")->execute(['id' => $newId, 'uid' => $userId, 'date' => $date, 'ci' => $inTime, 'co' => $outTime, 'status' => $status, 'coStatus' => $coStatus, 'reason' => $reason, 'by' => $hrId]);
            } else {
                $parts = ['correction_reason = :reason', 'corrected_by = :by', 'corrected_at = NOW()', 'status = :status', 'work_mode = \'WFO\'', 'work_mode_out = \'WFO\'', 'updated_at = NOW()'];
                $params = ['reason' => $reason, 'by' => $hrId, 'status' => $status, 'id' => $attId];
                if ($inTime)  { $parts[] = 'clock_in = :ci';  $params['ci'] = $inTime; }
                if ($outTime) { 
                    $parts[] = 'clock_out = :co'; $params['co'] = $outTime; 
                    $parts[] = 'clock_out_status = :coStatus'; $params['coStatus'] = $coStatus;
                }
                $this->db->prepare("UPDATE employee_attendance SET " . implode(', ', $parts) . " WHERE id = :id")->execute($params);
            }

            echo json_encode(['success' => true, 'message' => 'Koreksi presensi berhasil disimpan ke audit log.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    public function getSettings(): array { return $this->cfg; }

    private function formatDistance($meters): string {
        if ($meters === null) return '';
        if ($meters < 1000) {
            return round($meters) . 'm';
        }
        return number_format($meters / 1000, 1, '.', '') . ' km';
    }

    private function haversine($lat1, $lng1, $lat2, $lng2): int {
        $R    = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
        return (int)round($R * 2 * atan2(sqrt($a), sqrt(1-$a)));
    }

    private function resolveStatus(?string $clockIn): string {
        if (!$clockIn) return 'alpa';
        [$h, $m] = array_map('intval', explode(':', date('H:i', strtotime($clockIn))));
        [$sh, $sm] = array_map('intval', explode(':', $this->workStart()));
        $startMin = $sh * 60 + $sm;
        $deadline = $startMin + $this->graceMins();
        $nowMin = $h * 60 + $m;
        
        if ($nowMin < $startMin) return 'awal';
        return ($nowMin <= $deadline) ? 'tepat waktu' : 'terlambat';
    }

    private function resolveClockOutStatus(?string $clockOut): ?string {
        if (!$clockOut) return null;
        $endStr = $this->workEnd();
        $coGrace = (int)($this->cfg['checkout_grace_period_min'] ?? 15);
        
        [$endH, $endM] = array_map('intval', explode(':', $endStr));
        $endMin = $endH * 60 + $endM;
        $lateLimitMin = $endMin + $coGrace;
        
        [$h, $m] = array_map('intval', explode(':', date('H:i', strtotime($clockOut))));
        $nowMin = $h * 60 + $m;
        
        if ($nowMin < $endMin) {
            return 'pulang cepat';
        } elseif ($nowMin <= $lateLimitMin) {
            return 'wajar';
        } else {
            return 'pulang lambat';
        }
    }

    public function memberMonthly() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hiring_manager', 'hr_ops', 'admin', 'superadmin'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $userId = $_GET['user_id'] ?? '';
        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'User ID diperlukan.']);
            return;
        }

        // Strict Hierarchical Isolation Check for Hiring Manager
        if ($_SESSION['role'] === 'hiring_manager') {
            $stmtManager = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
            $stmtManager->execute(['id' => $_SESSION['user_id']]);
            $managerDeptId = $stmtManager->fetchColumn();

            if (empty($managerDeptId)) {
                echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
                return;
            }

            // Fetch target user department
            $stmtTarget = $this->db->prepare("SELECT department_id FROM users WHERE id = :id");
            $stmtTarget->execute(['id' => $userId]);
            $targetDeptId = $stmtTarget->fetchColumn();

            $allowedDepts = $this->getDescendantDepartmentIds($this->db, $managerDeptId);
            if (!in_array($targetDeptId, $allowedDepts)) {
                echo json_encode(['success' => false, 'message' => 'Akses ditolak. Karyawan berada di luar departemen Anda.']);
                return;
            }
        }

        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $targetMonth = "$year-$month";

        // Run backfill to make sure records are up to date
        $this->backfillAlpa($userId, $targetMonth);

        $monthStart = "$targetMonth-01";
        $monthEnd   = date('Y-m-t', strtotime($monthStart));

        $stmt = $this->db->prepare("
            SELECT * FROM employee_attendance 
            WHERE user_id = :uid AND attendance_date BETWEEN :s AND :e 
            ORDER BY attendance_date DESC
        ");
        $stmt->execute(['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $records]);
    }

    private function generateUuid(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
    }
}
