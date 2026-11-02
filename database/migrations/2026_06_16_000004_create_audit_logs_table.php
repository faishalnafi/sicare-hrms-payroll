<?php

use App\Config\Migration;

class CreateAuditLogsTable extends Migration {
    public function up() {
        // Ensure audit_logs exists with UUID PK
        $this->execute("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                action TEXT NOT NULL,
                table_name VARCHAR(100) NULL,
                ip_address VARCHAR(45) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down() {
        $this->execute("DROP TABLE IF EXISTS audit_logs");
    }
}
