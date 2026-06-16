# Roadmap Pengembangan Sistem HRIS & ATS (Corporate Edition)

> **PETUNJUK UNTUK AI DEVELOPER:**
> Kerjakan pengembangan aplikasi ini **Tepat Sesuai Urutan Bab (Step-by-Step)**. Jangan melompat ke Bab berikutnya sebelum seluruh fitur, logika bisnis, dan pengamanan (*security constraints*) di Bab yang sedang dikerjakan selesai 100% dan diuji. Pastikan Anda selalu merujuk pada `guide-for-ide.md` untuk aturan absolut keamanan dan logika *Role-Based Access Control* (RBAC).

---

## BAB 1: FONDASI & SISTEM MIGRASI KEBAL (IDEMPOTENT MIGRATIONS)
*Fokus Utama: Membangun skrip migrasi database yang aman dari kegagalan saat terjadi loncat versi (misal: v1 langsung ke v5).*

1. **Setup Arsitektur Proyek:** Inisialisasi struktur *folder* (MVC atau kerangka kerja pilihan), konfigurasi *environment* (`.env`), dan koneksi *database*.
2. **Implementasi Migration Engine (Idempotent):**
   - Skrip migrasi **WAJIB** mengecek keberadaan tabel sebelum membuatnya (`CREATE TABLE IF NOT EXISTS`).
   - Skrip modifikasi kolom **WAJIB** mengecek apakah kolom sudah ada di `INFORMATION_SCHEMA` sebelum melakukan `ALTER TABLE ADD COLUMN` atau `DROP COLUMN`.
   - Buat tabel `schema_migrations` untuk mencatat *batch* dan versi migrasi yang sudah dieksekusi.
3. **Setup Tabel Fundamental:**
   - Eksekusi pembuatan tabel `users`, `roles`, `audit_logs`, dan `sys_menus`.
   - Pastikan seluruh *Primary Key* menggunakan UUID v4.
4. **Fungsi Global Security:** Buat fungsi *helper* untuk perlindungan CSRF (`csrf_token()`), sanitasi XSS (`e()`), dan *Prepared Statements* global.

---

## BAB 2: AUTENTIKASI & KENDALI "SUPERADMIN" (GOD MODE)
*Fokus Utama: Mengamankan gerbang masuk dan membuat role absolut.*

1. **Sistem Login & Manajemen Sesi:** Buat halaman login tunggal untuk akses internal (Role 1-7). Gunakan enkripsi *password* yang kuat (Bcrypt/Argon2).
2. **Modul Dashboard Superadmin:** Buat antarmuka awal untuk `superadmin` dengan akses tanpa batas.
3. **Modul Global Audit Logs:** 
   - Buat tampilan tabel untuk membaca `audit_logs` (Hanya bisa dilihat oleh `superadmin` dan `executive`).
   - Buat fitur *Clear Logs* (TRUNCATE) khusus `superadmin` yang secara otomatis menyuntikkan 1 baris log kesaksian baru setelah dieksekusi.
4. **System Configuration:** Buat menu bagi `superadmin` untuk mengatur limit *upload* file (maksimal 10MB) dan pengaturan server (SMTP).

---

## BAB 3: DYNAMIC MENU ENGINE & MASTER DATA (ANTI-HARDCODE)
*Fokus Utama: Membuat sistem menu yang skalabel tanpa *hardcode* dan mengatur hierarki divisi.*

1. **Pembuatan Menu Builder:** Modul khusus `superadmin` untuk melakukan CRUD pada tabel `sys_menus` (menambah menu, mengatur urutan, dan ikon Google Material).
2. **Role Permission Builder (ACL Matrix):** Antarmuka bagi `superadmin` untuk menjodohkan Menu dengan `role_id` (UUID) dan `department_id` (UUID).
3. **Implementasi Caching:** Terapkan `SimpleCache` untuk tabel menu dan hak akses. Sistem menu *sidebar* harus di- *render* secara dinamis berdasarkan data *cache* saat *user login*.

---

## BAB 4: MANAJEMEN ORGANISASI & USER (ROLE: ADMIN)
*Fokus Utama: Membangun struktur 5 sub-level departemen dan operasional staf.*

1. **Manajemen Departemen (Adjacency List):** Buat antarmuka *tree-view* atau *cascading dropdown* bagi `admin` untuk membuat hierarki Direktorat hingga Tim (maksimal 5 level).
2. **Manajemen Pengguna Internal:**
   - Form pembuatan akun karyawan oleh `admin`.
   - Integrasi pembuatan/pengisian `employee_id` (Staff ID).
   - *Constraint:* Jika `admin` merubah `role_id` atau melakukan mutasi departemen, data **wajib masuk** ke tabel `approval_requests` (Status: PENDING), tidak langsung mengubah tabel `users`.

---

## BAB 5: MATRIKS PERSETUJUAN & ISOLASI (WORKFLOW ACC)
*Fokus Utama: Menghubungkan pengajuan Admin dengan eksekusi Eksekutif.*

1. **Modul Eksekutif (Approval Center):**
   - Buat *dashboard* antrean persetujuan bagi `executive`.
   - Implementasikan *Database Transaction* (Atomik): Jika `executive` menekan ACC pada pengajuan mutasi/ubah *role*, eksekusi *update* tabel `users`, catat ke `employment_history`, ubah status *request*, dan catat *log* dalam satu blok transaksi `try-catch`.
2. **Isolasi Horizontal Hiring Manager:** 
   - Bangun logika dan *query builder* pembatas agar `hiring_manager` hanya bisa menarik data/melihat bawahan yang UUID `department_id`-nya adalah turunan (*descendant*) dari divisinya.

---

## BAB 6: CORE HRIS & ONBOARDING (ROLE: HR OPS)
*Fokus Utama: Pengelolaan kelengkapan legal profil dan pemrosesan administrasi.*

1. **Master Data Karyawan (Tabel `user_profiles`):** Modul untuk mengelola NIK KTP, NPWP, Rekening Bank, dan Asuransi.
2. **Modul Verifikasi Onboarding:** Antrean bagi `hr_ops` untuk memeriksa unggahan dokumen pelamar. Jika di-ACC, ubah status kandidat menjadi `employee` dan kunci data tersebut.
3. **Data Correction Queue:** Sistem persetujuan jika `employee` mengajukan perubahan NIK/Rekening. `hr_ops` memverifikasi dan sistem menimpa (*overwrite*) profil lama.
4. **Payroll Engine (Dasar):** Formulasi penarikan data kehadiran bulanan sebagai basis data gaji.

---

## BAB 7: APPLICANT TRACKING SYSTEM (ROLE: RECRUITER & CANDIDATE)
*Fokus Utama: Membuka gerbang portal publik dan mengelola seleksi pelamar.*

1. **Manpower Requisition:** Form bagi `hiring_manager` untuk meminta *headcount*, yang disetujui oleh `executive`.
2. **Portal Loker Eksternal (Public View):** Halaman depan (di luar area login internal) untuk menampilkan lowongan aktif bagi `candidate`.
3. **Form Lamaran Low-Friction:** Proses pendaftaran akun `candidate`, unggah CV (validasi 10MB & `finfo`), dan penyimpanan ke *database*.
4. **ATS Pipeline (Kanban):** Antarmuka *drag-and-drop* bagi `recruiter` untuk menyeleksi kandidat (*Applied* > *Shortlisted* > *Interview*).
5. **Modul Wawancara & Offering:** 
   - Form *Scoring Rubric* bagi `hiring_manager`.
   - Modul unggah surat penawaran (*Offering Letter*) untuk ditandatangani digital oleh kandidat.

---

## BAB 8: EMPLOYEE SELF-SERVICE (ESS)
*Fokus Utama: Portal kemandirian untuk seluruh lini karyawan operasional.*

1. **Dashboard Profil:** Menampilkan sisa cuti, identitas diri, dan *Employee ID*.
2. **Modul Presensi:** Tombol *Clock-In / Clock-Out* (Simpan titik koordinat GPS & waktu).
3. **Pengajuan Cuti & Reimbursement:** Form dengan unggahan bukti (struk/surat dokter).
4. **Sistem Auto-Hide Approval:** Terapkan logika yang menyembunyikan tombol ACC apabila `hr_ops` atau `hiring_manager` sedang membuka antrean cutinya sendiri (No Peer-to-Peer Approval).
5. **Hierarki ACC Operasional:** Pastikan cuti `employee` masuk ke `hiring_manager`, dan cuti tingkat manajer naik vertikal sesuai `guide-for-ide.md`.

---

## BAB 9: ANALITIK & EKSEKUTIF DASHBOARD
*Fokus Utama: Pelaporan makro dan pengawasan level atas.*

1. **Dashboard Makro C-Level:** Buat grafik visual untuk metrik *Cost per Hire*, rasio *Turnover*, dan anggaran pengeluaran departemen (Read-Only).
2. **Board Portal:** Fasilitas unggah/unduh dokumen laporan rahasia (Keuangan, Manajemen) khusus antar dewan Direksi dan Komisaris.
3. **Finalisasi Keamanan Eksekutif:** Pastikan Komite Audit dapat mengakses dan membaca seluruh tabel log sistem secara utuh tanpa bisa mengubah isinya.

---

## BAB 10: OPTIMASI, EXCEL & PENYELESAIAN AKHIR
*Fokus Utama: Fitur integrasi massal dan penyesuaian visualisasi UI/UX.*

1. **Import/Export Massal (Excel):** 
   - Implementasikan *library* pihak ketiga (`phpspreadsheet`).
   - Buat logika UPSERT (Insert or Update) mutlak berdasarkan UUID. Tolak (*reject*) baris dengan UUID fiktif.
2. **Eksternal API (REST):** Buat *endpoint* berformat JSON statis yang dilindungi X-API-Key untuk integrasi mesin presensi fisik.
3. **Finishing UI/UX:** 
   - Eksekusi desain tabel (Pagination *auto-hide* jika <= 1 halaman, posisi kanan).
   - Penggantian `alert()` dengan SweetAlert2 di seluruh antarmuka.
   - Pengecekan ulang seluruh ikon untuk memastikan penggunaan Google Material Symbols.