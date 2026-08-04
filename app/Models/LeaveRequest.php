<?php

namespace App\Models;

use App\Config\Database;
use PDO;

/**
 * Class LeaveRequest
 * 
 * Data Layer Model representing the employee_leave_requests entity.
 */
class LeaveRequest {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUserId(string $userId): array {
        if (empty(trim($userId))) {
            return [];
        }
        $stmt = $this->db->prepare("SELECT * FROM employee_leave_requests WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingCount(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM employee_leave_requests WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    }
}
