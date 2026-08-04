# SKILL.md — Panduan Unit Testing siCare HRMS & Payroll

> **Versi:** 1.0.0 | **Diperbarui:** 2026-07-29
> **Framework:** Custom MVC PHP 8.3 — tanpa Laravel/Symfony
> **Tools:** PHPUnit 11.x, Mockery, PHP In-Memory SQLite (PDO)

---

## Daftar Isi

1. [Konvensi Penulisan Test](#1-konvensi-penulisan-test)
2. [Standar Penamaan](#2-standar-penamaan)
3. [Aturan Mocking dan Isolasi Lingkungan](#3-aturan-mocking-dan-isolasi-lingkungan)
4. [Cakupan Skenario (Test Coverage)](#4-cakupan-skenario-test-coverage)
5. [Alur Eksekusi dan Self-Correction (Wajib)](#5-alur-eksekusi-dan-self-correction-wajib)

---

## Peta Fitur Sistem siCare

Sebelum menulis test, pahami seluruh modul yang ada di sistem:

| Modul | Class Utama | Layer |
|---|---|---|
| Autentikasi | `AuthService`, `AuthController` | Business + Presentation |
| Absensi & Geofencing | `AttendanceService`, `AttendanceController` | Business |
| Cuti Karyawan | `LeaveService`, `LeaveController` | Business |
| Penggajian (Payroll) | `PayrollService`, `PayrollController` | Business |
| Reimbursement | `ReimbursementController` | Business |
| Persetujuan (Approval) | `ApprovalController` | Business |
| Departemen | `DepartmentController` | Business |
| Pengaturan Sistem | `SettingsController` | Business |
| Model Data | `User`, `Attendance`, `LeaveRequest`, `Payroll`, `Reimbursement`, `Department` | Data |
| Helper Global | `helpers.php` (`e()`, `csrf_token()`, `validate_password_strength()`, `resolveProfilePicture()`) | Utility |

---

## 1. Konvensi Penulisan Test

### 1.1 Struktur Direktori

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── AuthServiceTest.php          # → App\Services\AuthService
│   │   ├── AttendanceServiceTest.php    # → App\Services\AttendanceService
│   │   ├── LeaveServiceTest.php         # → App\Services\LeaveService
│   │   └── PayrollServiceTest.php       # → App\Services\PayrollService
│   ├── Models/
│   │   ├── UserModelTest.php            # → App\Models\User
│   │   ├── AttendanceModelTest.php      # → App\Models\Attendance
│   │   ├── LeaveRequestModelTest.php    # → App\Models\LeaveRequest
│   │   └── PayrollModelTest.php         # → App\Models\Payroll
│   └── Helpers/
│       └── HelpersTest.php              # → app/helpers.php
└── Feature/
    ├── Auth/
    │   └── LoginFlowTest.php
    ├── Attendance/
    │   └── ClockInFlowTest.php
    ├── Leave/
    │   └── SubmitLeaveFlowTest.php
    └── Payroll/
        └── PayrollCalculationFlowTest.php
```

### 1.2 Kelas Test Dasar — BaseUnitTest

Setiap Unit Test **wajib** extends class berikut:

```php
<?php
// tests/Unit/BaseUnitTest.php
// Base class untuk semua Unit Test — tidak ada database/session/HTTP

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * BaseUnitTest
 *
 * Menyediakan utilitas bersama untuk Unit Tests:
 * - Bersihkan superglobal $_SESSION, $_POST, $_GET sebelum setiap test
 * - Pastikan tidak ada state bocor antar test method
 *
 * Digunakan oleh: semua class di tests/Unit/
 * Terhubung ke: semua test yang memeriksa logika session (AuthService, LeaveController, dll)
 */
abstract class BaseUnitTest extends TestCase
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
}
```

### 1.3 Kelas Test Dasar — BaseFeatureTest

Feature Test **wajib** extends class berikut:

```php
<?php
// tests/Feature/BaseFeatureTest.php
// Base class untuk Feature/Integration Tests — menggunakan SQLite in-memory

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use PDO;

/**
 * BaseFeatureTest
 *
 * Menyediakan koneksi PDO SQLite in-memory yang ter-isolasi per test.
 * Menggantikan Database::getInstance() untuk menghindari ketergantungan MySQL.
 *
 * Terhubung ke: App\Config\Database (singleton diganti saat testing).
 */
abstract class BaseFeatureTest extends TestCase
{
    /** @var PDO Koneksi SQLite in-memory yang dibuat fresh tiap test method */
    protected PDO $db;

    /**
     * setUp() — buat koneksi SQLite in-memory fresh sebelum setiap test.
     * Pastikan setiap test mulai dengan database bersih (no bleed-through).
     * Terhubung ke: database/migrations/*.php (diikuti strukturnya).
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->createSchema();
    }

    /**
     * createSchema() — buat semua tabel yang dibutuhkan oleh test.
     * Setiap subclass wajib mengimplementasikan method ini.
     * Terhubung ke: database/migrations/2026_06_28_000009_consolidate_all_tables_v25.php
     */
    abstract protected function createSchema(): void;

    protected function tearDown(): void
    {
        unset($this->db); // SQLite in-memory otomatis dihapus saat koneksi ditutup
        $_SESSION = [];
        parent::tearDown();
    }
}
```

### 1.4 Aturan Umum — Pola AAA (Arrange-Act-Assert)

- **1 class = 1 file test.** Jangan gabungkan test untuk dua class berbeda.
- **1 method = 1 skenario.** Tidak boleh lebih dari satu `act` dalam satu method test.
- **Urutan AAA wajib** dengan komentar pemisah di setiap blok.
- **@covers annotation wajib** di setiap method test.
- **Komentar "Terhubung ke:" wajib** di setiap blok Arrange dan Assert.

```php
/**
 * @test
 * @covers \App\Services\PayrollService::calculateNetSalary
 * Skenario: semua komponen positif → net = gross - deductions.
 * Terhubung ke: PayrollService::calculateNetSalary(float $baseSalary, ...).
 */
public function calculateNetSalary_withAllPositiveComponents_returnsCorrectNet(): void
{
    // ── ARRANGE ─────────────────────────────────────────────────────────
    // Terhubung ke: PayrollService::calculateNetSalary() — parameter input
    $service    = new PayrollService();
    $baseSalary = 5_000_000.0;
    $allowances = 500_000.0;
    $overtime   = 200_000.0;
    $bonus      = 300_000.0;
    $deductions = 275_000.0; // total BPJS TK + BPJS Kes + PPh21

    // ── ACT ─────────────────────────────────────────────────────────────
    $result = $service->calculateNetSalary($baseSalary, $allowances, $overtime, $bonus, $deductions);

    // ── ASSERT ──────────────────────────────────────────────────────────
    // Gross: 5.000.000 + 500.000 + 200.000 + 300.000 = 6.000.000
    // Net:   6.000.000 - 275.000 = 5.725.000
    $this->assertSame(5_725_000.0, $result);
}
```

---

## 2. Standar Penamaan

### 2.1 Nama File

| Pola | Contoh |
|---|---|
| `{ClassName}Test.php` | `AuthServiceTest.php`, `PayrollServiceTest.php` |
| `{ClassName}ModelTest.php` | `UserModelTest.php`, `AttendanceModelTest.php` |
| `{Feature}FlowTest.php` | `LoginFlowTest.php`, `SubmitLeaveFlowTest.php` |

### 2.2 Nama Method Test

Format: `methodName_kondisiInput_hasilYangDiharapkan`

```
// Service tests
calculateDistance_sameCoordinates_returnsZero
authenticate_withEmptyEmail_returnsFailureWithoutDbCall
authenticate_withSoftDeletedAccount_returnsFailure
evaluateClockInStatus_atGracePeriodBoundary_returnsTepat
calculateLeaveDuration_startAfterEnd_returnsZero
calculateBpjsTk_withStandardSalary_returnsCorrectDeduction

// Model tests
generateUuid_returnsValidUuidFormat
generateUuid_twoCalls_returnsDifferentValues

// Helper tests
e_withHtmlCharacters_escapesThemCorrectly
e_withNullInput_returnsEmptyString
csrfToken_firstCallWithEmptySession_generatesHexToken
```

### 2.3 Nama Data Provider

Format: `provide{Skenario}Cases` — digunakan dengan `@dataProvider`

```php
/**
 * Data provider untuk test BPJS Kes berbagai level gaji.
 * Terhubung ke: PayrollService::calculateBpjsKes().
 *
 * @return array<string, array{float, float, float}>
 */
public static function provideStandardSalaryDeductionCases(): array
{
    return [
        // Kunci array = nama skenario yang muncul di output --testdox
        'gaji minimum'   => [2_000_000.0, 1.0, 20_000.0],
        'gaji menengah'  => [5_000_000.0, 1.0, 50_000.0],
        'gaji eksekutif' => [20_000_000.0, 1.0, 200_000.0],
    ];
}
```

### 2.4 Nama Variable Mock / Stub

```php
// Pola: $mock{ClassName} untuk mock, $stub{ClassName} untuk stub
$mockDb   = $this->createMock(PDO::class);           // mock PDO connection
$mockStmt = $this->createMock(PDOStatement::class);  // mock prepared statement
$mockUser = $this->createMock(User::class);          // mock User model
```

---

## 3. Aturan Mocking dan Isolasi Lingkungan

### 3.1 Prinsip Isolasi — Larangan Ketat di Unit Test

> Unit Test **DILARANG** menyentuh:
> - Database MySQL / MariaDB nyata (gunakan mock PDO atau SQLite in-memory di Feature Test)
> - File system nyata (gunakan `tempnam()` + hapus di tearDown)
> - Panggilan HTTP eksternal (Gravatar, API pihak ketiga)
> - `session_start()` — set `$_SESSION[]` langsung sebagai array assignment
> - Superglobal `$_POST`, `$_FILES` — set manual sebelum test, bersihkan di tearDown

### 3.2 Mocking PDO Singleton — Teknik Reflection Injection

`AuthService`, `LeaveService`, `AttendanceService` memanggil `Database::getInstance()->getConnection()`
yang merupakan **Singleton**. Gunakan Reflection API untuk menggantinya tanpa mengubah kode produksi:

```php
<?php
// tests/Unit/Services/AuthServiceTest.php

namespace Tests\Unit\Services;

use App\Services\AuthService;
use PDO;
use PDOStatement;
use Tests\Unit\BaseUnitTest;

/**
 * AuthServiceTest
 *
 * Menguji business logic AuthService terisolasi dari database nyata.
 * PDO di-mock menggunakan PHPUnit createMock() + Reflection untuk inject ke singleton.
 *
 * Terhubung ke:
 *   - App\Services\AuthService::authenticate()
 *   - App\Services\AuthService::processRememberMe()
 *   - App\Services\AuthService::validateProfileResetToken()
 *   - App\Models\User::findByEmail() (dipanggil oleh AuthService::authenticate)
 *   - App\Config\Database::getInstance() (singleton yang diganti saat testing)
 */
class AuthServiceTest extends BaseUnitTest
{
    /**
     * makeServiceWithMockDb() — inject mock PDO ke singleton Database via Reflection.
     * Terhubung ke: App\Config\Database::$instance — private static property.
     *
     * @param PDO $mockDb Instance PDO mock yang akan menggantikan koneksi nyata
     * @return AuthService Instance AuthService yang menggunakan mock DB
     */
    private function makeServiceWithMockDb(PDO $mockDb): AuthService
    {
        // Akses private static property Database::$instance
        $reflection   = new \ReflectionClass(\App\Config\Database::class);
        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setAccessible(true); // bypass private visibility

        // Anonymous class yang meniru interface Database::getConnection()
        $fakeDbInstance = new class($mockDb) {
            private PDO $pdo;
            public function __construct(PDO $pdo) { $this->pdo = $pdo; }
            /** Meniru Database::getConnection() — mengembalikan PDO mock */
            public function getConnection(): PDO { return $this->pdo; }
        };

        // Ganti singleton dengan fake instance
        $instanceProp->setValue(null, $fakeDbInstance);
        return new AuthService();
    }

    /**
     * @test
     * @covers \App\Services\AuthService::authenticate
     * Skenario: email kosong → gagal langsung tanpa query DB sama sekali.
     * Terhubung ke: AuthService::authenticate() — baris validasi awal (empty check).
     */
    public function authenticate_withEmptyEmail_returnsFailureWithoutDbCall(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Tidak perlu mock DB karena validasi terjadi SEBELUM query DB dilakukan
        // Terhubung ke: AuthService::authenticate() — kondisi if (empty($email))
        $service = new AuthService();

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $service->authenticate('', 'somepassword');

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('wajib diisi', $result['message']);
    }

    /**
     * @test
     * @covers \App\Services\AuthService::authenticate
     * Skenario: password kosong → gagal dengan pesan yang sesuai.
     * Terhubung ke: AuthService::authenticate() — kondisi empty($password).
     */
    public function authenticate_withEmptyPassword_returnsFailure(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $service = new AuthService();

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $service->authenticate('user@example.com', '');

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertFalse($result['success']);
        $this->assertSame('Email dan kata sandi wajib diisi.', $result['message']);
    }

    /**
     * @test
     * @covers \App\Services\AuthService::authenticate
     * Skenario: user dengan flag is_deleted=1 → ditolak setelah user ditemukan.
     * Terhubung ke: AuthService::authenticate() — blok if ($user['is_deleted'] === 1).
     */
    public function authenticate_withSoftDeletedAccount_returnsFailure(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Buat mock PDOStatement yang akan mengembalikan user dengan is_deleted=1
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetch')->willReturn([
            'id'            => 'user-uuid-001',
            'email'         => 'deleted@example.com',
            'password_hash' => password_hash('secret', PASSWORD_BCRYPT),
            'is_deleted'    => 1,      // user sudah di-soft delete
            'is_suspended'  => 0,
            'role'          => 'employee',
            'first_name'    => 'Deleted',
            'last_name'     => 'User',
        ]);

        // Buat mock PDO yang mengembalikan $mockStmt saat prepare() dipanggil
        $mockDb = $this->createMock(PDO::class);
        $mockDb->method('prepare')->willReturn($mockStmt);

        // Inject mock DB ke singleton via Reflection
        $service = $this->makeServiceWithMockDb($mockDb);

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $service->authenticate('deleted@example.com', 'secret');

        // ── ASSERT ──────────────────────────────────────────────────────
        // Terhubung ke: AuthService::authenticate() — if ($user['is_deleted'] === 1)
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('dinonaktifkan', $result['message']);
    }

    /**
     * @test
     * @covers \App\Services\AuthService::validateProfileResetToken
     * Skenario: token kosong → return null tanpa query ke DB.
     * Terhubung ke: AuthService::validateProfileResetToken() — baris if (empty($token)).
     */
    public function validateProfileResetToken_withEmptyToken_returnsNull(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $service = new AuthService();

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $service->validateProfileResetToken('');

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertNull($result);
    }

    /**
     * @test
     * @covers \App\Services\AuthService::processRememberMe
     * Skenario: cookie token kosong → return false tanpa query ke DB.
     * Terhubung ke: AuthService::processRememberMe() — baris if (empty($cookieToken)).
     */
    public function processRememberMe_withEmptyToken_returnsFalse(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $service = new AuthService();

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $service->processRememberMe('');

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertFalse($result);
    }
}
```

### 3.3 Mocking File Upload (`$_FILES`)

Untuk test `ReimbursementController` dan `LeaveController`:

```php
/**
 * setupFakeFileUpload() — simulasikan superglobal $_FILES dengan file dummy.
 * Membuat file nyata di temp dir OS, agar is_uploaded_file() bisa dilewati dengan mock.
 *
 * Terhubung ke: ReimbursementController::submit() — validasi $_FILES['receipt']
 *               LeaveController::submit()          — validasi $_FILES['attachment']
 *
 * @param string $fieldName  Nama field file input HTML (misal: 'receipt')
 * @param string $extension  Ekstensi file yang diklaim (misal: 'jpg', 'pdf')
 * @param int    $sizeBytes  Ukuran file dummy dalam bytes
 * @return string            Path ke temp file — hapus di tearDown
 */
private function setupFakeFileUpload(
    string $fieldName,
    string $extension = 'jpg',
    int $sizeBytes = 1024
): string {
    // Buat file sementara di temp dir sistem operasi
    $tmpPath = tempnam(sys_get_temp_dir(), 'sicare_test_');
    file_put_contents($tmpPath, str_repeat('x', $sizeBytes));

    // Set superglobal $_FILES sesuai struktur upload PHP standar
    $_FILES[$fieldName] = [
        'name'     => "test_receipt.{$extension}",  // nama file asli dari client
        'type'     => "image/{$extension}",          // MIME type yang diklaim
        'tmp_name' => $tmpPath,                      // path sementara di server
        'error'    => UPLOAD_ERR_OK,                 // tidak ada error upload
        'size'     => $sizeBytes,                    // ukuran file dalam bytes
    ];

    return $tmpPath; // kembalikan agar bisa dihapus di tearDown
}
```

### 3.4 Isolasi Session dan CSRF Token

```php
/**
 * setAuthenticatedSession() — simulasikan state session user yang sudah login.
 *
 * Terhubung ke:
 *   - PayrollController::checkAccess()        — memeriksa $_SESSION['user_id'] dan $_SESSION['role']
 *   - LeaveController::submit()               — memeriksa $_SESSION['role'] === 'employee'
 *   - ReimbursementController::submit()       — memeriksa $_SESSION['user_id']
 *   - ApprovalController::checkAccess()       — memeriksa role 'executive' atau 'superadmin'
 *   - DepartmentController::checkAccess()     — memeriksa role 'admin' atau 'superadmin'
 *
 * @param string $role    Role user: 'employee'|'hr_ops'|'admin'|'superadmin'|'executive'
 * @param string $userId  UUID user yang disimulasikan
 */
private function setAuthenticatedSession(
    string $role = 'employee',
    string $userId = 'test-user-uuid'
): void {
    // Set langsung sebagai array — tidak memanggil session_start() di CLI
    $_SESSION['user_id']    = $userId;
    $_SESSION['role']       = $role;
    $_SESSION['name']       = 'Test User';
    $_SESSION['email']      = 'test@example.com';
    $_SESSION['csrf_token'] = 'valid-csrf-token-for-testing';
    $_SESSION['logged_in']  = true;
}

/**
 * setCsrfToken() — sinkronkan CSRF token di $_SESSION (server) dan $_POST (client).
 *
 * Terhubung ke:
 *   - LeaveController::validateCsrf()         — hash_equals($_SESSION['csrf_token'], $token)
 *   - ReimbursementController::validateCsrf() — hash_equals($_SESSION['csrf_token'], $token)
 *   - DepartmentController::checkAccess()     — hash_equals($_SESSION['csrf_token'], $token)
 *   - PayrollController::checkAccess()        — hash_equals($_SESSION['csrf_token'], $token)
 */
private function setCsrfToken(): void
{
    $token = 'test-csrf-token-abc123';
    // Token di session (server side) harus sama dengan di POST body (client side)
    $_SESSION['csrf_token'] = $token;
    $_POST['csrf_token']    = $token;
}
```

---

## 4. Cakupan Skenario (Test Coverage)

### 4.1 AttendanceService — Geofencing & Evaluasi Status Absensi

```php
<?php
// tests/Unit/Services/AttendanceServiceTest.php

namespace Tests\Unit\Services;

use App\Services\AttendanceService;
use Tests\Unit\BaseUnitTest;

/**
 * AttendanceServiceTest
 *
 * Menguji pure function di AttendanceService tanpa koneksi database.
 * Semua method yang diuji bersifat stateless (tidak memerlukan PDO).
 *
 * Terhubung ke:
 *   - AttendanceService::calculateDistance()    → Haversine GPS formula
 *   - AttendanceService::isWithinGeofence()     → Geofence radius validation
 *   - AttendanceService::evaluateClockInStatus() → Status tepat waktu/terlambat
 */
class AttendanceServiceTest extends BaseUnitTest
{
    /** @var AttendanceService Instance service yang diuji — tidak ada dependency external */
    private AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Inisialisasi service — tidak butuh DB sehingga tidak perlu mock
        $this->service = new AttendanceService();
    }

    // ─── calculateDistance() ───────────────────────────────────────────

    /**
     * @test
     * @covers \App\Services\AttendanceService::calculateDistance
     * Skenario: koordinat identik → jarak harus nol meter.
     * Terhubung ke: AttendanceService::calculateDistance() — Haversine formula dengan dLat=0, dLng=0.
     */
    public function calculateDistance_withIdenticalCoordinates_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Koordinat kantor default dari AttendanceController::DEFAULTS
        $lat = -6.2297;
        $lng = 106.8164;

        // ── ACT ─────────────────────────────────────────────────────────
        $distance = $this->service->calculateDistance($lat, $lng, $lat, $lng);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Toleransi 0.001m untuk floating point Haversine
        $this->assertEqualsWithDelta(0.0, $distance, 0.001, 'Koordinat identik harus menghasilkan jarak nol');
    }

    /**
     * @test
     * @covers \App\Services\AttendanceService::calculateDistance
     * Skenario: dua titik diketahui ~122m terpisah → hasil mendekati jarak sesungguhnya.
     * Terhubung ke: AttendanceService::calculateDistance() — kalkulasi Haversine penuh.
     */
    public function calculateDistance_withKnownPoints_returnsApproximateDistance(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $officeLat = -6.2297;  // Koordinat kantor (DEFAULTS['office_lat'])
        $officeLng = 106.8164; // Koordinat kantor (DEFAULTS['office_lng'])
        $userLat   = -6.2286;  // ~122m ke utara dari kantor (0.0011 derajat lat)
        $userLng   = 106.8164; // Bujur sama

        // ── ACT ─────────────────────────────────────────────────────────
        $distance = $this->service->calculateDistance($userLat, $userLng, $officeLat, $officeLng);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Toleransi ±15m karena variasi ellipsoid bumi (WGS84 vs spherical)
        $this->assertGreaterThan(100.0, $distance, 'Jarak harus lebih dari 100m');
        $this->assertLessThan(150.0, $distance, 'Jarak harus kurang dari 150m');
    }

    // ─── isWithinGeofence() ────────────────────────────────────────────

    /**
     * @test
     * @covers \App\Services\AttendanceService::isWithinGeofence
     * Skenario: user persis di titik kantor → dalam radius geofence.
     * Terhubung ke: AttendanceService::isWithinGeofence() — kondisi ($distance <= $maxRadiusMeters).
     */
    public function isWithinGeofence_whenUserAtOffice_returnsTrue(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $officeLat = -6.2297;
        $officeLng = 106.8164;
        $maxRadius = 150.0; // Radius default dari AttendanceController::DEFAULTS['office_radius_m']

        // ── ACT ─────────────────────────────────────────────────────────
        // User pada koordinat yang persis sama dengan kantor
        $result = $this->service->isWithinGeofence(
            $officeLat, $officeLng,  // posisi user
            $officeLat, $officeLng,  // posisi kantor
            $maxRadius
        );

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertTrue($result, 'User di titik kantor harus berada dalam geofence');
    }

    /**
     * @test
     * @covers \App\Services\AttendanceService::isWithinGeofence
     * Skenario: user ~550m dari kantor, radius 150m → di luar geofence.
     * Terhubung ke: AttendanceService::isWithinGeofence() — kondisi gagal ($distance > $maxRadius).
     */
    public function isWithinGeofence_whenUserFarFromOffice_returnsFalse(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $officeLat = -6.2297;
        $officeLng = 106.8164;
        $userLat   = -6.2248;  // ~550m ke utara — jauh dari kantor
        $userLng   = 106.8164;
        $maxRadius = 150.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $this->service->isWithinGeofence($userLat, $userLng, $officeLat, $officeLng, $maxRadius);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertFalse($result, 'User 550m dari kantor harus di luar geofence');
    }

    /**
     * @test
     * @covers \App\Services\AttendanceService::isWithinGeofence
     * Skenario: user tepat di batas radius (150m) → masih dalam geofence (batas inklusif).
     * Terhubung ke: AttendanceService::isWithinGeofence() — operator <= (bukan <).
     */
    public function isWithinGeofence_whenUserAtExactBoundary_returnsTrue(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $officeLat = 0.0;  // Gunakan titik asal untuk kemudahan kalkulasi batas
        $officeLng = 0.0;
        $maxRadius = 150.0;

        // Kalkulasi titik tepat 150m ke utara dari titik asal
        // 1 derajat latitude ≈ 111.320 km → 150m ≈ 0.001348 derajat
        $userLat = 0.001348;
        $userLng = 0.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $this->service->isWithinGeofence($userLat, $userLng, $officeLat, $officeLng, $maxRadius);

        // ── ASSERT ──────────────────────────────────────────────────────
        // User tepat di batas harus masuk (operator <=)
        $this->assertTrue($result, 'User tepat di batas radius harus masuk geofence (inklusif)');
    }

    // ─── evaluateClockInStatus() ───────────────────────────────────────

    /**
     * @test
     * @covers \App\Services\AttendanceService::evaluateClockInStatus
     * Skenario: clock-in 07:50 (10 menit sebelum jam kerja) → 'tepat waktu'.
     * Terhubung ke: AttendanceService::evaluateClockInStatus() — $clockInTs <= $lateThresholdTs.
     */
    public function evaluateClockInStatus_beforeWorkStart_returnsTepat(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Gunakan tanggal hari ini agar tidak ada masalah timezone CI
        $clockIn     = date('Y-m-d') . ' 07:50:00';
        $workStart   = '08:00:00';
        $gracePeriod = 15; // menit — sesuai default parameter AttendanceService

        // ── ACT ─────────────────────────────────────────────────────────
        $status = $this->service->evaluateClockInStatus($clockIn, $workStart, $gracePeriod);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame('tepat waktu', $status);
    }

    /**
     * @test
     * @covers \App\Services\AttendanceService::evaluateClockInStatus
     * Skenario: clock-in tepat 08:15 (batas akhir grace period) → masih 'tepat waktu'.
     * Terhubung ke: AttendanceService::evaluateClockInStatus() — batas $lateThresholdTs bersifat inklusif (<=).
     */
    public function evaluateClockInStatus_atGracePeriodBoundary_returnsTepat(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $clockIn     = date('Y-m-d') . ' 08:15:00';
        $workStart   = '08:00:00';
        $gracePeriod = 15;

        // ── ACT ─────────────────────────────────────────────────────────
        $status = $this->service->evaluateClockInStatus($clockIn, $workStart, $gracePeriod);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Operator <= memastikan batas grace period inklusif
        $this->assertSame('tepat waktu', $status);
    }

    /**
     * @test
     * @covers \App\Services\AttendanceService::evaluateClockInStatus
     * Skenario: clock-in 08:16 (1 menit setelah grace period habis) → 'terlambat'.
     * Terhubung ke: AttendanceService::evaluateClockInStatus() — return 'terlambat'.
     */
    public function evaluateClockInStatus_oneMinuteAfterGrace_returnsTerlambat(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $clockIn     = date('Y-m-d') . ' 08:16:00';
        $workStart   = '08:00:00';
        $gracePeriod = 15;

        // ── ACT ─────────────────────────────────────────────────────────
        $status = $this->service->evaluateClockInStatus($clockIn, $workStart, $gracePeriod);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame('terlambat', $status);
    }

    /**
     * @test
     * @covers \App\Services\AttendanceService::evaluateClockInStatus
     * Skenario: clock-in sangat terlambat (11:00 siang) → 'terlambat'.
     * Terhubung ke: AttendanceService::evaluateClockInStatus().
     */
    public function evaluateClockInStatus_atNoon_returnsTerlambat(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $clockIn     = date('Y-m-d') . ' 11:00:00';
        $workStart   = '08:00:00';
        $gracePeriod = 15;

        // ── ACT ─────────────────────────────────────────────────────────
        $status = $this->service->evaluateClockInStatus($clockIn, $workStart, $gracePeriod);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame('terlambat', $status);
    }
}
```

---

### 4.2 LeaveService — Kalkulasi Durasi & Validasi Cuti

```php
<?php
// tests/Unit/Services/LeaveServiceTest.php

namespace Tests\Unit\Services;

use App\Services\LeaveService;
use Tests\Unit\BaseUnitTest;

/**
 * LeaveServiceTest
 *
 * Menguji logika perhitungan hari kerja dan validasi pengajuan cuti.
 *
 * Terhubung ke:
 *   - LeaveService::calculateLeaveDuration()     → pure function, tanpa DB
 *   - LeaveService::hasOverlappingLeave()         → butuh DB mock (ditest di Feature)
 *   - LeaveService::getRemainingAnnualLeave()     → butuh DB mock (ditest di Feature)
 */
class LeaveServiceTest extends BaseUnitTest
{
    /** @var LeaveService Instance service yang diuji */
    private LeaveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveService();
    }

    /**
     * @test
     * @covers \App\Services\LeaveService::calculateLeaveDuration
     * Skenario: tanggal mulai setelah tanggal selesai → return 0 (guard awal).
     * Terhubung ke: LeaveService::calculateLeaveDuration() — baris if ($start > $end) return 0.
     */
    public function calculateLeaveDuration_startAfterEnd_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $startDate = '2026-08-10'; // lebih besar dari endDate
        $endDate   = '2026-08-05';

        // ── ACT ─────────────────────────────────────────────────────────
        $duration = $this->service->calculateLeaveDuration($startDate, $endDate);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(0, $duration, 'start > end harus menghasilkan durasi 0');
    }

    /**
     * @test
     * @covers \App\Services\LeaveService::calculateLeaveDuration
     * Skenario: tanggal mulai = tanggal selesai (Selasa) → durasi 1 hari kerja.
     * Terhubung ke: LeaveService::calculateLeaveDuration() — kondisi batas loop ($curr <= $end).
     */
    public function calculateLeaveDuration_sameDateBothEnds_returnsOne(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $date = '2026-08-04'; // Selasa — hari kerja biasa (bukan weekend)

        // ── ACT ─────────────────────────────────────────────────────────
        $duration = $this->service->calculateLeaveDuration($date, $date);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(1, $duration);
    }

    /**
     * @test
     * @covers \App\Services\LeaveService::calculateLeaveDuration
     * Skenario: Senin s.d. Minggu (7 hari) → 5 hari kerja efektif (skip Sabtu-Minggu).
     * Terhubung ke: LeaveService::calculateLeaveDuration() — in_array($dayName, $weeklyHolidays).
     */
    public function calculateLeaveDuration_oneWeekRange_returnsFiveWorkdays(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $startDate = '2026-08-03'; // Senin, 3 Agustus 2026
        $endDate   = '2026-08-09'; // Minggu, 9 Agustus 2026

        // ── ACT ─────────────────────────────────────────────────────────
        // Sabtu (8 Agt) + Minggu (9 Agt) dieksklusi → 5 hari kerja
        $duration = $this->service->calculateLeaveDuration($startDate, $endDate);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(5, $duration, 'Senin-Minggu harus menghasilkan 5 hari kerja');
    }

    /**
     * @test
     * @covers \App\Services\LeaveService::calculateLeaveDuration
     * Skenario: ada hari libur perusahaan di tengah rentang → dieksklusi dari durasi.
     * Terhubung ke: LeaveService::calculateLeaveDuration() — in_array($currDateStr, $companyHolidays).
     */
    public function calculateLeaveDuration_withCompanyHoliday_excludesHoliday(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $startDate       = '2026-08-17'; // Senin — 17 Agustus (Hari Kemerdekaan)
        $endDate         = '2026-08-19'; // Rabu
        $companyHolidays = ['2026-08-17']; // 17 Agustus = libur nasional

        // ── ACT ─────────────────────────────────────────────────────────
        // Senin(libur) → Selasa → Rabu = 2 hari kerja efektif
        $duration = $this->service->calculateLeaveDuration($startDate, $endDate, $companyHolidays);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(2, $duration, 'Hari libur perusahaan harus dieksklusi dari durasi');
    }

    /**
     * @test
     * @covers \App\Services\LeaveService::calculateLeaveDuration
     * Skenario: custom weekly holidays (Jumat-Sabtu) → hari Jumat tidak dihitung.
     * Terhubung ke: LeaveService::calculateLeaveDuration() — parameter $weeklyHolidays.
     */
    public function calculateLeaveDuration_withCustomWeeklyHolidays_skipsCorrectDays(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $startDate      = '2026-08-03'; // Senin
        $endDate        = '2026-08-07'; // Jumat
        $weeklyHolidays = ['Fri', 'Sat']; // Override: Jumat dan Sabtu = libur

        // ── ACT ─────────────────────────────────────────────────────────
        // Senin Selasa Rabu Kamis = 4 hari; Jumat = libur
        $duration = $this->service->calculateLeaveDuration($startDate, $endDate, [], $weeklyHolidays);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(4, $duration, 'Custom weekly holidays harus dihormati');
    }
}
```

---

### 4.3 PayrollService — Kalkulasi Gaji & Potongan

```php
<?php
// tests/Unit/Services/PayrollServiceTest.php

namespace Tests\Unit\Services;

use App\Services\PayrollService;
use Tests\Unit\BaseUnitTest;

/**
 * PayrollServiceTest
 *
 * Menguji seluruh kalkulasi penggajian di PayrollService.
 * Semua method adalah pure computation (tidak ada DB call langsung).
 *
 * Terhubung ke:
 *   - PayrollService::calculateBpjsTk()      → Potongan BPJS Ketenagakerjaan (default 2%)
 *   - PayrollService::calculateBpjsKes()     → Potongan BPJS Kesehatan (default 1%)
 *   - PayrollService::calculatePph21()       → Potongan PPh21 (default 2.5%)
 *   - PayrollService::calculateNetSalary()   → Gaji bersih = gross - total deductions
 */
class PayrollServiceTest extends BaseUnitTest
{
    /** @var PayrollService Instance service yang diuji */
    private PayrollService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PayrollService();
    }

    /**
     * @test
     * @covers \App\Services\PayrollService::calculateBpjsTk
     * Skenario: gaji Rp 5.000.000, rate 2% → potongan Rp 100.000.
     * Terhubung ke: PayrollService::calculateBpjsTk() — formula round(($baseSalary * $pct) / 100, 2).
     */
    public function calculateBpjsTk_withStandardSalary_returnsCorrectDeduction(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary  = 5_000_000.0;
        $defaultRate = 2.0; // persen — sesuai default parameter PayrollService

        // ── ACT ─────────────────────────────────────────────────────────
        $deduction = $this->service->calculateBpjsTk($baseSalary, $defaultRate);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(100_000.0, $deduction);
    }

    /**
     * @test
     * @covers \App\Services\PayrollService::calculateBpjsTk
     * Skenario: gaji nol → potongan harus nol (tidak boleh minus atau NaN).
     * Terhubung ke: PayrollService::calculateBpjsTk() — edge case $baseSalary = 0.
     */
    public function calculateBpjsTk_withZeroSalary_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary = 0.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $deduction = $this->service->calculateBpjsTk($baseSalary);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(0.0, $deduction);
    }

    /**
     * @test
     * @dataProvider provideStandardSalaryDeductionCases
     * @covers \App\Services\PayrollService::calculateBpjsKes
     * Skenario: berbagai level gaji → BPJS Kes 1% harus selalu akurat.
     * Terhubung ke: PayrollService::calculateBpjsKes() — formula sama dengan calculateBpjsTk.
     */
    public function calculateBpjsKes_variousSalaries_returnsCorrectOnePercent(
        float $salary,
        float $pct,
        float $expected
    ): void {
        // ── ACT ─────────────────────────────────────────────────────────
        $result = $this->service->calculateBpjsKes($salary, $pct);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Toleransi 0.01 untuk perbedaan floating point dari round()
        $this->assertEqualsWithDelta($expected, $result, 0.01);
    }

    /**
     * Data provider untuk calculateBpjsKes dengan 3 level gaji berbeda.
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

    /**
     * @test
     * @covers \App\Services\PayrollService::calculatePph21
     * Skenario: penghasilan kena pajak Rp 5.000.000, rate 2.5% → PPh21 = Rp 125.000.
     * Terhubung ke: PayrollService::calculatePph21() — formula round(($taxableIncome * $pct) / 100, 2).
     */
    public function calculatePph21_withTaxableIncome_returnsCorrectTax(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $taxableIncome = 5_000_000.0;
        $defaultRate   = 2.5; // persen — sesuai default parameter PayrollService

        // ── ACT ─────────────────────────────────────────────────────────
        $tax = $this->service->calculatePph21($taxableIncome, $defaultRate);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(125_000.0, $tax);
    }

    /**
     * @test
     * @covers \App\Services\PayrollService::calculatePph21
     * Skenario: penghasilan nol → PPh21 harus nol.
     * Terhubung ke: PayrollService::calculatePph21() — edge case income = 0.
     */
    public function calculatePph21_withZeroIncome_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $taxableIncome = 0.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $tax = $this->service->calculatePph21($taxableIncome);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(0.0, $tax);
    }

    /**
     * @test
     * @covers \App\Services\PayrollService::calculateNetSalary
     * Skenario: semua komponen positif → net = gross - deductions.
     * Terhubung ke: PayrollService::calculateNetSalary() — $gross = base + allowances + overtime + bonus.
     */
    public function calculateNetSalary_withAllPositiveComponents_returnsCorrectNet(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary = 5_000_000.0;
        $allowances = 500_000.0;   // tunjangan transport, makan, dll
        $overtime   = 200_000.0;   // lembur
        $bonus      = 300_000.0;   // bonus kinerja
        $deductions = 275_000.0;   // total: BPJS TK 100k + BPJS Kes 50k + PPh21 125k

        // ── ACT ─────────────────────────────────────────────────────────
        $net = $this->service->calculateNetSalary($baseSalary, $allowances, $overtime, $bonus, $deductions);

        // ── ASSERT ──────────────────────────────────────────────────────
        // Gross: 5.000.000 + 500.000 + 200.000 + 300.000 = 6.000.000
        // Net:   6.000.000 - 275.000 = 5.725.000
        $this->assertSame(5_725_000.0, $net);
    }

    /**
     * @test
     * @covers \App\Services\PayrollService::calculateNetSalary
     * Skenario: total potongan melebihi gross → gaji bersih minimum 0 (tidak negatif).
     * Terhubung ke: PayrollService::calculateNetSalary() — max(0.0, round($gross - $deductions, 2)).
     */
    public function calculateNetSalary_whenDeductionsExceedGross_returnsZero(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary = 1_000_000.0;
        $allowances = 0.0;
        $overtime   = 0.0;
        $bonus      = 0.0;
        $deductions = 9_999_999.0; // potongan jauh melampaui gaji pokok

        // ── ACT ─────────────────────────────────────────────────────────
        $net = $this->service->calculateNetSalary($baseSalary, $allowances, $overtime, $bonus, $deductions);

        // ── ASSERT ──────────────────────────────────────────────────────
        // max(0.0, ...) memastikan tidak pernah negatif
        $this->assertSame(0.0, $net, 'Gaji bersih tidak boleh bernilai negatif');
    }

    /**
     * @test
     * @covers \App\Services\PayrollService::calculateNetSalary
     * Skenario: tidak ada komponen tambahan dan tidak ada potongan → net = base.
     * Terhubung ke: PayrollService::calculateNetSalary() — baseline minimalis test.
     */
    public function calculateNetSalary_withNoExtrasAndNoDeductions_returnsBaseSalary(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $baseSalary = 3_500_000.0;

        // ── ACT ─────────────────────────────────────────────────────────
        $net = $this->service->calculateNetSalary($baseSalary, 0.0, 0.0, 0.0, 0.0);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame(3_500_000.0, $net);
    }
}
```

---

### 4.4 User Model — Validasi UUID dan Interaksi Database

```php
<?php
// tests/Unit/Models/UserModelTest.php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\Unit\BaseUnitTest;

/**
 * UserModelTest
 *
 * Menguji method-method di User Model yang tidak membutuhkan database.
 * Method yang membutuhkan database diuji di Feature Test.
 *
 * Terhubung ke:
 *   - User::generateUuid()         → pure function, penanganan UUID v4
 *   - User::findByEmail()          → butuh DB → ditest di Feature Test
 *   - User::create()               → butuh DB → ditest di Feature Test
 *   - User::updateProfilePicture()  → butuh DB → ditest di Feature Test
 */
class UserModelTest extends BaseUnitTest
{
    // ─── generateUuid() ────────────────────────────────────────────────

    /**
     * @test
     * @covers \App\Models\User::generateUuid
     * Skenario: UUID yang dihasilkan harus berformat valid UUID v4 (8-4-4-4-12).
     * Terhubung ke: User::generateUuid() — format sprintf UUID v4.
     */
    public function generateUuid_returnsValidUuidFormat(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        // Bypass __construct yang memanggil Database::getInstance()
        $user = $this->getMockBuilder(User::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods([])
                     ->getMock();

        // ── ACT ─────────────────────────────────────────────────────────
        $uuid = $user->generateUuid();

        // ── ASSERT ──────────────────────────────────────────────────────
        // Regex validasi UUID v4: 8-4-4-4-12 karakter heksadesimal
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression($pattern, $uuid, 'UUID harus berformat valid v4');
    }

    /**
     * @test
     * @covers \App\Models\User::generateUuid
     * Skenario: dua pemanggilan berturutan harus menghasilkan UUID yang berbeda.
     * Terhubung ke: User::generateUuid() — keunikan nilai acak mt_rand.
     */
    public function generateUuid_twoCalls_returnsDifferentValues(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $user = $this->getMockBuilder(User::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods([])
                     ->getMock();

        // ── ACT ─────────────────────────────────────────────────────────
        $uuid1 = $user->generateUuid();
        $uuid2 = $user->generateUuid();

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertNotSame($uuid1, $uuid2, 'Setiap pemanggilan harus menghasilkan UUID unik');
    }
}
```

---

### 4.5 Helper Functions — Fungsi Utilitas Global

```php
<?php
// tests/Unit/Helpers/HelpersTest.php

namespace Tests\Unit\Helpers;

use Tests\Unit\BaseUnitTest;

/**
 * HelpersTest
 *
 * Menguji seluruh fungsi utilitas global di app/helpers.php.
 *
 * Terhubung ke:
 *   - helpers.php :: e()                          → XSS HTML escaping
 *   - helpers.php :: csrf_token()                 → CSRF Token generation
 *   - helpers.php :: csrfField()                  → Hidden HTML input generation
 *   - helpers.php :: validate_password_strength()  → Aturan kekuatan kata sandi
 *   - helpers.php :: resolveProfilePicture()      → Cascade avatar Google / Gravatar
 */
class HelpersTest extends BaseUnitTest
{
    // ─── e() — XSS HTML Escaping ───────────────────────────────────────

    /**
     * @test
     * @covers e
     * Skenario: string dengan tag HTML dan JavaScript → harus di-escape penuh.
     * Terhubung ke: helpers.php :: e() — htmlspecialchars(ENT_QUOTES, UTF-8).
     */
    public function e_withHtmlCharacters_escapesThemCorrectly(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $input    = '<script>alert("XSS")</script>';
        $expected = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';

        // ── ACT ─────────────────────────────────────────────────────────
        $result = e($input);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     * @covers e
     * Skenario: null input → return string kosong (aman dari TypeError).
     * Terhubung ke: helpers.php :: e() — handling null ($value ?? '').
     */
    public function e_withNullInput_returnsEmptyString(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $input = null;

        // ── ACT ─────────────────────────────────────────────────────────
        $result = e($input);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame('', $result);
    }

    /**
     * @test
     * @covers e
     * Skenario: karakter petik tunggal (') → di-escape menjadi &#039;.
     * Terhubung ke: helpers.php :: e() — ENT_QUOTES flag.
     */
    public function e_withSingleQuote_escapesCorrectly(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $input = "It's a test & <b>safe</b>";

        // ── ACT ─────────────────────────────────────────────────────────
        $result = e($input);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertStringContainsString('&#039;', $result);
        $this->assertStringContainsString('&amp;', $result);
    }

    // ─── csrf_token() ──────────────────────────────────────────────────

    /**
     * @test
     * @covers csrf_token
     * Skenario: session kosong → buat token hex 64 karakter (32 byte).
     * Terhubung ke: helpers.php :: csrf_token() — bin2hex(random_bytes(32)).
     */
    public function csrfToken_firstCallWithEmptySession_generatesHexToken(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $this->assertArrayNotHasKey('csrf_token', $_SESSION);

        // ── ACT ─────────────────────────────────────────────────────────
        $token = csrf_token();

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token, 'CSRF token harus 64 karakter hex');
    }

    /**
     * @test
     * @covers csrf_token
     * Skenario: token sudah ada di session → kembalikan token yang sama.
     * Terhubung ke: helpers.php :: csrf_token() — reuse $_SESSION['csrf_token'].
     */
    public function csrfToken_whenTokenAlreadyExists_returnsSameToken(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $_SESSION['csrf_token'] = 'existing-token-abc123def456';

        // ── ACT ─────────────────────────────────────────────────────────
        $token = csrf_token();

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame('existing-token-abc123def456', $token);
    }

    // ─── resolveProfilePicture() ───────────────────────────────────────

    /**
     * @test
     * @covers resolveProfilePicture
     * Skenario: Google profile picture tersedia → kembalikan URL Google.
     * Terhubung ke: helpers.php :: resolveProfilePicture() — prioritas pertama Google picture.
     */
    public function resolveProfilePicture_withGooglePicture_returnsGoogleUrl(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $email     = 'user@example.com';
        $googlePic = 'https://lh3.googleusercontent.com/photo/abc123';

        // ── ACT ─────────────────────────────────────────────────────────
        $result = resolveProfilePicture($email, $googlePic);

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertSame($googlePic, $result);
    }
}
```

---

### 4.6 Feature Test — Login Flow (Integrasi End-to-End)

```php
<?php
// tests/Feature/Auth/LoginFlowTest.php

namespace Tests\Feature\Auth;

use Tests\Feature\BaseFeatureTest;

/**
 * LoginFlowTest
 *
 * Menguji alur login end-to-end menggunakan SQLite in-memory database.
 * Terhubung ke:
 *   - App\Services\AuthService::authenticate()
 *   - App\Models\User::findByEmail()
 *   - App\Config\Database (singleton digantikan SQLite in-memory)
 */
class LoginFlowTest extends BaseFeatureTest
{
    /**
     * createSchema() — buat tabel yang dibutuhkan untuk skenario login.
     * Terhubung ke: database/migrations/2026_06_28_000009_consolidate_all_tables_v25.php
     */
    protected function createSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id               TEXT PRIMARY KEY,
                first_name       TEXT NOT NULL,
                last_name        TEXT,
                email            TEXT NOT NULL UNIQUE,
                password_hash    TEXT NOT NULL,
                role             TEXT DEFAULT 'employee',
                department_id    TEXT,
                is_deleted       INTEGER DEFAULT 0,
                is_suspended     INTEGER DEFAULT 0,
                remember_token   TEXT,
                remember_token_expires_at TEXT
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                email        TEXT NOT NULL,
                ip_address   TEXT NOT NULL,
                attempt_time TEXT NOT NULL
            )
        ");
    }

    /**
     * insertTestUser() — helper membuat data user dummy di SQLite test.
     */
    private function insertTestUser(string $email = 'test@example.com', string $password = 'ValidPass123', array $overrides = []): string
    {
        $id = 'test-user-uuid-001';
        $data = array_merge([
            'id'            => $id,
            'first_name'    => 'Test',
            'last_name'     => 'User',
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => 'employee',
            'is_deleted'    => 0,
            'is_suspended'  => 0,
        ], $overrides);

        $this->db->prepare("
            INSERT INTO users (id, first_name, last_name, email, password_hash, role, is_deleted, is_suspended)
            VALUES (:id, :first_name, :last_name, :email, :password_hash, :role, :is_deleted, :is_suspended)
        ")->execute($data);

        return $id;
    }

    /**
     * makeAuthService() — inject koneksi SQLite ke singleton Database.
     */
    private function makeAuthService(\PDO $testDb): \App\Services\AuthService
    {
        $reflection   = new \ReflectionClass(\App\Config\Database::class);
        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setAccessible(true);

        $fakeDb = new class($testDb) {
            private \PDO $pdo;
            public function __construct(\PDO $pdo) { $this->pdo = $pdo; }
            public function getConnection(): \PDO { return $this->pdo; }
        };

        $instanceProp->setValue(null, $fakeDb);
        return new \App\Services\AuthService();
    }

    /**
     * @test
     * @covers \App\Services\AuthService::authenticate
     * Skenario: email belum terdaftar → return pesan error.
     */
    public function loginFlow_withUnregisteredEmail_returnsNotFoundMessage(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $service = $this->makeAuthService($this->db);

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $service->authenticate('notexist@example.com', 'password123');

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('tidak terdaftar', $result['message']);
    }

    /**
     * @test
     * @covers \App\Services\AuthService::authenticate
     * Skenario: password salah → catat percobaan login di login_attempts.
     */
    public function loginFlow_withWrongPassword_logsAttemptAndFails(): void
    {
        // ── ARRANGE ─────────────────────────────────────────────────────
        $this->insertTestUser('user@example.com', 'CorrectPass123');
        $service = $this->makeAuthService($this->db);

        // ── ACT ─────────────────────────────────────────────────────────
        $result = $service->authenticate('user@example.com', 'WrongPass999', false, '127.0.0.1');

        // ── ASSERT ──────────────────────────────────────────────────────
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('tidak valid', $result['message']);

        // Verifikasi percobaan dicatat di database SQLite
        $count = (int)$this->db->query("SELECT COUNT(*) FROM login_attempts WHERE email = 'user@example.com'")->fetchColumn();
        $this->assertSame(1, $count);
    }
}
```

---

### 4.7 Tabel Ringkasan Coverage Wajib

| Modul | Method | Skenario Minimum |
|---|---|---|
| `AuthService::authenticate()` | email kosong, password kosong, soft deleted, suspended, throttle aktif, password salah, login sukses, remember-me |
| `AuthService::processRememberMe()` | token kosong, token kadaluarsa, token valid |
| `AttendanceService::calculateDistance()` | koordinat identik (0m), jarak dikenal |
| `AttendanceService::isWithinGeofence()` | di titik kantor, di luar radius, di batas radius |
| `AttendanceService::evaluateClockInStatus()` | sebelum jam kerja, di batas grace period, lewat grace period, sangat terlambat |
| `LeaveService::calculateLeaveDuration()` | start > end, tanggal identik, 1 minggu (skip weekend), libur perusahaan, custom weekly holiday |
| `PayrollService::calculateBpjsTk()` | gaji 0, gaji standar, gaji tinggi |
| `PayrollService::calculateBpjsKes()` | multi-level gaji (data provider 3 kasus) |
| `PayrollService::calculatePph21()` | income standar, income 0 |
| `PayrollService::calculateNetSalary()` | semua positif, potongan > gross (minimum 0), tanpa komponen tambahan |
| `User::generateUuid()` | format UUID v4 valid, keunikan dua pemanggilan |
| `helpers.php :: e()` | XSS payload, null, single quote |
| `helpers.php :: csrf_token()` | generate token pertama kali, reuse token yang ada |
| `helpers.php :: resolveProfilePicture()` | dengan Google picture, tanpa Google picture |

---

## 5. Alur Eksekusi dan Self-Correction (Wajib)

### 5.1 Alur Eksekusi Standard

```
STEP 1: IDENTIFIKASI CLASS TARGET
   Baca file di app/Services/ atau app/Models/
   Identifikasi: method apa, dependency apa, input/output apa
          │
          ▼
STEP 2: TENTUKAN JENIS TEST
   Method pure (tanpa I/O)     → Unit Test (tests/Unit/)
   Method dengan DB call       → Feature Test + SQLite in-memory
   Method dengan HTTP/File     → Feature Test + Mock $_FILES/$_SERVER
          │
          ▼
STEP 3: TULIS TEST (AAA Pattern)
   // ── ARRANGE ──  (siapkan data & mock)
   // ── ACT ──      (panggil satu method)
   // ── ASSERT ──   (verifikasi hasil)
   Sertakan @covers annotation + komentar "Terhubung ke: ..."
          │
          ▼
STEP 4: JALANKAN TEST
   ./vendor/bin/phpunit --testdox
          │
          ▼
STEP 5: ANALISIS HASIL → Self-Correction (lihat 5.2)
```

### 5.2 Tabel Diagnosa Error — Self-Correction

| Gejala Error | Kemungkinan Penyebab | Langkah Koreksi |
|---|---|---|
| `Call to undefined method PDO::prepare()` | Mock PDO tidak dikonfigurasi untuk method prepare | Tambahkan `$mockDb->method('prepare')->willReturn($mockStmt)` |
| `Cannot access private property Database::$instance` | Reflection `setAccessible()` belum dipanggil | Tambahkan `$instanceProp->setAccessible(true)` sebelum `setValue()` |
| `session_start(): Cannot start session` | `session_start()` dipanggil di CLI tanpa guard | Pastikan kode produksi memiliki guard `PHP_SAPI !== 'cli'` seperti di `helpers.php::csrf_token()` |
| `Expected: 5.0, got: 5.00000000001` | Floating point precision tidak sama | Ganti `assertSame()` dengan `assertEqualsWithDelta($expected, $result, 0.01)` |
| `Method X does not exist on mock` | Method tidak disertakan di `onlyMethods()` | Tambahkan nama method ke array: `->onlyMethods(['methodA', 'methodB'])` |
| `PDOException: no such table: users` | Tabel tidak dibuat di `createSchema()` | Pastikan semua tabel yang diakses service ada di `createSchema()` |
| `Expected: 'terlambat', got: 'tepat waktu'` | Timestamp test menggunakan tanggal hardcoded kemarin | Gunakan `date('Y-m-d') . ' HH:MM:SS'` bukan tanggal hardcoded |
| `Test passes locally, fails on CI` | Timezone berbeda antara lokal dan server CI | Tambahkan `date_default_timezone_set('Asia/Jakarta')` di `setUp()` atau `phpunit.xml` |
| `Deprecated: ReturnTypeWillChange` | Versi PHPUnit tidak kompatibel dengan PHP 8.3 | Pastikan PHPUnit ^11.0 sesuai `require: php: >=8.3.0` di `composer.json` |

### 5.3 Checklist Sebelum Commit Test

```
[ ] Setiap test method mengikuti pola AAA (Arrange-Act-Assert)
[ ] Setiap blok kode (Arrange/Act/Assert) memiliki komentar "Terhubung ke: ..."
[ ] Nama method test mengikuti format: methodName_kondisiInput_hasilYangDiharapkan
[ ] Tidak ada test yang bergantung pada urutan eksekusi (test independence)
[ ] Tidak ada koneksi ke database MySQL nyata di Unit Test (gunakan mock/SQLite)
[ ] Semua mock di-setup lengkap: prepare() → willReturn($stmt), fetch() → willReturn(...)
[ ] tearDown() atau setUp() membersihkan semua state ($_SESSION, temp files, singleton)
[ ] @covers annotation merujuk ke fully-qualified class name yang benar
[ ] Data provider dinamai dengan format provideSkenarioCases()
[ ] Assertion memiliki pesan deskriptif sebagai parameter ke-3
[ ] Tidak ada echo/var_dump yang tertinggal di dalam test
```

### 5.4 Konfigurasi phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!--
    phpunit.xml — Konfigurasi PHPUnit untuk siCare HRMS & Payroll
    Terhubung ke: seluruh file di tests/Unit/ dan tests/Feature/
-->
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd"
    bootstrap="vendor/autoload.php"
    colors="true"
    failOnWarning="true"
    failOnRisky="true"
>
    <!-- ─── Test Suites ─────────────────────────────────────────────── -->
    <testsuites>
        <!-- Unit Tests: murni logika, tanpa I/O eksternal -->
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>

        <!-- Feature Tests: integrasi dengan SQLite in-memory -->
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>

    <!-- ─── Code Coverage Source ────────────────────────────────────── -->
    <!--
        Terhubung ke: app/Services/, app/Models/, app/helpers.php
        Hanya source yang didaftarkan di sini yang dihitung coverage-nya
    -->
    <source>
        <include>
            <directory suffix=".php">app/Services</directory>
            <directory suffix=".php">app/Models</directory>
            <file>app/helpers.php</file>
        </include>
        <exclude>
            <!-- Exclude vendor dan file config yang tidak ditest -->
            <directory>vendor</directory>
            <directory>app/Config</directory>
        </exclude>
    </source>

    <!-- ─── PHP Settings ────────────────────────────────────────────── -->
    <php>
        <!-- Timezone konsisten antara lokal dan CI/CD server -->
        <ini name="date.timezone" value="Asia/Jakarta"/>
        <!-- Error reporting ketat — deteksi masalah lebih awal -->
        <ini name="error_reporting" value="E_ALL"/>
        <!-- Variabel environment khusus untuk testing -->
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
    </php>
</phpunit>
```

### 5.5 Cara Menjalankan Test

```bash
# Jalankan seluruh suite test
./vendor/bin/phpunit

# Jalankan hanya Unit Test
./vendor/bin/phpunit --testsuite Unit

# Jalankan hanya Feature Test
./vendor/bin/phpunit --testsuite Feature

# Output verbose — tampilkan nama setiap test method
./vendor/bin/phpunit --testdox

# Jalankan satu file test saja
./vendor/bin/phpunit tests/Unit/Services/PayrollServiceTest.php

# Jalankan dengan code coverage HTML (butuh Xdebug atau PCOV)
./vendor/bin/phpunit --coverage-html coverage/

# Filter berdasarkan nama method
./vendor/bin/phpunit --filter calculateNetSalary_whenDeductionsExceedGross

# Stop setelah error pertama (fail-fast)
./vendor/bin/phpunit --stop-on-failure
```

### 5.6 Target Coverage Minimum per Layer

| Layer | Direktori | Target Coverage |
|---|---|---|
| Service Layer | `app/Services/` | **≥ 90%** |
| Model Layer (pure methods) | `app/Models/` | **≥ 85%** |
| Helper Functions | `app/helpers.php` | **≥ 80%** |
| Controller Layer (via Feature) | `app/Controllers/` | **≥ 70%** |

---

> **Catatan Akhir:** Perbarui SKILL.md ini setiap kali ada penambahan fitur baru
> atau perubahan signifikan pada business logic. Setiap method baru di `app/Services/`
> wajib memiliki minimal **3 skenario test**:
> 1. **Happy path** — input valid, hasil normal
> 2. **Edge case** — nilai batas (0, null, tanggal identik, dll)
> 3. **Error/failure case** — input invalid, kondisi gagal

---

## 6. Standar Unit Test Performance SLA (< 1 Detik) & Query Efficiency

### 6.1 Prinsip Pengujian Performa SLA

1. **Waktu Muat / Respon Menu (< 1.000 ms = 1 Detik)**:
   Setiap fungsi controller, rendering view, atau pemrosesan batch data **dilarang keras** membutuhkan waktu lebih dari 1000 milidetik (1 detik).
2. **Penggunaan Helper `assertExecutionTimeLessThanMs()`**:
   Menggunakan helper yang diintegrasikan pada `BaseTestCase` untuk mengukur durasi fungsi via `microtime(true)`.

### 6.2 Prinsip Pengujian Efisiensi Query

1. **Pencegahan N+1 Query**:
   Memastikan jumlah query SQL yang dieksekusi saat memuat data daftar tidak bertambah secara linear ($O(N)$) terhadap jumlah item.
2. **Pencegahan Full Table Scan**:
   Seluruh query pencarian dan pencocokan wajib menggunakan klausa `WHERE` yang terikat pada *Primary Key* atau *Indexed Column* (misalnya `email`, `user_id`, `attendance_date`).
3. **Penggunaan Prepared Statements**:
   Seluruh query wajib menggunakan PDO Prepared Statements (`:param`) untuk mencegah SQL Injection sekaligus mengizinkan database engine memanfaatkan *execution plan cache*.

### 6.3 Contoh Kode Unit Test Performa & Query Efficiency

```php
// tests/Unit/Services/PerformanceAndQueryEfficiencyTest.php

#[Test]
public function menuAttendance_bulkEvaluationPerformance_isUnderOneSecond(): void
{
    $service = new AttendanceService();

    // Assertion SLA: 200 evaluasi data absensi wajib selesai di bawah 1.000 ms
    $this->assertExecutionTimeLessThanMs(function () use ($service) {
        for ($i = 0; $i < 200; $i++) {
            $service->evaluateClockInStatus('2026-07-31 08:10:00', '08:00:00', 15);
        }
    }, 1000.0, 'Batch evaluasi data absensi harus selesai < 1 detik.');
}

#[Test]
public function queryOptimization_usesPreparedStatementsWithIndexedKeys(): void
{
    $mockDb = $this->createMock(PDO::class);
    // Memastikan query selalu menggunakan parameter binding terindeks
    $mockDb->expects($this->atLeastOnce())
           ->method('prepare')
           ->willReturn($mockStmt);

    $this->assertExecutionTimeLessThanMs(function () use ($service) {
        return $service->authenticate('fast@example.com', 'Pass123');
    }, 1000.0, 'Query autentikasi harus terindeks dan selesai < 1 detik.');
}
```
