<?php

use App\Config\Migration;

/**
 * Migration 10: Menutup gap skema yang ditemukan oleh audit.
 * 
 * 1. CREATE TABLE departments (belum pernah ada di migration)
 * 2. ALTER employee_attendance: clock_out_status, work_mode_out
 * 3. Seed default departments (9 departemen)
 * 4. Seed default global_settings (25+ pengaturan)
 * 5. Seed tambahan: weekly_holidays, checkout_grace_period_min
 */
class CreateDepartmentsAndSeedDefaults extends Migration {

    public function up() {
        // ============================================================
        // 1. CREATE TABLE departments (source of truth)
        // ============================================================
        if (!$this->tableExists('departments')) {
            $this->execute("
                CREATE TABLE departments (
                    id CHAR(36) PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    parent_id CHAR(36) NULL,
                    level INT DEFAULT 1,
                    reimbursement_limit DECIMAL(15,2) DEFAULT NULL,
                    limit_medis DECIMAL(15,2) DEFAULT NULL,
                    limit_transport DECIMAL(15,2) DEFAULT NULL,
                    limit_operasional DECIMAL(15,2) DEFAULT NULL,
                    limit_makan DECIMAL(15,2) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    KEY idx_parent_id (parent_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: departments\n";
        }

        // ============================================================
        // 2. ALTER employee_attendance: clock_out_status, work_mode_out
        // ============================================================
        if ($this->tableExists('employee_attendance')) {
            if (!$this->columnExists('employee_attendance', 'clock_out_status')) {
                $this->execute("ALTER TABLE employee_attendance ADD COLUMN clock_out_status VARCHAR(30) DEFAULT NULL AFTER status");
                echo "Added column: employee_attendance.clock_out_status\n";
            }
            if (!$this->columnExists('employee_attendance', 'work_mode_out')) {
                $this->execute("ALTER TABLE employee_attendance ADD COLUMN work_mode_out VARCHAR(10) DEFAULT NULL AFTER work_mode");
                echo "Added column: employee_attendance.work_mode_out\n";
            }
        }

        // ============================================================
        // 3. Seed default departments (idempoten via INSERT IGNORE)
        // ============================================================
        $depts = [
            ['dept-it', 'Information Technology', null, 1],
            ['dept-hr', 'Human Resources', null, 1],
            ['dept-finance', 'Finance & Accounting', null, 1],
            ['dept-marketing', 'Marketing & Sales', null, 1],
            ['dept-ops', 'Operations', null, 1],
            ['dept-it-fe', 'Frontend Development', 'dept-it', 2],
            ['dept-it-be', 'Backend Development', 'dept-it', 2],
            ['dept-it-eng', 'Software Engineering', 'dept-it', 2],
            ['dept-it-ops', 'IT Operations', 'dept-it', 2],
            ['dept-hr-ops', 'HR Operations', 'dept-hr', 2],
            ['dept-hr-ta', 'Talent Acquisition', 'dept-hr', 2],
        ];

        $stmtDept = $this->db->prepare("
            INSERT IGNORE INTO departments (id, name, parent_id, level) 
            VALUES (?, ?, ?, ?)
        ");
        foreach ($depts as $d) {
            $stmtDept->execute($d);
        }
        echo "Seeded default departments (11 rows, IGNORE duplicates).\n";

        // ============================================================
        // 4. Seed default global_settings (idempoten via ON DUPLICATE KEY)
        // ============================================================
        if ($this->tableExists('global_settings')) {
            $defaultSettings = [
                // Attendance / Location
                ['office_lat',        '-6.2297',      'Latitude Kantor Pusat',            'attendance'],
                ['office_lng',        '106.8164',     'Longitude Kantor Pusat',           'attendance'],
                ['office_radius_m',   '150',          'Radius WFO (meter)',               'attendance'],
                ['work_start_time',   '08:00',        'Jam Masuk Standar',                'attendance'],
                ['work_end_time',     '17:00',        'Jam Pulang Standar',               'attendance'],
                ['grace_period_min',  '10',           'Toleransi Keterlambatan (menit)',  'attendance'],
                ['office_wifi_prefix','192.168.10.',  'Prefix IP WIFI Kantor',            'attendance'],
                // WFA
                ['wfa_allowed',       'true',         'Izinkan Work From Anywhere (WFA)', 'wfa'],
                ['wfa_days',          '',             'Hari WFA Diizinkan (kosong=semua)','wfa'],
                // Payroll
                ['payroll_tunj_jabatan_pct', '15',            'Persentase Tunjangan Jabatan (%)', 'payroll'],
                ['payroll_tunj_jabatan_cap', '2500000',       'Batas Maksimal Tunjangan Jabatan', 'payroll'],
                ['payroll_tunj_transport',   '1500000',       'Tunjangan Transport Flat (IDR)',   'payroll'],
                ['payroll_tunj_komunikasi',  '500000',        'Tunjangan Komunikasi Flat (IDR)',  'payroll'],
                // Reimbursement Category Defaults
                ['reimbursement_limit_medis',       '5000000',  'Plafon Medis Bulanan (Default)',       'reimbursement'],
                ['reimbursement_limit_transport',   '3000000',  'Plafon Transport Bulanan (Default)',   'reimbursement'],
                ['reimbursement_limit_operasional', '4000000',  'Plafon Operasional Bulanan (Default)', 'reimbursement'],
                ['reimbursement_limit_makan',       '2500000',  'Plafon Makan Bulanan (Default)',       'reimbursement'],
                // Department Default
                ['reimbursement_limit_department_default', '15000000', 'Plafon Departemen Bulanan (Default)', 'reimbursement'],
                // General App/Company Info
                ['app_name',                 'siCare',                 'Nama Aplikasi',                    'general'],
                ['app_company_name',         'PT SI CARE ENTERPRISE',  'Nama Perusahaan (PT)',             'general'],
                ['app_logo_icon',            'local_police',           'Logo Aplikasi (Material Icon)',    'general'],
                ['app_logo_type',            'icon',                   'Tipe Logo (icon/image)',           'general'],
                ['app_logo_image',           '',                       'URL Logo Gambar',                  'general'],
                ['app_idle_timeout_sec',     '0',                      'Batas Waktu Idle Aplikasi (Detik)', 'general'],
                ['google_maps_api_key',      '',                       'Google Maps API Key',              'general'],
                // Additional runtime settings
                ['weekly_holidays',          'Sat,Sun',  'Hari Libur Mingguan',                  'attendance'],
                ['checkout_grace_period_min','15',       'Toleransi Checkout Lebih Awal (menit)', 'attendance'],
            ];

            $stmtSetting = $this->db->prepare("
                INSERT INTO global_settings (`key`, `value`, `label`, `group`)
                VALUES (:key, :value, :label, :grp)
                ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group` = VALUES(`group`)
            ");
            foreach ($defaultSettings as [$k, $v, $lbl, $grp]) {
                $stmtSetting->execute(['key' => $k, 'value' => $v, 'label' => $lbl, 'grp' => $grp]);
            }
            echo "Seeded default global_settings (27 rows, ON DUPLICATE KEY).\n";
        }

        echo "=== Migration 10 complete ===\n";
    }

    public function down() {
        // Rollback kolom attendance
        if ($this->columnExists('employee_attendance', 'clock_out_status')) {
            $this->execute("ALTER TABLE employee_attendance DROP COLUMN clock_out_status");
        }
        if ($this->columnExists('employee_attendance', 'work_mode_out')) {
            $this->execute("ALTER TABLE employee_attendance DROP COLUMN work_mode_out");
        }

        // Hapus seed settings tambahan
        $this->execute("DELETE FROM global_settings WHERE `key` IN ('weekly_holidays', 'checkout_grace_period_min')");

        // TIDAK drop tabel departments karena tabel lain bergantung padanya
        echo "=== Migration 10 rollback complete ===\n";
    }
}
