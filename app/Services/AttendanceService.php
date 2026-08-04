<?php

namespace App\Services;

use App\Config\Database;
use PDO;

/**
 * Class AttendanceService
 * 
 * Business Layer Service encapsulating attendance tracking, GPS geofencing,
 * lateness calculations, and attendance statistics.
 */
class AttendanceService {

    /**
     * Calculate Haversine distance between two GPS coordinates in meters.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if a location is within the office radius geofence.
     */
    public function isWithinGeofence(float $userLat, float $userLng, float $officeLat, float $officeLng, float $maxRadiusMeters): bool {
        $distance = $this->calculateDistance($userLat, $userLng, $officeLat, $officeLng);
        return $distance <= $maxRadiusMeters;
    }

    /**
     * Evaluate clock-in status (tepat waktu vs terlambat) based on scheduled work time.
     */
    public function evaluateClockInStatus(string $clockInTime, string $workStartTime = '08:00:00', int $gracePeriodMinutes = 15): string {
        $clockInTs = strtotime($clockInTime);
        $startTs = strtotime(date('Y-m-d ') . $workStartTime);
        $lateThresholdTs = $startTs + ($gracePeriodMinutes * 60);

        if ($clockInTs <= $lateThresholdTs) {
            return 'tepat waktu';
        }
        return 'terlambat';
    }

    /**
     * Get attendance summary for today for all employees.
     */
    public function getTodaySummary(string $todayDate): array {
        if (empty(trim($todayDate))) {
            return [
                'total_recorded' => 0,
                'tepat_waktu' => 0,
                'terlambat' => 0,
                'izin' => 0,
                'alpa' => 0
            ];
        }
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) AS total_recorded,
                    SUM(CASE WHEN status IN ('tepat waktu', 'awal') THEN 1 ELSE 0 END) AS tepat_waktu,
                    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) AS terlambat,
                    SUM(CASE WHEN status IN ('sakit/izin', 'izin') THEN 1 ELSE 0 END) AS izin,
                    SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) AS alpa
                FROM employee_attendance
                WHERE attendance_date = :today
            ");
            $stmt->execute(['today' => $todayDate]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total_recorded' => 0,
                'tepat_waktu' => 0,
                'terlambat' => 0,
                'izin' => 0,
                'alpa' => 0
            ];
        } catch (\Exception $e) {
            return [
                'total_recorded' => 0,
                'tepat_waktu' => 0,
                'terlambat' => 0,
                'izin' => 0,
                'alpa' => 0
            ];
        }
    }
}
