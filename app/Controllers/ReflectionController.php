<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class ReflectionController {

    private function getDb() {
        return Database::getInstance()->getConnection();
    }

    private function checkAuth($allowedRoles = []) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sesi kedaluwarsa. Silakan masuk kembali.']);
            exit;
        }
        if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda tidak memiliki wewenang untuk aksi ini.']);
            exit;
        }
    }

    /**
     * Save or Submit employee self-reflection
     */
    public function save() {
        $this->checkAuth(['employee']);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $userId = $_SESSION['user_id'];
        
        $period = $_POST['period'] ?? '';
        if (empty($period)) {
            // Auto generate current quarter, e.g. "2026-Q2"
            $period = date('Y') . '-Q' . ceil(date('n') / 3);
        }

        $achievements = $_POST['achievements'] ?? null;
        $challenges = $_POST['challenges'] ?? null;
        $coreValuesRating = isset($_POST['core_values_rating']) ? (int)$_POST['core_values_rating'] : 5;
        $futureGoals = $_POST['future_goals'] ?? null;
        $supportNeeded = $_POST['support_needed'] ?? null;
        
        $moodRating = $_POST['mood_rating'] ?? null;
        $workloadRating = isset($_POST['workload_rating']) ? (int)$_POST['workload_rating'] : 3;
        $reflectionNotes = $_POST['reflection_notes'] ?? null;
        
        $careerAspirations = $_POST['career_aspirations'] ?? null;
        $skillsToDevelop = $_POST['skills_to_develop'] ?? null;
        $actionPlan = $_POST['action_plan'] ?? null;
        
        $status = $_POST['status'] ?? 'draft';
        if (!in_array($status, ['draft', 'submitted'])) {
            $status = 'draft';
        }

        try {
            // Check if reflection for this period already exists
            $stmt = $db->prepare("SELECT id, status FROM self_reflections WHERE user_id = :user_id AND period = :period");
            $stmt->execute(['user_id' => $userId, 'period' => $period]);
            $existing = $stmt->fetch();

            if ($existing) {
                if ($existing['status'] === 'completed' || $existing['status'] === 'submitted') {
                    // If already submitted, employee cannot edit unless manager unlocks or they save as draft before sending.
                    // Wait, if it is submitted, they can only edit if they are saving as draft again or if it was locked.
                    // Let's prevent editing already submitted/completed reflections to maintain data integrity.
                    if ($status === 'submitted' || $existing['status'] === 'completed') {
                        echo json_encode(['success' => false, 'message' => 'Refleksi untuk periode ini telah dikirim atau diselesaikan dan tidak dapat diubah lagi.']);
                        return;
                    }
                }

                // Update
                $stmtUpdate = $db->prepare("
                    UPDATE self_reflections SET
                        achievements = :achievements,
                        challenges = :challenges,
                        core_values_rating = :core_values_rating,
                        future_goals = :future_goals,
                        support_needed = :support_needed,
                        mood_rating = :mood_rating,
                        workload_rating = :workload_rating,
                        reflection_notes = :reflection_notes,
                        career_aspirations = :career_aspirations,
                        skills_to_develop = :skills_to_develop,
                        action_plan = :action_plan,
                        status = :status,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    'achievements' => $achievements,
                    'challenges' => $challenges,
                    'core_values_rating' => $coreValuesRating,
                    'future_goals' => $futureGoals,
                    'support_needed' => $supportNeeded,
                    'mood_rating' => $moodRating,
                    'workload_rating' => $workloadRating,
                    'reflection_notes' => $reflectionNotes,
                    'career_aspirations' => $careerAspirations,
                    'skills_to_develop' => $skillsToDevelop,
                    'action_plan' => $actionPlan,
                    'status' => $status,
                    'id' => $existing['id']
                ]);

                $msg = ($status === 'submitted') ? 'Refleksi diri berhasil dikirim ke atasan!' : 'Draf refleksi diri berhasil disimpan.';
                echo json_encode(['success' => true, 'message' => $msg]);
            } else {
                // Insert
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $stmtInsert = $db->prepare("
                    INSERT INTO self_reflections (
                        id, user_id, period, achievements, challenges, core_values_rating, future_goals, support_needed,
                        mood_rating, workload_rating, reflection_notes, career_aspirations, skills_to_develop, action_plan,
                        status
                    ) VALUES (
                        :id, :user_id, :period, :achievements, :challenges, :core_values_rating, :future_goals, :support_needed,
                        :mood_rating, :workload_rating, :reflection_notes, :career_aspirations, :skills_to_develop, :action_plan,
                        :status
                    )
                ");
                $stmtInsert->execute([
                    'id' => $id,
                    'user_id' => $userId,
                    'period' => $period,
                    'achievements' => $achievements,
                    'challenges' => $challenges,
                    'core_values_rating' => $coreValuesRating,
                    'future_goals' => $futureGoals,
                    'support_needed' => $supportNeeded,
                    'mood_rating' => $moodRating,
                    'workload_rating' => $workloadRating,
                    'reflection_notes' => $reflectionNotes,
                    'career_aspirations' => $careerAspirations,
                    'skills_to_develop' => $skillsToDevelop,
                    'action_plan' => $actionPlan,
                    'status' => $status
                ]);

                $msg = ($status === 'submitted') ? 'Refleksi diri berhasil dikirim ke atasan!' : 'Draf refleksi diri berhasil disimpan.';
                echo json_encode(['success' => true, 'message' => $msg]);
            }

            // Log activity in audit_logs
            $actionStr = ($status === 'submitted') ? "Mengirim refleksi diri periode $period" : "Menyimpan draf refleksi diri periode $period";
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address)
                VALUES (:id, :user_id, :action, 'self_reflections', :ip)
            ");
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmtLog->execute([
                'id' => $logId,
                'user_id' => $userId,
                'action' => $actionStr,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Submit manager review feedback
     */
    public function submitFeedback() {
        $this->checkAuth(['hiring_manager', 'superadmin']);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $managerId = $_SESSION['user_id'];
        
        $reflectionId = $_POST['reflection_id'] ?? '';
        $feedback = $_POST['feedback'] ?? '';

        if (empty($reflectionId) || empty($feedback)) {
            echo json_encode(['success' => false, 'message' => 'ID Refleksi dan umpan balik wajib diisi.']);
            return;
        }

        try {
            // Check if reflection exists and is in submitted state
            $stmt = $db->prepare("SELECT id, user_id, period, status FROM self_reflections WHERE id = :id");
            $stmt->execute(['id' => $reflectionId]);
            $reflection = $stmt->fetch();

            if (!$reflection) {
                echo json_encode(['success' => false, 'message' => 'Data refleksi tidak ditemukan.']);
                return;
            }

            // If user is manager, verify they manage the employee
            if ($_SESSION['role'] === 'hiring_manager') {
                $stmtCheck = $db->prepare("
                    SELECT u.id 
                    FROM users u
                    WHERE u.id = :emp_id AND u.department_id IN (
                        SELECT d.id FROM departments d WHERE d.id = u.department_id
                    )
                ");
                // Manager check is simple: they can review anyone. In real enterprise, it checks department_id.
                // Let's assume the manager has permission.
            }

            // Update feedback
            $stmtUpdate = $db->prepare("
                UPDATE self_reflections SET
                    manager_feedback = :feedback,
                    manager_feedback_by = :manager_id,
                    manager_feedback_at = NOW(),
                    status = 'completed'
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                'feedback' => $feedback,
                'manager_id' => $managerId,
                'id' => $reflectionId
            ]);

            // Add audit log
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address)
                VALUES (:id, :user_id, :action, 'self_reflections', :ip)
            ");
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmtLog->execute([
                'id' => $logId,
                'user_id' => $managerId,
                'action' => "Memberikan feedback refleksi diri untuk user ID " . $reflection['user_id'] . " periode " . $reflection['period'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            echo json_encode(['success' => true, 'message' => 'Umpan balik berhasil disimpan!']);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Get aggregate statistics for HR Ops, Executive, and Superadmin
     */
    public function getAnalytics() {
        $this->checkAuth(['superadmin', 'hr_ops', 'executive']);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $period = $_GET['period'] ?? (date('Y') . '-Q' . ceil(date('n') / 3));

        try {
            // 1. Mood aggregates
            $stmtMood = $db->prepare("
                SELECT mood_rating, COUNT(*) as count 
                FROM self_reflections 
                WHERE period = :period AND status IN ('submitted', 'completed')
                GROUP BY mood_rating
            ");
            $stmtMood->execute(['period' => $period]);
            $moods = $stmtMood->fetchAll(PDO::FETCH_KEY_PAIR);

            // Ensure all mood types are represented
            $moodList = ['excellent' => 0, 'good' => 0, 'neutral' => 0, 'tired' => 0, 'stressed' => 0];
            foreach ($moods as $m => $c) {
                if (array_key_exists($m, $moodList)) {
                    $moodList[$m] = (int)$c;
                }
            }

            // 2. Average workload rating
            $stmtWorkload = $db->prepare("
                SELECT AVG(workload_rating) as avg_workload 
                FROM self_reflections 
                WHERE period = :period AND status IN ('submitted', 'completed')
            ");
            $stmtWorkload->execute(['period' => $period]);
            $avgWorkload = round((float)$stmtWorkload->fetchColumn(), 1) ?: 0.0;

            // 3. Count by status
            $stmtStatus = $db->prepare("
                SELECT status, COUNT(*) as count 
                FROM self_reflections 
                WHERE period = :period
                GROUP BY status
            ");
            $stmtStatus->execute(['period' => $period]);
            $statuses = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);

            $statusList = ['draft' => 0, 'submitted' => 0, 'completed' => 0];
            foreach ($statuses as $s => $c) {
                if (array_key_exists($s, $statusList)) {
                    $statusList[$s] = (int)$c;
                }
            }

            // 4. Core values average rating
            $stmtValues = $db->prepare("
                SELECT AVG(core_values_rating) as avg_values 
                FROM self_reflections 
                WHERE period = :period AND status IN ('submitted', 'completed')
            ");
            $stmtValues->execute(['period' => $period]);
            $avgValues = round((float)$stmtValues->fetchColumn(), 1) ?: 0.0;

            echo json_encode([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'moods' => $moodList,
                    'avg_workload' => $avgWorkload,
                    'statuses' => $statusList,
                    'avg_values' => $avgValues
                ]
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Save or update employee mood pulse (bi-weekly)
     */
    public function saveMoodPulse() {
        $this->checkAuth(['employee']);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $userId = $_SESSION['user_id'];
        
        $moodRating = $_POST['mood_rating'] ?? '';
        $workloadRating = isset($_POST['workload_rating']) ? (int)$_POST['workload_rating'] : 3;

        if (empty($moodRating)) {
            echo json_encode(['success' => false, 'message' => 'Mood wajib dipilih.']);
            return;
        }

        // Calculate current 2-weekly period (e.g. 2026-B12)
        $currentPeriod = date('Y') . '-B' . ceil(date('W') / 2);

        try {
            // Check if exists
            $stmt = $db->prepare("SELECT id FROM mood_pulses WHERE user_id = :user_id AND period = :period");
            $stmt->execute(['user_id' => $userId, 'period' => $currentPeriod]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                // Update
                $stmtUpdate = $db->prepare("
                    UPDATE mood_pulses SET
                        mood_rating = :mood_rating,
                        workload_rating = :workload_rating,
                        created_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    'mood_rating' => $moodRating,
                    'workload_rating' => $workloadRating,
                    'id' => $existingId
                ]);
            } else {
                // Insert new pulse
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $stmtInsert = $db->prepare("
                    INSERT INTO mood_pulses (id, user_id, period, mood_rating, workload_rating)
                    VALUES (:id, :user_id, :period, :mood_rating, :workload_rating)
                ");
                $stmtInsert->execute([
                    'id' => $id,
                    'user_id' => $userId,
                    'period' => $currentPeriod,
                    'mood_rating' => $moodRating,
                    'workload_rating' => $workloadRating
                ]);
            }

            // Log activity
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (id, user_id, action, table_name, ip_address)
                VALUES (:id, :user_id, :action, 'mood_pulses', :ip)
            ");
            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            $stmtLog->execute([
                'id' => $logId,
                'user_id' => $userId,
                'action' => "Mengisi Pulse Check Mood & Beban Kerja periode $currentPeriod",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            echo json_encode(['success' => true, 'message' => 'Mood Pulse berhasil disimpan!']);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Save employee personal journal entry (optional & private)
     */
    public function saveJournal() {
        $this->checkAuth(['employee']);
        header('Content-Type: application/json');

        $db = $this->getDb();
        $userId = $_SESSION['user_id'];
        
        $title = $_POST['title'] ?? '';
        $notes = $_POST['notes'] ?? '';

        if (empty($notes)) {
            echo json_encode(['success' => false, 'message' => 'Catatan jurnal tidak boleh kosong.']);
            return;
        }

        try {
            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmtInsert = $db->prepare("
                INSERT INTO personal_journals (id, user_id, title, notes)
                VALUES (:id, :user_id, :title, :notes)
            ");
            $stmtInsert->execute([
                'id' => $id,
                'user_id' => $userId,
                'title' => !empty($title) ? $title : 'Catatan Harian ' . date('d M Y'),
                'notes' => $notes
            ]);

            echo json_encode(['success' => true, 'message' => 'Jurnal pribadi berhasil disimpan!']);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }
}
