<?php
$csrf = csrf_token();
?>
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Menu Builder</h1>
            <p class="text-on-surface-variant font-medium text-sm font-body">Kelola daftar menu navigasi sistem, struktur hierarki (sub-menu), dan ikon visual secara dinamis.</p>
        </div>
        <button type="button" onclick="openMenuModal()" class="bg-primary hover:bg-primary/90 text-white font-bold text-sm py-2.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm hover:scale-[1.02] active:scale-[0.98]">
            <span class="material-symbols-outlined text-base">add</span>
            Tambah Menu Baru
        </button>
    </div>

    <!-- Main Content Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-outline-variant/15 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/5 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-xl">widgets</span>
                </div>
                <div>
                    <h2 class="font-headline text-lg font-extrabold text-on-surface">Daftar Menu Navigasi</h2>
                    <p class="text-xs text-on-surface-variant font-medium mt-0.5">Seluruh menu terdaftar pada basis data</p>
                </div>
            </div>
            <!-- Search Bar -->
            <div class="relative max-w-md">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">search</span>
                <input type="text" id="searchMenuInput" oninput="filterMenuTable()" placeholder="Cari judul atau rute..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2 focus:outline-none focus:border-primary transition-all">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-surface-container-low/40 border-b border-outline-variant/10 text-on-surface-variant text-[11px] font-extrabold uppercase tracking-wider">
                        <th class="px-6 py-4 w-12">No</th>
                        <th class="px-6 py-4">Judul Menu</th>
                        <th class="px-6 py-4">URL Rute</th>
                        <th class="px-6 py-4">Ikon</th>
                        <th class="px-6 py-4">Menu Induk</th>
                        <th class="px-6 py-4 w-24">Urutan</th>
                        <th class="px-6 py-4 w-32 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="menuTableBody" class="divide-y divide-outline-variant/10 text-sm font-medium text-on-surface">
                    <!-- Loaded dynamically via JS -->
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-4xl animate-spin text-primary/40">autorenew</span>
                                <span class="text-xs font-semibold">Memuat daftar menu...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Dialog (Menu Form) -->
<div id="menuModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <!-- Overlay background -->
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeMenuModal()"></div>
    
    <!-- Modal container -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-2xl w-full max-w-lg mx-4 z-10 flex flex-col overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="menuModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-outline-variant/15 flex justify-between items-center bg-surface-container-low/20">
            <h3 id="modalTitle" class="font-headline text-lg font-extrabold text-on-surface">Tambah Menu</h3>
            <button onclick="closeMenuModal()" class="text-on-surface-variant/70 hover:text-primary hover:bg-surface-container-high p-1.5 rounded-lg transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>
        
        <!-- Modal Form Body -->
        <form id="menuForm" onsubmit="saveMenuSubmit(event)" class="p-6 space-y-4 flex-grow">
            <input type="hidden" id="menuId" name="id">
            
            <!-- Title -->
            <div>
                <label for="menuTitle" class="block text-xs font-bold text-on-surface-variant mb-2">Judul Menu</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">title</span>
                    <input type="text" id="menuTitle" required placeholder="Contoh: Pengaturan Sistem" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>
            </div>

            <!-- URL Route -->
            <div>
                <label for="menuUrl" class="block text-xs font-bold text-on-surface-variant mb-2">URL Rute</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">link</span>
                    <input type="text" id="menuUrl" required placeholder="Contoh: superadmin/settings" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>
            </div>

            <!-- Icon & Sort Order -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Icon -->
                <div>
                    <label for="menuIcon" class="block text-xs font-bold text-on-surface-variant mb-2">Ikon Material</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg" id="iconPreview">settings</span>
                        <input type="text" id="menuIcon" oninput="updateIconPreview(this.value)" placeholder="settings" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
                <!-- Sort Order -->
                <div>
                    <label for="menuSort" class="block text-xs font-bold text-on-surface-variant mb-2">Urutan Tampil</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">sort</span>
                        <input type="number" id="menuSort" placeholder="0" min="0" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
            </div>

            <!-- Parent Menu -->
            <div>
                <label for="menuParent" class="block text-xs font-bold text-on-surface-variant mb-2">Menu Induk (Sub-Menu)</label>
                <div class="relative">
                    <select id="menuParent" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-4 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <option value="">Tidak Ada (Menu Utama / Root)</option>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 pointer-events-none">expand_more</span>
                </div>
            </div>
            
            <!-- Submit Footer -->
            <div class="pt-4 border-t border-outline-variant/15 flex justify-end gap-3 bg-surface-container-low/20 -mx-6 -mb-6 px-6 py-4">
                <button type="button" onclick="closeMenuModal()" class="px-4 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-xl text-sm font-bold transition-colors">Batal</button>
                <button type="submit" id="saveMenuBtn" class="bg-primary hover:bg-primary/90 text-white font-bold text-sm py-2.5 px-5 rounded-xl transition-colors shadow-sm flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">save</span>
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var csrfToken = <?= json_encode($csrf) ?>;
    var allMenus = [];

    // Load menus on init
    window.loadMenus = function() {
        fetch('/superadmin/menu/list')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                allMenus = data.data;
                renderMenuTable(allMenus);
                populateParentSelect(allMenus);
            } else {
                Swal.fire('Error', data.message || 'Gagal memuat menu.', 'error');
            }
        })
        .catch(function(err) {
            console.error('Fetch menus error:', err);
            Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
        });
    };

    // Render table rows
    function renderMenuTable(menus) {
        var tbody = document.getElementById('menuTableBody');
        if (!tbody) return;

        if (menus.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-on-surface-variant">Belum ada menu terdaftar.</td></tr>';
            return;
        }

        var html = '';
        menus.forEach(function(m, idx) {
            // Find parent title if sub-menu
            var parentTitle = '-';
            if (m.parent_id) {
                var p = menus.find(function(item) { return item.id === m.parent_id; });
                if (p) parentTitle = p.title;
            }

            var iconHtml = m.icon 
                ? '<span class="material-symbols-outlined text-primary text-lg flex items-center justify-center bg-primary/5 w-8 h-8 rounded-lg">' + e(m.icon) + '</span>'
                : '<span class="text-xs text-on-surface-variant/40 italic">Tanpa Ikon</span>';

            html += '<tr class="hover:bg-surface-container-low/30 transition-colors">';
            html += '<td class="px-6 py-4 font-bold text-on-surface-variant">' + (idx + 1) + '</td>';
            html += '<td class="px-6 py-4"><span class="font-extrabold text-on-surface">' + e(m.title) + '</span></td>';
            html += '<td class="px-6 py-4"><code class="text-xs bg-surface-container text-primary px-2.5 py-1 rounded-md border border-outline-variant/10">' + e(m.url_route) + '</code></td>';
            html += '<td class="px-6 py-4">' + iconHtml + '</td>';
            html += '<td class="px-6 py-4 text-xs font-bold text-on-surface-variant">' + e(parentTitle) + '</td>';
            html += '<td class="px-6 py-4 font-bold text-on-surface-variant">' + m.sort_order + '</td>';
            html += '<td class="px-6 py-4 text-center">';
            html += '  <div class="flex items-center justify-center gap-2">';
            html += '    <button type="button" onclick="editMenu(' + JSON.stringify(m).replace(/"/g, '&quot;') + ')" class="p-1.5 hover:bg-primary/5 hover:text-primary rounded-lg text-on-surface-variant transition-colors" title="Edit">';
            html += '      <span class="material-symbols-outlined text-[18px]">edit</span>';
            html += '    </button>';
            html += '    <button type="button" onclick="deleteMenu(\'' + m.id + '\')" class="p-1.5 hover:bg-red-50 hover:text-red-600 rounded-lg text-on-surface-variant transition-colors" title="Hapus">';
            html += '      <span class="material-symbols-outlined text-[18px]">delete</span>';
            html += '    </button>';
            html += '  </div>';
            html += '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    // Populate Parent Select options
    function populateParentSelect(menus) {
        var select = document.getElementById('menuParent');
        if (!select) return;

        // Clear existing except first
        select.innerHTML = '<option value="">Tidak Ada (Menu Utama / Root)</option>';
        
        // Only root menus (parent_id is null) can be parents
        menus.forEach(function(m) {
            if (!m.parent_id) {
                var opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.title;
                select.appendChild(opt);
            }
        });
    }

    // Filter search
    window.filterMenuTable = function() {
        var q = document.getElementById('searchMenuInput').value.toLowerCase().trim();
        var filtered = allMenus.filter(function(m) {
            return m.title.toLowerCase().indexOf(q) !== -1 || m.url_route.toLowerCase().indexOf(q) !== -1;
        });
        renderMenuTable(filtered);
    };

    // Open Form modal
    window.openMenuModal = function(m) {
        m = m || {};
        document.getElementById('menuId').value = m.id || '';
        document.getElementById('menuTitle').value = m.title || '';
        document.getElementById('menuUrl').value = m.url_route || '';
        document.getElementById('menuIcon').value = m.icon || 'widgets';
        document.getElementById('menuSort').value = m.sort_order || 0;
        document.getElementById('menuParent').value = m.parent_id || '';
        
        updateIconPreview(m.icon || 'widgets');
        
        document.getElementById('modalTitle').textContent = m.id ? 'Edit Menu' : 'Tambah Menu Baru';
        
        var modal = document.getElementById('menuModal');
        var container = document.getElementById('menuModalContainer');
        modal.classList.remove('hidden');
        
        setTimeout(function() {
            container.classList.remove('scale-95', 'opacity-0');
            container.classList.add('scale-100', 'opacity-100');
        }, 10);
    };

    // Close Modal
    window.closeMenuModal = function() {
        var modal = document.getElementById('menuModal');
        var container = document.getElementById('menuModalContainer');
        container.classList.remove('scale-100', 'opacity-100');
        container.classList.add('scale-95', 'opacity-0');
        
        setTimeout(function() {
            modal.classList.add('hidden');
        }, 200);
    };

    // Update icon preview dynamically
    window.updateIconPreview = function(val) {
        var iconPreview = document.getElementById('iconPreview');
        if (iconPreview) {
            iconPreview.textContent = val.trim() || 'widgets';
        }
    };

    // Edit row
    window.editMenu = function(m) {
        openMenuModal(m);
    };

    // Delete row
    window.deleteMenu = function(id) {
        Swal.fire({
            title: 'Hapus Menu?',
            text: 'Data menu dan hak akses terkait akan dihapus secara permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            fetch('/superadmin/menu/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ id: id })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    Swal.fire('Terhapus!', 'Menu berhasil dihapus.', 'success');
                    loadMenus();
                } else {
                    Swal.fire('Error', data.message || 'Gagal menghapus menu.', 'error');
                }
            })
            .catch(function(err) {
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
            });
        });
    };

    // Save menu
    window.saveMenuSubmit = function(e) {
        e.preventDefault();
        
        var payload = {
            id: document.getElementById('menuId').value,
            title: document.getElementById('menuTitle').value.trim(),
            url_route: document.getElementById('menuUrl').value.trim(),
            icon: document.getElementById('menuIcon').value.trim(),
            sort_order: parseInt(document.getElementById('menuSort').value) || 0,
            parent_id: document.getElementById('menuParent').value
        };

        var btn = document.getElementById('saveMenuBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">autorenew</span> Menyimpan...';

        fetch('/superadmin/menu/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                closeMenuModal();
                loadMenus();
            } else {
                Swal.fire('Error', data.message || 'Gagal menyimpan menu.', 'error');
            }
        })
        .catch(function(err) {
            console.error(err);
            Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Simpan';
        });
    };

    // Simple XSS escaper
    function e(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;');
    }

    // Load initial data
    setTimeout(loadMenus, 100);

})();
</script>
