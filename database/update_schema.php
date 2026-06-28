<?php
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "Connected to database successfully.\n";

    // Create missing tables first
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id CHAR(36) PRIMARY KEY,
            employee_id VARCHAR(20) NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'candidate',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table users created or verified.\n";

    $db->exec("
        CREATE TABLE IF NOT EXISTS departments (
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
    echo "Table departments created or verified.\n";

    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            action TEXT NOT NULL,
            table_name VARCHAR(100) NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table audit_logs created or verified.\n";

    $db->exec("
        CREATE TABLE IF NOT EXISTS approval_requests (
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
    echo "Table approval_requests created or verified.\n";

    $db->exec("
        CREATE TABLE IF NOT EXISTS employment_history (
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
    echo "Table employment_history created or verified.\n";

    // Seed default departments if empty
    $countDepts = $db->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    if ($countDepts == 0) {
        $depts = [
            ['dept-it', 'Information Technology', null, 1],
            ['dept-hr', 'Human Resources', null, 1],
            ['dept-finance', 'Finance & Accounting', null, 1],
            ['dept-marketing', 'Marketing & Sales', null, 1],
            ['dept-ops', 'Operations', null, 1],
            ['dept-it-eng', 'Software Engineering', 'dept-it', 2],
            ['dept-it-ops', 'IT Operations', 'dept-it', 2],
            ['dept-hr-ops', 'HR Operations', 'dept-hr', 2],
            ['dept-hr-ta', 'Talent Acquisition', 'dept-hr', 2],
        ];
        $stmtDept = $db->prepare("INSERT INTO departments (id, name, parent_id, level) VALUES (?, ?, ?, ?)");
        foreach ($depts as $d) {
            $stmtDept->execute($d);
            echo "Seeded department: {$d[1]}\n";
        }
    }

    // 1. Add extra columns to users table
    $alterQueries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS no_telepon VARCHAR(20) NULL AFTER profile_picture",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS alamat_domisili TEXT NULL AFTER no_telepon",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS ktp_nik VARCHAR(20) NULL AFTER alamat_domisili",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS nama_sesuai_ktp VARCHAR(100) NULL AFTER ktp_nik",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS alamat_ktp TEXT NULL AFTER nama_sesuai_ktp",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) NULL AFTER alamat_ktp",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS bank_account_number VARCHAR(50) NULL AFTER bank_name",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS npwp_number VARCHAR(30) NULL AFTER bank_account_number",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS bpjs_tk VARCHAR(30) NULL AFTER npwp_number",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS bpjs_kes VARCHAR(30) NULL AFTER bpjs_tk",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS tanggal_lahir VARCHAR(50) NULL AFTER bpjs_kes",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS status_pernikahan VARCHAR(50) NULL AFTER tanggal_lahir",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS jenis_kelamin VARCHAR(20) NULL AFTER status_pernikahan",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS annual_leave_quota INT DEFAULT 12 AFTER jenis_kelamin",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS department_id CHAR(36) NULL AFTER annual_leave_quota",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS job_title VARCHAR(100) NULL AFTER department_id",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS base_salary DECIMAL(15,2) DEFAULT 0.00 AFTER job_title",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_reset_token VARCHAR(100) NULL AFTER base_salary",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS login_just_occurred TINYINT DEFAULT 0 AFTER profile_reset_token",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_suspended TINYINT DEFAULT 0 AFTER login_just_occurred",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_deleted TINYINT DEFAULT 0 AFTER is_suspended",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_token VARCHAR(255) NULL DEFAULT NULL AFTER is_deleted"
    ];

    foreach ($alterQueries as $q) {
        $db->exec($q);
        echo "Executed: " . substr($q, 0, 45) . "...\n";
    }

    // 2. Create employee_data_correction_requests table
    $stmt = $db->query("SHOW CREATE TABLE users");
    $createUsersTable = $stmt->fetch();
    echo "CREATE TABLE USERS: " . $createUsersTable['Create Table'] . "\n";

    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS employee_data_correction_requests (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            category VARCHAR(50) NOT NULL,
            field VARCHAR(50) NOT NULL,
            old_value TEXT NULL,
            new_value TEXT NOT NULL,
            reason TEXT NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            rejection_reason TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    try {
        $db->exec($createTableQuery);
        echo "Table employee_data_correction_requests created or verified.\n";
    } catch (Exception $ex) {
        echo "FK with charset utf8mb4_unicode_ci failed, trying general...\n";
        $createTableQueryGeneral = str_replace('utf8mb4_unicode_ci', 'utf8mb4_general_ci', $createTableQuery);
        try {
            $db->exec($createTableQueryGeneral);
            echo "Table created with general collation.\n";
        } catch (Exception $ex2) {
            echo "FK with collation failed, trying without FK constraint...\n";
            $createTableQueryNoFK = "
                CREATE TABLE IF NOT EXISTS employee_data_correction_requests (
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
            ";
            $db->exec($createTableQueryNoFK);
            echo "Table created without FK constraint successfully.\n";
        }
    }

    // 3. Seed/Update default users
    $passwordHash = password_hash('password', PASSWORD_BCRYPT);

    // Helper to insert/update user
    function upsertUser($db, $email, $role, $firstName, $lastName, $passwordHash, $extraData = []) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $id = $user['id'];
            // Update
            $sql = "UPDATE users SET role = :role, first_name = :first_name, last_name = :last_name";
            $params = ['role' => $role, 'first_name' => $firstName, 'last_name' => $lastName, 'id' => $id];
            foreach ($extraData as $col => $val) {
                $sql .= ", $col = :$col";
                $params[$col] = $val;
            }
            $sql .= " WHERE id = :id";
            $db->prepare($sql)->execute($params);
            echo "Updated user: $email ($role)\n";
            return $id;
        } else {
            // Insert
            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $sql = "INSERT INTO users (id, email, password_hash, role, first_name, last_name";
            $valSql = "VALUES (:id, :email, :password_hash, :role, :first_name, :last_name";
            $params = [
                'id' => $id,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
                'first_name' => $firstName,
                'last_name' => $lastName
            ];
            foreach ($extraData as $col => $val) {
                $sql .= ", $col";
                $valSql .= ", :$col";
                $params[$col] = $val;
            }
            $sql .= ") " . $valSql . ")";
            $db->prepare($sql)->execute($params);
            echo "Inserted user: $email ($role)\n";
            return $id;
        }
    }

    // Helper to get department ID by name
    if (!function_exists('getDeptIdByName')) {
        function getDeptIdByName($db, $name) {
            $stmt = $db->prepare("SELECT id FROM departments WHERE name = ?");
            $stmt->execute([$name]);
            return $stmt->fetchColumn() ?: null;
        }
    }

    $deptIt = getDeptIdByName($db, 'Information Technology');
    $deptHr = getDeptIdByName($db, 'Human Resources');
    $deptFrontend = getDeptIdByName($db, 'Frontend Development');
    $deptBackend = getDeptIdByName($db, 'Backend Development');
    $deptDevOps = getDeptIdByName($db, 'Cloud & DevOps');
    $deptTa = getDeptIdByName($db, 'Talent Acquisition') ?: getDeptIdByName($db, 'Recruitment') ?: $deptHr;
    $deptHrOps = getDeptIdByName($db, 'Employee Relations & Ops') ?: $deptHr;

    // Seed Super Admin
    upsertUser($db, 'superadmin@mail.com', 'superadmin', 'Super', 'Admin', $passwordHash, [
        'job_title' => 'System Administrator',
        'base_salary' => 0.00
    ]);

    // Seed Recruiter
    upsertUser($db, 'recruiter@mail.com', 'recruiter', 'Senior', 'Recruiter', $passwordHash, [
        'department_id' => $deptTa,
        'job_title' => 'Talent Acquisition Specialist',
        'base_salary' => 9000000.00
    ]);

    // Seed Executive
    upsertUser($db, 'executive@mail.com', 'executive', 'Chief', 'Executive', $passwordHash, [
        'job_title' => 'Chief Executive Officer',
        'base_salary' => 0.00
    ]);

    // Seed Hiring Manager
    $managerId = upsertUser($db, 'hiring@mail.com', 'hiring_manager', 'Hiring', 'Manager', $passwordHash, [
        'department_id' => $deptIt,
        'job_title' => 'VP of Information Technology',
        'base_salary' => 20000000.00
    ]);

    // Seed HR Ops Admin
    upsertUser($db, 'hrops@mail.com', 'hr_ops', 'HR Ops', 'Admin', $passwordHash, [
        'department_id' => $deptHr,
        'job_title' => 'HR Operations Manager',
        'base_salary' => 12000000.00
    ]);

    // Purge any existing dummy users and their transactional records if they exist in the DB
    $dummyEmails = [
        'employee@mail.com',
        'budi.santoso@example.com',
        'amanda.putri@example.com',
        'rian.hidayat@example.com',
        'siti.aminah@example.com',
        'farhan.said@example.com'
    ];

    foreach ($dummyEmails as $email) {
        $stmtGet = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmtGet->execute([$email]);
        $uid = $stmtGet->fetchColumn();
        if ($uid) {
            // Delete manually from non-cascade table if needed
            $db->prepare("DELETE FROM employee_attendance WHERE user_id = ?")->execute([$uid]);
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
            echo "Cleaned up dummy user: $email\n";
        }
    }

    // Dummy users and dummy data seeding removed as requested.

    // 5. Create employee_reimbursement_claims table
    $createClaimsTableQuery = "
        CREATE TABLE IF NOT EXISTS employee_reimbursement_claims (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            category VARCHAR(50) NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            description TEXT NOT NULL,
            receipt_path VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            rejection_reason TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    try {
        $db->exec($createClaimsTableQuery);
        echo "Table employee_reimbursement_claims created successfully.\n";
    } catch (Exception $ex) {
        echo "Table creation with FK failed, fallback to table without constraint...\n";
        $createClaimsTableNoFK = "
            CREATE TABLE IF NOT EXISTS employee_reimbursement_claims (
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
        ";
        $db->exec($createClaimsTableNoFK);
        echo "Table employee_reimbursement_claims created without FK constraint successfully.\n";
    }

    // Dummy reimbursement claims seeding removed.

    // Dummy receipt files generation removed.

    // 8. Create employee_leave_requests table
    $createLeaveTableQuery = "
        CREATE TABLE IF NOT EXISTS employee_leave_requests (
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    try {
        $db->exec($createLeaveTableQuery);
        echo "Table employee_leave_requests created successfully.\n";
    } catch (Exception $ex) {
        echo "Table creation with FK failed, fallback to table without constraint...\n";
        $createLeaveTableNoFK = "
            CREATE TABLE IF NOT EXISTS employee_leave_requests (
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
        ";
        $db->exec($createLeaveTableNoFK);
        echo "Table employee_leave_requests created without FK constraint successfully.\n";
    }

    // Dummy leave requests seeding removed.

    // Dummy leave files generation removed.

    // 11. Create employee_attendance table
    $createAttendanceTableQuery = "
        CREATE TABLE IF NOT EXISTS employee_attendance (
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
            location_method VARCHAR(20) NULL COMMENT 'GPS or WIFI',
            ip_address VARCHAR(45) NULL,
            notes TEXT NULL,
            correction_reason TEXT NULL,
            corrected_by CHAR(36) NULL,
            corrected_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_date (user_id, attendance_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    try {
        $db->exec($createAttendanceTableQuery);
        echo "Table employee_attendance created or verified.\n";
    } catch (Exception $ex) {
        // Try without UNIQUE key
        $db->exec("
            CREATE TABLE IF NOT EXISTS employee_attendance (
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
                ip_address VARCHAR(45) NULL,
                notes TEXT NULL,
                correction_reason TEXT NULL,
                corrected_by CHAR(36) NULL,
                corrected_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        echo "Table employee_attendance created (fallback).\n";
    }

    // Dummy attendance records seeding removed.

    // 13. Add work_mode column to employee_attendance (if not exists)
    try {
        $db->exec("ALTER TABLE employee_attendance ADD COLUMN IF NOT EXISTS work_mode VARCHAR(10) DEFAULT 'WFO' AFTER location_method");
        echo "Column work_mode added/verified in employee_attendance.\n";
    } catch (Exception $ex) {
        echo "work_mode column may already exist or not supported: " . $ex->getMessage() . "\n";
    }

    // 13.1 Add reimbursement_limit and specific category limits columns to departments (if not exists)
    try {
        $db->exec("ALTER TABLE departments ADD COLUMN IF NOT EXISTS reimbursement_limit DECIMAL(15,2) DEFAULT NULL AFTER level");
        $db->exec("ALTER TABLE departments ADD COLUMN IF NOT EXISTS limit_medis DECIMAL(15,2) DEFAULT NULL AFTER reimbursement_limit");
        $db->exec("ALTER TABLE departments ADD COLUMN IF NOT EXISTS limit_transport DECIMAL(15,2) DEFAULT NULL AFTER limit_medis");
        $db->exec("ALTER TABLE departments ADD COLUMN IF NOT EXISTS limit_operasional DECIMAL(15,2) DEFAULT NULL AFTER limit_transport");
        $db->exec("ALTER TABLE departments ADD COLUMN IF NOT EXISTS limit_makan DECIMAL(15,2) DEFAULT NULL AFTER limit_operasional");
        echo "Columns reimbursement_limit, limit_medis, limit_transport, limit_operasional, limit_makan added/verified in departments.\n";
    } catch (Exception $ex) {
        echo "reimbursement_limit or category limits columns addition: " . $ex->getMessage() . "\n";
    }

    // 13.5 Create company_holidays table
    $db->exec("
        CREATE TABLE IF NOT EXISTS company_holidays (
            id CHAR(36) NOT NULL PRIMARY KEY,
            holiday_date DATE NOT NULL,
            description VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_holiday_date (holiday_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table company_holidays created or verified.\n";

    // 14. Create global_settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS global_settings (
            `key`        VARCHAR(100) NOT NULL PRIMARY KEY,
            `value`      TEXT         NOT NULL,
            `label`      VARCHAR(255) NULL,
            `group`      VARCHAR(50)  DEFAULT 'general',
            updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table global_settings created or verified.\n";

    // 15. Seed default global settings
    $defaultSettings = [
        // --- Attendance / Location ---
        ['office_lat',        '-6.2297',      'Latitude Kantor Pusat',            'attendance'],
        ['office_lng',        '106.8164',     'Longitude Kantor Pusat',           'attendance'],
        ['office_radius_m',   '150',          'Radius WFO (meter)',               'attendance'],
        ['work_start_time',   '08:00',        'Jam Masuk Standar',                'attendance'],
        ['work_end_time',     '17:00',        'Jam Pulang Standar',               'attendance'],
        ['grace_period_min',  '10',           'Toleransi Keterlambatan (menit)',  'attendance'],
        ['office_wifi_prefix','192.168.10.',  'Prefix IP WIFI Kantor',            'attendance'],
        // --- WFA ---
        ['wfa_allowed',       'true',         'Izinkan Work From Anywhere (WFA)', 'wfa'],
        ['wfa_days',          '',             'Hari WFA Diizinkan (kosong=semua)','wfa'],
        // --- Payroll ---
        ['payroll_tunj_jabatan_pct', '15',            'Persentase Tunjangan Jabatan (%)', 'payroll'],
        ['payroll_tunj_jabatan_cap', '2500000',       'Batas Maksimal Tunjangan Jabatan', 'payroll'],
        ['payroll_tunj_transport',   '1500000',       'Tunjangan Transport Flat (IDR)',   'payroll'],
        ['payroll_tunj_komunikasi',  '500000',        'Tunjangan Komunikasi Flat (IDR)',  'payroll'],
        // --- Reimbursement Category Defaults ---
        ['reimbursement_limit_medis',       '5000000',  'Plafon Medis Bulanan (Default)',       'reimbursement'],
        ['reimbursement_limit_transport',   '3000000',  'Plafon Transport Bulanan (Default)',   'reimbursement'],
        ['reimbursement_limit_operasional', '4000000',  'Plafon Operasional Bulanan (Default)', 'reimbursement'],
        ['reimbursement_limit_makan',       '2500000',  'Plafon Makan Bulanan (Default)',       'reimbursement'],
        // --- Department Default ---
        ['reimbursement_limit_department_default', '15000000', 'Plafon Departemen Bulanan (Default)', 'reimbursement'],
        // --- General App/Company Info ---
        ['app_name',                 'siCare',                 'Nama Aplikasi',                    'general'],
        ['app_company_name',         'PT SI CARE ENTERPRISE',  'Nama Perusahaan (PT)',             'general'],
        ['app_logo_icon',            'local_police',           'Logo Aplikasi (Material Icon)',    'general'],
        ['app_logo_type',            'icon',                   'Tipe Logo (icon/image)',           'general'],
        ['app_logo_image',           '',                       'URL Logo Gambar',                  'general'],
        ['app_idle_timeout_sec',     '0',                      'Batas Waktu Idle Aplikasi (Detik)', 'general'],
        ['google_maps_api_key',      '',                       'Google Maps API Key',              'general'],
    ];

    $stmtSetting = $db->prepare("
        INSERT INTO global_settings (`key`, `value`, `label`, `group`)
        VALUES (:key, :value, :label, :group)
        ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group` = VALUES(`group`)
    ");
    foreach ($defaultSettings as [$k, $v, $lbl, $grp]) {
        $stmtSetting->execute(['key' => $k, 'value' => $v, 'label' => $lbl, 'group' => $grp]);
    }
    echo "Seeded default HR settings.\n";

    // 16. Create sessions table for horizontal scaling session persistence
    $db->exec("
        CREATE TABLE IF NOT EXISTS sessions (
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
    echo "Table sessions created or verified.\n";

    // 16b. Create login_logs table for access tracking
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_logs (
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
    
    // Check if status column exists in login_logs
    $stmtStatus = $db->query("SHOW COLUMNS FROM login_logs LIKE 'status'");
    if (!$stmtStatus->fetch()) {
        $db->exec("ALTER TABLE login_logs ADD COLUMN status VARCHAR(50) DEFAULT 'Akses Aplikasi' AFTER user_agent");
        echo "Column 'status' added to login_logs.\n";
    }
    echo "Table login_logs created or verified.\n";

    // 17. Create employee_payroll table
    $createPayrollTableQuery = "
        CREATE TABLE IF NOT EXISTS employee_payroll (
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
            UNIQUE KEY uq_user_month (user_id, month_year),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    try {
        $db->exec($createPayrollTableQuery);
        echo "Table employee_payroll created successfully.\n";
    } catch (Exception $ex) {
        echo "Table creation with FK failed, fallback to table without constraint...\n";
        $createPayrollTableNoFK = "
            CREATE TABLE IF NOT EXISTS employee_payroll (
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
        ";
        $db->exec($createPayrollTableNoFK);
        echo "Table employee_payroll created without FK constraint successfully.\n";
    }

    // Add overtime and other_deduction columns to employee_payroll if they don't exist
    try {
        $db->exec("ALTER TABLE employee_payroll ADD COLUMN overtime DECIMAL(15,2) DEFAULT 0.00 AFTER reimbursement");
        echo "Column 'overtime' added to employee_payroll table.\n";
    } catch (Exception $e) {
        // Column may already exist
    }
    try {
        $db->exec("ALTER TABLE employee_payroll ADD COLUMN other_deduction DECIMAL(15,2) DEFAULT 0.00 AFTER pph21");
        echo "Column 'other_deduction' added to employee_payroll table.\n";
    } catch (Exception $e) {
        // Column may already exist
    }

    // 18. Create self_reflections table
    $createReflectionTableQuery = "
        CREATE TABLE IF NOT EXISTS self_reflections (
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
            UNIQUE KEY uq_user_period (user_id, period),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    try {
        $db->exec($createReflectionTableQuery);
        echo "Table self_reflections created successfully.\n";
    } catch (Exception $ex) {
        // Fallback without FK constraint
        $createReflectionTableNoFK = "
            CREATE TABLE IF NOT EXISTS self_reflections (
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
        ";
        $db->exec($createReflectionTableNoFK);
        echo "Table self_reflections created without FK constraint successfully.\n";
    }

    // Dummy self reflections seeding removed.

    // 19. Create mood_pulses table
    $createMoodPulsesTableQuery = "
        CREATE TABLE IF NOT EXISTS mood_pulses (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            period VARCHAR(20) NOT NULL,
            mood_rating VARCHAR(50) NOT NULL,
            workload_rating INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_mood_period (user_id, period),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    try {
        $db->exec($createMoodPulsesTableQuery);
        echo "Table mood_pulses created successfully.\n";
    } catch (Exception $ex) {
        $createMoodPulsesTableNoFK = "
            CREATE TABLE IF NOT EXISTS mood_pulses (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                period VARCHAR(20) NOT NULL,
                mood_rating VARCHAR(50) NOT NULL,
                workload_rating INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_mood_period (user_id, period)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $db->exec($createMoodPulsesTableNoFK);
        echo "Table mood_pulses created without FK constraint successfully.\n";
    }

    // Dummy mood pulses seeding removed.

    // 20. Create personal_journals table
    $createPersonalJournalsTableQuery = "
        CREATE TABLE IF NOT EXISTS personal_journals (
            id CHAR(36) PRIMARY KEY,
            user_id CHAR(36) NOT NULL,
            title VARCHAR(150) NULL,
            notes TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    try {
        $db->exec($createPersonalJournalsTableQuery);
        echo "Table personal_journals created successfully.\n";
    } catch (Exception $ex) {
        $createPersonalJournalsTableNoFK = "
            CREATE TABLE IF NOT EXISTS personal_journals (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                title VARCHAR(150) NULL,
                notes TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $db->exec($createPersonalJournalsTableNoFK);
        echo "Table personal_journals created without FK constraint successfully.\n";
    }

    // Dummy personal journals seeding removed.

    // 21. Create changelogs table
    $createChangelogsTableQuery = "
        CREATE TABLE IF NOT EXISTS changelogs (
            version VARCHAR(50) PRIMARY KEY,
            edition VARCHAR(50) NOT NULL,
            repo_type VARCHAR(50) NOT NULL DEFAULT 'monorepo',
            compiled_date DATE NOT NULL,
            migration_level VARCHAR(100) NOT NULL,
            summary LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createChangelogsTableQuery);
    
    // Ensure repo_type column exists if table was already created
    try {
        $db->exec("ALTER TABLE changelogs ADD COLUMN IF NOT EXISTS repo_type VARCHAR(50) NOT NULL DEFAULT 'monorepo' AFTER edition");
    } catch (Exception $ex) {
        // Ignored
    }
    
    // Ensure alias_name column exists for stable version aliases
    try {
        $db->exec("ALTER TABLE changelogs ADD COLUMN IF NOT EXISTS alias_name VARCHAR(100) DEFAULT NULL AFTER migration_level");
    } catch (Exception $ex) {
        // Ignored
    }
    
    // Migrate edition names: business -> enterprise
    try {
        $db->exec("UPDATE changelogs SET edition = 'enterprise' WHERE edition = 'business'");
    } catch (Exception $ex) {
        // Ignored
    }

    // Run new idempotent migrations (Roadmap Bab 1)
    echo "Running Migrator...\n";
    $migrator = new \App\Config\Migrator($db, __DIR__ . '/migrations');
    $migrator->run();
    
    echo "Table changelogs created successfully.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

