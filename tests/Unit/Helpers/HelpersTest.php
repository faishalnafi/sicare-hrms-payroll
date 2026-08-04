<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\BaseTestCase;

/**
 * HelpersTest
 *
 * Menguji seluruh fungsi global (pure functions) yang ada di app/helpers.php
 * secara terisolasi tanpa koneksi database nyata.
 *
 * Terhubung ke:
 *   - helpers.php::e()                         (XSS escaping)
 *   - helpers.php::resolveProfilePicture()      (Profile picture cascade logic)
 *   - helpers.php::validate_and_format_phone()  (Phone number validator & formatter)
 */
class HelpersTest extends BaseTestCase
{
    /**
     * setUp() — muat helpers.php sebelum setiap test method.
     * Terhubung ke: app/helpers.php (global function definitions).
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Pastikan semua global helpers sudah ter-load
        require_once dirname(__DIR__, 3) . '/app/helpers.php';
    }

    // ─── e() — XSS Escaping ────────────────────────────────────────────

    /**
     * Skenario 1: String dengan karakter HTML berbahaya → di-escape menjadi entitas HTML aman.
     * Terhubung ke: helpers.php::e() L29-L31
     *               formula: htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8')
     */
    #[Test]
    public function e_withHtmlSpecialChars_returnsEscapedString(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Simulasi XSS payload yang diinput user
        $dangerous = '<script>alert("XSS")</script>';

        // ── ACT ─────────────────────────────────────────────────────────
        $safe = e($dangerous);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: helpers::e() — semua karakter < > " & harus di-escape
        $this->assertStringNotContainsString('<script>', $safe, 'Tag <script> tidak boleh lolos dari escaping');
        $this->assertStringContainsString('&lt;script&gt;', $safe, 'Tag <script> harus di-escape jadi &lt;script&gt;');
    }

    /**
     * Skenario 2: null input → harus menghasilkan string kosong tanpa error (null safe).
     * Terhubung ke: helpers.php::e() L29-L31
     *               baris: htmlspecialchars($value ?? '', ...)
     */
    #[Test]
    public function e_withNullInput_returnsEmptyString(): void
    {
        // ── ACT ─────────────────────────────────────────────────────────
        // Null di-handle oleh null-coalescing operator $value ?? ''
        $result = e(null);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: helpers::e() — null coalescing ke '' tanpa throw
        $this->assertSame('', $result, 'Fungsi e() dengan null harus mengembalikan string kosong');
    }

    // ─── resolveProfilePicture() ───────────────────────────────────────

    /**
     * Skenario 3: googlePic tersedia (tidak empty) → langsung dikembalikan tanpa network call.
     * Terhubung ke: helpers.php::resolveProfilePicture() L151-L154
     *               baris: if (!empty($googlePic)) return $googlePic;
     */
    #[Test]
    public function resolveProfilePicture_withGooglePic_returnsGooglePicDirectly(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $email     = 'test@example.com';
        $googlePic = 'https://lh3.googleusercontent.com/a/user-photo';

        // ── ACT ─────────────────────────────────────────────────────────
        // Jika googlePic di-pass, tidak ada network request ke Gravatar
        $result = resolveProfilePicture($email, $googlePic);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: helpers::resolveProfilePicture() — shortcircuit return googlePic
        $this->assertSame($googlePic, $result, 'Google profile picture harus langsung dikembalikan tanpa network call');
    }

    // ─── validate_and_format_phone() ──────────────────────────────────

    /**
     * Skenario 4: Nomor 08xx → dikonversi ke 628xx (format internasional).
     * Terhubung ke: helpers.php::validate_and_format_phone() L205-L207
     *               baris: if (str_starts_with($phone, '08')) return '628' . substr($phone, 2);
     */
    #[Test]
    public function validateAndFormatPhone_withLeadingZero_convertsToInternational(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $rawPhone = '081234567890'; // format lokal Indonesia (12 digit)
        $errorMsg = '';

        // ── ACT ─────────────────────────────────────────────────────────
        $formatted = validate_and_format_phone($rawPhone, $errorMsg);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: helpers::validate_and_format_phone() — '08' prefix dikonversi ke '628'
        $this->assertSame('6281234567890', $formatted, 'Nomor 08xx harus dikonversi ke format 628xx');
        $this->assertEmpty($errorMsg, 'Tidak boleh ada pesan error untuk nomor valid');
    }

    /**
     * Skenario 5: Nomor dengan karakter non-digit → return false + pesan error.
     * Terhubung ke: helpers.php::validate_and_format_phone() L194-L197
     *               baris: if (!preg_match('/^[0-9]+$/', $phone)) ...
     */
    #[Test]
    public function validateAndFormatPhone_withNonDigitChars_returnsFalseWithError(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $rawPhone = '0812 3456 7890'; // ada spasi di dalam nomor
        $errorMsg = '';

        // ── ACT ─────────────────────────────────────────────────────────
        $result = validate_and_format_phone($rawPhone, $errorMsg);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: helpers::validate_and_format_phone() — spasi tidak lolos regex /^[0-9]+$/
        $this->assertFalse($result, 'Nomor dengan spasi harus menghasilkan false');
        $this->assertNotEmpty($errorMsg, 'Harus ada pesan error untuk nomor dengan karakter non-digit');
    }

    /**
     * Skenario 6: Nomor terlalu pendek (kurang dari 10 digit) → return false + pesan error.
     * Terhubung ke: helpers.php::validate_and_format_phone() L199-L202
     *               baris: if ($length < 10 || $length > 14)
     */
    #[Test]
    public function validateAndFormatPhone_withTooShortNumber_returnsFalseWithError(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $rawPhone = '0812345'; // hanya 7 digit — di bawah minimum 10
        $errorMsg = '';

        // ── ACT ─────────────────────────────────────────────────────────
        $result = validate_and_format_phone($rawPhone, $errorMsg);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: helpers::validate_and_format_phone() — panjang < 10 ditolak
        $this->assertFalse($result, 'Nomor kurang dari 10 digit harus ditolak');
        $this->assertStringContainsString('10', $errorMsg, 'Pesan error harus menyebut batas minimum 10 digit');
    }
}
