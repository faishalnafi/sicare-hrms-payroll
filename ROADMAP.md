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
*Fokus Utama: Mengamankan gerbang masuk, membangun dasbor absolut, dan mewariskan SELURUH fitur aplikasi secara terstruktur agar tidak berantakan.*

- **Menu Terkait (Spesifik Superadmin):**
  - *Global Audit Logs* (Pusat pemantauan seluruh aktivitas).
  - *System Configuration* (Pengaturan server, SMTP, batasan ukuran file).
  - *Menu & Role Builder* (Pembuatan navigasi dan hak akses dinamis).
- **Menu Terkait (Warisan/Inherited dari Seluruh Role Bawah):**
  - **Grup Admin:** Manajemen Organisasi 5 Level, Manajemen Pengguna.
  - **Grup Eksekutif:** Executive Dashboard (Analitik), Matriks Persetujuan (Bypass view), Board Portal.
  - **Grup HR Ops:** Verifikasi Onboarding, Koreksi Data Karyawan, Payroll Engine.
  - **Grup Recruiter:** ATS Pipeline, Loker, Penjadwalan, Rubrik Wawancara.
  - **Grup Karyawan (ESS):** Log Presensi Keseluruhan, Data Cuti Keseluruhan.
- **Relasi Menu:** `superadmin` tidak terikat oleh UUID Departemen mana pun. Memiliki akses lintas batas (*cross-boundary*) ke semua data secara absolut.
- **Logika Alur Program:**
  1. **Flow Login:** *User* memasukkan Email & Password -> Validasi *Bcrypt* -> Set Session.
  2. **Flow Omni-Render Sidebar (Anti-Berantakan):** 
     - Jika `role == superadmin`, sistem **MEM-BYPASS** pengecekan tabel `menu_permissions`.
     - *Query* langsung mengambil seluruh data dari `sys_menus` (`SELECT * FROM sys_menus ORDER BY group, order_num`).
     - Render UI akan mengelompokkan menu ke dalam kategori yang rapi (Contoh: *Folder* "System Tools", *Folder* "HR Operations", *Folder* "Recruitment", dll) agar *sidebar* superadmin tidak panjang dan berantakan.
  3. **Flow Bypass Approval:** Jika `superadmin` melakukan CRUD (misal merubah peran/role karyawan), abaikan (*bypass*) alur `approval_requests`. Langsung lakukan `UPDATE` ke tabel utama `users` dan catat ke `audit_logs`.
  4. **Flow Clear Logs:** Saat superadmin mengeksekusi *Clear Logs*, sistem melakukan `TRUNCATE audit_logs`, lalu otomatis menjalankan `INSERT` log kesaksian (*"Superadmin telah menghapus seluruh log..."*).

---

## BAB 3: DYNAMIC MENU ENGINE & MASTER DATA (ANTI-HARDCODE)
*Fokus Utama: Membuat sistem menu yang skalabel untuk pengguna SELAIN superadmin.*

- **Menu Terkait:**
  - Diwarisi ke Bab 2 (Menu & Role Builder).
- **Relasi Menu:** *Superadmin* menentukan menu apa yang tampil untuk ke-7 *role* lainnya berdasarkan tabel `menu_permissions`.
- **Logika Alur Program:**
  1. **Flow Menu Builder:** CRUD ke tabel `sys_menus` (URL *route*, ikon).
  2. **Flow ACL:** Menyimpan matriks hak akses ke `menu_permissions` (UUID Menu + UUID Role + UUID Dept).
  3. **Flow Render Sidebar (Non-Superadmin):** Sistem menjalankan *query* filter: `SELECT * FROM sys_menus JOIN menu_permissions WHERE role_id = ? AND (department_id = ? OR department_id IS NULL)`.
  4. **Flow Cache Invalidation:** Perubahan menu akan memicu `$cache->delete('sidebar_menu')`.

---

## BAB 4: MANAJEMEN ORGANISASI & USER (ROLE: ADMIN)
*Fokus Utama: Membangun struktur departemen dan penempatan karyawan awal.*

- **Menu Terkait:**
  - *Manajemen Organisasi* (Hierarki Divisi 5 Level).
  - *Manajemen Pengguna* (Data internal karyawan).
- **Relasi Menu:** *Dropdown* yang dihasilkan digunakan di seluruh aplikasi (Filter ATS, filter HR, filter Ess).
- **Logika Alur Program:**
  1. **Flow Dept Builder:** Penyimpanan menggunakan konsep *Adjacency List*.
  2. **Flow Edit User (Trigger Approval):** Jika *Admin* yang mengedit role/jabatan, data WAJIB ditahan ke `approval_requests` (Status: PENDING). (Berbeda dengan *Superadmin* yang datanya langsung berubah).

---

## BAB 5: MATRIKS PERSETUJUAN & ISOLASI (WORKFLOW ACC)
*Fokus Utama: Memproses antrean persetujuan dari Admin (oleh Eksekutif) dan mengisolasi akses Kepala Divisi.*

- **Menu Terkait:**
  - *Approval Center* (Eksekutif & Superadmin).
- **Relasi Menu:** Menerima *payload* perubahan dari modul Admin dan Hiring Manager.
- **Logika Alur Program:**
  1. **Flow ACC Mutasi:** Eksekutif klik "Approve" -> `$db->beginTransaction()` -> `UPDATE users SET role_id = JSON_VAL` -> `INSERT employment_history` -> `UPDATE approval_requests` -> `$db->commit()`.
  2. **Flow Isolasi Horizontal (Hiring Manager):** Gunakan rekursif (*CTE*) agar manajer hanya bisa melihat data cuti/presensi staf dengan UUID departemen di bawah rantai komandonya (*descendants*).

---

## BAB 6: CORE HRIS & ONBOARDING (ROLE: HR OPS)
*Fokus Utama: Pengelolaan kelengkapan legal profil dan pemrosesan penggajian.*

- **Menu Terkait:**
  - *Verifikasi Onboarding* (HR Ops & Superadmin).
  - *Data Correction Queue* (HR Ops & Superadmin).
  - *Payroll Engine* (HR Ops, Eksekutif Keuangan, & Superadmin).
- **Relasi Menu:** Mengonversi data Kandidat menjadi Karyawan.
- **Logika Alur Program:**
  1. **Flow Verifikasi:** Terima KTP -> `UPDATE users SET role_id = Employee, employee_id = [Generated]` -> Profil aktif.
  2. **Flow Payroll:** Sum presensi bulanan -> potong pajak (PTKP) -> *Generate* PDF Slip Gaji.

---

## BAB 7: APPLICANT TRACKING SYSTEM (RECRUITER & CANDIDATE)
*Fokus Utama: Membuka gerbang portal publik dan mengelola seleksi pelamar.*

- **Menu Terkait:**
  - *Manpower Requisition*, *Portal Loker*, *ATS Pipeline*, *Scoring Rubric*. (Semua terbaca oleh Superadmin).
- **Logika Alur Program:**
  1. **Flow Lamaran (Candidate):** *Upload* CV (Maks 10MB divalidasi `finfo`) -> Simpan ke direktori `private/` -> Status: `APPLIED`.
  2. **Flow ATS Pipeline (Recruiter):** Modul visual Kanban Board. Menggeser kartu kandidat akan men- *trigger* eksekusi pembaruan status pelamar.

---

## BAB 8: EMPLOYEE SELF-SERVICE (ESS)
*Fokus Utama: Portal kemandirian untuk seluruh lini karyawan operasional.*

- **Menu Terkait:**
  - *Presensi Digital*, *Cuti & Klaim Reimbursement*, *Pengajuan Koreksi Data*.
- **Logika Alur Program:**
  1. **Flow Presensi:** Tombol *Clock-In/Out* yang mengunci UUID Karyawan, menyalin koordinat GPS dan *Timestamp*.
  2. **Flow Anti-Peer-to-Peer:** Middleware *Backend* wajib menghentikan setiap aksi di mana `target_user_id == session_user_id` pada tombol "Approve" agar HR/Manajer tidak bisa menyetujui cutinya sendiri.

---

## BAB 9: ANALITIK & EKSEKUTIF DASHBOARD
*Fokus Utama: Pelaporan makro dan pengawasan level atas.*

- **Menu Terkait:**
  - *Executive Dashboard* & *Board Portal* (Eksekutif & Superadmin).
- **Logika Alur Program:**
  1. **Flow Analitik Makro:** Pengumpulan *Aggregate* dari ATS, HRIS, dan Payroll. Ditampilkan secara visual (grafik pie, bar) dengan akses `Read-Only`.

---

## BAB 10: OPTIMASI, EXCEL & PENYELESAIAN AKHIR
*Fokus Utama: Fitur integrasi massal dan penyesuaian visualisasi UI/UX.*

- **Menu Terkait:**
  - *Import/Export Data Massal* (Admin, HR Ops, & Superadmin).
- **Logika Alur Program:**
  1. **Flow Import Excel (UPSERT):** Looping baris data -> Cek kolom UUID -> Lakukan `UPDATE` jika ada -> Lakukan `INSERT` (buat UUID v4 baru) jika kosong -> Lemparkan *Error* jika UUID terisi tapi fiktif.
  2. **Flow API:** Endpoint `/api/v1/attendance` JSON. Dilindungi validasi `X-API-Key`.
"""

with open("roadmap.md", "w") as f:
    f.write(content)
print("Updated roadmap.md with tidy Superadmin inheritance written successfully")