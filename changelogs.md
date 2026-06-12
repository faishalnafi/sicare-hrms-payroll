# Panduan Sistem Manajemen Versi & Changelog siCare

Dokumen ini menjelaskan rancangan tata kelola versi rilis, tata cara kompilasi log pengembangan (*tracker*), dan mekanisme sinkronisasi database untuk platform **siCare**.

---

## 1. Pembagian Versi Rilis & Ekosistem (The 3-Dimensional Track System)
Sistem ini menggunakan penentuan versi berbasis kalender (Calendar Versioning) untuk rilis stabil dan SemVer untuk rilis Beta. Baik rilis stabil maupun Beta membawa suffix `-LTS` atau `-STS` tanpa akhiran kata "-Beta". Setiap paket rilis membawa 3 atribut jalur (*fork*):

1. **Jalur Rilis (Release Path):**
   * **Versi Stabil (Production): `YY.MM-Suffix`**
     Dirilis setahun sekali. `26.05-LTS` untuk **LTS (Long Term Support)**, dan `26.09-STS` untuk **STS (Short Term Support)**.
   * **Versi Beta (Public Testing): `XXX.YYY.ZZZ-Suffix` (Format 3 digit per segmen)**
     Dirilis setiap 3 bulan sekali (misal: `001.001.000-LTS` atau `001.001.000-STS`).
   * **Versi Pre-Rilis (Continuous Development): `[Prefix]-YY.MM-NNNNN`**
     Versi otomatis harian dari lingkungan internal. Prefix meliputi `alpha` (uji internal besar), `canary` (versi harian mentah), dan `gamma` (kandidat rilis akhir).
2. **Edisi Aplikasi (Edition):**
   * **Business Edition:** Khusus kebutuhan bisnis/perusahaan multinasional (Eksklusif hanya untuk **LTS**, rilis **STS** tidak mendukung Business Edition).
   * **Community Edition:** Edisi open-source untuk komunitas (Tersedia baik untuk **LTS** maupun **STS** di rilis Beta maupun Stabil).
3. **Arsitektur Repositori (Repo Type):**
   * **Monorepo / Multirepo:** Label arsitektur repositori ini hanya ada/ditampilkan pada rilis **Stabil** saja (baik suffix LTS maupun STS), tidak ditampilkan pada rilis Beta.

---

## 2. Aturan Perhitungan Changelog ke Versi Beta (`XXX.YYY.ZZZ-Suffix`)
Jika kompilasi dilakukan ke jalur Beta, ke-8 tipe pembaruan menentukan angka mana yang akan naik (*increment*):
* **Naik ke X (Major):** Jika terdapat tag `Removed` atau `Changed` (breaking changes).
* **Naik ke Y (Minor):** Jika terdapat tag `Added` atau `Deprecated`.
* **Naik ke Z (Patch):** Diperuntukkan murni bagi `Fixed`, `Security`, dan `Refactored`.
* Penulisan versi final beta wajib menggunakan format 3 digit zero-padded per segmen (misal: `001.001.000-LTS`).

---

## 3. Kompiler & Mekanisme Sinkronisasi
Kompilasi dilakukan dari catatan manual developer di `tracker.md` menggunakan file [changelog_compiler.php](file:///d:/Server/WebApp/siCare/changelog_compiler.php).

### A. Perintah CLI Kompiler
Jalankan di terminal CLI untuk melakukan kompilasi rilis:
```bash
# Kompilasi Versi Stabil LTS Business Monorepo (Contoh rilis 26.05-LTS)
php changelog_compiler.php --type LTS --edition Business --repo monorepo --yes

# Kompilasi Versi Beta Business (Contoh rilis 001.001.000-LTS)
php changelog_compiler.php --type BETA --version 001.001.000-LTS --repo monorepo --yes
```

### B. Output Kompilasi
Kompiler akan mengosongkan log mentah di `tracker.md`, mengarsipkannya ke `tracker_archive.md`, lalu memperbarui berkas publik:
* `changelog.json`: Penyimpanan riwayat rilis terstruktur.
* `changelog.md`: Representasi dokumen riwayat rilis publik.

---

## 4. Mekanisme Pembaruan Sistem & Validasi Fork
Pada panel **Pembaruan Sistem (System Update)** khusus Superadmin, sistem akan membandingkan **Versi Skema Terinstal** di database dengan **Versi Berkas Unggahan (JSON)** terbaru.

### A. Penggunaan Istilah Korporat (Corporate Terminology)
* **Live Schema Connection:** Menggantikan istilah teknis mentah "Database Connection" pada halaman riwayat rilis.
* **Versi Skema Terinstal (*Installed Schema Version*):** Menggantikan istilah "Versi Database Saat Ini".

### B. Pelacakan Tanggal Ganda (Dual Date Tracking)
Setiap rilis mencatat dua tipe tanggal secara transparan:
* **Tanggal Rilis Versi:** Tanggal kompilasi berkas rilis kode (`compiled_date`).
* **Tanggal Update Aplikasi:** Tanggal nyata ketika skema database dimigrasikan secara fisik di server oleh Superadmin (`created_at` database).

### C. UI/UX Detail Perubahan
Semua detail log di bawah kategori pembaruan (`Added`, `Fixed`, dsb.) ditampilkan menggunakan standard bullet lists (`ul`/`li` dengan kelas `.list-disc .pl-5`) untuk keterbacaan tingkat tinggi di seluruh peramban.

### D. Deteksi & Peringatan Jalur (Fork Mismatch Warning)
Saat Superadmin menekan tombol **"Perbarui Aplikasi Sekarang"**, sistem memvalidasi kesesuaian jalur. Jika terdeteksi perpindahan jalur, popup **Pemberitahuan Kritis (SweetAlert2)** akan menghalangi konfirmasi biasa:
1. **Perpindahan Jalur Rilis:** Berubah antara `Stabil` ➔ `Beta` / `Pre-release` (dan sebaliknya). *Transisi sesama jalur stabil (LTS ➔ STS) dianggap aman dan tidak memicu warning.*
2. **Perubahan Edisi:** Berubah antara `Business` ➔ `Community`.
3. **Perubahan Arsitektur Repo:** Berubah antara `Monorepo` ➔ `Multirepo`.

Pengguna wajib menyetujui konfirmasi peringatan perpindahan jalur sebelum sistem melanjutkan proses migrasi database, pemutakhiran tabel `changelogs`, dan pembersihan sesi global (`sessions` reset).