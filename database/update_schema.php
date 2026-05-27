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
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS base_salary DECIMAL(15,2) DEFAULT 0.00 AFTER job_title"
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

    // Seed Alex Rivera (Employee)
    $alexId = upsertUser($db, 'employee@mail.com', 'employee', 'Alex', 'Rivera', $passwordHash, [
        'employee_id' => 'EMP-2026-0033',
        'no_telepon' => '+62 812-3456-7890',
        'alamat_domisili' => 'Jl. Gading Serpong Boulevard, Cluster Emerald No. 88, Tangerang, Banten 15810',
        'ktp_nik' => '3275012309990001',
        'nama_sesuai_ktp' => 'ALEX RIVERA',
        'alamat_ktp' => 'Jl. Boulevard Gading Serpong No. 12, Tangerang, Banten 15810',
        'bank_name' => 'Bank Central Asia (BCA)',
        'bank_account_number' => '8012345678',
        'npwp_number' => '12.345.678.9-012.000',
        'bpjs_tk' => '12098765432',
        'bpjs_kes' => '0001234567890',
        'tanggal_lahir' => '12 September 1995',
        'status_pernikahan' => 'Belum Menikah',
        'jenis_kelamin' => 'Laki-Laki',
        'department_id' => $deptFrontend ?: $deptIt,
        'job_title' => 'Senior Frontend Engineer',
        'base_salary' => 15000000.00
    ]);

    // Seed Budi Santoso (Employee)
    $budiId = upsertUser($db, 'budi.santoso@example.com', 'employee', 'Budi', 'Santoso', $passwordHash, [
        'employee_id' => 'EMP-2026-0034',
        'no_telepon' => '+62 813-1111-2222',
        'alamat_domisili' => 'Jl. Boulevard Raya No. 12, Tangerang',
        'ktp_nik' => '3275012309990003',
        'nama_sesuai_ktp' => 'BUDI SANTOSO',
        'alamat_ktp' => 'Jl. Boulevard Raya No. 12, Tangerang',
        'bank_name' => 'Bank Mandiri',
        'bank_account_number' => '1234567890123',
        'npwp_number' => '12.345.678.9-012.002',
        'bpjs_tk' => '12098765434',
        'bpjs_kes' => '0001234567892',
        'tanggal_lahir' => '15 Agustus 1993',
        'status_pernikahan' => 'Menikah',
        'jenis_kelamin' => 'Laki-Laki',
        'department_id' => $deptBackend ?: $deptIt,
        'job_title' => 'Lead Software Engineer',
        'base_salary' => 18000000.00
    ]);

    // Seed Amanda Putri (Employee)
    $amandaId = upsertUser($db, 'amanda.putri@example.com', 'employee', 'Amanda', 'Putri', $passwordHash, [
        'employee_id' => 'EMP-2026-0035',
        'no_telepon' => '+62 812-9876-5432',
        'alamat_domisili' => 'Jl. Raya Serpong No. 100, Tangerang, Banten 15310',
        'ktp_nik' => '3275012309990002',
        'nama_sesuai_ktp' => 'AMANDA PUTRI',
        'alamat_ktp' => 'Jl. Raya Serpong No. 100, Tangerang, Banten 15310',
        'bank_name' => 'Bank Central Asia (BCA)',
        'bank_account_number' => '8012345688',
        'npwp_number' => '12.345.678.9-012.001',
        'bpjs_tk' => '12098765433',
        'bpjs_kes' => '0001234567891',
        'tanggal_lahir' => '10 Mei 1997',
        'status_pernikahan' => 'Belum Menikah',
        'jenis_kelamin' => 'Perempuan',
        'department_id' => $deptFrontend ?: $deptIt,
        'job_title' => 'UI/UX Designer',
        'base_salary' => 10000000.00
    ]);

    // Seed Rian Hidayat (Employee)
    $rianId = upsertUser($db, 'rian.hidayat@example.com', 'employee', 'Rian', 'Hidayat', $passwordHash, [
        'employee_id' => 'EMP-2026-0036',
        'no_telepon' => '+62 812-5555-6666',
        'alamat_domisili' => 'Jl. Alam Sutera No. 45, Tangerang',
        'ktp_nik' => '3275012309990005',
        'nama_sesuai_ktp' => 'RIAN HIDAYAT',
        'alamat_ktp' => 'Jl. Alam Sutera No. 45, Tangerang',
        'bank_name' => 'Bank Mandiri',
        'bank_account_number' => '1234567890999',
        'npwp_number' => '12.345.678.9-012.005',
        'bpjs_tk' => '12098765439',
        'bpjs_kes' => '0001234567899',
        'tanggal_lahir' => '25 Juni 1994',
        'status_pernikahan' => 'Menikah',
        'jenis_kelamin' => 'Laki-Laki',
        'department_id' => $deptDevOps ?: $deptIt,
        'job_title' => 'DevOps Engineer',
        'base_salary' => 14000000.00
    ]);

    // Seed Siti Aminah (Employee)
    $sitiId = upsertUser($db, 'siti.aminah@example.com', 'employee', 'Siti', 'Aminah', $passwordHash, [
        'employee_id' => 'EMP-2026-0037',
        'no_telepon' => '+62 813-3333-4444',
        'alamat_domisili' => 'Jl. Gading Gajah No. 50, Tangerang',
        'ktp_nik' => '3275012309990004',
        'nama_sesuai_ktp' => 'SITI AMINAH',
        'alamat_ktp' => 'Jl. Gading Gajah No. 50, Tangerang',
        'bank_name' => 'Bank Central Asia (BCA)',
        'bank_account_number' => '8012345699',
        'npwp_number' => '00.000.000.0-000.000',
        'bpjs_tk' => '12098765435',
        'bpjs_kes' => '0001234567893',
        'tanggal_lahir' => '20 November 1996',
        'status_pernikahan' => 'Belum Menikah',
        'jenis_kelamin' => 'Perempuan',
        'department_id' => $deptHrOps ?: $deptHr,
        'job_title' => 'HR Staff',
        'base_salary' => 7000000.00
    ]);

    // Seed Farhan Said (Employee)
    $farhanId = upsertUser($db, 'farhan.said@example.com', 'employee', 'Farhan', 'Said', $passwordHash, [
        'employee_id' => 'EMP-2026-0038',
        'no_telepon' => '+62 813-7777-8888',
        'alamat_domisili' => 'Jl. BSD Grand Boulevard No. 10, Tangerang',
        'ktp_nik' => '3275012309990006',
        'nama_sesuai_ktp' => 'FARHAN SAID',
        'alamat_ktp' => 'Jl. BSD Grand Boulevard No. 10, Tangerang',
        'bank_name' => 'Bank Negara Indonesia (BNI)',
        'bank_account_number' => '0823456789',
        'npwp_number' => '12.345.678.9-012.006',
        'bpjs_tk' => '12098765438',
        'bpjs_kes' => '0001234567898',
        'tanggal_lahir' => '18 Maret 1992',
        'status_pernikahan' => 'Menikah',
        'jenis_kelamin' => 'Laki-Laki',
        'department_id' => $deptTa ?: $deptHr,
        'job_title' => 'Technical Recruiter',
        'base_salary' => 8000000.00
    ]);

    // 4. Seed initial correction requests
    $countCorrection = $db->query("SELECT COUNT(*) FROM employee_data_correction_requests")->fetchColumn();
    if ($countCorrection == 0) {
        $dummyRequests = [
            [
                'id' => 'req-alex-1',
                'user_id' => $alexId,
                'category' => 'finansial',
                'field' => 'bank_account_number',
                'old_value' => '8012345678',
                'new_value' => '1234567890',
                'reason' => 'Rekening gaji dipindahkan ke rekening BCA baru demi kemudahan penarikan bulanan.',
                'file_path' => 'buku_tabungan_alex.png',
                'status' => 'pending'
            ],
            [
                'id' => 'req-amanda-1',
                'user_id' => $amandaId,
                'category' => 'data_pribadi',
                'field' => 'status_pernikahan',
                'old_value' => 'Belum Menikah',
                'new_value' => 'Menikah',
                'reason' => 'Penyesuaian status PTKP pajak setelah pernikahan pada tanggal 10 Mei 2026.',
                'file_path' => 'buku_nikah_amanda.pdf',
                'status' => 'pending'
            ],
            [
                'id' => 'req-budi-1',
                'user_id' => $budiId,
                'category' => 'kependudukan',
                'field' => 'alamat_ktp',
                'old_value' => 'Jl. Melati No. 5, Jakarta',
                'new_value' => 'Jl. Boulevard Raya No. 12, Tangerang',
                'reason' => 'Pindah domisili tetap sesuai KTP baru.',
                'file_path' => 'ktp_baru_budi.jpg',
                'status' => 'approved'
            ],
            [
                'id' => 'req-siti-1',
                'user_id' => $sitiId,
                'category' => 'pajak_asuransi',
                'field' => 'npwp_number',
                'old_value' => '00.000.000.0-000.000',
                'new_value' => '12.345.678.9-012.000',
                'reason' => 'Pendaftaran NPWP baru selesai diproses kantor pajak.',
                'file_path' => 'npwp_siti.jpg',
                'status' => 'rejected',
                'rejection_reason' => 'Unggahan dokumen NPWP terpotong di bagian nomor dan nama sehingga tidak valid. Silakan ajukan ulang dengan scan dokumen penuh yang terbaca jelas.'
            ]
        ];

        $stmt = $db->prepare("INSERT INTO employee_data_correction_requests (id, user_id, category, field, old_value, new_value, reason, file_path, status, rejection_reason) VALUES (:id, :user_id, :category, :field, :old_value, :new_value, :reason, :file_path, :status, :rejection_reason)");

        foreach ($dummyRequests as $r) {
            $stmt->execute([
                'id' => $r['id'],
                'user_id' => $r['user_id'],
                'category' => $r['category'],
                'field' => $r['field'],
                'old_value' => $r['old_value'],
                'new_value' => $r['new_value'],
                'reason' => $r['reason'],
                'file_path' => $r['file_path'],
                'status' => $r['status'],
                'rejection_reason' => $r['rejection_reason'] ?? null
            ]);
        }
        echo "Seed correction requests complete.\n";
    } else {
        echo "Skipping correction requests seeding because data already exists.\n";
    }

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

    // 6. Seed employee_reimbursement_claims
    $countClaims = $db->query("SELECT COUNT(*) FROM employee_reimbursement_claims")->fetchColumn();
    if ($countClaims == 0) {
        $dummyClaims = [
            [
                'id' => 'claim-budi-1',
                'user_id' => $budiId,
                'category' => 'operasional',
                'amount' => 1250000.00,
                'description' => 'Pembelian Keyboard Mechanical Keychron K2 untuk efisiensi coding di kantor.',
                'receipt_path' => 'keychron.jpg',
                'status' => 'pending',
                'rejection_reason' => null
            ],
            [
                'id' => 'claim-amanda-1',
                'user_id' => $amandaId,
                'category' => 'medis',
                'amount' => 450000.00,
                'description' => 'Pembelian kacamata lensa anti-radiasi komputer sesuai rekomendasi medis optik.',
                'receipt_path' => 'kacamata.jpg',
                'status' => 'pending',
                'rejection_reason' => null
            ],
            [
                'id' => 'claim-rian-1',
                'user_id' => $rianId,
                'category' => 'transport',
                'amount' => 320000.00,
                'description' => 'Bensin & Tol kunjungan pusat data (Data Center) untuk pemeliharaan server tahunan.',
                'receipt_path' => 'tol.jpg',
                'status' => 'approved',
                'rejection_reason' => null
            ],
            [
                'id' => 'claim-siti-1',
                'user_id' => $sitiId,
                'category' => 'makan',
                'amount' => 850000.00,
                'description' => 'Makan malam diskusi kerja dengan auditor eksternal ISO 27001.',
                'receipt_path' => 'makan.jpg',
                'status' => 'pending',
                'rejection_reason' => null
            ],
            [
                'id' => 'claim-farhan-1',
                'user_id' => $farhanId,
                'category' => 'medis',
                'amount' => 3400000.00,
                'description' => 'Rawat jalan rumah sakit spesialis mata untuk keluhan mata minus mendadak.',
                'receipt_path' => 'mata.jpg',
                'status' => 'rejected',
                'rejection_reason' => 'Klaim ditolak secara otomatis oleh sistem karena sisa saldo (plafon) rawat jalan tahunan karyawan yang bersangkutan telah mencapai batas limit maksimal (Rp 0).'
            ]
        ];

        $stmt = $db->prepare("
            INSERT INTO employee_reimbursement_claims (id, user_id, category, amount, description, receipt_path, status, rejection_reason)
            VALUES (:id, :user_id, :category, :amount, :description, :receipt_path, :status, :rejection_reason)
        ");

        foreach ($dummyClaims as $c) {
            $stmt->execute([
                'id' => $c['id'],
                'user_id' => $c['user_id'],
                'category' => $c['category'],
                'amount' => $c['amount'],
                'description' => $c['description'],
                'receipt_path' => $c['receipt_path'],
                'status' => $c['status'],
                'rejection_reason' => $c['rejection_reason']
            ]);
        }
        echo "Seed reimbursement claims complete.\n";
    } else {
        echo "Skipping reimbursement claims seeding because data already exists.\n";
    }

    // 7. Write secure dummy receipt files
    $receiptsDir = __DIR__ . '/../storage/secured_storage/receipts/';
    if (!file_exists($receiptsDir)) {
        mkdir($receiptsDir, 0755, true);
    }

    // A tiny transparent valid 1x1 PNG image as dummy receipt content
    $tinyPngB64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    $tinyPngBytes = base64_decode($tinyPngB64);

    $dummyFiles = ['keychron.jpg', 'kacamata.jpg', 'tol.jpg', 'makan.jpg', 'mata.jpg'];
    foreach ($dummyFiles as $fn) {
        file_put_contents($receiptsDir . $fn, $tinyPngBytes);
        echo "Created dummy receipt file: $fn\n";
    }

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

    // 9. Seed employee_leave_requests
    $countLeaves = $db->query("SELECT COUNT(*) FROM employee_leave_requests")->fetchColumn();
    if ($countLeaves == 0) {
        $dummyLeaves = [
            [
                'id' => 'leave-rian-1',
                'user_id' => $rianId,
                'leave_type' => 'cuti sakit',
                'start_date' => '2026-05-20',
                'end_date' => '2026-05-21',
                'duration' => 2,
                'reason' => 'Demam tinggi disertai sakit kepala parah.',
                'attachment_path' => 'surat_dokter_rian.pdf',
                'status' => 'approved',
                'rejection_reason' => null
            ],
            [
                'id' => 'leave-amanda-1',
                'user_id' => $amandaId,
                'leave_type' => 'cuti tahunan',
                'start_date' => '2026-05-25',
                'end_date' => '2026-05-27',
                'duration' => 3,
                'reason' => 'Liburan keluarga ke luar kota.',
                'attachment_path' => null,
                'status' => 'pending',
                'rejection_reason' => null
            ],
            [
                'id' => 'leave-budi-1',
                'user_id' => $budiId,
                'leave_type' => 'cuti tahunan',
                'start_date' => '2026-05-22',
                'end_date' => '2026-05-22',
                'duration' => 1,
                'reason' => 'Mengurus administrasi KPR perbankan.',
                'attachment_path' => null,
                'status' => 'pending',
                'rejection_reason' => null
            ],
            [
                'id' => 'leave-siti-1',
                'user_id' => $sitiId,
                'leave_type' => 'cuti melahirkan',
                'start_date' => '2026-06-01',
                'end_date' => '2026-08-29',
                'duration' => 90,
                'reason' => 'Persalinan anak pertama (HPL awal Juni).',
                'attachment_path' => 'rujukan_hpl.pdf',
                'status' => 'pending',
                'rejection_reason' => null
            ],
            [
                'id' => 'leave-farhan-1',
                'user_id' => $farhanId,
                'leave_type' => 'cuti tahunan',
                'start_date' => '2026-05-12',
                'end_date' => '2026-05-16',
                'duration' => 5,
                'reason' => 'Liburan pasca rilis besar produk.',
                'attachment_path' => null,
                'status' => 'rejected',
                'rejection_reason' => 'Pengajuan cuti ditolak karena ada peluncuran fitur krusial yang memerlukan kehadiran Product Owner secara fisik di kantor pada periode tersebut.'
            ]
        ];

        $stmt = $db->prepare("
            INSERT INTO employee_leave_requests (id, user_id, leave_type, start_date, end_date, duration, reason, attachment_path, status, rejection_reason)
            VALUES (:id, :user_id, :leave_type, :start_date, :end_date, :duration, :reason, :attachment_path, :status, :rejection_reason)
        ");

        foreach ($dummyLeaves as $l) {
            $stmt->execute([
                'id' => $l['id'],
                'user_id' => $l['user_id'],
                'leave_type' => $l['leave_type'],
                'start_date' => $l['start_date'],
                'end_date' => $l['end_date'],
                'duration' => $l['duration'],
                'reason' => $l['reason'],
                'attachment_path' => $l['attachment_path'],
                'status' => $l['status'],
                'rejection_reason' => $l['rejection_reason']
            ]);
        }
        echo "Seed leave requests complete.\n";
    } else {
        echo "Skipping leave requests seeding because data already exists.\n";
    }

    // 10. Write secure dummy leave files
    $leavesDir = __DIR__ . '/../storage/secured_storage/leaves/';
    if (!file_exists($leavesDir)) {
        mkdir($leavesDir, 0755, true);
    }

    $dummyLeaveFiles = ['surat_dokter_rian.pdf', 'rujukan_hpl.pdf'];
    foreach ($dummyLeaveFiles as $fn) {
        file_put_contents($leavesDir . $fn, $tinyPngBytes);
        echo "Created dummy leave file: $fn\n";
    }

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

    // 12. Seed dummy attendance records for the past 30 days
    $countAttendance = $db->query("SELECT COUNT(*) FROM employee_attendance")->fetchColumn();
    if ($countAttendance == 0) {
        // Helper to generate UUID
        if (!function_exists('genUuid')) {
            function genUuid() {
                return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
            }
        }

        $employeeIds = [
            $alexId    => ['name' => 'Alex Rivera',   'pattern' => 'good'],
            $budiId    => ['name' => 'Budi Santoso',  'pattern' => 'good'],
            $amandaId  => ['name' => 'Amanda Putri',  'pattern' => 'late'],
            $rianId    => ['name' => 'Rian Hidayat',  'pattern' => 'absent'],
            $sitiId    => ['name' => 'Siti Aminah',   'pattern' => 'good'],
            $farhanId  => ['name' => 'Farhan Said',   'pattern' => 'late'],
        ];

        $officeIp = '192.168.10.45';
        $officeLat = -6.2297;
        $officeLng = 106.8164;
        $stmtAtt = $db->prepare("
            INSERT IGNORE INTO employee_attendance
                (id, user_id, attendance_date, clock_in, clock_out, status, clock_in_latitude, clock_in_longitude, clock_out_latitude, clock_out_longitude, location_method, ip_address)
            VALUES
                (:id, :user_id, :date, :clock_in, :clock_out, :status, :lat_in, :lng_in, :lat_out, :lng_out, :method, :ip)
        ");

        for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
            $date = date('Y-m-d', strtotime("-{$daysAgo} days"));
            $dayOfWeek = date('N', strtotime($date)); // 1=Mon..7=Sun
            if ($dayOfWeek >= 6) continue; // skip weekends

            foreach ($employeeIds as $empId => $info) {
                $pattern = $info['pattern'];
                $rand = mt_rand(1, 100);

                // Determine attendance outcome based on pattern
                if ($pattern === 'absent' && $rand <= 20) {
                    // Sick/leave day
                    $stmtAtt->execute([
                        'id' => genUuid(), 'user_id' => $empId, 'date' => $date,
                        'clock_in' => null, 'clock_out' => null, 'status' => 'sakit/izin',
                        'lat_in' => null, 'lng_in' => null, 'lat_out' => null, 'lng_out' => null,
                        'method' => null, 'ip' => null
                    ]);
                } elseif ($pattern === 'late' && $rand <= 40) {
                    // Late arrival
                    $lateMin = mt_rand(5, 90);
                    $baseH = 8; $baseM = 0;
                    $totalMin = $baseH * 60 + $baseM + $lateMin;
                    $clockIn = sprintf('%02d:%02d:00', intdiv($totalMin, 60), $totalMin % 60);
                    $clockOut = sprintf('%02d:%02d:00', mt_rand(16, 18), mt_rand(0, 59));
                    $stmtAtt->execute([
                        'id' => genUuid(), 'user_id' => $empId, 'date' => $date,
                        'clock_in' => $clockIn, 'clock_out' => $clockOut, 'status' => 'terlambat',
                        'lat_in' => $officeLat + (mt_rand(-5, 5) / 10000), 'lng_in' => $officeLng + (mt_rand(-5, 5) / 10000),
                        'lat_out' => $officeLat + (mt_rand(-5, 5) / 10000), 'lng_out' => $officeLng + (mt_rand(-5, 5) / 10000),
                        'method' => 'GPS', 'ip' => $officeIp
                    ]);
                } elseif ($rand <= 5) {
                    // Random alpa
                    $stmtAtt->execute([
                        'id' => genUuid(), 'user_id' => $empId, 'date' => $date,
                        'clock_in' => null, 'clock_out' => null, 'status' => 'alpa',
                        'lat_in' => null, 'lng_in' => null, 'lat_out' => null, 'lng_out' => null,
                        'method' => null, 'ip' => null
                    ]);
                } else {
                    // Tepat waktu
                    $earlyMin = mt_rand(0, 25);
                    $clockInH = 8; $clockInM = 0;
                    $totalMin = $clockInH * 60 + $clockInM - $earlyMin;
                    $clockIn = sprintf('%02d:%02d:00', intdiv($totalMin, 60), $totalMin % 60);
                    $clockOut = sprintf('%02d:%02d:00', mt_rand(17, 19), mt_rand(0, 59));
                    $method = (mt_rand(0,1) === 0) ? 'WIFI' : 'GPS';
                    $stmtAtt->execute([
                        'id' => genUuid(), 'user_id' => $empId, 'date' => $date,
                        'clock_in' => $clockIn, 'clock_out' => $clockOut, 'status' => 'tepat waktu',
                        'lat_in' => $officeLat + (mt_rand(-3, 3) / 10000), 'lng_in' => $officeLng + (mt_rand(-3, 3) / 10000),
                        'lat_out' => $officeLat + (mt_rand(-3, 3) / 10000), 'lng_out' => $officeLng + (mt_rand(-3, 3) / 10000),
                        'method' => $method, 'ip' => $officeIp
                    ]);
                }
            }
        }
        echo "Seed attendance records complete (30 days).\n";
    } else {
        echo "Skipping attendance records seeding because data already exists.\n";
    }

    // 13. Add work_mode column to employee_attendance (if not exists)
    try {
        $db->exec("ALTER TABLE employee_attendance ADD COLUMN IF NOT EXISTS work_mode VARCHAR(10) DEFAULT 'WFO' AFTER location_method");
        echo "Column work_mode added/verified in employee_attendance.\n";
    } catch (Exception $ex) {
        echo "work_mode column may already exist or not supported: " . $ex->getMessage() . "\n";
    }

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

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

