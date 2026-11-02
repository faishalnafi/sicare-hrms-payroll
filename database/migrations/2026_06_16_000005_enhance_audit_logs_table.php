<?php

use App\Config\Migration;

class EnhanceAuditLogsTable extends Migration {
    public function up() {
        // Add description column if not exists
        if (!$this->columnExists('audit_logs', 'description')) {
            $this->execute("ALTER TABLE audit_logs ADD COLUMN description TEXT DEFAULT NULL AFTER action");
        }

        // Add user_agent column if not exists
        if (!$this->columnExists('audit_logs', 'user_agent')) {
            $this->execute("ALTER TABLE audit_logs ADD COLUMN user_agent TEXT DEFAULT NULL AFTER ip_address");
        }

        // Add index on created_at for faster date-range queries
        if (!$this->indexExists('audit_logs', 'idx_audit_created_at')) {
            $this->execute("ALTER TABLE audit_logs ADD INDEX idx_audit_created_at (created_at)");
        }

        // Add index on user_id for faster user-based lookups
        if (!$this->indexExists('audit_logs', 'idx_audit_user_id')) {
            $this->execute("ALTER TABLE audit_logs ADD INDEX idx_audit_user_id (user_id)");
        }
    }

    public function down() {
        if ($this->columnExists('audit_logs', 'description')) {
            $this->execute("ALTER TABLE audit_logs DROP COLUMN description");
        }
        if ($this->columnExists('audit_logs', 'user_agent')) {
            $this->execute("ALTER TABLE audit_logs DROP COLUMN user_agent");
        }
    }
}
