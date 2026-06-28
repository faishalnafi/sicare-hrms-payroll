<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Manajemen Pengguna</h1>
            <p class="text-on-surface-variant font-medium text-sm font-body">Kelola hak akses sistem, posisi jabatan, mutasi departemen, status aktif, dan lakukan pembaruan data pengguna.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-primary/5 text-primary text-xs font-extrabold px-3.5 py-2 rounded-full border border-primary/10 flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[14px]">shield_person</span>
                Otoritas Super Admin
            </span>
        </div>
    </div>

    <!-- Filters and Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 overflow-hidden shadow-sm flex flex-col">
        <!-- Search and Filter Header -->
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest">
            <div class="flex flex-col lg:flex-row gap-4 items-end">
                <div class="flex-grow w-full relative">
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Cari Pengguna</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50">search</span>
                        <input type="text" id="superAdminUserSearchInput" placeholder="Cari nama, NIK, atau email..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
                <div class="flex flex-wrap md:flex-nowrap items-end gap-3 w-full lg:w-auto">
                    <!-- Filter Role -->
                    <div class="w-full md:w-56">
                        <label class="block text-xs font-bold text-on-surface-variant mb-2">Filter Role Sistem</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50">badge</span>
                            <select id="superAdminUserRoleFilter" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <option value="">Semua Role</option>
                                <option value="employee">Employee</option>
                                <option value="hr_ops">HR Ops</option>
                                <option value="hiring_manager">Hiring Manager</option>
                                <option value="recruiter">Recruiter</option>
                                <option value="executive">Executive</option>
                                <option value="admin">Admin</option>
                                <option value="superadmin">Superadmin</option>
                                <option value="candidate">Candidate</option>
                            </select>
                            
                        </div>
                    </div>
                    <!-- Tambah Pengguna Button -->
                    <div class="w-full md:w-auto flex-shrink-0">
                        <button id="superAdminAddUserBtn" onclick="window.openSuperAdminMutationModal()" class="w-full px-4 py-2.5 bg-primary hover:bg-primary/95 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98]">
                            <span class="material-symbols-outlined text-lg">person_add</span>
                            <span>Tambah Pengguna</span>
                        </button>
                    </div>
                    <!-- Trash Button -->
                    <div class="flex-shrink-0">
                        <button id="superAdminTrashBtn" onclick="window.toggleSuperAdminTrashFilter()" class="p-2.5 bg-surface-container-low hover:bg-surface-container-high text-on-surface-variant rounded-xl border border-outline-variant/30 transition-all flex items-center justify-center hover:scale-[1.02] active:scale-[0.98] shadow-sm" title="Tampilkan Akun Ditangguhkan (Trash)">
                            <span class="material-symbols-outlined text-xl">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse table-standardized" data-has-custom-pagination="true">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="no-col w-12 text-center py-4 px-6 text-[10px] font-extrabold uppercase tracking-wider">No</th>
                        <th id="saColPengguna" onclick="window.toggleSuperAdminUserSort('nama')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Pengguna</th>
                        <th id="saColEmail" onclick="window.toggleSuperAdminUserSort('email')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Email</th>
                        <th id="saColStruktur" onclick="window.toggleSuperAdminUserSort('struktur')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Struktur Organisasi (Posisi & Divisi)</th>
                        <th id="saColAkses" onclick="window.toggleSuperAdminUserSort('akses')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Akses Sistem</th>
                        <th id="saColAksi" onclick="window.toggleSuperAdminUserSort('aksi')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="superAdminUsersTableBody" class="divide-y divide-outline-variant/10 font-body">
                    <tr class="empty-row hidden">
                        <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl mb-2 opacity-50 block">manage_accounts</span>
                            <p class="font-bold">Data pengguna tidak ditemukan</p>
                        </td>
                    </tr>
                    <tr class="loading-row">
                        <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-3xl mb-2 opacity-50 block animate-spin">autorenew</span>
                            <p class="text-sm font-semibold">Memuat data...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="superAdminUserPagination" class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-between bg-surface-container-low/30 hidden">
            <!-- Left Info -->
            <div id="superAdminUserPaginationInfo" class="text-sm text-on-surface-variant font-medium">
                Menampilkan data 0 sampai 0 dari 0
            </div>
            <!-- Right Buttons -->
            <div class="flex items-center gap-1.5" id="superAdminUserPaginationBtns">
                <!-- First Page -->
                <button id="superAdminUserFirstBtn" onclick="window.firstSuperAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Pertama">
                    <span class="material-symbols-outlined text-sm">first_page</span>
                </button>
                <!-- Prev Page -->
                <button id="superAdminUserPrevBtn" onclick="window.prevSuperAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Sebelumnya">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                
                <!-- Page numbers container -->
                <div id="superAdminUserPageNumbers" class="flex items-center gap-1">
                    <!-- Dynamic Page Numbers -->
                </div>

                <!-- Next Page -->
                <button id="superAdminUserNextBtn" onclick="window.nextSuperAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Berikutnya">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
                <!-- Last Page -->
                <button id="superAdminUserLastBtn" onclick="window.lastSuperAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Terakhir">
                    <span class="material-symbols-outlined text-sm">last_page</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Mutation Modal -->
<div id="superAdminMutationModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="window.closeSuperAdminMutationModal()"></div>
    <div id="superAdminMutationModalContainer" class="bg-surface-container-lowest rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden scale-95 transition-transform duration-300 relative z-10 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-outline-variant/15 flex justify-between items-center bg-surface-container-low/30">
            <h3 class="font-headline text-xl font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">edit</span>
                Edit Data User Lengkap (Super Admin)
            </h3>
            <button onclick="window.closeSuperAdminMutationModal()" class="text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high p-2 rounded-full transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Form Body -->
        <form id="superAdminMutationForm" onsubmit="window.submitSuperAdminMutationForm(event)" class="overflow-y-auto p-6 space-y-6 custom-scrollbar font-body">
            <input type="hidden" id="superAdminMutId" name="id">
            
            <!-- User Summary Details Card -->
            <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/20 flex items-center gap-4">
                <img id="superAdminMutAvatar" src="" class="w-14 h-14 rounded-full object-cover border border-outline-variant/30 bg-surface shadow-sm" alt="Avatar">
                <div class="space-y-1">
                    <h4 id="superAdminMutFullName" class="text-base font-extrabold text-on-surface font-headline leading-tight">User Fullname</h4>
                    <p id="superAdminMutEmail" class="text-xs font-semibold text-on-surface-variant">user@email.com</p>
                    <span id="superAdminMutCurrentRole" class="text-[9px] font-bold uppercase tracking-wider bg-primary/10 text-primary px-2 py-0.5 rounded">Role</span>
                </div>
            </div>

            <!-- Tab Headers -->
            <div class="flex border-b border-outline-variant/15 font-headline font-semibold text-xs gap-1 overflow-x-auto pb-1 custom-scrollbar">
                <button type="button" onclick="window.switchSuperAdminMutTab('kepegawaian')" id="tab-sa-mut-kepegawaian" class="px-3 py-2 text-primary border-b-2 border-primary transition-all whitespace-nowrap">Kepegawaian</button>
                <button type="button" onclick="window.switchSuperAdminMutTab('profil')" id="tab-sa-mut-profil" class="px-3 py-2 text-on-surface-variant hover:text-on-surface border-b-2 border-transparent transition-all whitespace-nowrap">Profil & Kependudukan</button>
                <button type="button" onclick="window.switchSuperAdminMutTab('finansial')" id="tab-sa-mut-finansial" class="px-3 py-2 text-on-surface-variant hover:text-on-surface border-b-2 border-transparent transition-all whitespace-nowrap">Finansial</button>
                <button type="button" onclick="window.switchSuperAdminMutTab('lainnya')" id="tab-sa-mut-lainnya" class="px-3 py-2 text-on-surface-variant hover:text-on-surface border-b-2 border-transparent transition-all whitespace-nowrap">Plafon & Fisik</button>
            </div>

            <!-- Tab 1: Kepegawaian & Akses -->
            <div id="content-sa-mut-kepegawaian" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employee ID (Staff ID) -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Employee ID (NIK Perusahaan)</label>
                        <input type="text" id="superAdminMutStaffId" name="employee_id" placeholder="Cth: EM-2026-001" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all uppercase">
                    </div>
                    <!-- Job Title -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Posisi / Jabatan (Job Title)</label>
                        <input type="text" id="superAdminMutJobTitle" name="job_title" placeholder="Cth: Frontend Lead" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Department Selector -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Departemen (Divisi)</label>
                        <div class="relative">
                            <select id="superAdminMutDepartment" name="department_id" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <option value="">Tanpa Departemen</option>
                                <!-- Populated dynamically -->
                            </select>
                            
                        </div>
                    </div>
                    <!-- System Role -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Role Sistem *</label>
                        <div class="relative">
                            <select id="superAdminMutRole" name="role" required class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <option value="employee">Employee</option>
                                <option value="hr_ops">HR Ops</option>
                                <option value="hiring_manager">Hiring Manager</option>
                                <option value="recruiter">Recruiter</option>
                                <option value="executive">Executive</option>
                                <option value="admin">Admin</option>
                                <option value="superadmin">Superadmin</option>
                                <option value="candidate">Candidate</option>
                            </select>
                            
                        </div>
                    </div>
                    <!-- Kuota Cuti Tahunan -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Kuota Cuti Tahunan</label>
                        <input type="number" id="superAdminMutLeaveQuota" name="annual_leave_quota" placeholder="Cth: 12" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Gaji Pokok -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Gaji Pokok</label>
                        <input type="text" id="superAdminMutBaseSalary" name="base_salary" placeholder="Cth: Rp 10.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Reset Password (optional for admin safety) -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Reset Sandi Pengguna <span class="text-[10px] font-normal lowercase text-on-surface-variant/75">(kosongkan jika tidak ingin mengubah sandi)</span></label>
                        <div class="relative group">
                            <input type="password" id="superAdminMutPassword" name="password" placeholder="Masukkan sandi baru jika diperlukan..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-12 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <button type="button" id="superAdminToggleMutPassword" class="absolute right-3 top-3 text-on-surface-variant/40 hover:text-primary transition-colors flex items-center justify-center">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                        <!-- Strength Indicators -->
                        <div id="superAdminMutPwStrengthBox" class="hidden p-4 bg-surface-container-low rounded-xl border border-outline-variant/15 text-xs space-y-2 mt-2">
                            <p class="font-bold text-on-surface-variant mb-1.5">Kriteria Kata Sandi Kuat:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-medium">
                                <div id="superAdminMutPwLen" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Minimal 8 Karakter</div>
                                <div id="superAdminMutPwUpper" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kapital (A-Z)</div>
                                <div id="superAdminMutPwLower" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kecil (a-z)</div>
                                <div id="superAdminMutPwNum" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Angka (0-9)</div>
                                <div id="superAdminMutPwSpec" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Karakter Simbol (@$!%*?&amp;...)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Profil & Kependudukan -->
            <div id="content-sa-mut-profil" class="space-y-6 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- First Name -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Depan *</label>
                        <input type="text" id="superAdminMutFirstName" name="first_name" required placeholder="Nama Depan" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Last Name -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Belakang</label>
                        <input type="text" id="superAdminMutLastName" name="last_name" placeholder="Nama Belakang" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Email *</label>
                        <input type="email" id="superAdminMutEmailHidden" name="email" required placeholder="email@domain.com" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Phone Number -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor Telepon</label>
                        <input type="text" id="superAdminMutPhone" name="no_telepon" placeholder="Cth: 08123456789" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Nama Sesuai KTP -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Sesuai KTP</label>
                        <input type="text" id="superAdminMutKtpName" name="nama_sesuai_ktp" placeholder="Cth: JONATHAN DOE" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- NIK KTP -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">NIK KTP (16 Digit)</label>
                        <input type="text" id="superAdminMutKtpNik" name="ktp_nik" placeholder="Cth: 317101XXXXXXXXXX" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Tanggal Lahir -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Tanggal Lahir</label>
                        <input type="date" id="superAdminMutBirthDate" name="tanggal_lahir" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- Gender -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Jenis Kelamin</label>
                        <div class="relative">
                            <select id="superAdminMutGender" name="jenis_kelamin" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                            
                        </div>
                    </div>
                    <!-- Status Pernikahan -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Status Pernikahan</label>
                        <div class="relative">
                            <select id="superAdminMutMaritalStatus" name="status_pernikahan" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <option value="">Pilih Status</option>
                                <option value="Belum Kawin">Belum Kawin</option>
                                <option value="Kawin">Kawin</option>
                                <option value="Cerai Hidup">Cerai Hidup</option>
                                <option value="Cerai Mati">Cerai Mati</option>
                            </select>
                            
                        </div>
                    </div>
                    <div></div>
                    <!-- Alamat KTP -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Alamat Sesuai KTP</label>
                        <textarea id="superAdminMutKtpAddress" name="alamat_ktp" rows="2" placeholder="Masukkan alamat lengkap sesuai KTP..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none"></textarea>
                    </div>
                    <!-- Alamat Domisili -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Alamat Domisili</label>
                        <textarea id="superAdminMutDomisiliAddress" name="alamat_domisili" rows="2" placeholder="Masukkan alamat domisili saat ini..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Finansial & BPJS -->
            <div id="content-sa-mut-finansial" class="space-y-6 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Bank Name -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Bank</label>
                        <input type="text" id="superAdminMutBankName" name="bank_name" placeholder="Cth: BCA, Mandiri, BNI" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- Bank Account Number -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor Rekening Bank</label>
                        <input type="text" id="superAdminMutBankAccountNumber" name="bank_account_number" placeholder="Cth: 5220391823" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- NPWP Number -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor NPWP</label>
                        <input type="text" id="superAdminMutNpwpNumber" name="npwp_number" placeholder="Cth: 09.254.223.1-015.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- BPJS TK -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor BPJS Ketenagakerjaan</label>
                        <input type="text" id="superAdminMutBpjsTk" name="bpjs_tk" placeholder="Cth: 19028391823" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- BPJS Kes -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor BPJS Kesehatan</label>
                        <input type="text" id="superAdminMutBpjsKes" name="bpjs_kes" placeholder="Cth: 00012938123" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                </div>
            </div>

            <!-- Tab 4: Plafon & Fisik -->
            <div id="content-sa-mut-lainnya" class="space-y-6 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Plafon Medis -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Plafon Medis (Bulanan)</label>
                        <input type="text" id="superAdminMutPlafonMedis" name="reimburse_plafon_medis" placeholder="Cth: Rp 500.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Plafon Transport -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Plafon Transport (Bulanan)</label>
                        <input type="text" id="superAdminMutPlafonTransport" name="reimburse_plafon_transport" placeholder="Cth: Rp 1.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Plafon Operasional -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Plafon Operasional (Bulanan)</label>
                        <input type="text" id="superAdminMutPlafonOperasional" name="reimburse_plafon_operasional" placeholder="Cth: Rp 2.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Plafon Makan -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Plafon Makan (Bulanan)</label>
                        <input type="text" id="superAdminMutPlafonMakan" name="reimburse_plafon_makan" placeholder="Cth: Rp 300.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- Uniform Size -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Ukuran Seragam</label>
                        <input type="text" id="superAdminMutUniformSize" name="uniform_size" placeholder="Cth: L, XL, XXL" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all uppercase">
                    </div>
                    <!-- Access Card ID -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">ID Kartu Akses</label>
                        <input type="text" id="superAdminMutCardId" name="id_kartu" placeholder="Cth: AC-829381" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- QR Code ID -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">ID QR Code</label>
                        <input type="text" id="superAdminMutQrId" name="id_qrcode" placeholder="Cth: QR-839283" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <div></div>
                    <!-- Emergency Contact Header -->
                    <div class="md:col-span-2 border-t border-outline-variant/15 pt-4">
                        <h5 class="text-xs font-bold text-primary font-headline uppercase tracking-wider mb-2">Kontak Darurat</h5>
                    </div>
                    <!-- Emergency Name -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Kontak Darurat</label>
                        <input type="text" id="superAdminMutEmergencyName" name="emergency_name" placeholder="Nama lengkap kontak..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Emergency Relation -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Hubungan Kontak Darurat</label>
                        <input type="text" id="superAdminMutEmergencyRelation" name="emergency_relation" placeholder="Cth: Orang Tua, Suami, Istri" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Emergency Phone -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">No. Telepon Darurat</label>
                        <input type="text" id="superAdminMutEmergencyPhone" name="emergency_phone" placeholder="No. telepon aktif..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                </div>
            </div>
        </form>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-outline-variant/15 flex justify-end gap-3 bg-surface-container-lowest">
            <button type="button" onclick="window.closeSuperAdminMutationModal()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-full text-sm font-semibold transition-colors">
                Batal
            </button>
            <button type="submit" form="superAdminMutationForm" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-full text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">published_with_changes</span> Simpan Perubahan Mutasi
            </button>
        </div>
    </div>
</div>

<script>
/**
 * MVC Architecture: View Component
 * OOP Concept: Client-side controller and logic for User Management & Login Simulation.
 * 
 * File Connections:
 * - Backend endpoints: Fetches data from /hrops/employees/list and submits actions to /auth/impersonate
 * - Routing: Mapped dynamically in public/index.php
 * - Layout: Loaded into the master SPA shell resources/views/layouts/app.php
 */
(function() {
    // ─── Module State ───────────────────────────────────────────────────────────
    var superAdminUsersData       = [];
    var superAdminDepsData        = [];
    var currentSuperAdminUserPage = 1;
    var totalSuperAdminUserPages = 1;
    var itemsPerSuperAdminUserPage = 10;
    var currentLoggedInUserId     = <?= json_encode($_SESSION['user_id'] ?? '') ?>;
    var currentSuperAdminSortCol  = 'nama';
    var currentSuperAdminSortDir  = 'asc';
    
    window.superAdminShowSuspendedOnly = false;
    window.toggleSuperAdminTrashFilter = function() {
        window.superAdminShowSuspendedOnly = !window.superAdminShowSuspendedOnly;
        var btn = document.getElementById("superAdminTrashBtn");
        var addBtn = document.getElementById("superAdminAddUserBtn");
        if (btn) {
            if (window.superAdminShowSuspendedOnly) {
                btn.className = "p-2.5 bg-red-100 text-red-700 rounded-xl border border-red-300 transition-all flex items-center justify-center hover:scale-[1.02] active:scale-[0.98] shadow-sm";
                btn.innerHTML = '<span class="material-symbols-outlined text-xl">group</span>';
                btn.title = "Kembali ke Daftar Pengguna";
            } else {
                btn.className = "p-2.5 bg-surface-container-low hover:bg-surface-container-high text-on-surface-variant rounded-xl border border-outline-variant/30 transition-all flex items-center justify-center hover:scale-[1.02] active:scale-[0.98] shadow-sm";
                btn.innerHTML = '<span class="material-symbols-outlined text-xl">delete</span>';
                btn.title = "Tampilkan Tempat Sampah (Trash)";
            }
        }
        if (addBtn) {
            if (window.superAdminShowSuspendedOnly) {
                addBtn.className = "w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98]";
                addBtn.innerHTML = '<span class="material-symbols-outlined text-lg">delete_forever</span><span>Kosongkan Sampah</span>';
                addBtn.setAttribute("onclick", "window.emptySuperAdminTrash()");
            } else {
                addBtn.className = "w-full px-4 py-2.5 bg-primary hover:bg-primary/95 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98]";
                addBtn.innerHTML = '<span class="material-symbols-outlined text-lg">person_add</span><span>Tambah Pengguna</span>';
                addBtn.setAttribute("onclick", "window.openSuperAdminMutationModal()");
            }
        }
        currentSuperAdminUserPage = 1;
        window.renderSuperAdminUsersTable();
    };

    window.emptySuperAdminTrash = function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Kosongkan Tempat Sampah?',
                text: 'Semua akun pengguna di tempat sampah akan dihapus secara permanen dari sistem. Tindakan ini tidak dapat dibatalkan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus Permanen!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) {
                    fetch('/hrops/employees/empty-trash', { method: 'POST' })
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Berhasil',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonColor: '#000666'
                                });
                                window.loadSuperAdminUsers();
                            } else {
                                Swal.fire('Gagal', data.message, 'error');
                            }
                        })
                        .catch(function() {
                            Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                        });
                }
            });
        }
    };

    function sortSuperAdminUsersData() {
        superAdminUsersData.sort(function(a, b) {
            var valA = getSuperAdminSortValue(a, currentSuperAdminSortCol);
            var valB = getSuperAdminSortValue(b, currentSuperAdminSortCol);
            if (typeof valA === 'string') {
                return currentSuperAdminSortDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
            } else {
                return currentSuperAdminSortDir === 'asc' ? (valA - valB) : (valB - valA);
            }
        });
    }

    function getSuperAdminSortValue(user, col) {
        switch (col) {
            case 'nama':
                return (user.first_name + ' ' + (user.last_name || '')).trim().toLowerCase();
            case 'email':
                return (user.email || '').trim().toLowerCase();
            case 'struktur':
                return ((user.job_title || '') + ' ' + (user.department_name || '')).trim().toLowerCase();
            case 'akses':
                return (user.role || '').trim().toLowerCase();
            case 'aksi':
                return parseInt(user.is_suspended || 0);
            default:
                return '';
        }
    }

    window.toggleSuperAdminUserSort = function(column) {
        if (currentSuperAdminSortCol === column) {
            currentSuperAdminSortDir = currentSuperAdminSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            currentSuperAdminSortCol = column;
            currentSuperAdminSortDir = 'asc';
        }
        sortSuperAdminUsersData();
        window.renderSuperAdminUsersTable();
    };

    // ─── Helpers ────────────────────────────────────────────────────────────────
    /**
     * Utility Helper: Escapes HTML special characters to prevent Cross-Site Scripting (XSS).
     * OOP Concept: Pure utility function.
     * 
     * @param {string} text
     * @return {string}
     */
    function escHtml(text) {
        if (!text) return "";
        return String(text).replace(/[&<>"']/g, function(m) {
            return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[m];
        });
    }

    /**
     * Utility Helper: Determines CSS style class based on user role string.
     * OOP Concept: Role style mapping representation.
     * 
     * @param {string} role
     * @return {string}
     */
    function getRoleBadgeClass(role) {
        var map = {
            "superadmin": "bg-red-100 text-red-700",
            "admin":      "bg-purple-100 text-purple-700",
            "executive":  "bg-amber-100 text-amber-700",
            "hr_ops":     "bg-blue-100 text-blue-700",
            "hiring_manager": "bg-teal-100 text-teal-700",
            "recruiter":  "bg-indigo-100 text-indigo-700",
            "employee":   "bg-green-100 text-green-700",
            "candidate":  "bg-gray-100 text-gray-600"
        };
        return map[role] || "bg-gray-100 text-gray-600";
    }

    // ─── Load Data ──────────────────────────────────────────────────────────────
    /**
     * MVC View Logic: Asynchronously fetches employees and departments from database.
     * OOP Concept: Ajax fetch call with Promise resolution.
     * connects: App\Controllers\EmployeeMasterController::list via /hrops/employees/list.
     */
    window.loadSuperAdminUsers = function() {
        // Show loading state
        var tbody = document.getElementById("superAdminUsersTableBody");
        if (tbody) {
            var lr = tbody.querySelector(".loading-row");
            if (lr) lr.classList.remove("hidden");
            var er = tbody.querySelector(".empty-row");
            if (er) er.classList.add("hidden");
            // Remove old data rows
            var oldRows = tbody.querySelectorAll("tr.user-data-row");
            oldRows.forEach(function(r) { r.remove(); });
        }

        fetch("/hrops/employees/list")
            .then(function(response) {
                if (!response.ok) throw new Error("HTTP " + response.status);
                return response.json();
            })
            .then(function(res) {
                if (res.success) {
                    superAdminUsersData = res.data || [];
                    superAdminDepsData  = res.departments || [];
                    sortSuperAdminUsersData();
                    window.populateSuperAdminDeptDropdown();
                    window.renderSuperAdminUsersTable();
                } else {
                    if (typeof Swal !== "undefined") {
                        Swal.fire("Error", res.message || "Gagal memuat data", "error");
                    }
                }
            })
            .catch(function(e) {
                console.error("Super Admin users fetch error:", e);
                if (typeof Swal !== "undefined") {
                    Swal.fire("Error", "Gagal memuat data pengguna: " + e.message, "error");
                }
            });
    };

    // ─── Populate Department Dropdown ───────────────────────────────────────────
    /**
     * MVC View Helper: Builds hierarchical options for the department mutation select.
     * OOP Concept: Tree traversal algorithm on child arrays.
     * connects: #superAdminMutDepartment dropdown selector.
     */
    window.populateSuperAdminDeptDropdown = function() {
        var depSelect = document.getElementById("superAdminMutDepartment");
        if (!depSelect) return;

        depSelect.innerHTML = "<option value=\"\">Tanpa Departemen</option>";

        var map   = {};
        var roots = [];

        superAdminDepsData.forEach(function(dep) {
            map[dep.id] = Object.assign({}, dep, { children: [] });
        });
        superAdminDepsData.forEach(function(dep) {
            var mapped = map[dep.id];
            if (dep.parent_id && map[dep.parent_id]) {
                map[dep.parent_id].children.push(mapped);
            } else {
                roots.push(mapped);
            }
        });

        function addOptions(nodes, prefix) {
            prefix = prefix || "";
            nodes.forEach(function(node, index) {
                var num = prefix ? (prefix + "." + (index + 1)) : String(index + 1);
                var opt = document.createElement("option");
                opt.value = node.id;
                var indent = "";
                for (var i = 1; i < node.level; i++) indent += "\u00a0\u00a0";
                opt.textContent = indent + num + ". " + node.name;
                depSelect.appendChild(opt);
                if (node.children && node.children.length > 0) {
                    node.children.sort(function(a, b) { return a.name.localeCompare(b.name); });
                    addOptions(node.children, num);
                }
            });
        }

        roots.sort(function(a, b) { return a.name.localeCompare(b.name); });
        addOptions(roots);
    };

    // ─── Render Table ───────────────────────────────────────────────────────────
    /**
     * MVC View Helper: Renders the paginated user list table with search and role filters.
     * OOP Concept: State-based UI rendering, pagination partitioning.
     * connects: #superAdminUsersTableBody, search, and filter elements.
     */
    window.renderSuperAdminUsersTable = function() {
        var tbody    = document.getElementById("superAdminUsersTableBody");
        if (!tbody) return;

        // Update header classes for sorting visual representation
        var cols = {
            'nama': 'saColPengguna',
            'email': 'saColEmail',
            'struktur': 'saColStruktur',
            'akses': 'saColAkses',
            'aksi': 'saColAksi'
        };
        Object.keys(cols).forEach(function(col) {
            var el = document.getElementById(cols[col]);
            if (el) {
                el.classList.remove('sort-active-asc', 'sort-active-desc');
                if (col === currentSuperAdminSortCol) {
                    el.classList.add(currentSuperAdminSortDir === 'asc' ? 'sort-active-asc' : 'sort-active-desc');
                }
            }
        });

        var emptyRow   = tbody.querySelector(".empty-row");
        var loadingRow = tbody.querySelector(".loading-row");

        // Hide loading
        if (loadingRow) loadingRow.classList.add("hidden");

        // Remove old data rows
        var oldRows = tbody.querySelectorAll("tr.user-data-row");
        oldRows.forEach(function(r) { r.remove(); });

        var searchEl     = document.getElementById("superAdminUserSearchInput");
        var roleFilterEl = document.getElementById("superAdminUserRoleFilter");
        var search       = searchEl ? searchEl.value.toLowerCase() : "";
        var roleFilter   = roleFilterEl ? roleFilterEl.value : "";

        var filtered = superAdminUsersData.filter(function(user) {
            if (window.superAdminShowSuspendedOnly) {
                if (parseInt(user.is_deleted || 0) !== 1) return false;
            } else {
                if (parseInt(user.is_deleted || 0) === 1) return false;
            }

            var textMatch =
                (user.first_name  || "").toLowerCase().includes(search) ||
                (user.last_name   || "").toLowerCase().includes(search) ||
                (user.email       || "").toLowerCase().includes(search) ||
                (user.employee_id || "").toLowerCase().includes(search);
            var roleMatch = !roleFilter || user.role === roleFilter;
            return textMatch && roleMatch;
        });

        var totalItems = filtered.length;
        var totalPages = Math.ceil(totalItems / itemsPerSuperAdminUserPage) || 1;
        if (currentSuperAdminUserPage > totalPages) currentSuperAdminUserPage = totalPages;
        if (currentSuperAdminUserPage < 1) currentSuperAdminUserPage = 1;

        var startIdx = (currentSuperAdminUserPage - 1) * itemsPerSuperAdminUserPage;
        var endIdx   = Math.min(startIdx + itemsPerSuperAdminUserPage, totalItems);

        if (totalItems === 0) {
            if (emptyRow) emptyRow.classList.remove("hidden");
        } else {
            if (emptyRow) emptyRow.classList.add("hidden");

            for (var i = startIdx; i < endIdx; i++) {
                var user = filtered[i];
                var tr = document.createElement("tr");
                tr.className = "user-data-row hover:bg-surface-container-low/30 transition-colors group";

                var fullName = escHtml(user.first_name + (user.last_name ? " " + user.last_name : ""));
                var empId    = user.employee_id
                    ? escHtml(user.employee_id)
                    : "<span class=\"text-[9px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded font-bold uppercase\">BELUM ADA NIK</span>";
                var md5Hash = window.md5((user.email || "").trim().toLowerCase());
                var pp = user.profile_picture
                    ? escHtml(user.profile_picture)
                    : "https://www.gravatar.com/avatar/" + md5Hash + "?d=identicon&s=120";
                var badgeClass = getRoleBadgeClass(user.role);

                // Impersonation button mapping using dataset attributes
                var impersonateButton = "";
                if (user.id !== currentLoggedInUserId && parseInt(user.is_deleted || 0) !== 1) {
                    impersonateButton = 
                        "<button onclick=\"window.startSuperAdminImpersonate(this)\" data-id=\"" + escHtml(user.id) + "\" data-name=\"" + fullName + "\" class=\"px-3 py-1.5 bg-amber-500/10 hover:bg-amber-500 text-amber-700 hover:text-white rounded-full text-xs font-bold border border-amber-500/20 shadow-sm transition-all flex items-center gap-1\">" +
                            "<span class=\"material-symbols-outlined text-[14px]\">login</span> Simulasi" +
                        "</button>";
                }

                // Reset lockout button if user has failed attempts
                var resetLockoutButton = "";
                if (user.failed_attempts && parseInt(user.failed_attempts) > 0 && parseInt(user.is_deleted || 0) !== 1) {
                    var isBlocked = parseInt(user.failed_attempts) >= 21;
                    var btnColor = isBlocked
                        ? "bg-red-600 hover:bg-red-700 text-white border-red-700"
                        : "bg-orange-500/10 hover:bg-orange-500 text-orange-700 hover:text-white border-orange-500/20";
                    var btnText = isBlocked ? "Reset Blokir" : "Reset Percobaan";
                    var btnTitle = isBlocked
                        ? "Akun ini diblokir (gagal login " + user.failed_attempts + " kali). Klik untuk membuka blokir."
                        : "Gagal login " + user.failed_attempts + " kali. Klik untuk mereset.";
                    resetLockoutButton = 
                        "<button onclick=\"window.triggerSuperAdminResetLockout(this)\" data-id=\"" + escHtml(user.id) + "\" data-name=\"" + fullName + "\" class=\"px-3 py-1.5 rounded-full text-xs font-bold border shadow-sm transition-all flex items-center gap-1 " + btnColor + "\" title=\"" + btnTitle + "\">" +
                            "<span class=\"material-symbols-outlined text-[14px]\">lock_open</span> " + btnText +
                        "</button>";
                }

                // Edit Button
                var editButton = "";
                if (parseInt(user.is_deleted || 0) !== 1) {
                    editButton = 
                        "<button onclick=\"window.openSuperAdminMutationModal('" + escHtml(user.id) + "')\" class=\"px-3 py-1.5 bg-primary/5 hover:bg-primary text-primary hover:text-white rounded-full text-xs font-bold border border-primary/10 shadow-sm transition-all flex items-center gap-1\" title=\"Edit data user lengkap\">" +
                            "<span class=\"material-symbols-outlined text-[14px]\">edit</span> Edit" +
                        "</button>";
                }

                // Suspend/Aktifkan / Restore Button
                var suspendButton = "";
                if (user.id !== currentLoggedInUserId) {
                    if (parseInt(user.is_deleted || 0) === 1) {
                        suspendButton = 
                            "<button onclick=\"window.triggerSuperAdminRestore(this)\" data-id=\"" + escHtml(user.id) + "\" data-name=\"" + fullName + "\" class=\"px-3 py-1.5 bg-green-500/10 hover:bg-green-500 text-green-700 hover:text-white rounded-full text-xs font-bold border border-green-500/20 shadow-sm transition-all flex items-center gap-1\" title=\"Pulihkan akun\">" +
                                "<span class=\"material-symbols-outlined text-[14px]\">restore</span> Pulihkan" +
                            "</button>";
                    } else {
                        if (parseInt(user.is_suspended) === 1) {
                            suspendButton = 
                                "<button onclick=\"window.triggerSuperAdminToggleSuspend(this)\" data-id=\"" + escHtml(user.id) + "\" data-name=\"" + fullName + "\" class=\"px-3 py-1.5 bg-green-500/10 hover:bg-green-500 text-green-700 hover:text-white rounded-full text-xs font-bold border border-green-500/20 shadow-sm transition-all flex items-center gap-1\" title=\"Aktifkan akun\">" +
                                    "<span class=\"material-symbols-outlined text-[14px]\">check_circle</span> Aktifkan" +
                                "</button>";
                        } else {
                            suspendButton = 
                                "<button onclick=\"window.triggerSuperAdminToggleSuspend(this)\" data-id=\"" + escHtml(user.id) + "\" data-name=\"" + fullName + "\" class=\"px-3 py-1.5 bg-slate-500/10 hover:bg-slate-500 text-slate-700 hover:text-white rounded-full text-xs font-bold border border-slate-500/20 shadow-sm transition-all flex items-center gap-1\" title=\"Tangguhkan (suspend) akun\">" +
                                    "<span class=\"material-symbols-outlined text-[14px]\">block</span> Suspend" +
                                "</button>";
                        }
                    }
                }

                // Hapus/Delete / Hapus Permanen Button
                var deleteButton = "";
                if (user.id !== currentLoggedInUserId && user.role !== 'superadmin') {
                    if (parseInt(user.is_deleted || 0) === 1) {
                        deleteButton = 
                            "<button onclick=\"window.triggerSuperAdminDeletePermanent(this)\" data-id=\"" + escHtml(user.id) + "\" data-name=\"" + fullName + "\" class=\"px-3 py-1.5 bg-red-500/10 hover:bg-red-500 text-red-700 hover:text-white rounded-full text-xs font-bold border border-red-500/20 shadow-sm transition-all flex items-center gap-1\" title=\"Hapus akun secara permanen\">" +
                                "<span class=\"material-symbols-outlined text-[14px]\">delete_forever</span> Hapus Permanen" +
                            "</button>";
                    } else {
                        deleteButton = 
                            "<button onclick=\"window.triggerSuperAdminDelete(this)\" data-id=\"" + escHtml(user.id) + "\" data-name=\"" + fullName + "\" class=\"px-3 py-1.5 bg-red-500/10 hover:bg-red-500 text-red-700 hover:text-white rounded-full text-xs font-bold border border-red-500/20 shadow-sm transition-all flex items-center gap-1\" title=\"Hapus akun\">" +
                                "<span class=\"material-symbols-outlined text-[14px]\">delete</span> Hapus" +
                            "</button>";
                    }
                }

                var rowNum = i + 1;
                tr.innerHTML =
                    "<td class=\"no-col-cell px-6 py-4 text-center font-bold text-xs text-on-surface-variant\">" + rowNum + "</td>" +
                    "<td class=\"px-6 py-4\">" +
                        "<div class=\"flex items-center gap-3\">" +
                            "<img referrerpolicy=\"no-referrer\" src=\"" + pp + "\" class=\"w-10 h-10 rounded-full object-cover border border-outline-variant/30 flex-shrink-0\" alt=\"Avatar\" onerror=\"window.handleAvatarError(this, '" + md5Hash + "')\">" +
                            "<div>" +
                                "<p class=\"text-sm font-bold text-on-surface\">" + fullName + 
                                    (parseInt(user.is_deleted || 0) === 1 ? " <span class=\"text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded border border-red-200 ml-1.5\">TERHAPUS (TRASH)</span>" : (parseInt(user.is_suspended) === 1 ? " <span class=\"text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded border border-red-200 ml-1.5\">DITANGGUHKAN</span>" : "")) + 
                                "</p>" +
                                "<p class=\"text-xs text-on-surface-variant font-mono mt-0.5\">" + empId + "</p>" +
                                "</div>" +
                        "</div>" +
                    "</td>" +
                    "<td class=\"px-6 py-4\">" +
                        "<span class=\"text-sm font-semibold text-on-surface\">" + escHtml(user.email) + "</span>" +
                    "</td>" +
                    "<td class=\"px-6 py-4\">" +
                        "<div class=\"flex flex-col\">" +
                            "<span class=\"text-sm font-bold text-on-surface capitalize\">" + escHtml(user.job_title || "—") + "</span>" +
                            "<span class=\"text-xs text-on-surface-variant mt-0.5\">" + escHtml(user.department_name || "Tanpa Departemen") + "</span>" +
                        "</div>" +
                    "</td>" +
                    "<td class=\"px-6 py-4\">" +
                        "<span class=\"text-[10px] font-bold uppercase inline-block px-2.5 py-1 rounded " + badgeClass + "\">" + escHtml(user.role) + "</span>" +
                    "</td>" +
                    "<td class=\"px-6 py-4 text-right\">" +
                        "<div class=\"flex items-center justify-end gap-1.5 flex-nowrap\">" +
                            impersonateButton +
                            resetLockoutButton +
                            editButton +
                            suspendButton +
                            deleteButton +
                            (parseInt(user.is_deleted || 0) === 1 ? "" : 
                            "<button onclick=\"window.triggerSuperAdminResetProfile(this)\" data-id=\"" + escHtml(user.id) + "\" data-name=\"" + fullName + "\" class=\"px-3 py-1.5 bg-red-500/10 hover:bg-red-500 text-red-700 hover:text-white rounded-full text-xs font-bold border border-red-500/20 shadow-sm transition-all flex items-center gap-1\" title=\"Reset Identitas Lengkap Karyawan\">" +
                                "<span class=\"material-symbols-outlined text-[14px]\">restart_alt</span> Reset" +
                            "</button>") +
                        "</div>" +
                    "</td>";

                tbody.appendChild(tr);
            }
        }

        // Save totalPages globally
        totalSuperAdminUserPages = totalPages;

        // Pagination visibility
        var pagination = document.getElementById("superAdminUserPagination");
        if (pagination) {
            if (totalItems === 0 || totalPages <= 1) {
                pagination.classList.add("hidden");
            } else {
                pagination.classList.remove("hidden");
            }
        }

        // Update Info Text (showing X to Y of Z)
        var infoEl = document.getElementById("superAdminUserPaginationInfo");
        if (infoEl) {
            var startShow = totalItems === 0 ? 0 : startIdx + 1;
            var endShow = endIdx;
            infoEl.textContent = "Menampilkan data " + startShow + " sampai " + endShow + " dari " + totalItems;
        }

        // Update Buttons Disabled States
        var firstBtn = document.getElementById("superAdminUserFirstBtn");
        var prevBtn = document.getElementById("superAdminUserPrevBtn");
        var nextBtn = document.getElementById("superAdminUserNextBtn");
        var lastBtn = document.getElementById("superAdminUserLastBtn");

        if (firstBtn) firstBtn.disabled = currentSuperAdminUserPage === 1;
        if (prevBtn) prevBtn.disabled = currentSuperAdminUserPage === 1;
        if (nextBtn) nextBtn.disabled = currentSuperAdminUserPage === totalPages || totalPages === 0;
        if (lastBtn) lastBtn.disabled = currentSuperAdminUserPage === totalPages || totalPages === 0;

        // Render Page Numbers
        var pageNumbersContainer = document.getElementById("superAdminUserPageNumbers");
        if (pageNumbersContainer) {
            pageNumbersContainer.innerHTML = "";
            
            var startPage = currentSuperAdminUserPage - 1;
            var endPage = currentSuperAdminUserPage + 1;
            if (startPage < 1) {
                startPage = 1;
                endPage = Math.min(3, totalPages);
            }
            if (endPage > totalPages) {
                endPage = totalPages;
                startPage = Math.max(1, totalPages - 2);
            }
            
            for (var p = startPage; p <= endPage; p++) {
                (function(pageNum) {
                    var btn = document.createElement("button");
                    btn.className = "w-8 h-8 flex items-center justify-center rounded-full text-xs font-semibold transition-all border ";
                    if (pageNum === currentSuperAdminUserPage) {
                        btn.className += "bg-primary text-white border-primary shadow-sm";
                    } else {
                        btn.className += "hover:bg-surface-container-high text-on-surface border-transparent";
                    }
                    btn.textContent = pageNum;
                    btn.onclick = function() {
                        currentSuperAdminUserPage = pageNum;
                        window.renderSuperAdminUsersTable();
                    };
                    pageNumbersContainer.appendChild(btn);
                })(p);
            }
        }
    };

    /**
     * MVC View UI Actions: Pagination Navigation helpers.
     */
    window.firstSuperAdminUserPage = function() {
        if (currentSuperAdminUserPage > 1) {
            currentSuperAdminUserPage = 1;
            window.renderSuperAdminUsersTable();
        }
    };

    window.prevSuperAdminUserPage = function() {
        if (currentSuperAdminUserPage > 1) {
            currentSuperAdminUserPage--;
            window.renderSuperAdminUsersTable();
        }
    };

    window.nextSuperAdminUserPage = function() {
        if (currentSuperAdminUserPage < totalSuperAdminUserPages) {
            currentSuperAdminUserPage++;
            window.renderSuperAdminUsersTable();
        }
    };

    window.lastSuperAdminUserPage = function() {
        if (currentSuperAdminUserPage < totalSuperAdminUserPages) {
            currentSuperAdminUserPage = totalSuperAdminUserPages;
            window.renderSuperAdminUsersTable();
        }
    };

    // ─── Impersonation (Simulasi Login) ─────────────────────────────────────────
    /**
     * MVC Controller Call: Initiates superadmin login impersonation/simulation for a target user.
     * OOP Concept: Event receiver with DOM argument type checking.
     * connects: App\Controllers\AuthController::impersonate via POST /auth/impersonate.
     * 
     * @param {string|HTMLElement} idOrEl
     * @param {string} name
     */
    window.startSuperAdminImpersonate = function(idOrEl, name) {
        if (typeof Swal === "undefined") return;
        var id = idOrEl;
        if (idOrEl instanceof HTMLElement) {
            id = idOrEl.getAttribute("data-id");
            name = idOrEl.getAttribute("data-name");
        }

        Swal.fire({
            title: "Simulasi Login?",
            text: "Anda akan masuk ke dalam sistem sebagai \"" + name + "\". Akses dan navigasi Anda akan menyesuaikan dengan peran mereka.",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#d97706", // Amber-600
            cancelButtonColor: "#c6c5d4",
            confirmButtonText: "Ya, Mulai Simulasi",
            cancelButtonText: "Batal"
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append("user_id", id);

                fetch("/auth/impersonate", { method: "POST", body: formData })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({
                                title: "Memulai Simulasi...",
                                text: "Mengalihkan ke dashboard pengguna.",
                                icon: "success",
                                timer: 1500,
                                showConfirmButton: false
                            });
                            setTimeout(function() {
                                window.location.href = data.redirect || "/dashboard";
                            }, 1200);
                        } else {
                            Swal.fire("Error", data.message || "Gagal memulai simulasi", "error");
                        }
                    })
                    .catch(function(err) {
                        console.error("Impersonation error:", err);
                        Swal.fire("Error", "Terjadi kesalahan sistem saat mencoba login.", "error");
                    });
            }
        });
    };

    // ─── Reset Profile ──────────────────────────────────────────────────────────
    /**
     * MVC Controller Call: Resets full profile identity of an employee and creates a reset token.
     * OOP Concept: Event receiver with DOM element extraction.
     * connects: App\Controllers\EmployeeMasterController::resetProfileToken via /superadmin/users/reset-profile.
     * 
     * @param {string|HTMLElement} userIdOrEl
     * @param {string} fullName
     */
    window.triggerSuperAdminResetProfile = function(userIdOrEl, fullName) {
        if (typeof Swal === "undefined") return;
        var userId = userIdOrEl;
        if (userIdOrEl instanceof HTMLElement) {
            userId = userIdOrEl.getAttribute("data-id");
            fullName = userIdOrEl.getAttribute("data-name");
        }

        Swal.fire({
            title: "Reset Identitas Lengkap?",
            text: "Tindakan ini akan mengosongkan seluruh data administratif terkunci (NIK, Rekening Bank, NPWP, BPJS, dll.) milik \"" + fullName + "\" dan membuat tautan baru bagi karyawan untuk mengisinya kembali.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444", // Red-500
            cancelButtonColor: "#c6c5d4",
            confirmButtonText: "Ya, Reset Data",
            cancelButtonText: "Batal"
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append("user_id", userId);
                formData.append("csrf_token", <?= json_encode(csrf_token()) ?>);

                fetch("/superadmin/users/reset-profile", { method: "POST", body: formData })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({
                                title: "Tautan Berhasil Dibuat!",
                                html: "<p class=\"text-sm mb-3\">Tautan pengisian identitas untuk <b>" + fullName + "</b>:</p>" +
                                      "<div class=\"relative bg-surface-container-low border border-outline-variant/30 rounded-xl p-3 mb-4 font-mono text-xs break-all select-all text-left\">" + data.link + "</div>" +
                                      "<p class=\"text-xs text-on-surface-variant\">Karyawan harus login ke portal siCare terlebih dahulu saat membuka tautan ini.</p>",
                                icon: "success",
                                showCancelButton: true,
                                confirmButtonText: "Salin Tautan",
                                cancelButtonText: "Tutup",
                                confirmButtonColor: "#000666"
                            }).then(function(swalResult) {
                                if (swalResult.isConfirmed) {
                                    navigator.clipboard.writeText(data.link).then(function() {
                                        Swal.fire({ title: "Disalin!", text: "Tautan berhasil disalin ke clipboard.", icon: "success", timer: 1500, showConfirmButton: false });
                                    });
                                }
                            });
                            window.loadSuperAdminUsers();
                        } else {
                            Swal.fire("Error", data.message || "Gagal mereset profil", "error");
                        }
                    })
                    .catch(function(err) {
                        console.error("Reset profile error:", err);
                        Swal.fire("Error", "Terjadi kesalahan sistem.", "error");
                    });
            }
        });
    };

    // ─── Reset Lockout / Blokir ──────────────────────────────────────────────────
    /**
     * Resets failed login attempts and lockouts for a user.
     * connects: App\Controllers\EmployeeMasterController::resetLockout via /superadmin/users/reset-lockout.
     * 
     * @param {string|HTMLElement} userIdOrEl
     * @param {string} fullName
     */
    window.triggerSuperAdminResetLockout = function(userIdOrEl, fullName) {
        if (typeof Swal === "undefined") return;
        var userId = userIdOrEl;
        if (userIdOrEl instanceof HTMLElement) {
            userId = userIdOrEl.getAttribute("data-id");
            fullName = userIdOrEl.getAttribute("data-name");
        }

        Swal.fire({
            title: "Reset Blokir Login?",
            text: "Tindakan ini akan menghapus seluruh data percobaan login yang gagal untuk \"" + fullName + "\" sehingga pengguna dapat mencoba masuk kembali.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#f97316", // Orange-500
            cancelButtonColor: "#c6c5d4",
            confirmButtonText: "Ya, Reset Blokir",
            cancelButtonText: "Batal"
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append("user_id", userId);

                fetch("/superadmin/users/reset-lockout", { method: "POST", body: formData })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ title: "Berhasil!", text: data.message, icon: "success", timer: 1500, showConfirmButton: false });
                            window.loadSuperAdminUsers();
                        } else {
                            Swal.fire("Error", data.message || "Gagal mereset blokir", "error");
                        }
                    })
                    .catch(function(err) {
                        console.error("Reset lockout error:", err);
                        Swal.fire("Error", "Terjadi kesalahan sistem.", "error");
                    });
            }
        });
    };

    // ─── Toggle Suspend ──────────────────────────────────────────────────────────
    /**
     * Toggles the suspension status (is_suspended) of a user.
     * connects: App\Controllers\EmployeeMasterController::toggleSuspend via /hrops/employees/toggle-suspend.
     * 
     * @param {HTMLElement} btn
     */
    window.triggerSuperAdminToggleSuspend = function(btn) {
        if (typeof Swal === "undefined") return;
        var userId = btn.getAttribute("data-id");
        var fullName = btn.getAttribute("data-name");
        var isSuspended = btn.innerHTML.indexOf("Aktifkan") !== -1;
        var actionText = isSuspended ? "Mengaktifkan kembali" : "Menangguhkan (suspend)";
        var confirmText = isSuspended ? "Ya, Aktifkan" : "Ya, Suspend";

        Swal.fire({
            title: isSuspended ? "Aktifkan Akun?" : "Suspend Akun?",
            text: "Apakah Anda yakin ingin " + actionText.toLowerCase() + " akun untuk \"" + fullName + "\"? " + 
                  (isSuspended ? "Pengguna akan dapat masuk kembali ke sistem." : "Pengguna tidak akan bisa masuk ke sistem sampai diaktifkan kembali."),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: isSuspended ? "#10b981" : "#64748b", // Green or Slate
            cancelButtonColor: "#c6c5d4",
            confirmButtonText: confirmText,
            cancelButtonText: "Batal"
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append("id", userId);

                fetch("/hrops/employees/toggle-suspend", { method: "POST", body: formData })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ title: "Berhasil!", text: data.message, icon: "success", timer: 1500, showConfirmButton: false });
                            window.loadSuperAdminUsers();
                        } else {
                            Swal.fire("Error", data.message || "Gagal mengubah status akses", "error");
                        }
                    })
                    .catch(function(err) {
                        console.error("Toggle suspend error:", err);
                        Swal.fire("Error", "Terjadi kesalahan sistem.", "error");
                    });
            }
        });
    };

    // ─── Hapus / Delete User ──────────────────────────────────────────────────────
    /**
     * Deletes a user account.
     * connects: App\Controllers\EmployeeMasterController::delete via /hrops/employees/delete.
     * 
     * @param {HTMLElement} btn
     */
    window.triggerSuperAdminDelete = function(btn) {
        if (typeof Swal === "undefined") return;
        var userId = btn.getAttribute("data-id");
        var fullName = btn.getAttribute("data-name");

        Swal.fire({
            title: "Pindahkan ke Tempat Sampah?",
            text: "Akun untuk \"" + fullName + "\" akan dipindahkan ke tempat sampah. Karyawan ini tidak akan bisa login sampai akunnya dipulihkan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444", // Red-500
            cancelButtonColor: "#c6c5d4",
            confirmButtonText: "Ya, Pindahkan ke Sampah",
            cancelButtonText: "Batal"
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append("id", userId);

                fetch("/hrops/employees/delete", { method: "POST", body: formData })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ title: "Berhasil!", text: data.message, icon: "success", timer: 1500, showConfirmButton: false });
                            window.loadSuperAdminUsers();
                        } else {
                            Swal.fire("Error", data.message || "Gagal menghapus data", "error");
                        }
                    })
                    .catch(function(err) {
                        console.error("Delete user error:", err);
                        Swal.fire("Error", "Terjadi kesalahan sistem.", "error");
                    });
            }
        });
    };

    window.triggerSuperAdminRestore = function(btn) {
        if (typeof Swal === "undefined") return;
        var userId = btn.getAttribute("data-id");
        var fullName = btn.getAttribute("data-name");

        Swal.fire({
            title: "Pulihkan Akun Karyawan?",
            text: "Akun untuk \"" + fullName + "\" akan dipulihkan kembali ke daftar karyawan aktif.",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#10b981", // Green
            cancelButtonColor: "#c6c5d4",
            confirmButtonText: "Ya, Pulihkan Akun",
            cancelButtonText: "Batal"
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append("id", userId);

                fetch("/hrops/employees/restore", { method: "POST", body: formData })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ title: "Berhasil!", text: data.message, icon: "success", timer: 1500, showConfirmButton: false });
                            window.loadSuperAdminUsers();
                        } else {
                            Swal.fire("Error", data.message || "Gagal memulihkan data", "error");
                        }
                    })
                    .catch(function(err) {
                        console.error("Restore user error:", err);
                        Swal.fire("Error", "Terjadi kesalahan sistem.", "error");
                    });
            }
        });
    };

    window.triggerSuperAdminDeletePermanent = function(btn) {
        if (typeof Swal === "undefined") return;
        var userId = btn.getAttribute("data-id");
        var fullName = btn.getAttribute("data-name");

        Swal.fire({
            title: "Hapus Akun secara Permanen?",
            text: "Akun untuk \"" + fullName + "\" akan dihapus selamanya dari database. Tindakan ini tidak dapat dibatalkan!",
            icon: "error",
            showCancelButton: true,
            confirmButtonColor: "#ef4444", // Red-500
            cancelButtonColor: "#c6c5d4",
            confirmButtonText: "Ya, Hapus Permanen",
            cancelButtonText: "Batal"
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append("id", userId);

                fetch("/hrops/employees/delete-permanent", { method: "POST", body: formData })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ title: "Terhapus!", text: data.message, icon: "success", timer: 1500, showConfirmButton: false });
                            window.loadSuperAdminUsers();
                        } else {
                            Swal.fire("Error", data.message || "Gagal menghapus data secara permanen", "error");
                        }
                    })
                    .catch(function(err) {
                        console.error("Delete permanent user error:", err);
                        Swal.fire("Error", "Terjadi kesalahan sistem.", "error");
                    });
            }
        });
    };

    // ─── Mutation Modal ─────────────────────────────────────────────────────────
    /**
     * Helper to format currency values to Indonesian Rupiah.
     */
    function formatRupiah(value) {
        if (value === null || value === undefined || value === "") return "Rp 0";
        var number = parseFloat(value);
        if (isNaN(number)) return "Rp 0";
        return "Rp " + number.toLocaleString("id-ID", { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    /**
     * Tab switching handler for Superadmin mutation modal.
     */
    window.switchSuperAdminMutTab = function(tabName) {
        var tabs = ['kepegawaian', 'profil', 'finansial', 'lainnya'];
        tabs.forEach(function(t) {
            var btn = document.getElementById('tab-sa-mut-' + t);
            var content = document.getElementById('content-sa-mut-' + t);
            if (btn) {
                if (t === tabName) {
                    btn.classList.add('text-primary', 'border-primary');
                    btn.classList.remove('text-on-surface-variant', 'border-transparent');
                } else {
                    btn.classList.remove('text-primary', 'border-primary');
                    btn.classList.add('text-on-surface-variant', 'border-transparent');
                }
            }
            if (content) {
                if (t === tabName) {
                    content.classList.remove('hidden');
                } else {
                    content.classList.add('hidden');
                }
            }
        });
    };

    /**
     * MVC View UI Action: Opens the mutation/settings modal and pre-fills input forms.
     * OOP Concept: DOM querying and value mapping.
     * connects: #superAdminMutationModal and individual input elements.
     * 
     * @param {string} id
     */
    window.openSuperAdminMutationModal = function(id) {
        var el = function(eid) { return document.getElementById(eid); };
        var modalTitle = document.querySelector("#superAdminMutationModal h3");

        if (id) {
            // EDIT USER MODE
            var user = superAdminUsersData.find(function(u) { return u.id === id; });
            if (!user) return;

            if (modalTitle) {
                modalTitle.innerHTML = '<span class="material-symbols-outlined text-primary">edit</span> Edit Data User Lengkap (Super Admin)';
            }

            var fullName = (user.first_name || "") + (user.last_name ? " " + user.last_name : "");
            if (el("superAdminMutFullName"))   el("superAdminMutFullName").textContent  = fullName;
            if (el("superAdminMutEmail"))      el("superAdminMutEmail").textContent     = user.email;
            if (el("superAdminMutCurrentRole")) el("superAdminMutCurrentRole").textContent = user.role;
            if (el("superAdminMutAvatar")) {
                el("superAdminMutAvatar").src = user.profile_picture || "https://www.gravatar.com/avatar/" + window.md5((user.email || "").trim().toLowerCase()) + "?d=identicon&s=120";
                el("superAdminMutAvatar").onerror = function() {
                    window.handleAvatarError(this, window.md5((user.email || "").trim().toLowerCase()));
                };
            }

            if (el("superAdminMutId"))          el("superAdminMutId").value          = user.id          || "";
            if (el("superAdminMutStaffId"))     el("superAdminMutStaffId").value     = user.employee_id || "";
            if (el("superAdminMutJobTitle"))    el("superAdminMutJobTitle").value    = user.job_title   || "";
            if (el("superAdminMutDepartment"))  el("superAdminMutDepartment").value  = user.department_id || "";
            if (el("superAdminMutRole"))        el("superAdminMutRole").value        = user.role        || "employee";
            if (el("superAdminMutPassword"))    el("superAdminMutPassword").value    = "";

            if (el("superAdminMutFirstName"))   el("superAdminMutFirstName").value   = user.first_name        || "";
            if (el("superAdminMutLastName"))    el("superAdminMutLastName").value    = user.last_name         || "";
            if (el("superAdminMutEmailHidden")) el("superAdminMutEmailHidden").value = user.email             || "";
            if (el("superAdminMutLeaveQuota"))  el("superAdminMutLeaveQuota").value  = user.annual_leave_quota || 12;
            if (el("superAdminMutBaseSalary"))  el("superAdminMutBaseSalary").value  = formatRupiah(user.base_salary);

            // Populate personal profile inputs
            if (el("superAdminMutPhone"))            el("superAdminMutPhone").value            = user.no_telepon || "";
            if (el("superAdminMutKtpName"))          el("superAdminMutKtpName").value          = user.nama_sesuai_ktp || "";
            if (el("superAdminMutKtpNik"))           el("superAdminMutKtpNik").value           = user.ktp_nik || "";
            if (el("superAdminMutBirthDate"))        el("superAdminMutBirthDate").value        = user.tanggal_lahir || "";
            if (el("superAdminMutGender"))           el("superAdminMutGender").value           = user.jenis_kelamin || "";
            if (el("superAdminMutMaritalStatus"))    el("superAdminMutMaritalStatus").value    = user.status_pernikahan || "";
            if (el("superAdminMutKtpAddress"))       el("superAdminMutKtpAddress").value       = user.alamat_ktp || "";
            if (el("superAdminMutDomisiliAddress"))  el("superAdminMutDomisiliAddress").value  = user.alamat_domisili || "";
            if (el("superAdminMutBankName"))         el("superAdminMutBankName").value         = user.bank_name || "";
            if (el("superAdminMutBankAccountNumber"))el("superAdminMutBankAccountNumber").value = user.bank_account_number || "";
            if (el("superAdminMutNpwpNumber"))       el("superAdminMutNpwpNumber").value       = user.npwp_number || "";
            if (el("superAdminMutBpjsTk"))           el("superAdminMutBpjsTk").value           = user.bpjs_tk || "";
            if (el("superAdminMutBpjsKes"))          el("superAdminMutBpjsKes").value          = user.bpjs_kes || "";
            
            if (el("superAdminMutPlafonMedis"))      el("superAdminMutPlafonMedis").value      = formatRupiah(user.reimburse_plafon_medis);
            if (el("superAdminMutPlafonTransport"))  el("superAdminMutPlafonTransport").value  = formatRupiah(user.reimburse_plafon_transport);
            if (el("superAdminMutPlafonOperasional"))el("superAdminMutPlafonOperasional").value= formatRupiah(user.reimburse_plafon_operasional);
            if (el("superAdminMutPlafonMakan"))      el("superAdminMutPlafonMakan").value      = formatRupiah(user.reimburse_plafon_makan);

            if (el("superAdminMutUniformSize"))      el("superAdminMutUniformSize").value      = user.uniform_size || "";
            if (el("superAdminMutCardId"))           el("superAdminMutCardId").value           = user.id_kartu || "";
            if (el("superAdminMutQrId"))             el("superAdminMutQrId").value             = user.id_qrcode || "";
            if (el("superAdminMutEmergencyName"))    el("superAdminMutEmergencyName").value    = user.emergency_name || "";
            if (el("superAdminMutEmergencyRelation"))el("superAdminMutEmergencyRelation").value= user.emergency_relation || "";
            if (el("superAdminMutEmergencyPhone"))   el("superAdminMutEmergencyPhone").value   = user.emergency_phone || "";
        } else {
            // ADD NEW USER MODE
            if (modalTitle) {
                modalTitle.innerHTML = '<span class="material-symbols-outlined text-primary">person_add</span> Tambah Pengguna Baru';
            }

            if (el("superAdminMutFullName"))   el("superAdminMutFullName").textContent  = "Pengguna Baru";
            if (el("superAdminMutEmail"))      el("superAdminMutEmail").textContent     = "Belum diisi";
            if (el("superAdminMutCurrentRole")) el("superAdminMutCurrentRole").textContent = "Baru";
            if (el("superAdminMutAvatar")) {
                el("superAdminMutAvatar").src = "https://www.gravatar.com/avatar/00000000000000000000000000000000?d=identicon&s=120";
            }

            // Clear all fields
            var fieldsToClear = [
                "superAdminMutId", "superAdminMutStaffId", "superAdminMutJobTitle", "superAdminMutDepartment",
                "superAdminMutPassword", "superAdminMutFirstName", "superAdminMutLastName", "superAdminMutEmailHidden",
                "superAdminMutPhone", "superAdminMutKtpName", "superAdminMutKtpNik", "superAdminMutBirthDate",
                "superAdminMutGender", "superAdminMutMaritalStatus", "superAdminMutKtpAddress", "superAdminMutDomisiliAddress",
                "superAdminMutBankName", "superAdminMutBankAccountNumber", "superAdminMutNpwpNumber", "superAdminMutBpjsTk",
                "superAdminMutBpjsKes", "superAdminMutPlafonMedis", "superAdminMutPlafonTransport", "superAdminMutPlafonOperasional",
                "superAdminMutPlafonMakan", "superAdminMutUniformSize", "superAdminMutCardId", "superAdminMutQrId",
                "superAdminMutEmergencyName", "superAdminMutEmergencyRelation", "superAdminMutEmergencyPhone"
            ];
            fieldsToClear.forEach(function(fid) {
                if (el(fid)) el(fid).value = "";
            });

            if (el("superAdminMutRole"))       el("superAdminMutRole").value = "employee";
            if (el("superAdminMutLeaveQuota")) el("superAdminMutLeaveQuota").value = 12;
        }

        // Reset default active tab
        window.switchSuperAdminMutTab('kepegawaian');

        // Hide password strength box when opening
        var pwBox = document.getElementById("superAdminMutPwStrengthBox");
        if (pwBox) pwBox.classList.add("hidden");
        window.superAdminMutPasswordValid = true;

        var modal     = document.getElementById("superAdminMutationModal");
        var container = document.getElementById("superAdminMutationModalContainer");
        if (modal)     modal.classList.remove("opacity-0", "pointer-events-none");
        if (container) container.classList.remove("scale-95");
    };

    /**
     * MVC View UI Action: Closes the mutation/settings modal and hides it.
     * OOP Concept: DOM state mutation.
     * connects: #superAdminMutationModal.
     */
    window.closeSuperAdminMutationModal = function() {
        var modal     = document.getElementById("superAdminMutationModal");
        var container = document.getElementById("superAdminMutationModalContainer");
        if (modal)     modal.classList.add("opacity-0", "pointer-events-none");
        if (container) container.classList.add("scale-95");
    };

    /**
     * MVC Controller Call: Submits the user modification/mutation form data.
     * OOP Concept: Form data encoding and asynchronously sending to API endpoint.
     * connects: App\Controllers\EmployeeMasterController::save via /hrops/employees/save.
     * 
     * @param {Event} e
     */
    window.submitSuperAdminMutationForm = function(e) {
        e.preventDefault();

        if (typeof window.superAdminMutPasswordValid !== "undefined" && !window.superAdminMutPasswordValid) {
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "Kata Sandi Kurang Kuat!",
                    text: "Penuhi semua kriteria sandi kuat sebelum menyimpan.",
                    icon: "error",
                    confirmButtonColor: "#000666"
                });
            }
            return;
        }

        var formData = new FormData(e.target);

        fetch("/hrops/employees/save", { method: "POST", body: formData })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    window.closeSuperAdminMutationModal();
                    if (typeof Swal !== "undefined") {
                        Swal.fire({ title: "Berhasil", text: "Data pengguna berhasil diperbarui.", icon: "success", confirmButtonColor: "#000666" });
                    }
                    window.loadSuperAdminUsers();
                } else {
                    if (typeof Swal !== "undefined") Swal.fire("Error", data.message, "error");
                }
            })
            .catch(function(err) {
                if (typeof Swal !== "undefined") Swal.fire("Error", "Terjadi kesalahan sistem.", "error");
            });
    };

    // ─── Password Strength (for Mutation Modal) ─────────────────────────────────
    /**
     * MVC View UI Action: Sets initial validity state for mutation password.
     */
    window.superAdminMutPasswordValid = true;

    var passwordInput    = document.getElementById("superAdminMutPassword");
    var togglePasswordBtn = document.getElementById("superAdminToggleMutPassword");
    var pwStrengthBox    = document.getElementById("superAdminMutPwStrengthBox");
    var pwLen   = document.getElementById("superAdminMutPwLen");
    var pwUpper = document.getElementById("superAdminMutPwUpper");
    var pwLower = document.getElementById("superAdminMutPwLower");
    var pwNum   = document.getElementById("superAdminMutPwNum");
    var pwSpec  = document.getElementById("superAdminMutPwSpec");

    /**
     * MVC View UI Helper: Updates the visual checklist for a password strength criteria.
     * OOP Concept: Pure UI styling update helper.
     * 
     * @param {HTMLElement} elem
     * @param {boolean} met
     */
    function updateCrit(elem, met) {
        if (!elem) return;
        if (met) {
            elem.classList.remove("text-red-500");
            elem.classList.add("text-green-600");
            var ico = elem.querySelector(".material-symbols-outlined");
            if (ico) ico.textContent = "check_circle";
        } else {
            elem.classList.remove("text-green-600");
            elem.classList.add("text-red-500");
            var ico = elem.querySelector(".material-symbols-outlined");
            if (ico) ico.textContent = "cancel";
        }
    }

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener("click", function() {
            var type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            var ico = this.querySelector(".material-symbols-outlined");
            if (ico) ico.textContent = type === "password" ? "visibility" : "visibility_off";
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener("focus", function() {
            if (pwStrengthBox) pwStrengthBox.classList.remove("hidden");
        });

        passwordInput.addEventListener("input", function() {
            var val = this.value;
            if (val.length === 0) {
                if (pwStrengthBox) pwStrengthBox.classList.add("hidden");
                window.superAdminMutPasswordValid = true;
                return;
            }
            if (pwStrengthBox) pwStrengthBox.classList.remove("hidden");

            var hasLen   = val.length >= 8;
            var hasUpper = /[A-Z]/.test(val);
            var hasLower = /[a-z]/.test(val);
            var hasNum   = /[0-9]/.test(val);
            var hasSpec  = /[^A-Za-z0-9]/.test(val);

            updateCrit(pwLen,   hasLen);
            updateCrit(pwUpper, hasUpper);
            updateCrit(pwLower, hasLower);
            updateCrit(pwNum,   hasNum);
            updateCrit(pwSpec,  hasSpec);

            window.superAdminMutPasswordValid = hasLen && hasUpper && hasLower && hasNum && hasSpec;
        });
    }

    // ─── Search & Filter listeners ───────────────────────────────────────────────
    var searchEl     = document.getElementById("superAdminUserSearchInput");
    var roleFilterEl = document.getElementById("superAdminUserRoleFilter");
    if (searchEl)     searchEl.addEventListener("input", function() { currentSuperAdminUserPage = 1; window.renderSuperAdminUsersTable(); });
    if (roleFilterEl) roleFilterEl.addEventListener("change", function() { currentSuperAdminUserPage = 1; window.renderSuperAdminUsersTable(); });

    // ─── Kick off data load ──────────────────────────────────────────────────────
    // Use a slight delay so DOM is fully settled after SPA injection
    setTimeout(function() {
        window.loadSuperAdminUsers();
    }, 100);

})();
</script>