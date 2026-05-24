<?php
$sessName  = $_SESSION['name'] ?? 'User';
$sessEmail = $_SESSION['email'] ?? '';
$sessRole  = $_SESSION['role'] ?? 'candidate';
$profilePic = $_SESSION['profile_picture'] ?? null;

$db = \App\Config\Database::getInstance()->getConnection();
if (isset($_SESSION['user_id'])) {
    $userQuery = $db->prepare("SELECT profile_picture FROM users WHERE id = :id");
    $userQuery->execute(['id' => $_SESSION['user_id']]);
    $dbUser = $userQuery->fetch();
    if ($dbUser && !empty($dbUser['profile_picture'])) {
        $profilePic = $dbUser['profile_picture'];
    }
}

if (empty($profilePic)) {
    $hash = md5(strtolower(trim($sessEmail)));
    $profilePic = "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
}

$roleLabel = str_replace('_', ' ', $sessRole);

$pathLabels = [
    'candidate/jobs' => [
        'title' => 'Dashboard Lowongan Pekerjaan',
        'desc' => 'Lihat lowongan yang tersedia dan pantau status lamaran Anda.',
        'features' => [
            ['title' => 'Daftar Lowongan Aktif', 'desc' => 'Eksplorasi posisi karir terbuka di perusahaan.'],
            ['title' => 'Status Lamaran Kerja', 'desc' => 'Pantau kemajuan berkas Anda secara real-time.'],
            ['title' => 'Notifikasi Pembaruan', 'desc' => 'Dapatkan info terkini saat status seleksi Anda berubah.'],
            ['title' => 'Filter Cepat Kategori', 'desc' => 'Cari posisi yang sesuai bidang dan kualifikasi Anda.']
        ]
    ],
    'candidate/interviews' => [
        'title' => 'Jadwal Wawancara',
        'desc' => 'Konfirmasi dan kelola jadwal wawancara Anda.',
        'features' => [
            ['title' => 'Integrasi Kalender', 'desc' => 'Lihat tanggal, jam, dan zona waktu wawancara.'],
            ['title' => 'Tautan Pertemuan Video', 'desc' => 'Akses langsung link video conference (Meet/Zoom).'],
            ['title' => 'Konfirmasi Kehadiran', 'desc' => 'Konfirmasi kehadiran atau ajukan reschedule dengan satu klik.'],
            ['title' => 'Informasi Pewawancara', 'desc' => 'Ketahui tim rekruter atau manajer yang akan mewawancarai Anda.']
        ]
    ],
    'candidate/offerings' => [
        'title' => 'Penawaran & Kontrak Kerja',
        'desc' => 'Review surat penawaran kerja dan draf kontrak formal.',
        'features' => [
            ['title' => 'Review Offering Letter', 'desc' => 'Periksa detail gaji, fasilitas, dan deskripsi tugas.'],
            ['title' => 'Tanda Tangan Digital', 'desc' => 'Bubuhkan e-signature aman untuk menyetujui penawaran.'],
            ['title' => 'Unduh Draf Kontrak', 'desc' => 'Simpan berkas perjanjian kerja resmi dalam format PDF.'],
            ['title' => 'Riwayat Negosiasi', 'desc' => 'Akses catatan komunikasi terkait penawaran kerja Anda.']
        ]
    ],
    'candidate/onboarding' => [
        'title' => 'Wizard Onboarding Mandiri',
        'desc' => 'Lengkapi data administratif sensitif sebelum aktif bekerja.',
        'features' => [
            ['title' => 'Identitas Kependudukan', 'desc' => 'Unggah scan KTP, Kartu Keluarga, dan isi NIK resmi Anda.'],
            ['title' => 'Data Finansial & Pajak', 'desc' => 'Lengkapi NPWP dan nomor rekening bank untuk payroll bulanan.'],
            ['title' => 'BPJS & Riwayat Kesehatan', 'desc' => 'Masukkan nomor kepesertaan BPJS Kesehatan dan BPJS TK.'],
            ['title' => 'ID Card & Inventaris', 'desc' => 'Unggah pas foto formal dan tentukan ukuran seragam perusahaan.']
        ]
    ],
    'employee/profile' => [
        'title' => 'Profil & Akun Pribadi',
        'desc' => 'Kelola informasi pribadi, preferensi, dan keamanan akun Anda.',
        'features' => [
            ['title' => 'Informasi Personal Mandiri', 'desc' => 'Ubah biodata dasar, kontak darurat, dan foto profil Anda.'],
            ['title' => 'Audit Log Keamanan', 'desc' => 'Pantau riwayat masuk dan perubahan krusial pada akun.'],
            ['title' => 'Ubah Kata Sandi Berkala', 'desc' => 'Perbarui sandi akun dengan indikator kekuatan sandi real-time.'],
            ['title' => 'Pengajuan Koreksi Data', 'desc' => 'Minta pembaharuan data administratif terkunci ke tim HR.']
        ]
    ],
    'employee/attendance' => [
        'title' => 'Manajemen Presensi Harian',
        'desc' => 'Catat waktu kerja Anda dengan akurat berbasis lokasi.',
        'features' => [
            ['title' => 'Clock-In & Clock-Out', 'desc' => 'Lakukan absensi masuk dan pulang harian dengan mudah.'],
            ['title' => 'Verifikasi Lokasi (GPS)', 'desc' => 'Sistem mencatat koordinat lokasi kerja presisi.'],
            ['title' => 'Riwayat Kehadiran Bulanan', 'desc' => 'Pantau akumulasi jam kerja, keterlambatan, dan jam lembur.'],
            ['title' => 'Klaim Koreksi Absen', 'desc' => 'Ajukan permohonan koreksi jika lupa melakukan clock-in/out.']
        ]
    ],
    'employee/leaves' => [
        'title' => 'Pengajuan Cuti & Izin Kerja',
        'desc' => 'Ajukan cuti tahunan, sakit, atau izin khusus Anda.',
        'features' => [
            ['title' => 'Formulir Pengajuan Cuti', 'desc' => 'Pilih tanggal, jenis cuti, dan isi keterangan alasan.'],
            ['title' => 'Grafik Kuota Cuti', 'desc' => 'Lihat sisa kuota cuti tahunan Anda secara dinamis.'],
            ['title' => 'Unggah Surat Dokter', 'desc' => 'Lampirkan bukti surat dokter untuk pengajuan cuti sakit.'],
            ['title' => 'Status Persetujuan Atasan', 'desc' => 'Pantau apakah cuti disetujui oleh Hiring Manager Anda.']
        ]
    ],
    'employee/finance' => [
        'title' => 'Informasi Finansial & Gaji Mandiri',
        'desc' => 'Akses slip gaji bulanan dan bukti potong pajak tahunan.',
        'features' => [
            ['title' => 'Unduh Slip Gaji Digital', 'desc' => 'Simpan bukti pembayaran gaji bulanan resmi berformat PDF.'],
            ['title' => 'Bukti Potong Pajak', 'desc' => 'Dapatkan Form 1721-A1 tahunan untuk pelaporan SPT Anda.'],
            ['title' => 'Rincian Komponen Gaji', 'desc' => 'Lihat detail tunjangan, potongan BPJS, dan PPh 21.'],
            ['title' => 'Informasi Akun Payroll', 'desc' => 'Pastikan nomor rekening bank penerima gaji terdaftar dengan benar.']
        ]
    ],
    'employee/reimbursements' => [
        'title' => 'Pengajuan Klaim & Reimbursement',
        'desc' => 'Ajukan klaim dana biaya operasional atau pengobatan.',
        'features' => [
            ['title' => 'Formulir Klaim Baru', 'desc' => 'Pilih kategori reimbursement, isi nominal, dan keterangan.'],
            ['title' => 'Unggah Nota & Kuitansi', 'desc' => 'Unggah foto bukti bayar asli untuk validasi klaim (Max 10MB).'],
            ['title' => 'Matriks Persetujuan Cepat', 'desc' => 'Alur verifikasi berjenjang dari atasan hingga finance.'],
            ['title' => 'Riwayat Pembayaran Klaim', 'desc' => 'Pantau tanggal pencairan dana klaim yang disetujui.']
        ]
    ],
    'recruiter/jobs' => [
        'title' => 'Manajemen Lowongan Pekerjaan',
        'desc' => 'Kelola draf, publikasi, dan penutupan loker perusahaan.',
        'features' => [
            ['title' => 'Pembuat Loker Visual', 'desc' => 'Tulis deskripsi pekerjaan dan kualifikasi menggunakan editor rapi.'],
            ['title' => 'Publikasi Sekali Klik', 'desc' => 'Tayangkan loker di portal karir internal dan publik.'],
            ['title' => 'Atur Kategori Jabatan', 'desc' => 'Klasifikasikan lowongan berdasarkan divisi dan tingkat keahlian.'],
            ['title' => 'Arsip & Histori Lowongan', 'desc' => 'Simpan loker lama untuk dibuka kembali di masa depan.']
        ]
    ],
    'recruiter/ats' => [
        'title' => 'Pipeline Seleksi Pelamar (ATS)',
        'desc' => 'Pantau alur seleksi kandidat menggunakan Kanban board visual.',
        'features' => [
            ['title' => 'Kanban Board Interaktif', 'desc' => 'Seret pelamar dari tahap Applied, Screening, s.d. Offering.'],
            ['title' => 'Screening CV Cepat', 'desc' => 'Bandingkan kualifikasi, riwayat kerja, dan ekspektasi gaji.'],
            ['title' => 'Filter Pelamar Pintar', 'desc' => 'Cari kandidat berdasarkan kata kunci keahlian atau IPK.'],
            ['title' => 'Komunikasi Massal', 'desc' => 'Kirim email status rekrutmen secara otomatis ke banyak kandidat.']
        ]
    ],
    'recruiter/interviews' => [
        'title' => 'Penjadwalan Wawancara & Evaluasi',
        'desc' => 'Atur agenda pertemuan kandidat dengan tim pewawancara.',
        'features' => [
            ['title' => 'Kalender Wawancara Terpadu', 'desc' => 'Pantau slot waktu kosong tim rekruter dan Hiring Manager.'],
            ['title' => 'Undangan Otomatis', 'desc' => 'Kirim tautan konfirmasi jadwal langsung ke akun kandidat.'],
            ['title' => 'Pengingat Otomatis (Reminder)', 'desc' => 'Kirim email notifikasi pengingat sebelum sesi interview.'],
            ['title' => 'Catatan Koordinasi Tim', 'desc' => 'Bagikan berkas pendukung kandidat dengan tim penilai.']
        ]
    ],
    'recruiter/offerings' => [
        'title' => 'Penerbitan Kontrak & Offering Letter',
        'desc' => 'Kelola pembuatan surat penawaran kerja dan draf kontrak.',
        'features' => [
            ['title' => 'Template Surat Penawaran', 'desc' => 'Hasilkan berkas offering letter dinamis secara instan.'],
            ['title' => 'Pemantau Tanda Tangan', 'desc' => 'Lihat status apakah kandidat sudah membaca atau menandatangani.'],
            ['title' => 'Persetujuan Gaji Internal', 'desc' => 'Validasi nominal gaji dengan jajaran manajemen sebelum rilis.'],
            ['title' => 'Arsip Berkas Rekrutmen', 'desc' => 'Simpan kontrak digital yang ditandatangani dengan aman di cloud.']
        ]
    ],
    'manager/requisitions' => [
        'title' => 'Permintaan Penambahan Tenaga Kerja',
        'desc' => 'Ajukan headcount karyawan baru di departemen Anda.',
        'features' => [
            ['title' => 'Manpower Requisition Form', 'desc' => 'Isi posisi yang dibutuhkan, kualifikasi, dan alasan penambahan.'],
            ['title' => 'Alur Persetujuan Direksi', 'desc' => 'Kirim permohonan anggaran fungsional ke C-Level.'],
            ['title' => 'Lacak Status Headcount', 'desc' => 'Pantau progres pengajuan anggaran posisi divisi Anda.'],
            ['title' => 'Kolaborasi dengan Recruiter', 'desc' => 'Diskusi langsung dengan rekruter pasca pengajuan disetujui.']
        ]
    ],
    'manager/candidates' => [
        'title' => 'Review Portofolio & CV Pelamar',
        'desc' => 'Tinjau berkas kandidat yang disaring oleh rekruter.',
        'features' => [
            ['title' => 'Akses CV & Portofolio', 'desc' => 'Tinjau resume profesional dan berkas karya kandidat.'],
            ['title' => 'Shortlist Mandiri', 'desc' => 'Pilih pelamar terbaik untuk lanjut ke sesi wawancara teknis.'],
            ['title' => 'Bandingkan Kompetensi', 'desc' => 'Bandingkan nilai kecocokan kandidat secara side-by-side.'],
            ['title' => 'Disposisi Umpan Balik', 'desc' => 'Tinggalkan catatan awal untuk ditindaklanjuti tim rekruter.']
        ]
    ],
    'manager/interviews' => [
        'title' => 'Lembar Penilaian & Rubrik Wawancara',
        'desc' => 'Isi scoring rubric wawancara teknis kandidat.',
        'features' => [
            ['title' => 'Rubrik Penilaian Terstruktur', 'desc' => 'Beri skor kompetensi kandidat pada form terstandarisasi.'],
            ['title' => 'Catatan Wawancara Teknis', 'desc' => 'Tulis kelebihan dan kelemahan pelamar saat interview.'],
            ['title' => 'Rekomendasi Keputusan', 'desc' => 'Tentukan status kelulusan kandidat (Pass/Fail) secara langsung.'],
            ['title' => 'Konsolidasikan Skor Tim', 'desc' => 'Kombinasikan penilaian dari sesama panel pewawancara.']
        ]
    ],
    'manager/approvals' => [
        'title' => 'Persetujuan Operasional Tim',
        'desc' => 'Setujui atau tolak pengajuan absensi, cuti, dan reimbursement tim.',
        'features' => [
            ['title' => 'Verifikasi Pengajuan Cuti', 'desc' => 'Review tanggal dan keterangan cuti staf di bawah divisi Anda.'],
            ['title' => 'Persetujuan Lembur & Presensi', 'desc' => 'Validasi klaim kehadiran lembur bulanan anggota tim.'],
            ['title' => 'Tinjau Reimbursement Staf', 'desc' => 'Periksa kuitansi dan nominal klaim belanja operasional tim.'],
            ['title' => 'Isolasi Tim Vertikal', 'desc' => 'Sistem membatasi Anda hanya mengelola anggota divisi sendiri.']
        ]
    ],
    'hrops/onboarding' => [
        'title' => 'Verifikasi Onboarding Karyawan Baru',
        'desc' => 'Review berkas kependudukan dan finansial new hire.',
        'features' => [
            ['title' => 'Verifikasi Berkas KTP/KK', 'desc' => 'Periksa keabsahan berkas kependudukan karyawan baru.'],
            ['title' => 'Validasi Rekening & Pajak', 'desc' => 'Pastikan nomor rekening bank payroll dan NPWP terverifikasi.'],
            ['title' => 'Tombol Aktivasi Akun', 'desc' => 'Konversi status candidate menjadi employee aktif dalam sistem.'],
            ['title' => 'Alokasi Staff ID (NIK)', 'desc' => 'Tentukan nomor induk karyawan resmi secara otomatis.']
        ]
    ],
    'hrops/employees' => [
        'title' => 'Master Data Karyawan (Core HRIS)',
        'desc' => 'Kelola basis data terpusat seluruh karyawan aktif perusahaan.',
        'features' => [
            ['title' => 'Profil Karyawan Komprehensif', 'desc' => 'Akses data kontrak, divisi, jabatan, dan riwayat mutasi.'],
            ['title' => 'Manajemen Status Kontrak', 'desc' => 'Kelola masa berlaku kontrak kerja (Probation, Tetap, PKWT).'],
            ['title' => 'Pembaruan Divisi & Jabatan', 'desc' => 'Mutasi karyawan antar departemen terintegrasi approval requests.'],
            ['title' => 'Penonaktifan Akun (Resign)', 'desc' => 'Proses administratif akun bagi karyawan yang keluar.']
        ]
    ],
    'hrops/verifications' => [
        'title' => 'Verifikasi Data Karyawan (Correction Queue)',
        'desc' => 'Tinjau antrean pengajuan perubahan data administratif terkunci.',
        'features' => [
            ['title' => 'Antrean Pengajuan Pending', 'desc' => 'Lihat daftar karyawan yang mengajukan pembaruan data penting.'],
            ['title' => 'Komparasi Data Lama vs Baru', 'desc' => 'Bandingkan perbedaan data sebelum disetujui secara visual.'],
            ['title' => 'Verifikasi Dokumen Bukti', 'desc' => 'Periksa foto buku tabungan atau scan KK baru yang diunggah.'],
            ['title' => 'Aksi Tulis Otomatis (Overwrite)', 'desc' => 'Sistem langsung memperbarui database setelah Anda memberi ACC.']
        ]
    ],
    'hrops/payroll' => [
        'title' => 'Pemrosesan Penggajian & Pajak (Payroll)',
        'desc' => 'Eksekusi penggajian bulanan, perhitungan BPJS, dan PPh 21.',
        'features' => [
            ['title' => 'Integrasi Rekap Kehadiran', 'desc' => 'Tarik rekapitulasi presensi bulanan secara otomatis.'],
            ['title' => 'Perhitungan Pajak PPh 21', 'desc' => 'Kalkulasi pajak penghasilan sesuai PTKP masing-masing.'],
            ['title' => 'Potongan BPJS Otomatis', 'desc' => 'Hitung potongan iuran BPJS Kesehatan dan BPJS TK.'],
            ['title' => 'Kalkulator Reimbursement', 'desc' => 'Tambahkan nominal klaim yang disetujui ke dalam payroll bulanan.']
        ]
    ],
    'admin/departments' => [
        'title' => 'Struktur Departemen & Hubungan Hierarki',
        'desc' => 'Kelola bagan organisasi korporat hingga kedalaman 5 level.',
        'features' => [
            ['title' => 'Bagan Organisasi Interaktif', 'desc' => 'Visualisasikan bagan organisasi dinamis bergaya Gemini.'],
            ['title' => 'Kelola Sub-Level 5 Tingkat', 'desc' => 'Buat, edit, dan susun sub-divisi secara terstruktur.'],
            ['title' => 'Isolasi Otoritas Kepala', 'desc' => 'Tentukan manager penanggung jawab tiap unit kerja.'],
            ['title' => 'Validasi Cascade Update', 'desc' => 'Sistem otomatis memperbarui jenjang level di bawahnya jika berubah.']
        ]
    ],
    'admin/users' => [
        'title' => 'Manajemen Pengguna & Otoritas Role',
        'desc' => 'Kelola akun pengguna operasional dan penugasan role.',
        'features' => [
            ['title' => 'Manajemen Akun Internal', 'desc' => 'Buat, edit, dan kelola kredensial akun tim HR dan Manager.'],
            ['title' => 'Pemberian Akses Role (RBAC)', 'desc' => 'Atur tingkatan role akun di bawah superadmin.'],
            ['title' => 'Pengajuan Perubahan Mutasi', 'desc' => 'Ajukan perubahan role staf fungsional dengan alur approval.'],
            ['title' => 'Status Aktivasi Pengguna', 'desc' => 'Aktifkan atau bekukan sementara hak akses pengguna aplikasi.']
        ]
    ],
    'executive/analytics' => [
        'title' => 'Dashboard Analitik & Makro Performa SDM',
        'desc' => 'Laporan grafik makro real-time untuk direksi dan komisaris.',
        'features' => [
            ['title' => 'Metrik Cost per Hire', 'desc' => 'Grafik analisis efisiensi pengeluaran anggaran rekrutmen.'],
            ['title' => 'Rasio Turnover Karyawan', 'desc' => 'Pantau statistik keluar masuk karyawan tingkat global.'],
            ['title' => 'Distribusi Demografi Karyawan', 'desc' => 'Lihat penyebaran staf berdasarkan umur, gender, dan jenjang.'],
            ['title' => 'Laporan Kinerja Organisasi', 'desc' => 'Pantau produktivitas dan kepatuhan kehadiran seluruh divisi.']
        ]
    ],
    'executive/budgets' => [
        'title' => 'Matriks Persetujuan Anggaran Organisasi',
        'desc' => 'Review permohonan pembukaan posisi kerja dari para manager.',
        'features' => [
            ['title' => 'Permintaan Anggaran Aktif', 'desc' => 'Tinjau usulan pembukaan loker baru beserta rincian gajinya.'],
            ['title' => 'Evaluasi Kebutuhan Staf', 'desc' => 'Bandingkan kapasitas departemen dengan beban kerja real.'],
            ['title' => 'Tombol ACC Anggaran', 'desc' => 'Setujui atau tolak pembukaan headcount di sistem secara instan.'],
            ['title' => 'Lacak Histori Anggaran', 'desc' => 'Periksa catatan pengeluaran headcount departemen sepanjang tahun.']
        ]
    ],
    'executive/approvals' => [
        'title' => 'Matriks Persetujuan Mutasi & Perubahan Jabatan',
        'desc' => 'Otorisasi persetujuan kenaikan jabatan, promosi, dan mutasi.',
        'features' => [
            ['title' => 'Antrean Pengajuan Mutasi', 'desc' => 'Lihat permohonan promosi staf yang dikirim oleh Admin.'],
            ['title' => 'Perbandingan Data Jabatan', 'desc' => 'Review detail jabatan/role lama versus usulan data baru.'],
            ['title' => 'Aksi Atomic Transaction', 'desc' => 'Sistem langsung mengeksekusi perpindahan database setelah disetujui.'],
            ['title' => 'Umpan Balik Penolakan', 'desc' => 'Berikan catatan alasan terstruktur jika mutasi ditolak.']
        ]
    ],
    'superadmin/users' => [
        'title' => 'Manajemen Otoritas Pengguna & Hak Akses',
        'desc' => 'Kelola wewenang tertinggi seluruh pengguna sistem.',
        'features' => [
            ['title' => 'Manajemen Hak Akses Penuh', 'desc' => 'Buat, ubah, dan hapus akun operasional di seluruh level.'],
            ['title' => ' bypass Persetujuan (Override)', 'desc' => 'Ubah status role atau mutasi staf secara langsung tanpa request.'],
            ['title' => 'Manajemen Tenant & Cabang', 'desc' => 'Konfigurasi parameter multi-cabang korporat.'],
            ['title' => 'Reset Akun Darurat', 'desc' => 'Fasilitasi penanganan akun terkunci secara cepat.']
        ]
    ],
    'superadmin/settings' => [
        'title' => 'Konfigurasi Sistem Global & Parameter Aplikasi',
        'desc' => 'Kelola variabel global, API, dan server email aplikasi.',
        'features' => [
            ['title' => 'Konfigurasi Server SMTP', 'desc' => 'Atur pengiriman email pemberitahuan otomatis perusahaan.'],
            ['title' => 'Manajemen API & Integrasi', 'desc' => 'Kelola X-API-Key untuk integrasi mesin presensi eksternal.'],
            ['title' => 'Batasan Ukuran Unggah File', 'desc' => 'Atur batas maksimal berkas secara global (Maks 10MB).'],
            ['title' => 'Pembersihan Cache Sistem', 'desc' => 'Lakukan manual cache purge untuk membebaskan ruang penyimpanan.']
        ]
    ],
    'superadmin/audit' => [
        'title' => 'Security Audit Logs & Aktivitas Sistem',
        'desc' => 'Memonitor aktivitas keamanan dan rekam jejak pengguna.',
        'features' => [
            ['title' => 'System Audit Trail', 'desc' => 'Pantau riwayat mutasi data sensitif di seluruh database.'],
            ['title' => 'Pelacak Alamat IP Pengguna', 'desc' => 'Identifikasi lokasi dan IP pengakses sistem.'],
            ['title' => 'Aksi Destruktif Clear Log', 'desc' => 'Kosongkan riwayat log lama untuk mempercepat database.'],
            ['title' => 'Otomasi Log Kesaksian', 'desc' => 'Sistem otomatis menyisakan 1 log permanen pasca pembersihan log.']
        ]
    ]
];

$requestedPath = $requestedPath ?? '';
$pageInfo = $pathLabels[$requestedPath] ?? [
    'title' => 'Modul Sedang Dikembangkan',
    'desc' => 'Halaman ini sedang dalam proses pengembangan arsitektur sistem.',
    'features' => [
        ['title' => 'Optimalisasi Alur Kerja', 'desc' => 'Pengembangan antarmuka berkinerja tinggi untuk efisiensi Anda.'],
        ['title' => 'Keamanan Data Berlapis', 'desc' => 'Sistem enkripsi tingkat tinggi untuk keamanan informasi rahasia.'],
        ['title' => 'Integrasi Modul Terpadu', 'desc' => 'Sinkronisasi instan dengan database utama HRIS & ATS.'],
        ['title' => 'Antarmuka Responsif Premium', 'desc' => 'Tampilan modern yang nyaman diakses dari berbagai gawai Anda.']
    ]
];
?>
<div class="space-y-8 animate-fade-in">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight"><?= htmlspecialchars($pageInfo['title']) ?></h1>
            <p class="text-on-surface-variant font-medium text-sm"><?= htmlspecialchars($pageInfo['desc']) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <span class="bg-primary/5 text-primary text-xs font-extrabold px-3 py-1.5 rounded-lg border border-primary/10 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>
                Sistem Terintegrasi
            </span>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Profile Summary Card (Left) -->
        <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-2xl p-6 shadow-[0_8px_30px_rgba(0,6,102,0.02)] flex flex-col items-center text-center space-y-6">
            <div class="relative group">
                <div class="absolute -inset-1.5 bg-gradient-to-tr from-primary to-blue-500 rounded-full blur opacity-10 group-hover:opacity-20 transition duration-300"></div>
                <img src="<?= htmlspecialchars($profilePic) ?>" alt="Avatar" class="relative w-28 h-28 rounded-full object-cover border-4 border-surface-container-lowest shadow-md" />
                <span class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-4 border-surface-container-lowest rounded-full shadow-sm"></span>
            </div>
            
            <div class="space-y-2">
                <h3 class="text-xl font-bold text-on-surface font-headline leading-tight"><?= htmlspecialchars($sessName) ?></h3>
                <span class="inline-block px-3 py-1 bg-primary/10 text-primary text-xs font-extrabold uppercase tracking-wider rounded-full"><?= htmlspecialchars($roleLabel) ?></span>
                <p class="text-sm font-medium text-on-surface-variant"><?= htmlspecialchars($sessEmail) ?></p>
            </div>

            <div class="w-full border-t border-outline-variant/15 pt-6 space-y-3 text-left">
                <div class="flex justify-between items-center text-xs font-bold">
                    <span class="text-on-surface-variant">Status Akun</span>
                    <span class="text-green-600 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                        Aktif Terverifikasi
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs font-bold">
                    <span class="text-on-surface-variant">Tingkat Keamanan</span>
                    <span class="text-blue-600">Sangat Baik</span>
                </div>
                <div class="flex justify-between items-center text-xs font-bold">
                    <span class="text-on-surface-variant">Sesi Saat Ini</span>
                    <span class="text-primary font-mono text-[10px] bg-primary/5 px-2 py-0.5 rounded">Aktif</span>
                </div>
            </div>
        </div>

        <!-- Coming Soon Card (Right) -->
        <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant/15 rounded-2xl p-8 shadow-[0_8px_30px_rgba(0,6,102,0.02)] flex flex-col justify-between space-y-8">
            <div class="space-y-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/5 border border-primary/10 flex items-center justify-center text-primary shadow-inner">
                        <span class="material-symbols-outlined text-2xl animate-spin" style="animation-duration: 4s;">settings</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold text-primary uppercase tracking-widest bg-primary/10 px-2.5 py-1 rounded">SEDANG DIKEMBANGKAN</span>
                        <h4 class="text-xl font-extrabold text-on-surface mt-1 font-headline"><?= htmlspecialchars($pageInfo['title']) ?></h4>
                    </div>
                </div>
                
                <p class="text-on-surface-variant text-sm leading-relaxed max-w-2xl">
                    Halo, <strong><?= htmlspecialchars($sessName) ?></strong>. Halaman/modul <span class="font-bold text-primary"><?= htmlspecialchars($pageInfo['title']) ?></span> untuk peran <span class="font-bold text-primary"><?= htmlspecialchars($roleLabel) ?></span> saat ini sedang dalam proses pengembangan arsitektur sistem. Kami sedang merancang pengalaman digital terbaik yang berfokus pada efisiensi alur kerja dan keamanan data Anda.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <?php foreach($pageInfo['features'] as $feat): ?>
                    <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/10 flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-lg mt-0.5">check_circle</span>
                        <div>
                            <h5 class="text-xs font-extrabold text-on-surface font-headline"><?= htmlspecialchars($feat['title']) ?></h5>
                            <p class="text-[11px] text-on-surface-variant mt-0.5"><?= htmlspecialchars($feat['desc']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="border-t border-outline-variant/15 pt-6 flex items-center justify-between text-xs font-medium text-on-surface-variant">
                <span class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">info</span>
                    Memerlukan bantuan?
                </span>
                <a href="/support" data-spa class="text-primary font-bold hover:underline">Hubungi Tim IT Support &rarr;</a>
            </div>
        </div>
    </div>
</div>
