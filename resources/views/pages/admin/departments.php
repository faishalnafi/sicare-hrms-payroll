<div class="space-y-6 animate-fade-in font-body">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Struktur Departemen 10 Level</h1>
            <p class="text-on-surface-variant font-medium text-sm">Kelola hierarki organisasi perusahaan, sub-divisi, dan unit kerja hingga kedalaman 10 tingkat.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-primary/5 text-primary text-xs font-extrabold px-3.5 py-2 rounded-full border border-primary/10 flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[14px]">account_tree</span>
                Hierarki Korporat
            </span>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Tree View Card (Left - 7 Columns) -->
        <div class="lg:col-span-7 bg-surface-container-lowest border border-outline-variant/15 rounded-2xl p-6 shadow-sm flex flex-col space-y-6">
            <div class="flex justify-between items-center pb-4 border-b border-outline-variant/10">
                <h3 class="font-headline text-lg font-extrabold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">lan</span>
                    Bagan Struktur Organisasi
                </h3>
                <button onclick="window.startAddRootDept()" class="px-3.5 py-2 bg-primary/5 hover:bg-primary text-primary hover:text-white text-xs font-extrabold border border-primary/10 rounded-full transition-all flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-sm">add</span> Divisi Utama (Root)
                </button>
            </div>

            <!-- Root Drop Zone (visible during drag) -->
            <div id="rootDropZone" class="hidden border-2 border-dashed border-primary/30 rounded-xl p-4 text-center text-xs font-bold text-primary bg-primary/5 transition-all hover:bg-primary/10 hover:border-primary cursor-pointer flex items-center justify-center gap-2" ondragover="window.onDeptDragOver(event)" ondragenter="this.classList.add('bg-primary/10', 'border-primary')" ondragleave="this.classList.remove('bg-primary/10', 'border-primary')" ondrop="window.onDeptDrop(event, null)">
                <span class="material-symbols-outlined text-base">arrow_upward</span>
                Lepaskan di sini untuk menjadikan Divisi Utama (Level 1)
            </div>

            <!-- Tree Content -->
            <div id="deptTreeContainer" class="space-y-3 pl-1 relative min-h-[300px]" ondragover="window.onDeptDragOver(event)" ondrop="window.onDeptDrop(event, null)">
                <!-- Loaded Dynamically -->
                <div class="text-center py-12 text-on-surface-variant" id="treeLoadingState">
                    <span class="material-symbols-outlined text-3xl animate-spin mb-2 opacity-50 block">autorenew</span>
                    <p class="text-sm font-semibold">Memetakan struktur organisasi...</p>
                </div>
            </div>
        </div>

        <!-- Form Editor Card (Right - 5 Columns) -->
        <div class="lg:col-span-5 bg-surface-container-lowest border border-outline-variant/15 rounded-2xl p-6 shadow-sm flex flex-col space-y-6">
            <div class="pb-4 border-b border-outline-variant/10">
                <h3 id="formTitle" class="font-headline text-lg font-extrabold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary" id="formTitleIcon">add_box</span>
                    Tambah Divisi Utama
                </h3>
            </div>

            <!-- Edit Form -->
            <form id="deptForm" onsubmit="window.submitDeptForm(event)" class="space-y-5">
                <input type="hidden" id="deptId" name="id" value="">
                
                <!-- Parent Dept Info -->
                <div id="parentInfoBox" class="hidden p-4 rounded-xl bg-surface-container-low border border-outline-variant/15 text-xs text-on-surface-variant space-y-1">
                    <span class="font-bold text-[10px] uppercase text-primary/75 tracking-wider">Departemen Induk (Parent)</span>
                    <p id="parentInfoName" class="font-extrabold text-on-surface text-sm font-headline">Parent Department Name</p>
                    <span id="parentInfoLevel" class="inline-block bg-primary/10 text-primary text-[9px] font-extrabold uppercase px-2 py-0.5 rounded mt-1">Level 1</span>
                </div>

                <!-- Parent Select Option (Fallback/Manual choice) -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Pilih Departemen Induk</label>
                    <div class="relative">
                        <select id="deptParentSelector" name="parent_id" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <option value="">Tanpa Induk (Root / Divisi Utama)</option>
                            <!-- Populated dynamically -->
                        </select>
                        
                    </div>
                </div>

                <!-- Dept Name -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Nama Departemen / Divisi / Unit *</label>
                    <input type="text" id="deptName" name="name" required placeholder="Cth: Frontend Development" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>

                <!-- Plafon Reimbursement -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Plafon Reimbursement Bulanan (Total)</label>
                    <div class="relative flex items-center gap-2">
                        <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                        <input type="text" id="deptReimbursementLimit" name="reimbursement_limit" placeholder="Cth: 15.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');">
                    </div>
                    <p class="text-[10px] text-on-surface-variant/70 mt-1.5 font-medium">Batas kumulatif bulanan untuk seluruh karyawan di departemen ini. Biarkan kosong untuk menggunakan nilai default global. Set 0 untuk menonaktifkan.</p>
                </div>

                <!-- Plafon Detail Kategori -->
                <div class="space-y-4 pt-4 border-t border-outline-variant/10">
                    <label class="block text-xs font-bold text-primary mb-1 uppercase tracking-wider">Detail Plafon Kategori (Bulanan)</label>
                    
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1.5">Plafon Medis</label>
                        <div class="relative flex items-center gap-2">
                            <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                            <input type="text" id="deptLimitMedis" name="limit_medis" placeholder="Cth: 5.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1.5">Plafon Transportasi</label>
                        <div class="relative flex items-center gap-2">
                            <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                            <input type="text" id="deptLimitTransport" name="limit_transport" placeholder="Cth: 3.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1.5">Plafon Operasional</label>
                        <div class="relative flex items-center gap-2">
                            <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                            <input type="text" id="deptLimitOperasional" name="limit_operasional" placeholder="Cth: 4.000.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant mb-1.5">Plafon Makan & Bisnis</label>
                        <div class="relative flex items-center gap-2">
                            <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap">Rp</span>
                            <input type="text" id="deptLimitMakan" name="limit_makan" placeholder="Cth: 2.500.000" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl px-4 py-2 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all font-mono" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');">
                        </div>
                    </div>
                    
                    <p class="text-[10px] text-on-surface-variant/70 font-medium mt-1">Biarkan kosong untuk menggunakan nilai default global kategori masing-masing. Set 0 untuk menonaktifkan.</p>
                </div>

                <!-- Form Controls -->
                <div class="pt-4 border-t border-outline-variant/10 flex justify-end gap-3">
                    <button type="button" id="resetFormBtn" onclick="window.resetDeptForm()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-full text-xs font-semibold transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-full text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">save</span> Simpan Departemen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var departmentsList = [];

    function escHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, function(m) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
        });
    }

    window.loadDepartments = function() {
        // Fetch departments list
        fetch('/hrops/employees/list')
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (res.success) {
                    departmentsList = res.departments || [];
                    window.renderDeptTree();
                    window.populateParentDropdown();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', res.message, 'error');
                }
            })
            .catch(function(e) {
                console.error('Fetch departments error:', e);
            });
    };

    window.populateParentDropdown = function() {
        var parentSel = document.getElementById('deptParentSelector');
        if (!parentSel) return;

        parentSel.innerHTML = '<option value="">Tanpa Induk (Root / Divisi Utama)</option>';
        
        var map = {};
        var roots = [];
        
        departmentsList.forEach(function(dep) {
            map[dep.id] = Object.assign({}, dep, { children: [] });
        });
        
        departmentsList.forEach(function(dep) {
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
                // If deep nesting is Level 10, it cannot have children, so don't show as parent choice if level >= 10
                if (node.level >= 10) return;
                
                var num = prefix ? (prefix + '.' + (index + 1)) : String(index + 1);
                var opt = document.createElement('option');
                opt.value = node.id;
                
                var indent = '';
                for (var i = 1; i < node.level; i++) indent += '\u00a0\u00a0\u00a0\u00a0';
                opt.textContent = indent + num + '. ' + node.name;
                parentSel.appendChild(opt);
                
                if (node.children && node.children.length > 0) {
                    node.children.sort(function(a, b) { return a.name.localeCompare(b.name); });
                    addOptions(node.children, num);
                }
            });
        }

        roots.sort(function(a, b) { return a.name.localeCompare(b.name); });
        addOptions(roots);
    };

    window.renderDeptTree = function() {
        var container = document.getElementById('deptTreeContainer');
        if (!container) return;

        if (departmentsList.length === 0) {
            container.innerHTML = 
                '<div class="text-center py-12 text-on-surface-variant">' +
                    '<span class="material-symbols-outlined text-5xl mb-2 text-outline-variant/60">schema</span>' +
                    '<h3 class="text-base font-bold text-on-surface">Struktur Kosong</h3>' +
                    '<p class="text-xs text-on-surface-variant mt-1">Belum ada departemen yang terdaftar. Tambahkan divisi utama pertama Anda!</p>' +
                '</div>';
            return;
        }

        var map = {};
        var roots = [];
        
        departmentsList.forEach(function(dep) {
            map[dep.id] = Object.assign({}, dep, { children: [] });
        });
        
        departmentsList.forEach(function(dep) {
            var mapped = map[dep.id];
            if (dep.parent_id && map[dep.parent_id]) {
                map[dep.parent_id].children.push(mapped);
            } else {
                roots.push(mapped);
            }
        });

        // Recursively build nested HTML tree
        function buildTreeHtml(nodes, prefix) {
            prefix = prefix || '';
            var html = '<div class="space-y-2 w-full">';
            
            nodes.forEach(function(node, index) {
                var num = prefix ? (prefix + '.' + (index + 1)) : String(index + 1);
                var hasChildren = node.children && node.children.length > 0;
                
                var borderClass = 'border-l-2 border-primary/20 pl-4 ml-3';
                if (node.level === 1) borderClass = '';

                var depthLabel = 'Level ' + node.level;
                var depthColor = 'bg-primary/5 text-primary border-primary/10';
                if (node.level === 2) depthColor = 'bg-blue-50 text-blue-700 border-blue-100';
                if (node.level === 3) depthColor = 'bg-teal-50 text-teal-700 border-teal-100';
                if (node.level === 4) depthColor = 'bg-purple-50 text-purple-700 border-purple-100';
                if (node.level === 5) depthColor = 'bg-red-50 text-red-700 border-red-100';
                if (node.level === 6) depthColor = 'bg-orange-50 text-orange-700 border-orange-100';
                if (node.level === 7) depthColor = 'bg-pink-50 text-pink-700 border-pink-100';
                if (node.level === 8) depthColor = 'bg-yellow-50 text-yellow-700 border-yellow-100';
                if (node.level === 9) depthColor = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                if (node.level >= 10) depthColor = 'bg-rose-50 text-rose-700 border-rose-100';

                var limitLabel = '';
                if (node.reimbursement_limit !== null && node.reimbursement_limit !== undefined && node.reimbursement_limit !== '') {
                    var limitVal = parseFloat(node.reimbursement_limit);
                    limitLabel = '<span class="text-[9px] font-extrabold uppercase border px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-750 border-indigo-100/50">Plafon: Rp ' + String(limitVal).replace(/\B(?=(\d{3})+(?!\d))/g, ".") + '</span>';
                }

                html += 
                    '<div class="' + borderClass + ' py-1">' +
                        '<div class="dept-card flex items-center justify-between group p-3.5 bg-surface-container-low border border-outline-variant/15 rounded-xl hover:bg-surface-container-high/50 hover:border-primary/20 transition-all shadow-inner-sm cursor-grab active:cursor-grabbing" ' +
                        'data-id="' + node.id + '" ' +
                        'draggable="true" ' +
                        'ondragstart="window.onDeptDragStart(event, \'' + node.id + '\')" ' +
                        'ondragend="window.onDeptDragEnd(event)" ' +
                        'ondragover="window.onDeptDragOver(event)" ' +
                        'ondragenter="window.onDeptDragEnter(event)" ' +
                        'ondragleave="window.onDeptDragLeave(event)" ' +
                        'ondrop="window.onDeptDrop(event, \'' + node.id + '\')">' +
                            '<div class="flex items-center gap-3 min-w-0 flex-1 flex-wrap">' +
                                '<span class="material-symbols-outlined text-on-surface-variant/40 group-hover:text-primary cursor-grab select-none flex-shrink-0 drag-handle">drag_indicator</span>' +
                                '<span class="text-xs font-mono font-bold text-primary bg-primary/10 px-2 py-0.5 rounded">' + num + '</span>' +
                                '<span class="text-sm font-bold text-on-surface truncate font-headline">' + escHtml(node.name) + '</span>' +
                                '<span class="text-[9px] font-extrabold uppercase border px-2 py-0.5 rounded-full ' + depthColor + '">' + depthLabel + '</span>' +
                                (limitLabel ? limitLabel : '') +
                            '</div>' +
                            
                            '<!-- Controls -->' +
                            '<div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 ml-2">' +
                                (node.level < 10 
                                    ? '<button onclick="window.startAddChildDept(\'' + node.id + '\', \'' + escHtml(node.name) + '\', ' + node.level + ')" class="p-1 hover:bg-primary/10 text-primary hover:text-primary rounded" title="Tambah Sub-divisi"><span class="material-symbols-outlined text-[16px] font-bold">add_box</span></button>'
                                    : '') +
                                '<button onclick="window.startEditDept(\'' + node.id + '\')" class="p-1 hover:bg-blue-50 text-blue-600 hover:text-blue-700 rounded" title="Edit Departemen"><span class="material-symbols-outlined text-[16px] font-bold">edit</span></button>' +
                                '<button onclick="window.deleteDept(\'' + node.id + '\', \'' + escHtml(node.name) + '\')" class="p-1 hover:bg-red-50 text-red-600 hover:text-red-700 rounded" title="Hapus"><span class="material-symbols-outlined text-[16px] font-bold">delete</span></button>' +
                            '</div>' +
                        '</div>';

                if (hasChildren) {
                    node.children.sort(function(a, b) { return a.name.localeCompare(b.name); });
                    html += buildTreeHtml(node.children, num);
                }
                
                html += '</div>';
            });
            
            html += '</div>';
            return html;
        }

        roots.sort(function(a, b) { return a.name.localeCompare(b.name); });
        container.innerHTML = buildTreeHtml(roots);
    };

    window.startAddRootDept = function() {
        window.resetDeptForm();
        document.getElementById('formTitle').innerHTML = '<span class="material-symbols-outlined text-primary" id="formTitleIcon">add_box</span> Tambah Divisi Utama';
    };

    window.startAddChildDept = function(parentId, parentName, parentLevel) {
        window.resetDeptForm();
        
        document.getElementById('formTitle').innerHTML = '<span class="material-symbols-outlined text-primary" id="formTitleIcon">add_box</span> Tambah Sub-divisi';
        document.getElementById('deptParentSelector').value = parentId;
        
        var infoBox = document.getElementById('parentInfoBox');
        var infoName = document.getElementById('parentInfoName');
        var infoLevel = document.getElementById('parentInfoLevel');
        
        if (infoBox && infoName && infoLevel) {
            infoName.textContent = parentName;
            infoLevel.textContent = 'Level ' + parentLevel;
            infoBox.classList.remove('hidden');
        }
    };

    window.startEditDept = function(id) {
        var node = departmentsList.find(function(d) { return d.id === id; });
        if (!node) return;

        window.resetDeptForm();
        
        document.getElementById('formTitle').innerHTML = '<span class="material-symbols-outlined text-blue-600" id="formTitleIcon">edit_note</span> Edit Departemen';
        document.getElementById('deptId').value = node.id;
        document.getElementById('deptName').value = node.name;
        document.getElementById('deptParentSelector').value = node.parent_id || '';
        
        function setLimitVal(inputId, val) {
            var input = document.getElementById(inputId);
            if (input) {
                if (val !== '' && val !== null && val !== undefined) {
                    var parsed = parseFloat(val);
                    input.value = String(parsed).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                } else {
                    input.value = '';
                }
            }
        }
        
        setLimitVal('deptReimbursementLimit', node.reimbursement_limit);
        setLimitVal('deptLimitMedis', node.limit_medis);
        setLimitVal('deptLimitTransport', node.limit_transport);
        setLimitVal('deptLimitOperasional', node.limit_operasional);
        setLimitVal('deptLimitMakan', node.limit_makan);

        // Hide parent info block for direct edit mode to allow full selector dropdown
        var infoBox = document.getElementById('parentInfoBox');
        if (infoBox) infoBox.classList.add('hidden');
    };

    window.resetDeptForm = function() {
        document.getElementById('deptForm').reset();
        document.getElementById('deptId').value = '';
        document.getElementById('deptParentSelector').value = '';
        
        var inputs = [
            'deptReimbursementLimit',
            'deptLimitMedis',
            'deptLimitTransport',
            'deptLimitOperasional',
            'deptLimitMakan'
        ];
        inputs.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
        
        var infoBox = document.getElementById('parentInfoBox');
        if (infoBox) infoBox.classList.add('hidden');
        
        document.getElementById('formTitle').innerHTML = '<span class="material-symbols-outlined text-primary" id="formTitleIcon">add_box</span> Tambah Divisi Utama';
    };

    window.submitDeptForm = function(e) {
        e.preventDefault();
        
        var formData = new FormData(e.target);

        fetch('/admin/departments/save', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Berhasil',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#000666'
                    });
                }
                window.resetDeptForm();
                window.loadDepartments();
            } else {
                if (typeof Swal !== 'undefined') Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(function(err) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
        });
    };

    window.deleteDept = function(id, name) {
        if (typeof Swal === 'undefined') return;

        Swal.fire({
            title: 'Hapus Departemen?',
            text: 'Apakah Anda yakin ingin menghapus departemen "' + name + '"? Tindakan ini tidak dapat dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                var fd = new FormData();
                fd.append('id', id);

                fetch('/admin/departments/delete', {
                    method: 'POST',
                    body: fd
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        Swal.fire({
                            title: 'Dihapus',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#000666'
                        });
                        window.loadDepartments();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(function(err) {
                    Swal.fire('Error', 'Terjadi kesalahan saat menghapus.', 'error');
                });
            }
        });
    };

    var draggedDeptId = null;

    // Helper: Cek apakah childId adalah keturunan dari parentId
    function isDescendantJs(parentId, childId) {
        var current = departmentsList.find(function(d) { return d.id === childId; });
        while (current && current.parent_id) {
            if (current.parent_id === parentId) return true;
            current = departmentsList.find(function(d) { return d.id === current.parent_id; });
        }
        return false;
    }

    // Helper: Cari kedalaman (level) maksimum dari sub-tree departemen
    function getMaxDescendantDepthJs(deptId, currentDepth) {
        var children = departmentsList.filter(function(d) { return d.parent_id === deptId; });
        if (children.length === 0) return currentDepth;
        var max = currentDepth;
        children.forEach(function(child) {
            var depth = getMaxDescendantDepthJs(child.id, currentDepth + 1);
            if (depth > max) max = depth;
        });
        return max;
    }

    // Helper: Cek apakah target valid untuk dijatuhkan
    function isValidDropJs(draggedId, targetId) {
        if (!draggedId) return false;
        if (!targetId) return true; // Drop di root selalu diperbolehkan
        if (draggedId === targetId) return false; // Tidak boleh di diri sendiri
        
        // Tidak boleh drop di salah satu keturunannya sendiri
        if (isDescendantJs(draggedId, targetId)) return false;
        
        // Cek batasan level kedalaman (maksimal 10)
        var draggedNode = departmentsList.find(function(d) { return d.id === draggedId; });
        var targetNode = departmentsList.find(function(d) { return d.id === targetId; });
        if (!draggedNode || !targetNode) return false;
        
        var maxDescendantDepth = getMaxDescendantDepthJs(draggedId, draggedNode.level);
        var height = maxDescendantDepth - draggedNode.level + 1;
        var newMaxLevel = targetNode.level + height;
        
        if (newMaxLevel > 10) return false;
        
        return true;
    }

    window.onDeptDragStart = function(e, id) {
        // Hanya izinkan seret lewat drag-handle (ikon grab)
        var isHandle = e.target.classList.contains('drag-handle') || e.target.closest('.drag-handle');
        if (!isHandle) {
            e.preventDefault();
            return false;
        }

        draggedDeptId = id;
        e.dataTransfer.setData('text/plain', id);
        e.dataTransfer.effectAllowed = 'move';
        e.currentTarget.classList.add('opacity-40');
        
        // Tampilkan drop zone root
        var rootDropZone = document.getElementById('rootDropZone');
        if (rootDropZone) {
            rootDropZone.classList.remove('hidden');
        }
    };

    window.onDeptDragEnd = function(e) {
        e.currentTarget.classList.remove('opacity-40');
        draggedDeptId = null;
        
        // Sembunyikan drop zone root
        var rootDropZone = document.getElementById('rootDropZone');
        if (rootDropZone) {
            rootDropZone.classList.add('hidden');
            rootDropZone.classList.remove('bg-primary/10', 'border-primary');
        }

        document.querySelectorAll('.dept-card').forEach(function(el) {
            el.classList.remove('border-primary', 'bg-primary/5', 'scale-[1.01]', 'border-red-500/50', 'bg-red-500/5');
        });
    };

    window.onDeptDragOver = function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    };

    window.onDeptDragEnter = function(e) {
        e.preventDefault();
        var card = e.currentTarget.closest('.dept-card');
        if (!card) return;
        
        var targetId = card.getAttribute('data-id');
        if (isValidDropJs(draggedDeptId, targetId)) {
            card.classList.add('border-primary', 'bg-primary/5', 'scale-[1.01]');
        } else {
            card.classList.add('border-red-500/50', 'bg-red-500/5');
        }
    };

    window.onDeptDragLeave = function(e) {
        var card = e.currentTarget.closest('.dept-card');
        if (card) {
            card.classList.remove('border-primary', 'bg-primary/5', 'scale-[1.01]', 'border-red-500/50', 'bg-red-500/5');
        }
    };

    window.onDeptDrop = function(e, targetId) {
        e.preventDefault();
        e.stopPropagation();
        
        var card = e.currentTarget.closest('.dept-card');
        if (card) {
            card.classList.remove('border-primary', 'bg-primary/5', 'scale-[1.01]', 'border-red-500/50', 'bg-red-500/5');
        }

        var draggedId = e.dataTransfer.getData('text/plain') || draggedDeptId;
        if (!draggedId || draggedId === targetId) return;

        if (!isValidDropJs(draggedId, targetId)) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Tindakan Ditolak',
                    text: 'Tidak dapat memindahkan departemen ke posisi ini karena melanggar aturan hierarki (kedalaman > 10 level atau referensi berputar).',
                    icon: 'error',
                    confirmButtonColor: '#000666'
                });
            }
            return;
        }

        window.moveDepartment(draggedId, targetId);
    };

    window.moveDepartment = function(draggedId, targetId) {
        var fd = new FormData();
        fd.append('id', draggedId);
        if (targetId) {
            fd.append('parent_id', targetId);
        }

        fetch('/admin/departments/move', {
            method: 'POST',
            body: fd
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Berhasil',
                        text: data.message,
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
                window.loadDepartments();
            } else {
                if (typeof Swal !== 'undefined') Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(function(err) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Terjadi kesalahan saat memindahkan departemen.', 'error');
        });
    };

    // Auto load on init
    setTimeout(function() {
        window.loadDepartments();
    }, 100);

})();
</script>
