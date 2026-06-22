# siCare Release Changelog

All public releases of the siCare HRMS Payroll system are listed here.

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
