<?php

namespace App\Models;

use App\Config\Database;
use PDO;

/**
 * Class Department
 * 
 * Data Layer Model representing the departments entity.
 */
class Department {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM departments ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLevel1(): array {
        $stmt = $this->db->query("SELECT name FROM departments WHERE parent_id IS NULL OR level = 1 ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
