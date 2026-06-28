<?php

namespace App\Config;

use PDO;

abstract class Migration {
    protected $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    abstract public function up();
    abstract public function down();

    /**
     * Helper to check if a table exists.
     */
    protected function tableExists($table) {
        try {
            $result = $this->db->query("SELECT 1 FROM `$table` LIMIT 1");
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if a column exists in a table.
     */
    protected function columnExists($table, $column) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = :db 
              AND TABLE_NAME = :table 
              AND COLUMN_NAME = :column
        ");
        $stmt->execute([
            'db' => $_ENV['DB_DATABASE'] ?? 'sicare_db',
            'table' => $table,
            'column' => $column
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Helper to check if an index exists in a table.
     */
    protected function indexExists($table, $indexName) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = :db 
              AND TABLE_NAME = :table 
              AND INDEX_NAME = :indexName
        ");
        $stmt->execute([
            'db' => $_ENV['DB_DATABASE'] ?? 'sicare_db',
            'table' => $table,
            'indexName' => $indexName
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Helper to execute SQL statement.
     */
    protected function execute($sql, $params = []) {
        if (empty($params)) {
            return $this->db->exec($sql);
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
