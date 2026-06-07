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

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $allowed = [
            'office_lat', 'office_lng', 'office_radius_m',
            'home_radius_m',
            'work_start_time', 'work_end_time', 'grace_period_min',
            'office_wifi_prefix', 'wfa_allowed', 'wfa_days',
            'weekly_holidays', 'checkout_grace_period_min',
            'payroll_tunj_jabatan_pct', 'payroll_tunj_jabatan_cap',
            'payroll_tunj_transport', 'payroll_tunj_komunikasi', 'payroll_late_deduction',
            'payroll_bpjs_tk_pct', 'payroll_bpjs_kes_pct', 'payroll_pph21_pct',
            'app_name', 'app_company_name', 'app_logo_icon',
            'app_logo_type', 'app_logo_image',
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
                    if (in_array($key, ['payroll_tunj_jabatan_pct', 'payroll_bpjs_tk_pct', 'payroll_bpjs_kes_pct', 'payroll_pph21_pct'])) {
                        if (!is_numeric($val) || (float)$val < 0 || (float)$val > 100) {
                            echo json_encode(['success' => false, 'message' => "Nilai persentase harus antara 0% sampai 100%."]);
                            return;
                        }
                    }
                    if (in_array($key, ['payroll_tunj_jabatan_cap', 'payroll_tunj_transport', 'payroll_tunj_komunikasi', 'payroll_late_deduction'])) {
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

            echo json_encode(['success' => true, 'message' => 'Pengaturan presensi berhasil disimpan.']);
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

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
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

        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'admin'])) {
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
}
