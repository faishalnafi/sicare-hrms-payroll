<?php

use App\Config\Migration;

class RemoveUnusedColumnsAndAddIsDeleted extends Migration {
    public function up() {
        // 1. Tambahkan kolom is_deleted jika belum ada
        if (!$this->columnExists('users', 'is_deleted')) {
            $this->execute("ALTER TABLE users ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER is_suspended");
        }

        // 2. Tambahkan kolom remember_token jika belum ada
        if (!$this->columnExists('users', 'remember_token')) {
            $this->execute("ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) NULL DEFAULT NULL AFTER is_deleted");
        }

        // 3. Migrasikan gambar profil
        $stmt = $this->db->query("SELECT id, email, profile_picture, google_profile_picture, gravatar_profile_picture FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Include helpers.php secara aman jika fungsi resolveProfilePicture belum ter-load
        if (!function_exists('resolveProfilePicture')) {
            require_once __DIR__ . '/../../app/helpers.php';
        }

        foreach ($users as $user) {
            $profilePic = $user['profile_picture'];
            
            // Jika kosong, lakukan cascade check
            if (empty($profilePic)) {
                if (!empty($user['google_profile_picture'])) {
                    $profilePic = $user['google_profile_picture'];
                } elseif (!empty($user['gravatar_profile_picture'])) {
                    $profilePic = $user['gravatar_profile_picture'];
                } else {
                    $profilePic = resolveProfilePicture($user['email']);
                }

                $stmtUpdate = $this->db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmtUpdate->execute([$profilePic, $user['id']]);
            }
        }

        // 4. Hapus kolom-kolom yang tidak digunakan
        if ($this->columnExists('users', 'google_profile_picture')) {
            $this->execute("ALTER TABLE users DROP COLUMN google_profile_picture");
        }
        if ($this->columnExists('users', 'gravatar_profile_picture')) {
            $this->execute("ALTER TABLE users DROP COLUMN gravatar_profile_picture");
        }
        if ($this->columnExists('employee_attendance', 'notes')) {
            $this->execute("ALTER TABLE employee_attendance DROP COLUMN notes");
        }
    }

    public function down() {
        // Tambahkan kembali kolom yang dihapus jika di-rollback
        if (!$this->columnExists('users', 'google_profile_picture')) {
            $this->execute("ALTER TABLE users ADD COLUMN google_profile_picture VARCHAR(500) NULL AFTER profile_picture");
        }
        if (!$this->columnExists('users', 'gravatar_profile_picture')) {
            $this->execute("ALTER TABLE users ADD COLUMN gravatar_profile_picture VARCHAR(500) NULL AFTER google_profile_picture");
        }
        if (!$this->columnExists('employee_attendance', 'notes')) {
            $this->execute("ALTER TABLE employee_attendance ADD COLUMN notes TEXT NULL AFTER ip_address");
        }
    }
}
