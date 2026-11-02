<?php

namespace App\Models;

use App\Config\Database;
use PDO;

/**
 * Class Reimbursement
 * 
 * Data Layer Model representing the employee_reimbursement_claims entity.
 */
class Reimbursement {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUserId(string $userId): array {
        if (empty(trim($userId))) {
            return [];
        }
        $stmt = $this->db->prepare("SELECT * FROM employee_reimbursement_claims WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
