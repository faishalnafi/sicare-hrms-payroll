<?php

namespace App\Services;

use App\Config\Database;
use PDO;

/**
 * Class LeaveService
 * 
 * Business Layer Service encapsulating leave requests, balance calculations,
 * working day calculations, and quota validation.
 */
class LeaveService {

    /**
     * Calculate working days duration between start_date and end_date excluding weekends & company holidays.
     */
    public function calculateLeaveDuration(string $startDate, string $endDate, array $companyHolidays = [], array $weeklyHolidays = ['Sat', 'Sun']): int {
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        if ($start > $end) return 0;

        $duration = 0;
        for ($curr = $start; $curr <= $end; $curr = strtotime('+1 day', $curr)) {
            $currDateStr = date('Y-m-d', $curr);
            $dayName = date('D', $curr);

            if (!in_array($dayName, $weeklyHolidays) && !in_array($currDateStr, $companyHolidays)) {
                $duration++;
            }
        }

        return $duration;
    }

    /**
     * Check if a leave request overlaps with existing approved/pending leave requests for a user.
     */
    public function hasOverlappingLeave(string $userId, string $startDate, string $endDate, ?string $excludeRequestId = null): bool {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "
                SELECT COUNT(*) FROM employee_leave_requests 
                WHERE user_id = :user_id 
                  AND status IN ('pending', 'approved')
                  AND start_date <= :end_date 
                  AND end_date >= :start_date
            ";
            $params = [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            if ($excludeRequestId) {
                $sql .= " AND id != :exclude_id";
                $params['exclude_id'] = $excludeRequestId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get remaining annual leave quota for an employee.
     */
    public function getRemainingAnnualLeave(string $userId): int {
        try {
            $db = Database::getInstance()->getConnection();

            $stmtUser = $db->prepare("SELECT annual_leave_quota FROM users WHERE id = :id");
            $stmtUser->execute(['id' => $userId]);
            $userQuota = (int)($stmtUser->fetchColumn() ?: 12);

            $stmtUsed = $db->prepare("
                SELECT SUM(duration) FROM employee_leave_requests
                WHERE user_id = :user_id
                  AND leave_type = 'cuti tahunan'
                  AND status = 'approved'
                  AND YEAR(start_date) = YEAR(CURRENT_DATE())
            ");
            $stmtUsed->execute(['user_id' => $userId]);
            $usedQuota = (int)($stmtUsed->fetchColumn() ?: 0);

            return max(0, $userQuota - $usedQuota);
        } catch (\Exception $e) {
            return 12;
        }
    }
}
