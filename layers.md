# siCare — Layer Architecture Mapping (3 Layer vs MVC)

> Alur bersifat **top-down dan satu arah**: Presentation → Business → Data.
> Tidak ada komunikasi balik antar layer (no bidirectional dependency).

---

## 🗺️ Peta Hubungan: 3-Layer Architecture vs Pattern MVC

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        1. PRESENTATION LAYER                            │
│ ┌─────────────────────────────┐         ┌─────────────────────────────┐ │
│ │  View (HTML/PHP Templates)  │         │  Front Controller / Router  │ │
│ │  resources/views/...        │         │  public/index.php           │ │
│ └─────────────────────────────┘         └─────────────────────────────┘ │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │  1. Request / User Input
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          2. BUSINESS LAYER                              │
│ ┌─────────────────────────────┐         ┌─────────────────────────────┐ │
│ │  Controllers (HTTP Handlers)│ ──────> │  Services / Helpers         │ │
│ │  app/Controllers/...        │         │  app/Services/...           │ │
│ └─────────────────────────────┘         └─────────────────────────────┘ │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │  2. Data Fetch / Query Request
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                            3. DATA LAYER                                │
│ ┌─────────────────────────────┐         ┌─────────────────────────────┐ │
│ │  Models / Entities          │ ──────> │  Database Connection (PDO)  │ │
│ │  app/Models/...             │         │  app/Config/Database.php    │ │
│ └─────────────────────────────┘         └─────────────────────────────┘ │
│                                         ┌─────────────────────────────┐ │
│                                         │  Tables (MySQL / MariaDB)   │ │
│                                         │  users, attendances, etc.   │ │
│                                         └─────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 📌 Penjelasan Pemetaan Component MVC ke 3 Layer

| 3 Layer | Komponen MVC / Codebase | Peran & Tanggung Jawab Utama |
|---|---|---|
| **1. Presentation Layer** | **View** (`resources/views/`)<br>**Router** (`public/index.php`) | • Menampilkan antarmuka pengguna (UI/HTML)<br>• Menerima input dari browser/user<br>• Meneruskan request HTTP ke Controller<br>• **Dilarang keras** memanggil Database / PDO secara langsung! |
| **2. Business Layer** | **Controller** (`app/Controllers/`)<br>**Service Layer** (`app/Services/`, `app/Session/`, `app/Config/JwtHelper.php`) | • Memproses aturan bisnis, perhitungan, validasi, dan otorisasi<br>• Menghubungkan permintaan dari View/Router ke Service/Model<br>• Mempersiapkan data murni untuk disajikan ke Presentation Layer |
| **3. Data Layer** | **Model** (`app/Models/`)<br>**PDO Singleton** (`app/Config/Database.php`)<br>**Schema & Migrations** (`database/`) | • Menangani query SQL (SELECT, INSERT, UPDATE, DELETE)<br>• Menyimpan dan mengambil data mentah dari database<br>• **Dilarang** tahu tentang tampilan HTML/View |

---

## 🔄 Aturan Alur Data (Top-Down Unidirectional)

1. **User → Presentation Layer**: User melakukan aksi di browser (misal klik tombol *Clock In* atau membuka halaman *Settings*).
2. **Presentation Layer → Business Layer**: `index.php` mengarahkan request ke Controller di Business Layer (misal `AttendanceController`).
3. **Business Layer Internal**: Controller memanggil Service/Helper (misal `ViewHelper` atau `JwtHelper`) untuk validasi, otorisasi, dan kalkulasi aturan bisnis.
4. **Business Layer → Data Layer**: Controller/Service meminta data dari Model/Database (`app/Models/` atau `Database.php`).
5. **Data Layer**: Menjalankan query SQL ke database MySQL dan mengembalikan hasilnya kembali ke Business Layer.
6. **Business Layer → Presentation Layer**: Controller mengirimkan variabel data murni ke View untuk di-render menjadi HTML.

---

## 1. Presentation Layer

Bertanggung jawab menampilkan UI kepada pengguna dan meneruskan input/request ke Business Layer.

### Entry Point & Router
| File | Peran |
|---|---|
| `public/index.php` | Front controller tunggal: menangkap semua request, validasi JWT pada POST, dispatch ke controller yang sesuai |
| `public/.htaccess` | Redirect semua request ke `index.php` (URL rewriting) |
| `public/sw.js` | Service Worker — caching aset untuk PWA |
| `public/manifest.json` | PWA manifest (nama app, icon, theme) |

### Views (Templates PHP)
| Direktori / File | Konten |
|---|---|
| `resources/views/layouts/app.php` | Layout utama aplikasi (authenticated users): sidebar, header, slot konten |
| `resources/views/layouts/guest.php` | Layout halaman publik / tamu |
| `resources/views/parts/app_sidebar.php` | Navigasi sidebar — render menu berdasarkan role |
| `resources/views/parts/app_header.php` | Header aplikasi (notifikasi, profil) |
| `resources/views/parts/guest_header.php` | Header halaman publik |
| `resources/views/parts/guest_footer.php` | Footer halaman publik |
| `resources/views/auth/` | Login, register, forgot password |
| `resources/views/dashboard/` | Halaman dashboard per role |
| `resources/views/pages/landing.php` | Landing page publik |
| `resources/views/pages/employee/attendance.php` | Absensi ESS karyawan |
| `resources/views/pages/employee/leaves.php` | Pengajuan cuti karyawan |
| `resources/views/pages/employee/finance.php` | Slip gaji & keuangan karyawan |
| `resources/views/pages/employee/profile.php` | Profil & data diri karyawan |
| `resources/views/pages/employee/reimbursements.php` | Pengajuan reimbursement |
| `resources/views/pages/employee/reflection.php` | Jurnal refleksi karyawan |
| `resources/views/pages/hrops/attendance.php` | Manajemen absensi oleh HR Ops |
| `resources/views/pages/hrops/employees.php` | Data karyawan oleh HR Ops |
| `resources/views/pages/hrops/leaves.php` | Manajemen cuti oleh HR Ops |
| `resources/views/pages/hrops/payroll.php` | Manajemen penggajian oleh HR Ops |
| `resources/views/pages/hrops/settings.php` | Pengaturan HR |
| `resources/views/pages/hrops/verifications.php` | Verifikasi dokumen/data |
| `resources/views/pages/manager/approvals.php` | Approval request oleh Manager |
| `resources/views/pages/manager/team.php` | Manajemen tim |
| `resources/views/pages/manager/reflection.php` | Refleksi tim oleh Manager |
| `resources/views/pages/admin/users.php` | Manajemen user oleh Admin |
| `resources/views/pages/admin/departments.php` | Manajemen departemen |
| `resources/views/pages/admin/settings.php` | Pengaturan sistem oleh Admin |
| `resources/views/pages/superadmin/users.php` | Manajemen user level Super Admin |
| `resources/views/pages/superadmin/audit.php` | Audit log sistem |
| `resources/views/pages/superadmin/global-approval.php` | Approval global |
| `resources/views/pages/superadmin/settings.php` | Pengaturan global |
| `resources/views/pages/executive/approvals.php` | Dashboard eksekutif |
| `resources/views/pages/errors/` | Halaman error (403, 404, dsb.) |
| `resources/views/pages/changelogs.php` | Halaman changelog aplikasi |

### Aset Frontend
| Direktori | Konten |
|---|---|
| `resources/css/` | Sumber CSS sebelum diproses |
| `resources/js/` | Sumber JavaScript sebelum diproses |
| `public/css/` | CSS publik (siap pakai) |
| `public/js/` | JavaScript publik (siap pakai) |
| `public/images/` | Gambar & ikon |
| `public/ringtones/` | Aset audio (notifikasi) |

---

## 2. Business Layer

Menerima request dari Presentation Layer, menjalankan logika bisnis, validasi, otorisasi, kalkulasi, lalu mengambil/menyimpan data melalui Data Layer.

### Controllers (HTTP Logic per Fitur)
| File | Tanggung Jawab |
|---|---|
| `app/Controllers/AuthController.php` | Login, logout, register, remember-me, reset password, JWT issue |
| `app/Controllers/AttendanceController.php` | Clock-in/out, validasi GPS/WiFi, rekap absensi, koreksi, holiday |
| `app/Controllers/LeaveController.php` | Pengajuan, approval, penolakan, saldo cuti |
| `app/Controllers/PayrollController.php` | Kalkulasi gaji, komponen payroll, slip gaji, export |
| `app/Controllers/ReimbursementController.php` | Pengajuan, review, approval reimbursement |
| `app/Controllers/ApprovalController.php` | Workflow multi-level approval lintas fitur |
| `app/Controllers/CorrectionController.php` | Koreksi data absensi & kehadiran |
| `app/Controllers/DashboardController.php` | Agregasi data ringkasan per role untuk dashboard |
| `app/Controllers/DepartmentController.php` | CRUD departemen, struktur organisasi |
| `app/Controllers/EmployeeMasterController.php` | Master data karyawan, onboarding, dokumen |
| `app/Controllers/ReflectionController.php` | Jurnal refleksi karyawan & review manajer |
| `app/Controllers/SettingsController.php` | Pengaturan sistem (global settings, jam kerja, lokasi kantor) |
| `app/Controllers/AuditLogController.php` | Pencatatan & tampilan audit trail sistem |
| `app/Controllers/NotificationController.php` | Push notification & in-app notification |

### Services & Helpers (Business Logic & View Data Services)
| File / Class | Tanggung Jawab |
|---|---|
| `app/Services/ViewHelper.php` | Jembatan penyedia data UI untuk Presentation Layer (settings, menu, user profile) |
| `app/Services/AuthService.php` | Autentikasi, brute-force protection, session initialization, remember-me token, password reset |
| `app/Services/AttendanceService.php` | Kalkulasi jarak GPS (Haversine), validasi geofencing, evaluasi status keterlambatan, rekap harian |
| `app/Services/PayrollService.php` | Kalkulasi BPJS TK (2%), BPJS Kes (1%), PPh21 (2.5%), gaji bersih (net salary) |
| `app/Services/LeaveService.php` | Kalkulasi durasi hari kerja (tanpa libur), deteksi overlapping cuti, saldo cuti tahunan |
| `app/helpers.php` | Fungsi utilitas global (e() XSS escape, csrf_token, csrfField, db_query, validate_password_strength) |
| `app/Session/UnifiedSessionHandler.php` | Custom session handler (Database + Redis hybrid), remember-me |
| `app/Config/Container.php` | Container DI (Dependency Injection) PSR-11 compliant dengan autowiring via Reflection |
| `app/Config/JwtHelper.php` | Generate & validasi JWT token untuk otorisasi CRUD (HS256, env-based secret) |
| `app/Config/SimpleCache.php` | In-memory / file cache ringan untuk optimasi query berulang |


---

## 3. Data Layer

Bertanggung jawab atas seluruh akses ke penyimpanan data: database, migrasi skema, dan seeding. **Tidak pernah berkomunikasi langsung dengan Presentation Layer.**

### Koneksi & Konfigurasi Database
| File | Tanggung Jawab |
|---|---|
| `app/Config/Database.php` | Singleton PDO — satu koneksi MySQL/MariaDB untuk seluruh aplikasi |
| `.env` | Variabel lingkungan: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD |

### Migrasi & Skema
| File | Tanggung Jawab |
|---|---|
| `app/Config/Migration.php` | Kontrak/interface migrasi |
| `app/Config/Migrator.php` | Runner migrasi — eksekusi file migrasi secara berurutan |
| `database/update_schema.php` | Script update skema incremental (digunakan via route database/migrate) |
| `database/schema.sql` | Skema awal database |
| `sicare_db.sql` | Dump database lengkap (termasuk data awal) |

### Model
| File | Tanggung Jawab |
|---|---|
| `app/Models/User.php` | Representasi entitas User (relasi data, query user, UUID generation) |
| `app/Models/Attendance.php` | Representasi entitas presensi (clock-in/out per user per tanggal) |
| `app/Models/LeaveRequest.php` | Representasi entitas pengajuan cuti (query per user, pending count) |
| `app/Models/Payroll.php` | Representasi entitas penggajian (query slip per user & tahun) |
| `app/Models/Reimbursement.php` | Representasi entitas klaim reimbursement (query per user) |
| `app/Models/Department.php` | Representasi entitas departemen (getAll, getLevel1) |


### Tabel Database Utama
| Tabel | Keterangan |
|---|---|
| `users` | Data seluruh karyawan & akun sistem |
| `roles` | Definisi role: superadmin, admin, hrops, manager, employee, executive |
| `sys_menus` | Menu navigasi & permission akses per role |
| `departments` | Struktur departemen organisasi |
| `attendances` | Rekaman absensi harian karyawan |
| `leaves` | Pengajuan dan histori cuti |
| `leave_balances` | Saldo cuti per karyawan per tahun |
| `payroll_components` | Komponen gaji (tunjangan, potongan) |
| `payroll_runs` | Rekap penggajian per periode |
| `reimbursements` | Pengajuan dan status reimbursement |
| `approvals` | Workflow approval lintas fitur |
| `reflections` | Jurnal refleksi karyawan |
| `global_settings` | Konfigurasi sistem (jam kerja, lokasi, dll.) |
| `audit_logs` | Log seluruh aksi penting di sistem |
| `login_attempts` | Guard brute-force login |
| `company_holidays` | Hari libur perusahaan |
| `portal_applications` | Aplikasi yang ditampilkan di portal karyawan |

---

## Alur Request End-to-End (Contoh: Karyawan Clock-In)

```
[Browser Karyawan]
      │  POST /attendance/clock-in  (+ JWT token di header)
      ▼
┌─────────────────────────────┐
│   PRESENTATION LAYER        │
│  public/index.php           │  ← Validasi JWT, dispatch request
│  resources/views/           │  ← Tampilkan response (HTML/JSON)
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│   BUSINESS LAYER            │
│  AttendanceController       │  ← Validasi GPS, WiFi, jam kerja
│  helpers.php                │  ← Hitung status: Tepat Waktu / Terlambat
│  JwtHelper.php              │  ← Verifikasi token
│  SimpleCache.php            │  ← Cache settings kantor
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│   DATA LAYER                │
│  Database.php (PDO)         │  ← INSERT ke tabel attendances
│  global_settings table      │  ← READ konfigurasi kantor
│  audit_logs table           │  ← INSERT log aksi
└─────────────────────────────┘
```
