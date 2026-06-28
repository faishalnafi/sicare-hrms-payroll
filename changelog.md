# siCare Release Changelog

All public releases of the siCare HRMS Payroll system are listed here.

---

## Version local-26.06.00025 (Enterprise) - Mono
*Compiled Date: 2026-06-28 | Migration: stg -> production*

### Security
- Penerapan proteksi CSRF Token terpusat menggunakan timing-safe hash_equals() pada 13 backend controller (CorrectionController, LeaveController, ReimbursementController, SettingsController, PayrollController, DepartmentController, ApprovalController, AuthController, EmployeeMasterController, ReflectionController, AttendanceController, AuditLogController, DashboardController).
- Migrasi penyimpanan JWT Secret Key dari hardcoded string ke environment variable (JWT_SECRET) pada file .env dengan mekanisme fallback otomatis berbasis APP_KEY.
- Pengetatan validasi tipe berkas unggahan berbasis binary MIME type (finfo_file) dan pembatasan akses download berkas legal/nota di luar webroot.

### Added
- Injeksi variabel JavaScript global window.csrfToken pada layouts/app.php untuk menangani proteksi CSRF otomatis pada seluruh request AJAX FormData.
- Kalkulasi otomatis SLA (Service Level Agreement) penanganan verifikasi dokumen dalam satuan jam pada header modul HR Ops Verifications.
- Penyediaan update real-time via AJAX pada konter statistik KPI HR Ops saat permohonan disetujui atau ditolak tanpa reload halaman.

### Fixed
- Penyelarasan kalkulasi angka statistik KPI (Antrean, Disetujui, Ditolak) pada modul HR Ops Verifications agar sesuai 1-to-1 dengan filter data bulanan di tabel.

### Changed
- Pembaruan file .env.example untuk menyelaraskan seluruh variabel sistem terbaru tanpa menyertakan nilai kredensial sensitif.

### Removed
- Pembersihan berkas draf, log temporer, script eksperimen, dan file contoh yang tidak digunakan dari root repositori (old_sidebar.php, test.php, scratch_query_menus.php, tomcat-web.xml.example, checklog.*, tracker.*).

### Database (Migration 000009 & 000010)
- Konsolidasi seluruh skema database ke dalam migration file formal: 16 tabel (termasuk departments, approval_requests, employment_history, dll).
- Sinkronisasi kolom tambahan tabel users (20 kolom), departments (5 kolom limit reimbursement), employee_attendance (work_mode, clock_out_status, work_mode_out), login_logs (status), employee_payroll (overtime, other_deduction), changelogs (repo_type, alias_name).
- Seed data default untuk tabel departments (11 departemen utama) dan global_settings (27 konfigurasi standar sistem).

---


## Version local-26.06.00024 (Enterprise) - Mono
*Compiled Date: 2026-06-27 | Migration: stg -> production*

### Changed
- Menyelaraskan tampilan visual halaman placeholder coming_soon.php agar sama persis 1-to-1 dengan gambar acuan (ikon construction polos tanpa lingkaran latar).

---


## Version local-26.06.00023 (Enterprise) - Mono
*Compiled Date: 2026-06-27 | Migration: stg -> production*

### Changed
- Standardisasi global pagination tabel di layouts/app.php agar memiliki tombol First/Last, info data di kiri, dan max 3 page numbers.
- Penyelarasan pagination di superadmin/audit.php, hrops/employees.php, employee/leaves.php, employee/attendance.php, dan dashboard/index.php.
- Merapikan dan menyusun ulang struktur menu navigasi role Superadmin menjadi 10 item spesifik dengan fitur submenu collapsible accordion di app_sidebar.php.
- Menambahkan routing superadmin/departments dan registrasi route unbuilt (Apps, API, Persetujuan Multi-role) ke coming_soon.php.

---


## Version local-26.06.00022 (Enterprise) - Mono
*Compiled Date: 2026-06-27 | Migration: stg -> production*

### Changed
- Standardisasi global pagination tabel di layouts/app.php agar memiliki tombol First/Last, info data di kiri, dan max 3 page numbers.
- Penyelarasan pagination di superadmin/audit.php, hrops/employees.php, employee/leaves.php, employee/attendance.php, dan dashboard/index.php.

---


## Version local-26.06.00021 (Enterprise) - Mono
*Compiled Date: 2026-06-26 | Migration: stg -> production*

### Added
- Proteksi URL level-controller untuk route "changelogs/guide" (Pedoman Penomoran) agar mengembalikan error 403 jika diakses non-admin.
- Penerapan custom scrollbar premium tipis (6px) rounded dan scroll listener dinamis (class '.is-scrolling') di layouts/guest.php.

### Improved
- Penyempurnaan warna visual sort icon (panah tidak aktif) menjadi '#b0b2c3' agar tetap tampak redup, bukan tersamar putih.

### Fixed
- Optimalisasi performa table standardizer dengan menambahkan flag 'isStandardizing' di MutationObserver untuk mencegah loop tak terbatas.
- Mempercepat loading data tabel pengguna dengan meniadakan cURL network request HEAD serial ke Gravatar.
- Inisialisasi ulang standardisasi tabel saat navigasi SPA selesai agar sort icon langsung muncul tanpa refresh halaman manual.

### Removed
- Penghapusan style custom-scrollbar lokal di auth/signup.php untuk keselarasan dengan scrollbar global.

### Changed
- Pembatasan menu "Pedoman Penomoran" agar hanya tampil di role admin & superadmin di sidebar.
- Mengubah query parameter Gravatar default dari 'd=404' menjadi 'd=identicon' di seluruh controllers dan views untuk performa optimal.
- Mengganti fallback default profil kosong di seluruh UI menu sidebar dan header dengan motif Identicon dinamis.

---


## Version local-26.06.00020 (Enterprise) - Mono
*Compiled Date: 2026-06-26 | Migration: stg -> production*

### Changed
- Kustomisasi pagination tabel manajemen pengguna (Superadmin & Admin) untuk menampilkan info data range di sebelah kiri dan navigasi halaman dengan tombol First/Last/Prev/Next serta 3 page numbers berpusat di halaman aktif di sebelah kanan.

---


## Version local-26.06.00019 (Enterprise) - Mono
*Compiled Date: 2026-06-25 | Migration: stg -> production*

### Changed
- Penghapusan fitur Menu Builder dan Matriks Akses (ACL) secara bersih pada kode program (views, controller, routes, seeder) serta entri database terkait dan pembersihan cache sidebar.

---


## Version local-26.06.00018 (Enterprise) - Mono
*Compiled Date: 2026-06-25 | Migration: stg -> production*

### Fixed
- Perbaikan overflow layout sidebar collapsed (minimize) agar terpotong rapi sesuai batas wadah dan bagian menu navigasi dapat di-scroll dengan custom scrollbar tipis, serta implementasi floating tooltip berbasis JavaScript untuk menggantikan tooltip CSS.

---


## Version local-26.06.00017 (Enterprise) - Mono
*Compiled Date: 2026-06-25 | Migration: stg -> production*

### Local-26.06.00017
- Memperluas form edit user/employee modal di Admin dan Superadmin untuk memuat semua kolom profil pribadi dan kepegawaian yang dapat diubah serta pembersihan base_salary/plafon desimal saat save.

---


## Version local-26.06.00016 (Enterprise) - Mono
*Compiled Date: 2026-06-25 | Migration: stg -> production*

### Added
- Mengintegrasikan tombol Edit Data User Lengkap, Suspend/Aktifkan, dan Hapus Pengguna di kolom aksi Manajemen Pengguna Admin dan Superadmin, serta memvalidasi status penangguhan saat login.

### Changed
- Memperbarui judul halaman Manajemen Pengguna, label modal edit lengkap, serta menyesuaikan pesan sukses penyimpanan data pengguna pada portal Admin dan Superadmin.

---


## Version local-26.06.00015 (Enterprise) - Mono
*Compiled Date: 2026-06-25 | Migration: stg -> production*

### Added
- Mengintegrasikan tombol Edit Data User Lengkap, Suspend/Aktifkan, dan Hapus Pengguna di kolom aksi Manajemen Pengguna Admin dan Superadmin, serta memvalidasi status penangguhan saat login.

---


## Version local-26.06.00014 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Improved
- Menyempurnakan detail pesan error pada kegagalan login untuk secara spesifik menginformasikan apabila email tidak terdaftar (Email tidak terdaftar) atau kata sandi salah (Kata sandi salah) bersama dengan informasi sisa percobaan.

---


## Version local-26.06.00013 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Added
- Menambahkan fitur throttling login berbasis database. Percobaan 1-9 menampilkan sisa percobaan. Percobaan 10-12 memberikan penguncian 15s hingga 150s. Percobaan 13-20 melipatgandakan durasi penguncian sebelumnya. Percobaan 21+ memblokir akun secara permanen.

---


## Version local-26.06.00012 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Removed
- Menghapus tabel redundan system_menus dan department_menu_privileges yang tidak digunakan oleh sistem. Memperbarui migrasi SeedSysMenusAndPermissions untuk menggunakan UUID v4 acak (random) alih-alih UUID berurutan, serta menyinkronkan ulang database.

---


## Version local-26.06.00011 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Fixed
- Memperbaiki query pengambilan versi skema terinstal di halaman Pembaruan Sistem dan daftar Changelogs agar diurutkan berdasarkan string versi terbalik (version DESC), sehingga versi terbaru (local-26.06.00010) teridentifikasi secara akurat.

---


## Version local-26.06.00010 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Improved
- Memperbaiki dan menyeragamkan ukuran foto profil pemohon (w-10 h-10) serta ukuran teks nama pemohon (text-sm) di halaman Global Approval Center, serta menyetel label portal header secara dinamis menjadi "Portal Superadmin" berdasarkan peran pengguna

---


## Version local-26.06.00009 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Security
- Mengimplementasikan otorisasi dan validasi JWT Token pada seluruh operasi CRUD data (POST) untuk mencegah manipulasi data tidak sah dan serangan CSRF

---


## Version local-26.06.00008 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Improved
- Memperbaiki foto profil pemohon di Global Approval Center agar memprioritaskan database profile_picture sebelum Gravatar

---


## Version local-26.06.00007 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Improved
- Standardisasi tampilan foto profil pemohon pada halaman Global Approval Center menggunakan Gravatar dengan fallback, menyelaraskan dengan aturan global aplikasi

---


## Version local-26.06.00006 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Added
- Penambahan metode presensi POP (Presensi Online Portal) untuk laptop/desktop yang tidak memiliki GPS fisik, termasuk bypass validasi radius

---


## Version local-26.06.00005 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Removed
- Penghapusan total fitur alarm presensi beserta tombol pengaturan dan berkas audio pengingat (samsung_s25.mp3)

---


## Version local-26.06.00004 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Improved
- Mengubah desain banner simulasi login menjadi kartu melayang (floating card) dengan rounded-2xl dan bayangan lembut agar selaras dengan desain sidebar

---


## Version local-26.06.00003 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Fixed
- Penempatan tombol hamburger menu langsung di dalam banner simulasi login agar tidak tertutup ketika banner melipat di layar kecil

---


## Version local-26.06.00002 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Added
- Penambahan dokumentasi arsitektur MVC/OOP terperinci pada seluruh method di AuthController dan DashboardController

### Improved
- Refaktor filter dropdown Global Approval Center agar memuat departemen Level 1 dinamis dari database

### Fixed
- Perbaikan tumpang tindih tombol menu hamburger dengan banner simulasi pada perangkat tablet/mobile
- Perbaikan error syntax JavaScript pada tabel manajemen pengguna akibat baris kode pagination terpotong
- Perbaikan kegagalan tombol simulasi login bagi pengguna yang memiliki nama bertanda kutip tunggal

### Refactored
- Refaktorisasi string literal dari kutip satu ke kutip dua di AuthController.php dan DashboardController.php

---


## Version local-26.06.00001 (Enterprise) - Mono
*Compiled Date: 2026-06-22 | Migration: stg -> production*

### Added
- Pembuatan akun pengguna tingkat C-Level (CEO, CFO, CTO, COO, CBO) dengan kata sandi bawaan "password"
- Pembuatan akun pengguna hiring manager untuk sub-departemen level 2 dan level 3 dengan kata sandi bawaan "password"
- Pembuatan akun pengguna HR Operations, Recruiter, dan Candidate
- Fitur pemindahan departemen interaktif menggunakan drag-and-drop

### Improved
- Peningkatan batas kedalaman struktur departemen maksimal menjadi 10 level

### Removed
- Pembersihan file-file cadangan, log lama, file duplikat, dan skrip pelacakan yang tidak lagi digunakan

### Changed
- Menampilkan menu Struktur Departemen juga pada peran Superadmin dan membersihkan cache sidebar

---


## Version 002.000.000 (Enterprise) - Mono
*Compiled Date: 2026-06-16 | Migration: stg -> production*

### Added
- Fitur deteksi aktivitas idle (idle session warning) dengan modal popup glassmorphic, info waktu hening, opsi Ya/Tidak, dan hitung mundur auto-logout.
- Integrasi Kalender Libur Google: Tombol 'Ambil dari Google Calendar' untuk menarik seluruh hari libur nasional dengan checklist approval bagi admin.
- Sistem Loading Skeleton Scale: transisi SPA dan browser navigation menampilkan skeleton bernapas dengan efek scale cubic-bezier.
- Initial Page Load Skeleton: overlay skeleton bernapas ketika halaman pertama kali dibuka lambat (> 2 detik).
- Minimal Jam Pulang: batasan jam kepulangan karyawan (clock-out) di menu pengaturan.
- Switch Toggle Minimal Jam: opsi untuk mengaktifkan/menonaktifkan batasan minimal jam masuk & minimal jam pulang secara mandiri.
- Integrasi pemilih lokasi peta interaktif hybrid (Google Maps / Leaflet) dengan Google Places Autocomplete dan pencarian Nominatim.
- Menambahkan tombol pemilih tampilan peta (Jalan / Satelit / Terrain) pada modal peta global, openMapPicker, dan koordinat kantor.
- Menambahkan tombol Edit (ikon pensil biru) di samping tombol Hapus pada setiap item hari libur di Daftar Libur Bulan Ini.
- Halaman khusus pedoman penomoran baru di `/changelogs/guide`.
- Integrasi event listener online/offline global untuk memicu popup SweetAlert2 ketika koneksi internet terputus atau terhubung kembali.
- Penanganan error database (PDOException) pada server-side di Database.php yang mengembalikan respon JSON untuk AJAX dan merender halaman error pangkalan data berdesain premium.

### Improved
- Penyesuaian dimensi modal deteksi idle agar lebih ramping.
- Konsistensi Warna Box: menyelaraskan warna box deskripsi live preview jam masuk dan jam pulang menjadi seragam (amber theme).
- Pembersihan Antarmuka: menghapus deskripsi paragraf penjelasan statis pada batas toleransi pulang lambat.
- Notifikasi Penyimpanan: mengubah notifikasi pop-up simpan pengaturan dari Toast menjadi SweetAlert2 modal dialog standar.
- Menyelaraskan lebar input Logo Aplikasi (Material Icon) agar mengambil sisa ruang kolom md:col-span-2.
- Mengubah pemuatan script Google Maps API di layouts/app.php agar dimuat secara global dan default.
- Mengubah ubin peta Leaflet pada modal openMapPicker() agar memuat ubin Google Maps Roadmap secara gratis.
- Mengosongkan innerHTML elemen leafletMapElement sebelum inisialisasi ulang peta.
- Menambahkan deteksi otomatis perubahan google_maps_api_key pada fungsi saveSettings().
- Optimasi Service Worker sw.js untuk melakukan caching halaman HTML secara dinamis pada request GET.

### Fixed
- Perbaikan tombol Perbarui Aplikasi Sekarang yang unclickable akibat JS syntax error.
- Perbaikan pratinjau berkas PDF agar tidak terunduh otomatis.
- Mengembalikan pemuatan script Google Maps API menjadi kondisional untuk mencegah popup error jika API Key kosong.
- Mengatasi double popup error saat SPA page navigation gagal akibat status error server.

### Security
- Pencegahan akses langsung berkas via URL mentah (onboarding, cuti, koreksi, klaim) dengan menyajikan halaman kustom 403 Forbidden.
- Menyembunyikan detail log developer (stack trace) pada halaman gangguan database jika environment aplikasi (APP_ENV) diset ke production.

### Changed
- Penyesuaian format versi rilis Beta menjadi SemVer 3-digit zero-padded tanpa kata Beta.

### Removed
- Menghapus skeleton loader di seluruh halaman aplikasi untuk kecepatan transisi halaman.
- Menghapus gaya CSS skeletonScale, markup overlay, dan helper JS dari file layouts/app.php.

---

## Version 001.001.000 (Enterprise) - Mono
*Compiled Date: 2026-06-12 | Migration: stg -> production*

### Added
- Fitur ekspor slip gaji ke format PDF terenkripsi kata sandi (tanggal lahir)

### Improved
- Optimasi loading rekap kehadiran karyawan pada dashboard supervisor

### Fixed
- Bug pembulatan pecahan rupiah pada penghitungan tunjangan lembur

### Security
- Validasi payload token JWT OAuth2 untuk integrasi Google Login

---

## Version 26.05-LTS / Ammonite (Enterprise) - Mono
*Compiled Date: 2026-06-11 | Migration: stg -> production*

### Added
- Menu Presensi GPS tracking terintegrasi untuk karyawan WFH/WFO
- Integrasi library SweetAlert2 untuk dialog konfirmasi tindakan
- Fitur self-reflection kuartalan (Q2) untuk pelacakan performa karyawan
- Sistem session handler hybrid yang menyimpan state di Redis/DB

### Improved
- Mempercepat query perhitungan rekap absensi bulanan untuk payroll

### Fixed
- Validasi double clock-in agar tidak menduplikasi baris absensi
- Memperbaiki overflow scroll horizontal pada tabel master data di mobile

### Security
- Penggunaan finfo_open() pada backend upload berkas klaim
- Audit log otomatis untuk mencatat semua koreksi data sensitif oleh HR

---
