<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\SimpleCache;
use PDO;

class MenuController {
    private $db;
    private $cache;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance()->getConnection();
        $this->cache = SimpleCache::getInstance();
    }

    /**
     * Check if user is superadmin.
     */
    private function authorizeSuperAdmin() {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'superadmin') {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak: Hanya Superadmin yang diizinkan melakukan tindakan ini.']);
            exit;
        }
    }

    /**
     * GET /superadmin/menu/list
     */
    public function getMenus() {
        $this->authorizeSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $stmt = $this->db->query("SELECT * FROM sys_menus ORDER BY sort_order ASC, title ASC");
            $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $menus]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /superadmin/menu/save
     */
    public function saveMenu() {
        $this->authorizeSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = trim($input['id'] ?? '');
        $title = trim($input['title'] ?? '');
        $urlRoute = trim($input['url_route'] ?? '');
        $icon = trim($input['icon'] ?? '');
        $parentId = trim($input['parent_id'] ?? '');
        $sortOrder = (int)($input['sort_order'] ?? 0);

        if (empty($title) || empty($urlRoute)) {
            echo json_encode(['success' => false, 'message' => 'Judul menu dan URL rute wajib diisi.']);
            exit;
        }

        if ($parentId === '') {
            $parentId = null;
        }

        try {
            if (empty($id)) {
                // Generate UUID v4
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $stmt = $this->db->prepare("
                    INSERT INTO sys_menus (id, title, url_route, icon, parent_id, sort_order)
                    VALUES (:id, :title, :url, :icon, :parent, :sort)
                ");
                $stmt->execute([
                    'id' => $id,
                    'title' => $title,
                    'url' => $urlRoute,
                    'icon' => $icon ?: null,
                    'parent' => $parentId,
                    'sort' => $sortOrder
                ]);

                // Map to superadmin by default so superadmin can always see it
                $permId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $stmtPerm = $this->db->prepare("
                    INSERT INTO menu_permissions (id, menu_id, role_id, department_id)
                    VALUES (:id, :menu_id, '8f5d7b6c-9c3f-4e08-8e67-d86b976f92c1', NULL)
                ");
                $stmtPerm->execute(['id' => $permId, 'menu_id' => $id]);

                $message = 'Menu baru berhasil dibuat dan otomatis terpetakan ke Superadmin.';
            } else {
                $stmt = $this->db->prepare("
                    UPDATE sys_menus 
                    SET title = :title, url_route = :url, icon = :icon, parent_id = :parent, sort_order = :sort
                    WHERE id = :id
                ");
                $stmt->execute([
                    'title' => $title,
                    'url' => $urlRoute,
                    'icon' => $icon ?: null,
                    'parent' => $parentId,
                    'sort' => $sortOrder,
                    'id' => $id
                ]);
                $message = 'Menu berhasil diperbarui.';
            }

            // Invalidate cache
            $this->cache->clear('sidebar_menus_');

            // Log action
            $this->writeAuditLog("Menyimpan menu: $title (URL: $urlRoute)", 'sys_menus');

            echo json_encode(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /superadmin/menu/delete
     */
    public function deleteMenu() {
        $this->authorizeSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = trim($input['id'] ?? '');

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID menu wajib ditentukan.']);
            exit;
        }

        try {
            // Fetch title first for logging
            $stmtName = $this->db->prepare("SELECT title FROM sys_menus WHERE id = :id");
            $stmtName->execute(['id' => $id]);
            $title = $stmtName->fetchColumn() ?: 'Unknown';

            $stmt = $this->db->prepare("DELETE FROM sys_menus WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Invalidate cache
            $this->cache->clear('sidebar_menus_');

            // Log action
            $this->writeAuditLog("Menghapus menu: $title (ID: $id)", 'sys_menus');

            echo json_encode(['success' => true, 'message' => 'Menu berhasil dihapus.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * GET /superadmin/menu-permissions/matrix
     */
    public function getPermissionMatrix() {
        $this->authorizeSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        try {
            // Fetch all menus
            $menus = $this->db->query("SELECT id, title, url_route FROM sys_menus ORDER BY sort_order ASC, title ASC")->fetchAll(PDO::FETCH_ASSOC);
            
            // Fetch all roles
            $roles = $this->db->query("SELECT id, name, display_name FROM roles ORDER BY display_name ASC")->fetchAll(PDO::FETCH_ASSOC);

            // Fetch C-Level departments (Executive departments)
            $departments = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

            // Fetch all current permissions
            $permissions = $this->db->query("SELECT menu_id, role_id, department_id FROM menu_permissions")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'menus' => $menus,
                'roles' => $roles,
                'departments' => $departments,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /superadmin/menu-permissions/save
     */
    public function savePermissions() {
        $this->authorizeSuperAdmin();
        header('Content-Type: application/json; charset=utf-8');

        // Validate CSRF
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $permissions = $input['permissions'] ?? [];

        try {
            $this->db->beginTransaction();

            // Clear all permissions
            $this->db->exec("DELETE FROM menu_permissions");

            // Insert new permissions
            $stmt = $this->db->prepare("
                INSERT INTO menu_permissions (id, menu_id, role_id, department_id)
                VALUES (:id, :menu_id, :role_id, :dept_id)
            ");

            $savedCount = 0;
            foreach ($permissions as $p) {
                $menuId = trim($p['menu_id'] ?? '');
                $roleId = trim($p['role_id'] ?? '');
                $deptId = trim($p['department_id'] ?? '');

                if (empty($menuId) || empty($roleId)) continue;

                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $stmt->execute([
                    'id' => $uuid,
                    'menu_id' => $menuId,
                    'role_id' => $roleId,
                    'dept_id' => $deptId !== '' ? $deptId : null
                ]);
                $savedCount++;
            }

            $this->db->commit();

            // Invalidate cache
            $this->cache->clear('sidebar_menus_');

            // Log action
            $this->writeAuditLog("Memperbarui matriks ACL menu ($savedCount izin tersimpan).", 'menu_permissions');

            echo json_encode(['success' => true, 'message' => 'Matriks hak akses menu berhasil disimpan.']);
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Helper to write audit logs.
     */
    private function writeAuditLog($action, $table) {
        try {
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address)
                VALUES (:id, :user, :action, :table, :ip)
            ");
            $stmt->execute([
                'id' => $logId,
                'user' => $_SESSION['user_id'] ?? '',
                'action' => $action,
                'table' => $table,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (\Exception $e) {
            // Fail-safe
        }
    }
}
