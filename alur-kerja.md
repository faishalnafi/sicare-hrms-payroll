# Alur Kerja & Arsitektur Menu: Sistem Rekrutmen (ATS) & Manajemen Karyawan (HRIS)

Dokumen ini menjelaskan rancangan alur kerja menyeluruh (*end-to-end*) mulai dari proses pendaftaran kandidat, pelengkapan berkas, hingga transisi menjadi karyawan aktif, beserta detail fitur dan struktur menu yang ditampilkan untuk setiap *role* pengguna sesuai dengan standar korporasi multinasional.

---

## 1. Spesifikasi Global & Arsitektur Sistem
Untuk memastikan kepatuhan, keamanan data, dan skalabilitas tinggi, sistem ini mengimplementasikan aturan baku berikut:
- **Sistem Identitas Ganda (Dual ID)**: 
  - **UUID v4 (`CHAR(36)`)**: Digunakan sebagai *Primary Key* internal di database untuk seluruh tabel. Bersifat acak, terisolasi, dan digunakan untuk relasi *Foreign Key* guna mencegah eksploitasi URL/ID (*Insecure Direct Object Reference*).
  - **Employee ID (Staff ID)**: Identitas *human-readable* (contoh: `EM-2026-0034`) yang dibuat secara kustom oleh HR atau dibuat otomatis oleh sistem saat transisi menjadi karyawan. Digunakan untuk keperluan operasional harian, presensi, login, dan pencarian.
- **Keamanan Manipulasi Berkas (Upload Limit 10MB)**:
  - Batas ukuran file maksimal mutlak untuk seluruh unggahan (CV, Foto KTP, KK, NPWP, Buku Tabungan, Bukti Klaim) adalah **10MB**.
  - Validasi sisi server wajib menggunakan PHP ekstensi `finfo` (`finfo_open(FILEINFO_MIME_TYPE)`) untuk memeriksa struktur asli berkas, bukan sekadar mempercayai ekstensi dari *client*.
  - File sensitif disimpan di dalam direktori penyimpanan privat (*secured storage*) dan di-*serve* melalui kendali *controller* terotorisasi.
- **Aset & Komponen Visual**:
  - Seluruh ikon wajib menggunakan **Google Material Symbols API** (Outlined).
  - Tipografi global menggunakan Google Fonts API (Inter / Plus Jakarta Sans).
  - Dialog interaksi menggunakan *library* SweetAlert2 (tidak ada dialog bawaan browser).

---

## 2. Alur Kerja End-to-End (Step-by-Step)

```
[Candidate Portal] Registrasi & Apply (CV/Resume)
       │
       ▼
[Recruiter / ATS] Screening Berkas & Shortlist
       │
       ▼
[Hiring Manager] Wawancara Teknis & Scoring Rubric
       │
       ▼
[Recruiter] Upload Offering Letter & E-Signature Kontrak
       │
       ▼
[Onboarding Phase] Kandidat Mengisi Berkas Sensitif (KTP, NPWP, Bank)
       │
       ▼
[HR Operations] Verifikasi Berkas & Approve Onboarding
       │
       ▼
[System Automation] Database Transaction: Role Candidate ➔ Employee + Generate Employee ID
       │
       ▼
[Employee Portal / ESS] Akses Absensi, Cuti, Slip Gaji, & Data Correction
```

### Tahap 1: Registrasi & Lamaran Low Friction (Role: Candidate)
1. **Pencarian Kerja**: Kandidat mengunjungi Portal Karir publik perusahaan, meninjau deskripsi lowongan, kualifikasi, dan lokasi penempatan.
2. **Pembuatan Akun**: Kandidat mendaftarkan diri dengan memasukkan nama lengkap, email, nomor WhatsApp, dan kata sandi. Akun mendapatkan hak akses awal sebagai `candidate`.
3. **Pengisian Formulir Lamaran**: Untuk mengurangi hambatan (*friction*), kandidat hanya diminta mengisi profil profesional esensial: riwayat pendidikan terakhir, pengalaman kerja terakhir, ekspektasi gaji, *notice period* (ketersediaan tanggal), tautan LinkedIn/Portofolio, serta mengunggah file CV/Resume (PDF, maks 10MB).
4. **Submit Lamaran**: Lamaran masuk ke sistem dengan status awal `Applied`. Kandidat dapat memantau status seleksi secara langsung dari *dashboard* akun mereka.

### Tahap 2: Penyaringan & Kolaborasi Seleksi (Role: Recruiter & Hiring Manager)
1. **Screening Awal (Recruiter)**: Tim *Talent Acquisition* (Recruiter) meninjau CV kandidat yang masuk. Sistem membantu melakukan filter otomatis berdasarkan kriteria dasar (seperti tingkat pendidikan minimum atau kesediaan relokasi). Status lamaran dipindahkan ke `Screening` atau `Shortlisted`.
2. **Penjadwalan Wawancara**: Recruiter menjadwalkan wawancara dan mengirimkan undangan otomatis berisi tanggal, waktu, nama pewawancara, dan tautan pertemuan video melalui sistem. Kandidat melakukan konfirmasi (Terima/Reschedule) melalui portal mereka.
3. **Penilaian Teknis (Hiring Manager)**: Kepala divisi terkait (`hiring_manager`) masuk ke portal mereka untuk melihat daftar kandidat yang dijadwalkan wawancara dengan mereka. Setelah wawancara selesai, Hiring Manager mengisi formulir rubrik penilaian (*scoring rubric*) langsung di aplikasi dan memberikan keputusan akhir: *Pass* atau *Fail*.

### Tahap 3: Penawaran Kerja & Kontrak Digital (Role: Recruiter & Candidate)
1. **Penerbitan Offering**: Recruiter meninjau rekomendasi dari Hiring Manager. Jika disetujui, Recruiter mengunggah berkas *Offering Letter* dan draf kontrak kerja formal ke sistem. Status berubah menjadi `Offering Issued`.
2. **Persetujuan Kandidat**: Kandidat menerima notifikasi email, masuk ke portal, membaca penawaran, dan dapat memberikan tanda tangan digital (*e-signature*) langsung di dalam aplikasi untuk menyatakan persetujuan. Status berubah menjadi `Contract Signed`.

### Tahap 4: Onboarding & Pelengkapan Berkas Terkunci (Role: Candidate ➔ HR Operations)
1. **Buka Akses Onboarding**: Begitu kontrak ditandatangani, sistem secara otomatis mengunci portal rekrutmen kandidat dan membuka halaman **Modul Onboarding Karyawan Baru**.
2. **Input Administrasi Legal**: Kandidat diwajibkan melengkapi seluruh data administratif sensitif yang dibutuhkan oleh regulasi korporasi dan hukum negara, yaitu:
   - **Kependudukan**: NIK KTP, Nomor KK, Alamat Domisili, Status Pernikahan (+ upload berkas KTP & KK).
   - **Finansial & Pajak**: Nomor NPWP, Nama Bank, Nomor Rekening Pajak/Payroll (+ upload foto kartu NPWP & halaman depan buku tabungan).
   - **Asuransi & Kesehatan**: Nomor BPJS Kesehatan, BPJS Ketenagakerjaan (jika sudah ada), Golongan Darah, dan Hasil Medical Check-Up (jika diwajibkan).
   - **Kontak Darurat**: Nama, hubungan keluarga, dan nomor telepon yang bisa dihubungi.
   - **Inventaris**: Pas foto formal (untuk ID Card) dan ukuran seragam.
3. **Verifikasi HR Operations**: Tim `hr_ops` mendapatkan notifikasi pengajuan onboarding baru. Mereka memeriksa kesesuaian data yang diinput dengan berkas dokumen fisik yang diunggah (Maks 10MB per file).
4. **Kelulusan Onboarding (Atomic Transaction)**: Jika seluruh data valid, HR Ops menekan tombol **"Setujui & Aktifkan Karyawan"**. Sistem menjalankan transaksi database aman (`beginTransaction()`):
   - Status peran akun diubah dari `candidate` menjadi `employee`.
   - Mengalokasikan nomor identitas resmi karyawan (**Employee ID / Staff ID**).
   - Mengunci data administratif di atas agar tidak dapat diubah sembarangan oleh karyawan tanpa persetujuan HR.
   - Mengaktifkan profil karyawan aktif di dalam sistem HRIS internal.

### Tahap 5: Karyawan Aktif & Layanan Mandiri (Role: Employee)
1. **Akses Dashboard Internal**: Pengguna kini masuk ke sistem menggunakan portal *Employee Self-Service* (ESS).
2. **Operasional Harian**: Karyawan menggunakan aplikasi untuk melakukan absensi harian (*clock-in/out* dengan koordinat GPS), mengajukan cuti/izin, mengunggah nota klaim pengobatan/perjalanan (*reimbursement*), serta mengunduh slip gaji bulanan secara mandiri.
3. **Mekanisme Perbaikan Data (Data Correction Request)**: Jika di kemudian hari ada perubahan data administratif yang terkunci (misalnya pindah alamat, ganti rekening bank, atau perubahan status pernikahan dari lajang menjadi kawin demi penyesuaian pajak PTKP), karyawan wajib menggunakan fitur pengajuan perbaikan data dengan mengunggah dokumen bukti baru untuk diverifikasi kembali oleh tim `hr_ops`.

---

## 3. Arsitektur Menu & Fitur Berdasarkan Role (RBAC)

Aplikasi dibagi menjadi 8 modul utama berdasarkan tingkat hak akses masing-masing *role*:

### 1. Modul Kandidat (Candidate Portal - Eksternal)
Menu yang ditampilkan saat pengguna masih berstatus pelamar atau dalam proses onboarding:
- **Dashboard Lowongan**: Melihat status dari seluruh lowongan yang pernah dilamar (`Applied`, `Interview`, `Offering`, `Rejected`).
- **Jadwal Wawancara**: Konfirmasi kehadiran undangan wawancara atau tes teknis.
- **Menu Penawaran (Offering & Contract)**: Menampilkan berkas digital surat penawaran, nominal kompensasi, dan tombol e-signature kontrak.
- **Menu Wizard Onboarding (Hanya aktif jika kontrak ditandatangani)**: Formulir bertahap untuk menginput NIK, KK, NPWP, No Rekening, Kontak Darurat, serta area *drag-and-drop* untuk unggah dokumen (Max 10MB, divalidasi `finfo`).

### 2. Modul Karyawan (Employee Portal / ESS - Internal)
Menu layanan mandiri untuk seluruh staf fungsional setelah dinyatakan aktif bekerja:
- **Dashboard Profil Saya**: Menampilkan informasi umum, foto profil, jabatan, divisi, dan `Employee ID`.
- **Menu Presensi**: Tombol *Clock-In* dan *Clock-Out* harian yang terintegrasi dengan deteksi lokasi (GPS) dan riwayat kehadiran bulanan.
- **Menu Manajemen Cuti & Izin**: Fitur pengajuan cuti tahunan, sakit, atau izin khusus dengan grafik sisa kuota cuti. Wajib upload surat dokter (Max 10MB) jika memilih opsi sakit.
- **Menu Finansial Mandiri**: Tempat mengunduh slip gaji bulanan digital dan dokumen bukti potong pajak tahunan (Form 1721-A1).
- **Menu Reimbursement / Klaim**: Formulir pengajuan pengembalian dana operasional atau kesehatan, dilengkapi input nominal dan upload foto kuitansi/struk belanja (Max 10MB).
- **Menu Data Correction Request**: Tombol "Ajukan Perbaikan Data" pada profil untuk memicu modal pengajuan perubahan data terkunci (seperti ganti nomor rekening payroll atau pembaharuan KK).

### 3. Modul Rekruter (Recruiter / Talent Acquisition Dashboard)
Fokus pada pengelolaan siklus hidup pelamar dan alur kerja ATS:
- **Manajemen Lowongan Pekerjaan**: Membuat draf, mempublikasikan, atau menutup lowongan kerja di portal karir utama.
- **Pipeline Pelamar (ATS Track)**: Tampilan visual berbasis papan Kanban untuk menyeret (*drag-and-drop*) status kandidat dari `Applied` ➔ `Screening` ➔ `Shortlisted` ➔ `Interview` ➔ `Offering`.
- **Penjadwalan Massal (Interview Scheduler)**: Integrasi kalender untuk menetapkan jadwal wawancara antara kandidat dan *Hiring Manager*.
- **Penerbitan Kontrak & Offering**: Mengunggah berkas penawaran resmi, melacak apakah pelamar sudah membaca atau menandatangani dokumen tersebut secara digital.

### 4. Modul Manajer Departemen (Hiring Manager Dashboard)
Diberikan kepada kepala divisi yang sedang melakukan penambahan tim:
- **Permintaan Tenaga Kerja (Manpower Requisition)**: Formulir pengajuan penambahan *headcount* karyawan baru di divisinya untuk diajukan ke jajaran direksi.
- **Review Portofolio & CV**: Melihat daftar pelamar yang sudah disaring oleh rekruter khusus untuk kebutuhan divisinya saja.
- **Lembar Wawancara & Scoring Rubric**: Form input nilai teknis, catatan umpan balik (*interview feedback*), dan tombol rekomendasi keputusan (Terima / Tolak kandidat).
- **Persetujuan Tim Internal (Approval Matrix)**: Menyetujui atau menolak pengajuan cuti, presensi lembur, atau klaim *reimbursement* dari staf yang berada langsung di bawah supervisinya.

### 5. Modul Operasional HR (HR Operations / HRIS Admin Dashboard)
Memiliki kendali penuh atas data operasional SDM perusahaan, kepatuhan, dan penggajian:
- **Verifikasi Onboarding Karyawan Baru**: Halaman khusus untuk memeriksa kecocokan data administratif pendaftar baru dengan dokumen asli (KTP, NPWP, Rekening Bank). Dilengkapi tombol "Approve Onboarding" untuk mengonversi kandidat menjadi karyawan aktif.
- **Master Data Karyawan (Core HRIS)**: Basis data terpusat seluruh karyawan perusahaan. Memiliki wewenang mutlak untuk mengubah struktur organisasi, jenjang jabatan, level gaji, departemen, dan mematikan akun jika karyawan *resign* atau diberhentikan.
- **Manajemen Verifikasi Data (Correction Queue)**: Menampilkan antrean pengajuan perbaikan data terkunci dari portal ESS karyawan. HR Ops melakukan review dokumen bukti baru dan menekan tombol "Approve & Overwrite" untuk memperbarui database secara otomatis.
- **Pemrosesan Penggajian (Payroll Integration)**: Menarik rekapitulasi presensi bulanan, total cuti tidak dibayar, serta total klaim *reimbursement* yang disetujui untuk diekspor ke sistem penggajian.

### 6. Modul Eksekutif (Executive / C-Level Dashboard)
Akses laporan makro untuk kebutuhan pengambilan keputusan strategis:
- **Dashboard Analitik SDM**: Grafik analitik real-time yang menampilkan total pengeluaran biaya rekrutmen (*Cost per Hire*), rata-rata durasi perekrutan (*Time to Hire*), statistik persebaran demografi karyawan, rasio perputaran karyawan (*Turnover Rate*), dan tingkat kehadiran perusahaan secara global.
- **Matriks Persetujuan Anggaran (Budget Approval)**: Menyetujui atau menolak permohonan pembukaan posisi kerja skala besar dari departemen yang membutuhkan anggaran *headcount* tambahan.

### 7. Modul Admin (General Admin Dashboard)
Fokus pada pengelolaan operasional harian non-IT secara umum di tingkat perusahaan atau kantor cabang:
- **Manajemen Pengguna & Otorisasi Internal**: Mengatur penugasan peran (*role assignment*) untuk admin tingkat departemen, recruiter, dan hr_ops.
- **Konfigurasi Modul & Parameter Aplikasi**: Mengatur parameter tingkat tinggi non-keamanan seperti template email, format slip gaji, kategori klaim reimbursement, dan parameter libur nasional.
- **Monitoring Aktivitas & Pelaporan**: Melihat visualisasi aktivitas pengguna harian, mengunduh laporan aktivitas operasional, serta menangani keluhan/tiket administratif umum dari modul ESS.

### 8. Modul Superadmin (IT & System Dashboard)
Kendali teknis infrastruktur dan keamanan aplikasi, terisolasi dari data bisnis:
- **Manajemen Pengguna & RBAC**: Membuat akun pengguna internal (HR Ops, Recruiter, Manager, Admin) dan menetapkan batasan peran akses secara ketat.
- **Konfigurasi Sistem Global**: Mengatur parameter global aplikasi, seperti batasan ukuran file (10MB), pengaturan server email (SMTP), enkripsi data, dan integrasi API pihak ketiga (seperti mesin absen biometrik).
- **Audit Log & Security**: Memantau seluruh jejak aktivitas pengguna (*who did what and when*) untuk mendeteksi potensi kebocoran data sensitif karyawan atau manipulasi sistem yang tidak sah.