<?php

use App\Config\Migration;

class SeedSysMenusAndPermissions extends Migration {
    public function up() {
        // Real Role UUIDs from CreateRolesTable migration
        $roleIds = [
            'superadmin'     => '8f5d7b6c-9c3f-4e08-8e67-d86b976f92c1',
            'executive'      => '3d6e5c8a-7b2f-4c19-9d58-e4b9c1f2d3a4',
            'admin'          => '1b9c8d7e-6f5a-4b3c-8d2e-1a0f9e8d7c6b',
            'hiring_manager' => '7e5d8c6b-9a4f-4e3d-8c2b-1a0f9e8d7c6b',
            'hr_ops'         => '9c8d7e6f-5a4b-3c2d-1e0f-9a8b7c6d5e4f',
            'recruiter'      => '5a4b3c2d-1e0f-9a8b-7c6d-5e4f3a2b1c0d',
            'employee'       => '2d3e4f5a-6b7c-8d9e-0f1a-2b3c4d5e6f7a',
            'candidate'      => '4b5c6d7e-8f9a-0b1c-2d3e-4f5a6b7c8d9e'
        ];

        // 1. Seed Common Menus (accessible by all roles)
        $commonMenus = [
            ['id' => 'e0000000-0000-0000-0000-000000000001', 'title' => 'Changelog Rilis', 'url_route' => 'changelogs', 'icon' => 'history', 'sort_order' => 10],
            ['id' => 'e0000000-0000-0000-0000-000000000002', 'title' => 'Pedoman Penomoran', 'url_route' => 'changelogs/guide', 'icon' => 'menu_book', 'sort_order' => 11]
        ];

        foreach ($commonMenus as $m) {
            $this->insertMenu($m);
            foreach ($roleIds as $roleName => $roleId) {
                $this->insertPermission($m['id'], $roleId);
            }
        }

        // 2. Seed Role-specific Menus and Permissions
        $roleMenus = [
            'candidate' => [
                ['id' => 'e0000000-0000-0000-0000-000000000101', 'title' => 'Beranda', 'url_route' => 'candidate/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'e0000000-0000-0000-0000-000000000102', 'title' => 'Dashboard Lowongan', 'url_route' => 'candidate/jobs', 'icon' => 'list_alt', 'sort_order' => 2],
                ['id' => 'e0000000-0000-0000-0000-000000000103', 'title' => 'Jadwal Wawancancara', 'url_route' => 'candidate/interviews', 'icon' => 'event_available', 'sort_order' => 3],
                ['id' => 'e0000000-0000-0000-0000-000000000104', 'title' => 'Penawaran & Kontrak', 'url_route' => 'candidate/offerings', 'icon' => 'history_edu', 'sort_order' => 4],
                ['id' => 'e0000000-0000-0000-0000-000000000105', 'title' => 'Wizard Onboarding', 'url_route' => 'candidate/onboarding', 'icon' => 'rocket_launch', 'sort_order' => 5]
            ],
            'employee' => [
                ['id' => 'e0000000-0000-0000-0000-000000000201', 'title' => 'Beranda', 'url_route' => 'employee/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'e0000000-0000-0000-0000-000000000202', 'title' => 'Profil Pribadi', 'url_route' => 'employee/profile', 'icon' => 'account_circle', 'sort_order' => 2],
                ['id' => 'e0000000-0000-0000-0000-000000000203', 'title' => 'Menu Presensi', 'url_route' => 'employee/attendance', 'icon' => 'alarm_on', 'sort_order' => 3],
                ['id' => 'e0000000-0000-0000-0000-000000000204', 'title' => 'Cuti & Izin', 'url_route' => 'employee/leaves', 'icon' => 'event_note', 'sort_order' => 4],
                ['id' => 'e0000000-0000-0000-0000-000000000205', 'title' => 'Finansial Mandiri', 'url_route' => 'employee/finance', 'icon' => 'payments', 'sort_order' => 5],
                ['id' => 'e0000000-0000-0000-0000-000000000206', 'title' => 'Reimbursement', 'url_route' => 'employee/reimbursements', 'icon' => 'receipt_long', 'sort_order' => 6],
                ['id' => 'e0000000-0000-0000-0000-000000000207', 'title' => 'Refleksi Diri', 'url_route' => 'employee/reflection', 'icon' => 'psychology', 'sort_order' => 7]
            ],
            'recruiter' => [
                ['id' => 'e0000000-0000-0000-0000-000000000301', 'title' => 'Beranda', 'url_route' => 'recruiter/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'e0000000-0000-0000-0000-000000000302', 'title' => 'Manajemen Lowongan', 'url_route' => 'recruiter/jobs', 'icon' => 'work', 'sort_order' => 2],
                ['id' => 'e0000000-0000-0000-0000-000000000303', 'title' => 'Pipeline ATS', 'url_route' => 'recruiter/ats', 'icon' => 'view_kanban', 'sort_order' => 3],
                ['id' => 'e0000000-0000-0000-0000-000000000304', 'title' => 'Jadwal Wawancara', 'url_route' => 'recruiter/interviews', 'icon' => 'calendar_month', 'sort_order' => 4],
                ['id' => 'e0000000-0000-0000-0000-000000000305', 'title' => 'Kontrak & Offering', 'url_route' => 'recruiter/offerings', 'icon' => 'history_edu', 'sort_order' => 5]
            ],
            'hiring_manager' => [
                ['id' => 'e0000000-0000-0000-0000-000000000401', 'title' => 'Beranda', 'url_route' => 'manager/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'e0000000-0000-0000-0000-000000000402', 'title' => 'Anggota Tim', 'url_route' => 'manager/team', 'icon' => 'group', 'sort_order' => 2],
                ['id' => 'e0000000-0000-0000-0000-000000000403', 'title' => 'Permintaan Tenaga Kerja', 'url_route' => 'manager/requisitions', 'icon' => 'person_add', 'sort_order' => 3],
                ['id' => 'e0000000-0000-0000-0000-000000000404', 'title' => 'Review Kandidat', 'url_route' => 'manager/candidates', 'icon' => 'preview', 'sort_order' => 4],
                ['id' => 'e0000000-0000-0000-0000-000000000405', 'title' => 'Lembar Wawancara', 'url_route' => 'manager/interviews', 'icon' => 'fact_check', 'sort_order' => 5],
                ['id' => 'e0000000-0000-0000-0000-000000000406', 'title' => 'Persetujuan Tim', 'url_route' => 'manager/approvals', 'icon' => 'verified', 'sort_order' => 6],
                ['id' => 'e0000000-0000-0000-0000-000000000407', 'title' => 'Refleksi Tim', 'url_route' => 'manager/reflection', 'icon' => 'psychology', 'sort_order' => 7]
            ],
            'hr_ops' => [
                ['id' => 'e0000000-0000-0000-0000-000000000501', 'title' => 'Beranda', 'url_route' => 'hrops/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'e0000000-0000-0000-0000-000000000502', 'title' => 'Verifikasi Onboarding', 'url_route' => 'hrops/onboarding', 'icon' => 'rule', 'sort_order' => 2],
                ['id' => 'e0000000-0000-0000-0000-000000000503', 'title' => 'Master Data Karyawan', 'url_route' => 'hrops/employees', 'icon' => 'group', 'sort_order' => 3],
                ['id' => 'e0000000-0000-0000-0000-000000000504', 'title' => 'Verifikasi Data', 'url_route' => 'hrops/verifications', 'icon' => 'verified_user', 'sort_order' => 4],
                ['id' => 'e0000000-0000-0000-0000-000000000505', 'title' => 'Pemrosesan Penggajian', 'url_route' => 'hrops/payroll', 'icon' => 'account_balance_wallet', 'sort_order' => 5],
                ['id' => 'e0000000-0000-0000-0000-000000000506', 'title' => 'Refleksi Karyawan', 'url_route' => 'hrops/reflection', 'icon' => 'psychology', 'sort_order' => 6]
            ],
            'admin' => [
                ['id' => 'e0000000-0000-0000-0000-000000000601', 'title' => 'Beranda', 'url_route' => 'admin/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'e0000000-0000-0000-0000-000000000602', 'title' => 'Struktur Departemen', 'url_route' => 'admin/departments', 'icon' => 'account_tree', 'sort_order' => 2],
                ['id' => 'e0000000-0000-0000-0000-000000000603', 'title' => 'Manajemen Pengguna', 'url_route' => 'admin/users', 'icon' => 'manage_accounts', 'sort_order' => 3],
                ['id' => 'e0000000-0000-0000-0000-000000000604', 'title' => 'Pengaturan Sistem', 'url_route' => 'admin/settings', 'icon' => 'settings', 'sort_order' => 4]
            ],
            'executive' => [
                ['id' => 'e0000000-0000-0000-0000-000000000701', 'title' => 'Beranda', 'url_route' => 'executive/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'e0000000-0000-0000-0000-000000000702', 'title' => 'Dashboard Analitik', 'url_route' => 'executive/analytics', 'icon' => 'analytics', 'sort_order' => 2],
                ['id' => 'e0000000-0000-0000-0000-000000000703', 'title' => 'Persetujuan Anggaran', 'url_route' => 'executive/budgets', 'icon' => 'request_quote', 'sort_order' => 3],
                ['id' => 'e0000000-0000-0000-0000-000000000704', 'title' => 'Persetujuan Mutasi', 'url_route' => 'executive/approvals', 'icon' => 'published_with_changes', 'sort_order' => 4],
                ['id' => 'e0000000-0000-0000-0000-000000000705', 'title' => 'Analitik Refleksi', 'url_route' => 'executive/reflection', 'icon' => 'psychology', 'sort_order' => 5],
                ['id' => 'e0000000-0000-0000-0000-000000000706', 'title' => 'Audit Log & Security', 'url_route' => 'superadmin/audit', 'icon' => 'security', 'sort_order' => 6]
            ],
            'superadmin' => [
                ['id' => 'e0000000-0000-0000-0000-000000000801', 'title' => 'Beranda', 'url_route' => 'superadmin/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'e0000000-0000-0000-0000-000000000602', 'title' => 'Struktur Departemen', 'url_route' => 'admin/departments', 'icon' => 'account_tree', 'sort_order' => 2],
                ['id' => 'e0000000-0000-0000-0000-000000000802', 'title' => 'Manajemen Pengguna', 'url_route' => 'superadmin/users', 'icon' => 'manage_accounts', 'sort_order' => 3],
                ['id' => 'e0000000-0000-0000-0000-000000000803', 'title' => 'Konfigurasi Global', 'url_route' => 'superadmin/settings', 'icon' => 'settings', 'sort_order' => 4],
                ['id' => 'e0000000-0000-0000-0000-000000000804', 'title' => 'Pengaturan Sistem', 'url_route' => 'superadmin/system-settings', 'icon' => 'settings_applications', 'sort_order' => 5],
                ['id' => 'e0000000-0000-0000-0000-000000000805', 'title' => 'Audit Log & Security', 'url_route' => 'superadmin/audit', 'icon' => 'security', 'sort_order' => 6],
                ['id' => 'e0000000-0000-0000-0000-000000000806', 'title' => 'Pembaruan Sistem', 'url_route' => 'superadmin/update', 'icon' => 'system_update', 'sort_order' => 7],
                ['id' => 'e0000000-0000-0000-0000-000000000807', 'title' => 'Refleksi Karyawan', 'url_route' => 'superadmin/reflection', 'icon' => 'psychology', 'sort_order' => 8],
                ['id' => 'e0000000-0000-0000-0000-000000000808', 'title' => 'Menu Builder', 'url_route' => 'superadmin/menu-builder', 'icon' => 'widgets', 'sort_order' => 9],
                ['id' => 'e0000000-0000-0000-0000-000000000809', 'title' => 'Matriks Akses (ACL)', 'url_route' => 'superadmin/menu-permissions', 'icon' => 'rule_folder', 'sort_order' => 10],
                ['id' => 'e0000000-0000-0000-0000-000000000810', 'title' => 'Global Approval', 'url_route' => 'superadmin/global-approval', 'icon' => 'fact_check', 'sort_order' => 11]
            ]
        ];

        foreach ($roleMenus as $roleName => $menus) {
            $roleId = $roleIds[$roleName];
            foreach ($menus as $m) {
                $this->insertMenu($m);
                $this->insertPermission($m['id'], $roleId);
            }
        }
    }

    private function insertMenu($m) {
        $this->execute("
            INSERT INTO sys_menus (id, title, url_route, icon, sort_order)
            VALUES (:id, :title, :url_route, :icon, :sort_order)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                url_route = VALUES(url_route),
                icon = VALUES(icon),
                sort_order = VALUES(sort_order)
        ", $m);
    }

    private function insertPermission($menuId, $roleId) {
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $this->execute("
            INSERT INTO menu_permissions (id, menu_id, role_id, department_id)
            VALUES (:id, :menu_id, :role_id, NULL)
            ON DUPLICATE KEY UPDATE id = id
        ", ['id' => $uuid, 'menu_id' => $menuId, 'role_id' => $roleId]);
    }

    public function down() {
        $this->execute("DELETE FROM menu_permissions");
        $this->execute("DELETE FROM sys_menus");
    }
}
