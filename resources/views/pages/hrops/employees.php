<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Master Data Karyawan</h1>
            <p class="text-on-surface-variant font-medium text-sm">Kelola informasi staf, hak akses, dan detail pekerjaan.</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <button onclick="stubImportExport('Import Excel')" class="flex items-center gap-2 px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-full text-sm font-semibold transition-colors border border-outline-variant/30">
                <span class="material-symbols-outlined text-lg">upload</span> Import
            </button>
            <button onclick="stubImportExport('Export Excel')" class="flex items-center gap-2 px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-full text-sm font-semibold transition-colors border border-outline-variant/30">
                <span class="material-symbols-outlined text-lg">download</span> Export
            </button>
            <button onclick="openEmployeeModal()" class="flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-full text-sm font-semibold shadow-sm transition-colors w-full md:w-auto justify-center">
                <span class="material-symbols-outlined text-lg">person_add</span> Tambah Karyawan
            </button>
        </div>
    </div>

    <!-- Filters and Table Container -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 overflow-hidden shadow-sm flex flex-col">
        
        <!-- Filters Header -->
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest">
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full relative">
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Cari Karyawan</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50">search</span>
                        <input type="text" id="employeeSearchInput" placeholder="Nama, ID, atau Email..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
                <div class="w-full md:w-64">
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Filter Role</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50">badge</span>
                        <select id="employeeRoleFilter" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
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

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Profil</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Email & Kontak</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Posisi & Role</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Gaji Pokok</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider">Sisa Cuti</th>
                        <th class="py-4 px-6 text-[11px] font-bold uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="employeesTableBody" class="divide-y divide-outline-variant/10">
                    <tr class="empty-row hidden">
                        <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl mb-2 opacity-50">badge</span>
                            <p class="font-medium">Data karyawan tidak ditemukan</p>
                        </td>
                    </tr>
                    <!-- Data populated by JS -->
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div id="employeePagination" class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-end bg-surface-container-low/30 hidden">
            <div class="flex items-center gap-2">
                <button id="employeePrevBtn" onclick="prevEmployeePage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                <button id="employeeNextBtn" onclick="nextEmployeePage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Employee Modal -->
<div id="employeeModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeEmployeeModal()"></div>
    <div id="employeeModalContainer" class="bg-surface-container-lowest rounded-2xl w-full max-w-3xl shadow-2xl overflow-hidden scale-95 transition-transform duration-300 relative z-10 flex flex-col max-h-[90vh]">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-outline-variant/15 flex justify-between items-center bg-surface-container-low/30">
            <h3 class="font-headline text-xl font-bold text-on-surface" id="employeeModalTitle">Tambah Karyawan</h3>
            <button onclick="closeEmployeeModal()" class="text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high p-2 rounded-full transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Form Body -->
        <form id="employeeForm" onsubmit="submitEmployeeForm(event)" class="overflow-y-auto p-6 space-y-6 custom-scrollbar">
            <input type="hidden" id="employeeDbId" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Nama Depan *</label>
                    <input type="text" id="employeeFirstName" name="first_name" required class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Nama Belakang</label>
                    <input type="text" id="employeeLastName" name="last_name" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Employee ID (Staff ID)</label>
                    <input type="text" id="employeeStaffId" name="employee_id" placeholder="Cth: EM-2026-001" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Email *</label>
                    <input type="email" id="employeeEmail" name="email" required class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Posisi / Jabatan (Job Title)</label>
                    <input type="text" id="employeeJobTitle" name="job_title" placeholder="Cth: Software Engineer" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Departemen</label>
                    <div class="relative">
                        <select id="employeeDepartment" name="department_id" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <option value="">Tanpa Departemen</option>
                            <!-- Populated dynamically -->
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 pointer-events-none">expand_more</span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Gaji Pokok (Base Salary)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-sm font-semibold">Rp</span>
                        <input type="text" id="employeeBaseSalary" name="base_salary" value="0" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Sisa Cuti Tahunan *</label>
                    <input type="number" id="employeeLeaveQuota" name="annual_leave_quota" required value="12" min="0" max="30" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Role Sistem *</label>
                    <div class="relative">
                        <select id="employeeRole" name="role" required class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <option value="employee">Employee</option>
                            <option value="hr_ops">HR Ops</option>
                            <option value="hiring_manager">Hiring Manager</option>
                            <option value="recruiter">Recruiter</option>
                            <option value="executive">Executive</option>
                            <option value="admin">Admin</option>
                            <option value="candidate">Candidate</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 pointer-events-none">expand_more</span>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-on-surface-variant mb-2">Set Kata Sandi <span id="passwordHelper" class="text-[10px] font-normal lowercase">(kosongkan jika tidak ingin diubah)</span></label>
                    <div class="relative group">
                        <input type="password" id="employeePassword" name="password" placeholder="Minimal 8 karakter..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-12 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <button type="button" id="toggleEmpPassword" class="absolute right-3 top-3 text-on-surface-variant/40 hover:text-primary transition-colors flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">visibility</span>
                        </button>
                    </div>
                    <!-- Strength Indicators -->
                    <div id="emp-pw-strength-box" class="hidden p-4 bg-surface-container-low rounded-xl border border-outline-variant/15 text-xs space-y-2 mt-2">
                        <p class="font-bold text-on-surface-variant mb-1.5">Kriteria Kata Sandi Kuat:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-medium">
                            <div id="emp-pw-len" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Minimal 8 Karakter
                            </div>
                            <div id="emp-pw-upper" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kapital (A-Z)
                            </div>
                            <div id="emp-pw-lower" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Huruf Kecil (a-z)
                            </div>
                            <div id="emp-pw-num" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Angka (0-9)
                            </div>
                            <div id="emp-pw-spec" class="flex items-center gap-1.5 text-red-500 transition-colors">
                                <span class="material-symbols-outlined text-[16px] font-bold">cancel</span> Karakter Simbol (@$!%*?&...)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-outline-variant/15 flex justify-end gap-3 bg-surface-container-lowest">
            <button type="button" onclick="closeEmployeeModal()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-full text-sm font-semibold transition-colors">
                Batal
            </button>
            <button type="submit" form="employeeForm" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-full text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">save</span> Simpan Data
            </button>
        </div>
    </div>
</div>

<script>
    window.onerror = function(message, source, lineno, colno, error) {
        Swal.fire({
            title: 'JavaScript Error',
            html: `<b>Pesan:</b> ${message}<br><b>Baris:</b> ${lineno}<br><b>Kolom:</b> ${colno}<br><b>Berkas:</b> ${source}`,
            icon: 'error',
            confirmButtonColor: '#ba1a1a'
        });
        return false;
    };
    let employeesData = [];
    let departmentsData = [];
    let currentEmployeePage = 1;
    const itemsPerEmployeePage = 10;

    window.stubImportExport = function(action) {
        Swal.fire({
            title: action,
            text: `Fitur ${action} Excel masih dalam masa pengembangan. Silakan gunakan manajemen CRUD untuk sementara waktu.`,
            icon: 'info',
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#000666'
        });
    }

    window.loadEmployees = async function() {
        try {
            const response = await fetch('/hrops/employees/list');
            const res = await response.json();
            if (res.success) {
                employeesData = res.data;
                departmentsData = res.departments || [];
                populateDepartmentDropdowns();
                renderEmployeeTable();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        } catch (e) {
            console.error('Fetch Error:', e);
            Swal.fire('Error', 'Gagal mengambil data karyawan.', 'error');
        }
    }

    window.populateDepartmentDropdowns = function() {
        const depSelect = document.getElementById('employeeDepartment');
        if (!depSelect) return;
        
        depSelect.innerHTML = '<option value="">Tanpa Departemen</option>';
        
        const map = {};
        const roots = [];
        
        departmentsData.forEach(dep => {
            map[dep.id] = { ...dep, children: [] };
        });
        
        departmentsData.forEach(dep => {
            const mapped = map[dep.id];
            if (dep.parent_id && map[dep.parent_id]) {
                map[dep.parent_id].children.push(mapped);
            } else {
                roots.push(mapped);
            }
        });
        
        function addOptions(nodes, prefix = '') {
            nodes.forEach((node, index) => {
                const num = prefix ? `${prefix}.${index + 1}` : `${index}`;
                const opt = document.createElement('option');
                opt.value = node.id;
                
                const indent = '&nbsp;&nbsp;'.repeat(node.level - 1);
                opt.innerHTML = indent + num + '. ' + escapeHtml(node.name);
                depSelect.appendChild(opt);
                
                if (node.children && node.children.length > 0) {
                    node.children.sort((a, b) => a.name.localeCompare(b.name));
                    addOptions(node.children, num);
                }
            });
        }
        
        roots.sort((a, b) => a.name.localeCompare(b.name));
        addOptions(roots);
    }

    window.renderEmployeeTable = function() {
        const tbody = document.getElementById('employeesTableBody');
        const emptyRow = tbody.querySelector('.empty-row');
        
        // Remove old rows
        const oldRows = tbody.querySelectorAll('tr.employee-data-row');
        oldRows.forEach(r => r.remove());

        const search = document.getElementById('employeeSearchInput').value.toLowerCase();
        const roleFilter = document.getElementById('employeeRoleFilter').value;

        let filtered = employeesData.filter(emp => {
            const textMatch = 
                (emp.first_name || '').toLowerCase().includes(search) ||
                (emp.last_name || '').toLowerCase().includes(search) ||
                (emp.email || '').toLowerCase().includes(search) ||
                (emp.employee_id || '').toLowerCase().includes(search);
            const roleMatch = !roleFilter || emp.role === roleFilter;
            return textMatch && roleMatch;
        });

        const totalItems = filtered.length;
        const totalPages = Math.ceil(totalItems / itemsPerEmployeePage) || 1;

        if (currentEmployeePage > totalPages) currentEmployeePage = totalPages;
        if (currentEmployeePage < 1) currentEmployeePage = 1;

        const startIdx = (currentEmployeePage - 1) * itemsPerEmployeePage;
        const endIdx = Math.min(startIdx + itemsPerEmployeePage, totalItems);

        if (totalItems === 0) {
            emptyRow.classList.remove('hidden');
        } else {
            emptyRow.classList.add('hidden');
            
            for (let i = startIdx; i < endIdx; i++) {
                const emp = filtered[i];
                const tr = document.createElement('tr');
                tr.className = 'employee-data-row hover:bg-surface-container-low/30 transition-colors group';
                
                const fullName = escapeHtml(emp.first_name + (emp.last_name ? ' ' + emp.last_name : ''));
                const empId = emp.employee_id ? escapeHtml(emp.employee_id) : '<span class="text-[10px] bg-warning/20 text-warning px-1.5 py-0.5 rounded uppercase font-bold">UNASSIGNED</span>';
                const pp = emp.profile_picture ? escapeHtml(emp.profile_picture) : 'https://www.gravatar.com/avatar/' + window.md5((emp.email || '').trim().toLowerCase()) + '?d=identicon&s=120';
                const salary = emp.base_salary ? new Intl.NumberFormat('id-ID').format(emp.base_salary) : '0';

                tr.innerHTML = `
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img referrerpolicy="no-referrer" src="${pp}" class="w-10 h-10 rounded-full object-cover border border-outline-variant/30" alt="Avatar" onerror="window.handleAvatarError(this, '${window.md5((emp.email || '').trim().toLowerCase())}')">
                            <div>
                                <p class="text-sm font-bold text-on-surface">${fullName}</p>
                                <p class="text-xs text-on-surface-variant font-mono mt-0.5">${empId}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-on-surface">${escapeHtml(emp.email)}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-on-surface capitalize">${escapeHtml(emp.job_title || 'N/A')}</span>
                            <span class="text-xs text-on-surface-variant mt-0.5">${escapeHtml(emp.department_name || 'Tanpa Departemen')}</span>
                            <span class="text-[10px] font-bold uppercase mt-1 inline-block bg-primary/10 text-primary px-2 py-0.5 rounded max-w-max">${escapeHtml(emp.role)}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-mono font-bold text-on-surface">Rp ${salary}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-bold text-on-surface">${emp.annual_leave_quota} <span class="text-xs font-normal text-on-surface-variant">Hari</span></span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button onclick="editEmployee('${emp.id}')" class="p-1.5 bg-surface-container hover:bg-surface-container-high rounded-lg text-on-surface-variant hover:text-primary transition-colors border border-outline-variant/20" title="Edit">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </button>
                            <button onclick="deleteEmployee('${emp.id}')" class="p-1.5 bg-surface-container hover:bg-error/10 rounded-lg text-on-surface-variant hover:text-error transition-colors border border-outline-variant/20" title="Hapus">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            }
        }

        const pagination = document.getElementById('employeePagination');
        if (totalItems === 0 || totalPages <= 1) {
            pagination.classList.add('hidden');
        } else {
            pagination.classList.remove('hidden');
        }

        document.getElementById('employeePrevBtn').disabled = currentEmployeePage === 1;
        document.getElementById('employeeNextBtn').disabled = currentEmployeePage === totalPages || totalPages === 0;
    }

    document.getElementById('employeeSearchInput').addEventListener('input', () => { currentEmployeePage = 1; renderEmployeeTable(); });
    document.getElementById('employeeRoleFilter').addEventListener('change', () => { currentEmployeePage = 1; renderEmployeeTable(); });

    window.prevEmployeePage = function() {
        if (currentEmployeePage > 1) { currentEmployeePage--; renderEmployeeTable(); }
    }
    window.nextEmployeePage = function() {
        currentEmployeePage++; renderEmployeeTable();
    }

    window.openEmployeeModal = function() {
        document.getElementById('employeeForm').reset();
        document.getElementById('employeeDbId').value = '';
        document.getElementById('employeeDepartment').value = '';
        document.getElementById('employeeModalTitle').textContent = 'Tambah Karyawan';
        document.getElementById('employeePassword').required = true;
        document.getElementById('passwordHelper').textContent = '(Wajib diisi untuk akun baru)';
        
        const modal = document.getElementById('employeeModal');
        const container = document.getElementById('employeeModalContainer');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-95');
    }

    window.closeEmployeeModal = function() {
        const modal = document.getElementById('employeeModal');
        const container = document.getElementById('employeeModalContainer');
        modal.classList.add('opacity-0', 'pointer-events-none');
        container.classList.add('scale-95');
    }

    window.editEmployee = function(id) {
        const emp = employeesData.find(e => e.id === id);
        if (!emp) return;

        document.getElementById('employeeDbId').value = emp.id;
        document.getElementById('employeeFirstName').value = emp.first_name || '';
        document.getElementById('employeeLastName').value = emp.last_name || '';
        document.getElementById('employeeStaffId').value = emp.employee_id || '';
        document.getElementById('employeeEmail').value = emp.email || '';
        document.getElementById('employeeJobTitle').value = emp.job_title || '';
        document.getElementById('employeeDepartment').value = emp.department_id || '';
        document.getElementById('employeeRole').value = emp.role || 'employee';
        document.getElementById('employeeLeaveQuota').value = emp.annual_leave_quota;
        
        const formatSalary = new Intl.NumberFormat('id-ID').format(emp.base_salary || 0);
        document.getElementById('employeeBaseSalary').value = formatSalary;

        document.getElementById('employeePassword').value = '';
        document.getElementById('employeePassword').required = false;
        document.getElementById('passwordHelper').textContent = '(Kosongkan jika tidak ingin mengubah sandi)';

        document.getElementById('employeeModalTitle').textContent = 'Edit Karyawan';
        
        const modal = document.getElementById('employeeModal');
        const container = document.getElementById('employeeModalContainer');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-95');
    }

    window.submitEmployeeForm = async function(e) {
        e.preventDefault();
        
        if (typeof window.empPasswordValid !== 'undefined' && !window.empPasswordValid) {
            Swal.fire({
                title: 'Kata Sandi Kurang Kuat!',
                text: 'Harap penuhi semua kriteria kata sandi kuat sebelum menyimpan data karyawan.',
                icon: 'error',
                confirmButtonColor: '#000666'
            });
            return;
        }

        const formData = new FormData(e.target);
        
        try {
            const res = await fetch('/hrops/employees/save', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                closeEmployeeModal();
                Swal.fire({
                    title: 'Berhasil',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#000666'
                });
                loadEmployees();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
        }
    }

    window.deleteEmployee = function(id) {
        Swal.fire({
            title: 'Hapus Karyawan?',
            text: 'Data yang dihapus tidak dapat dikembalikan, termasuk seluruh presensi dan historinya. Yakin ingin melanjutkan?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                
                try {
                    const res = await fetch('/hrops/employees/delete', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        });
                        loadEmployees();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Terjadi kesalahan saat menghapus data.', 'error');
                }
            }
        });
    }

    window.escapeHtml = function(text) {
        if (text === null || text === undefined) return '';
        const str = String(text);
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    };

    // Password Strength & Toggle Show/Hide setup for Employee Form
    (function() {
        const passwordInput = document.getElementById('employeePassword');
        const togglePasswordBtn = document.getElementById('toggleEmpPassword');
        if (passwordInput && togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('.material-symbols-outlined').textContent = type === 'password' ? 'visibility' : 'visibility_off';
            });
        }

        const pwStrengthBox = document.getElementById('emp-pw-strength-box');
        const pwLen = document.getElementById('emp-pw-len');
        const pwUpper = document.getElementById('emp-pw-upper');
        const pwLower = document.getElementById('emp-pw-lower');
        const pwNum = document.getElementById('emp-pw-num');
        const pwSpec = document.getElementById('emp-pw-spec');

        window.empPasswordValid = true; // default true because it can be optional (empty) when editing

        if (passwordInput) {
            passwordInput.addEventListener('focus', () => {
                if (pwStrengthBox) pwStrengthBox.classList.remove('hidden');
            });

            passwordInput.addEventListener('input', function() {
                const val = this.value;
                const isRequired = passwordInput.hasAttribute('required') || passwordInput.required;
                
                if (val.length === 0) {
                    if (isRequired) {
                        pwStrengthBox.classList.remove('hidden');
                        window.empPasswordValid = false;
                    } else {
                        pwStrengthBox.classList.add('hidden');
                        window.empPasswordValid = true;
                    }
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

                window.empPasswordValid = hasLen && hasUpper && hasLower && hasNum && hasSpec;
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

    // Load data on start
    setTimeout(() => {
        loadEmployees();
    }, 200);
</script>