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
            employee_id VARCHAR(50) NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            profile_picture VARCHAR(255) NULL,
            no_telepon VARCHAR(20) NULL,
            alamat_domisili TEXT NULL,
            home_latitude DECIMAL(10,7) NULL,
            home_longitude DECIMAL(10,7) NULL,
            ktp_nik VARCHAR(20) NULL,
            nama_sesuai_ktp VARCHAR(100) NULL,
            alamat_ktp TEXT NULL,
            tanggal_lahir VARCHAR(50) NULL,
            jenis_kelamin VARCHAR(20) NULL,
            rombel VARCHAR(20) NULL,
            angkatan_masuk VARCHAR(50) NULL,
            bulan_bergabung VARCHAR(30) NULL,
            department_id CHAR(36) NULL,
            job_title VARCHAR(100) NULL,
            profile_reset_token VARCHAR(100) NULL,
            login_just_occurred TINYINT DEFAULT 0,
            is_suspended TINYINT DEFAULT 0,
            is_deleted TINYINT DEFAULT 0,
            remember_token VARCHAR(255) NULL DEFAULT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'candidate',
            role_id CHAR(36) NULL,
            onboarding_status VARCHAR(50) DEFAULT 'Pending',
            ktp_file VARCHAR(255) NULL,
            kk_file VARCHAR(255) NULL,
            foto_file VARCHAR(255) NULL,
            emergency_name VARCHAR(100) NULL,
            emergency_relation VARCHAR(50) NULL,
            emergency_phone VARCHAR(20) NULL,
            uniform_size VARCHAR(10) NULL,
            akses TINYINT(1) DEFAULT 0,
            id_kartu VARCHAR(255) NULL,
            id_qrcode VARCHAR(255) NULL,
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
            type VARCHAR(50) NOT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

    // Seed departments if empty
    $stmt = $db->query("SELECT COUNT(*) FROM departments");
    if ($stmt->fetchColumn() == 0) {
        $depts = [
            ['35133ec6-b31c-4b53-9092-95f2a1b1b001', 'Badan Pengurus Harian (BPH)'],
            ['35133ec6-b31c-4b53-9092-95f2a1b1b002', 'Post Production Staff'],
            ['35133ec6-b31c-4b53-9092-95f2a1b1b003', 'Editorial Staff'],
            ['35133ec6-b31c-4b53-9092-95f2a1b1b004', 'Broadcast Staff'],
            ['35133ec6-b31c-4b53-9092-95f2a1b1b005', 'Visual Production Staff']
        ];
        $insertDept = $db->prepare("INSERT INTO departments (id, name) VALUES (?, ?)");
        foreach ($depts as $d) {
            $insertDept->execute($d);
            echo "Seeded department: {$d[1]}\n";
        }
    }

    // 1. Add extra columns to users table
    $alterQueries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER email",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS rombel VARCHAR(20) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS angkatan_masuk VARCHAR(50) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS bulan_bergabung VARCHAR(30) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS no_telepon VARCHAR(20) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS alamat_domisili TEXT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS home_latitude DECIMAL(10,7) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS home_longitude DECIMAL(10,7) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS ktp_nik VARCHAR(20) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS nama_sesuai_ktp VARCHAR(100) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS alamat_ktp TEXT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS tanggal_lahir VARCHAR(50) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS jenis_kelamin VARCHAR(20) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS department_id CHAR(36) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS job_title VARCHAR(100) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_reset_token VARCHAR(100) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS login_just_occurred TINYINT DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_suspended TINYINT DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_deleted TINYINT DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_token VARCHAR(255) NULL DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS role_id CHAR(36) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS onboarding_status VARCHAR(50) DEFAULT 'Pending'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS ktp_file VARCHAR(255) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS kk_file VARCHAR(255) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS foto_file VARCHAR(255) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS emergency_name VARCHAR(100) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS emergency_relation VARCHAR(50) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS emergency_phone VARCHAR(20) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS uniform_size VARCHAR(10) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS akses TINYINT(1) DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS id_kartu VARCHAR(255) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS id_qrcode VARCHAR(255) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS employee_id VARCHAR(50) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS metode_presensi VARCHAR(50) DEFAULT 'default'"
    ];

    // Explicit check for metode_presensi on older MySQL/MariaDB engines
    try {
        $db->exec("ALTER TABLE `users` ADD COLUMN `metode_presensi` VARCHAR(50) DEFAULT 'default'");
    } catch (\Exception $e) {}

    foreach ($alterQueries as $q) {
        try {
            $db->exec($q);
            echo "Executed: " . substr($q, 0, 45) . "...\n";
        } catch (\PDOException $e) {
            // If ALTER with AFTER fails, retry without AFTER clause
            if (preg_match('/AFTER\s+\S+/i', $q)) {
                $fallbackQ = preg_replace('/AFTER\s+\S+/i', '', $q);
                try {
                    $db->exec($fallbackQ);
                    echo "Executed Fallback: " . substr($fallbackQ, 0, 45) . "...\n";
                } catch (\PDOException $e2) {
                    echo "Warning: " . $e2->getMessage() . "\n";
                }
            } else {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }

    // Drop unused corporate HR & recruitment columns if they exist
    $dropColumns = [
        'bank_name', 'bank_account_number', 'npwp_number', 'bpjs_tk', 'bpjs_kes',
        'status_pernikahan', 'reimburse_plafon_medis', 'reimburse_plafon_transport',
        'reimburse_plafon_operasional', 'reimburse_plafon_makan', 'npwp_file',
        'tabungan_file', 'bank_book_file', 'ekspektasi_gaji', 'riwayat_pekerjaan',
        'resume_file', 'cover_letter_file', 'base_salary', 'annual_leave_quota',
        'pendidikan_terakhir', 'tautan_profesional', 'emergency_contact_name',
        'emergency_contact_phone', 'emergency_contact_relation'
    ];
    foreach ($dropColumns as $col) {
        try {
            $db->exec("ALTER TABLE users DROP COLUMN {$col}");
            echo "Dropped unused column: {$col}\n";
        } catch (\PDOException $e) {
            // Column may not exist, ignore
        }
    }

    // Drop corporate HR tables if they exist
    $dropTables = ['employee_payroll', 'employee_reimbursement_claims'];
    foreach ($dropTables as $tbl) {
        try {
            $db->exec("DROP TABLE IF EXISTS {$tbl}");
            echo "Dropped unused corporate table: {$tbl}\n";
        } catch (\PDOException $e) {
            // Ignore if table does not exist
        }
    }

    // 2. Create employee_data_correction_requests table
    $stmt = $db->query("SHOW CREATE TABLE users");
    try {
        $db->exec("DELETE FROM sys_menus WHERE url_route IN ('employee/finance', 'employee/reimbursements', 'hrops/payroll')");
        
        $stmtChk = $db->query("SELECT id FROM sys_menus WHERE url_route = 'admin/mass-attendance' LIMIT 1");
        if (!$stmtChk->fetch()) {
            $menuId = 'd945f8a0-5621-4a2b-9876-massattndnc01';
            $db->exec("INSERT INTO sys_menus (id, title, url_route, icon, sort_order, created_at, updated_at) 
                       VALUES ('{$menuId}', 'Presensi Massal', 'admin/mass-attendance', 'contactless', 2, NOW(), NOW())");
            
            $db->exec("INSERT IGNORE INTO menu_permissions (id, menu_id, role_id, created_at, updated_at) VALUES
                       (UUID(), '{$menuId}', '1b9c8d7e-6f5a-4b3c-8d2e-1a0f9e8d7c6b', NOW(), NOW()),
                       (UUID(), '{$menuId}', '8f5d7b6c-9c3f-4e08-8e67-d86b976f92c1', NOW(), NOW())");
            $db->exec("ALTER TABLE employee_attendance MODIFY COLUMN location_method VARCHAR(100) DEFAULT NULL");
            echo "Added Presensi Massal menu, permissions, and expanded location_method column.\n";
        }
    } catch (\PDOException $e) {
        // Table or routes may not exist
    }

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

    // 3. Seed/Update default users (Password: @Password2025)
    $passwordHash = password_hash('@Password2025', PASSWORD_BCRYPT);

    // Helper to insert/update user
    function upsertUser($db, $email, $role, $firstName, $lastName, $passwordHash, $extraData = []) {
        $stmtR = $db->prepare("SELECT id FROM roles WHERE name = :role LIMIT 1");
        $stmtR->execute(['role' => $role]);
        $roleId = $stmtR->fetchColumn() ?: null;

        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $id = $user['id'];
            // Update
            $sql = "UPDATE users SET role = :role, role_id = :role_id, first_name = :first_name, last_name = :last_name, password_hash = :password_hash";
            $params = ['role' => $role, 'role_id' => $roleId, 'first_name' => $firstName, 'last_name' => $lastName, 'password_hash' => $passwordHash, 'id' => $id];
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
            $sql = "INSERT INTO users (id, email, password_hash, role, role_id, first_name, last_name";
            $valSql = "VALUES (:id, :email, :password_hash, :role, :role_id, :first_name, :last_name";
            $params = [
                'id' => $id,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
                'role_id' => $roleId,
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

    // 1 User per Role
    upsertUser($db, 'superadmin@mail.com', 'superadmin', 'Super', 'Admin', $passwordHash, [
        'job_title' => 'Super Admin'
    ]);

    upsertUser($db, 'admin@mail.com', 'admin', 'Admin', 'Operasional', $passwordHash, [
        'job_title' => 'Admin Sistem'
    ]);

    upsertUser($db, 'hrops@mail.com', 'hr_ops', 'Pengurus', 'BPH', $passwordHash, [
        'department_id' => 'dept-bph',
        'job_title' => 'Pengurus BPH'
    ]);

    upsertUser($db, 'recruiter@mail.com', 'recruiter', 'Tim', 'Rekruter', $passwordHash, [
        'department_id' => 'dept-bph',
        'job_title' => 'Panitia Rekrutmen'
    ]);

    upsertUser($db, 'executive@mail.com', 'executive', 'Pengurus', 'Eksekutif', $passwordHash, [
        'department_id' => 'dept-bph',
        'job_title' => 'Pengurus Eksekutif'
    ]);

    upsertUser($db, 'employee@mail.com', 'employee', 'Anggota', 'Staff', $passwordHash, [
        'department_id' => 'dept-postprod',
        'job_title' => 'Anggota Ekstrakurikuler'
    ]);

    upsertUser($db, 'candidate@mail.com', 'candidate', 'Pendaftar', 'Kandidat', $passwordHash, [
        'rombel' => 'X1',
        'angkatan_masuk' => '2024 (Angkatan 34)',
        'bulan_bergabung' => 'Agustus',
        'no_telepon' => '6281234567890'
    ]);

    // 1 Koordinator (Hiring Manager) per 4 Divisi Operasional
    upsertUser($db, 'kadiv.postprod@mail.com', 'hiring_manager', 'Koordinator', 'Post Production', $passwordHash, [
        'department_id' => 'dept-postprod',
        'job_title' => 'Kepala Divisi Post Production'
    ]);

    upsertUser($db, 'kadiv.editorial@mail.com', 'hiring_manager', 'Koordinator', 'Editorial', $passwordHash, [
        'department_id' => 'dept-editorial',
        'job_title' => 'Kepala Divisi Editorial'
    ]);

    upsertUser($db, 'kadiv.broadcast@mail.com', 'hiring_manager', 'Koordinator', 'Broadcast', $passwordHash, [
        'department_id' => 'dept-broadcast',
        'job_title' => 'Kepala Divisi Broadcast'
    ]);

    upsertUser($db, 'kadiv.visprod@mail.com', 'hiring_manager', 'Koordinator', 'Visual Production', $passwordHash, [
        'department_id' => 'dept-visprod',
        'job_title' => 'Kepala Divisi Visual Production'
    ]);

    // Cleanup legacy hiring@mail.com if exists
    $stmtGet = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmtGet->execute(['hiring@mail.com']);
    $legacyHiringId = $stmtGet->fetchColumn();
    if ($legacyHiringId) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$legacyHiringId]);
    }

    // Purge any existing dummy users and their transactional records if they exist in the DB
    $dummyEmails = [
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

    // 5. Employee reimbursement claims table removed for high school extracurricular setup.

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

    // 13. Add work_mode, metode_presensi, clock_in_photo, clock_out_photo columns to employee_attendance (if not exists)
    try {
        $db->exec("ALTER TABLE employee_attendance ADD COLUMN IF NOT EXISTS work_mode VARCHAR(10) DEFAULT 'WFO' AFTER location_method");
        $db->exec("ALTER TABLE employee_attendance ADD COLUMN IF NOT EXISTS metode_presensi VARCHAR(20) DEFAULT 'default' AFTER work_mode");
        $db->exec("ALTER TABLE employee_attendance ADD COLUMN IF NOT EXISTS clock_in_photo VARCHAR(255) NULL AFTER metode_presensi");
        $db->exec("ALTER TABLE employee_attendance ADD COLUMN IF NOT EXISTS clock_out_photo VARCHAR(255) NULL AFTER clock_in_photo");
        echo "Columns work_mode, metode_presensi, clock_in_photo, clock_out_photo added/verified in employee_attendance.\n";
    } catch (Exception $ex) {
        echo "Attendance columns may already exist or not supported: " . $ex->getMessage() . "\n";
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

    // 17. Employee payroll table removed for high school extracurricular setup.

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

    // 22. Create portal_applications table
    $createPortalAppsQuery = "
        CREATE TABLE IF NOT EXISTS portal_applications (
            id CHAR(36) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            sort_order INT DEFAULT 0,
            description TEXT NULL,
            logo_url VARCHAR(255) NULL,
            icon_material VARCHAR(100) DEFAULT 'apps',
            icon_color VARCHAR(50) DEFAULT '#3b82f6',
            portal_url VARCHAR(255) NULL,
            login_url VARCHAR(255) NULL,
            visibility_type VARCHAR(20) NOT NULL DEFAULT 'global',
            allowed_roles TEXT NULL,
            allowed_departments TEXT NULL,
            is_active TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createPortalAppsQuery);
    echo "Table portal_applications created or verified.\n";

    // 23. Create job_requisitions and job_requisition_approvals tables
    $createJobReqsQuery = "
        CREATE TABLE IF NOT EXISTS job_requisitions (
            id CHAR(36) PRIMARY KEY,
            req_number VARCHAR(50) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            department_id CHAR(36) NOT NULL,
            requested_by CHAR(36) NOT NULL,
            headcount INT NOT NULL DEFAULT 1,
            employment_type VARCHAR(50) NOT NULL DEFAULT 'full_time',
            min_salary DECIMAL(15, 2) DEFAULT 0.00,
            max_salary DECIMAL(15, 2) DEFAULT 0.00,
            job_description TEXT NULL,
            requirements TEXT NULL,
            reason TEXT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending_dept_clevel',
            icon VARCHAR(50) DEFAULT 'work',
            card_color VARCHAR(50) DEFAULT '#0c145e',
            logo_url VARCHAR(255) DEFAULT NULL,
            dept_clevel_approved_at TIMESTAMP NULL,
            dept_clevel_approved_by CHAR(36) NULL,
            cfo_approved_at TIMESTAMP NULL,
            cfo_approved_by CHAR(36) NULL,
            chro_approved_at TIMESTAMP NULL,
            chro_approved_by CHAR(36) NULL,
            ceo_approved_at TIMESTAMP NULL,
            ceo_approved_by CHAR(36) NULL,
            published_at TIMESTAMP NULL,
            published_by CHAR(36) NULL,
            rejected_at TIMESTAMP NULL,
            rejected_by CHAR(36) NULL,
            rejection_reason TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createJobReqsQuery);

    try {
        $db->exec("ALTER TABLE job_requisitions ADD COLUMN IF NOT EXISTS icon VARCHAR(50) DEFAULT 'work'");
        $db->exec("ALTER TABLE job_requisitions ADD COLUMN IF NOT EXISTS card_color VARCHAR(50) DEFAULT '#0c145e'");
        $db->exec("ALTER TABLE job_requisitions ADD COLUMN IF NOT EXISTS logo_url VARCHAR(255) DEFAULT NULL");
    } catch (\Exception $e) {}

    $createJobReqApprovalsQuery = "
        CREATE TABLE IF NOT EXISTS job_requisition_approvals (
            id CHAR(36) PRIMARY KEY,
            requisition_id CHAR(36) NOT NULL,
            action_by CHAR(36) NOT NULL,
            action_role VARCHAR(50) NOT NULL,
            stage VARCHAR(50) NOT NULL,
            decision VARCHAR(20) NOT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createJobReqApprovalsQuery);
    try {
        $db->exec("ALTER TABLE job_requisitions MODIFY COLUMN status ENUM('pending_dept_clevel', 'pending_cfo_chro', 'pending_ceo', 'approved', 'published', 'closed', 'rejected') NOT NULL DEFAULT 'pending_dept_clevel'");
    } catch (\Exception $e) {}

    $createJobAppsQuery = "
        CREATE TABLE IF NOT EXISTS job_applications (
            id CHAR(36) PRIMARY KEY,
            requisition_id CHAR(36) NOT NULL,
            candidate_id CHAR(36) NULL,
            candidate_name VARCHAR(255) NOT NULL,
            candidate_email VARCHAR(255) NOT NULL,
            candidate_phone VARCHAR(50) NULL,
            portfolio_url VARCHAR(255) NULL,
            resume_path VARCHAR(255) NULL,
            cover_letter TEXT NULL,
            status VARCHAR(50) DEFAULT 'submitted',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createJobAppsQuery);
    try {
        $db->exec("ALTER TABLE job_applications ADD COLUMN IF NOT EXISTS portfolio_url VARCHAR(255) NULL AFTER candidate_phone");
    } catch (\Exception $e) {}

    $createJobInterviewsQuery = "
        CREATE TABLE IF NOT EXISTS job_interviews (
            id CHAR(36) PRIMARY KEY,
            application_id CHAR(36) NOT NULL,
            interviewer_id CHAR(36) NOT NULL,
            interview_type VARCHAR(20) DEFAULT 'online',
            interview_date DATE NOT NULL,
            interview_time VARCHAR(20) NOT NULL,
            location_or_link VARCHAR(255) NOT NULL,
            candidate_status VARCHAR(50) DEFAULT 'pending',
            candidate_notes TEXT NULL,
            manager_score INT NULL,
            manager_feedback TEXT NULL,
            manager_decision VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createJobInterviewsQuery);
    try {
        $db->exec("ALTER TABLE job_interviews ADD COLUMN IF NOT EXISTS interview_type VARCHAR(20) DEFAULT 'online' AFTER interviewer_id");
        $db->exec("ALTER TABLE job_interviews ADD COLUMN IF NOT EXISTS score_technical INT NULL AFTER manager_score");
        $db->exec("ALTER TABLE job_interviews ADD COLUMN IF NOT EXISTS score_problem_solving INT NULL AFTER score_technical");
        $db->exec("ALTER TABLE job_interviews ADD COLUMN IF NOT EXISTS score_communication INT NULL AFTER score_problem_solving");
        $db->exec("ALTER TABLE job_interviews ADD COLUMN IF NOT EXISTS score_cultural_fit INT NULL AFTER score_communication");
    } catch (\Exception $e) {}

    $createJobOffersQuery = "
        CREATE TABLE IF NOT EXISTS job_offers (
            id CHAR(36) PRIMARY KEY,
            application_id CHAR(36) NOT NULL,
            offering_letter_path VARCHAR(255) NOT NULL,
            offered_salary DECIMAL(15,2) NOT NULL,
            status VARCHAR(50) DEFAULT 'sent',
            candidate_signature TEXT NULL,
            signed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($createJobOffersQuery);

    echo "Tables job_requisitions, job_requisition_approvals, job_applications, job_interviews, and job_offers created or verified.\n";

    echo "Running Migrator...\n";
    $migrator = new \App\Config\Migrator($db, __DIR__ . '/migrations');
    $migrator->run();
    
    // Auto-sync role_id and role for all users
    $db->exec("
        UPDATE users u 
        JOIN roles r ON r.name = u.role 
        SET u.role_id = r.id 
        WHERE u.role_id IS NULL OR u.role_id = '' OR u.role_id = '3'
    ");
    $db->exec("
        UPDATE users u 
        JOIN roles r ON r.id = u.role_id 
        SET u.role = r.name 
        WHERE u.role IS NULL OR u.role = ''
    ");
    echo "Role IDs automatically synchronized for all users.\n";
    echo "Table changelogs created successfully.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

