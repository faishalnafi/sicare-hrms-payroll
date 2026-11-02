<?php

namespace Tests\Unit\Services;

use App\Services\LeaveService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\BaseTestCase;

/**
 * LeaveServiceTest
 *
 * Menguji business logic LeaveService secara terisolasi dari database nyata.
 * Semua method yang diuji adalah pure computation (calculateLeaveDuration) tanpa DB.
 *
 * Terhubung ke:
 *   - App\Services\LeaveService::calculateLeaveDuration()  (kalkulasi hari kerja efektif)
 */
#[CoversClass(LeaveService::class)]
class LeaveServiceTest extends BaseTestCase
{
    /** @var LeaveService Instance service yang diuji */
    private LeaveService $service;

    /**
     * setUp() — inisialisasi LeaveService sebelum setiap test method.
     * Terhubung ke: App\Services\LeaveService (tidak ada dependency eksternal untuk calculateLeaveDuration).
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveService();
    }

    // ─── calculateLeaveDuration() ──────────────────────────────────────

    /**
     * Skenario 1: Tanggal mulai lebih besar dari tanggal selesai → return 0 (guard clause).
     * Terhubung ke: LeaveService::calculateLeaveDuration() di LeaveService.php:L19
     *               baris: if ($start > $end) return 0;
     */
    #[Test]
    public function calculateLeaveDuration_startAfterEnd_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // start lebih besar dari end — kondisi tidak valid
        $startDate = '2026-08-10';
        $endDate   = '2026-08-05';

        // ── ACT ─────────────────────────────────────────────────────────
        $duration = $this->service->calculateLeaveDuration($startDate, $endDate);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: LeaveService::calculateLeaveDuration() — guard clause return 0
        $this->assertSame(0, $duration, 'Tanggal mulai setelah selesai harus menghasilkan durasi 0');
    }

    /**
     * Skenario 2: Tanggal mulai sama dengan tanggal selesai di hari kerja (Selasa) → durasi 1 hari.
     * Terhubung ke: LeaveService::calculateLeaveDuration() di LeaveService.php:L26-L33
     *               baris: for ($curr = $start; $curr <= $end; ...)
     */
    #[Test]
    public function calculateLeaveDuration_sameDateBothEnds_returnsOne(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // 4 Agustus 2026 = Selasa (hari kerja biasa, bukan weekend)
        $date = '2026-08-04';

        // ── ACT ─────────────────────────────────────────────────────────
        $duration = $this->service->calculateLeaveDuration($date, $date);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: LeaveService::calculateLeaveDuration() — satu hari kerja terhitung
        $this->assertSame(1, $duration, 'Cuti satu hari di hari kerja harus menghasilkan durasi 1');
    }

    /**
     * Skenario 3: Rentang Senin–Minggu (7 hari) → 5 hari kerja efektif (Sabtu & Minggu dieksklusi).
     * Terhubung ke: LeaveService::calculateLeaveDuration() di LeaveService.php:L30
     *               baris: if (!in_array($dayName, $weeklyHolidays) && ...)
     */
    #[Test]
    public function calculateLeaveDuration_oneWeekRange_returnsFiveWorkdays(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Senin 3 Agustus 2026 → Minggu 9 Agustus 2026 = 7 hari kalender
        $startDate = '2026-08-03'; // Senin
        $endDate   = '2026-08-09'; // Minggu

        // ── ACT ─────────────────────────────────────────────────────────
        // Sabtu (8 Agt) dan Minggu (9 Agt) otomatis dieksklusi oleh weeklyHolidays default
        $duration = $this->service->calculateLeaveDuration($startDate, $endDate);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: LeaveService::calculateLeaveDuration() — ['Sat','Sun'] tereksklusi
        $this->assertSame(5, $duration, 'Rentang Senin–Minggu harus menghasilkan 5 hari kerja efektif');
    }

    /**
     * Skenario 4: Rentang 3 hari di mana hari pertama adalah libur perusahaan → durasi 2 hari.
     * Terhubung ke: LeaveService::calculateLeaveDuration() di LeaveService.php:L30
     *               baris: && !in_array($currDateStr, $companyHolidays)
     */
    #[Test]
    public function calculateLeaveDuration_withCompanyHoliday_excludesHoliday(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // 17 Agustus 2026 = Senin (Hari Kemerdekaan, libur perusahaan)
        $startDate       = '2026-08-17'; // Senin — libur nasional
        $endDate         = '2026-08-19'; // Rabu
        $companyHolidays = ['2026-08-17']; // 17 Agustus dikecualikan

        // ── ACT ─────────────────────────────────────────────────────────
        // Senin(libur) → Selasa → Rabu = 2 hari kerja efektif
        $duration = $this->service->calculateLeaveDuration($startDate, $endDate, $companyHolidays);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: LeaveService::calculateLeaveDuration() — companyHolidays dikecualikan
        $this->assertSame(2, $duration, 'Hari libur perusahaan harus dieksklusi dari hitungan durasi');
    }

    /**
     * Skenario 5: Custom weekly holidays (Jumat + Sabtu) → dari Senin–Jumat hanya 4 hari kerja.
     * Terhubung ke: LeaveService::calculateLeaveDuration() di LeaveService.php:L30
     *               parameter: array $weeklyHolidays — bisa di-override dari default ['Sat','Sun']
     */
    #[Test]
    public function calculateLeaveDuration_withCustomWeeklyHolidays_skipsCorrectDays(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Senin 3 Agt — Jumat 7 Agt 2026 (5 hari kalender)
        $startDate      = '2026-08-03'; // Senin
        $endDate        = '2026-08-07'; // Jumat
        // Override: Jumat dan Sabtu menjadi hari libur mingguan (misal kantor tutup Jumat)
        $weeklyHolidays = ['Fri', 'Sat'];

        // ── ACT ─────────────────────────────────────────────────────────
        // Senin Selasa Rabu Kamis = 4 hari; Jumat = libur → total 4 hari kerja
        $duration = $this->service->calculateLeaveDuration($startDate, $endDate, [], $weeklyHolidays);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: LeaveService::calculateLeaveDuration() — custom weeklyHolidays dihormati
        $this->assertSame(4, $duration, 'Custom weekly holidays Jumat-Sabtu harus dikecualikan');
    }
}
