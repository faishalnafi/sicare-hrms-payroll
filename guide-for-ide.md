content = """# Manual Panduan Pengembangan (Guide for IDE/AI) - HRIS & ATS System (Corporate Edition)

> **INSTRUKSI MUTLAK UNTUK AI/IDE:**
> Selain mematuhi dokumen ini, Anda **WAJIB** membaca dan mengecek versi terbaru dari arsitektur sistem pada berkas berikut sebelum melakukan perubahan kode:
> 1. `changelog.md` (Untuk melacak versi pembaruan fitur dan *patch*).
> 2. `security.md` (Untuk protokol keamanan, enkripsi, dan mitigasi spesifik).
> 3. `ui-guide-ide.md` (Untuk standar komponen visual, Tailwind/CSS, dan interaksi antarmuka).

Dokumen ini adalah **spesifikasi teknis mutlak (*absolute truth*)** untuk arsitektur, keamanan, dan logika bisnis sistem *Human Resources Information System* (HRIS) & *Applicant Tracking System* (ATS) korporasi skala besar. Seluruh asisten AI dan *developer* di masa mendatang **WAJIB** mematuhi panduan ini tanpa terkecuali untuk memastikan integritas data, isolasi akses (*Role-Based Access Control*), tata kelola korporat (*Corporate Governance*), dan keamanan tingkat tinggi.

---

## BAB 1: KEAMANAN TINGKAT TINGGI (HIGH-LEVEL SECURITY) & BATASAN AI

Untuk mencegah pembobolan data (*data breach*) dan menjaga kerahasiaan data korporasi, AI **wajib** mengimplementasikan standar keamanan berikut dalam setiap pembuatan *codebase*:

### 1.1 Proteksi Injeksi & Database
- **Prepared Statements Wajib**: DILARANG KERAS menggunakan interpolasi string langsung (seperti `$id`) ke dalam *query* SQL. Selalu gunakan *Prepared Statements* (PDO/MySQLi binding) untuk semua operasi CRUD.
- **UUID v4 Terisolasi**: DILARANG menggunakan `AUTO_INCREMENT` untuk ID utama tabel mana pun, termasuk `role_id`. Gunakan UUID v4 (`CHAR(36)`) murni tanpa embel-embel teks (misal: dilarang menggunakan format `uuid-admin`). Ini untuk menyembunyikan ukuran/jumlah data perusahaan dan mencegah serangan IDOR.

### 1.2 Manajemen Berkas (File Upload) Strict & Anti-Shell
- **Pembatasan Ukuran Mutlak**: Batas ukuran file maksimal **10MB** untuk seluruh unggahan. Jika melebihi, sistem wajib mengembalikan *error* sebelum file dipindahkan.
- **Validasi MIME Type Server-Side**: Dilarang mempercayai ekstensi dari sisi *client*. Validasi **WAJIB** menggunakan fungsi PHP `finfo_open(FILEINFO_MIME_TYPE)`.
- **Isolasi Direktori (Private Storage)**: Berkas kandidat dan dokumen rahasia karyawan (KTP, NPWP, Kontrak) **TIDAK BOLEH** diletakkan di direktori `public/`. Harus disimpan di direktori privat dan di-*serve* melalui *Controller* yang melakukan pengecekan RBAC.

### 1.3 Proteksi Mutasi Data & Output
- **CSRF Token**: Seluruh formulir dengan *method* `POST`, `PUT`, `PATCH`, `DELETE` wajib menyertakan token CSRF.
- **XSS Protection**: Seluruh *output* data wajib di-*escape* ke entitas HTML menggunakan fungsi sanitasi (contoh: `htmlspecialchars()`).

---

## BAB 2: ARSITEKTUR DATABASE & STRUKTUR UTAMA (ERD CONCEPT)

AI wajib mematuhi standar rancangan relasional tabel berikut untuk mengakomodasi kemajemukan korporasi:

1. **`users` (Akun Utama)**
   - `id` (CHAR(36), PK)
   - `employee_id` (VARCHAR(20), UNIQUE, Nullable untuk Candidate)
   - `email` (VARCHAR(100), UNIQUE)
   - `password_hash` (VARCHAR(255))
   - `role_id` (CHAR(36), FK ke tabel roles)
   - `is_active` (BOOLEAN)
2. **`user_profiles` (Data Sensitif)**
   - `id` (CHAR(36), PK)
   - `user_id` (CHAR(36), FK, UNIQUE)
   - Data Kependudukan, Bank, NPWP, Asuransi.
3. **`departments` (Struktur Divisi 5 Sub-Level)**
   - `id` (CHAR(36), PK)
   - `name` (VARCHAR(100))
   - `parent_id` (CHAR(36), FK ke `departments.id`, Nullable untuk level puncak/C-Level)
   - `level` (INT, 1 hingga 5)
4. **`employment_history` (Riwayat Jabatan)**
   - `id` (CHAR(36), PK)
   - `user_id` (CHAR(36), FK)
   - `department_id` (CHAR(36), FK)
   - `job_title` (VARCHAR(150)) -> *Diisi manual (Text Input) agar master data tidak membengkak.*
5. **`sys_menus` & `menu_permissions` (Dynamic Menu Engine - *Anti Hardcode*)**
   - **`sys_menus`**: Menyimpan daftar menu (id, title, url_route, icon, parent_id).
   - **`menu_permissions`**: Menyimpan matriks akses. Kolom: `id`, `menu_id`, `role_id` (CHAR(36)), `department_id` (CHAR(36), Nullable).
6. **`approval_requests` (Workflow Mutasi/Role)**
   - Menyimpan *request* tertunda (PENDING) beserta payload `new_data` dalam format JSON sebelum di-ACC Eksekutif.
7. **`audit_logs` (Rekam Jejak Keamanan)**
   - `id`, `user_id`, `action`, `table_name`, `ip_address`, `description`, `created_at`.

---

## BAB 3: DYNAMIC MENU ENGINE & MODULARISASI C-LEVEL

Sistem DILARANG KERAS menggunakan *hardcode* dalam menampilkan menu (contoh yang dilarang: `if role == 'CFO'`). Sistem harus sepenuhnya dinamis dan skalabel untuk mengantisipasi pemekaran C-Level di masa depan.

### 3.1 Konsep Role UUID vs Department UUID
Seluruh dewan direksi/C-Level (CEO, CFO, CTO, COO, CBO) memiliki **`role_id` yang SAMA** yaitu UUID `executive`. Perbedaan hak akses menu mereka dipisahkan secara vertikal melalui UUID **`department_id`**.
- CEO: `department_id = NULL` (Membawahi semua / Global).
- CFO: `department_id = [UUID-Direktorat-Keuangan]`.
- CTO: `department_id = [UUID-Direktorat-Teknologi]`.

### 3.2 Cara Kerja Menu Permissions (ACL)
Saat *user login*, sistem membaca tabel `menu_permissions`.
- Menu *Payroll & Budgeting* hanya di- *mapping* ke UUID Role Executive + UUID Direktorat Keuangan. Sehingga hanya CFO yang melihatnya.
- Menu *Approval Center* di- *mapping* ke UUID Role Executive + `department_id = NULL`. Sehingga seluruh C-Level mendapatkannya.

---

## BAB 4: LOGIKA BISNIS: ROLE-BASED ACCESS CONTROL (RBAC)

Terdapat 8 Role Utama dengan UUID murni.

### 1. `superadmin` (Absolute Power & God-Mode)
- **Tugas**: Infrastruktur global, Menu Builder, Role Permission Builder.
- **Pewarisan Hak Akses**: `superadmin` secara algoritmik **mewarisi SEMUA MENU** yang dimiliki oleh *role* dan departemen lain (termasuk menu manajemen C-Level).
- **Akses Absolut (Bypass ACC)**: Dapat melakukan CRUD ke seluruh entitas tanpa perlu melalui tabel `approval_requests` (tanpa ACC role lain).
- **Manajemen Log Khusus**: Berhak melakukan TRUNCATE/DELETE isi `audit_logs` agar aplikasi tidak lemot. **ATURAN MUTLAK**: Setiap kali log dihapus, sistem WAJIB otomatis menyisipkan 1 baris log baru dengan keterangan: *"Superadmin [Nama] telah menghapus seluruh log sistem pada [Timestamp]"*.

### 2. `executive` (C-Level, Dewan Komisaris, Komite Audit)
- **Tugas**: Puncak hierarki (*Final Approver*) & Pengawas makro.
- **Hak Akses Log**: Memiliki menu untuk membaca `audit_logs` guna mengawasi aktivitas `superadmin` dan mutasi sistem.
- Tidak memiliki menu ESS presensi mandiri (karena pengawasan *fiduciary*).

### 3. `admin` (Operational Administrator)
- **Tugas**: Pengelola struktur 5 level departemen dan master data. Mutasi/kenaikan jabatan yang mereka ajukan akan masuk ke status `PENDING` menunggu ACC `executive`.

### 4. `hiring_manager` (Kepala Divisi / Departemen)
- **Isolasi Horizontal**: **HANYA BISA** melakukan *query* atau menyetujui transaksi terhadap staf yang `department_id`-nya merupakan *descendant* (anak/turunan) dari divisinya sendiri. Buta 100% terhadap divisi lain.
- Jika berada di level menengah, cutinya di-ACC atasan manajernya. Jika berada di Level 1, cutinya di-ACC langsung oleh `executive`.

### 5. `hr_ops` (HR Operations & Payroll)
- **Tugas**: Verifikasi *onboarding*, pengeksekusi gaji, penyetuju perubahan KTP/Rekening.
- Terikat vertikal ke HR Manager untuk urusan audit absensi pribadinya.

### 6. `recruiter` (Talent Acquisition)
- **Tugas**: Manajemen loker, penjadwalan wawancara, Kanban ATS. Tidak punya akses ke data gaji internal HRIS.

### 7. `employee` (Karyawan Fungsional)
- **Tugas**: Modul ESS (Presensi *clock-in/out*, Cuti, Klaim *Reimbursement*, Unduh Slip Gaji).

### 8. `candidate` (Pelamar Eksternal)
- **Tugas**: Terkunci di portal publik. Jika lolos dan menandatangani *Offering*, wajib melengkapi berkas *Onboarding*. Setelah diverifikasi `hr_ops`, statusnya berpindah permanen menjadi `employee`.

---

## BAB 5: MATRIKS AUDIT PRESENSI & PAYROLL (SEGREGATION OF DUTIES)

1. **No Peer-to-Peer Approval**: Sesama *role* (misal: sesama `hr_ops` atau sesama `hiring_manager` se-departemen) dilarang saling menyetujui cuti/absensi.
2. **Auto-Hide Approval Button**: Saat pengguna membuka profil ESS miliknya sendiri, tombol *Approve* WAJIB disembunyikan/dikunci sistem. Tidak ada yang bisa meng-ACC data dirinya sendiri.

---

## BAB 6: PRINSIP UI/UX, DESAIN & ASET EKSTERNAL

- **Konsistensi Tema**: Glassmorphism, UI bersih, dan bayangan proporsional.
- **Aset Wajib Google (Strict Constraint)**: 
  - Ikon **WAJIB** menggunakan Google Material Symbols Outlined via API. (Dilarang pakai FontAwesome/pihak ke-3).
  - Font **WAJIB** menggunakan Google Fonts (Inter / Roboto / Plus Jakarta Sans).
- **Interaksi Dialog**: **WAJIB** menggunakan **SweetAlert2** untuk semua notifikasi dan konfirmasi. DILARANG menggunakan `window.alert()` atau dialog *native browser*.
- **Aturan Pagination**:
  - Tombol navigasi di sisi kanan (`justify-end`), latar semi transparan.
  - Teks seperti *"Menampilkan 1-10 dari 50"* **DILARANG** ditampilkan. Desain harus minimalis.
  - **Auto-Hide**: Jika data <= limit 1 halaman, blok pagination WAJIB disembunyikan total via JavaScript/CSS.

---

## BAB 7: STRATEGI CACHING & OPTIMASI

- **File-based Cache (`SimpleCache`)**: Tabel master (Struktur Departemen, Jabatan, Role, PTKP, Menu Dinamis) **WAJIB** di-*cache* ke memori.
- **Invalidasi Cache (Cache Purge)**: Setiap kali `admin` atau `superadmin` memperbarui data master (contoh: memindah urutan menu atau mengubah struktur divisi), fungsi `$cache->delete('key')` **HARUS DIMUAT MUTLAK** agar sistem segera mendapatkan pembaruan secara *real-time*.

---

## BAB 8: KEBIJAKAN FOTO PROFIL & AVATAR PENGGUNA

Untuk menjaga kualitas visual dan estetika premium aplikasi, seluruh modul rendering avatar dan foto profil wajib mengikuti aturan ketat berikut:
1. **Prioritas Utama**: Foto profil harus diambil dari database (kolom `profile_picture` yang diisi secara dinamis dari unggahan).
2. **Prioritas Kedua**: Jika data kosong, sistem wajib mengambil data avatar dari **Gravatar** menggunakan *hash* MD5 dari alamat email pengguna (`https://www.gravatar.com/avatar/{md5(email)}`).
3. **Prioritas Ketiga (Fallback)**: Jika email tidak terdaftar di Gravatar, sistem **HARUS** menggunakan avatar acak bermotif geometris dengan parameter fallback `d=identicon` (`https://www.gravatar.com/avatar/{md5(email)}?d=identicon`).
4. **Larangan Mutlak**: **DILARANG KERAS** menampilkan gambar profil berupa huruf inisial (*letter-based avatar* seperti `ui-avatars.com`). Wajib menggunakan pola *fallback identicon* geometris untuk menjaga nuansa desain yang misterius, premium, dan profesional.
"""

with open("guide-for-ide.md", "w") as f:
    f.write(content)
print("Final dynamic guide-for-ide.md created successfully")