<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Manajemen Pengguna</h1>
            <p class="text-on-surface-variant font-medium text-sm font-body">Kelola hak akses sistem, posisi jabatan, mutasi departemen, status aktif, dan lakukan pembaruan data pengguna secara terpusat.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-primary/5 text-primary text-xs font-extrabold px-3.5 py-2 rounded-full border border-primary/10 flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[14px]">shield_person</span>
                Otoritas Administrator
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
                        <input type="text" id="adminUserSearchInput" placeholder="Cari nama, NIK, atau email..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
                <div class="flex flex-wrap md:flex-nowrap items-end gap-3 w-full lg:w-auto">
                    <!-- Filter Role -->
                    <div class="w-full md:w-56">
                        <label class="block text-xs font-bold text-on-surface-variant mb-2">Filter Role Sistem</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50">badge</span>
                            <select id="adminUserRoleFilter" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
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
                        <button id="adminAddUserBtn" onclick="window.openMutationModal()" class="w-full px-4 py-2.5 bg-primary hover:bg-primary/95 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98]">
                            <span class="material-symbols-outlined text-lg">person_add</span>
                            <span>Tambah Pengguna</span>
                        </button>
                    </div>
                    <!-- Trash Button -->
                    <div class="flex-shrink-0">
                        <button id="adminTrashBtn" onclick="window.toggleAdminTrashFilter()" class="p-2.5 bg-surface-container-low hover:bg-surface-container-high text-on-surface-variant rounded-xl border border-outline-variant/30 transition-all flex items-center justify-center hover:scale-[1.02] active:scale-[0.98] shadow-sm" title="Tampilkan Tempat Sampah (Trash)">
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
                        <th id="admColPengguna" onclick="window.toggleAdminUserSort('nama')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Pengguna</th>
                        <th id="admColEmail" onclick="window.toggleAdminUserSort('email')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Email</th>
                        <th id="admColStruktur" onclick="window.toggleAdminUserSort('struktur')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Struktur Organisasi (Posisi & Divisi)</th>
                        <th id="admColAkses" onclick="window.toggleAdminUserSort('akses')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Akses Sistem</th>
                        <th id="admColAksi" onclick="window.toggleAdminUserSort('aksi')" class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="adminUsersTableBody" class="divide-y divide-outline-variant/10 font-body">
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
        <div id="adminUserPagination" class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-between bg-surface-container-low/30 hidden">
            <!-- Left Info -->
            <div id="adminUserPaginationInfo" class="text-sm text-on-surface-variant font-medium">
                Menampilkan data 0 sampai 0 dari 0
            </div>
            <!-- Right Buttons -->
            <div class="flex items-center gap-1.5" id="adminUserPaginationBtns">
                <!-- First Page -->
                <button id="adminUserFirstBtn" onclick="window.firstAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Pertama">
                    <span class="material-symbols-outlined text-sm">first_page</span>
                </button>
                <!-- Prev Page -->
                <button id="adminUserPrevBtn" onclick="window.prevAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Sebelumnya">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                
                <!-- Page numbers container -->
                <div id="adminUserPageNumbers" class="flex items-center gap-1">
                    <!-- Dynamic Page Numbers -->
                </div>

                <!-- Next Page -->
                <button id="adminUserNextBtn" onclick="window.nextAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Berikutnya">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
                <!-- Last Page -->
                <button id="adminUserLastBtn" onclick="window.lastAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent" title="Halaman Terakhir">
                    <span class="material-symbols-outlined text-sm">last_page</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Mutation Modal -->
<div id="mutationModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="window.closeMutationModal()"></div>
    <div id="mutationModalContainer" class="bg-surface-container-lowest rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden scale-95 transition-transform duration-300 relative z-10 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-outline-variant/15 flex justify-between items-center bg-surface-container-low/30">
            <h3 class="font-headline text-xl font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">edit</span>
                Edit Data User Lengkap
            </h3>
            <button onclick="window.closeMutationModal()" class="text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high p-2 rounded-full transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Form Body -->
        <form id="mutationForm" onsubmit="window.submitMutationForm(event)" class="overflow-y-auto p-6 space-y-6 custom-scrollbar font-body">
            <input type="hidden" id="mutId" name="id">
            
            <!-- User Summary Details Card -->
            <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/20 flex items-center gap-4">
                <img id="mutAvatar" src="" class="w-14 h-14 rounded-full object-cover border border-outline-variant/30 bg-surface shadow-sm" alt="Avatar">
                <div class="space-y-1">
                    <h4 id="mutFullName" class="text-base font-extrabold text-on-surface font-headline leading-tight">User Fullname</h4>
                    <p id="mutEmail" class="text-xs font-semibold text-on-surface-variant">user@email.com</p>
                    <span id="mutCurrentRole" class="text-[9px] font-bold uppercase tracking-wider bg-primary/10 text-primary px-2 py-0.5 rounded">Role</span>
                </div>
            </div>

            <!-- Tab Headers -->
            <div class="flex border-b border-outline-variant/15 font-headline font-semibold text-xs gap-1 overflow-x-auto pb-1 custom-scrollbar">
                <button type="button" onclick="window.switchMutTab('kepegawaian')" id="tab-mut-kepegawaian" class="px-3 py-2 text-primary border-b-2 border-primary transition-all whitespace-nowrap">Kepegawaian</button>
                <button type="button" onclick="window.switchMutTab('profil')" id="tab-mut-profil" class="px-3 py-2 text-on-surface-variant hover:text-on-surface border-b-2 border-transparent transition-all whitespace-nowrap">Profil & Kependudukan</button>
                <button type="button" onclick="window.switchMutTab('finansial')" id="tab-mut-finansial" class="px-3 py-2 text-on-surface-variant hover:text-on-surface border-b-2 border-transparent transition-all whitespace-nowrap">Finansial</button>
                <button type="button" onclick="window.switchMutTab('lainnya')" id="tab-mut-lainnya" class="px-3 py-2 text-on-surface-variant hover:text-on-surface border-b-2 border-transparent transition-all whitespace-nowrap">Plafon & Fisik</button>
            </div>

            <!-- Tab 1: Kepegawaian & Akses -->
            <div id="content-mut-kepegawaian" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employee ID (Staff ID) -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Employee ID (NIK Perusahaan)</label>
                        <input type="text" id="mutStaffId" name="employee_id" placeholder="Cth: EM-2026-001" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all uppercase">
                    </div>
                    <!-- Job Title -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Posisi / Jabatan (Job Title)</label>
                        <input type="text" id="mutJobTitle" name="job_title" placeholder="Cth: Frontend Lead" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Department Selector -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Departemen (Divisi)</label>
                        <div class="relative">
                            <select id="mutDepartment" name="department_id" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <option value="">Tanpa Departemen</option>
                                <!-- Populated dynamically -->
                            </select>
                            
                        </div>
                    </div>
                    <!-- System Role -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Role Sistem *</label>
                        <div class="relative">
                            <select id="mutRole" name="role" required class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <option value="employee">Employee</option>
                                <option value="hr_ops">HR Ops</option>
                                <option value="hiring_manager">Hiring Manager</option>
                                <option value="recruiter">Recruiter</option>
                                <option value="executive">Executive</option>
                                <option value="admin">Admin</option>
                                <option value="candidate">Candidate</option>
                            </select>
                            
                        </div>
                    </div>
                    <!-- Kuota Cuti Tahunan -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Kuota Cuti Tahunan</label>
                        <input type="number" id="mutLeaveQuota" name="annual_leave_quota" placeholder="Cth: 12" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Gaji Pokok -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Gaji Pokok</label>
                        <input type="text" id="mutBaseSalary" name="base_salary" placeholder="Cth: Rp 10.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Reset Password (optional for admin safety) -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Reset Sandi Pengguna <span class="text-[10px] font-normal lowercase text-on-surface-variant/75">(kosongkan jika tidak ingin mengubah sandi)</span></label>
                        <div class="relative group">
                            <input type="password" id="mutPassword" name="password" placeholder="Masukkan sandi baru jika diperlukan..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-12 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                            <button type="button" id="toggleMutPassword" class="absolute right-3 top-3 text-on-surface-variant/40 hover:text-primary transition-colors flex items-center justify-center">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                        <!-- Strength Indicators -->
                        <div id="mut-pw-strength-box" class="hidden p-4 bg-surface-container-low rounded-xl border border-outline-variant/15 text-xs space-y-2 mt-2">
                            <p class="font-bold text-on-surface-variant mb-1.5">Kriteria Kata Sandi Kuat:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-medium">
                                <div id="mut-pw-len" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Minimal 8 Karakter</div>
                                <div id="mut-pw-upper" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kapital (A-Z)</div>
                                <div id="mut-pw-lower" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kecil (a-z)</div>
                                <div id="mut-pw-num" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Angka (0-9)</div>
                                <div id="mut-pw-spec" class="flex items-center gap-1.5 text-red-500 transition-colors"><span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Karakter Simbol (@$!%*?&amp;...)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Profil & Kependudukan -->
            <div id="content-mut-profil" class="space-y-6 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- First Name -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Depan *</label>
                        <input type="text" id="mutFirstName" name="first_name" required placeholder="Nama Depan" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- Last Name -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Belakang</label>
                        <input type="text" id="mutLastName" name="last_name" placeholder="Nama Belakang" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Email *</label>
                        <input type="email" id="mutEmailHidden" name="email" required placeholder="email@domain.com" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Phone Number -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor Telepon</label>
                        <input type="text" id="mutPhone" name="no_telepon" placeholder="Cth: 08123456789" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Nama Sesuai KTP -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Sesuai KTP</label>
                        <input type="text" id="mutKtpName" name="nama_sesuai_ktp" placeholder="Cth: JONATHAN DOE" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- NIK KTP -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">NIK KTP (16 Digit)</label>
                        <input type="text" id="mutKtpNik" name="ktp_nik" placeholder="Cth: 317101XXXXXXXXXX" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- Tanggal Lahir -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Tanggal Lahir</label>
                        <input type="date" id="mutBirthDate" name="tanggal_lahir" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- Gender -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Jenis Kelamin</label>
                        <div class="relative">
                            <select id="mutGender" name="jenis_kelamin" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
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
                            <select id="mutMaritalStatus" name="status_pernikahan" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
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
                        <textarea id="mutKtpAddress" name="alamat_ktp" rows="2" placeholder="Masukkan alamat lengkap sesuai KTP..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none"></textarea>
                    </div>
                    <!-- Alamat Domisili -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Alamat Domisili</label>
                        <textarea id="mutDomisiliAddress" name="alamat_domisili" rows="2" placeholder="Masukkan alamat domisili saat ini..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Finansial & BPJS -->
            <div id="content-mut-finansial" class="space-y-6 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Bank Name -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Bank</label>
                        <input type="text" id="mutBankName" name="bank_name" placeholder="Cth: BCA, Mandiri, BNI" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Bank Account Number -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor Rekening Bank</label>
                        <input type="text" id="mutBankAccountNumber" name="bank_account_number" placeholder="Cth: 5220391823" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- NPWP Number -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor NPWP</label>
                        <input type="text" id="mutNpwpNumber" name="npwp_number" placeholder="Cth: 09.254.223.1-015.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- BPJS TK -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor BPJS Ketenagakerjaan</label>
                        <input type="text" id="mutBpjsTk" name="bpjs_tk" placeholder="Cth: 19028391823" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- BPJS Kes -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nomor BPJS Kesehatan</label>
                        <input type="text" id="mutBpjsKes" name="bpjs_kes" placeholder="Cth: 00012938123" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                </div>
            </div>

            <!-- Tab 4: Plafon & Fisik -->
            <div id="content-mut-lainnya" class="space-y-6 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Plafon Medis -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Plafon Medis (Bulanan)</label>
                        <input type="text" id="mutPlafonMedis" name="reimburse_plafon_medis" placeholder="Cth: Rp 500.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Plafon Transport -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Plafon Transport (Bulanan)</label>
                        <input type="text" id="mutPlafonTransport" name="reimburse_plafon_transport" placeholder="Cth: Rp 1.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- Plafon Operasional -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Plafon Operasional (Bulanan)</label>
                        <input type="text" id="mutPlafonOperasional" name="reimburse_plafon_operasional" placeholder="Cth: Rp 2.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Plafon Makan -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Plafon Makan (Bulanan)</label>
                        <input type="text" id="mutPlafonMakan" name="reimburse_plafon_makan" placeholder="Cth: Rp 300.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body">
                    </div>
                    <!-- Uniform Size -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Ukuran Seragam</label>
                        <input type="text" id="mutUniformSize" name="uniform_size" placeholder="Cth: L, XL, XXL" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all uppercase animate-none font-body">
                    </div>
                    <!-- Access Card ID -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">ID Kartu Akses</label>
                        <input type="text" id="mutCardId" name="id_kartu" placeholder="Cth: AC-829381" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- QR Code ID -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">ID QR Code</label>
                        <input type="text" id="mutQrId" name="id_qrcode" placeholder="Cth: QR-839283" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <div></div>
                    <!-- Emergency Contact Header -->
                    <div class="md:col-span-2 border-t border-outline-variant/15 pt-4">
                        <h5 class="text-xs font-bold text-primary font-headline uppercase tracking-wider mb-2">Kontak Darurat</h5>
                    </div>
                    <!-- Emergency Name -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Nama Kontak Darurat</label>
                        <input type="text" id="mutEmergencyName" name="emergency_name" placeholder="Nama lengkap kontak..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Emergency Relation -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">Hubungan Kontak Darurat</label>
                        <input type="text" id="mutEmergencyRelation" name="emergency_relation" placeholder="Cth: Orang Tua, Suami, Istri" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <!-- Emergency Phone -->
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-2 font-headline">No. Telepon Darurat</label>
                        <input type="text" id="mutEmergencyPhone" name="emergency_phone" placeholder="No. telepon aktif..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-body font-body">
                    </div>
                </div>
            </div>
        </form>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-outline-variant/15 flex justify-end gap-3 bg-surface-container-lowest">
            <button type="button" onclick="window.closeMutationModal()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-full text-sm font-semibold transition-colors">
                Batal
            </button>
            <button type="submit" form="mutationForm" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-full text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">published_with_changes</span> Simpan Perubahan Mutasi
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    // ─── Module State ───────────────────────────────────────────────────────────
    var adminUsersData       = [];
    var adminDepsData        = [];
    var currentAdminUserPage = 1;
    var totalAdminUserPages = 1;
    var itemsPerAdminUserPage = 10;
    var currentLoggedInUserId     = <?= json_encode($_SESSION['user_id'] ?? '') ?>;
    var currentAdminSortCol  = 'nama';
    var currentAdminSortDir  = 'asc';

    window.adminShowSuspendedOnly = false;
    window.toggleAdminTrashFilter = function() {
        window.adminShowSuspendedOnly = !window.adminShowSuspendedOnly;
        var btn = document.getElementById("adminTrashBtn");
        var addBtn = document.getElementById("adminAddUserBtn");
        if (btn) {
            if (window.adminShowSuspendedOnly) {
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
            if (window.adminShowSuspendedOnly) {
                addBtn.className = "w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98]";
                addBtn.innerHTML = '<span class="material-symbols-outlined text-lg">delete_forever</span><span>Kosongkan Sampah</span>';
                addBtn.setAttribute("onclick", "window.emptyAdminTrash()");
            } else {
                addBtn.className = "w-full px-4 py-2.5 bg-primary hover:bg-primary/95 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-[0.98]";
                addBtn.innerHTML = '<span class="material-symbols-outlined text-lg">person_add</span><span>Tambah Pengguna</span>';
                addBtn.setAttribute("onclick", "window.openMutationModal()");
            }
        }
        currentAdminUserPage = 1;
        window.renderAdminUsersTable();
    };

    window.emptyAdminTrash = function() {
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
                                window.loadAdminUsers();
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

    function sortAdminUsersData() {
        adminUsersData.sort(function(a, b) {
            var valA = getAdminSortValue(a, currentAdminSortCol);
            var valB = getAdminSortValue(b, currentAdminSortCol);
            if (typeof valA === 'string') {
                return currentAdminSortDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
            } else {
                return currentAdminSortDir === 'asc' ? (valA - valB) : (valB - valA);
            }
        });
    }

    function getAdminSortValue(user, col) {
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

    window.toggleAdminUserSort = function(column) {
        if (currentAdminSortCol === column) {
            currentAdminSortDir = currentAdminSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            currentAdminSortCol = column;
            currentAdminSortDir = 'asc';
        }
        sortAdminUsersData();
        window.renderAdminUsersTable();
    };

    // ─── Helpers ────────────────────────────────────────────────────────────────
    function escHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, function(m) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
        });
    }

    function getRoleBadgeClass(role) {
        var map = {
            'superadmin': 'bg-red-100 text-red-700',
            'admin':      'bg-purple-100 text-purple-700',
            'executive':  'bg-amber-100 text-amber-700',
            'hr_ops':     'bg-blue-100 text-blue-700',
            'hiring_manager': 'bg-teal-100 text-teal-700',
            'recruiter':  'bg-indigo-100 text-indigo-700',
            'employee':   'bg-green-100 text-green-700',
            'candidate':  'bg-gray-100 text-gray-600'
        };
        return map[role] || 'bg-gray-100 text-gray-600';
    }

    // ─── Load Data ──────────────────────────────────────────────────────────────
    window.loadAdminUsers = function() {
        // Show loading state
        var tbody = document.getElementById('adminUsersTableBody');
        if (tbody) {
            var lr = tbody.querySelector('.loading-row');
            if (lr) lr.classList.remove('hidden');
            var er = tbody.querySelector('.empty-row');
            if (er) er.classList.add('hidden');
            // Remove old data rows
            var oldRows = tbody.querySelectorAll('tr.user-data-row');
            oldRows.forEach(function(r) { r.remove(); });
        }

        fetch('/hrops/employees/list')
            .then(function(response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function(res) {
                if (res.success) {
                    adminUsersData = res.data || [];
                    adminDepsData  = res.departments || [];
                    sortAdminUsersData();
                    window.populateAdminDeptDropdown();
                    window.renderAdminUsersTable();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', res.message || 'Gagal memuat data', 'error');
                    }
                }
            })
            .catch(function(e) {
                console.error('Admin users fetch error:', e);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal memuat data pengguna: ' + e.message, 'error');
                }
            });
    };

    // ─── Populate Department Dropdown ───────────────────────────────────────────
    window.populateAdminDeptDropdown = function() {
        var depSelect = document.getElementById('mutDepartment');
        if (!depSelect) return;

        depSelect.innerHTML = '<option value="">Tanpa Departemen</option>';

        var map   = {};
        var roots = [];

        adminDepsData.forEach(function(dep) {
            map[dep.id] = Object.assign({}, dep, { children: [] });
        });
        adminDepsData.forEach(function(dep) {
            var mapped = map[dep.id];
            if (dep.parent_id && map[dep.parent_id]) {
                map[dep.parent_id].children.push(mapped);
            } else {
                roots.push(mapped);
            }
        });

        function addOptions(nodes, prefix) {
            prefix = prefix || '';
            nodes.forEach(function(node, index) {
                var num = prefix ? (prefix + '.' + (index + 1)) : String(index + 1);
                var opt = document.createElement('option');
                opt.value = node.id;
                var indent = '';
                for (var i = 1; i < node.level; i++) indent += '\u00a0\u00a0';
                opt.textContent = indent + num + '. ' + node.name;
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
    window.renderAdminUsersTable = function() {
        var tbody    = document.getElementById('adminUsersTableBody');
        if (!tbody) return;

        // Update header classes for sorting visual representation
        var cols = {
            'nama': 'admColPengguna',
            'email': 'admColEmail',
            'struktur': 'admColStruktur',
            'akses': 'admColAkses',
            'aksi': 'admColAksi'
        };
        Object.keys(cols).forEach(function(col) {
            var el = document.getElementById(cols[col]);
            if (el) {
                el.classList.remove('sort-active-asc', 'sort-active-desc');
                if (col === currentAdminSortCol) {
                    el.classList.add(currentAdminSortDir === 'asc' ? 'sort-active-asc' : 'sort-active-desc');
                }
            }
        });

        var emptyRow   = tbody.querySelector('.empty-row');
        var loadingRow = tbody.querySelector('.loading-row');

        // Hide loading
        if (loadingRow) loadingRow.classList.add('hidden');

        // Remove old data rows
        var oldRows = tbody.querySelectorAll('tr.user-data-row');
        oldRows.forEach(function(r) { r.remove(); });

        var searchEl     = document.getElementById('adminUserSearchInput');
        var roleFilterEl = document.getElementById('adminUserRoleFilter');
        var search       = searchEl ? searchEl.value.toLowerCase() : '';
        var roleFilter   = roleFilterEl ? roleFilterEl.value : '';

        var filtered = adminUsersData.filter(function(user) {
            // Filter by trash status
            if (window.adminShowSuspendedOnly) {
                if (parseInt(user.is_deleted || 0) !== 1) return false;
            } else {
                if (parseInt(user.is_deleted || 0) === 1) return false;
            }

            var textMatch =
                (user.first_name  || '').toLowerCase().includes(search) ||
                (user.last_name   || '').toLowerCase().includes(search) ||
                (user.email       || '').toLowerCase().includes(search) ||
                (user.employee_id || '').toLowerCase().includes(search);
            var roleMatch = !roleFilter || user.role === roleFilter;
            return textMatch && roleMatch;
        });

        var totalItems = filtered.length;
        var totalPages = Math.ceil(totalItems / itemsPerAdminUserPage) || 1;
        if (currentAdminUserPage > totalPages) currentAdminUserPage = totalPages;
        if (currentAdminUserPage < 1) currentAdminUserPage = 1;

        var startIdx = (currentAdminUserPage - 1) * itemsPerAdminUserPage;
        var endIdx   = Math.min(startIdx + itemsPerAdminUserPage, totalItems);

        if (totalItems === 0) {
            if (emptyRow) emptyRow.classList.remove('hidden');
        } else {
            if (emptyRow) emptyRow.classList.add('hidden');

            for (var i = startIdx; i < endIdx; i++) {
                var user = filtered[i];
                var tr = document.createElement('tr');
                tr.className = 'user-data-row hover:bg-surface-container-low/30 transition-colors group';

                var fullName = escHtml(user.first_name + (user.last_name ? ' ' + user.last_name : ''));
                var empId    = user.employee_id
                    ? escHtml(user.employee_id)
                    : '<span class="text-[9px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded font-bold uppercase">BELUM ADA NIK</span>';
                var md5Hash = window.md5((user.email || '').trim().toLowerCase());
                var pp = user.profile_picture
                    ? escHtml(user.profile_picture)
                    : 'https://www.gravatar.com/avatar/' + md5Hash + '?d=identicon&s=120';
                var badgeClass = getRoleBadgeClass(user.role);

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
                        "<button onclick=\"window.triggerAdminResetLockout(this)\" data-id=\"" + escHtml(user.id) + "\" data-name=\"" + fullName + "\" class=\"px-3 py-1.5 rounded-full text-xs font-bold border shadow-sm transition-all flex items-center gap-1 " + btnColor + "\" title=\"" + btnTitle + "\">" +
                            "<span class=\"material-symbols-outlined text-[14px]\">lock_open</span> " + btnText +
                        "</button>";
                }

                // Edit Button
                var editButton = "";
                if (parseInt(user.is_deleted || 0) !== 1) {
                    editButton = 
                        '<button onclick="window.openMutationModal(\'' + escHtml(user.id) + '\')" class="px-3 py-1.5 bg-primary/5 hover:bg-primary text-primary hover:text-white rounded-full text-xs font-bold border border-primary/10 shadow-sm transition-all flex items-center gap-1" title="Edit data user lengkap">' +
                            '<span class="material-symbols-outlined text-[14px]">edit</span> Edit' +
                        '</button>';
                }

                // Suspend/Aktifkan / Restore Button
                var suspendButton = '';
                if (user.id !== currentLoggedInUserId) {
                    if (parseInt(user.is_deleted || 0) === 1) {
                        suspendButton = 
                            '<button onclick="window.triggerAdminRestore(this)" data-id="' + escHtml(user.id) + '" data-name="' + fullName + '" class="px-3 py-1.5 bg-green-500/10 hover:bg-green-500 text-green-700 hover:text-white rounded-full text-xs font-bold border border-green-500/20 shadow-sm transition-all flex items-center gap-1" title="Pulihkan akun">' +
                                '<span class="material-symbols-outlined text-[14px]">restore</span> Pulihkan' +
                            '</button>';
                    } else {
                        if (parseInt(user.is_suspended) === 1) {
                            suspendButton = 
                                '<button onclick="window.triggerAdminToggleSuspend(this)" data-id="' + escHtml(user.id) + '" data-name="' + fullName + '" class="px-3 py-1.5 bg-green-500/10 hover:bg-green-500 text-green-700 hover:text-white rounded-full text-xs font-bold border border-green-500/20 shadow-sm transition-all flex items-center gap-1" title="Aktifkan akun">' +
                                    '<span class="material-symbols-outlined text-[14px]">check_circle</span> Aktifkan' +
                                '</button>';
                        } else {
                            suspendButton = 
                                '<button onclick="window.triggerAdminToggleSuspend(this)" data-id="' + escHtml(user.id) + '" data-name="' + fullName + '" class="px-3 py-1.5 bg-slate-500/10 hover:bg-slate-500 text-slate-700 hover:text-white rounded-full text-xs font-bold border border-slate-500/20 shadow-sm transition-all flex items-center gap-1" title="Tangguhkan (suspend) akun">' +
                                    '<span class="material-symbols-outlined text-[14px]">block</span> Suspend' +
                                '</button>';
                        }
                    }
                }

                // Hapus/Delete / Hapus Permanen Button
                var deleteButton = '';
                if (user.id !== currentLoggedInUserId && user.role !== 'superadmin') {
                    if (parseInt(user.is_deleted || 0) === 1) {
                        deleteButton = 
                            '<button onclick="window.triggerAdminDeletePermanent(this)" data-id="' + escHtml(user.id) + '" data-name="' + fullName + '" class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500 text-red-700 hover:text-white rounded-full text-xs font-bold border border-red-500/20 shadow-sm transition-all flex items-center gap-1" title="Hapus akun secara permanen">' +
                                '<span class="material-symbols-outlined text-[14px]">delete_forever</span> Hapus Permanen' +
                            '</button>';
                    } else {
                        deleteButton = 
                            '<button onclick="window.triggerAdminDelete(this)" data-id="' + escHtml(user.id) + '" data-name="' + fullName + '" class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500 text-red-700 hover:text-white rounded-full text-xs font-bold border border-red-500/20 shadow-sm transition-all flex items-center gap-1" title="Hapus akun">' +
                                '<span class="material-symbols-outlined text-[14px]">delete</span> Hapus' +
                            '</button>';
                    }
                }

                var rowNum = i + 1;
                tr.innerHTML =
                    '<td class="no-col-cell px-6 py-4 text-center font-bold text-xs text-on-surface-variant">' + rowNum + '</td>' +
                    '<td class="px-6 py-4">' +
                        '<div class="flex items-center gap-3">' +
                            '<img referrerpolicy="no-referrer" src="' + pp + '" class="w-10 h-10 rounded-full object-cover border border-outline-variant/30 flex-shrink-0" alt="Avatar" onerror="window.handleAvatarError(this, \'' + md5Hash + '\')">' +
                            '<div>' +
                                '<p class="text-sm font-bold text-on-surface">' + fullName + 
                                    (parseInt(user.is_deleted || 0) === 1 ? ' <span class="text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded border border-red-200 ml-1.5">TERHAPUS (TRASH)</span>' : (parseInt(user.is_suspended) === 1 ? ' <span class="text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded border border-red-200 ml-1.5">DITANGGUHKAN</span>' : '')) + 
                                '</p>' +
                                '<p class="text-xs text-on-surface-variant font-mono mt-0.5">' + empId + '</p>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td class="px-6 py-4">' +
                        '<span class="text-sm font-semibold text-on-surface">' + escHtml(user.email) + '</span>' +
                    '</td>' +
                    '<td class="px-6 py-4">' +
                        '<div class="flex flex-col">' +
                            '<span class="text-sm font-bold text-on-surface capitalize">' + escHtml(user.job_title || '—') + '</span>' +
                            '<span class="text-xs text-on-surface-variant mt-0.5">' + escHtml(user.department_name || 'Tanpa Departemen') + '</span>' +
                        '</div>' +
                    '</td>' +
                    '<td class="px-6 py-4">' +
                        '<span class="text-[10px] font-bold uppercase inline-block px-2.5 py-1 rounded ' + badgeClass + '">' + escHtml(user.role) + '</span>' +
                    '</td>' +
                    '<td class="px-6 py-4 text-right">' +
                        '<div class="flex items-center justify-end gap-1.5 flex-nowrap">' +
                            resetLockoutButton +
                            editButton +
                            suspendButton +
                            deleteButton +
                        '</div>' +
                    '</td>';

                tbody.appendChild(tr);
            }
        }

        // Save totalPages globally
        totalAdminUserPages = totalPages;

        // Pagination visibility
        var pagination = document.getElementById("adminUserPagination");
        if (pagination) {
            if (totalItems === 0 || totalPages <= 1) {
                pagination.classList.add("hidden");
            } else {
                pagination.classList.remove("hidden");
            }
        }

        // Update Info Text (showing X to Y of Z)
        var infoEl = document.getElementById("adminUserPaginationInfo");
        if (infoEl) {
            var startShow = totalItems === 0 ? 0 : startIdx + 1;
            var endShow = endIdx;
            infoEl.textContent = "Menampilkan data " + startShow + " sampai " + endShow + " dari " + totalItems;
        }

        // Update Buttons Disabled States
        var firstBtn = document.getElementById("adminUserFirstBtn");
        var prevBtn = document.getElementById("adminUserPrevBtn");
        var nextBtn = document.getElementById("adminUserNextBtn");
        var lastBtn = document.getElementById("adminUserLastBtn");

        if (firstBtn) firstBtn.disabled = currentAdminUserPage === 1;
        if (prevBtn) prevBtn.disabled = currentAdminUserPage === 1;
        if (nextBtn) nextBtn.disabled = currentAdminUserPage === totalPages || totalPages === 0;
        if (lastBtn) lastBtn.disabled = currentAdminUserPage === totalPages || totalPages === 0;

        // Render Page Numbers
        var pageNumbersContainer = document.getElementById("adminUserPageNumbers");
        if (pageNumbersContainer) {
            pageNumbersContainer.innerHTML = "";
            
            var startPage = currentAdminUserPage - 1;
            var endPage = currentAdminUserPage + 1;
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
                    if (pageNum === currentAdminUserPage) {
                        btn.className += "bg-primary text-white border-primary shadow-sm";
                    } else {
                        btn.className += "hover:bg-surface-container-high text-on-surface border-transparent";
                    }
                    btn.textContent = pageNum;
                    btn.onclick = function() {
                        currentAdminUserPage = pageNum;
                        window.renderAdminUsersTable();
                    };
                    pageNumbersContainer.appendChild(btn);
                })(p);
            }
        }
    };

    /**
     * MVC View UI Actions: Pagination Navigation helpers.
     */
    window.firstAdminUserPage = function() {
        if (currentAdminUserPage > 1) {
            currentAdminUserPage = 1;
            window.renderAdminUsersTable();
        }
    };

    window.prevAdminUserPage = function() {
        if (currentAdminUserPage > 1) {
            currentAdminUserPage--;
            window.renderAdminUsersTable();
        }
    };

    window.nextAdminUserPage = function() {
        if (currentAdminUserPage < totalAdminUserPages) {
            currentAdminUserPage++;
            window.renderAdminUsersTable();
        }
    };

    window.lastAdminUserPage = function() {
        if (currentAdminUserPage < totalAdminUserPages) {
            currentAdminUserPage = totalAdminUserPages;
            window.renderAdminUsersTable();
        }
    };

    // ─── Reset Lockout / Blokir ──────────────────────────────────────────────────
    /**
     * Resets failed login attempts and lockouts for a user.
     * connects: App\Controllers\EmployeeMasterController::resetLockout via /admin/users/reset-lockout.
     * 
     * @param {string|HTMLElement} userIdOrEl
     * @param {string} fullName
     */
    window.triggerAdminResetLockout = function(userIdOrEl, fullName) {
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

                fetch("/admin/users/reset-lockout", { method: "POST", body: formData })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ title: "Berhasil!", text: data.message, icon: "success", timer: 1500, showConfirmButton: false });
                            window.loadAdminUsers();
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
    window.triggerAdminToggleSuspend = function(btn) {
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
                            window.loadAdminUsers();
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
    window.triggerAdminDelete = function(btn) {
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
                            window.loadAdminUsers();
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

    window.triggerAdminRestore = function(btn) {
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
                            window.loadAdminUsers();
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

    window.triggerAdminDeletePermanent = function(btn) {
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
                            window.loadAdminUsers();
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
     * Tab switching handler for Admin mutation modal.
     */
    window.switchMutTab = function(tabName) {
        var tabs = ['kepegawaian', 'profil', 'finansial', 'lainnya'];
        tabs.forEach(function(t) {
            var btn = document.getElementById('tab-mut-' + t);
            var content = document.getElementById('content-mut-' + t);
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

    window.openMutationModal = function(id) {
        var el = function(eid) { return document.getElementById(eid); };
        var modalTitle = document.querySelector("#mutationModal h3");

        if (id) {
            // EDIT USER MODE
            var user = adminUsersData.find(function(u) { return u.id === id; });
            if (!user) return;

            if (modalTitle) {
                modalTitle.innerHTML = '<span class="material-symbols-outlined text-primary">edit</span> Edit Data User Lengkap (Admin)';
            }

            var fullName = (user.first_name || '') + (user.last_name ? ' ' + user.last_name : '');
            if (el('mutFullName'))   el('mutFullName').textContent  = fullName;
            if (el('mutEmail'))      el('mutEmail').textContent     = user.email;
            if (el('mutCurrentRole')) el('mutCurrentRole').textContent = user.role;
            if (el('mutAvatar')) {
                el('mutAvatar').src = user.profile_picture || 'https://www.gravatar.com/avatar/' + window.md5((user.email || '').trim().toLowerCase()) + '?d=identicon&s=120';
                el('mutAvatar').onerror = function() {
                    window.handleAvatarError(this, window.md5((user.email || '').trim().toLowerCase()));
                };
            }

            if (el('mutId'))          el('mutId').value          = user.id          || '';
            if (el('mutStaffId'))     el('mutStaffId').value     = user.employee_id || '';
            if (el('mutJobTitle'))    el('mutJobTitle').value    = user.job_title   || '';
            if (el('mutDepartment'))  el('mutDepartment').value  = user.department_id || '';
            if (el('mutRole'))        el('mutRole').value        = user.role        || 'employee';
            if (el('mutPassword'))    el('mutPassword').value    = '';

            if (el('mutFirstName'))   el('mutFirstName').value   = user.first_name        || '';
            if (el('mutLastName'))    el('mutLastName').value    = user.last_name         || '';
            if (el('mutEmailHidden')) el('mutEmailHidden').value = user.email             || '';
            if (el('mutLeaveQuota'))  el('mutLeaveQuota').value  = user.annual_leave_quota || 12;
            if (el('mutBaseSalary'))  el('mutBaseSalary').value  = formatRupiah(user.base_salary);

            // Populate new personal profile inputs
            if (el("mutPhone"))            el("mutPhone").value            = user.no_telepon || "";
            if (el("mutKtpName"))          el("mutKtpName").value          = user.nama_sesuai_ktp || "";
            if (el("mutKtpNik"))           el("mutKtpNik").value           = user.ktp_nik || "";
            if (el("mutBirthDate"))        el("mutBirthDate").value        = user.tanggal_lahir || "";
            if (el("mutGender"))           el("mutGender").value           = user.jenis_kelamin || "";
            if (el("mutMaritalStatus"))    el("mutMaritalStatus").value    = user.status_pernikahan || "";
            if (el("mutKtpAddress"))       el("mutKtpAddress").value       = user.alamat_ktp || "";
            if (el("mutDomisiliAddress"))  el("mutDomisiliAddress").value  = user.alamat_domisili || "";
            if (el("mutBankName"))         el("mutBankName").value         = user.bank_name || "";
            if (el("mutBankAccountNumber"))el("mutBankAccountNumber").value = user.bank_account_number || "";
            if (el("mutNpwpNumber"))       el("mutNpwpNumber").value       = user.npwp_number || "";
            if (el("mutBpjsTk"))           el("mutBpjsTk").value           = user.bpjs_tk || "";
            if (el("mutBpjsKes"))          el("mutBpjsKes").value           = user.bpjs_kes || "";
            
            if (el("mutPlafonMedis"))      el("mutPlafonMedis").value      = formatRupiah(user.reimburse_plafon_medis);
            if (el("mutPlafonTransport"))  el("mutPlafonTransport").value  = formatRupiah(user.reimburse_plafon_transport);
            if (el("mutPlafonOperasional"))el("mutPlafonOperasional").value= formatRupiah(user.reimburse_plafon_operasional);
            if (el("mutPlafonMakan"))      el("mutPlafonMakan").value      = formatRupiah(user.reimburse_plafon_makan);

            if (el("mutUniformSize"))      el("mutUniformSize").value      = user.uniform_size || "";
            if (el("mutCardId"))           el("mutCardId").value           = user.id_kartu || "";
            if (el("mutQrId"))             el("mutQrId").value             = user.id_qrcode || "";
            if (el("mutEmergencyName"))    el("mutEmergencyName").value    = user.emergency_name || "";
            if (el("mutEmergencyRelation"))el("mutEmergencyRelation").value= user.emergency_relation || "";
            if (el("mutEmergencyPhone"))   el("mutEmergencyPhone").value   = user.emergency_phone || "";
        } else {
            // ADD NEW USER MODE
            if (modalTitle) {
                modalTitle.innerHTML = '<span class="material-symbols-outlined text-primary">person_add</span> Tambah Pengguna Baru';
            }

            if (el('mutFullName'))   el('mutFullName').textContent  = "Pengguna Baru";
            if (el('mutEmail'))      el('mutEmail').textContent     = "Belum diisi";
            if (el('mutCurrentRole')) el('mutCurrentRole').textContent = "Baru";
            if (el('mutAvatar')) {
                el('mutAvatar').src = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=identicon&s=120';
            }

            // Clear all fields
            var fieldsToClear = [
                "mutId", "mutStaffId", "mutJobTitle", "mutDepartment",
                "mutPassword", "mutFirstName", "mutLastName", "mutEmailHidden",
                "mutPhone", "mutKtpName", "mutKtpNik", "mutBirthDate",
                "mutGender", "mutMaritalStatus", "mutKtpAddress", "mutDomisiliAddress",
                "mutBankName", "mutBankAccountNumber", "mutNpwpNumber", "mutBpjsTk",
                "mutBpjsKes", "mutPlafonMedis", "mutPlafonTransport", "mutPlafonOperasional",
                "mutPlafonMakan", "mutUniformSize", "mutCardId", "mutQrId",
                "mutEmergencyName", "mutEmergencyRelation", "mutEmergencyPhone"
            ];
            fieldsToClear.forEach(function(fid) {
                if (el(fid)) el(fid).value = "";
            });

            if (el("mutRole"))        el("mutRole").value        = "employee";
            if (el("mutLeaveQuota"))  el("mutLeaveQuota").value  = 12;
        }

        // Reset default active tab
        window.switchMutTab('kepegawaian');

        // Hide password strength box when opening
        var pwBox = document.getElementById('mut-pw-strength-box');
        if (pwBox) pwBox.classList.add('hidden');
        window.mutPasswordValid = true;

        var modal     = document.getElementById('mutationModal');
        var container = document.getElementById('mutationModalContainer');
        if (modal)     modal.classList.remove('opacity-0', 'pointer-events-none');
        if (container) container.classList.remove('scale-95');
    };

    window.closeMutationModal = function() {
        var modal     = document.getElementById('mutationModal');
        var container = document.getElementById('mutationModalContainer');
        if (modal)     modal.classList.add('opacity-0', 'pointer-events-none');
        if (container) container.classList.add('scale-95');
    };

    window.submitMutationForm = function(e) {
        e.preventDefault();

        if (typeof window.mutPasswordValid !== 'undefined' && !window.mutPasswordValid) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Kata Sandi Kurang Kuat!',
                    text: 'Penuhi semua kriteria sandi kuat sebelum menyimpan.',
                    icon: 'error',
                    confirmButtonColor: '#000666'
                });
            }
            return;
        }

        var formData = new FormData(e.target);

        fetch('/hrops/employees/save', { method: 'POST', body: formData })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    window.closeMutationModal();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ title: 'Berhasil', text: 'Data pengguna berhasil diperbarui.', icon: 'success', confirmButtonColor: '#000666' });
                    }
                    window.loadAdminUsers();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(function(err) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            });
    };

    // ─── Password Strength (for Mutation Modal) ─────────────────────────────────
    window.mutPasswordValid = true;

    var passwordInput    = document.getElementById('mutPassword');
    var togglePasswordBtn = document.getElementById('toggleMutPassword');
    var pwStrengthBox    = document.getElementById('mut-pw-strength-box');
    var pwLen   = document.getElementById('mut-pw-len');
    var pwUpper = document.getElementById('mut-pw-upper');
    var pwLower = document.getElementById('mut-pw-lower');
    var pwNum   = document.getElementById('mut-pw-num');
    var pwSpec  = document.getElementById('mut-pw-spec');

    function updateCrit(elem, met) {
        if (!elem) return;
        if (met) {
            elem.classList.remove('text-red-500');
            elem.classList.add('text-green-600');
            var ico = elem.querySelector('.material-symbols-outlined');
            if (ico) ico.textContent = 'check_circle';
        } else {
            elem.classList.remove('text-green-600');
            elem.classList.add('text-red-500');
            var ico = elem.querySelector('.material-symbols-outlined');
            if (ico) ico.textContent = 'cancel';
        }
    }

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function() {
            var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            var ico = this.querySelector('.material-symbols-outlined');
            if (ico) ico.textContent = type === 'password' ? 'visibility' : 'visibility_off';
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('focus', function() {
            if (pwStrengthBox) pwStrengthBox.classList.remove('hidden');
        });

        passwordInput.addEventListener('input', function() {
            var val = this.value;
            if (val.length === 0) {
                if (pwStrengthBox) pwStrengthBox.classList.add('hidden');
                window.mutPasswordValid = true;
                return;
            }
            if (pwStrengthBox) pwStrengthBox.classList.remove('hidden');

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

            window.mutPasswordValid = hasLen && hasUpper && hasLower && hasNum && hasSpec;
        });
    }

    // ─── Search & Filter listeners ───────────────────────────────────────────────
    var searchEl     = document.getElementById('adminUserSearchInput');
    var roleFilterEl = document.getElementById('adminUserRoleFilter');
    if (searchEl)     searchEl.addEventListener('input', function() { currentAdminUserPage = 1; window.renderAdminUsersTable(); });
    if (roleFilterEl) roleFilterEl.addEventListener('change', function() { currentAdminUserPage = 1; window.renderAdminUsersTable(); });

    // ─── Kick off data load ──────────────────────────────────────────────────────
    // Use a slight delay so DOM is fully settled after SPA injection
    setTimeout(function() {
        window.loadAdminUsers();
    }, 100);

})();
</script>
