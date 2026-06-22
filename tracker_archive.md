# siCare Development Tracker Archive

This file archives past compiled/published raw logs.

| Date | Type | Description | Original Code/Behavior | Stage | Developer |
| :--- | :--- | :--- | :--- | :--- | :--- |
| 2026-06-11 | Added | Menu Presensi GPS tracking terintegrasi untuk karyawan WFH/WFO | Tidak ada validasi GPS sebelumnya | env | Alex Rivera |
| 2026-06-10 | Fixed | Validasi double clock-in agar tidak menduplikasi baris absensi | Database sempat melemparkan error duplikasi | dev | Budi Santoso |
| 2026-06-09 | Improved | Mempercepat query perhitungan rekap absensi bulanan untuk payroll | Query lambat saat memproses 1000+ baris | tqa | Budi Santoso |
| 2026-06-08 | Added | Integrasi library SweetAlert2 untuk dialog konfirmasi tindakan | Menggunakan window.confirm browser bawaan | env | Amanda Putri |
| 2026-06-07 | Security | Penggunaan finfo_open() pada backend upload berkas klaim | Mempercayai ekstensi berkas mentah dari client | tqa | Rian Hidayat |
| 2026-06-06 | Fixed | Memperbaiki overflow scroll horizontal pada tabel master data di mobile | Tabel terpotong pada lebar layar < 320px | dev | Amanda Putri |
| 2026-06-05 | Added | Fitur self-reflection kuartalan (Q2) untuk pelacakan performa karyawan | Formulir penilaian manual di Excel | env | Alex Rivera |
| 2026-06-04 | Security | Audit log otomatis untuk mencatat semua koreksi data sensitif oleh HR | Perubahan data langsung terupdate tanpa riwayat | tqa | Rian Hidayat |
| 2026-06-03 | Added | Sistem session handler hybrid yang menyimpan state di Redis/DB | Session disimpan di file lokal tunggal | dev | Budi Santoso |
| 2026-06-12 | Added | Fitur ekspor slip gaji ke format PDF terenkripsi kata sandi (tanggal lahir) | Slip gaji diunduh tanpa password PDF | env | Alex Rivera |
| 2026-06-12 | Fixed | Bug pembulatan pecahan rupiah pada penghitungan tunjangan lembur | Terjadi selisih pembulatan desimal kecil | dev | Budi Santoso |
| 2026-06-12 | Improved | Optimasi loading rekap kehadiran karyawan pada dashboard supervisor | Loading lambat saat data di atas 500 baris | tqa | Amanda Putri |
| 2026-06-12 | Security | Validasi payload token JWT OAuth2 untuk integrasi Google Login | Memverifikasi token tanpa validasi payload penuh | dev | Rian Hidayat |
| 2026-06-12 | Fixed | Perbaikan tombol Perbarui Aplikasi Sekarang yang unclickable akibat JS syntax error | Tombol tidak merespons dikarenakan template literal bersarang | dev | Antigravity |
| 2026-06-12 | Changed | Penyesuaian format versi rilis Beta menjadi SemVer 3-digit zero-padded tanpa kata Beta | Menggunakan format penanggalan kalender atau kata Beta | env | Antigravity |
| 2026-06-16 | Added | Integrasi event listener online/offline global untuk memicu popup SweetAlert2 ketika koneksi internet terputus atau terhubung kembali | Mengandalkan Service Worker offline page bawaan saja | stg | Antigravity |
| 2026-06-16 | Improved | Optimasi Service Worker sw.js untuk melakukan caching halaman HTML secara dinamis pada request GET | Hanya meng-cache static assets seperti CSS, JS, dan gambar saja | stg | Antigravity |
| 2026-06-16 | Fixed | Mengatasi double popup error saat SPA page navigation gagal akibat status error server | Muncul popup error bawaan SPA yang menimpa popup detail database error | stg | Antigravity |
| 2026-06-16 | Added | Penanganan error database (PDOException) pada server-side di Database.php yang mengembalikan respon JSON untuk AJAX dan merender halaman error pangkalan data berdesain premium | Terjadi uncaught PDOException yang memicu PHP Fatal Error mentah pada browser | stg | Antigravity |
| 2026-06-16 | Security | Menyembunyikan detail log developer (stack trace) pada halaman gangguan database jika environment aplikasi (APP_ENV) diset ke production | Menampilkan rincian kredensial sensitif dalam trace error di semua environment | stg | Antigravity |
| 2026-06-22 | Added | Pembuatan akun pengguna tingkat C-Level (CEO, CFO, CTO, COO, CBO) dengan kata sandi bawaan "password" | Belum ada akun pengguna tingkat C-Level | local | Antigravity |
| 2026-06-22 | Added | Pembuatan akun pengguna hiring manager untuk sub-departemen level 2 dan level 3 dengan kata sandi bawaan "password" | Belum ada akun hiring manager sub-departemen | local | Antigravity |
| 2026-06-22 | Added | Pembuatan akun pengguna HR Operations, Recruiter, dan Candidate | Belum ada akun HR Ops, Recruiter, dan Candidate | local | Antigravity |
| 2026-06-22 | Improved | Peningkatan batas kedalaman struktur departemen maksimal menjadi 10 level | Struktur departemen dibatasi 5 level | local | Antigravity |
| 2026-06-22 | Added | Fitur pemindahan departemen interaktif menggunakan drag-and-drop | Pemindahan departemen hanya melalui dropdown form | local | Antigravity |
| 2026-06-22 | Changed | Menampilkan menu Struktur Departemen juga pada peran Superadmin dan membersihkan cache sidebar | Menu hanya ditampilkan untuk peran Admin | local | Antigravity |
| 2026-06-22 | Removed | Pembersihan file-file cadangan, log lama, file duplikat, dan skrip pelacakan yang tidak lagi digunakan | Banyak file sampah/tidak terpakai di folder root | local | Antigravity |
| 2026-06-22 | Fixed | Perbaikan tumpang tindih tombol menu hamburger dengan banner simulasi pada perangkat tablet/mobile | Tombol hamburger tertutup oleh banner simulasi pada layar kecil | local | Antigravity |
| 2026-06-22 | Fixed | Perbaikan error syntax JavaScript pada tabel manajemen pengguna akibat baris kode pagination terpotong | Tabel terus berputar karena eror sintaks JS | local | Antigravity |
| 2026-06-22 | Fixed | Perbaikan kegagalan tombol simulasi login bagi pengguna yang memiliki nama bertanda kutip tunggal | Tombol simulasi tidak merespon eror parsing string | local | Antigravity |
| 2026-06-22 | Improved | Refaktor filter dropdown Global Approval Center agar memuat departemen Level 1 dinamis dari database | Kategori departemen hardcoded | local | Antigravity |
| 2026-06-22 | Refactored | Refaktorisasi string literal dari kutip satu ke kutip dua di AuthController.php dan DashboardController.php | Menggunakan kutip tunggal | local | Antigravity |
| 2026-06-22 | Added | Penambahan dokumentasi arsitektur MVC/OOP terperinci pada seluruh method di AuthController dan DashboardController | Metode controller tidak memiliki penjelasan terdokumentasi | local | Antigravity |
