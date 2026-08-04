<?php

namespace App\Models;

use App\Config\Database;
use PDO;

/**
 * Class Payroll
 * 
 * Data Layer Model representing the employee_payroll entity.
 */
class Payroll {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findApprovedByUserAndYear(string $userId, int $year): array {
        if (empty(trim($userId)) || $year < 1900 || $year > 2100) {
            return [];
        }
        $stmt = $this->db->prepare("
            SELECT ep.*
            FROM employee_payroll ep
            WHERE ep.user_id = :user_id
              AND ep.status IN ('Approved', 'Paid')
              AND ep.month_year LIKE :year_pattern
            ORDER BY ep.month_year DESC
        ");
        $stmt->execute([
            'user_id' => $userId,
            'year_pattern' => '%-' . $year,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
