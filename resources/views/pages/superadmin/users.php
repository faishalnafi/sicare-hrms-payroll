<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Manajemen Pengguna &amp; Simulasi Login</h1>
            <p class="text-on-surface-variant font-medium text-sm font-body">Kelola hak akses sistem, posisi jabatan, mutasi departemen, dan lakukan simulasi login sebagai pengguna.</p>
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
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full relative">
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Cari Pengguna</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50">search</span>
                        <input type="text" id="superAdminUserSearchInput" placeholder="Cari nama, NIK, atau email..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
                <div class="w-full md:w-64">
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
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 pointer-events-none">expand_more</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Pengguna</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Email</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Struktur Organisasi (Posisi &amp; Divisi)</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Akses Sistem</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="superAdminUsersTableBody" class="divide-y divide-outline-variant/10 font-body">
                    <tr class="empty-row hidden">
                        <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl mb-2 opacity-50 block">manage_accounts</span>
                            <p class="font-bold">Data pengguna tidak ditemukan</p>
                        </td>
                    </tr>
                    <tr class="loading-row">
                        <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-3xl mb-2 opacity-50 block animate-spin">autorenew</span>
                            <p class="text-sm font-semibold">Memuat data...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="superAdminUserPagination" class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end bg-surface-container-low/30 hidden">
            <div class="flex items-center gap-2">
                <button id="superAdminUserPrevBtn" onclick="window.prevSuperAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                <button id="superAdminUserNextBtn" onclick="window.nextSuperAdminUserPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
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
                <span class="material-symbols-outlined text-primary">published_with_changes</span>
                Mutasi Jabatan &amp; Akses Sistem (Super Admin)
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

            <!-- Edit Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Employee ID (Staff ID) -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Employee ID (NIK Perusahaan)</label>
                    <input type="text" id="superAdminMutStaffId" name="employee_id" placeholder="Cth: EM-2026-001" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all uppercase">
                </div>

                <!-- Job Title -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Posisi / Jabatan (Job Title)</label>
                    <input type="text" id="superAdminMutJobTitle" name="job_title" placeholder="Cth: Frontend Lead" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>

                <!-- Department Selector -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Departemen (Divisi)</label>
                    <div class="relative">
                        <select id="superAdminMutDepartment" name="department_id" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <option value="">Tanpa Departemen</option>
                            <!-- Populated dynamically -->
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 pointer-events-none">expand_more</span>
                    </div>
                </div>

                <!-- System Role -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Role Sistem *</label>
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
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 pointer-events-none">expand_more</span>
                    </div>
                </div>

                <!-- Reset Password (optional for admin safety) -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Reset Sandi Pengguna <span class="text-[10px] font-normal lowercase text-on-surface-variant/75">(kosongkan jika tidak ingin mengubah sandi)</span></label>
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

            <!-- Hidden values from original record to avoid loss -->
            <input type="hidden" id="superAdminMutFirstName" name="first_name">
            <input type="hidden" id="superAdminMutLastName" name="last_name">
            <input type="hidden" id="superAdminMutEmailHidden" name="email">
            <input type="hidden" id="superAdminMutLeaveQuota" name="annual_leave_quota">
            <input type="hidden" id="superAdminMutBaseSalary" name="base_salary">
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
(function() {
    // ─── Module State ───────────────────────────────────────────────────────────
    var superAdminUsersData       = [];
    var superAdminDepsData        = [];
    var currentSuperAdminUserPage = 1;
    var itemsPerSuperAdminUserPage = 10;
    var currentLoggedInUserId     = <?= json_encode($_SESSION['user_id'] ?? '') ?>;

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
    window.loadSuperAdminUsers = function() {
        // Show loading state
        var tbody = document.getElementById('superAdminUsersTableBody');
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
                    superAdminUsersData = res.data || [];
                    superAdminDepsData  = res.departments || [];
                    window.populateSuperAdminDeptDropdown();
                    window.renderSuperAdminUsersTable();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', res.message || 'Gagal memuat data', 'error');
                    }
                }
            })
            .catch(function(e) {
                console.error('Super Admin users fetch error:', e);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal memuat data pengguna: ' + e.message, 'error');
                }
            });
    };

    // ─── Populate Department Dropdown ───────────────────────────────────────────
    window.populateSuperAdminDeptDropdown = function() {
        var depSelect = document.getElementById('superAdminMutDepartment');
        if (!depSelect) return;

        depSelect.innerHTML = '<option value="">Tanpa Departemen</option>';

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
    window.renderSuperAdminUsersTable = function() {
        var tbody    = document.getElementById('superAdminUsersTableBody');
        if (!tbody) return;

        var emptyRow   = tbody.querySelector('.empty-row');
        var loadingRow = tbody.querySelector('.loading-row');

        // Hide loading
        if (loadingRow) loadingRow.classList.add('hidden');

        // Remove old data rows
        var oldRows = tbody.querySelectorAll('tr.user-data-row');
        oldRows.forEach(function(r) { r.remove(); });

        var searchEl     = document.getElementById('superAdminUserSearchInput');
        var roleFilterEl = document.getElementById('superAdminUserRoleFilter');
        var search       = searchEl ? searchEl.value.toLowerCase() : '';
        var roleFilter   = roleFilterEl ? roleFilterEl.value : '';

        var filtered = superAdminUsersData.filter(function(user) {
            var textMatch =
                (user.first_name  || '').toLowerCase().includes(search) ||
                (user.last_name   || '').toLowerCase().includes(search) ||
                (user.email       || '').toLowerCase().includes(search) ||
                (user.employee_id || '').toLowerCase().includes(search);
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
                    : 'https://www.gravatar.com/avatar/' + md5Hash + '?d=404&s=120';
                var badgeClass = getRoleBadgeClass(user.role);

                // Simulasi Login (Impersonation) button, only show if target is not the current logged in user
                var impersonateButton = '';
                if (user.id !== currentLoggedInUserId) {
                    impersonateButton = 
                        '<button onclick="window.startSuperAdminImpersonate(\'' + escHtml(user.id) + '\', \'' + fullName + '\')" class="px-3 py-1.5 bg-amber-500/10 hover:bg-amber-500 text-amber-700 hover:text-white rounded-full text-xs font-bold border border-amber-500/20 shadow-sm transition-all flex items-center gap-1">' +
                            '<span class="material-symbols-outlined text-[14px]">login</span> Simulasi' +
                        '</button>';
                }

                tr.innerHTML =
                    '<td class="px-6 py-4">' +
                        '<div class="flex items-center gap-3">' +
                            '<img src="' + pp + '" class="w-10 h-10 rounded-full object-cover border border-outline-variant/30 flex-shrink-0" alt="Avatar" onerror="window.handleAvatarError(this, \'' + md5Hash + '\')">' +
                            '<div>' +
                                '<p class="text-sm font-bold text-on-surface">' + fullName + '</p>' +
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
                        '<div class="flex items-center justify-end gap-2">' +
                            impersonateButton +
                            '<button onclick="window.openSuperAdminMutationModal(\'' + escHtml(user.id) + '\')" class="px-3 py-1.5 bg-primary/5 hover:bg-primary text-primary hover:text-white rounded-full text-xs font-bold border border-primary/10 shadow-sm transition-all flex items-center gap-1">' +
                                '<span class="material-symbols-outlined text-[14px]">published_with_changes</span> Mutasi' +
                            '</button>' +
                        '</div>' +
                    '</td>';

                tbody.appendChild(tr);
            }
        }

        // Pagination visibility
        var pagination = document.getElementById('superAdminUserPagination');
        if (pagination) {
            if (totalItems === 0 || totalPages <= 1) {
                pagination.classList.add('hidden');
            } else {
                pagination.classList.remove('hidden');
            }
        }

        var prevBtn = document.getElementById('superAdminUserPrevBtn');
        var nextBtn = document.getElementById('superAdminUserNextBtn');
        if (prevBtn) prevBtn.disabled = currentSuperAdminUserPage === 1;
        if (nextBtn) nextBtn.disabled = currentSuperAdminUserPage === totalPages || totalPages === 0;
    };

    // ─── Pagination ─────────────────────────────────────────────────────────────
    window.prevSuperAdminUserPage = function() {
        if (currentSuperAdminUserPage > 1) { currentSuperAdminUserPage--; window.renderSuperAdminUsersTable(); }
    };
    window.nextSuperAdminUserPage = function() {
        currentSuperAdminUserPage++; window.renderSuperAdminUsersTable();
    };

    // ─── Impersonation (Simulasi Login) ─────────────────────────────────────────
    window.startSuperAdminImpersonate = function(id, name) {
        if (typeof Swal === 'undefined') return;

        Swal.fire({
            title: 'Simulasi Login?',
            text: 'Anda akan masuk ke dalam sistem sebagai "' + name + '". Akses dan navigasi Anda akan menyesuaikan dengan peran mereka.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d97706', // Amber-600
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Mulai Simulasi',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('user_id', id);

                fetch('/auth/impersonate', { method: 'POST', body: formData })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({
                                title: 'Memulai Simulasi...',
                                text: 'Mengalihkan ke dashboard pengguna.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            setTimeout(function() {
                                window.location.href = data.redirect || '/dashboard';
                            }, 1200);
                        } else {
                            Swal.fire('Error', data.message || 'Gagal memulai simulasi', 'error');
                        }
                    })
                    .catch(function(err) {
                        console.error('Impersonation error:', err);
                        Swal.fire('Error', 'Terjadi kesalahan sistem saat mencoba login.', 'error');
                    });
            }
        });
    };

    // ─── Mutation Modal ─────────────────────────────────────────────────────────
    window.openSuperAdminMutationModal = function(id) {
        var user = superAdminUsersData.find(function(u) { return u.id === id; });
        if (!user) return;

        var fullName = (user.first_name || '') + (user.last_name ? ' ' + user.last_name : '');
        var el = function(eid) { return document.getElementById(eid); };

        if (el('superAdminMutFullName'))   el('superAdminMutFullName').textContent  = fullName;
        if (el('superAdminMutEmail'))      el('superAdminMutEmail').textContent     = user.email;
        if (el('superAdminMutCurrentRole')) el('superAdminMutCurrentRole').textContent = user.role;
        if (el('superAdminMutAvatar')) {
            el('superAdminMutAvatar').src = user.profile_picture || 'https://www.gravatar.com/avatar/' + window.md5((user.email || '').trim().toLowerCase()) + '?d=404&s=120';
            el('superAdminMutAvatar').onerror = function() {
                window.handleAvatarError(this, window.md5((user.email || '').trim().toLowerCase()));
            };
        }

        if (el('superAdminMutId'))          el('superAdminMutId').value          = user.id          || '';
        if (el('superAdminMutStaffId'))     el('superAdminMutStaffId').value     = user.employee_id || '';
        if (el('superAdminMutJobTitle'))    el('superAdminMutJobTitle').value    = user.job_title   || '';
        if (el('superAdminMutDepartment'))  el('superAdminMutDepartment').value  = user.department_id || '';
        if (el('superAdminMutRole'))        el('superAdminMutRole').value        = user.role        || 'employee';
        if (el('superAdminMutPassword'))    el('superAdminMutPassword').value    = '';

        if (el('superAdminMutFirstName'))   el('superAdminMutFirstName').value   = user.first_name        || '';
        if (el('superAdminMutLastName'))    el('superAdminMutLastName').value    = user.last_name         || '';
        if (el('superAdminMutEmailHidden')) el('superAdminMutEmailHidden').value = user.email             || '';
        if (el('superAdminMutLeaveQuota'))  el('superAdminMutLeaveQuota').value  = user.annual_leave_quota || 12;
        if (el('superAdminMutBaseSalary'))  el('superAdminMutBaseSalary').value  = user.base_salary       || 0;

        // Hide password strength box when opening
        var pwBox = document.getElementById('superAdminMutPwStrengthBox');
        if (pwBox) pwBox.classList.add('hidden');
        window.superAdminMutPasswordValid = true;

        var modal     = document.getElementById('superAdminMutationModal');
        var container = document.getElementById('superAdminMutationModalContainer');
        if (modal)     modal.classList.remove('opacity-0', 'pointer-events-none');
        if (container) container.classList.remove('scale-95');
    };

    window.closeSuperAdminMutationModal = function() {
        var modal     = document.getElementById('superAdminMutationModal');
        var container = document.getElementById('superAdminMutationModalContainer');
        if (modal)     modal.classList.add('opacity-0', 'pointer-events-none');
        if (container) container.classList.add('scale-95');
    };

    window.submitSuperAdminMutationForm = function(e) {
        e.preventDefault();

        if (typeof window.superAdminMutPasswordValid !== 'undefined' && !window.superAdminMutPasswordValid) {
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
                    window.closeSuperAdminMutationModal();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ title: 'Berhasil', text: 'Mutasi jabatan berhasil diperbarui.', icon: 'success', confirmButtonColor: '#000666' });
                    }
                    window.loadSuperAdminUsers();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(function(err) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            });
    };

    // ─── Password Strength (for Mutation Modal) ─────────────────────────────────
    window.superAdminMutPasswordValid = true;

    var passwordInput    = document.getElementById('superAdminMutPassword');
    var togglePasswordBtn = document.getElementById('superAdminToggleMutPassword');
    var pwStrengthBox    = document.getElementById('superAdminMutPwStrengthBox');
    var pwLen   = document.getElementById('superAdminMutPwLen');
    var pwUpper = document.getElementById('superAdminMutPwUpper');
    var pwLower = document.getElementById('superAdminMutPwLower');
    var pwNum   = document.getElementById('superAdminMutPwNum');
    var pwSpec  = document.getElementById('superAdminMutPwSpec');

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
                window.superAdminMutPasswordValid = true;
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

            window.superAdminMutPasswordValid = hasLen && hasUpper && hasLower && hasNum && hasSpec;
        });
    }

    // ─── Search & Filter listeners ───────────────────────────────────────────────
    var searchEl     = document.getElementById('superAdminUserSearchInput');
    var roleFilterEl = document.getElementById('superAdminUserRoleFilter');
    if (searchEl)     searchEl.addEventListener('input', function() { currentSuperAdminUserPage = 1; window.renderSuperAdminUsersTable(); });
    if (roleFilterEl) roleFilterEl.addEventListener('change', function() { currentSuperAdminUserPage = 1; window.renderSuperAdminUsersTable(); });

    // ─── Kick off data load ──────────────────────────────────────────────────────
    // Use a slight delay so DOM is fully settled after SPA injection
    setTimeout(function() {
        window.loadSuperAdminUsers();
    }, 100);

})();
</script>