<?php

use App\Config\Migration;

class CreateRolesTable extends Migration {
    public function up() {
        // 1. Create table 'roles' if not exists
        $this->execute("
            CREATE TABLE IF NOT EXISTS roles (
                id CHAR(36) PRIMARY KEY,
                name VARCHAR(50) UNIQUE NOT NULL,
                display_name VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Seed 8 core roles
        $roles = [
            [
                'id' => '8f5d7b6c-9c3f-4e08-8e67-d86b976f92c1',
                'name' => 'superadmin',
                'display_name' => 'Super Administrator',
                'description' => 'System administrator with unrestricted global access.'
            ],
            [
                'id' => '3d6e5c8a-7b2f-4c19-9d58-e4b9c1f2d3a4',
                'name' => 'executive',
                'display_name' => 'Executive Management (C-Level)',
                'description' => 'Dewan direksi (CEO, CFO, CTO, dll.) dan pengawas makro.'
            ],
            [
                'id' => '1b9c8d7e-6f5a-4b3c-8d2e-1a0f9e8d7c6b',
                'name' => 'admin',
                'display_name' => 'Operational Administrator',
                'description' => 'Pengelola departemen, manajemen pengguna, dan konfigurasi sistem.'
            ],
            [
                'id' => '7e5d8c6b-9a4f-4e3d-8c2b-1a0f9e8d7c6b',
                'name' => 'hiring_manager',
                'display_name' => 'Hiring Manager (Kepala Divisi)',
                'description' => 'Pimpinan departemen / divisi.'
            ],
            [
                'id' => '9c8d7e6f-5a4b-3c2d-1e0f-9a8b7c6d5e4f',
                'name' => 'hr_ops',
                'display_name' => 'HR Operations',
                'description' => 'Pengelola administrasi karyawan, cuti, absensi, dan penggajian.'
            ],
            [
                'id' => '5a4b3c2d-1e0f-9a8b-7c6d-5e4f3a2b1c0d',
                'name' => 'recruiter',
                'display_name' => 'Recruiter (Talent Acquisition)',
                'description' => 'Pengelola lowongan kerja, seleksi pelamar, dan offering letter.'
            ],
            [
                'id' => '2d3e4f5a-6b7c-8d9e-0f1a-2b3c4d5e6f7a',
                'name' => 'employee',
                'display_name' => 'Functional Employee',
                'description' => 'Karyawan perusahaan dengan akses modul mandiri (ESS).'
            ],
            [
                'id' => '4b5c6d7e-8f9a-0b1c-2d3e-4f5a6b7c8d9e',
                'name' => 'candidate',
                'display_name' => 'Job Candidate',
                'description' => 'Pelamar kerja eksternal di portal rekrutmen.'
            ]
        ];

        foreach ($roles as $role) {
            $this->execute("
                INSERT INTO roles (id, name, display_name, description)
                VALUES (:id, :name, :display_name, :description)
                ON DUPLICATE KEY UPDATE
                    display_name = VALUES(display_name),
                    description = VALUES(description)
            ", $role);
        }
    }

    public function down() {
        $this->execute("DROP TABLE IF EXISTS roles");
    }
}
