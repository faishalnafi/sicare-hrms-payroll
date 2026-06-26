<?php
$sessName  = $_SESSION['name'] ?? 'Alex Rivera';
$sessEmail = $_SESSION['email'] ?? 'alex.rivera@example.com';
$sessRole  = $_SESSION['role'] ?? 'employee';
// Fetch dynamic user data from DB with department joined
$db = \App\Config\Database::getInstance()->getConnection();
$userQuery = $db->prepare("
    SELECT u.*, d.name AS department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.id = :id
");
$userQuery->execute(['id' => $_SESSION['user_id']]);
$dbUser = $userQuery->fetch();

$deptName = !empty($dbUser['department_name']) ? $dbUser['department_name'] : '-';
$jobTitle = !empty($dbUser['job_title']) ? $dbUser['job_title'] : '-';

$profilePic = $dbUser['profile_picture'] ?? $_SESSION['profile_picture'] ?? null;

$hash = md5(strtolower(trim($sessEmail)));
if (empty($profilePic)) {
    $profilePic = "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
}

$employeeId = !empty($dbUser['employee_id']) ? $dbUser['employee_id'] : '-';
$ktpNik = !empty($dbUser['ktp_nik']) ? $dbUser['ktp_nik'] : '-';
$namaSesuaiKtp = !empty($dbUser['nama_sesuai_ktp']) ? $dbUser['nama_sesuai_ktp'] : '-';
$displayName = !empty($dbUser['nama_sesuai_ktp']) ? $dbUser['nama_sesuai_ktp'] : $sessName;
$alamatKtp = !empty($dbUser['alamat_ktp']) ? $dbUser['alamat_ktp'] : '-';
$bankName = !empty($dbUser['bank_name']) ? $dbUser['bank_name'] : '-';
$bankAccountNumber = !empty($dbUser['bank_account_number']) ? $dbUser['bank_account_number'] : '-';
$npwpNumber = !empty($dbUser['npwp_number']) ? $dbUser['npwp_number'] : '-';
$bpjsTk = !empty($dbUser['bpjs_tk']) ? $dbUser['bpjs_tk'] : '-';
$bpjsKes = !empty($dbUser['bpjs_kes']) ? $dbUser['bpjs_kes'] : '-';
$tanggalLahir = !empty($dbUser['tanggal_lahir']) ? $dbUser['tanggal_lahir'] : '-';
$statusPernikahan = !empty($dbUser['status_pernikahan']) ? $dbUser['status_pernikahan'] : '-';
$jenisKelamin = !empty($dbUser['jenis_kelamin']) ? $dbUser['jenis_kelamin'] : '-';
$noTelepon = !empty($dbUser['no_telepon']) ? $dbUser['no_telepon'] : '';
$alamatDomisili = !empty($dbUser['alamat_domisili']) ? $dbUser['alamat_domisili'] : '';
$homeLat = $dbUser['home_latitude'] ?? '';
$homeLng = $dbUser['home_longitude'] ?? '';

// Fetch pending, approved, and rejected correction requests within the last 1 month
$reqQuery = $db->prepare("
    SELECT * FROM employee_data_correction_requests 
    WHERE user_id = :id 
      AND (status = 'pending' OR created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH))
    ORDER BY created_at DESC
");
$reqQuery->execute(['id' => $_SESSION['user_id']]);
$pendingRequests = $reqQuery->fetchAll();
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Profil Pribadi</h1>
            <p class="text-on-surface-variant font-medium text-sm">Kelola data personal terverifikasi dan ajukan perbaikan data administratif ESS Anda.</p>
        </div>
        <div>
            <button onclick="openCorrectionModal()" class="bg-primary hover:bg-primary/95 text-white font-bold text-xs py-3 px-5 rounded-lg flex items-center gap-2 transition-all shadow-md shadow-primary/20 hover:scale-[1.02] active:scale-95 duration-200">
                <span class="material-symbols-outlined text-sm">edit_note</span> Ajukan Perbaikan Data
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left Column: Profile Card -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-[0_20px_40px_rgba(0,6,102,0.03)] text-center relative overflow-hidden group">
                <div class="absolute -top-16 -right-16 w-56 h-56 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 group-hover:scale-110 transition-all duration-500"></div>
                
                <div class="relative flex flex-col items-center">
                    <div class="relative mb-4">
                        <img referrerpolicy="no-referrer" alt="Foto Profil" class="w-32 h-32 rounded-full object-cover border-4 border-surface shadow-md bg-white" src="<?php echo htmlspecialchars($profilePic); ?>" onerror="window.handleAvatarError(this, '<?= $hash ?>')"/>
                        <button onclick="Swal.fire({title: 'Ubah Foto Profil', text: 'Unggah foto profil baru divalidasi aman via server. Maks 10MB.', icon: 'info', confirmButtonColor: '#000666'})" class="hidden absolute bottom-1 right-1 bg-primary text-white p-2 rounded-full shadow-lg hover:scale-110 active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-sm">photo_camera</span>
                        </button>
                    </div>
                    
                    <h2 class="text-2xl font-black text-on-surface font-headline mb-1"><?php echo htmlspecialchars($displayName); ?></h2>
                    <p class="text-xs font-bold text-primary uppercase tracking-wider bg-primary/5 px-3 py-1 rounded-full mb-4">Karyawan Aktif</p>
                    
                    <!-- Meta info list without 1px dividers -->
                    <div class="w-full space-y-3 pt-4 border-t border-outline-variant/10 text-left">
                        <div class="bg-surface-container-low/60 rounded-xl p-3 flex justify-between items-center">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-primary text-lg">badge</span>
                                <span class="text-xs font-semibold text-on-surface-variant">ID Karyawan</span>
                            </div>
                            <span class="text-xs font-mono font-bold text-on-surface bg-surface-container-high px-2 py-0.5 rounded"><?php echo htmlspecialchars($employeeId); ?></span>
                        </div>
                        <div class="bg-surface-container-low/60 rounded-xl p-3 flex justify-between items-center">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-primary text-lg">schema</span>
                                <span class="text-xs font-semibold text-on-surface-variant">Divisi / Dept</span>
                            </div>
                            <span class="text-xs font-bold text-on-surface"><?php echo htmlspecialchars($deptName); ?></span>
                        </div>
                        <div class="bg-surface-container-low/60 rounded-xl p-3 flex justify-between items-center">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-primary text-lg">work</span>
                                <span class="text-xs font-semibold text-on-surface-variant">Jabatan</span>
                            </div>
                            <span class="text-xs font-bold text-on-surface"><?php echo htmlspecialchars($jobTitle); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification Status Bento Card -->
            <div class="bg-primary text-white rounded-2xl p-6 relative overflow-hidden shadow-lg shadow-primary/10 group">
                <div class="relative z-10 space-y-4">
                    <div class="bg-white/10 rounded-full w-12 h-12 flex items-center justify-center">
                        <span class="material-symbols-outlined text-white text-2xl font-bold">verified</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold font-headline">Status Administratif</h3>
                        <p class="text-xs text-white/80 mt-1 leading-relaxed">Seluruh data administratif Anda telah terverifikasi secara hukum oleh Divisi HR Ops. Setiap perubahan wajib melalui pengajuan dengan bukti dokumen legal.</p>
                    </div>
                </div>
                <div class="absolute -bottom-6 -right-6 opacity-[0.08] group-hover:scale-110 transition-transform duration-500">
                    <span class="material-symbols-outlined text-[150px]" style="font-variation-settings: 'FILL' 1;">security</span>
                </div>
            </div>

            <!-- Status Pengajuan Perbaikan Data -->
            <?php 
            $itemsCount = count($pendingRequests);
            if ($itemsCount > 0): 
                $itemsPerPage = 3;
                $totalPages = ceil($itemsCount / $itemsPerPage);
            ?>
            <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-[0_12px_24px_rgba(0,6,102,0.01)] space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-outline-variant/10">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">fact_check</span>
                        <h3 class="text-sm font-extrabold text-on-surface">Status Pengajuan (<span class="text-primary"><?php echo $itemsCount; ?></span>)</h3>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <div class="flex items-center gap-1 bg-surface-container-low px-2 py-0.5 rounded-full border border-outline-variant/10 select-none">
                            <button type="button" onclick="prevPendingPage()" class="p-0.5 hover:bg-surface-container-high rounded-full transition-colors text-on-surface-variant disabled:opacity-30 disabled:pointer-events-none" id="prevPendingBtn" disabled>
                                <span class="material-symbols-outlined text-sm font-bold">chevron_left</span>
                            </button>
                            <span class="text-[10px] font-extrabold text-primary min-w-[28px] text-center" id="pendingPageIndicator">1 / <?php echo $totalPages; ?></span>
                            <button type="button" onclick="nextPendingPage()" class="p-0.5 hover:bg-surface-container-high rounded-full transition-colors text-on-surface-variant disabled:opacity-30 disabled:pointer-events-none" id="nextPendingBtn">
                                <span class="material-symbols-outlined text-sm font-bold">chevron_right</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="space-y-3">
                    <?php 
                    $index = 0;
                    foreach($pendingRequests as $req): 
                        $page = floor($index / $itemsPerPage) + 1;
                        $index++;
                        
                        // Format field label simply
                        $fieldLabel = ucwords(str_replace('_', ' ', $req['field']));
                        
                        // Dynamic borders & badges depending on status
                        if ($req['status'] === 'pending') {
                            $leftBorder = 'border-l-4 border-l-amber-500';
                            $badge = '<span class="text-[9px] font-bold text-amber-600 bg-amber-100 px-2 py-0.5 rounded uppercase flex-shrink-0 whitespace-nowrap">Pending</span>';
                        } elseif ($req['status'] === 'approved') {
                            $leftBorder = 'border-l-4 border-l-green-500';
                            $badge = '<span class="text-[9px] font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded uppercase flex-shrink-0 whitespace-nowrap">Disetujui</span>';
                        } else {
                            $leftBorder = 'border-l-4 border-l-red-500';
                            $badge = '<span class="text-[9px] font-bold text-red-700 bg-red-100 px-2 py-0.5 rounded uppercase flex-shrink-0 whitespace-nowrap">Ditolak</span>';
                        }
                    ?>
                    <div data-page="<?php echo $page; ?>" class="pending-request-item bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative <?php echo $leftBorder; ?> transition-all hover:shadow-sm <?php echo $page > 1 ? 'hidden' : ''; ?>">
                        <div class="flex justify-between items-start mb-1.5 gap-2">
                            <label class="text-[9px] uppercase font-black tracking-wider text-on-surface-variant/80 block truncate" title="<?php echo htmlspecialchars($fieldLabel); ?>"><?php echo htmlspecialchars($fieldLabel); ?></label>
                            <?php echo $badge; ?>
                        </div>
                        <p class="text-xs font-bold text-on-surface truncate" title="<?php echo htmlspecialchars($req['new_value']); ?>"><?php echo htmlspecialchars($req['new_value']); ?></p>
                        <p class="text-[10px] text-on-surface-variant mt-1 italic break-words whitespace-normal leading-relaxed" title="<?php echo htmlspecialchars($req['reason']); ?>">"<?php echo htmlspecialchars($req['reason']); ?>"</p>
                        
                        <?php if ($req['status'] === 'rejected' && !empty($req['rejection_reason'])): ?>
                            <div class="mt-2.5 pt-2 border-t border-red-200/30 text-[10px] text-red-700 font-semibold leading-relaxed">
                                <span class="font-bold text-red-800">Alasan Ditolak:</span>
                                <p class="text-red-600 italic">"<?php echo htmlspecialchars($req['rejection_reason']); ?>"</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Form Sections & Bento Grid -->
        <div class="lg:col-span-8 space-y-8">
            <!-- Section: 4 Locked Administrative Data Categories -->
            <section class="space-y-4">
                <div class="flex justify-between items-end">
                    <div class="space-y-0.5">
                        <h2 class="text-2xl font-black text-on-surface tracking-tight font-headline">Data Administratif Terkunci</h2>
                        <p class="text-on-surface-variant text-xs">Informasi sensitif perpajakan, keuangan, dan kependudukan dilindungi secara ketat.</p>
                    </div>
                </div>

                <!-- 4 Grid Categories -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Category 1: Kependudukan -->
                    <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-[0_12px_24px_rgba(0,6,102,0.01)] space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/10">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-xl">demography</span>
                                <h3 class="text-sm font-extrabold text-on-surface">Kependudukan</h3>
                            </div>
                            <span class="material-symbols-outlined text-primary text-base font-bold" title="Locked Data">lock</span>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">NIK (Nomor Induk Kependudukan)</label>
                                <p class="text-xs font-bold text-on-surface select-all"><?php echo htmlspecialchars($ktpNik); ?></p>
                            </div>
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">Nama Lengkap Sesuai KTP</label>
                                <p class="text-xs font-bold text-on-surface select-all"><?php echo htmlspecialchars($namaSesuaiKtp); ?></p>
                            </div>
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">Alamat KTP</label>
                                <p class="text-xs font-bold text-on-surface leading-relaxed select-all"><?php echo htmlspecialchars($alamatKtp); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Category 2: Finansial -->
                    <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-[0_12px_24px_rgba(0,6,102,0.01)] space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/10">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-xl">payments</span>
                                <h3 class="text-sm font-extrabold text-on-surface">Finansial & Payroll</h3>
                            </div>
                            <span class="material-symbols-outlined text-primary text-base font-bold" title="Locked Data">lock</span>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">Nama Bank Penerima</label>
                                <p class="text-xs font-bold text-on-surface select-all"><?php echo htmlspecialchars($bankName); ?></p>
                            </div>
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">Nomor Rekening</label>
                                <p class="text-xs font-bold text-on-surface font-mono select-all"><?php echo htmlspecialchars($bankAccountNumber); ?></p>
                            </div>
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">Atas Nama Rekening</label>
                                <p class="text-xs font-bold text-on-surface select-all"><?php echo htmlspecialchars($namaSesuaiKtp); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Category 3: Pajak & Asuransi -->
                    <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-[0_12px_24px_rgba(0,6,102,0.01)] space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/10">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-xl">receipt_long</span>
                                <h3 class="text-sm font-extrabold text-on-surface">Pajak & Jaminan Sosial</h3>
                            </div>
                            <span class="material-symbols-outlined text-primary text-base font-bold" title="Locked Data">lock</span>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">NPWP (Nomor Pokok Wajib Pajak)</label>
                                <p class="text-xs font-bold text-on-surface select-all"><?php echo htmlspecialchars($npwpNumber); ?></p>
                            </div>
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">BPJS Ketenagakerjaan</label>
                                <p class="text-xs font-bold text-on-surface select-all font-mono"><?php echo htmlspecialchars($bpjsTk); ?></p>
                            </div>
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">BPJS Kesehatan</label>
                                <p class="text-xs font-bold text-on-surface select-all font-mono"><?php echo htmlspecialchars($bpjsKes); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Category 4: Data Pribadi -->
                    <div class="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/15 shadow-[0_12px_24px_rgba(0,6,102,0.01)] space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/10">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-xl">account_box</span>
                                <h3 class="text-sm font-extrabold text-on-surface">Data Pribadi</h3>
                            </div>
                            <span class="material-symbols-outlined text-primary text-base font-bold" title="Locked Data">lock</span>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">Tanggal Lahir</label>
                                <p class="text-xs font-bold text-on-surface select-all"><?php echo htmlspecialchars($tanggalLahir); ?></p>
                            </div>
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">Status Pernikahan</label>
                                <p class="text-xs font-bold text-on-surface select-all"><?php echo htmlspecialchars($statusPernikahan); ?></p>
                            </div>
                            <div class="bg-surface-container-low/40 p-3.5 rounded-xl border border-outline-variant/10 relative">
                                <label class="text-[9px] uppercase font-bold tracking-wider text-on-surface-variant/80 block mb-1">Jenis Kelamin</label>
                                <p class="text-xs font-bold text-on-surface select-all"><?php echo htmlspecialchars($jenisKelamin); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section: Editable Contact Information -->
            <section class="space-y-4">
                <div class="space-y-0.5">
                    <h2 class="text-2xl font-black text-on-surface tracking-tight font-headline">Informasi Kontak & Domisili</h2>
                    <p class="text-on-surface-variant text-xs">Kelola bagaimana Anda menerima pemberitahuan resmi dan perbarui alamat tempat tinggal aktif Anda.</p>
                </div>
                
                <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/15 shadow-[0_12px_24px_rgba(0,6,102,0.01)]">
                    <form id="contactForm" onsubmit="saveContactChanges(event)" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-on-surface-variant ml-1">Alamat Email Pribadi</label>
                                <div class="relative group">
                                    <input type="email" id="email_pribadi" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-medium text-xs text-on-surface" placeholder="alex.rivera@email.com" value="<?php echo htmlspecialchars($sessEmail); ?>" required />
                                    <div class="absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors flex items-center justify-center">
                                        <span class="material-symbols-outlined text-lg">mail</span>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-on-surface-variant ml-1">Nomor Telepon Seluler</label>
                                <div class="relative group">
                                    <input type="tel" id="no_telepon" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-medium text-xs text-on-surface" placeholder="+62 812-3456-7890" value="<?php echo htmlspecialchars($noTelepon); ?>" required />
                                    <div class="absolute right-3 top-3 text-on-surface-variant/40 group-focus-within:text-primary transition-colors flex items-center justify-center">
                                        <span class="material-symbols-outlined text-lg">phone_iphone</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-on-surface-variant ml-1">Alamat Tempat Tinggal Saat Ini (Domisili)</label>
                                <textarea id="alamat_domisili" rows="2" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-medium text-xs text-on-surface resize-none" placeholder="Masukkan alamat domisili lengkap saat ini..." required><?php echo htmlspecialchars($alamatDomisili); ?></textarea>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-on-surface-variant ml-1">Ubah Kata Sandi <span class="text-[10px] font-normal lowercase">(kosongkan jika tidak ingin diubah)</span></label>
                                <div class="relative group">
                                    <input type="password" id="password" class="w-full bg-surface-container-low border-none rounded-lg pl-4 pr-12 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-medium text-xs text-on-surface" placeholder="Masukkan kata sandi baru..." />
                                    <button type="button" id="togglePassword" class="absolute right-3 top-3 text-on-surface-variant/40 hover:text-primary transition-colors flex items-center justify-center">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </button>
                                </div>
                                <!-- Strength Indicators -->
                                <div id="pw-strength-box" class="hidden p-4 bg-surface-container-low rounded-xl border border-outline-variant/15 text-xs space-y-2 mt-2">
                                    <p class="font-bold text-on-surface-variant mb-1.5">Kriteria Kata Sandi Kuat:</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-medium">
                                        <div id="pw-len" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                            <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Minimal 8 Karakter
                                        </div>
                                        <div id="pw-upper" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                            <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kapital (A-Z)
                                        </div>
                                        <div id="pw-lower" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                            <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kecil (a-z)
                                        </div>
                                        <div id="pw-num" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                            <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Angka (0-9)
                                        </div>
                                        <div id="pw-spec" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                            <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Karakter Simbol (@$!%*?&...)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2 mt-4">
                            <div class="flex justify-between items-center">
                                <label class="text-xs font-bold text-on-surface-variant ml-1">Koordinat Rumah (Lokasi WFH)</label>
                                <button type="button" onclick="detectHomeCoordinates()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary/10 hover:bg-primary/15 text-primary text-xs font-bold transition-all border border-primary/20">
                                    <span class="material-symbols-outlined text-sm">my_location</span>
                                    Deteksi GPS
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="relative group">
                                    <input type="text" id="home_latitude" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-mono text-xs text-on-surface" placeholder="Latitude Rumah (cth: -6.2297)" value="<?php echo htmlspecialchars($homeLat); ?>" />
                                    <div class="absolute right-3 top-3 text-[10px] uppercase font-bold text-on-surface-variant/40">Lat</div>
                                </div>
                                <div class="relative group">
                                    <input type="text" id="home_longitude" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-mono text-xs text-on-surface" placeholder="Longitude Rumah (cth: 106.8164)" value="<?php echo htmlspecialchars($homeLng); ?>" />
                                    <div class="absolute right-3 top-3 text-[10px] uppercase font-bold text-on-surface-variant/40">Lng</div>
                                </div>
                            </div>
                            <p class="text-[10px] text-on-surface-variant mt-1">Koordinat ini digunakan untuk memvalidasi lokasi ketika Anda melakukan presensi WFH (Work From Home) dari rumah.</p>
                        </div>
                        <div class="pt-2 flex justify-end">
                            <button type="submit" class="bg-primary text-white px-8 py-2.5 rounded-lg font-bold text-xs shadow-lg shadow-primary/20 hover:opacity-95 active:scale-95 transition-all duration-200">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </section>

        </div>
    </div>

    <!-- Security Awareness Card (Moved to span full width) -->
    <div class="bg-amber-50/50 border border-amber-200/50 p-5 rounded-2xl flex gap-4 items-start shadow-sm mt-8">
        <span class="material-symbols-outlined text-amber-700 bg-amber-100 p-2 rounded-xl text-xl font-bold flex-shrink-0">verified_user</span>
        <div>
            <h4 class="text-amber-900 font-extrabold text-sm">Privasi & Keamanan Data Tingkat Tinggi</h4>
            <p class="text-amber-800 text-xs mt-1 leading-relaxed">Seluruh berkas identitas administratif, NIK, dan perbankan di-serve secara terotorisasi melalui server internal privat yang aman dan terenkripsi. siCare menjamin kerahasiaan data pribadi Anda.</p>
        </div>
    </div>
</div>

<!-- Modal Overlay: Ajukan Perbaikan Data -->
<div id="correctionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden animate-in fade-in duration-200">
    <!-- Backdrop with blur -->
    <div class="absolute inset-0 bg-on-surface/40 backdrop-blur-sm" onclick="closeCorrectionModal()"></div>
    
    <!-- Modal Content Box -->
    <div class="relative bg-surface-container-lowest w-full max-w-lg rounded-2xl shadow-2xl border border-outline-variant/30 overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-lowest">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary text-2xl">edit_document</span>
                <h2 class="text-lg font-extrabold text-on-surface tracking-tight font-headline">Ajukan Perbaikan Data</h2>
            </div>
            <button onclick="closeCorrectionModal()" class="p-1.5 hover:bg-surface-container-high rounded-full transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined text-on-surface-variant">close</span>
            </button>
        </div>

        <!-- Modal Body Form -->
        <form id="correctionForm" onsubmit="submitCorrectionRequest(event)" class="p-6 space-y-4 overflow-y-auto flex-grow">
            <!-- Kategori Data Select -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider ml-1">Kategori Data <span class="text-red-500">*</span></label>
                <div class="relative">
                    <select id="correctionCategory" onchange="updateCategoryFields()" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-semibold text-xs text-on-surface appearance-none cursor-pointer">
                        <option value="kependudukan">Kependudukan (NIK, Nama KTP, Alamat KTP)</option>
                        <option value="finansial">Finansial & Rekening (Bank, No Rekening)</option>
                        <option value="pajak_asuransi">Pajak & Asuransi (NPWP, BPJS TK, BPJS Kes)</option>
                        <option value="data_pribadi">Data Pribadi (Tanggal Lahir, Status Pernikahan)</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-3 pointer-events-none text-on-surface-variant/60 text-lg">expand_more</span>
                </div>
            </div>

            <!-- Field yang ingin Diperbaiki -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider ml-1">Kolom yang Ingin Diperbaiki <span class="text-red-500">*</span></label>
                <div class="relative">
                    <select id="correctionField" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-semibold text-xs text-on-surface appearance-none cursor-pointer">
                        <!-- Will be populated dynamically via Javascript -->
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-3 pointer-events-none text-on-surface-variant/60 text-lg">expand_more</span>
                </div>
            </div>

            <!-- Nilai Baru -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider ml-1">Nilai Data Baru yang Benar <span class="text-red-500">*</span></label>
                <input type="text" id="correctionNewValue" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-semibold text-xs text-on-surface" placeholder="Masukkan nilai data baru" required />
            </div>

            <!-- Alasan Perbaikan -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-on-surface-variant uppercase tracking-wider ml-1">Alasan Perbaikan Data <span class="text-red-500">*</span></label>
                <textarea id="correctionReason" rows="3" class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:bg-surface-container-lowest focus:ring-0 focus:border-b-2 focus:border-primary transition-all font-semibold text-xs text-on-surface resize-none" placeholder="Tulis alasan lengkap perbaikan data di sini..." required></textarea>
            </div>

            <!-- Dokumen Pendukung File Upload -->
            <div class="space-y-2">
                <label id="uploadLabel" class="text-xs font-bold text-on-surface-variant uppercase tracking-wider ml-1">Dokumen Bukti Pendukung (Maks. 10MB) <span class="text-red-500">*</span></label>
                <div class="relative w-full">
                    <input type="file" id="correctionFile" onchange="validateUploadedFile(this)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept=".pdf,.jpg,.jpeg,.png" required title="Silakan unggah berkas pendukung" />
                    <div id="dropZone" class="border-2 border-dashed border-outline-variant/60 rounded-xl p-5 flex flex-col items-center justify-center bg-surface-container-low hover:bg-surface-container-low/75 transition-colors group">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 group-hover:text-primary transition-colors mb-2">cloud_upload</span>
                        <p id="dropZoneText" class="text-xs font-extrabold text-on-surface-variant text-center">Seret berkas ke sini atau Klik untuk memilih berkas</p>
                        <p id="dropZoneSubText" class="text-[10px] text-on-surface-variant/60 mt-1 uppercase font-extrabold">Scan Dokumen Asli (PDF, JPG, PNG - Maks. 10MB)</p>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="pt-4 flex gap-3 border-t border-outline-variant/10">
                <button type="button" onclick="closeCorrectionModal()" class="flex-1 px-5 py-3 border border-outline-variant text-on-surface-variant font-bold text-xs rounded-lg hover:bg-surface-container-high transition-colors">
                    Batal
                </button>
                <button type="submit" class="flex-1 bg-primary text-white px-5 py-3 rounded-lg font-bold text-xs shadow-lg shadow-primary/20 hover:opacity-90 active:scale-95 transition-all">
                    Kirim Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Pagination logic for Status Pengajuan
    let currentPendingPage = 1;
    const totalPendingPages = <?php echo isset($totalPages) ? $totalPages : 1; ?>;

    window.prevPendingPage = function prevPendingPage() {
        if (currentPendingPage > 1) {
            showPendingPage(currentPendingPage - 1);
        }
    }

    window.nextPendingPage = function nextPendingPage() {
        if (currentPendingPage < totalPendingPages) {
            showPendingPage(currentPendingPage + 1);
        }
    }

    function showPendingPage(page) {
        currentPendingPage = page;
        
        // Update indicator
        const indicator = document.getElementById('pendingPageIndicator');
        if (indicator) {
            indicator.textContent = `${page} / ${totalPendingPages}`;
        }
        
        // Update button states
        const prevBtn = document.getElementById('prevPendingBtn');
        const nextBtn = document.getElementById('nextPendingBtn');
        if (prevBtn) prevBtn.disabled = (page === 1);
        if (nextBtn) nextBtn.disabled = (page === totalPendingPages);
        
        // Show/hide items with fade-in and scale transitions
        const items = document.querySelectorAll('.pending-request-item');
        items.forEach(item => {
            const itemPage = parseInt(item.getAttribute('data-page'));
            if (itemPage === page) {
                item.classList.remove('hidden');
                item.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    item.classList.remove('opacity-0', 'scale-95');
                    item.classList.add('opacity-100', 'scale-100', 'transition-all', 'duration-300');
                }, 10);
            } else {
                item.classList.add('hidden');
                item.classList.remove('opacity-100', 'scale-100');
            }
        });
    }

    // Map Categories to specific Fields with corresponding support document descriptions
    const categoryFields = {
        kependudukan: [
            { value: 'ktp_nik', label: 'NIK (Nomor Induk Kependudukan)', doc: 'Scan KTP / Kartu Keluarga Asli' },
            { value: 'nama_sesuai_ktp', label: 'Nama Lengkap Sesuai KTP', doc: 'Scan KTP / Kartu Keluarga Asli' },
            { value: 'alamat_ktp', label: 'Alamat Lengkap Sesuai KTP', doc: 'Scan KTP / Kartu Keluarga Asli' }
        ],
        finansial: [
            { value: 'bank_name', label: 'Nama Bank Penerima', doc: 'Scan Buku Tabungan / Screenshot m-Banking' },
            { value: 'bank_account_number', label: 'Nomor Rekening', doc: 'Scan Buku Tabungan / Screenshot m-Banking' }
        ],
        pajak_asuransi: [
            { value: 'npwp_number', label: 'NPWP (Nomor Pokok Wajib Pajak)', doc: 'Scan Kartu NPWP Asli / e-NPWP' },
            { value: 'bpjs_tk', label: 'BPJS Ketenagakerjaan', doc: 'Scan Kartu BPJS Ketenagakerjaan' },
            { value: 'bpjs_kes', label: 'BPJS Kesehatan', doc: 'Scan Kartu BPJS Kesehatan / Mobile JKN' }
        ],
        data_pribadi: [
            { value: 'tanggal_lahir', label: 'Tanggal Lahir', doc: 'Scan KTP / KK / Akta Kelahiran' },
            { value: 'status_pernikahan', label: 'Status Pernikahan', doc: 'Scan Buku Nikah / Akta Cerai / KK' }
        ]
    };

    // Update drop zone text based on selected category fields
    window.updateCategoryFields = function updateCategoryFields() {
        const category = document.getElementById('correctionCategory').value;
        const fieldsSelect = document.getElementById('correctionField');
        const fields = categoryFields[category];

        // Clear existing
        fieldsSelect.innerHTML = '';

        fields.forEach(field => {
            const opt = document.createElement('option');
            opt.value = field.value;
            opt.textContent = field.label;
            fieldsSelect.appendChild(opt);
        });

        // Update upload text dynamically to reflect document requirements
        if (fields.length > 0) {
            document.getElementById('dropZoneSubText').textContent = `${fields[0].doc} (PDF, JPG, PNG - Maks. 10MB)`;
        }
    }

    // Modal display controllers
    window.openCorrectionModal = function openCorrectionModal() {
        const modal = document.getElementById('correctionModal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // block page scroll
        updateCategoryFields(); // initial populate
    }

    window.closeCorrectionModal = function closeCorrectionModal() {
        const modal = document.getElementById('correctionModal');
        modal.classList.add('hidden');
        document.body.style.overflow = ''; // restore page scroll
        document.getElementById('correctionForm').reset();
        resetDropZone();
    }

    // File validation: Ensure upload doesn't exceed 10MB and meets MIME type rules
    window.validateUploadedFile = function validateUploadedFile(input) {
        const file = input.files[0];
        if (!file) return;

        const maxSizeBytes = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSizeBytes) {
            Swal.fire({
                title: 'Berkas Terlalu Besar!',
                text: 'Batas maksimal ukuran berkas pendukung adalah 10 Megabyte (10MB). Unggahan ditolak sebelum diproses.',
                icon: 'error',
                confirmButtonColor: '#000666'
            });
            input.value = ''; // reset file
            resetDropZone();
            return;
        }

        // Show uploaded file name in drag zone
        const dropZoneText = document.getElementById('dropZoneText');
        dropZoneText.innerHTML = `<span class="text-primary font-bold">Terpilih:</span> ${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
    }

    window.resetDropZone = function resetDropZone() {
        document.getElementById('dropZoneText').textContent = 'Seret berkas ke sini atau Klik untuk memilih berkas';
    }

    // Detect Home GPS Coordinates
    window.detectHomeCoordinates = function detectHomeCoordinates() {
        if (!navigator.geolocation) {
            Swal.fire('Gagal', 'Geolocation tidak didukung oleh browser Anda.', 'error');
            return;
        }

        // Detect if getCurrentPosition or watchPosition has been overridden by spoofing extensions
        const isMockedAPI = navigator.geolocation.getCurrentPosition.toString().indexOf('[native code]') === -1 || 
                            navigator.geolocation.watchPosition.toString().indexOf('[native code]') === -1;

        if (isMockedAPI) {
            Swal.fire({
                title: '⚠️ Manipulasi GPS Terdeteksi!',
                html: `
                    <div class="text-left bg-red-50 p-4 rounded-xl border border-red-200 mt-3 text-xs space-y-2 text-red-950 leading-relaxed font-medium">
                        <p class="font-bold text-red-800 text-sm">Alasan Gagal:</p>
                        <p class="text-gray-700">Sistem mendeteksi adanya manipulasi lokasi menggunakan ekstensi Fake GPS atau lokasi tiruan (Mock Location).</p>
                        <p class="font-bold text-red-800 text-sm mt-3">Solusi Penyelesaian:</p>
                        <ul class="list-decimal list-inside space-y-1 text-gray-700">
                            <li>Nonaktifkan atau hapus ekstensi browser <strong>Fake GPS / Location Spoofer</strong>.</li>
                            <li>Pastikan Anda tidak menggunakan emulator Android atau browser developer tool.</li>
                            <li>Buka portal siCare lewat browser resmi di <strong>ponsel fisik asli</strong> Anda.</li>
                            <li>Muat ulang (*refresh*) halaman dan coba deteksi kembali.</li>
                        </ul>
                    </div>
                `,
                icon: 'error',
                confirmButtonColor: '#ba1a1a',
                confirmButtonText: 'Tutup & Perbaiki'
            });
            return;
        }

        Swal.fire({
            title: 'Mendeteksi GPS...',
            text: 'Harap izinkan akses lokasi jika diminta oleh browser.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        navigator.geolocation.getCurrentPosition(
            (position) => {
                if (position.coords.accuracy <= 0) {
                    Swal.fire({
                        title: '⚠️ Manipulasi GPS Terdeteksi!',
                        html: `
                            <div class="text-left bg-red-50 p-4 rounded-xl border border-red-200 mt-3 text-xs space-y-2 text-red-950 leading-relaxed font-medium">
                                <p class="font-bold text-red-800 text-sm">Alasan Gagal:</p>
                                <p class="text-gray-700">Akurasi GPS mencurigakan (tidak wajar). Harap gunakan perangkat fisik asli tanpa emulator.</p>
                                <p class="font-bold text-red-800 text-sm mt-3">Solusi Penyelesaian:</p>
                                <ul class="list-decimal list-inside space-y-1 text-gray-700">
                                    <li>Gunakan perangkat ponsel fisik asli, bukan emulator atau simulator.</li>
                                    <li>Gunakan browser standar non-developer mode.</li>
                                    <li>Muat ulang (*refresh*) halaman dan coba lagi.</li>
                                </ul>
                            </div>
                        `,
                        icon: 'error',
                        confirmButtonColor: '#ba1a1a',
                        confirmButtonText: 'Tutup & Perbaiki'
                    });
                    return;
                }

                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                document.getElementById('home_latitude').value = lat.toFixed(7);
                document.getElementById('home_longitude').value = lng.toFixed(7);
                Swal.fire({
                    title: 'GPS Terdeteksi!',
                    text: `Koordinat berhasil diisi: ${lat.toFixed(7)}, ${lng.toFixed(7)}`,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            },
            (error) => {
                let msg = 'Gagal mendeteksi lokasi.';
                let solutionHtml = '';
                let title = '⚠️ Deteksi Gagal';

                if (error.code === error.PERMISSION_DENIED) {
                    title = '⚠️ Izin Lokasi Diblokir';
                    solutionHtml = `
                        <div class="text-left bg-amber-50 p-4 rounded-xl border border-amber-200 mt-3 text-xs space-y-2 text-amber-950 leading-relaxed font-medium">
                            <p class="font-bold text-amber-800 text-sm">Alasan Gagal:</p>
                            <p class="text-gray-700">Akses lokasi ditolak oleh pengguna atau diblokir oleh browser.</p>
                            <p class="font-bold text-amber-800 text-sm mt-3">Solusi Penyelesaian:</p>
                            <ul class="list-decimal list-inside space-y-1 text-gray-700">
                                <li>Klik ikon <strong>gembok / pengaturan situs</strong> di sebelah kiri kolom URL browser Anda.</li>
                                <li>Ubah status izin <strong>"Lokasi" (Location)</strong> menjadi <strong>"Izinkan" (Allow)</strong>.</li>
                                <li>Muat ulang (*refresh*) halaman dan coba lagi.</li>
                            </ul>
                        </div>
                    `;
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    title = '⚠️ Sinyal GPS Lemah';
                    solutionHtml = `
                        <div class="text-left bg-amber-50 p-4 rounded-xl border border-amber-200 mt-3 text-xs space-y-2 text-amber-950 leading-relaxed font-medium">
                            <p class="font-bold text-amber-800 text-sm">Alasan Gagal:</p>
                            <p class="text-gray-700">Sinyal lokasi (GPS) tidak tersedia atau tidak dapat ditentukan oleh perangkat.</p>
                            <p class="font-bold text-amber-800 text-sm mt-3">Solusi Penyelesaian:</p>
                            <ul class="list-decimal list-inside space-y-1 text-gray-700">
                                <li>Pastikan fitur <strong>GPS / Layanan Lokasi</strong> di perangkat Anda sudah <strong>AKTIF</strong>.</li>
                                <li>Pindah ke area terbuka atau dekat jendela untuk memperkuat tangkapan sinyal GPS.</li>
                                <li>Aktifkan Wi-Fi perangkat Anda untuk membantu meningkatkan akurasi lokasi.</li>
                            </ul>
                        </div>
                    `;
                } else if (error.code === error.TIMEOUT) {
                    title = '⚠️ Waktu Deteksi Habis';
                    solutionHtml = `
                        <div class="text-left bg-amber-50 p-4 rounded-xl border border-amber-200 mt-3 text-xs space-y-2 text-amber-950 leading-relaxed font-medium">
                            <p class="font-bold text-amber-800 text-sm">Alasan Gagal:</p>
                            <p class="text-gray-700">Waktu pengambilan lokasi habis sebelum mendapatkan koordinat stabil.</p>
                            <p class="font-bold text-amber-800 text-sm mt-3">Solusi Penyelesaian:</p>
                            <ul class="list-decimal list-inside space-y-1 text-gray-700">
                                <li>Pastikan GPS perangkat aktif dan tidak terhalang bangunan beton tebal.</li>
                                <li>Muat ulang (*refresh*) halaman dan coba deteksi kembali.</li>
                            </ul>
                        </div>
                    `;
                }

                if (solutionHtml) {
                    Swal.fire({
                        title: title,
                        html: solutionHtml,
                        icon: 'error',
                        confirmButtonColor: '#ba1a1a',
                        confirmButtonText: 'Tutup & Perbaiki'
                    });
                } else {
                    Swal.fire('Deteksi Gagal', msg, 'error');
                }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    // Handle Editable Contact Info changes
    window.saveContactChanges = function saveContactChanges(e) {
        e.preventDefault();
        
        if (typeof window.profilePasswordValid !== 'undefined' && !window.profilePasswordValid) {
            Swal.fire({
                title: 'Kata Sandi Kurang Kuat!',
                text: 'Harap penuhi semua kriteria kata sandi kuat sebelum menyimpan perubahan.',
                icon: 'error',
                confirmButtonColor: '#000666'
            });
            return;
        }
        
        const email = document.getElementById('email_pribadi').value;
        const tel = document.getElementById('no_telepon').value;
        const domisili = document.getElementById('alamat_domisili').value;
        const password = document.getElementById('password').value;
        const homeLat = document.getElementById('home_latitude').value;
        const homeLng = document.getElementById('home_longitude').value;

        Swal.fire({
            title: 'Simpan Perubahan Kontak?',
            text: 'Apakah Anda yakin ingin memperbarui data kontak pribadi aktif Anda?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Memproses Perubahan...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = new FormData();
                formData.append('email', email);
                formData.append('no_telepon', tel);
                formData.append('alamat_domisili', domisili);
                formData.append('password', password);
                formData.append('home_latitude', homeLat);
                formData.append('home_longitude', homeLng);

                fetch('/employee/profile/save', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Perubahan Disimpan!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        }).then(() => {
                            if (window.loadPage) {
                                window.loadPage('/employee/profile');
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire('Gagal!', data.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error!', 'Terjadi kesalahan jaringan atau koneksi ditolak server.', 'error');
                });
            }
        });
    }

    // Handle AJAX Correction Request Submission
    window.submitCorrectionRequest = function submitCorrectionRequest(e) {
        e.preventDefault();

        const category = document.getElementById('correctionCategory').value;
        const field = document.getElementById('correctionField').value;
        const newValue = document.getElementById('correctionNewValue').value;
        const reason = document.getElementById('correctionReason').value;
        const fileInput = document.getElementById('correctionFile');

        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire('Kesalahan', 'Silakan unggah berkas pendukung sebagai bukti validasi legal.', 'error');
            return;
        }

        // Show loading spinner
        Swal.fire({
            title: 'Mengirim Pengajuan...',
            text: 'Melakukan validasi keamanan dan unggah berkas legal aman.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Real AJAX request to backend
        const formData = new FormData();
        formData.append('category', category);
        formData.append('field', field);
        formData.append('new_value', newValue);
        formData.append('reason', reason);
        formData.append('file', fileInput.files[0]);

        fetch('/employee/profile/correction', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            closeCorrectionModal();
            if (data.success) {
                Swal.fire({
                    title: 'Pengajuan Terkirim!',
                    html: `
                        <div class="text-left text-xs space-y-2 mt-2 bg-surface-container-low p-4 rounded-xl">
                            <p><strong>Pengajuan perbaikan data berhasil dikirim! Status: PENDING.</strong></p>
                            <p class="pt-2"><strong>Kolom:</strong> ${data.field_label}</p>
                            <p><strong>Nilai Baru:</strong> ${data.new_value}</p>
                            <p class="text-on-surface-variant italic font-semibold">Tinjauan dokumen bukti asli oleh HR Ops akan selesai maksimal dalam 1x24 jam kerja.</p>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonColor: '#000666',
                    confirmButtonText: 'Selesai'
                });
            } else {
                Swal.fire('Gagal!', data.message, 'error');
            }
        })
        .catch(err => {
            closeCorrectionModal();
            Swal.fire('Error!', 'Terjadi kesalahan jaringan atau koneksi ditolak server.', 'error');
        });
    }

    // Drag and drop event listeners for modern UI file upload
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('correctionFile');

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropZone.classList.add('bg-surface-container-high', 'border-primary');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropZone.classList.remove('bg-surface-container-high', 'border-primary');
        }, false);
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            fileInput.files = files;
            validateUploadedFile(fileInput);
        }
    }, false);

    // Password Strength & Toggle Show/Hide setup
    (function() {
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('togglePassword');
        if (passwordInput && togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('.material-symbols-outlined').textContent = type === 'password' ? 'visibility' : 'visibility_off';
            });
        }

        const pwStrengthBox = document.getElementById('pw-strength-box');
        const pwLen = document.getElementById('pw-len');
        const pwUpper = document.getElementById('pw-upper');
        const pwLower = document.getElementById('pw-lower');
        const pwNum = document.getElementById('pw-num');
        const pwSpec = document.getElementById('pw-spec');

        window.profilePasswordValid = true; // default true because it's optional (empty)

        if (passwordInput) {
            passwordInput.addEventListener('focus', () => {
                pwStrengthBox.classList.remove('hidden');
            });

            passwordInput.addEventListener('input', function() {
                const val = this.value;
                if (val.length === 0) {
                    pwStrengthBox.classList.add('hidden');
                    window.profilePasswordValid = true;
                    return;
                }
                pwStrengthBox.classList.remove('hidden');
                
                const hasLen = val.length >= 8;
                const hasUpper = /[A-Z]/.test(val);
                const hasLower = /[a-z]/.test(val);
                const hasNum = /[0-9]/.test(val);
                const hasSpec = /[^A-Za-z0-9]/.test(val);

                updateCrit(pwLen, hasLen);
                updateCrit(pwUpper, hasUpper);
                updateCrit(pwLower, hasLower);
                updateCrit(pwNum, hasNum);
                updateCrit(pwSpec, hasSpec);

                window.profilePasswordValid = hasLen && hasUpper && hasLower && hasNum && hasSpec;
            });
        }

        function updateCrit(elem, met) {
            if (met) {
                elem.classList.remove('text-red-500');
                elem.classList.add('text-green-600');
                elem.querySelector('.material-symbols-outlined').textContent = 'check_circle';
            } else {
                elem.classList.remove('text-green-600');
                elem.classList.add('text-red-500');
                elem.querySelector('.material-symbols-outlined').textContent = 'cancel';
            }
        }
    })();
</script>
