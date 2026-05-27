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

$menus = [];
$menus[] = ['title' => 'Beranda', 'icon' => 'dashboard', 'link' => '/dashboard'];

switch ($sessRole) {
    case 'candidate':
        $menus[] = ['title' => 'Dashboard Lowongan', 'icon' => 'list_alt', 'link' => '/candidate/jobs'];
        $menus[] = ['title' => 'Jadwal Wawancara', 'icon' => 'event_available', 'link' => '/candidate/interviews'];
        $menus[] = ['title' => 'Penawaran & Kontrak', 'icon' => 'history_edu', 'link' => '/candidate/offerings'];
        $menus[] = ['title' => 'Wizard Onboarding', 'icon' => 'rocket_launch', 'link' => '/candidate/onboarding'];
        break;
    case 'employee':
        $menus[] = ['title' => 'Profil Pribadi', 'icon' => 'account_circle', 'link' => '/employee/profile'];
        $menus[] = ['title' => 'Menu Presensi', 'icon' => 'alarm_on', 'link' => '/employee/attendance'];
        $menus[] = ['title' => 'Cuti & Izin', 'icon' => 'event_note', 'link' => '/employee/leaves'];
        $menus[] = ['title' => 'Finansial Mandiri', 'icon' => 'payments', 'link' => '/employee/finance'];
        $menus[] = ['title' => 'Reimbursement', 'icon' => 'receipt_long', 'link' => '/employee/reimbursements'];
        $menus[] = ['title' => 'Refleksi Diri', 'icon' => 'psychology', 'link' => '/employee/reflection'];
        break;
    case 'recruiter':
        $menus[] = ['title' => 'Manajemen Lowongan', 'icon' => 'work', 'link' => '/recruiter/jobs'];
        $menus[] = ['title' => 'Pipeline ATS', 'icon' => 'view_kanban', 'link' => '/recruiter/ats'];
        $menus[] = ['title' => 'Jadwal Wawancara', 'icon' => 'calendar_month', 'link' => '/recruiter/interviews'];
        $menus[] = ['title' => 'Kontrak & Offering', 'icon' => 'history_edu', 'link' => '/recruiter/offerings'];
        break;
    case 'hiring_manager':
        $menus[] = ['title' => 'Anggota Tim', 'icon' => 'group', 'link' => '/manager/team'];
        $menus[] = ['title' => 'Permintaan Tenaga Kerja', 'icon' => 'person_add', 'link' => '/manager/requisitions'];
        $menus[] = ['title' => 'Review Kandidat', 'icon' => 'preview', 'link' => '/manager/candidates'];
        $menus[] = ['title' => 'Lembar Wawancara', 'icon' => 'fact_check', 'link' => '/manager/interviews'];
        $menus[] = ['title' => 'Persetujuan Tim', 'icon' => 'verified', 'link' => '/manager/approvals'];
        break;
    case 'hr_ops':
        $menus[] = ['title' => 'Verifikasi Onboarding', 'icon' => 'rule', 'link' => '/hrops/onboarding'];
        $menus[] = ['title' => 'Master Data Karyawan', 'icon' => 'group', 'link' => '/hrops/employees'];
        $menus[] = ['title' => 'Verifikasi Data', 'icon' => 'verified_user', 'link' => '/hrops/verifications'];
        $menus[] = ['title' => 'Pemrosesan Penggajian', 'icon' => 'account_balance_wallet', 'link' => '/hrops/payroll'];
        break;
    case 'admin':
        $menus[] = ['title' => 'Struktur Departemen', 'icon' => 'account_tree', 'link' => '/admin/departments'];
        $menus[] = ['title' => 'Manajemen Pengguna', 'icon' => 'manage_accounts', 'link' => '/admin/users'];
        $menus[] = ['title' => 'Pengaturan Sistem', 'icon' => 'settings', 'link' => '/admin/settings'];
        break;
    case 'executive':
        $menus[] = ['title' => 'Dashboard Analitik', 'icon' => 'analytics', 'link' => '/executive/analytics'];
        $menus[] = ['title' => 'Persetujuan Anggaran', 'icon' => 'request_quote', 'link' => '/executive/budgets'];
        $menus[] = ['title' => 'Persetujuan Mutasi', 'icon' => 'published_with_changes', 'link' => '/executive/approvals'];
        break;
    case 'superadmin':
        $menus[] = ['title' => 'Struktur Departemen', 'icon' => 'account_tree', 'link' => '/admin/departments'];
        $menus[] = ['title' => 'Manajemen Pengguna', 'icon' => 'manage_accounts', 'link' => '/superadmin/users'];
        $menus[] = ['title' => 'Konfigurasi Global', 'icon' => 'settings', 'link' => '/superadmin/settings'];
        $menus[] = ['title' => 'Menu Privilege Mapping', 'icon' => 'account_tree', 'link' => '/superadmin/menus/list'];
        $menus[] = ['title' => 'Audit Log & Security', 'icon' => 'security', 'link' => '/superadmin/audit/list'];
        break;
}
?>
<?php
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentUri = '/' . trim($requestUri, '/');
?>
<aside id="appSidebar" class="fixed top-0 left-0 h-screen lg:top-4 lg:h-[calc(100vh-2rem)] w-72 lg:w-20 lg:hover:w-72 xl:w-72 bg-surface-container-lowest border-r lg:border border-outline-variant/15 lg:rounded-2xl flex flex-col justify-between py-6 px-4 lg:px-2 lg:hover:px-4 xl:px-4 z-40 -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out shadow-[4px_4px_30px_rgba(0,6,102,0.05)] group">
    <!-- Brand Logo, Desktop Toggle and Mobile Close Button -->
    <!-- Brand Logo, Desktop Toggle and Mobile Close Button -->
    <div class="brand-logo-container flex items-center justify-between px-3 lg:px-2 lg:group-hover:px-3 xl:px-3 mb-6 flex-shrink-0">
        <a href="/dashboard" data-spa class="brand-logo-link text-2xl font-black text-primary font-headline tracking-tight flex items-center gap-2.5 hover:opacity-90 transition-opacity">
            <span class="brand-logo-icon material-symbols-outlined text-primary text-3xl font-bold flex-shrink-0">local_police</span>
            <span class="brand-text bg-gradient-to-r from-primary to-blue-800 bg-clip-text text-transparent whitespace-nowrap">siCare</span>
        </a>
        <!-- Desktop Sidebar Toggle (Gemini Style) -->
        <button id="desktopSidebarToggle" class="hidden lg:flex p-1.5 text-on-surface-variant hover:bg-surface-container-high rounded-lg transition-all items-center justify-center cursor-pointer select-none">
            <span class="material-symbols-outlined text-lg">side_navigation</span>
        </button>
        <!-- Close Button (Mobile Only, using matching Gemini style icon) -->
        <button id="mobileSidebarClose" class="lg:hidden p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full transition-colors flex items-center justify-center">
            <span class="material-symbols-outlined">side_navigation</span>
        </button>
    </div>

    <!-- Scrollable Navigation Menu -->
    <nav class="flex-grow overflow-y-auto space-y-1.5 px-1 pr-2 -mr-2 scrollbar-thin scrollbar-thumb-surface-container-high scrollbar-track-transparent">
        <?php foreach($menus as $menu): 
            $cleanUri = rtrim($currentUri, '/');
            $cleanLink = rtrim($menu['link'], '/');
            $isActive = ($cleanUri === $cleanLink || str_ends_with($cleanUri, $cleanLink));
            $activeClass = $isActive 
                ? 'bg-primary/10 text-primary font-bold shadow-[0_4px_12px_rgba(0,6,102,0.02)]' 
                : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary transition-all duration-200';
        ?>
        <a href="<?php echo htmlspecialchars($menu['link']); ?>" data-spa class="block" data-tooltip="<?php echo htmlspecialchars($menu['title']); ?>">
            <div class="rounded-xl p-3 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-3 group cursor-pointer transition-all duration-200 <?php echo $activeClass; ?>">
                <span class="material-symbols-outlined flex-shrink-0 transition-colors <?php echo $isActive ? 'text-primary' : 'text-on-surface-variant group-hover:text-primary'; ?>"><?php echo htmlspecialchars($menu['icon']); ?></span>
                <span class="text-sm font-medium flex-grow whitespace-nowrap transition-colors <?php echo $isActive ? 'text-primary' : 'text-on-surface-variant group-hover:text-primary'; ?>"><?php echo htmlspecialchars($menu['title']); ?></span>
                <span class="material-symbols-outlined text-sm flex-shrink-0 ml-auto <?php echo $isActive ? 'text-primary opacity-100' : 'text-primary/50 opacity-0 group-hover:opacity-100'; ?>">chevron_right</span>
            </div>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Footer: Profile and Sign Out -->
    <div class="border-t border-outline-variant/15 pt-4 mt-4 flex-shrink-0 space-y-3">
        <!-- Profile Widget -->
        <?php
        $profileFolder = $sessRole;
        if ($sessRole === 'hiring_manager') $profileFolder = 'manager';
        if ($sessRole === 'hr_ops') $profileFolder = 'hrops';
        ?>
        <a href="/<?= $profileFolder ?>/profile" data-spa class="block" data-tooltip="Profil: <?= htmlspecialchars($sessName) ?>">
            <div class="profile-widget-container flex items-center gap-3 p-2 rounded-xl hover:bg-surface-container-low transition-all duration-200 group/profile cursor-pointer">
                <div class="relative flex-shrink-0">
                    <img src="<?= htmlspecialchars($profilePic) ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-outline-variant/20 shadow-sm" />
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-surface-container-lowest rounded-full"></span>
                </div>
                <div class="profile-details flex-grow min-w-0 whitespace-nowrap">
                    <h4 class="text-xs font-extrabold text-on-surface truncate"><?= htmlspecialchars($sessName) ?></h4>
                    <?php 
                    $roleLabel = str_replace('_', ' ', $sessRole);
                    if ($sessRole === 'employee') $roleLabel = 'Karyawan Aktif';
                    ?>
                    <p class="text-[9px] font-bold text-primary uppercase tracking-wider mt-0.5"><?= htmlspecialchars($roleLabel) ?></p>
                </div>
            </div>
        </a>

        <!-- Logout Button -->
        <button onclick="confirmAction('Keluar dari sistem?', 'Anda akan diarahkan ke halaman utama.', '/auth/logout', 'Ya, Keluar')" class="w-full text-left" data-tooltip="Keluar">
            <div class="rounded-xl p-3 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-3 cursor-pointer bg-red-50 hover:bg-red-100/80 transition-all duration-200 border border-red-100">
                <span class="material-symbols-outlined text-red-600 flex-shrink-0">logout</span>
                <span class="text-sm font-bold text-red-600 whitespace-nowrap">Keluar</span>
                <span class="material-symbols-outlined text-sm text-red-400 ml-auto">chevron_right</span>
            </div>
        </button>
    </div>
</aside>
