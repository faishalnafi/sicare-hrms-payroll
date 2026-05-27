# siCare HRMS & Payroll — Web-Based Employee Management System

<p align="center">
  <img src="https://img.shields.io/badge/Platform-Web-blue" alt="Platform Web">
  <img src="https://img.shields.io/badge/Version-v2.0.0--beta.1-purple" alt="Version">
  <img src="https://img.shields.io/badge/License-MIT-green" alt="License MIT">
  <img src="https://img.shields.io/badge/Mainstream-SEO_Friendly-orange" alt="SEO Friendly">
</p>

**siCare** (Sistem Informasi Catatan, Presensi, Rekap, & Evaluasi) adalah aplikasi berbasis web kelas korporasi yang dirancang khusus untuk mempermudah manajemen operasional internal perusahaan secara terpusat, aman, dan mutakhir.

Aplikasi ini menggabungkan modul **Human Resources Information System (HRIS)** dan **Applicant Tracking System (ATS)** ke dalam satu arsitektur terintegrasi yang andal dengan keamanan standar tinggi (UU PDP Compliance).

---

## 🚀 Fitur Utama & Pembaruan Sistem (Key Features & Updates)

Berikut adalah daftar modul dan fitur mendalam yang diimplementasikan pada versi **v2.0.0**:

### 🛡️ 1. Security & Data Protection (Keamanan Tingkat Tinggi)
*   **Dual Identity Isolation**: Menggunakan **UUID v4** (`CHAR(36)`) sebagai *Primary Key* utama di backend untuk keamanan dari serangan IDOR, dipadukan dengan **Employee ID** untuk visual *human-readable*.
*   **Strict File Shield**: Membatasi unggahan berkas maksimal **10MB** dengan pembacaan MIME tipe asli dari binary header menggunakan `finfo` server-side untuk menangkal malware shell upload.
*   **Encrypted Storage**: Seluruh berkas sensitif (KTP, NPWP, slip gaji) disimpan di direktori privat terisolasi (`storage/private/`) dan di-serve via pengontrol terotorisasi.
*   **Prepared Statements & CSRF**: Proteksi kueri database mutlak dan token CSRF di setiap form mutasi data.

### 🏢 2. Multi-Level Organization (Struktur Divisi 5 Level)
*   **Deep Hierarchy Adjacency List**: Mendukung pembuatan divisi bertingkat tak terbatas dengan performa optimal hingga **5 sub-level** (Direktorat -> Divisi -> Departemen -> Sub-Dept -> Tim).
*   **Cascading Dropdowns UI**: Formulir dinamis berbasis AJAX untuk perpindahan divisi karyawan tanpa kebingungan tata letak.
*   **Flexible Job Titles**: Input jabatan manual yang fleksibel per divisi tanpa menimbun tabel master.

### 👥 3. Advanced Employee Self-Service (ESS) & Attendance
*   **Real-time GPS Clocking**: Presensi masuk (*clock-in*) dan keluar (*clock-out*) karyawan yang mengunci koordinat GPS, foto profil dinamis, dan riwayat bulanan.
*   **Smart Leaves Management**: Pengajuan cuti/sakit digital dengan unggahan surat dokter bersyarat yang memotong kuota cuti tahunan secara otomatis.
*   **Data Correction Request**: Mekanisme pengajuan koreksi data sensitif terverifikasi oleh tim HR, menjaga akurasi perpajakan dan penggajian.

### 💼 4. Recruitment & Corporate ATS
*   **Low-Friction Candidate Portal**: Registrasi dan lamaran cepat bagi kandidat eksternal.
*   **Kanban Pipeline ATS**: Pelacakan status pelamar visual berbasis seret-dan-lepas (*drag-and-drop*).
*   **Atomic Onboarding Transition**: Otomasi mutasi aman (`beginTransaction()`) kandidat menjadi karyawan aktif saat verifikasi dokumen selesai.

### 📈 5. Executive Analytics & Immutable Auditing
*   **Macro Dashboard Analytics**: Metrik strategis real-time (*Turnover Rate*, *Cost per Hire*, rekap pengeluaran gaji) untuk C-Level.
*   **Immutable Audit Logs**: Rekam jejak seluruh operasi CRUD yang aman dari pembersihan log sepihak (otomatis menyisakan log kesaksian jika dihapus).

---

## 🛠️ Teknologi yang Digunakan (Tech Stack)

*   **Backend:** PHP 8.x (Custom Native MVC Framework / Core PHP Architecture)
*   **Frontend:** Vanilla JS, CSS3 Modern, TailwindCSS Framework
*   **Database:** MySQL / MariaDB (Prepared Statements & Transaksi Atomik)
*   **Aset & Ikon:** Google Fonts & Google Material Symbols Outlined API (Strict Constraint)
*   **Alert & Dialog:** SweetAlert2 integration (No native browser dialogs)

---

## ⚙️ Panduan Instalasi (Installation Guide)

### 1. Clone Repositori
```bash
git clone https://github.com/faishalnafi/sicare-hrms-payroll.git
cd sicare-hrms-payroll
```

### 2. Konfigurasi Environment
Salin file konfigurasi lingkungan `.env.example` ke `.env` dan atur kredensial database Anda:
```bash
cp .env.example .env
```
Sesuaikan nilai-nilai berikut di dalam file `.env`:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sicare_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Migrasi Database Produksi Aman
Untuk memastikan database versi sebelumnya tidak kehilangan data yang ada, jalankan script update migrasi khusus produksi:
```bash
php database/update_schema.php
```
*Script ini menggunakan klausa pengaman `IF NOT EXISTS` sehingga data karyawan lama tetap utuh.*

### 4. Jalankan Server Lokal
```bash
# Menggunakan build-in PHP server
php -S localhost:8000 -t public
```
Akses sistem melalui peramban Anda di alamat `http://localhost:8000`.

---

## 📌 Informasi Rilis & Staging

*   **Versi Saat Ini**: `v2.0.0--beta.1`
*   **Status Staging**: Siap dideploy ke lingkungan Staging/Production setelah pengujian integrasi akhir selesai.
