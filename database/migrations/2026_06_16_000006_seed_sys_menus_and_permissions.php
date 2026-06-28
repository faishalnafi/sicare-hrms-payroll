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
            ['id' => '1b0b58b2-eca1-40e8-8514-92d54d245a03', 'title' => 'Changelog Rilis', 'url_route' => 'changelogs', 'icon' => 'history', 'sort_order' => 10],
            ['id' => '337e44c0-e3be-41b1-9b37-0fee569aa4d5', 'title' => 'Pedoman Penomoran', 'url_route' => 'changelogs/guide', 'icon' => 'menu_book', 'sort_order' => 11]
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
                ['id' => 'fbe452c1-316c-4ed3-b5fd-b7068f7a2bdd', 'title' => 'Beranda', 'url_route' => 'candidate/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'b58a9c1d-dac1-49a8-8cb0-df66aa4d4827', 'title' => 'Dashboard Lowongan', 'url_route' => 'candidate/jobs', 'icon' => 'list_alt', 'sort_order' => 2],
                ['id' => '3bd05f55-fb55-4eaf-b681-42a4712b688f', 'title' => 'Jadwal Wawancancara', 'url_route' => 'candidate/interviews', 'icon' => 'event_available', 'sort_order' => 3],
                ['id' => 'eb3fa637-1aaf-4f8f-884d-54dc2daa619b', 'title' => 'Penawaran & Kontrak', 'url_route' => 'candidate/offerings', 'icon' => 'history_edu', 'sort_order' => 4],
                ['id' => 'b6680462-c430-446b-a3ca-5c86288c1d88', 'title' => 'Wizard Onboarding', 'url_route' => 'candidate/onboarding', 'icon' => 'rocket_launch', 'sort_order' => 5]
            ],
            'employee' => [
                ['id' => 'bb95b7c4-9436-4f28-9961-d8b29a906fb4', 'title' => 'Beranda', 'url_route' => 'employee/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'e7333720-727f-48ba-a139-ec4ebe7ebbd8', 'title' => 'Profil Pribadi', 'url_route' => 'employee/profile', 'icon' => 'account_circle', 'sort_order' => 2],
                ['id' => '09ac5d13-94ce-4ffb-9536-62ae9eb53c89', 'title' => 'Menu Presensi', 'url_route' => 'employee/attendance', 'icon' => 'alarm_on', 'sort_order' => 3],
                ['id' => '1f1768a9-30ef-4ba4-a752-1b5a50fd24cd', 'title' => 'Cuti & Izin', 'url_route' => 'employee/leaves', 'icon' => 'event_note', 'sort_order' => 4],
                ['id' => '31d6f395-6222-4957-85e4-f76b31210a43', 'title' => 'Finansial Mandiri', 'url_route' => 'employee/finance', 'icon' => 'payments', 'sort_order' => 5],
                ['id' => 'f02c787d-e37b-43f2-b7e7-893f25291879', 'title' => 'Reimbursement', 'url_route' => 'employee/reimbursements', 'icon' => 'receipt_long', 'sort_order' => 6],
                ['id' => '49b93867-63ec-4d33-b5ae-918609071388', 'title' => 'Refleksi Diri', 'url_route' => 'employee/reflection', 'icon' => 'psychology', 'sort_order' => 7]
            ],
            'recruiter' => [
                ['id' => '43bf7319-7c03-4120-b2b4-67323eb04730', 'title' => 'Beranda', 'url_route' => 'recruiter/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => '6aa0d2e1-7332-416e-b0ce-aee8d8e2cf78', 'title' => 'Manajemen Lowongan', 'url_route' => 'recruiter/jobs', 'icon' => 'work', 'sort_order' => 2],
                ['id' => '5fb02414-e5ba-48d6-9cd8-abfeaa275a2f', 'title' => 'Pipeline ATS', 'url_route' => 'recruiter/ats', 'icon' => 'view_kanban', 'sort_order' => 3],
                ['id' => '6dd47330-5117-405a-980c-539e3e637f9f', 'title' => 'Jadwal Wawancara', 'url_route' => 'recruiter/interviews', 'icon' => 'calendar_month', 'sort_order' => 4],
                ['id' => '7196a482-9468-4bb2-a756-48c7334104be', 'title' => 'Kontrak & Offering', 'url_route' => 'recruiter/offerings', 'icon' => 'history_edu', 'sort_order' => 5]
            ],
            'hiring_manager' => [
                ['id' => 'c69cf216-40f2-43e4-b402-b5c2aecb42dc', 'title' => 'Beranda', 'url_route' => 'manager/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'a1fd81cb-fd98-454f-8d8b-0598a52d2933', 'title' => 'Anggota Tim', 'url_route' => 'manager/team', 'icon' => 'group', 'sort_order' => 2],
                ['id' => 'e01408df-403c-4ccb-9189-7992a62c88a2', 'title' => 'Permintaan Tenaga Kerja', 'url_route' => 'manager/requisitions', 'icon' => 'person_add', 'sort_order' => 3],
                ['id' => '4c434b63-7474-4473-8314-e0719fe26280', 'title' => 'Review Kandidat', 'url_route' => 'manager/candidates', 'icon' => 'preview', 'sort_order' => 4],
                ['id' => '338714e4-5245-47cf-9c5a-021a1fff494f', 'title' => 'Lembar Wawancara', 'url_route' => 'manager/interviews', 'icon' => 'fact_check', 'sort_order' => 5],
                ['id' => '8d7ca6a2-7b70-4463-8150-dd2eb741beeb', 'title' => 'Persetujuan Tim', 'url_route' => 'manager/approvals', 'icon' => 'verified', 'sort_order' => 6],
                ['id' => '74a5695e-8f54-42d0-8184-7a878ef380e5', 'title' => 'Refleksi Tim', 'url_route' => 'manager/reflection', 'icon' => 'psychology', 'sort_order' => 7]
            ],
            'hr_ops' => [
                ['id' => '7ebead8c-7ae4-4093-9250-d55788314534', 'title' => 'Beranda', 'url_route' => 'hrops/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => '34635209-5409-4d99-ae38-d7d6cdbad360', 'title' => 'Verifikasi Onboarding', 'url_route' => 'hrops/onboarding', 'icon' => 'rule', 'sort_order' => 2],
                ['id' => '00f9f4e1-4e85-4818-ad92-c88141db0a3c', 'title' => 'Master Data Karyawan', 'url_route' => 'hrops/employees', 'icon' => 'group', 'sort_order' => 3],
                ['id' => '2ac3ef53-0dba-4c45-acdb-63d78cb429b0', 'title' => 'Verifikasi Data', 'url_route' => 'hrops/verifications', 'icon' => 'verified_user', 'sort_order' => 4],
                ['id' => '6e3f9599-1e45-4e94-9752-87992a2a010d', 'title' => 'Pemrosesan Penggajian', 'url_route' => 'hrops/payroll', 'icon' => 'account_balance_wallet', 'sort_order' => 5],
                ['id' => 'f43b8e24-6775-4747-90d7-e34c3a89ddd9', 'title' => 'Refleksi Karyawan', 'url_route' => 'hrops/reflection', 'icon' => 'psychology', 'sort_order' => 6]
            ],
            'admin' => [
                ['id' => '3ffa11e0-1679-4ff0-9aef-e595d5c41da5', 'title' => 'Beranda', 'url_route' => 'admin/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'cc7add52-9c56-471c-844e-7b2f93ee354c', 'title' => 'Struktur Departemen', 'url_route' => 'admin/departments', 'icon' => 'account_tree', 'sort_order' => 2],
                ['id' => '0a57d39c-4ac9-4a37-a439-11fb06b471a2', 'title' => 'Manajemen Pengguna', 'url_route' => 'admin/users', 'icon' => 'manage_accounts', 'sort_order' => 3],
                ['id' => 'e38bf828-2ffa-4df2-b1cf-55b83f3479c7', 'title' => 'Pengaturan Sistem', 'url_route' => 'admin/settings', 'icon' => 'settings', 'sort_order' => 4]
            ],
            'executive' => [
                ['id' => '537a270f-f6d5-4882-ad53-28892824d2c5', 'title' => 'Beranda', 'url_route' => 'executive/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => '9717b356-185b-43e7-a539-b440af731a8f', 'title' => 'Dashboard Analitik', 'url_route' => 'executive/analytics', 'icon' => 'analytics', 'sort_order' => 2],
                ['id' => 'aa0d6977-17ea-4452-9f02-3fb2d774a1ba', 'title' => 'Persetujuan Anggaran', 'url_route' => 'executive/budgets', 'icon' => 'request_quote', 'sort_order' => 3],
                ['id' => '6ab38a3c-5aca-459e-9556-26b3a6a166df', 'title' => 'Persetujuan Mutasi', 'url_route' => 'executive/approvals', 'icon' => 'published_with_changes', 'sort_order' => 4],
                ['id' => '7e305bd8-20ea-4717-ad9f-55d3ce681b07', 'title' => 'Analitik Refleksi', 'url_route' => 'executive/reflection', 'icon' => 'psychology', 'sort_order' => 5],
                ['id' => '69025efa-351c-4a4b-b01a-28ffa3bacd99', 'title' => 'Audit Log & Security', 'url_route' => 'superadmin/audit', 'icon' => 'security', 'sort_order' => 6]
            ],
            'superadmin' => [
                ['id' => 'cb9ee0a3-318e-44f5-8b37-b2b27dd3b476', 'title' => 'Beranda', 'url_route' => 'superadmin/dashboard', 'icon' => 'dashboard', 'sort_order' => 1],
                ['id' => 'cc7add52-9c56-471c-844e-7b2f93ee354c', 'title' => 'Struktur Departemen', 'url_route' => 'admin/departments', 'icon' => 'account_tree', 'sort_order' => 2],
                ['id' => '979a51b9-87fa-48de-b928-622f78895cb9', 'title' => 'Manajemen Pengguna', 'url_route' => 'superadmin/users', 'icon' => 'manage_accounts', 'sort_order' => 3],
                ['id' => '905b4683-2060-4221-b133-e303f76f9966', 'title' => 'Konfigurasi Global', 'url_route' => 'superadmin/settings', 'icon' => 'settings', 'sort_order' => 4],
                ['id' => '2bff6113-d3a1-4e2d-b946-918f64837986', 'title' => 'Pengaturan Sistem', 'url_route' => 'superadmin/system-settings', 'icon' => 'settings_applications', 'sort_order' => 5],
                ['id' => '79cfefb3-fb60-42a8-b482-52cff3cafa91', 'title' => 'Audit Log & Security', 'url_route' => 'superadmin/audit', 'icon' => 'security', 'sort_order' => 6],
                ['id' => '45063a9c-4378-48c9-b357-d3681f74fdfc', 'title' => 'Pembaruan Sistem', 'url_route' => 'superadmin/update', 'icon' => 'system_update', 'sort_order' => 7],
                ['id' => '6f4bd13e-0ac9-4742-8633-0d10e4528b09', 'title' => 'Refleksi Karyawan', 'url_route' => 'superadmin/reflection', 'icon' => 'psychology', 'sort_order' => 8],
                ['id' => '3108b15b-ad42-42fd-9d68-0b72dd1513da', 'title' => 'Global Approval', 'url_route' => 'superadmin/global-approval', 'icon' => 'fact_check', 'sort_order' => 9]
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
