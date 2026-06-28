<?php

namespace App\Config;

use PDO;
use Exception;

class Migrator {
    private $db;
    private $migrationsDir;

    public function __construct(PDO $db, $migrationsDir) {
        $this->db = $db;
        $this->migrationsDir = rtrim($migrationsDir, '/');
    }

    /**
     * Run all outstanding migrations.
     */
    public function run() {
        $this->ensureSchemaMigrationsTable();

        $executed = $this->getExecutedMigrations();
        $files = $this->getMigrationFiles();

        $batch = $this->getNextBatchNumber();
        $count = 0;

        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if (!in_array($filename, $executed)) {
                echo "Running migration: $filename...\n";
                
                try {
                    $this->runMigrationFile($file, 'up');
                    $this->logMigration($filename, $batch);
                    echo "Migration completed: $filename.\n";
                    $count++;
                } catch (Exception $e) {
                    echo "Migration FAILED: $filename. Error: " . $e->getMessage() . "\n";
                    throw $e;
                }
            }
        }

        if ($count === 0) {
            echo "Nothing to migrate.\n";
        } else {
            echo "Successfully executed $count migrations.\n";
        }
    }

    /**
     * Ensure the tracking table exists.
     */
    private function ensureSchemaMigrationsTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    /**
     * Get all migrations that have already been run.
     */
    private function getExecutedMigrations() {
        $stmt = $this->db->query("SELECT version FROM schema_migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get all migration files sorted chronologically.
     */
    private function getMigrationFiles() {
        $pattern = $this->migrationsDir . '/*.php';
        $files = glob($pattern);
        if ($files === false) {
            return [];
        }
        sort($files);
        return $files;
    }

    /**
     * Calculate next batch number.
     */
    private function getNextBatchNumber() {
        $stmt = $this->db->query("SELECT MAX(batch) FROM schema_migrations");
        $max = $stmt->fetchColumn();
        return $max ? (int)$max + 1 : 1;
    }

    /**
     * Record completed migration in history.
     */
    private function logMigration($version, $batch) {
        $stmt = $this->db->prepare("INSERT INTO schema_migrations (version, batch) VALUES (?, ?)");
        $stmt->execute([$version, $batch]);
    }

    /**
     * Instantiate and run a single migration file.
     */
    private function runMigrationFile($file, $method = 'up') {
        require_once $file;
        $filename = basename($file, '.php');

        // Extract class name from filename: e.g. "2026_06_16_000001_create_roles_table"
        // to CamelCase: "CreateRolesTable"
        $parts = explode('_', $filename);
        $classParts = [];
        foreach ($parts as $p) {
            if (!is_numeric($p)) {
                $classParts[] = ucfirst($p);
            }
        }
        $className = implode('', $classParts);

        if (!class_exists($className)) {
            // Fallback: try mapping class directly by name in case namespace matches or file is literal
            $className = 'App\\Config\\Migrations\\' . $className;
            if (!class_exists($className)) {
                throw new Exception("Migration class matching filename '$filename' not found.");
            }
        }

        $migration = new $className($this->db);
        if (!method_exists($migration, $method)) {
            throw new Exception("Method '$method' not found in migration class '$className'.");
        }

        $migration->$method();
    }
}
