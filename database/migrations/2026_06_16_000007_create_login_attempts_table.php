<?php

use App\Config\Migration;

class CreateLoginAttemptsTable extends Migration {
    public function up() {
        $this->execute("
            CREATE TABLE IF NOT EXISTS login_attempts (
                email VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempts INT DEFAULT 0,
                last_attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                locked_until TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (email, ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down() {
        $this->execute("DROP TABLE IF EXISTS login_attempts");
    }
}
