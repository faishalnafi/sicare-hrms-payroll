<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generateUuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function create($firstName, $lastName, $email, $password, $profilePicture = null) {
        $id = $this->generateUuid();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare("INSERT INTO users (id, first_name, last_name, email, profile_picture, password_hash, role) VALUES (:id, :first_name, :last_name, :email, :profile_picture, :password_hash, 'candidate')");
        
        $success = $stmt->execute([
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'profile_picture' => $profilePicture,
            'password_hash' => $hash
        ]);

        if ($success) {
            return $id;
        }
        return false;
    }

    public function updateProfilePicture($id, $url) {
        $stmt = $this->db->prepare("UPDATE users SET profile_picture = :url WHERE id = :id");
        return $stmt->execute(['url' => $url, 'id' => $id]);
    }
}
