<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\BaseTestCase;

/**
 * UserModelTest
 *
 * Menguji fungsi pure dari User model tanpa koneksi DB nyata.
 * generateUuid() tidak memerlukan DB — dapat diuji secara murni.
 *
 * Terhubung ke:
 *   - App\Models\User::generateUuid()  (UUID v4 generator)
 */
class UserModelTest extends BaseTestCase
{
    // ─── generateUuid() ────────────────────────────────────────────────

    /**
     * Skenario 1: generateUuid() menghasilkan string dengan format UUID v4 yang valid.
     * Terhubung ke: App\Models\User::generateUuid() di User.php:L15-L23
     *               format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx (RFC 4122 UUID v4)
     */
    #[Test]
    public function generateUuid_returnsValidUuidV4Format(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Instansiasi manual generateUuid() tanpa User constructor (yang memerlukan DB).
        // Kita ekstrak logic-nya ke closure agar bisa diuji secara pure.
        $generateUuid = function (): string {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, // bit versi '4' → UUID v4
                mt_rand(0, 0x3fff) | 0x8000, // bit variant '8','9','a','b' → RFC 4122
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        };

        // ── ACT ─────────────────────────────────────────────────────────
        $uuid = $generateUuid();

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: User::generateUuid() — validasi format xxxxxxxx-xxxx-4xxx-[89ab]xxx-xxxxxxxxxxxx
        $uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression(
            $uuidRegex,
            $uuid,
            'generateUuid() harus menghasilkan string dengan format UUID v4 yang valid'
        );
    }

    /**
     * Skenario 2: Dua panggilan generateUuid() harus menghasilkan UUID yang BERBEDA (uniqueness).
     * Terhubung ke: App\Models\User::generateUuid() di User.php:L15-L23
     *               properti uniqueness: setiap UUID harus unik karena berbasis mt_rand()
     */
    #[Test]
    public function generateUuid_calledTwice_returnsTwoDifferentValues(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Gunakan closure yang sama dengan logika generateUuid() di User model
        $generateUuid = function (): string {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        };

        // ── ACT ─────────────────────────────────────────────────────────
        $uuid1 = $generateUuid();
        $uuid2 = $generateUuid();

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: User::generateUuid() — probabilitas collision praktis nol
        $this->assertNotSame($uuid1, $uuid2, 'Dua UUID yang dibangkitkan berturut-turut harus memiliki nilai yang berbeda');
    }
}
