content = """# Roadmap Pengembangan Sistem HRIS & ATS (Corporate Edition)

> **PETUNJUK UNTUK AI DEVELOPER:**
> Kerjakan pengembangan aplikasi ini **Tepat Sesuai Urutan Bab (Step-by-Step)**. Jangan melompat ke Bab berikutnya sebelum seluruh fitur, logika bisnis, dan pengamanan (*security constraints*) di Bab yang sedang dikerjakan selesai 100% dan diuji. Setiap bab di bawah ini telah dilengkapi dengan detail Menu, Relasi Antar-Menu, dan Logika Alur Programnya.

---

## BAB 1: FONDASI & SISTEM MIGRASI KEBAL (IDEMPOTENT MIGRATIONS)
*Fokus Utama: Membangun skrip migrasi database yang aman dari kegagalan saat terjadi loncat versi.*

- **Menu Terkait:** (Belum ada menu UI, fokus pada *Backend CLI/Script*).
- **Logika Alur Program:**
  1. Eksekusi skrip migrasi menggunakan perintah `CREATE TABLE IF NOT EXISTS`.
  2. Untuk modifikasi (*Alter*), *query* wajib mengecek tabel `INFORMATION_SCHEMA.COLUMNS` terlebih dahulu. Jika kolom belum ada, baru eksekusi `ALTER TABLE ADD`.
  3. *Generate* struktur awal tabel: `users`, `user_profiles`, `roles`, `departments`, `sys_menus`, `menu_permissions`, `audit_logs`, `approval_requests`.
  4. Tetapkan konfigurasi *helper* keamanan: `csrf_token()`, XSS `e()`, dan fungsi validasi file `finfo_open(FILEINFO_MIME_TYPE)`.

---

## BAB 2: AUTENTIKASI & KENDALI "SUPERADMIN" (GOD MODE)
*Fokus Utama: Mengamankan gerbang masuk dan membuat role absolut.*

- **Menu Terkait:** 
  - *Login Portal* (Global).
  - *Global Audit Logs* (Hanya Superadmin & Komite Audit Eksekutif).
  - *System Configuration* (Hanya Superadmin).
- **Relasi Menu:** Menu `System Configuration` mengatur parameter yang memengaruhi seluruh sistem (contoh: maksimal *upload* file kandidat/staf).
- **Logika Alur Program:**
  1. **Flow Login:** *User* memasukkan Email & Password $\rightarrow$ Validasi *Bcrypt* $\rightarrow$ Set Session (Simpan UUID User, UUID Role, dan UUID Department).
  2. **Flow Global Audit Logs:** *Query SELECT* murni ke tabel `audit_logs` *ORDER BY created_at DESC*.
  3. **Flow Clear Logs:** Superadmin klik "Clear Logs" $\rightarrow$ Konfirmasi SweetAlert2 $\rightarrow$ Eksekusi `TRUNCATE audit_logs` $\rightarrow$ **Sistem Otomatis** menjalankan `INSERT INTO audit_logs` berisi catatan: *"Superadmin [Nama] telah menghapus seluruh log sistem pada [Timestamp]"*.

---

## BAB 3: DYNAMIC MENU ENGINE & MASTER DATA (ANTI-HARDCODE)
*Fokus Utama: Membuat sistem menu yang skalabel tanpa *hardcode*.*

- **Menu Terkait:**
  - *Menu Builder* (Tambah/Edit Menu Sistem).
  - *Role Permission Builder* (Matriks ACL).
- **Relasi Menu:** `superadmin` menentukan menu apa saja yang akan muncul di bilah navigasi (sidebar) milik ke-7 *role* lainnya.
- **Logika Alur Program:**
  1. **Flow Menu Builder:** Form CRUD biasa yang menyimpan data URL *route* dan Ikon Google Material ke tabel `sys_menus`.
  2. **Flow ACL:** Menyimpan relasi ke tabel `menu_permissions` (UUID Menu + UUID Role + UUID Dept).
  3. **Flow Render Sidebar:** Saat *user login*, sistem menjalankan *query*: `SELECT * FROM sys_menus JOIN menu_permissions WHERE role_id = ? AND (department_id = ? OR department_id IS NULL)`.
  4. **Flow Cache Invalidation:** Saat `superadmin` menyimpan perubahan hak akses, sistem WAJIB menjalankan `$cache->delete('sidebar_menu')` agar *cache* terhapus dan UI langsung diperbarui.

---

## BAB 4: MANAJEMEN ORGANISASI & USER (ROLE: ADMIN)
*Fokus Utama: Membangun struktur departemen dan penempatan karyawan.*

- **Menu Terkait:**
  - *Manajemen Organisasi* (Hierarki Divisi 5 Level).
  - *Manajemen Pengguna* (Data internal karyawan).
- **Relasi Menu:** Data dari *Manajemen Organisasi* akan digunakan sebagai *Dropdown* pilihan oleh `recruiter` (saat buka loker) dan oleh `hr_ops` (saat mutasi).
- **Logika Alur Program:**
  1. **Flow Dept Builder:** Penyimpanan menggunakan konsep *Adjacency List*. Pembuatan sub-divisi wajib mengirimkan UUID `parent_id`. 
  2. **Flow Edit User (Trigger Approval):** `admin` mengubah UUID *role* seorang staf dari `employee` menjadi `hiring_manager`. Sistem menahan perubahan ini dengan membuat *record* di `approval_requests` berisi *payload* JSON (Role baru). Tabel `users` belum disentuh.

---

## BAB 5: MATRIKS PERSETUJUAN & ISOLASI (WORKFLOW ACC)
*Fokus Utama: Menghubungkan pengajuan Admin dengan eksekusi Eksekutif.*

- **Menu Terkait:**
  - *Matriks Persetujuan / Approval Center* (Dimiliki oleh role `executive`).
- **Relasi Menu:** Berisi daftar antrean dari aksi yang dilakukan oleh `admin` (Mutasi) dan `hiring_manager` (Penambahan Karyawan Baru).
- **Logika Alur Program:**
  1. **Flow ACC Mutasi/Promosi:** `executive` klik tombol "Approve" $\rightarrow$ Mulai `$db->beginTransaction()` $\rightarrow$ Ekstrak JSON `new_data` dari `approval_requests` $\rightarrow$ `UPDATE users SET role_id = JSON_VAL` $\rightarrow$ `INSERT INTO employment_history` $\rightarrow$ `UPDATE approval_requests SET status = 'APPROVED'` $\rightarrow$ `INSERT INTO audit_logs` $\rightarrow$ `$db->commit()`.

---

## BAB 6: CORE HRIS & ONBOARDING (ROLE: HR OPS)
*Fokus Utama: Pengelolaan kelengkapan legal profil dan pemrosesan administrasi.*

- **Menu Terkait:**
  - *Verifikasi Onboarding*.
  - *Data Correction Queue*.
  - *Payroll Engine*.
- **Relasi Menu:** Data KTP/NPWP disuplai oleh `candidate` dari modul Onboarding; *Data Correction* disuplai oleh `employee` dari modul ESS.
- **Logika Alur Program:**
  1. **Flow Verifikasi Onboarding:** `hr_ops` melihat foto KTP kandidat $\rightarrow$ Klik "Approve" $\rightarrow$ Sistem `UPDATE users SET role_id = [UUID-Employee], employee_id = [Generated ID]` $\rightarrow$ Profil berubah menjadi *Employee* aktif.
  2. **Flow Payroll Engine:** Sistem menjalankan *query SUM* pada tabel presensi dan lembur selama tanggal *cut-off*, memotong pajak (berdasarkan PTKP di `user_profiles`), dan menghasilkan PDF Slip Gaji ke portal masing-masing *user*.

---

## BAB 7: APPLICANT TRACKING SYSTEM (RECRUITER & CANDIDATE)
*Fokus Utama: Membuka gerbang portal publik dan mengelola seleksi pelamar.*

- **Menu Terkait:**
  - *Manpower Requisition* (Oleh `hiring_manager`).
  - *Portal Loker* (Oleh `candidate`).
  - *ATS Pipeline & Jadwal* (Oleh `recruiter`).
  - *Scoring Rubric* (Oleh `hiring_manager`).
- **Relasi Menu:** Estafet berantai: Manajer minta kuota $\rightarrow$ Eksekutif ACC $\rightarrow$ Rekruter buat loker $\rightarrow$ Kandidat daftar $\rightarrow$ Rekruter sortir $\rightarrow$ Manajer nilai wawancara.
- **Logika Alur Program:**
  1. **Flow Lamaran (Candidate):** Formulir pendaftaran $\rightarrow$ *Upload* CV divalidasi MIME Type-nya dengan `finfo` (Maks 10MB) $\rightarrow$ Simpan URL/path terenkripsi ke database $\rightarrow$ Status: `APPLIED`.
  2. **Flow ATS Pipeline:** UI Kanban. Saat `recruiter` menarik kartu kandidat ke kolom "Shortlisted", trigger AJAX mengirimkan UUID kandidat dan UUID status baru $\rightarrow$ `UPDATE status`.

---

## BAB 8: EMPLOYEE SELF-SERVICE (ESS)
*Fokus Utama: Portal kemandirian untuk seluruh lini karyawan operasional.*

- **Menu Terkait:**
  - *Presensi Digital* (Clock In/Out).
  - *Cuti & Klaim*.
  - *Persetujuan Tim* (Khusus `hiring_manager` dan Direktur).
- **Relasi Menu:** Menu Cuti dari `employee` akan muncul di menu *Persetujuan Tim* milik atasannya langsung (Berdasarkan *Adjacency List* Departemen).
- **Logika Alur Program:**
  1. **Flow Isolasi Vertikal/Horizontal:** Saat `hiring_manager` membuka menu *Persetujuan Tim*, *query* WAJIB menggunakan metode CTE (*Common Table Expression*) atau *Recursive Query* untuk mencari UUID departemen turunan (*descendants*).
  2. **Flow Anti-Peer-to-Peer:** Apabila `hiring_manager` atau `hr_ops` membuka halaman cuti/presensinya sendiri (dimana `target_user_id == session_user_id`), blok *render* tombol "Approve" via *frontend* dan tolak eksekusi via *backend* middleware.

---

## BAB 9: ANALITIK & EKSEKUTIF DASHBOARD
*Fokus Utama: Pelaporan makro dan pengawasan level atas.*

- **Menu Terkait:**
  - *Executive Dashboard* (Khusus C-Level).
  - *Board Portal*.
- **Relasi Menu:** Menarik intisari (Data *Aggregate*) dari modul Rekrutmen (ATS) dan Operasional (HRIS).
- **Logika Alur Program:**
  1. **Flow Analitik:** *Query SELECT COUNT, SUM, AVG* pada tabel `users`, `employment_history`, dan pengeluaran *Payroll* $\rightarrow$ Ekstraksi menjadi JSON untuk *charting library* (misal: Chart.js). Data wajib berstatus *Read-Only*.

---

## BAB 10: OPTIMASI, EXCEL & PENYELESAIAN AKHIR
*Fokus Utama: Fitur integrasi massal dan penyesuaian visualisasi UI/UX.*

- **Menu Terkait:**
  - *Import/Export Massal*.
- **Relasi Menu:** Digunakan oleh `hr_ops` (untuk data presensi/karyawan) dan `admin` (untuk data master).
- **Logika Alur Program:**
  1. **Flow Import Excel (UPSERT):** 
     - Modul membaca *Spreadsheet*.
     - Looping baris data: Cek kolom UUID.
     - `IF UUID exists` $\rightarrow$ Lakukan `UPDATE` data.
     - `IF UUID is null` $\rightarrow$ Lakukan *Generate UUID v4* $\rightarrow$ Lakukan `INSERT` data baru.
     - `IF UUID is invalid/fictitious` $\rightarrow$ Lemparkan *Error (Reject row)*.
  2. **Flow Eksternal API:** *Endpoint* `/api/v1/attendance` menerima *payload* JSON dari mesin sidik jari. Wajib mencocokkan *Header* `X-API-Key` sebelum melakukan `INSERT` presensi ke basis data.
"""

with open("roadmap.md", "w") as f:
    f.write(content)
print("Updated roadmap.md with menus and flow written successfully")