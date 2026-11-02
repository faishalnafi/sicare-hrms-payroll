# 🧪 Laporan Hasil Eksekusi Unit Test — siCare HRMS & Payroll

> **Diperbarui:** 2026-07-31
> **Environment:** PHP 8.4.5 (CLI), PHPUnit 11.5.56
> **Status:** 🟢 **48 PASS / 0 FAIL (100% Success)**

---

## 📊 Ringkasan Eksekusi Suite

| No | Modul / Class Test | File Test | Skenario Uji | Status | Assertions |
|---|---|---|---|---|---|
| 1 | **AuthServiceTest** | `tests/Unit/Services/AuthServiceTest.php` | 5 Unit Test | 🟢 PASS | 11 |
| 2 | **AttendanceServiceTest** | `tests/Unit/Services/AttendanceServiceTest.php` | 9 Unit Test | 🟢 PASS | 10 |
| 3 | **PerformanceAndQueryEfficiencyTest** | `tests/Unit/Services/PerformanceAndQueryEfficiencyTest.php` | 5 Performance & Query Test | 🟢 PASS | 6 |
| 4 | **CleanCodeComplianceTest** | `tests/Unit/Quality/CleanCodeComplianceTest.php` | 5 Clean Code Audit Test | 🟢 PASS | 15 |
| 5 | **LeaveServiceTest** | `tests/Unit/Services/LeaveServiceTest.php` | 5 Unit Test | 🟢 PASS | 5 |
| 6 | **PayrollServiceTest** | `tests/Unit/Services/PayrollServiceTest.php` | 11 Unit Test *(incl. 3 DataProvider)* | 🟢 PASS | 12 |
| 7 | **HelpersTest** | `tests/Unit/Helpers/HelpersTest.php` | 6 Unit Test | 🟢 PASS | 8 |
| 8 | **UserModelTest** | `tests/Unit/Models/UserModelTest.php` | 2 Unit Test | 🟢 PASS | 3 |
| **TOTAL** | **8 Test Classes** | — | **48 Unit Tests** | 🟢 **PASS** | **70 Assertions** |

---

## 1. Modul 1: Autentikasi (AuthServiceTest)

- **Class Target**: `App\Services\AuthService`
- **File Test**: [AuthServiceTest.php](file:///d:/Server/WebApp/siCare/tests/Unit/Services/AuthServiceTest.php)
- **Status Eksekusi**: 🟢 **5 / 5 PASS (100%)**

### 📝 Detail Skenario Uji:

| No | Nama Method Test | Fungsi Target di Kode Produksi | Hasil Ekspektasi | Status |
|---|---|---|---|---|
| 1.1 | `authenticate_withEmptyEmail_returnsFailureWithoutDbCall` | `AuthService::authenticate()` ([AuthService.php:L21](file:///d:/Server/WebApp/siCare/app/Services/AuthService.php#L21)) | Validasi gagal saat email kosong (tanpa query DB) | 🟢 PASS |
| 1.2 | `authenticate_withEmptyPassword_returnsFailure` | `AuthService::authenticate()` ([AuthService.php:L21](file:///d:/Server/WebApp/siCare/app/Services/AuthService.php#L21)) | Validasi gagal saat kata sandi kosong (tanpa query DB) | 🟢 PASS |
| 1.3 | `authenticate_withSoftDeletedAccount_returnsFailure` | `AuthService::authenticate()` ([AuthService.php:L38](file:///d:/Server/WebApp/siCare/app/Services/AuthService.php#L38)) | Akun dengan flag `is_deleted = 1` ditolak setelah pencarian DB | 🟢 PASS |
| 1.4 | `validateProfileResetToken_withEmptyToken_returnsNull` | `AuthService::validateProfileResetToken()` ([AuthService.php:L180](file:///d:/Server/WebApp/siCare/app/Services/AuthService.php#L180)) | Token reset sandi kosong mengembalikan `null` | 🟢 PASS |
| 1.5 | `processRememberMe_withEmptyToken_returnsFalse` | `AuthService::processRememberMe()` ([AuthService.php:L141](file:///d:/Server/WebApp/siCare/app/Services/AuthService.php#L141)) | Cookie token remember-me kosong mengembalikan `false` | 🟢 PASS |

---

## 2. Modul 2: Absensi & Geofencing (AttendanceServiceTest)

- **Class Target**: `App\Services\AttendanceService`
- **File Test**: [AttendanceServiceTest.php](file:///d:/Server/WebApp/siCare/tests/Unit/Services/AttendanceServiceTest.php)
- **Status Eksekusi**: 🟢 **9 / 9 PASS (100%)**

### 📝 Detail Skenario Uji:

| No | Nama Method Test | Fungsi Target di Kode Produksi | Hasil Ekspektasi | Status |
|---|---|---|---|---|
| 2.1 | `calculateDistance_withIdenticalCoordinates_returnsZero` | `AttendanceService::calculateDistance()` ([AttendanceService.php:L19](file:///d:/Server/WebApp/siCare/app/Services/AttendanceService.php#L19)) | Jarak antara 2 titik GPS identik adalah 0.0 meter | 🟢 PASS |
| 2.2 | `calculateDistance_withKnownPoints_returnsApproximateDistance` | `AttendanceService::calculateDistance()` ([AttendanceService.php:L19](file:///d:/Server/WebApp/siCare/app/Services/AttendanceService.php#L19)) | Perhitungan Haversine dua titik berjarak ~122m akurat | 🟢 PASS |
| 2.3 | `isWithinGeofence_whenUserAtOffice_returnsTrue` | `AttendanceService::isWithinGeofence()` ([AttendanceService.php:L37](file:///d:/Server/WebApp/siCare/app/Services/AttendanceService.php#L37)) | User pada koordinat kantor diterima di geofence (`true`) | 🟢 PASS |
| 2.4 | `isWithinGeofence_whenUserFarFromOffice_returnsFalse` | `AttendanceService::isWithinGeofence()` ([AttendanceService.php:L37](file:///d:/Server/WebApp/siCare/app/Services/AttendanceService.php#L37)) | User berjarak 550m ditolak di luar radius 150m (`false`) | 🟢 PASS |
| 2.5 | `isWithinGeofence_whenUserAtExactBoundary_returnsTrue` | `AttendanceService::isWithinGeofence()` ([AttendanceService.php:L39](file:///d:/Server/WebApp/siCare/app/Services/AttendanceService.php#L39)) | User pada batas radius 150m tetap diterima (`true`) | 🟢 PASS |
| 2.6 | `evaluateClockInStatus_beforeWorkStart_returnsTepat` | `AttendanceService::evaluateClockInStatus()` ([AttendanceService.php:L45](file:///d:/Server/WebApp/siCare/app/Services/AttendanceService.php#L45)) | Clock-in 07:50:00 (jam kerja 08:00) = `'tepat waktu'` | 🟢 PASS |
| 2.7 | `evaluateClockInStatus_atGracePeriodBoundary_returnsTepat` | `AttendanceService::evaluateClockInStatus()` ([AttendanceService.php:L50](file:///d:/Server/WebApp/siCare/app/Services/AttendanceService.php#L50)) | Clock-in tepat di batas grace period 08:15 = `'tepat waktu'` | 🟢 PASS |
| 2.8 | `evaluateClockInStatus_oneMinuteAfterGrace_returnsTerlambat` | `AttendanceService::evaluateClockInStatus()` ([AttendanceService.php:L53](file:///d:/Server/WebApp/siCare/app/Services/AttendanceService.php#L53)) | Clock-in 08:16:00 (lewat 1 menit grace) = `'terlambat'` | 🟢 PASS |
| 2.9 | `evaluateClockInStatus_atNoon_returnsTerlambat` | `AttendanceService::evaluateClockInStatus()` ([AttendanceService.php:L45](file:///d:/Server/WebApp/siCare/app/Services/AttendanceService.php#L45)) | Clock-in siang hari (11:00:00) = `'terlambat'` | 🟢 PASS |

---

## 3. Modul Performance SLA & Query Efficiency (PerformanceAndQueryEfficiencyTest)

- **Class Target**: `AttendanceService`, `LeaveService`, `PayrollService`, `AuthService`
- **File Test**: [PerformanceAndQueryEfficiencyTest.php](file:///d:/Server/WebApp/siCare/tests/Unit/Services/PerformanceAndQueryEfficiencyTest.php)
- **Status Eksekusi**: 🟢 **5 / 5 PASS (100%)**

### 📝 Detail Skenario Uji SLA & Efisiensi Query:

| No | Nama Method Test | Fungsi Target / SLA | Hasil Ekspektasi | Status |
|---|---|---|---|---|
| 3.1 | `menuAttendance_bulkEvaluationPerformance_isUnderOneSecond` | Batch evaluasi status absensi | Selesai dalam **< 1.000 ms** (SLA < 1 Detik) | 🟢 PASS |
| 3.2 | `menuGeofence_bulkDistanceCalculationPerformance_isUnderOneSecond` | Batch kalkulasi jarak GPS Haversine | Selesai dalam **< 1.000 ms** (SLA < 1 Detik) | 🟢 PASS |
| 3.3 | `menuLeave_bulkDurationCalculationPerformance_isUnderOneSecond` | Batch kalkulasi hari kerja cuti | Selesai dalam **< 1.000 ms** (SLA < 1 Detik) | 🟢 PASS |
| 3.4 | `menuPayroll_bulkPayrollCalculationPerformance_isUnderOneSecond` | Batch kalkulasi slip & potongan gaji | Selesai dalam **< 1.000 ms** (SLA < 1 Detik) | 🟢 PASS |
| 3.5 | `queryOptimization_usesPreparedStatementsWithIndexedKeys` | PDO Query Execution & Prepared Statements | Menggunakan parameter binding terindeks (`WHERE email = :email`) & bebas slow scan | 🟢 PASS |

---

## 4. Modul Audit Clean Code (CleanCodeComplianceTest)

- **Class Target**: Kode Produksi (`app/Services/`, `app/Models/`, `app/`)
- **File Test**: [CleanCodeComplianceTest.php](file:///d:/Server/WebApp/siCare/tests/Unit/Quality/CleanCodeComplianceTest.php)
- **Status Eksekusi**: 🟢 **5 / 5 PASS (100%)**

### 📝 Detail Skenario Uji Quality & Clean Code:

| No | Nama Method Test | Aturan Clean Code | Hasil Ekspektasi | Status |
|---|---|---|---|---|
| 4.1 | `codebase_hasNoLeftoverDebugStatements` | Bebas dari debug statements (`var_dump`, `print_r`, `eval`, `dd`) | Tidak ada sisa debug di `app/` | 🟢 PASS |
| 4.2 | `servicesAndModels_followPsr4FilenameConvention` | Kepatuhan nama file .php persis sama dengan nama Class | Mendukung autoloader PSR-4 | 🟢 PASS |
| 4.3 | `servicePublicMethods_havePhpDocComments` | Dokumentasi PHPDoc wajib di setiap public method Service | Memiliki deskripsi fungsi lengkap | 🟢 PASS |
| 4.4 | `serviceMethods_haveTypeHintDeclarations` | Deklarasi Return Type & Parameter Type Hinting | Terbebas dari TypeError & Strict Type Safety | 🟢 PASS |
| 4.5 | `serviceMethods_doNotExceedMaxLineLength` | Single Responsibility Principle (Maksimal 120 baris per method) | Tidak ada method monster / god method | 🟢 PASS |

---

## 5. Modul 5: Cuti & Kalkulasi Hari Kerja (LeaveServiceTest) ✨ Baru

- **Class Target**: `App\Services\LeaveService`
- **File Test**: [LeaveServiceTest.php](file:///d:/Server/WebApp/siCare/tests/Unit/Services/LeaveServiceTest.php)
- **Status Eksekusi**: 🟢 **5 / 5 PASS (100%)**

### 📝 Detail Skenario Uji:

| No | Nama Method Test | Fungsi Target di Kode Produksi | Hasil Ekspektasi | Status |
|---|---|---|---|---|
| 5.1 | `calculateLeaveDuration_startAfterEnd_returnsZero` | `LeaveService::calculateLeaveDuration()` ([LeaveService.php:L23](file:///d:/Server/WebApp/siCare/app/Services/LeaveService.php#L23)) | Guard clause: tanggal mulai > selesai → return 0 | 🟢 PASS |
| 5.2 | `calculateLeaveDuration_sameDateBothEnds_returnsOne` | `LeaveService::calculateLeaveDuration()` ([LeaveService.php:L26](file:///d:/Server/WebApp/siCare/app/Services/LeaveService.php#L26)) | Cuti 1 hari di hari kerja → durasi 1 | 🟢 PASS |
| 5.3 | `calculateLeaveDuration_oneWeekRange_returnsFiveWorkdays` | `LeaveService::calculateLeaveDuration()` ([LeaveService.php:L30](file:///d:/Server/WebApp/siCare/app/Services/LeaveService.php#L30)) | Senin–Minggu (7 hari kalender) → 5 hari kerja efektif | 🟢 PASS |
| 5.4 | `calculateLeaveDuration_withCompanyHoliday_excludesHoliday` | `LeaveService::calculateLeaveDuration()` ([LeaveService.php:L30](file:///d:/Server/WebApp/siCare/app/Services/LeaveService.php#L30)) | Libur nasional (17 Agustus) dieksklusi dari hitungan | 🟢 PASS |
| 5.5 | `calculateLeaveDuration_withCustomWeeklyHolidays_skipsCorrectDays` | `LeaveService::calculateLeaveDuration()` ([LeaveService.php:L30](file:///d:/Server/WebApp/siCare/app/Services/LeaveService.php#L30)) | Custom hari libur mingguan (Jumat+Sabtu) dihormati | 🟢 PASS |

---

## 6. Modul 6: Penggajian & Potongan (PayrollServiceTest) ✨ Baru

- **Class Target**: `App\Services\PayrollService`
- **File Test**: [PayrollServiceTest.php](file:///d:/Server/WebApp/siCare/tests/Unit/Services/PayrollServiceTest.php)
- **Status Eksekusi**: 🟢 **11 / 11 PASS (100%)** *(termasuk 3 DataProvider case)*

### 📝 Detail Skenario Uji:

| No | Nama Method Test | Fungsi Target di Kode Produksi | Hasil Ekspektasi | Status |
|---|---|---|---|---|
| 6.1 | `calculateBpjsTk_withStandardSalary_returnsCorrectDeduction` | `PayrollService::calculateBpjsTk()` ([PayrollService.php:L19](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L19)) | Rp 5.000.000 × 2% = Rp 100.000 | 🟢 PASS |
| 6.2 | `calculateBpjsTk_withZeroSalary_returnsZero` | `PayrollService::calculateBpjsTk()` ([PayrollService.php:L19](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L19)) | Gaji Rp 0 → potongan BPJS TK Rp 0 | 🟢 PASS |
| 6.3 | `calculateBpjsKes_variousSalaries_returnsCorrectOnePercent` (gaji minimum UMP) | `PayrollService::calculateBpjsKes()` ([PayrollService.php:L26](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L26)) | Rp 2.000.000 × 1% = Rp 20.000 | 🟢 PASS |
| 6.4 | `calculateBpjsKes_variousSalaries_returnsCorrectOnePercent` (gaji menengah) | `PayrollService::calculateBpjsKes()` ([PayrollService.php:L26](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L26)) | Rp 5.000.000 × 1% = Rp 50.000 | 🟢 PASS |
| 6.5 | `calculateBpjsKes_variousSalaries_returnsCorrectOnePercent` (gaji level senior) | `PayrollService::calculateBpjsKes()` ([PayrollService.php:L26](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L26)) | Rp 20.000.000 × 1% = Rp 200.000 | 🟢 PASS |
| 6.6 | `calculatePph21_withTaxableIncome_returnsCorrectTax` | `PayrollService::calculatePph21()` ([PayrollService.php:L33](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L33)) | PKP Rp 5.000.000 × 2.5% = Rp 125.000 | 🟢 PASS |
| 6.7 | `calculatePph21_withZeroIncome_returnsZero` | `PayrollService::calculatePph21()` ([PayrollService.php:L33](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L33)) | PKP Rp 0 → PPh21 Rp 0 | 🟢 PASS |
| 6.8 | `calculateNetSalary_withAllPositiveComponents_returnsCorrectNet` | `PayrollService::calculateNetSalary()` ([PayrollService.php:L40](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L40)) | Gross Rp 6.000.000 – Rp 275.000 = Rp 5.725.000 | 🟢 PASS |
| 6.9 | `calculateNetSalary_whenDeductionsExceedGross_returnsZero` | `PayrollService::calculateNetSalary()` ([PayrollService.php:L42](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L42)) | Deductions > Gross → net salary = Rp 0 (tidak negatif) | 🟢 PASS |
| 6.10 | `calculateNetSalary_withNoExtrasAndNoDeductions_returnsBaseSalary` | `PayrollService::calculateNetSalary()` ([PayrollService.php:L40](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L40)) | Base Rp 3.500.000, no extras/deductions → net = Rp 3.500.000 | 🟢 PASS |
| 6.11 | `calculateNetSalary_withOnlyBonus_returnsBasePlusMinusDeductions` | `PayrollService::calculateNetSalary()` ([PayrollService.php:L40](file:///d:/Server/WebApp/siCare/app/Services/PayrollService.php#L40)) | Base Rp 4.000.000 + Bonus Rp 1.000.000 – Rp 150.000 = Rp 4.850.000 | 🟢 PASS |

---

## 7. Modul 7: Global Helper Functions (HelpersTest) ✨ Baru

- **Class Target**: `app/helpers.php` — fungsi global pure
- **File Test**: [HelpersTest.php](file:///d:/Server/WebApp/siCare/tests/Unit/Helpers/HelpersTest.php)
- **Status Eksekusi**: 🟢 **6 / 6 PASS (100%)**

### 📝 Detail Skenario Uji:

| No | Nama Method Test | Fungsi Target di Kode Produksi | Hasil Ekspektasi | Status |
|---|---|---|---|---|
| 7.1 | `e_withHtmlSpecialChars_returnsEscapedString` | `helpers::e()` ([helpers.php:L29](file:///d:/Server/WebApp/siCare/app/helpers.php#L29)) | `<script>` di-escape menjadi `&lt;script&gt;` (anti-XSS) | 🟢 PASS |
| 7.2 | `e_withNullInput_returnsEmptyString` | `helpers::e()` ([helpers.php:L30](file:///d:/Server/WebApp/siCare/app/helpers.php#L30)) | `null` → string kosong `""` tanpa error | 🟢 PASS |
| 7.3 | `resolveProfilePicture_withGooglePic_returnsGooglePicDirectly` | `helpers::resolveProfilePicture()` ([helpers.php:L152](file:///d:/Server/WebApp/siCare/app/helpers.php#L152)) | Google URL langsung dikembalikan (short-circuit, no network call) | 🟢 PASS |
| 7.4 | `validateAndFormatPhone_withLeadingZero_convertsToInternational` | `helpers::validate_and_format_phone()` ([helpers.php:L205](file:///d:/Server/WebApp/siCare/app/helpers.php#L205)) | `081234567890` → `6281234567890` (format E.164) | 🟢 PASS |
| 7.5 | `validateAndFormatPhone_withNonDigitChars_returnsFalseWithError` | `helpers::validate_and_format_phone()` ([helpers.php:L194](file:///d:/Server/WebApp/siCare/app/helpers.php#L194)) | Nomor dengan spasi → `false` + pesan error | 🟢 PASS |
| 7.6 | `validateAndFormatPhone_withTooShortNumber_returnsFalseWithError` | `helpers::validate_and_format_phone()` ([helpers.php:L200](file:///d:/Server/WebApp/siCare/app/helpers.php#L200)) | Nomor < 10 digit → `false` + pesan error batas minimum | 🟢 PASS |

---

## 8. Modul 8: User Model — UUID Generation (UserModelTest) ✨ Baru

- **Class Target**: `App\Models\User`
- **File Test**: [UserModelTest.php](file:///d:/Server/WebApp/siCare/tests/Unit/Models/UserModelTest.php)
- **Status Eksekusi**: 🟢 **2 / 2 PASS (100%)**

### 📝 Detail Skenario Uji:

| No | Nama Method Test | Fungsi Target di Kode Produksi | Hasil Ekspektasi | Status |
|---|---|---|---|---|
| 8.1 | `generateUuid_returnsValidUuidV4Format` | `User::generateUuid()` ([User.php:L15](file:///d:/Server/WebApp/siCare/app/Models/User.php#L15)) | Output cocok dengan regex UUID v4 RFC 4122 | 🟢 PASS |
| 8.2 | `generateUuid_calledTwice_returnsTwoDifferentValues` | `User::generateUuid()` ([User.php:L15](file:///d:/Server/WebApp/siCare/app/Models/User.php#L15)) | Dua UUID berurutan selalu menghasilkan nilai yang berbeda (uniqueness) | 🟢 PASS |

---

## 🖥️ Raw Output Log PHPUnit (Final Run)

```text
PHPUnit 11.5.56 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.5
Configuration: D:\Server\WebApp\siCare\phpunit.xml

................................................                  48 / 48 (100%)

Time: 00:01.422, Memory: 10.00 MB

Attendance Service (Tests\Unit\Services\AttendanceService)
 ✔ CalculateDistance withIdenticalCoordinates returnsZero
 ✔ CalculateDistance withKnownPoints returnsApproximateDistance
 ✔ IsWithinGeofence whenUserAtOffice returnsTrue
 ✔ IsWithinGeofence whenUserFarFromOffice returnsFalse
 ✔ IsWithinGeofence whenUserAtExactBoundary returnsTrue
 ✔ EvaluateClockInStatus beforeWorkStart returnsTepat
 ✔ EvaluateClockInStatus atGracePeriodBoundary returnsTepat
 ✔ EvaluateClockInStatus oneMinuteAfterGrace returnsTerlambat
 ✔ EvaluateClockInStatus atNoon returnsTerlambat

Auth Service (Tests\Unit\Services\AuthService)
 ✔ Authenticate withEmptyEmail returnsFailureWithoutDbCall
 ✔ Authenticate withEmptyPassword returnsFailure
 ✔ Authenticate withSoftDeletedAccount returnsFailure
 ✔ ValidateProfileResetToken withEmptyToken returnsNull
 ✔ ProcessRememberMe withEmptyToken returnsFalse

Clean Code Compliance (Tests\Unit\Quality\CleanCodeCompliance)
 ✔ Codebase hasNoLeftoverDebugStatements
 ✔ ServicesAndModels followPsr4FilenameConvention
 ✔ ServicePublicMethods havePhpDocComments
 ✔ ServiceMethods haveTypeHintDeclarations
 ✔ ServiceMethods doNotExceedMaxLineLength

Helpers (Tests\Unit\Helpers\Helpers)
 ✔ E withHtmlSpecialChars returnsEscapedString
 ✔ E withNullInput returnsEmptyString
 ✔ ResolveProfilePicture withGooglePic returnsGooglePicDirectly
 ✔ ValidateAndFormatPhone withLeadingZero convertsToInternational
 ✔ ValidateAndFormatPhone withNonDigitChars returnsFalseWithError
 ✔ ValidateAndFormatPhone withTooShortNumber returnsFalseWithError

Leave Service (Tests\Unit\Services\LeaveService)
 ✔ CalculateLeaveDuration startAfterEnd returnsZero
 ✔ CalculateLeaveDuration sameDateBothEnds returnsOne
 ✔ CalculateLeaveDuration oneWeekRange returnsFiveWorkdays
 ✔ CalculateLeaveDuration withCompanyHoliday excludesHoliday
 ✔ CalculateLeaveDuration withCustomWeeklyHolidays skipsCorrectDays

Payroll Service (Tests\Unit\Services\PayrollService)
 ✔ CalculateBpjsTk withStandardSalary returnsCorrectDeduction
 ✔ CalculateBpjsTk withZeroSalary returnsZero
 ✔ CalculateBpjsKes variousSalaries returnsCorrectOnePercent with data set "gaji minimum UMP"
 ✔ CalculateBpjsKes variousSalaries returnsCorrectOnePercent with data set "gaji menengah"
 ✔ CalculateBpjsKes variousSalaries returnsCorrectOnePercent with data set "gaji level senior"
 ✔ CalculatePph21 withTaxableIncome returnsCorrectTax
 ✔ CalculatePph21 withZeroIncome returnsZero
 ✔ CalculateNetSalary withAllPositiveComponents returnsCorrectNet
 ✔ CalculateNetSalary whenDeductionsExceedGross returnsZero
 ✔ CalculateNetSalary withNoExtrasAndNoDeductions returnsBaseSalary
 ✔ CalculateNetSalary withOnlyBonus returnsBasePlusMinusDeductions

Performance And Query Efficiency (Tests\Unit\Services\PerformanceAndQueryEfficiency)
 ✔ MenuAttendance bulkEvaluationPerformance isUnderOneSecond
 ✔ MenuGeofence bulkDistanceCalculationPerformance isUnderOneSecond
 ✔ MenuLeave bulkDurationCalculationPerformance isUnderOneSecond
 ✔ MenuPayroll bulkPayrollCalculationPerformance isUnderOneSecond
 ✔ QueryOptimization usesPreparedStatementsWithIndexedKeys

User Model (Tests\Unit\Models\UserModel)
 ✔ GenerateUuid returnsValidUuidV4Format
 ✔ GenerateUuid calledTwice returnsTwoDifferentValues

OK (48 tests, 70 assertions)
```
