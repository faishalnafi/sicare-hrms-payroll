<?php
require __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "Connected to database successfully.\n";

    // Create system_menus table
    $db->exec("
        CREATE TABLE IF NOT EXISTS system_menus (
            id CHAR(36) PRIMARY KEY,
            menu_name VARCHAR(100) NOT NULL,
            menu_key VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table system_menus created or verified.\n";

    // Create department_menu_privileges table
    $db->exec("
        CREATE TABLE IF NOT EXISTS department_menu_privileges (
            id CHAR(36) PRIMARY KEY,
            department_id CHAR(36) NOT NULL,
            menu_id CHAR(36) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
            FOREIGN KEY (menu_id) REFERENCES system_menus(id) ON DELETE CASCADE,
            UNIQUE KEY uq_dept_menu (department_id, menu_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table department_menu_privileges created or verified.\n";

    // Alter departments to add is_executive_entity
    $db->exec("ALTER TABLE departments ADD COLUMN IF NOT EXISTS is_executive_entity BOOLEAN DEFAULT FALSE");
    echo "Column is_executive_entity added to departments.\n";

    // Define seed data for system_menus (The 16 core MNC reporting menus)
    $systemMenus = [
        ['menu_name' => 'Rekapitulasi Keuangan', 'menu_key' => 'finance_recap', 'description' => 'Akses laporan keuangan dan payroll perusahaan.'],
        ['menu_name' => 'Monitoring KPI Produk', 'menu_key' => 'product_kpi', 'description' => 'Akses matriks kinerja produk dan operasional.'],
        ['menu_name' => 'Log Keamanan Sistem', 'menu_key' => 'security_logs', 'description' => 'Akses rekam jejak audit (audit_logs) dan aktivitas pengguna.'],
        ['menu_name' => 'Analitik Sumber Daya Manusia', 'menu_key' => 'hr_analytics', 'description' => 'Akses statistik turnover, rekrutmen, dan demografi karyawan.'],
        ['menu_name' => 'Manajemen Risiko Korporat', 'menu_key' => 'risk_management', 'description' => 'Akses laporan kepatuhan dan manajemen risiko perusahaan.'],
        ['menu_name' => 'Dasbor Strategi Bisnis', 'menu_key' => 'business_strategy', 'description' => 'Akses metrik bisnis tingkat tinggi dan perencanaan strategis.']
    ];

    $stmtMenu = $db->prepare("INSERT IGNORE INTO system_menus (id, menu_name, menu_key, description) VALUES (?, ?, ?, ?)");
    foreach ($systemMenus as $menu) {
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $stmtMenu->execute([$uuid, $menu['menu_name'], $menu['menu_key'], $menu['description']]);
    }
    echo "Seed system_menus completed.\n";

    // Identify/Create CTO Department and set is_executive_entity to true
    $db->exec("UPDATE departments SET is_executive_entity = TRUE WHERE name = 'Information Technology' OR name LIKE '%CTO%'");
    
    echo "\n[v2.0.0-beta.1] Database migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
