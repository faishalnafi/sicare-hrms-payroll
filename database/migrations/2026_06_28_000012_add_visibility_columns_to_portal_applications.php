<?php

use App\Config\Migration;

class AddVisibilityColumnsToPortalApplications extends Migration {

    public function up() {
        if ($this->tableExists('portal_applications')) {
            if (!$this->columnExists('portal_applications', 'visibility_type')) {
                $this->execute("ALTER TABLE portal_applications ADD COLUMN visibility_type VARCHAR(20) NOT NULL DEFAULT 'global' AFTER login_url");
                echo "Added column: portal_applications.visibility_type\n";
            }
            if (!$this->columnExists('portal_applications', 'allowed_roles')) {
                $this->execute("ALTER TABLE portal_applications ADD COLUMN allowed_roles TEXT NULL AFTER visibility_type");
                echo "Added column: portal_applications.allowed_roles\n";
            }
            if (!$this->columnExists('portal_applications', 'allowed_departments')) {
                $this->execute("ALTER TABLE portal_applications ADD COLUMN allowed_departments TEXT NULL AFTER allowed_roles");
                echo "Added column: portal_applications.allowed_departments\n";
            }
            
            // Also update the column icon_color length and default if needed
            $this->execute("ALTER TABLE portal_applications MODIFY COLUMN icon_color VARCHAR(50) DEFAULT '#3b82f6'");
            // Migrate existing text colors to hex equivalents (just in case they exist)
            $this->execute("UPDATE portal_applications SET icon_color = '#3b82f6' WHERE icon_color = 'blue'");
            $this->execute("UPDATE portal_applications SET icon_color = '#10b981' WHERE icon_color = 'emerald'");
            $this->execute("UPDATE portal_applications SET icon_color = '#f59e0b' WHERE icon_color = 'amber'");
            $this->execute("UPDATE portal_applications SET icon_color = '#8b5cf6' WHERE icon_color = 'purple'");
        }
    }

    public function down() {
        if ($this->tableExists('portal_applications')) {
            if ($this->columnExists('portal_applications', 'visibility_type')) {
                $this->execute("ALTER TABLE portal_applications DROP COLUMN visibility_type");
                echo "Dropped column: portal_applications.visibility_type\n";
            }
            if ($this->columnExists('portal_applications', 'allowed_roles')) {
                $this->execute("ALTER TABLE portal_applications DROP COLUMN allowed_roles");
                echo "Dropped column: portal_applications.allowed_roles\n";
            }
            if ($this->columnExists('portal_applications', 'allowed_departments')) {
                $this->execute("ALTER TABLE portal_applications DROP COLUMN allowed_departments");
                echo "Dropped column: portal_applications.allowed_departments\n";
            }
        }
    }
}
