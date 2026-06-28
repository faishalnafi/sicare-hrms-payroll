<?php

use App\Config\Migration;

class CreateSysMenusTable extends Migration {
    public function up() {
        // 1. Create table 'sys_menus' if not exists
        $this->execute("
            CREATE TABLE IF NOT EXISTS sys_menus (
                id CHAR(36) PRIMARY KEY,
                title VARCHAR(100) NOT NULL,
                url_route VARCHAR(255) NOT NULL,
                icon VARCHAR(100) DEFAULT NULL,
                parent_id CHAR(36) DEFAULT NULL,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_sys_menus_parent FOREIGN KEY (parent_id) REFERENCES sys_menus (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Create table 'menu_permissions' if not exists
        $this->execute("
            CREATE TABLE IF NOT EXISTS menu_permissions (
                id CHAR(36) PRIMARY KEY,
                menu_id CHAR(36) NOT NULL,
                role_id CHAR(36) NOT NULL,
                department_id CHAR(36) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_menu_role_dept (menu_id, role_id, department_id),
                CONSTRAINT fk_menu_permissions_menu FOREIGN KEY (menu_id) REFERENCES sys_menus (id) ON DELETE CASCADE,
                CONSTRAINT fk_menu_permissions_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
                CONSTRAINT fk_menu_permissions_dept FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down() {
        $this->execute("DROP TABLE IF EXISTS menu_permissions");
        $this->execute("DROP TABLE IF EXISTS sys_menus");
    }
}
