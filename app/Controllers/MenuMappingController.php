<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\AuthHelper;
use App\Helpers\ViewHelper;

class MenuMappingController {
    public function list() {
        if (!AuthHelper::hasRole('superadmin') && !AuthHelper::hasRole('admin')) {
            header('HTTP/1.1 403 Forbidden');
            die('Access Denied');
        }

        $db = Database::getInstance()->getConnection();
        
        // Fetch all system menus
        $stmtMenus = $db->query("SELECT * FROM system_menus ORDER BY menu_name ASC");
        $menus = $stmtMenus->fetchAll();

        // Fetch executive departments
        $stmtDepts = $db->query("SELECT * FROM departments WHERE is_executive_entity = TRUE ORDER BY name ASC");
        $depts = $stmtDepts->fetchAll();

        // Fetch current assignments
        $stmtPrivileges = $db->query("
            SELECT department_id, menu_id, is_active 
            FROM department_menu_privileges 
            WHERE is_active = TRUE
        ");
        $privileges = $stmtPrivileges->fetchAll();

        $assignmentMap = [];
        foreach ($privileges as $p) {
            $assignmentMap[$p['department_id']][] = $p['menu_id'];
        }

        ViewHelper::render('pages/superadmin/menu_mapping', [
            'menus' => $menus, 
            'departments' => $depts,
            'assignmentMap' => $assignmentMap,
            'title' => 'Menu Privilege Mapping'
        ]);
    }

    public function assign() {
        if (!AuthHelper::hasRole('superadmin') && !AuthHelper::hasRole('admin')) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            exit;
        }

        $departmentId = $_POST['department_id'] ?? '';
        $menuIds = $_POST['menu_ids'] ?? [];

        if (empty($departmentId)) {
            echo json_encode(['success' => false, 'message' => 'Department ID is required']);
            exit;
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->beginTransaction();

            // Clear existing mapping for this department
            $stmtClear = $db->prepare("DELETE FROM department_menu_privileges WHERE department_id = ?");
            $stmtClear->execute([$departmentId]);

            // Insert new mapping
            if (!empty($menuIds)) {
                $stmtInsert = $db->prepare("INSERT INTO department_menu_privileges (id, department_id, menu_id, is_active) VALUES (?, ?, ?, TRUE)");
                foreach ($menuIds as $menuId) {
                    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    $stmtInsert->execute([$uuid, $departmentId, $menuId]);
                }
            }

            // Log action if superadmin
            if (AuthHelper::hasRole('superadmin')) {
                $user = AuthHelper::getUser();
                $actorName = $user['first_name'] . ' ' . ($user['last_name'] ?? '');
                $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $desc = "Superadmin [{$actorName}] memperbarui mapping menu untuk departemen ID [{$departmentId}]";
                $stmtLog = $db->prepare("INSERT INTO audit_logs (id, user_id, action, table_name, ip_address, description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtLog->execute([
                    $logId,
                    $user['id'],
                    'UPDATE',
                    'department_menu_privileges',
                    $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    $desc
                ]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Menu mapping updated successfully']);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error updating mapping: ' . $e->getMessage()]);
        }
    }
}
