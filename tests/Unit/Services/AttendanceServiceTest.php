<?php

namespace Tests\Unit\Services;

use App\Services\AttendanceService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\BaseTestCase;

/**
 * AttendanceServiceTest
 *
 * Menguji seluruh pure function di AttendanceService tanpa koneksi database.
 * Semua method yang diuji bersifat stateless (perhitungan matematika GPS dan jam masuk).
 *
 * Terhubung ke:
 *   - App\Services\AttendanceService::calculateDistance()    (Formula Haversine jarak GPS)
 *   - App\Services\AttendanceService::isWithinGeofence()     (Validasi geofence radius)
 *   - App\Services\AttendanceService::evaluateClockInStatus() (Evaluasi status tepat waktu / terlambat)
 */
#[CoversClass(AttendanceService::class)]
class AttendanceServiceTest extends BaseTestCase
{
    /** @var AttendanceService Instance service yang diuji */
    private AttendanceService $service;

    /**
     * setUp() — inisialisasi instance service sebelum setiap test method.
     * Terhubung ke: App\Services\AttendanceService
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceService();
    }

    // ─── 1. calculateDistance() ──────────────────────────────────────────

    /**
     * Skenario 1: Dua koordinat identik → jarak harus 0.0 meter.
     * Terhubung ke: AttendanceService::calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2)
     *               di AttendanceService.php:L19-L32 (Formula Haversine).
     */
    #[Test]
    public function calculateDistance_withIdenticalCoordinates_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Terhubung ke: Posisi kantor default di Jakarta (-6.2297, 106.8164)
        $lat = -6.2297;
        $lng = 106.8164;

        // ── ACT ─────────────────────────────────────────────────────────
        // Hitung jarak antara dua koordinat yang persis sama
        $distance = $this->service->calculateDistance($lat, $lng, $lat, $lng);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AttendanceService::calculateDistance() — kembalikan float 0.0
        $this->assertEqualsWithDelta(0.0, $distance, 0.001, 'Jarak antara koordinat identik harus 0 meter');
    }

    /**
     * Skenario 2: Dua koordinat dengan jarak diketahui ~122m → hasil sesuai toleransi.
     * Terhubung ke: AttendanceService::calculateDistance() di AttendanceService.php:L19-L32.
     */
    #[Test]
    public function calculateDistance_withKnownPoints_returnsApproximateDistance(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Terhubung ke: Koordinat kantor (-6.2297, 106.8164) dan posisi user ~122m ke utara (-6.2286, 106.8164)
        $officeLat = -6.2297;
        $officeLng = 106.8164;
        $userLat   = -6.2286;
        $userLng   = 106.8164;

        // ── ACT ─────────────────────────────────────────────────────────
        $distance = $this->service->calculateDistance($userLat, $userLng, $officeLat, $officeLng);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AttendanceService::calculateDistance() — ekspektasi jarak antara 100m dan 150m
        $this->assertGreaterThan(100.0, $distance, 'Jarak harus lebih dari 100 meter');
        $this->assertLessThan(150.0, $distance, 'Jarak harus kurang dari 150 meter');
    }

    // ─── 2. isWithinGeofence() ───────────────────────────────────────────

    /**
     * Skenario 3: User berada tepat di titik kantor → berada di dalam geofence (true).
     * Terhubung ke: AttendanceService::isWithinGeofence(float $userLat, float $userLng, ...)
     *               di AttendanceService.php:L37-L40 ($distance <= $maxRadiusMeters).
     */
    #[Test]
    public function isWithinGeofence_whenUserAtOffice_returnsTrue(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Terhubung ke: Radius kantor default 150 meter
        $officeLat = -6.2297;
        $officeLng = 106.8164;
        $maxRadius = 150.0;

        // ── ACT ─────────────────────────────────────────────────────────
        // User di koordinat kantor yang sama
        $result = $this->service->isWithinGeofence($officeLat, $officeLng, $officeLat, $officeLng, $maxRadius);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AttendanceService::isWithinGeofence() — return true
        $this->assertTrue($result, 'User pada titik kantor harus berada di dalam geofence');
    }

    /**
     * Skenario 4: User berjarak ~550m dari kantor (radius 150m) → di luar geofence (false).
     * Terhubung ke: AttendanceService::isWithinGeofence() di AttendanceService.php:L37-L40.
     */
    #[Test]
    public function isWithinGeofence_whenUserFarFromOffice_returnsFalse(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Terhubung ke: Posisi user ~550m dari kantor
        $officeLat = -6.2297;
        $officeLng = 106.8164;
        $userLat   = -6.2248;
        $userLng   = 106.8164;
        $maxRadius = 150.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $this->service->isWithinGeofence($userLat, $userLng, $officeLat, $officeLng, $maxRadius);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AttendanceService::isWithinGeofence() — return false
        $this->assertFalse($result, 'User berjarak 550m harus berada di luar geofence');
    }

    /**
     * Skenario 5: User berada tepat di batas geofence → di dalam geofence (true, operator <=).
     * Terhubung ke: AttendanceService::isWithinGeofence() di AttendanceService.php:L39 ($distance <= $maxRadiusMeters).
     */
    #[Test]
    public function isWithinGeofence_whenUserAtExactBoundary_returnsTrue(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $officeLat = 0.0;
        $officeLng = 0.0;
        $maxRadius = 150.0;
        // ~150m ke utara pada khatulistiwa (1 deg lat ≈ 111.320m → 150m ≈ 0.001348 deg)
        $userLat   = 0.001348;
        $userLng   = 0.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $this->service->isWithinGeofence($userLat, $userLng, $officeLat, $officeLng, $maxRadius);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Operator <= bersifat inklusif untuk batas geofence
        $this->assertTrue($result, 'User pada batas radius harus dianggap berada di dalam geofence');
    }

    // ─── 3. evaluateClockInStatus() ──────────────────────────────────────

    /**
     * Skenario 6: Clock-in 07:50:00 (jam kerja 08:00:00) → 'tepat waktu'.
     * Terhubung ke: AttendanceService::evaluateClockInStatus(string $clockInTime, ...)
     *               di AttendanceService.php:L45-L54.
     */
    #[Test]
    public function evaluateClockInStatus_beforeWorkStart_returnsTepat(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Clock-in 10 menit sebelum jam masuk
        $clockIn     = date('Y-m-d') . ' 07:50:00';
        $workStart   = '08:00:00';
        $gracePeriod = 15; // menit

        // ── ACT ─────────────────────────────────────────────────────────
        $status = $this->service->evaluateClockInStatus($clockIn, $workStart, $gracePeriod);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AttendanceService::evaluateClockInStatus() — return 'tepat waktu'
        $this->assertSame('tepat waktu', $status);
    }

    /**
     * Skenario 7: Clock-in tepat 08:15:00 (jam kerja 08:00:00 + grace period 15 menit) → 'tepat waktu'.
     * Terhubung ke: AttendanceService::evaluateClockInStatus() di AttendanceService.php:L50 ($clockInTs <= $lateThresholdTs).
     */
    #[Test]
    public function evaluateClockInStatus_atGracePeriodBoundary_returnsTepat(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Clock-in tepat di batas grace period (08:00 + 15 menit = 08:15)
        $clockIn     = date('Y-m-d') . ' 08:15:00';
        $workStart   = '08:00:00';
        $gracePeriod = 15;

        // ── ACT ─────────────────────────────────────────────────────────
        $status = $this->service->evaluateClockInStatus($clockIn, $workStart, $gracePeriod);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Operator <= di kode produksi memastikan batas grace period bersifat inklusif
        $this->assertSame('tepat waktu', $status);
    }

    /**
     * Skenario 8: Clock-in 08:16:00 (1 menit setelah grace period habis) → 'terlambat'.
     * Terhubung ke: AttendanceService::evaluateClockInStatus() di AttendanceService.php:L53 (return 'terlambat').
     */
    #[Test]
    public function evaluateClockInStatus_oneMinuteAfterGrace_returnsTerlambat(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Clock-in 1 menit lewat dari grace period (08:16)
        $clockIn     = date('Y-m-d') . ' 08:16:00';
        $workStart   = '08:00:00';
        $gracePeriod = 15;

        // ── ACT ─────────────────────────────────────────────────────────
        $status = $this->service->evaluateClockInStatus($clockIn, $workStart, $gracePeriod);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AttendanceService::evaluateClockInStatus() — return 'terlambat'
        $this->assertSame('terlambat', $status);
    }

    /**
     * Skenario 9: Clock-in siang hari (11:00:00) → 'terlambat'.
     * Terhubung ke: AttendanceService::evaluateClockInStatus() di AttendanceService.php:L45-L54.
     */
    #[Test]
    public function evaluateClockInStatus_atNoon_returnsTerlambat(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Clock-in jam 11 siang
        $clockIn     = date('Y-m-d') . ' 11:00:00';
        $workStart   = '08:00:00';
        $gracePeriod = 15;

        // ── ACT ─────────────────────────────────────────────────────────
        $status = $this->service->evaluateClockInStatus($clockIn, $workStart, $gracePeriod);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame('terlambat', $status);
    }
}
