<?php

use App\Config\Migration;

class CreatePortalApplicationsTable extends Migration {

    public function up() {
        if (!$this->tableExists('portal_applications')) {
            $this->execute("
                CREATE TABLE portal_applications (
                    id CHAR(36) PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    sort_order INT DEFAULT 0,
                    description TEXT NULL,
                    logo_url VARCHAR(255) NULL,
                    icon_material VARCHAR(100) DEFAULT 'apps',
                    icon_color VARCHAR(50) DEFAULT 'blue',
                    portal_url VARCHAR(255) NULL,
                    login_url VARCHAR(255) NULL,
                    is_active TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            echo "Created table: portal_applications\n";
        }
    }

    public function down() {
        if ($this->tableExists('portal_applications')) {
            $this->execute("DROP TABLE portal_applications;");
            echo "Dropped table: portal_applications\n";
        }
    }
}
