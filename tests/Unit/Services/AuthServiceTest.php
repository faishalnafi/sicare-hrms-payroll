<?php

namespace Tests\Unit\Services;

use App\Services\AuthService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PDO;
use PDOStatement;
use Tests\Unit\BaseTestCase;

/**
 * AuthServiceTest
 *
 * Menguji business logic AuthService secara terisolasi dari database nyata.
 * Menggunakan teknik Reflection Injection untuk me-replace singleton Database::$instance.
 *
 * Terhubung ke:
 *   - App\Services\AuthService::authenticate()               (Email & password auth, throttling, soft-delete)
 *   - App\Services\AuthService::processRememberMe()           (Verifikasi cookie token)
 *   - App\Services\AuthService::validateProfileResetToken()  (Validasi token reset sandi)
 *   - App\Models\User::findByEmail()                         (Dipanggil oleh AuthService::authenticate)
 *   - App\Config\Database::getInstance()                     (Singleton PDO connection)
 */
#[CoversClass(AuthService::class)]
class AuthServiceTest extends BaseTestCase
{
    /**
     * makeServiceWithMockDb() — inject PDO mock ke singleton Database via Reflection API.
     *
     * Terhubung ke: App\Config\Database::$instance (private static property di Database.php)
     *
     * @param PDO $mockDb Instance PDO mock yang akan diinjeksikan saat testing
     * @return AuthService Instance AuthService fresh yang terhubung ke PDO mock
     */
    private function makeServiceWithMockDb(PDO $mockDb): AuthService
    {
        // ── ARRANGE MOCK SINGLETON ───────────────────────────────────────
        // Akses private static property Database::$instance via Reflection
        $reflection   = new \ReflectionClass(\App\Config\Database::class);
        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setAccessible(true); // bypass private visibility modifier

        // Buat anonymous class wrapper yang meniru interface Database::getConnection()
        $fakeDbInstance = new class($mockDb) {
            private PDO $pdo;
            public function __construct(PDO $pdo) { $this->pdo = $pdo; }
            /** Meniru Database::getConnection() — mengembalikan PDO mock */
            public function getConnection(): PDO { return $this->pdo; }
        };

        // Ganti nilai static singleton dengan fake instance
        $instanceProp->setValue(null, $fakeDbInstance);

        return new AuthService();
    }

    /**
     * Skenario 1: Email kosong dikirim → harus gagal langsung tanpa menyentuh DB.
     * Terhubung ke: AuthService::authenticate(string $email, string $password, ...)
     *               baris: if (empty($email) || empty($password)) return ['success' => false, ...]
     */
    #[Test]
    public function authenticate_withEmptyEmail_returnsFailureWithoutDbCall(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Terhubung ke: App\Services\AuthService
        $service  = new AuthService();
        $email    = '';
        $password = 'password123';

        // ── ACT ─────────────────────────────────────────────────────────
        // Panggil method authenticate dengan email kosong
        $result = $service->authenticate($email, $password);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AuthService::authenticate() — return pesan error validasi input
        $this->assertIsArray($result, 'Hasil autentikasi harus berupa array');
        $this->assertFalse($result['success'], 'Status success harus false jika email kosong');
        $this->assertSame('Email dan kata sandi wajib diisi.', $result['message']);
    }

    /**
     * Skenario 2: Password kosong dikirim → harus gagal langsung tanpa query DB.
     * Terhubung ke: AuthService::authenticate(string $email, string $password, ...)
     *               baris: if (empty($email) || empty($password)) return ['success' => false, ...]
     */
    #[Test]
    public function authenticate_withEmptyPassword_returnsFailure(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Terhubung ke: App\Services\AuthService
        $service  = new AuthService();
        $email    = 'user@example.com';
        $password = '';

        // ── ACT ─────────────────────────────────────────────────────────
        // Panggil method authenticate dengan password kosong
        $result = $service->authenticate($email, $password);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AuthService::authenticate() — return pesan error validasi input
        $this->assertIsArray($result, 'Hasil autentikasi harus berupa array');
        $this->assertFalse($result['success'], 'Status success harus false jika password kosong');
        $this->assertSame('Email dan kata sandi wajib diisi.', $result['message']);
    }

    /**
     * Skenario 3: Akun terdaftar tetapi flag is_deleted = 1 → ditolak dengan pesan nonaktif.
     * Terhubung ke: AuthService::authenticate()
     *               baris: if (isset($user['is_deleted']) && (int)$user['is_deleted'] === 1)
     */
    #[Test]
    public function authenticate_withSoftDeletedAccount_returnsFailure(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Terhubung ke: App\Models\User::findByEmail() dan PDOStatement
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetch')->willReturn([
            'id'            => 'user-uuid-001',
            'first_name'    => 'Deleted',
            'last_name'     => 'User',
            'email'         => 'deleted@example.com',
            'password_hash' => password_hash('Secret123', PASSWORD_BCRYPT),
            'role'          => 'employee',
            'is_deleted'    => 1, // User ditandai soft-deleted (dinonaktifkan)
            'is_suspended'  => 0,
        ]);

        // Mock PDO yang mengembalikan $mockStmt saat prepare() dipanggil
        $mockDb = $this->createMock(PDO::class);
        $mockDb->method('prepare')->willReturn($mockStmt);

        // Inject mock DB ke singleton via Reflection
        $service = $this->makeServiceWithMockDb($mockDb);

        // ── ACT ─────────────────────────────────────────────────────────
        // Panggil authenticate untuk email akun yang dinonaktifkan
        $result = $service->authenticate('deleted@example.com', 'Secret123');

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AuthService::authenticate() — return pesan akun dinonaktifkan
        $this->assertIsArray($result, 'Hasil harus berupa array');
        $this->assertFalse($result['success'], 'Status success harus false untuk akun deleted');
        $this->assertStringContainsString('dinonaktifkan', $result['message']);
    }

    /**
     * Skenario 4: Token reset sandi kosong dikirim → langsung mengembalikan null.
     * Terhubung ke: AuthService::validateProfileResetToken(string $token)
     *               baris: if (empty($token)) return null;
     */
    #[Test]
    public function validateProfileResetToken_withEmptyToken_returnsNull(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Terhubung ke: App\Services\AuthService
        $service = new AuthService();
        $token   = '';

        // ── ACT ─────────────────────────────────────────────────────────
        // Panggil validateProfileResetToken dengan token string kosong
        $result = $service->validateProfileResetToken($token);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AuthService::validateProfileResetToken() — guard clause null
        $this->assertNull($result, 'Token reset kosong harus mengembalikan null');
    }

    /**
     * Skenario 5: Cookie token remember-me kosong dikirim → mengembalikan false.
     * Terhubung ke: AuthService::processRememberMe(string $cookieToken)
     *               baris: if (empty($cookieToken)) return false;
     */
    #[Test]
    public function processRememberMe_withEmptyToken_returnsFalse(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Terhubung ke: App\Services\AuthService
        $service     = new AuthService();
        $cookieToken = '';

        // ── ACT ─────────────────────────────────────────────────────────
        // Panggil processRememberMe dengan token cookie kosong
        $result = $service->processRememberMe($cookieToken);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AuthService::processRememberMe() — guard clause false
        $this->assertFalse($result, 'Cookie token kosong harus mengembalikan false');
    }
}
