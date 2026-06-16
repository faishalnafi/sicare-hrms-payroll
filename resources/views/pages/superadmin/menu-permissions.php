<?php
$csrf = csrf_token();
?>
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Matriks Akses (ACL)</h1>
            <p class="text-on-surface-variant font-medium text-sm font-body">Petakan hak akses menu navigasi secara dinamis berdasarkan Peran (Roles) dan Departemen Executive (C-Level).</p>
        </div>
        <button type="button" onclick="savePermissionsMatrix()" class="bg-primary hover:bg-primary/90 text-white font-bold text-sm py-2.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm hover:scale-[1.02] active:scale-[0.98]">
            <span class="material-symbols-outlined text-base">save</span>
            Simpan Matriks Akses
        </button>
    </div>

    <!-- Alert Box for Superadmin Override -->
    <div class="bg-primary/5 border border-primary/10 rounded-2xl p-4 flex items-start gap-3 shadow-sm">
        <div class="bg-primary/10 p-1.5 rounded-full shrink-0 flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-sm">info</span>
        </div>
        <div class="text-xs text-primary-dark font-medium leading-relaxed">
            <strong>Catatan Otoritas:</strong> Superadmin mewarisi semua menu secara algoritmik (God-Mode), namun pendaftaran matriks tetap diwajibkan untuk keperluan rendering standar. C-Level menggunakan peran tunggal <code>executive</code> dan dipisahkan hak akses menunya secara vertikal menggunakan ID Departemen.
        </div>
    </div>

    <!-- Matrix Card Container -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-outline-variant/15 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/5 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-xl">rule_folder</span>
            </div>
            <div>
                <h2 class="font-headline text-lg font-extrabold text-on-surface">Matriks Hak Akses Menu</h2>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">Centang untuk memberikan hak akses menu navigasi</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-sm" id="matrixTable">
                <thead>
                    <tr class="bg-surface-container-low/40 border-b border-outline-variant/10 text-on-surface-variant text-[10px] font-extrabold uppercase tracking-wider" id="matrixHeaderRow">
                        <th class="px-6 py-4 min-w-[200px]">Menu Navigasi</th>
                        <!-- Dynamic role columns go here -->
                        <th class="px-6 py-4 text-center">Loading...</th>
                    </tr>
                </thead>
                <tbody id="matrixTableBody" class="divide-y divide-outline-variant/10 font-medium text-on-surface">
                    <tr>
                        <td colspan="2" class="px-6 py-12 text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-4xl animate-spin text-primary/40">autorenew</span>
                                <span class="text-xs font-semibold">Memuat matriks akses...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Table Footer Action -->
        <div class="px-6 py-4 border-t border-outline-variant/15 flex justify-end bg-surface-container-low/30">
            <button type="button" onclick="savePermissionsMatrix()" id="saveMatrixBtnBottom" class="bg-primary hover:bg-primary/90 text-white font-bold text-sm py-2.5 px-5 rounded-xl transition-colors shadow-sm flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">save</span>
                Simpan Matriks Akses
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var csrfToken = <?= json_encode($csrf) ?>;
    var rawData = null;

    // Load matrix data
    window.loadMatrixData = function() {
        fetch('/superadmin/menu-permissions/matrix')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                rawData = data;
                buildMatrix(data);
            } else {
                Swal.fire('Error', data.message || 'Gagal memuat matriks.', 'error');
            }
        })
        .catch(function(err) {
            console.error('Fetch matrix error:', err);
            Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
        });
    };

    // Build the grid dynamic matrix
    function buildMatrix(data) {
        var headerRow = document.getElementById('matrixHeaderRow');
        var tbody = document.getElementById('matrixTableBody');
        if (!headerRow || !tbody) return;

        // Clear header
        headerRow.innerHTML = '<th class="px-6 py-4 min-w-[220px] text-on-surface">Menu Navigasi</th>';

        // Filter out executive role from simple columns
        // Executive role has ID: 3d6e5c8a-7b2f-4c19-9d58-e4b9c1f2d3a4
        var execRole = data.roles.find(function(r) { return r.name === 'executive'; });
        var simpleRoles = data.roles.filter(function(r) { return r.name !== 'executive'; });

        // Columns metadata helper
        var columns = [];

        // 1. Add normal roles
        simpleRoles.forEach(function(r) {
            columns.push({
                title: r.display_name,
                role_id: r.id,
                department_id: null,
                key: r.id + '_null'
            });
        });

        // 2. Add Executive (Global / CEO)
        if (execRole) {
            columns.push({
                title: 'CEO / Executive (Global)',
                role_id: execRole.id,
                department_id: null,
                key: execRole.id + '_null'
            });

            // 3. Add Executive for each department
            data.departments.forEach(function(d) {
                columns.push({
                    title: 'Executive - ' + d.name,
                    role_id: execRole.id,
                    department_id: d.id,
                    key: execRole.id + '_' + d.id
                });
            });
        }

        // Render header columns
        columns.forEach(function(col) {
            var th = document.createElement('th');
            th.className = 'px-4 py-4 text-center text-xs font-bold text-on-surface-variant min-w-[150px] border-l border-outline-variant/10';
            th.textContent = col.title;
            headerRow.appendChild(th);
        });

        // Render rows (Menus)
        if (data.menus.length === 0) {
            tbody.innerHTML = '<tr><td colspan="' + (columns.length + 1) + '" class="px-6 py-8 text-center text-on-surface-variant">Belum ada menu terdaftar. Silakan buat menu terlebih dahulu.</td></tr>';
            return;
        }

        var html = '';
        data.menus.forEach(function(m) {
            html += '<tr class="hover:bg-surface-container-low/30 transition-colors">';
            // Menu title column
            html += '<td class="px-6 py-3.5">';
            html += '  <div class="flex flex-col">';
            html += '    <span class="font-extrabold text-on-surface">' + e(m.title) + '</span>';
            html += '    <span class="text-[10px] text-on-surface-variant/60 font-semibold mt-0.5">' + e(m.url_route) + '</span>';
            html += '  </div>';
            html += '</td>';

            // Checkbox columns
            columns.forEach(function(col) {
                // Check if this menu is permitted for this role + department
                var isChecked = data.permissions.some(function(p) {
                    return p.menu_id === m.id && 
                           p.role_id === col.role_id && 
                           (p.department_id === col.department_id || (!p.department_id && !col.department_id));
                });

                var checkAttr = isChecked ? 'checked' : '';
                var deptAttr = col.department_id ? 'data-dept-id="' + col.department_id + '"' : '';

                html += '<td class="px-4 py-3.5 text-center border-l border-outline-variant/10">';
                html += '  <label class="inline-flex items-center justify-center cursor-pointer p-1 rounded-lg hover:bg-surface-container-high/40 transition-colors">';
                html += '    <input type="checkbox" class="matrix-checkbox w-4.5 h-4.5 rounded-sm border-outline-variant/30 text-primary focus:ring-primary/20 cursor-pointer" ';
                html += '           data-menu-id="' + m.id + '" ';
                html += '           data-role-id="' + col.role_id + '" ';
                html += '           ' + deptAttr + ' ';
                html += '           ' + checkAttr + '>';
                html += '  </label>';
                html += '</td>';
            });

            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    // Save Permissions
    window.savePermissionsMatrix = function() {
        var checkboxes = document.querySelectorAll('.matrix-checkbox');
        var permissions = [];

        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                permissions.push({
                    menu_id: cb.getAttribute('data-menu-id'),
                    role_id: cb.getAttribute('data-role-id'),
                    department_id: cb.getAttribute('data-dept-id') || null
                });
            }
        });

        Swal.fire({
            title: 'Simpan Matriks Akses?',
            text: 'Perubahan hak akses menu navigasi akan langsung diterapkan ke seluruh pengguna sistem.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            var btns = [
                document.querySelector('[onclick="window.savePermissionsMatrix()"]'),
                document.getElementById('saveMatrixBtnBottom')
            ];
            btns.forEach(function(btn) {
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">autorenew</span> Menyimpan...';
                }
            });

            fetch('/superadmin/menu-permissions/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ permissions: permissions })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    Swal.fire({
                        title: 'Tersimpan!',
                        text: 'Matriks hak akses berhasil diperbarui.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadMatrixData();
                } else {
                    Swal.fire('Error', data.message || 'Gagal menyimpan matriks.', 'error');
                }
            })
            .catch(function(err) {
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
            })
            .finally(function() {
                btns.forEach(function(btn) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Simpan Matriks Akses';
                    }
                });
            });
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
    setTimeout(loadMatrixData, 100);

})();
</script>
