<?php

use App\Config\Migration;

/**
 * Consolidation Migration v25
 * 
 * Memastikan seluruh tabel dan kolom yang sudah ada di update_schema.php
 * juga tercatat secara resmi di migration system.
 * Semua operasi bersifat idempoten (IF NOT EXISTS / columnExists check).
 */
class ConsolidateAllTablesV25 extends Migration {

    public function up() {
        // ============================================================
        // TABEL 1: approval_requests
        // ============================================================
        if (!$this->tableExists('approval_requests')) {
            $this->execute("
                CREATE TABLE approval_requests (
                    id CHAR(36) PRIMARY KEY,
                    requester_id CHAR(36) NOT NULL,
                    target_user_id CHAR(36) NOT NULL,
                    approver_id CHAR(36) NULL,
                    action_type VARCHAR(50) NOT NULL,
                    new_data LONGTEXT NULL,
                    status VARCHAR(20) DEFAULT 'PENDING',
                    rejection_reason TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: approval_requests\n";
        }

        // ============================================================
        // TABEL 2: employment_history
        // ============================================================
        if (!$this->tableExists('employment_history')) {
            $this->execute("
                CREATE TABLE employment_history (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    department_id CHAR(36) NULL,
                    job_title VARCHAR(255) NULL,
                    status VARCHAR(20) DEFAULT 'ACTIVE',
                    start_date DATE NOT NULL,
                    end_date DATE NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: employment_history\n";
        }

        // ============================================================
        // TABEL 3: employee_data_correction_requests
        // ============================================================
        if (!$this->tableExists('employee_data_correction_requests')) {
            $this->execute("
                CREATE TABLE employee_data_correction_requests (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    category VARCHAR(50) NOT NULL,
                    field VARCHAR(50) NOT NULL,
                    old_value TEXT NULL,
                    new_value TEXT NOT NULL,
                    reason TEXT NOT NULL,
                    file_path VARCHAR(255) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    rejection_reason TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: employee_data_correction_requests\n";
        }

        // ============================================================
        // TABEL 4: employee_reimbursement_claims
        // ============================================================
        if (!$this->tableExists('employee_reimbursement_claims')) {
            $this->execute("
                CREATE TABLE employee_reimbursement_claims (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    category VARCHAR(50) NOT NULL,
                    amount DECIMAL(15,2) NOT NULL,
                    description TEXT NOT NULL,
                    receipt_path VARCHAR(255) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    rejection_reason TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: employee_reimbursement_claims\n";
        }

        // ============================================================
        // TABEL 5: employee_leave_requests
        // ============================================================
        if (!$this->tableExists('employee_leave_requests')) {
            $this->execute("
                CREATE TABLE employee_leave_requests (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    leave_type VARCHAR(50) NOT NULL,
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    duration INT NOT NULL,
                    reason TEXT NOT NULL,
                    attachment_path VARCHAR(255) NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    rejection_reason TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: employee_leave_requests\n";
        }

        // ============================================================
        // TABEL 6: employee_attendance
        // ============================================================
        if (!$this->tableExists('employee_attendance')) {
            $this->execute("
                CREATE TABLE employee_attendance (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    attendance_date DATE NOT NULL,
                    clock_in TIME NULL,
                    clock_out TIME NULL,
                    status VARCHAR(30) DEFAULT 'alpa',
                    clock_in_latitude DECIMAL(10,7) NULL,
                    clock_in_longitude DECIMAL(10,7) NULL,
                    clock_out_latitude DECIMAL(10,7) NULL,
                    clock_out_longitude DECIMAL(10,7) NULL,
                    location_method VARCHAR(20) NULL,
                    work_mode VARCHAR(10) DEFAULT 'WFO',
                    ip_address VARCHAR(45) NULL,
                    correction_reason TEXT NULL,
                    corrected_by CHAR(36) NULL,
                    corrected_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_date (user_id, attendance_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: employee_attendance\n";
        }

        // Kolom work_mode di employee_attendance
        if ($this->tableExists('employee_attendance') && !$this->columnExists('employee_attendance', 'work_mode')) {
            $this->execute("ALTER TABLE employee_attendance ADD COLUMN work_mode VARCHAR(10) DEFAULT 'WFO' AFTER location_method");
            echo "Added column: employee_attendance.work_mode\n";
        }

        // ============================================================
        // TABEL 7: company_holidays
        // ============================================================
        if (!$this->tableExists('company_holidays')) {
            $this->execute("
                CREATE TABLE company_holidays (
                    id CHAR(36) NOT NULL PRIMARY KEY,
                    holiday_date DATE NOT NULL,
                    description VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_holiday_date (holiday_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: company_holidays\n";
        }

        // ============================================================
        // TABEL 8: global_settings
        // ============================================================
        if (!$this->tableExists('global_settings')) {
            $this->execute("
                CREATE TABLE global_settings (
                    `key` VARCHAR(100) NOT NULL PRIMARY KEY,
                    `value` TEXT NOT NULL,
                    `label` VARCHAR(255) NULL,
                    `group` VARCHAR(50) DEFAULT 'general',
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: global_settings\n";
        }

        // ============================================================
        // TABEL 9: sessions
        // ============================================================
        if (!$this->tableExists('sessions')) {
            $this->execute("
                CREATE TABLE sessions (
                    id VARCHAR(255) PRIMARY KEY,
                    user_id CHAR(36) NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    payload LONGTEXT NOT NULL,
                    last_activity INT NOT NULL,
                    KEY sessions_user_id_index (user_id),
                    KEY sessions_last_activity_index (last_activity)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: sessions\n";
        }

        // ============================================================
        // TABEL 10: login_logs
        // ============================================================
        if (!$this->tableExists('login_logs')) {
            $this->execute("
                CREATE TABLE login_logs (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    latitude DECIMAL(10,7) NULL,
                    longitude DECIMAL(10,7) NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    status VARCHAR(50) DEFAULT 'Akses Aplikasi'
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: login_logs\n";
        }

        // Kolom status di login_logs
        if ($this->tableExists('login_logs') && !$this->columnExists('login_logs', 'status')) {
            $this->execute("ALTER TABLE login_logs ADD COLUMN status VARCHAR(50) DEFAULT 'Akses Aplikasi' AFTER user_agent");
            echo "Added column: login_logs.status\n";
        }

        // ============================================================
        // TABEL 11: employee_payroll
        // ============================================================
        if (!$this->tableExists('employee_payroll')) {
            $this->execute("
                CREATE TABLE employee_payroll (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    month_year VARCHAR(15) NOT NULL,
                    base_salary DECIMAL(15,2) DEFAULT 0.00,
                    tunj_jabatan DECIMAL(15,2) DEFAULT 0.00,
                    tunj_transport_makan DECIMAL(15,2) DEFAULT 0.00,
                    tunj_komunikasi DECIMAL(15,2) DEFAULT 0.00,
                    bonus DECIMAL(15,2) DEFAULT 0.00,
                    reimbursement DECIMAL(15,2) DEFAULT 0.00,
                    overtime DECIMAL(15,2) DEFAULT 0.00,
                    bpjs_tk DECIMAL(15,2) DEFAULT 0.00,
                    bpjs_kes DECIMAL(15,2) DEFAULT 0.00,
                    pph21 DECIMAL(15,2) DEFAULT 0.00,
                    other_deduction DECIMAL(15,2) DEFAULT 0.00,
                    net_salary DECIMAL(15,2) DEFAULT 0.00,
                    status VARCHAR(20) DEFAULT 'Draft',
                    payment_date TIMESTAMP NULL DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_month (user_id, month_year)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: employee_payroll\n";
        }

        // Kolom overtime dan other_deduction di employee_payroll
        if ($this->tableExists('employee_payroll') && !$this->columnExists('employee_payroll', 'overtime')) {
            $this->execute("ALTER TABLE employee_payroll ADD COLUMN overtime DECIMAL(15,2) DEFAULT 0.00 AFTER reimbursement");
            echo "Added column: employee_payroll.overtime\n";
        }
        if ($this->tableExists('employee_payroll') && !$this->columnExists('employee_payroll', 'other_deduction')) {
            $this->execute("ALTER TABLE employee_payroll ADD COLUMN other_deduction DECIMAL(15,2) DEFAULT 0.00 AFTER pph21");
            echo "Added column: employee_payroll.other_deduction\n";
        }

        // ============================================================
        // TABEL 12: self_reflections
        // ============================================================
        if (!$this->tableExists('self_reflections')) {
            $this->execute("
                CREATE TABLE self_reflections (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    period VARCHAR(20) NOT NULL,
                    achievements TEXT NULL,
                    challenges TEXT NULL,
                    core_values_rating INT DEFAULT 5,
                    future_goals TEXT NULL,
                    support_needed TEXT NULL,
                    mood_rating VARCHAR(50) NULL,
                    workload_rating INT DEFAULT 3,
                    reflection_notes TEXT NULL,
                    career_aspirations TEXT NULL,
                    skills_to_develop TEXT NULL,
                    action_plan TEXT NULL,
                    status VARCHAR(20) DEFAULT 'draft',
                    manager_feedback TEXT NULL,
                    manager_feedback_by CHAR(36) NULL,
                    manager_feedback_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_period (user_id, period)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: self_reflections\n";
        }

        // ============================================================
        // TABEL 13: mood_pulses
        // ============================================================
        if (!$this->tableExists('mood_pulses')) {
            $this->execute("
                CREATE TABLE mood_pulses (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    period VARCHAR(20) NOT NULL,
                    mood_rating VARCHAR(50) NOT NULL,
                    workload_rating INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_mood_period (user_id, period)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: mood_pulses\n";
        }

        // ============================================================
        // TABEL 14: personal_journals
        // ============================================================
        if (!$this->tableExists('personal_journals')) {
            $this->execute("
                CREATE TABLE personal_journals (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    title VARCHAR(150) NULL,
                    notes TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: personal_journals\n";
        }

        // ============================================================
        // TABEL 15: changelogs
        // ============================================================
        if (!$this->tableExists('changelogs')) {
            $this->execute("
                CREATE TABLE changelogs (
                    version VARCHAR(50) PRIMARY KEY,
                    edition VARCHAR(50) NOT NULL,
                    repo_type VARCHAR(50) NOT NULL DEFAULT 'monorepo',
                    compiled_date DATE NOT NULL,
                    migration_level VARCHAR(100) NOT NULL,
                    alias_name VARCHAR(100) DEFAULT NULL,
                    summary LONGTEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: changelogs\n";
        }

        // Kolom repo_type dan alias_name di changelogs
        if ($this->tableExists('changelogs') && !$this->columnExists('changelogs', 'repo_type')) {
            $this->execute("ALTER TABLE changelogs ADD COLUMN repo_type VARCHAR(50) NOT NULL DEFAULT 'monorepo' AFTER edition");
            echo "Added column: changelogs.repo_type\n";
        }
        if ($this->tableExists('changelogs') && !$this->columnExists('changelogs', 'alias_name')) {
            $this->execute("ALTER TABLE changelogs ADD COLUMN alias_name VARCHAR(100) DEFAULT NULL AFTER migration_level");
            echo "Added column: changelogs.alias_name\n";
        }

        // Migrasi nama edisi: business -> enterprise
        $this->execute("UPDATE changelogs SET edition = 'enterprise' WHERE edition = 'business'");

        // ============================================================
        // ALTER TABLE: departments (reimbursement limits)
        // ============================================================
        $deptLimitCols = [
            ['reimbursement_limit', 'DECIMAL(15,2) DEFAULT NULL', 'level'],
            ['limit_medis', 'DECIMAL(15,2) DEFAULT NULL', 'reimbursement_limit'],
            ['limit_transport', 'DECIMAL(15,2) DEFAULT NULL', 'limit_medis'],
            ['limit_operasional', 'DECIMAL(15,2) DEFAULT NULL', 'limit_transport'],
            ['limit_makan', 'DECIMAL(15,2) DEFAULT NULL', 'limit_operasional'],
        ];

        foreach ($deptLimitCols as [$col, $type, $after]) {
            if (!$this->columnExists('departments', $col)) {
                $this->execute("ALTER TABLE departments ADD COLUMN $col $type AFTER $after");
                echo "Added column: departments.$col\n";
            }
        }

        // ============================================================
        // ALTER TABLE: users (semua kolom tambahan)
        // ============================================================
        $userExtraCols = [
            ['no_telepon', 'VARCHAR(20) NULL', 'profile_picture'],
            ['alamat_domisili', 'TEXT NULL', 'no_telepon'],
            ['ktp_nik', 'VARCHAR(20) NULL', 'alamat_domisili'],
            ['nama_sesuai_ktp', 'VARCHAR(100) NULL', 'ktp_nik'],
            ['alamat_ktp', 'TEXT NULL', 'nama_sesuai_ktp'],
            ['bank_name', 'VARCHAR(100) NULL', 'alamat_ktp'],
            ['bank_account_number', 'VARCHAR(50) NULL', 'bank_name'],
            ['npwp_number', 'VARCHAR(30) NULL', 'bank_account_number'],
            ['bpjs_tk', 'VARCHAR(30) NULL', 'npwp_number'],
            ['bpjs_kes', 'VARCHAR(30) NULL', 'bpjs_tk'],
            ['tanggal_lahir', 'VARCHAR(50) NULL', 'bpjs_kes'],
            ['status_pernikahan', 'VARCHAR(50) NULL', 'tanggal_lahir'],
            ['jenis_kelamin', 'VARCHAR(20) NULL', 'status_pernikahan'],
            ['annual_leave_quota', 'INT DEFAULT 12', 'jenis_kelamin'],
            ['department_id', 'CHAR(36) NULL', 'annual_leave_quota'],
            ['job_title', 'VARCHAR(100) NULL', 'department_id'],
            ['base_salary', 'DECIMAL(15,2) DEFAULT 0.00', 'job_title'],
            ['profile_reset_token', 'VARCHAR(100) NULL', 'base_salary'],
            ['login_just_occurred', 'TINYINT DEFAULT 0', 'profile_reset_token'],
            ['is_suspended', 'TINYINT DEFAULT 0', 'login_just_occurred'],
        ];

        foreach ($userExtraCols as [$col, $type, $after]) {
            if (!$this->columnExists('users', $col)) {
                $this->execute("ALTER TABLE users ADD COLUMN $col $type AFTER $after");
                echo "Added column: users.$col\n";
            }
        }

        echo "=== Consolidation migration v25 complete ===\n";
    }

    public function down() {
        // Rollback: drop tables yang dibuat oleh migration ini
        // PERHATIAN: Hanya drop tabel yang TIDAK memiliki data kritis
        $tables = [
            'personal_journals',
            'mood_pulses', 
            'self_reflections',
            'employee_payroll',
            'login_logs',
            'sessions',
            'global_settings',
            'company_holidays',
            'employee_attendance',
            'employee_leave_requests',
            'employee_reimbursement_claims',
            'employee_data_correction_requests',
            'employment_history',
            'approval_requests',
            'changelogs',
        ];

        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                $this->execute("DROP TABLE $table");
                echo "Dropped table: $table\n";
            }
        }

        // Rollback kolom departments
        $deptCols = ['limit_makan', 'limit_operasional', 'limit_transport', 'limit_medis', 'reimbursement_limit'];
        foreach ($deptCols as $col) {
            if ($this->columnExists('departments', $col)) {
                $this->execute("ALTER TABLE departments DROP COLUMN $col");
            }
        }

        // Rollback kolom users (reverse order)
        $userCols = [
            'is_suspended', 'login_just_occurred', 'profile_reset_token',
            'base_salary', 'job_title', 'department_id', 'annual_leave_quota',
            'jenis_kelamin', 'status_pernikahan', 'tanggal_lahir',
            'bpjs_kes', 'bpjs_tk', 'npwp_number', 'bank_account_number',
            'bank_name', 'alamat_ktp', 'nama_sesuai_ktp', 'ktp_nik',
            'alamat_domisili', 'no_telepon'
        ];
        foreach ($userCols as $col) {
            if ($this->columnExists('users', $col)) {
                $this->execute("ALTER TABLE users DROP COLUMN $col");
            }
        }

        echo "=== Consolidation rollback complete ===\n";
    }
}
