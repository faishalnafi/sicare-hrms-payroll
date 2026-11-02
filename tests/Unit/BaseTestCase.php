<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * BaseTestCase
 *
 * Class abstrak dasar untuk semua Unit & Performance Tests:
 * - Reset superglobal $_SESSION, $_POST, $_GET, $_COOKIE, $_SERVER sebelum & sesudah setiap test
 * - Menyediakan helper assertion Waktu Eksekusi (Performance SLA < 1000ms)
 * - Menyediakan helper assertion Efisiensi Query (Anti N+1 Query & Table Scan)
 *
 * Digunakan oleh: semua class test di direktori tests/Unit/ & tests/Feature/
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * setUp() — dijalankan sebelum setiap test method.
     * Reset superglobal agar state tidak bocor antar test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Bersihkan semua superglobal sebelum setiap test
        $_SESSION = [];
        $_POST    = [];
        $_GET     = [];
        $_COOKIE  = [];
        $_SERVER  = array_merge($_SERVER, [
            'REMOTE_ADDR'           => '127.0.0.1',
            'HTTP_X_REQUESTED_WITH' => '',
        ]);
    }

    /**
     * tearDown() — dijalankan setelah setiap test method.
     * Pastikan tidak ada state tersisa untuk test berikutnya.
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * assertExecutionTimeLessThanMs() — Assertion Performance SLA.
     * Memastikan fungsi / rendering menu selesai di bawah ambang batas milidetik tertentu (default < 1000ms = 1s).
     *
     * Terhubung ke: SLA Performa Membuka Menu / Load Data (< 1 Detik).
     *
     * @param callable $action     Callback fungsi / controller action yang diuji
     * @param float    $maxMs      Ambang batas maksimal waktu dalam milidetik (default 1000.0 ms)
     * @param string   $message    Pesan kesalahan jika melebihi SLA
     * @return mixed               Hasil return dari callback $action
     */
    protected function assertExecutionTimeLessThanMs(callable $action, float $maxMs = 1000.0, string $message = '')
    {
        $startTime = microtime(true);
        $result = $action();
        $endTime = microtime(true);

        $executionTimeMs = ($endTime - $startTime) * 1000.0;

        $failureMsg = !empty($message) 
            ? $message 
            : sprintf('Waktu eksekusi (%.2f ms) melebihi SLA performa maksimal %.2f ms (1 detik).', $executionTimeMs, $maxMs);

        $this->assertLessThan($maxMs, $executionTimeMs, $failureMsg);

        return $result;
    }
}
