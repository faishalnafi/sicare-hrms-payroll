<?php

namespace App\Models;

use App\Config\Database;
use PDO;

/**
 * Class Attendance
 * 
 * Data Layer Model representing the employee_attendance entity.
 */
class Attendance {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUserIdAndDate(string $userId, string $date): ?array {
        if (empty(trim($userId)) || empty(trim($date))) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM employee_attendance WHERE user_id = :user_id AND attendance_date = :date LIMIT 1");
        $stmt->execute(['user_id' => $userId, 'date' => $date]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getLogsForDate(string $date): array {
        if (empty(trim($date))) {
            return [];
        }
        $stmt = $this->db->prepare("
            SELECT a.*, u.first_name, u.last_name, u.email, u.profile_picture, u.job_title, d.name AS department_name
            FROM employee_attendance a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE a.attendance_date = :date
            ORDER BY a.clock_in ASC
        ");
        $stmt->execute(['date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
