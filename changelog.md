# siCare Release Changelog

All public releases of the siCare HRMS Payroll system are listed here.

---

## Version 001.001.000-LTS (Business)
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


## Version 26.05-LTS (Business)
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

