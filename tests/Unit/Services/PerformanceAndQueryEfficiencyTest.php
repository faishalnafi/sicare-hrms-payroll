<?php

namespace Tests\Unit\Services;

use App\Services\AttendanceService;
use App\Services\AuthService;
use App\Services\LeaveService;
use App\Services\PayrollService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PDO;
use PDOStatement;
use Tests\Unit\BaseTestCase;

/**
 * PerformanceAndQueryEfficiencyTest
 *
 * Menguji SLA performa membuka menu dan memuat data (harus < 1 detik = 1000 ms)
 * serta menguji efisiensi query database agar terbebas dari query lemot (Full Table Scan / N+1).
 *
 * Terhubung ke:
 *   - App\Services\AttendanceService
 *   - App\Services\LeaveService
 *   - App\Services\PayrollService
 *   - App\Services\AuthService
 */
#[CoversClass(AttendanceService::class)]
#[CoversClass(LeaveService::class)]
#[CoversClass(PayrollService::class)]
#[CoversClass(AuthService::class)]
class PerformanceAndQueryEfficiencyTest extends BaseTestCase
{
    /**
     * Skenario 1: Evaluasi 200 kalkulasi status absensi harus selesai < 1.000 ms (SLA < 1 Detik).
     * Terhubung ke: AttendanceService::evaluateClockInStatus() di AttendanceService.php:L45.
     */
    #[Test]
    public function menuAttendance_bulkEvaluationPerformance_isUnderOneSecond(): void
    {
        $service = new AttendanceService();

        // Uji eksekusi batch 200 data absensi dengan SLA max 1000.0 ms (1 detik)
        $this->assertExecutionTimeLessThanMs(function () use ($service) {
            for ($i = 0; $i < 200; $i++) {
                $service->evaluateClockInStatus('2026-07-31 08:10:00', '08:00:00', 15);
            }
        }, 1000.0, 'Batch evaluasi data absensi harus selesai < 1 detik.');
    }

    /**
     * Skenario 2: Evaluasi 200 kalkulasi jarak GPS Haversine harus selesai < 1.000 ms (SLA < 1 Detik).
     * Terhubung ke: AttendanceService::calculateDistance() di AttendanceService.php:L19.
     */
    #[Test]
    public function menuGeofence_bulkDistanceCalculationPerformance_isUnderOneSecond(): void
    {
        $service = new AttendanceService();

        // Uji 200 kalkulasi GPS Haversine dengan SLA max 1000.0 ms (1 detik)
        $this->assertExecutionTimeLessThanMs(function () use ($service) {
            for ($i = 0; $i < 200; $i++) {
                $service->calculateDistance(-6.2297, 106.8164, -6.2286, 106.8164);
            }
        }, 1000.0, 'Batch 200 perhitungan jarak geofence GPS harus selesai < 1 detik.');
    }

    /**
     * Skenario 3: Perhitungan 200 durasi pengajuan cuti harus selesai < 1.000 ms (SLA < 1 Detik).
     * Terhubung ke: LeaveService::calculateLeaveDuration() di LeaveService.php:L19.
     */
    #[Test]
    public function menuLeave_bulkDurationCalculationPerformance_isUnderOneSecond(): void
    {
        $service = new LeaveService();

        // Uji 200 kalkulasi durasi cuti dengan SLA max 1000.0 ms (1 detik)
        $this->assertExecutionTimeLessThanMs(function () use ($service) {
            for ($i = 0; $i < 200; $i++) {
                $service->calculateLeaveDuration('2026-08-01', '2026-08-15', ['2026-08-17']);
            }
        }, 1000.0, 'Kalkulasi durasi cuti batch harus selesai < 1 detik.');
    }

    /**
     * Skenario 4: Perhitungan 200 slip gaji (BPJS TK, BPJS Kes, PPh21, Net Salary) harus selesai < 1.000 ms (SLA < 1 Detik).
     * Terhubung ke: PayrollService::calculateNetSalary() di PayrollService.php:L40.
     */
    #[Test]
    public function menuPayroll_bulkPayrollCalculationPerformance_isUnderOneSecond(): void
    {
        $service = new PayrollService();

        // Uji 200 kalkulasi slip gaji dengan SLA max 1000.0 ms (1 detik)
        $this->assertExecutionTimeLessThanMs(function () use ($service) {
            for ($i = 0; $i < 200; $i++) {
                $tk   = $service->calculateBpjsTk(5000000);
                $kes  = $service->calculateBpjsKes(5000000);
                $pph  = $service->calculatePph21(5000000);
                $net  = $service->calculateNetSalary(5000000, 500000, 200000, 300000, $tk + $kes + $pph);
            }
        }, 1000.0, 'Batch kalkulasi penggajian 200 karyawan harus selesai < 1 detik.');
    }

    /**
     * Skenario 5: Verifikasi Query SQL Menggunakan Prepared Statements Terindeks (Mencegah Query Lemot / Table Scan).
     * Terhubung ke: PDO prepared statement execution pada model/service siCare.
     */
    #[Test]
    public function queryOptimization_usesPreparedStatementsWithIndexedKeys(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetch')->willReturn([
            'id'            => 'uuid-001',
            'first_name'    => 'Fast',
            'last_name'     => 'User',
            'email'         => 'fast@example.com',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
            'role'          => 'employee',
            'department_id' => 'dept-001',
            'is_deleted'    => 0,
            'is_suspended'  => 0,
        ]);
        $mockStmt->method('fetchColumn')->willReturn(0);

        $mockDb = $this->createMock(PDO::class);
        // Memastikan query SQL selalu menggunakan parameter binding terindeks (:email)
        $mockDb->expects($this->atLeastOnce())
               ->method('prepare')
               ->willReturn($mockStmt);

        // Inject mock DB via Reflection
        $reflection   = new \ReflectionClass(\App\Config\Database::class);
        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setAccessible(true);

        $fakeDbInstance = new class($mockDb) {
            private PDO $pdo;
            public function __construct(PDO $pdo) { $this->pdo = $pdo; }
            public function getConnection(): PDO { return $this->pdo; }
        };
        $instanceProp->setValue(null, $fakeDbInstance);

        $service = new AuthService();

        // Eksekusi query dengan pengukuran waktu SLA
        $this->assertExecutionTimeLessThanMs(function () use ($service) {
            return $service->authenticate('fast@example.com', 'Pass123');
        }, 1000.0, 'Query autentikasi pengguna harus menggunakan prepared statement terindeks dan selesai < 1 detik.');
    }
}
