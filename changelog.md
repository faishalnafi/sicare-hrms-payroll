# siCare Release Changelog

All public releases of the siCare HRMS Payroll system are listed here.

---

## Version stg-26.06.00002 (Enterprise) - Mono
*Compiled Date: 2026-06-16 | Migration: stg -> production*

### Added
- Integrasi event listener online/offline global untuk memicu popup SweetAlert2 ketika koneksi internet terputus atau terhubung kembali
- Penanganan error database (PDOException) pada server-side di Database.php yang mengembalikan respon JSON untuk AJAX dan merender halaman error pangkalan data berdesain premium

### Improved
- Optimasi Service Worker sw.js untuk melakukan caching halaman HTML secara dinamis pada request GET

### Fixed
- Mengatasi double popup error saat SPA page navigation gagal akibat status error server

### Security
- Menyembunyikan detail log developer (stack trace) pada halaman gangguan database jika environment aplikasi (APP_ENV) diset ke production

---


## Version stg-26.06.00001 (Enterprise) - Mono
*Compiled Date: 2026-06-16 | Migration: stg -> production*

### Fixed
- Perbaikan tombol Perbarui Aplikasi Sekarang yang unclickable akibat JS syntax error

### Changed
- Penyesuaian format versi rilis Beta menjadi SemVer 3-digit zero-padded tanpa kata Beta

---


## Version 26.06.00009 (Enterprise) - Mono
*Compiled Date: 2026-06-15 | Migration: stg -> production*

### Added
- Menambahkan tombol **Edit** (ikon pensil biru) di samping tombol Hapus pada setiap item hari libur di Daftar Libur Bulan Ini, di `admin/settings.php` dan `hrops/settings.php`. Klik tombol edit membuka modal SweetAlert2 dengan field tanggal dan keterangan yang sudah terisi data saat ini, lalu menyimpan perubahan via endpoint baru `POST /admin(hrops)/holidays/update` yang ditangani oleh method `updateHoliday()` baru di `SettingsController.php`. Route baru juga didaftarkan di `public/index.php`.

---

## Version 26.06.00008 (Enterprise) - Mono
*Compiled Date: 2026-06-15 | Migration: stg -> production*

### Added
- Menambahkan tombol pemilih tampilan peta (Jalan / Satelit / Terrain) ke dalam modal **Pilih Lokasi Kantor** (`openMapPicker`) di `admin/settings.php` dan `hrops/settings.php`. Tombol tampil di bawah peta, aktif langsung sesuai tampilan yang dipilih sebelumnya (state dipertahankan dalam sesi). Menggantikan `mapTypeControl: true` bawaan Google Maps dan tile layer Leaflet statis dengan kontrol kustom yang konsisten di kedua mode.

---

## Version 26.06.00007 (Enterprise) - Mono
*Compiled Date: 2026-06-15 | Migration: stg -> production*

### Added
- Menambahkan tombol pemilih tampilan peta (Jalan / Satelit / Terrain) di bawah peta pada modal peta global (`globalLeafletMapModal`) di `layouts/app.php`. Tombol bekerja di kedua mode: Google Maps API (`setMapTypeId`) dan Leaflet (swap tile layer). Menghapus `L.control.layers()` bawaan Leaflet dan menggantinya dengan tombol kustom berdesain premium yang konsisten antara Google Maps dan Leaflet. Pilihan tampilan peta dipertahankan antar buka-tutup modal dalam satu sesi.

---

## Version 26.06.00006 (Enterprise) - Mono
*Compiled Date: 2026-06-15 | Migration: stg -> production*

### Improved
- Mengosongkan innerHTML elemen `leafletMapElement` sebelum inisialisasi ulang peta (Google Maps atau Leaflet) di `layouts/app.php` agar tidak ada sisa elemen DOM dari render sebelumnya yang menyebabkan konflik visual.
- Menambahkan deteksi otomatis perubahan `google_maps_api_key` pada fungsi `saveSettings()` di `admin/settings.php` dan `hrops/settings.php`. Jika kunci API Google Maps berubah (ditambah atau dihapus), halaman akan otomatis direload penuh (`window.location.reload()`) setelah 2 detik agar script SDK Google Maps dari `layouts/app.php` termuat atau terhapus secara bersih tanpa perlu refresh manual.

---

## Version 26.06.00005 (Enterprise) - Mono
*Compiled Date: 2026-06-15 | Migration: stg -> production*

### Improved
- Mengubah ubin peta Leaflet pada modal openMapPicker() di admin/settings.php dan hrops/settings.php agar memuat ubin Google Maps Roadmap, menyajikan visual Google Maps secara gratis tanpa memerlukan kunci API.

### Fixed
- Mengembalikan pemuatan script Google Maps API di layouts/app.php menjadi kondisional untuk mencegah munculnya popup error Google Maps jika API Key kosong.

---

## Version 26.06.00004 (Enterprise) - Mono
*Compiled Date: 2026-06-15 | Migration: stg -> production*

### Improved
- Mengubah pemuatan script Google Maps API di layouts/app.php agar dimuat secara global dan default untuk memastikan peta pemilihan koordinat langsung menampilkan Google Maps.

---

## Version 26.06.00003 (Enterprise) - Mono
*Compiled Date: 2026-06-15 | Migration: stg -> production*

### Added
- Integrasi pemilih lokasi peta interaktif hybrid (Google Maps / Leaflet) di dalam SweetAlert2 modal dengan Google Places Autocomplete dan pencarian Nominatim.

### Improved
- Menyelaraskan lebar input Logo Aplikasi (Material Icon) agar mengambil sisa ruang kolom md:col-span-2 pada form pengaturan profil.

### Removed
- Menghapus skeleton loader di seluruh halaman aplikasi untuk kecepatan transisi halaman.
- Menghapus gaya CSS skeletonScale, markup overlay, dan helper JS dari file layouts/app.php.

---

## Version 26.06.00002 (Enterprise) - Mono
*Compiled Date: 2026-06-15 | Migration: stg -> production*

### Added
- Integrasi Kalender Libur Google: Tombol 'Ambil dari Google Calendar' untuk menarik seluruh hari libur nasional dan cuti bersama dari feed kalender resmi Google, lengkap dengan checklist approval bagi admin agar tetap fleksibel menentukan hari libur perusahaan.
- Sistem Loading Skeleton Scale: transisi SPA (Single Page Application) dan browser navigation (popstate) menampilkan skeleton bernapas dengan efek scale cubic-bezier jika pemuatan halaman lambat (> 2 detik).
- Initial Page Load Skeleton: mendeteksi jika halaman pertama kali dibuka lambat (> 2 detik) dan menampilkan overlay skeleton bernapas, kemudian memudar halus ketika halaman selesai dimuat.
- Minimal Jam Pulang: batasan jam kepulangan karyawan (clock-out) di menu pengaturan, yang menolak presensi pulang jika dilakukan sebelum waktunya.
- Switch Toggle Minimal Jam: opsi untuk mengaktifkan/menonaktifkan batasan minimal jam masuk & minimal jam pulang secara mandiri dengan switch toggle UI premium.

### Improved
- Konsistensi Warna Box: menyelaraskan warna box deskripsi live preview jam masuk dan jam pulang menjadi seragam (amber theme) untuk keindahan estetika.
- Pembersihan Antarmuka: menghapus deskripsi paragraf penjelasan statis yang redundan pada batas toleransi pulang lambat, digantikan oleh dynamic live preview.
- Notifikasi Penyimpanan: mengubah notifikasi pop-up simpan pengaturan dari Toast di pojok kanan atas menjadi SweetAlert2 modal dialog standar di tengah layar dengan bar indikator hitung mundur (timer: 1.5 detik).

---

## Version 26.06.00001 (Enterprise) - Mono
*Compiled Date: 2026-06-13 | Migration: stg -> production*

### Added
- Fitur deteksi aktivitas idle (idle session warning) dengan modal popup glassmorphic blur natural, info waktu hening, opsi Ya/Tidak, dan hitung mundur auto-logout

### Improved
- Penyesuaian dimensi modal deteksi idle agar lebih ramping (compact) dan memastikan teks tombol tidak melipat

### Fixed
- Perbaikan tombol Perbarui Aplikasi Sekarang yang unclickable akibat JS syntax error
- Perbaikan pratinjau berkas PDF agar tidak terunduh otomatis dengan menonaktifkan CSP pada file stream dan melonggarkan Cache-Control ke no-cache

### Security
- Pencegahan akses langsung berkas via URL mentah (onboarding, cuti, koreksi, klaim) dengan menyajikan halaman kustom 403 Forbidden

### Changed
- Penyesuaian format versi rilis Beta menjadi SemVer 3-digit zero-padded tanpa kata Beta

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

