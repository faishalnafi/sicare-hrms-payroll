<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;

class DepartmentController {
    
    private function checkAccess() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        $role = $_SESSION['role'] ?? '';
        $allowed = in_array($role, ['admin', 'superadmin']);
        if (!$allowed) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Hanya Admin atau Superadmin yang dapat mengelola struktur departemen.']);
            exit;
        }
    }

    public function save() {
        $this->checkAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

        $parseLimit = function($val, $fieldName) {
            if ($val !== '') {
                $val = str_replace([',', '.'], '', $val);
                if (!is_numeric($val) || floatval($val) < 0) {
                    throw new \Exception("Nilai {$fieldName} harus berupa angka positif atau kosong.");
                }
                return floatval($val);
            }
            return null;
        };

        try {
            $reimbursement_limit = $parseLimit(isset($_POST['reimbursement_limit']) ? trim($_POST['reimbursement_limit']) : '', 'plafon total departemen');
            $limit_medis = $parseLimit(isset($_POST['limit_medis']) ? trim($_POST['limit_medis']) : '', 'plafon medis');
            $limit_transport = $parseLimit(isset($_POST['limit_transport']) ? trim($_POST['limit_transport']) : '', 'plafon transportasi');
            $limit_operasional = $parseLimit(isset($_POST['limit_operasional']) ? trim($_POST['limit_operasional']) : '', 'plafon operasional');
            $limit_makan = $parseLimit(isset($_POST['limit_makan']) ? trim($_POST['limit_makan']) : '', 'plafon makan & bisnis');
        } catch (\Exception $ex) {
            echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
            return;
        }

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Nama departemen wajib diisi.']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Calculate Level
            $level = 1;
            if ($parent_id) {
                // Fetch parent level
                $stmtParent = $db->prepare("SELECT level, parent_id FROM departments WHERE id = ?");
                $stmtParent->execute([$parent_id]);
                $parent = $stmtParent->fetch(PDO::FETCH_ASSOC);
                
                if (!$parent) {
                    echo json_encode(['success' => false, 'message' => 'Departemen induk tidak ditemukan.']);
                    return;
                }
                
                $level = $parent['level'] + 1;
                
                if ($level > 10) {
                    echo json_encode(['success' => false, 'message' => 'Batas kedalaman struktur departemen maksimal adalah 10 level!']);
                    return;
                }

                // Prevent circular referencing when editing
                if (!empty($id) && $id === $parent_id) {
                    echo json_encode(['success' => false, 'message' => 'Departemen tidak boleh menjadi induk dari dirinya sendiri.']);
                    return;
                }
            }

            $db->beginTransaction();

            if (empty($id)) {
                // INSERT NEW
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $stmt = $db->prepare("
                    INSERT INTO departments (id, name, parent_id, level, reimbursement_limit, limit_medis, limit_transport, limit_operasional, limit_makan, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$uuid, $name, $parent_id, $level, $reimbursement_limit, $limit_medis, $limit_transport, $limit_operasional, $limit_makan]);
                $message = 'Departemen baru berhasil ditambahkan.';
            } else {
                // UPDATE EXISTING
                // Check if moving this department would cause child levels to exceed 5
                if ($parent_id) {
                    // Get all descendant max level depth
                    $maxDepth = $this->getMaxDescendantDepth($db, $id, 0);
                    if ($level + $maxDepth > 10) {
                        echo json_encode(['success' => false, 'message' => 'Gagal memindahkan departemen. Tindakan ini akan menyebabkan sub-divisi di bawahnya melebihi kedalaman 10 level.']);
                        return;
                    }
                }

                $stmt = $db->prepare("
                    UPDATE departments 
                    SET name = ?, parent_id = ?, level = ?, reimbursement_limit = ?, limit_medis = ?, limit_transport = ?, limit_operasional = ?, limit_makan = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $parent_id, $level, $reimbursement_limit, $limit_medis, $limit_transport, $limit_operasional, $limit_makan, $id]);

                // Recursively update levels of all descendants
                $this->updateDescendantsLevels($db, $id, $level);

                $message = 'Departemen berhasil diperbarui.';
            }

            // Write to audit log
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $actionText = "Mengelola struktur departemen: " . (empty($id) ? "Tambah" : "Edit") . " '$name' (Level: $level)";
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address) 
                VALUES (?, ?, ?, 'departments', ?)
            ");
            $stmtLog->execute([$logId, $_SESSION['user_id'], $actionText, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $db->commit();
            echo json_encode(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal memproses data: ' . $e->getMessage()]);
        }
    }

    private function getMaxDescendantDepth(PDO $db, string $deptId, int $currentDepth): int {
        $stmt = $db->prepare("SELECT id FROM departments WHERE parent_id = ?");
        $stmt->execute([$deptId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($children)) {
            return $currentDepth;
        }
        
        $max = $currentDepth;
        foreach ($children as $childId) {
            $depth = $this->getMaxDescendantDepth($db, $childId, $currentDepth + 1);
            if ($depth > $max) {
                $max = $depth;
            }
        }
        return $max;
    }

    private function updateDescendantsLevels(PDO $db, string $parentId, int $parentLevel) {
        $stmt = $db->prepare("SELECT id FROM departments WHERE parent_id = ?");
        $stmt->execute([$parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($children as $childId) {
            $childLevel = $parentLevel + 1;
            $stmtUpdate = $db->prepare("UPDATE departments SET level = ? WHERE id = ?");
            $stmtUpdate->execute([$childLevel, $childId]);
            $this->updateDescendantsLevels($db, $childId, $childLevel);
        }
    }

    public function move() {
        $this->checkAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $id = $_POST['id'] ?? '';
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID departemen wajib diisi.']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Fetch the department to move
            $stmt = $db->prepare("SELECT name FROM departments WHERE id = ?");
            $stmt->execute([$id]);
            $name = $stmt->fetchColumn();
            if (!$name) {
                echo json_encode(['success' => false, 'message' => 'Departemen tidak ditemukan.']);
                return;
            }

            // Calculate Level
            $level = 1;
            if ($parent_id) {
                // Fetch parent level
                $stmtParent = $db->prepare("SELECT level FROM departments WHERE id = ?");
                $stmtParent->execute([$parent_id]);
                $parentLevel = $stmtParent->fetchColumn();
                
                if ($parentLevel === false) {
                    echo json_encode(['success' => false, 'message' => 'Departemen induk tidak ditemukan.']);
                    return;
                }
                
                $level = $parentLevel + 1;
                
                if ($level > 10) {
                    echo json_encode(['success' => false, 'message' => 'Batas kedalaman struktur departemen maksimal adalah 10 level!']);
                    return;
                }

                // Prevent circular referencing
                if ($id === $parent_id) {
                    echo json_encode(['success' => false, 'message' => 'Departemen tidak boleh menjadi induk dari dirinya sendiri.']);
                    return;
                }

                // Check if the parent is a descendant of the department being moved
                if ($this->isDescendant($db, $id, $parent_id)) {
                    echo json_encode(['success' => false, 'message' => 'Tidak dapat memindahkan departemen di bawah salah satu sub-divisinya sendiri.']);
                    return;
                }
            }

            // Check if moving this department would cause child levels to exceed 10
            $maxDepth = $this->getMaxDescendantDepth($db, $id, 0);
            if ($level + $maxDepth > 10) {
                echo json_encode(['success' => false, 'message' => 'Gagal memindahkan departemen. Tindakan ini akan menyebabkan sub-divisi di bawahnya melebihi kedalaman 10 level.']);
                return;
            }

            $db->beginTransaction();

            $stmtUpdate = $db->prepare("UPDATE departments SET parent_id = ?, level = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtUpdate->execute([$parent_id, $level, $id]);

            // Recursively update levels of all descendants
            $this->updateDescendantsLevels($db, $id, $level);

            // Write to audit log
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $actionText = "Memindahkan departemen: '$name' (Level baru: $level)";
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address) 
                VALUES (?, ?, ?, 'departments', ?)
            ");
            $stmtLog->execute([$logId, $_SESSION['user_id'], $actionText, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Departemen berhasil dipindahkan.']);

        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal memproses data: ' . $e->getMessage()]);
        }
    }

    private function isDescendant(PDO $db, string $ancestorId, string $descendantId): bool {
        $stmt = $db->prepare("SELECT parent_id FROM departments WHERE id = ?");
        $stmt->execute([$descendantId]);
        $parentId = $stmt->fetchColumn();
        if (!$parentId) {
            return false;
        }
        if ($parentId === $ancestorId) {
            return true;
        }
        return $this->isDescendant($db, $ancestorId, $parentId);
    }

    public function delete() {
        $this->checkAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID departemen wajib diisi.']);
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Check if there are any child departments under this department
            $stmtCheckChild = $db->prepare("SELECT COUNT(*) FROM departments WHERE parent_id = ?");
            $stmtCheckChild->execute([$id]);
            $hasChildren = $stmtCheckChild->fetchColumn() > 0;

            if ($hasChildren) {
                echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus departemen ini karena memiliki sub-departemen di bawahnya. Harap hapus atau pindahkan sub-departemen terlebih dahulu.']);
                return;
            }

            // Check if there are any users registered in this department
            $stmtCheckUser = $db->prepare("SELECT COUNT(*) FROM users WHERE department_id = ?");
            $stmtCheckUser->execute([$id]);
            $hasUsers = $stmtCheckUser->fetchColumn() > 0;

            if ($hasUsers) {
                echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus departemen ini karena masih memiliki karyawan terdaftar di dalamnya. Silakan pindahkan karyawan tersebut terlebih dahulu.']);
                return;
            }

            // Fetch department name for log
            $stmtName = $db->prepare("SELECT name FROM departments WHERE id = ?");
            $stmtName->execute([$id]);
            $name = $stmtName->fetchColumn() ?: 'Departemen';

            $db->beginTransaction();

            // Execute delete
            $stmtDelete = $db->prepare("DELETE FROM departments WHERE id = ?");
            $stmtDelete->execute([$id]);

            // Write to audit log
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $actionText = "Menghapus departemen: '$name'";
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address) 
                VALUES (?, ?, ?, 'departments', ?)
            ");
            $stmtLog->execute([$logId, $_SESSION['user_id'], $actionText, 'departments', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Departemen berhasil dihapus.']);

        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus departemen: ' . $e->getMessage()]);
        }
    }
}
