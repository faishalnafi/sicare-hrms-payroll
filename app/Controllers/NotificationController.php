<?php

namespace App\Controllers;

use App\Config\Database;
use Exception;
use PDO;

class NotificationController {

    /**
     * SSE Endpoint for Real-time Notifications.
     * Uses Smart Polling: sends one event and closes connection, letting client EventSource auto-reconnect.
     */
    public function stream() {
        // Start session if none exists
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get user_id and roles before closing session
        $userId = $_SESSION['user_id'] ?? null;
        $userRole = $_SESSION['role'] ?? '';
        $origRole = $_SESSION['original_role'] ?? '';
        
        // Immediately close session to prevent blocking other concurrent AJAX requests for this user
        session_write_close();

        if (!$userId) {
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }

        // Disable attendance SSE notifications for Superadmin role or when Superadmin is impersonating
        if ($userRole === 'superadmin' || $origRole === 'superadmin') {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
            echo "data: {\"ping\": 1}\n\n";
            ob_flush();
            flush();
            exit;
        }

        // Set SSE Headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        // Prevent buffering on some web servers
        header('X-Accel-Buffering: no'); 

        $db = Database::getInstance()->getConnection();

        // 1. Fetch Global Settings for time bounds
        $stmt = $db->query("SELECT `key`, `value` FROM global_settings WHERE `key` IN ('work_start_time', 'work_min_start_time', 'work_min_start_time_enabled', 'work_end_time', 'work_min_end_time', 'work_min_end_time_enabled')");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }

        $workStartTime = $settings['work_start_time'] ?? '07:00';
        $workMinStartTime = $settings['work_min_start_time'] ?? '06:00';
        $minStartEnabled = ($settings['work_min_start_time_enabled'] ?? 'true') === 'true';

        $workEndTime = $settings['work_end_time'] ?? '21:00';
        $workMinEndTime = $settings['work_min_end_time'] ?? '15:00';
        $minEndEnabled = ($settings['work_min_end_time_enabled'] ?? 'false') === 'true';

        // 2. Fetch today's attendance for the user
        $today = date('Y-m-d');
        $stmt = $db->prepare("SELECT clock_in, clock_out FROM employee_attendance WHERE user_id = ? AND attendance_date = ?");
        $stmt->execute([$userId, $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        $hasClockedIn = false;
        $hasClockedOut = false;

        if ($attendance) {
            $hasClockedIn = !empty($attendance['clock_in']);
            $hasClockedOut = !empty($attendance['clock_out']);
        }

        $currentTime = date('H:i');
        
        $formatTimeDiff = function($time1, $time2) {
            $diffSeconds = abs(strtotime($time2) - strtotime($time1));
            $diffMinutes = round($diffSeconds / 60);
            if ($diffMinutes < 60) {
                return $diffMinutes . " menit";
            } elseif ($diffMinutes % 60 === 0) {
                return ($diffMinutes / 60) . " jam";
            } else {
                return floor($diffMinutes / 60) . " jam " . ($diffMinutes % 60) . " menit";
            }
        };

        $events = [];

        // --- Logic for Clock In Reminders ---
        if (!$hasClockedIn) {
            // A. Minimal Waktu Masuk Notification
            if ($minStartEnabled && $currentTime >= $workMinStartTime && $currentTime < $workStartTime) {
                $diffText = $formatTimeDiff($currentTime, $workStartTime);

                $events[] = [
                    'id' => 'clock_in_min',
                    'type' => 'reminder',
                    'title' => 'Waktunya Presensi!',
                    'message' => "Presensi Masuk kurang $diffText lagi dan sudah dapat dilakukan mulai sekarang, jangan terlupa untuk melakukan presensi hari ini."
                ];
            }
            // B. Waktu Standar Masuk Notification
            else if ($currentTime >= $workStartTime) {
                // If it's too late (e.g. standard is 07:00, maybe stop reminding at 12:00?)
                // We'll just rely on the frontend localStorage to show it exactly once per day.
                $events[] = [
                    'id' => 'clock_in_std',
                    'type' => 'reminder',
                    'title' => 'Waktu Masuk Berjalan!',
                    'message' => "Saat ini sudah memasuki waktu presensi masuk ($workStartTime). Segera lakukan presensi sebelum jam masuk ditutup."
                ];
            }
        }

        // --- Logic for Clock Out Reminders ---
        if ($hasClockedIn && !$hasClockedOut) {
            // C. Minimal Waktu Pulang Notification
            if ($minEndEnabled && $currentTime >= $workMinEndTime && $currentTime < $workEndTime) {
                $diffText = $formatTimeDiff($currentTime, $workEndTime);

                $events[] = [
                    'id' => 'clock_out_min',
                    'type' => 'reminder',
                    'title' => 'Peringatan Waktu Pulang!',
                    'message' => "Presensi Pulang kurang $diffText lagi dan sudah dapat dilakukan mulai sekarang, jangan terlupa untuk melakukan presensi hari ini."
                ];
            }
            // D. Waktu Standar Pulang Notification
            else if ($currentTime >= $workEndTime) {
                $events[] = [
                    'id' => 'clock_out_std',
                    'type' => 'reminder',
                    'title' => 'Waktunya Pulang!',
                    'message' => "Jam kerja telah berakhir ($workEndTime). Silakan melakukan presensi pulang sebelum meninggalkan pekerjaan."
                ];
            }
        }

        // Output JSON as SSE data
        if (!empty($events)) {
            echo "data: " . json_encode(['notifications' => $events]) . "\n\n";
        } else {
            // Keep-alive heartbeat to prevent the connection from dying without output
            echo "data: {\"ping\": 1}\n\n";
        }
        
        ob_flush();
        flush();
        
        // Smart polling: we exit immediately after sending one chunk of data.
        // The browser's EventSource will automatically reconnect after ~3-5 seconds.
        // This is much safer for standard PHP setups (Apache/PHP-FPM) than infinite while(true) loops.
        exit;
    }
}
