<?php
$sessName  = $_SESSION['name'] ?? 'User';
$sessEmail = $_SESSION['email'] ?? '';
$sessRole  = $_SESSION['role'] ?? 'candidate';

$roleLabels = [
    'employee' => 'Portal Karyawan',
    'hr_ops' => 'Portal HR Ops',
    'hiring_manager' => 'Portal Manager',
    'candidate' => 'Portal Kandidat',
    'recruiter' => 'Portal Rekruter',
    'admin' => 'Portal Admin',
    'executive' => 'Portal Eksekutif',
    'superadmin' => 'Portal Superadmin'
];
$portalLabel = $roleLabels[$sessRole] ?? 'Portal Karyawan';

$pathLabels = [
    'candidate/jobs' => [
        'title' => 'Dashboard Lowongan Pekerjaan',
    ],
    'candidate/interviews' => [
        'title' => 'Jadwal Wawancara',
    ],
    'candidate/offerings' => [
        'title' => 'Penawaran & Kontrak Kerja',
    ],
    'candidate/onboarding' => [
        'title' => 'Verifikasi Onboarding',
    ],
    'employee/profile' => [
        'title' => 'Profil & Akun Pribadi',
    ],
    'employee/attendance' => [
        'title' => 'Manajemen Presensi Harian',
    ],
    'employee/leaves' => [
        'title' => 'Pengajuan Cuti & Izin Kerja',
    ],
    'employee/finance' => [
        'title' => 'Informasi Finansial & Gaji Mandiri',
    ],
    'employee/reimbursements' => [
        'title' => 'Pengajuan Klaim & Reimbursement',
    ],
    'employee/reflection' => [
        'title' => 'Refleksi Diri',
    ],
    'recruiter/jobs' => [
        'title' => 'Manajemen Lowongan Pekerjaan',
    ],
    'recruiter/ats' => [
        'title' => 'Pipeline Seleksi Pelamar (ATS)',
    ],
    'recruiter/interviews' => [
        'title' => 'Penjadwalan Wawancara & Evaluasi',
    ],
    'recruiter/offerings' => [
        'title' => 'Penerbitan Kontrak & Offering Letter',
    ],
    'manager/requisitions' => [
        'title' => 'Permintaan Penambahan Tenaga Kerja',
    ],
    'manager/candidates' => [
        'title' => 'Review Portofolio & CV Pelamar',
    ],
    'manager/interviews' => [
        'title' => 'Lembar Penilaian & Rubrik Wawancara',
    ],
    'manager/approvals' => [
        'title' => 'Persetujuan Operasional Tim',
    ],
    'hrops/onboarding' => [
        'title' => 'Verifikasi Onboarding',
    ],
    'hrops/employees' => [
        'title' => 'Master Data Karyawan (Core HRIS)',
    ],
    'hrops/verifications' => [
        'title' => 'Verifikasi Data Karyawan (Correction Queue)',
    ],
    'hrops/payroll' => [
        'title' => 'Pemrosesan Penggajian & Pajak (Payroll)',
    ],
    'admin/departments' => [
        'title' => 'Struktur Departemen & Hubungan Hierarki',
    ],
    'admin/users' => [
        'title' => 'Manajemen Otoritas Pengguna & Hak Akses',
    ],
    'admin/settings' => [
        'title' => 'Konfigurasi Sistem Global & Parameter Aplikasi',
    ],
    'executive/analytics' => [
        'title' => 'Dashboard Analitik & Makro Performa SDM',
    ],
    'executive/budgets' => [
        'title' => 'Matriks Persetujuan Anggaran Organisasi',
    ],
    'executive/approvals' => [
        'title' => 'Matriks Persetujuan Mutasi & Perubahan Jabatan',
    ],
    'superadmin/users' => [
        'title' => 'Manajemen Otoritas Pengguna & Hak Akses',
    ],
    'superadmin/settings' => [
        'title' => 'Konfigurasi Sistem Global & Parameter Parameter',
    ],
    'superadmin/audit' => [
        'title' => 'Security Audit Logs & Aktivitas Sistem',
    ]
];

$requestedPath = $requestedPath ?? '';
$pageInfo = $pathLabels[$requestedPath] ?? [
    'title' => 'Modul Sedang Dikembangkan',
];
?>
<div class="space-y-6 animate-fade-in">
    <!-- Title -->
    <div class="space-y-1">
        <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight"><?= htmlspecialchars($pageInfo['title']) ?></h1>
        <p class="text-on-surface-variant font-medium text-sm">Modul ini masih dalam tahap pengembangan (Placeholder).</p>
    </div>

    <!-- Center Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 rounded-3xl p-16 shadow-[0_8px_30px_rgba(0,6,102,0.015)] flex flex-col items-center justify-center text-center min-h-[340px]">
        <!-- Naked Large Material Symbol Icon -->
        <div class="mb-6 flex justify-center items-center">
            <span class="material-symbols-outlined text-[64px] text-[#a5a9c0] select-none" style="font-size: 64px;">construction</span>
        </div>
        
        <h3 class="text-xl font-bold text-on-surface font-headline mb-3">Sedang Dibangun</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed max-w-xl">
            Halaman <strong><?= htmlspecialchars($pageInfo['title']) ?></strong> ini merupakan bagian dari arsitektur peran spesifik, sesuai alur kerja. Akan segera diimplementasikan fitur fungsionalnya.
        </p>
    </div>
</div>
