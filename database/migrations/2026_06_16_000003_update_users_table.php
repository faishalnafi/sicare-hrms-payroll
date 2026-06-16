<?php

use App\Config\Migration;

class UpdateUsersTable extends Migration {
    public function up() {
        // 1. Ensure users table exists (it should, but safety first)
        $this->execute("
            CREATE TABLE IF NOT EXISTS users (
                id CHAR(36) PRIMARY KEY,
                employee_id VARCHAR(20) DEFAULT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) DEFAULT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'candidate',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Add role_id column if not exists
        if (!$this->columnExists('users', 'role_id')) {
            $this->execute("
                ALTER TABLE users 
                ADD COLUMN role_id CHAR(36) NULL DEFAULT NULL AFTER role
            ");
        }

        // 3. Add foreign key index & constraint if not exists
        try {
            $this->execute("
                ALTER TABLE users
                ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE SET NULL
            ");
        } catch (Exception $e) {
            // Constraint might already exist, which is fine
        }

        // 4. Update existing records: map string 'role' to 'role_id' UUID
        $roleMappings = [
            'superadmin' => '8f5d7b6c-9c3f-4e08-8e67-d86b976f92c1',
            'executive' => '3d6e5c8a-7b2f-4c19-9d58-e4b9c1f2d3a4',
            'admin' => '1b9c8d7e-6f5a-4b3c-8d2e-1a0f9e8d7c6b',
            'hiring_manager' => '7e5d8c6b-9a4f-4e3d-8c2b-1a0f9e8d7c6b',
            'hr_ops' => '9c8d7e6f-5a4b-3c2d-1e0f-9a8b7c6d5e4f',
            'recruiter' => '5a4b3c2d-1e0f-9a8b-7c6d-5e4f3a2b1c0d',
            'employee' => '2d3e4f5a-6b7c-8d9e-0f1a-2b3c4d5e6f7a',
            'candidate' => '4b5c6d7e-8f9a-0b1c-2d3e-4f5a6b7c8d9e'
        ];

        foreach ($roleMappings as $roleName => $roleUuid) {
            $this->execute("
                UPDATE users 
                SET role_id = :roleUuid 
                WHERE role = :roleName AND role_id IS NULL
            ", [
                'roleUuid' => $roleUuid,
                'roleName' => $roleName
            ]);
        }
    }

    public function down() {
        try {
            $this->execute("ALTER TABLE users DROP FOREIGN KEY fk_users_role");
        } catch (Exception $e) {}
        
        if ($this->columnExists('users', 'role_id')) {
            $this->execute("ALTER TABLE users DROP COLUMN role_id");
        }
    }
}
