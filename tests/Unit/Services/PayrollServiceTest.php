<?php

namespace Tests\Unit\Services;

use App\Services\PayrollService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\BaseTestCase;

/**
 * PayrollServiceTest
 *
 * Menguji seluruh kalkulasi penggajian dan potongan di PayrollService.
 * Semua method yang diuji adalah pure computation (tidak ada DB call langsung).
 *
 * Terhubung ke:
 *   - App\Services\PayrollService::calculateBpjsTk()      (Potongan BPJS Ketenagakerjaan — default 2%)
 *   - App\Services\PayrollService::calculateBpjsKes()     (Potongan BPJS Kesehatan — default 1%)
 *   - App\Services\PayrollService::calculatePph21()       (Potongan PPh21 — default 2.5%)
 *   - App\Services\PayrollService::calculateNetSalary()   (Gaji bersih = gross - total deductions)
 */
#[CoversClass(PayrollService::class)]
class PayrollServiceTest extends BaseTestCase
{
    /** @var PayrollService Instance service yang diuji */
    private PayrollService $service;

    /**
     * setUp() — inisialisasi PayrollService sebelum setiap test method.
     * Terhubung ke: App\Services\PayrollService (tidak ada dependency eksternal untuk pure calculations).
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PayrollService();
    }

    // ─── calculateBpjsTk() ─────────────────────────────────────────────

    /**
     * Skenario 1: Gaji Rp 5.000.000, rate default 2% → potongan BPJS TK = Rp 100.000.
     * Terhubung ke: PayrollService::calculateBpjsTk() di PayrollService.php:L19
     *               formula: round(($baseSalary * $percentage) / 100, 2)
     */
    #[Test]
    public function calculateBpjsTk_withStandardSalary_returnsCorrectDeduction(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary  = 5_000_000.0;
        $defaultRate = 2.0; // persen — sesuai default parameter PayrollService

        // ── ACT ─────────────────────────────────────────────────────────
        $deduction = $this->service->calculateBpjsTk($baseSalary, $defaultRate);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: PayrollService::calculateBpjsTk() — 5.000.000 * 2% = 100.000
        $this->assertSame(100_000.0, $deduction);
    }

    /**
     * Skenario 2: Gaji Rp 0 → potongan BPJS TK harus Rp 0 (tidak boleh minus atau NaN).
     * Terhubung ke: PayrollService::calculateBpjsTk() di PayrollService.php:L19
     *               edge case: $baseSalary = 0.0
     */
    #[Test]
    public function calculateBpjsTk_withZeroSalary_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary = 0.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $deduction = $this->service->calculateBpjsTk($baseSalary);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: PayrollService::calculateBpjsTk() — 0 * 2% = 0
        $this->assertSame(0.0, $deduction, 'Gaji 0 harus menghasilkan potongan BPJS TK 0');
    }

    // ─── calculateBpjsKes() dengan Data Provider ──────────────────────

    /**
     * Skenario 3: Berbagai level gaji dengan rate 1% → BPJS Kes harus selalu akurat.
     * Terhubung ke: PayrollService::calculateBpjsKes() di PayrollService.php:L26
     *               formula: round(($baseSalary * $percentage) / 100, 2)
     */
    #[Test]
    #[DataProvider('provideStandardSalaryDeductionCases')]
    public function calculateBpjsKes_variousSalaries_returnsCorrectOnePercent(
        float $salary,
        float $pct,
        float $expected
    ): void {
        // ── ACT ─────────────────────────────────────────────────────────
        // Panggil calculateBpjsKes dengan data dari provider
        $result = $this->service->calculateBpjsKes($salary, $pct);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: PayrollService::calculateBpjsKes() — toleransi 0.01 untuk floating point
        $this->assertEqualsWithDelta($expected, $result, 0.01);
    }

    /**
     * Data provider untuk test BPJS Kes dengan 3 level gaji berbeda.
     * Terhubung ke: PayrollService::calculateBpjsKes().
     *
     * @return array<string, array{float, float, float}>
     */
    public static function provideStandardSalaryDeductionCases(): array
    {
        return [
            // [gaji_pokok, persentase, potongan_yang_diharapkan]
            'gaji minimum UMP'   => [2_000_000.0, 1.0, 20_000.0],
            'gaji menengah'      => [5_000_000.0, 1.0, 50_000.0],
            'gaji level senior'  => [20_000_000.0, 1.0, 200_000.0],
        ];
    }

    // ─── calculatePph21() ──────────────────────────────────────────────

    /**
     * Skenario 4: PKP Rp 5.000.000, rate default 2.5% → PPh21 = Rp 125.000.
     * Terhubung ke: PayrollService::calculatePph21() di PayrollService.php:L33
     *               formula: round(($taxableIncome * $percentage) / 100, 2)
     */
    #[Test]
    public function calculatePph21_withTaxableIncome_returnsCorrectTax(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $taxableIncome = 5_000_000.0;
        $defaultRate   = 2.5; // persen — sesuai default parameter PayrollService

        // ── ACT ─────────────────────────────────────────────────────────
        $tax = $this->service->calculatePph21($taxableIncome, $defaultRate);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: PayrollService::calculatePph21() — 5.000.000 * 2.5% = 125.000
        $this->assertSame(125_000.0, $tax);
    }

    /**
     * Skenario 5: PKP Rp 0 → PPh21 harus nol (tidak boleh minus atau NaN).
     * Terhubung ke: PayrollService::calculatePph21() di PayrollService.php:L33
     *               edge case: $taxableIncome = 0.0
     */
    #[Test]
    public function calculatePph21_withZeroIncome_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $taxableIncome = 0.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $tax = $this->service->calculatePph21($taxableIncome);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: PayrollService::calculatePph21() — 0 * 2.5% = 0
        $this->assertSame(0.0, $tax, 'PKP Rp 0 harus menghasilkan PPh21 Rp 0');
    }

    // ─── calculateNetSalary() ──────────────────────────────────────────

    /**
     * Skenario 6: Semua komponen gaji positif → net = gross - deductions.
     * Terhubung ke: PayrollService::calculateNetSalary() di PayrollService.php:L40
     *               baris: $gross = $baseSalary + $allowances + $overtime + $bonus
     *               baris: return max(0.0, round($gross - $deductions, 2));
     */
    #[Test]
    public function calculateNetSalary_withAllPositiveComponents_returnsCorrectNet(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary = 5_000_000.0;
        $allowances = 500_000.0;   // tunjangan transport, makan, dll
        $overtime   = 200_000.0;   // upah lembur
        $bonus      = 300_000.0;   // bonus kinerja
        $deductions = 275_000.0;   // total: BPJS TK 100k + BPJS Kes 50k + PPh21 125k

        // ── ACT ─────────────────────────────────────────────────────────
        $net = $this->service->calculateNetSalary($baseSalary, $allowances, $overtime, $bonus, $deductions);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Gross: 5.000.000 + 500.000 + 200.000 + 300.000 = 6.000.000
        // Net:   6.000.000 - 275.000 = 5.725.000
        $this->assertSame(5_725_000.0, $net, 'Net salary harus sesuai gross dikurangi deductions');
    }

    /**
     * Skenario 7: Total deductions melebihi gross → gaji bersih harus minimal Rp 0 (tidak negatif).
     * Terhubung ke: PayrollService::calculateNetSalary() di PayrollService.php:L42
     *               baris: return max(0.0, round($gross - $deductions, 2));
     */
    #[Test]
    public function calculateNetSalary_whenDeductionsExceedGross_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary = 1_000_000.0;
        $allowances = 0.0;
        $overtime   = 0.0;
        $bonus      = 0.0;
        $deductions = 9_999_999.0; // potongan jauh melampaui total gross

        // ── ACT ─────────────────────────────────────────────────────────
        $net = $this->service->calculateNetSalary($baseSalary, $allowances, $overtime, $bonus, $deductions);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: PayrollService::calculateNetSalary() — max(0.0, ...) memastikan tidak negatif
        $this->assertSame(0.0, $net, 'Gaji bersih tidak boleh bernilai negatif meskipun deductions > gross');
    }

    /**
     * Skenario 8: Tidak ada komponen tambahan dan tidak ada potongan → net = base salary.
     * Terhubung ke: PayrollService::calculateNetSalary() di PayrollService.php:L40-L42
     *               baseline test tanpa tambahan apapun.
     */
    #[Test]
    public function calculateNetSalary_withNoExtrasAndNoDeductions_returnsBaseSalary(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary = 3_500_000.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $net = $this->service->calculateNetSalary($baseSalary, 0.0, 0.0, 0.0, 0.0);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: PayrollService::calculateNetSalary() — 3.500.000 + 0 - 0 = 3.500.000
        $this->assertSame(3_500_000.0, $net, 'Net salary tanpa komponen tambahan harus sama dengan base salary');
    }

    /**
     * Skenario 9: Hanya bonus yang ada (tanpa allowance & overtime), ada deduction → net = base + bonus - deductions.
     * Terhubung ke: PayrollService::calculateNetSalary() di PayrollService.php:L40
     *               edge case: allowances=0 dan overtime=0, hanya bonus yang aktif.
     */
    #[Test]
    public function calculateNetSalary_withOnlyBonus_returnsBasePlusMinusDeductions(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary = 4_000_000.0;
        $allowances = 0.0;
        $overtime   = 0.0;
        $bonus      = 1_000_000.0; // bonus kinerja akhir tahun
        $deductions = 150_000.0;   // total BPJS saja

        // ── ACT ─────────────────────────────────────────────────────────
        $net = $this->service->calculateNetSalary($baseSalary, $allowances, $overtime, $bonus, $deductions);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Gross: 4.000.000 + 0 + 0 + 1.000.000 = 5.000.000
        // Net:   5.000.000 - 150.000 = 4.850.000
        $this->assertSame(4_850_000.0, $net, 'Net salary dengan bonus saja harus dihitung benar');
    }
}
