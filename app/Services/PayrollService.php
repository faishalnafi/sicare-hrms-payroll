<?php

namespace App\Services;

use App\Config\Database;
use PDO;

/**
 * Class PayrollService
 * 
 * Business Layer Service encapsulating salary calculations, BPJS deductions,
 * PPh21 tax deductions, and payroll run aggregation.
 */
class PayrollService {

    /**
     * Calculate BPJS Ketenagakerjaan deduction.
     */
    public function calculateBpjsTk(float $baseSalary, float $percentage = 2.0): float {
        return round(($baseSalary * $percentage) / 100, 2);
    }

    /**
     * Calculate BPJS Kesehatan deduction.
     */
    public function calculateBpjsKes(float $baseSalary, float $percentage = 1.0): float {
        return round(($baseSalary * $percentage) / 100, 2);
    }

    /**
     * Calculate PPh21 income tax deduction.
     */
    public function calculatePph21(float $taxableIncome, float $percentage = 2.5): float {
        return round(($taxableIncome * $percentage) / 100, 2);
    }

    /**
     * Calculate net salary from gross earnings and total deductions.
     */
    public function calculateNetSalary(float $baseSalary, float $allowances, float $overtime, float $bonus, float $deductions): float {
        $gross = $baseSalary + $allowances + $overtime + $bonus;
        return max(0.0, round($gross - $deductions, 2));
    }

    /**
     * Get active payroll settings (percentages and rates).
     */
    public function getPayrollSettings(): array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT `key`, `value` FROM global_settings WHERE `key` LIKE 'payroll_%'");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            return [
                'bpjs_tk_pct' => (float)($settings['payroll_bpjs_tk_pct'] ?? 2.0),
                'bpjs_kes_pct' => (float)($settings['payroll_bpjs_kes_pct'] ?? 1.0),
                'pph21_pct' => (float)($settings['payroll_pph21_pct'] ?? 2.5),
                'late_deduction' => (float)($settings['payroll_late_deduction'] ?? 50000.0),
            ];
        } catch (\Exception $e) {
            return [
                'bpjs_tk_pct' => 2.0,
                'bpjs_kes_pct' => 1.0,
                'pph21_pct' => 2.5,
                'late_deduction' => 50000.0,
            ];
        }
    }
}
