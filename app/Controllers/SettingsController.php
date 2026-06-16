<?php

namespace App\Controllers;

use App\Config\Database;
use Exception;

/**
 * SettingsController
 * Handles reading and saving configurations from the global_settings table.
 */
class SettingsController {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all settings as a key => value associative array.
     */
    public function getAll(): array {
        try {
            $stmt = $this->db->query("SELECT `key`, `value` FROM global_settings");
            $rows = $stmt->fetchAll();
            $result = [];
            foreach ($rows as $row) {
                $result[$row['key']] = $row['value'];
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get a single setting value with optional default fallback.
     */
    public function get(string $key, $default = null) {
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
     * Save (upsert) a single setting.
     */
    public function set(string $key, string $value): void {
        $this->db->prepare(
            "INSERT INTO global_settings (`key`, `value`) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE `value` = :value2, updated_at = NOW()"
        )->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
    }

    /**
     * POST /admin/settings/save — Save all settings submitted from the form.
     */
    public function save() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin', 'hr_ops'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $allowed = [
            'office_lat', 'office_lng', 'office_radius_m',
            'home_radius_m',
            'work_start_time', 'work_min_start_time', 'work_min_start_time_enabled',
            'work_end_time', 'work_min_end_time', 'work_min_end_time_enabled',
            'grace_period_min',
            'office_wifi_prefix', 'office_wifi_ipv6_prefix', 'wfa_allowed', 'wfa_days',
            'weekly_holidays', 'checkout_grace_period_min',
            'payroll_tunj_jabatan_pct', 'payroll_tunj_jabatan_cap',
            'payroll_tunj_transport', 'payroll_tunj_komunikasi', 'payroll_late_deduction',
            'payroll_bpjs_tk_pct', 'payroll_bpjs_kes_pct', 'payroll_pph21_pct',
            'app_name', 'app_company_name', 'app_logo_icon',
            'app_logo_type', 'app_logo_image',
            'reimbursement_limit_medis', 'reimbursement_limit_transport',
            'reimbursement_limit_operasional', 'reimbursement_limit_makan',
            'reimbursement_limit_department_default',
            'app_idle_timeout_sec',
            'app_idle_countdown_sec',
            'google_maps_api_key',
        ];

        try {
            // Handle Logo File Upload if present
            if (isset($_FILES['app_logo_file']) && $_FILES['app_logo_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['app_logo_file'];
                $allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];
                
                // Validate MIME via finfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (in_array($mimeType, $allowedMimes)) {
                    $uploadDir = __DIR__ . '/../../public/uploads/logos/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    if (empty($ext)) {
                        $extMap = [
                            'image/png' => 'png',
                            'image/jpeg' => 'jpg',
                            'image/gif' => 'gif',
                            'image/svg+xml' => 'svg'
                        ];
                        $ext = $extMap[$mimeType] ?? 'png';
                    }
                    
                    $filename = 'app_logo_' . time() . '.' . $ext;
                    $destination = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $this->set('app_logo_image', '/uploads/logos/' . $filename);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file logo ke server.']);
                        return;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Format file logo tidak didukung. Gunakan PNG, JPG, GIF, atau SVG.']);
                    return;
                }
            }

            foreach ($allowed as $key) {
                if (isset($_POST[$key])) {
                    $val = trim($_POST[$key]);
                    // Auto-normalize commas to dots for coordinate decimals
                    if (in_array($key, ['office_lat', 'office_lng'])) {
                        $val = str_replace(',', '.', $val);
                    }

                    // Validate numeric fields
                    if (in_array($key, ['office_lat', 'office_lng']) && !is_numeric($val)) {
                        echo json_encode(['success' => false, 'message' => "Nilai koordinat '{$key}' harus berupa angka."]);
                        return;
                    }
                    if (in_array($key, ['office_radius_m', 'home_radius_m'])) {
                        if (!is_numeric($val) || (int)$val < 1 || (int)$val > 1000) {
                            $label = $key === 'office_radius_m' ? 'WFO' : 'WFH';
                            echo json_encode(['success' => false, 'message' => "Jarak radius {$label} harus antara 1 sampai 1000 meter."]);
                            return;
                        }
                    }
                    if (in_array($key, ['office_radius_m', 'home_radius_m', 'grace_period_min', 'checkout_grace_period_min']) && (!is_numeric($val) || (int)$val < 0)) {
                        echo json_encode(['success' => false, 'message' => "Nilai '{$key}' harus berupa angka positif."]);
                        return;
                    }
                    if ($key === 'app_idle_timeout_sec') {
                        if (!is_numeric($val) || (int)$val < 0) {
                            echo json_encode(['success' => false, 'message' => "Batas waktu idle aplikasi harus berupa angka positif atau 0."]);
                            return;
                        }
                    }
                    if (in_array($key, ['payroll_tunj_jabatan_pct', 'payroll_bpjs_tk_pct', 'payroll_bpjs_kes_pct', 'payroll_pph21_pct'])) {
                        if (!is_numeric($val) || (float)$val < 0 || (float)$val > 100) {
                            echo json_encode(['success' => false, 'message' => "Nilai persentase harus antara 0% sampai 100%."]);
                            return;
                        }
                    }
                    if (in_array($key, [
                        'payroll_tunj_jabatan_cap', 'payroll_tunj_transport', 'payroll_tunj_komunikasi', 'payroll_late_deduction',
                        'reimbursement_limit_medis', 'reimbursement_limit_transport', 'reimbursement_limit_operasional', 'reimbursement_limit_makan',
                        'reimbursement_limit_department_default'
                    ])) {
                        $val = str_replace([',', '.'], '', $val);
                        if (!is_numeric($val) || (float)$val < 0) {
                            echo json_encode(['success' => false, 'message' => "Nilai nominal harus berupa angka positif."]);
                            return;
                        }
                    }
                    $this->set($key, $val);
                }
            }
            // wfa_days can be empty (means all days allowed)
            if (!isset($_POST['wfa_days'])) {
                $this->set('wfa_days', '');
            }
            // weekly_holidays can be empty
            if (!isset($_POST['weekly_holidays'])) {
                $this->set('weekly_holidays', '');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Pengaturan presensi berhasil disimpan.',
                'settings' => [
                    'app_idle_timeout_sec' => $this->get('app_idle_timeout_sec', '0'),
                    'app_idle_countdown_sec' => $this->get('app_idle_countdown_sec', '0'),
                    'app_name' => $this->get('app_name', 'siCare'),
                    'app_logo_type' => $this->get('app_logo_type', 'icon'),
                    'app_logo_icon' => $this->get('app_logo_icon', 'local_police'),
                    'app_logo_image' => $this->get('app_logo_image', ''),
                    'google_maps_api_key' => $this->get('google_maps_api_key', ''),
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /admin/holidays/add — Add a new holiday.
     */
    public function addHoliday() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin', 'hr_ops'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $date = $_POST['holiday_date'] ?? '';
        $desc = trim($_POST['description'] ?? '');

        if (empty($date) || empty($desc)) {
            echo json_encode(['success' => false, 'message' => 'Tanggal dan keterangan libur wajib diisi.']);
            return;
        }

        try {
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));

            $stmt = $this->db->prepare("INSERT INTO company_holidays (id, holiday_date, description) VALUES (:id, :date, :desc)");
            $stmt->execute(['id' => $uuid, 'date' => $date, 'desc' => $desc]);

            // Immediately purge any auto-generated alpa records for all employees on this new holiday date
            $delStmt = $this->db->prepare("DELETE FROM employee_attendance WHERE attendance_date = :date AND status = 'alpa' AND clock_in IS NULL AND clock_out IS NULL");
            $delStmt->execute(['date' => $date]);

            echo json_encode(['success' => true, 'message' => 'Hari libur berhasil ditambahkan.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /admin/holidays/delete — Delete a holiday.
     */
    public function deleteHoliday() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin', 'hr_ops'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM company_holidays WHERE id = :id");
            $stmt->execute(['id' => $id]);

            echo json_encode(['success' => true, 'message' => 'Hari libur berhasil dihapus.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /admin/holidays/update | /hrops/holidays/update
     */
    public function updateHoliday() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin', 'hr_ops'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $id          = trim($_POST['id'] ?? '');
        $date        = trim($_POST['holiday_date'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($id) || empty($date) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'ID, tanggal, dan keterangan wajib diisi.']);
            return;
        }

        // Validate date format YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid.']);
            return;
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE company_holidays SET holiday_date = :date, description = :description WHERE id = :id"
            );
            $stmt->execute(['date' => $date, 'description' => $description, 'id' => $id]);

            echo json_encode(['success' => true, 'message' => 'Hari libur berhasil diperbarui.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /admin/settings/fetch-google-holidays
     */
    public function fetchGoogleHolidays() {
        header('Content-Type: application/json');
        session_start();
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin', 'hr_ops'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $year = (int)($_GET['year'] ?? date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }

        // Google Calendar Public ICS Feed for Indonesian Holidays
        $url = "https://calendar.google.com/calendar/ical/id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics";

        $icsContent = @file_get_contents($url);
        if (!$icsContent) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $icsContent = curl_exec($ch);
            curl_close($ch);
        }

        if (!$icsContent) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data kalender dari Google.']);
            return;
        }

        $events = [];
        $lines = explode("\n", str_replace("\r", "", $icsContent));
        $currentEvent = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === 'BEGIN:VEVENT') {
                $currentEvent = [];
            } elseif ($line === 'END:VEVENT') {
                if ($currentEvent && isset($currentEvent['date']) && isset($currentEvent['summary'])) {
                    if (str_starts_with($currentEvent['date'], (string)$year)) {
                        $events[] = $currentEvent;
                    }
                }
                $currentEvent = null;
            } elseif ($currentEvent !== null) {
                if (str_starts_with($line, 'DTSTART')) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $rawDate = preg_replace('/[^0-9]/', '', $parts[1]);
                        if (strlen($rawDate) >= 8) {
                            $y = substr($rawDate, 0, 4);
                            $m = substr($rawDate, 4, 2);
                            $d = substr($rawDate, 6, 2);
                            $currentEvent['date'] = "{$y}-{$m}-{$d}";
                        }
                    }
                } elseif (str_starts_with($line, 'SUMMARY')) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        $summary = trim($parts[1]);
                        $summary = str_replace(['\\,', '\\;'], [',', ';'], $summary);
                        $currentEvent['summary'] = $summary;
                    }
                }
            }
        }

        // Deduplicate by date
        $deduped = [];
        foreach ($events as $ev) {
            $dateKey = $ev['date'];
            if (!isset($deduped[$dateKey])) {
                $deduped[$dateKey] = $ev;
            }
        }
        $events = array_values($deduped);

        // Sort events by date
        usort($events, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        echo json_encode([
            'success' => true,
            'year' => $year,
            'holidays' => $events
        ]);
    }

    /**
     * POST /admin/settings/import-google-holidays
     */
    public function importGoogleHolidays() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin', 'hr_ops'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $holidays = $input['holidays'] ?? [];

        if (!is_array($holidays)) {
            echo json_encode(['success' => false, 'message' => 'Format data tidak valid.']);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO company_holidays (id, holiday_date, description)
                VALUES (:id, :date, :desc)
                ON DUPLICATE KEY UPDATE description = VALUES(description)
            ");

            $count = 0;
            foreach ($holidays as $h) {
                $date = $h['date'] ?? '';
                $desc = trim($h['desc'] ?? '');

                if ($date && $desc) {
                    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                        mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                    $stmt->execute(['id' => $uuid, 'date' => $date, 'desc' => $desc]);
                    
                    // Immediately purge any auto-generated alpa records for all employees on this holiday date
                    $delStmt = $this->db->prepare("DELETE FROM employee_attendance WHERE attendance_date = :date AND status = 'alpa' AND clock_in IS NULL AND clock_out IS NULL");
                    $delStmt->execute(['date' => $date]);
                    
                    $count++;
                }
            }

            echo json_encode(['success' => true, 'message' => "Berhasil mengimpor {$count} hari libur perusahaan."]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengimpor: ' . $e->getMessage()]);
        }
    }
}
