<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Audit Log &amp; Security</h1>
            <p class="text-on-surface-variant font-medium text-sm font-body">Pantau seluruh aktivitas pengguna, perubahan data, dan akses sistem secara real-time untuk keamanan organisasi.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-primary/5 text-primary text-xs font-extrabold px-3.5 py-2 rounded-full border border-primary/10 flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[14px]">shield_person</span>
                Otoritas Super Admin
            </span>
            <?php if (($_SESSION['role'] ?? '') === 'superadmin'): ?>
            <button onclick="window.clearAuditLogs()" class="bg-red-500 hover:bg-red-600 text-white font-bold text-sm py-2.5 px-4 rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">delete_sweep</span>
                Bersihkan Log
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Stats Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Log Entries -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/5 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-primary text-2xl">receipt_long</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total Log</p>
                <p id="auditStatTotal" class="text-2xl font-extrabold text-on-surface font-headline tracking-tight mt-0.5">—</p>
            </div>
        </div>
        <!-- Log Hari Ini -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/5 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-blue-600 text-2xl">today</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Log Hari Ini</p>
                <p id="auditStatToday" class="text-2xl font-extrabold text-on-surface font-headline tracking-tight mt-0.5">—</p>
            </div>
        </div>
        <!-- Log Minggu Ini -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/5 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-amber-600 text-2xl">date_range</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Log Minggu Ini</p>
                <p id="auditStatWeek" class="text-2xl font-extrabold text-on-surface font-headline tracking-tight mt-0.5">—</p>
            </div>
        </div>
        <!-- Pengguna Aktif -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-green-500/5 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-green-600 text-2xl">group</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Pengguna Aktif</p>
                <p id="auditStatUsers" class="text-2xl font-extrabold text-on-surface font-headline tracking-tight mt-0.5">—</p>
            </div>
        </div>
    </div>

    <!-- Filters and Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 overflow-hidden shadow-sm flex flex-col">
        <!-- Filter Header -->
        <div class="p-6 border-b border-outline-variant/15 bg-surface-container-lowest">
            <div class="flex flex-col lg:flex-row gap-4 items-end">
                <!-- Search -->
                <div class="flex-1 w-full relative">
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Cari Aktivitas</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50">search</span>
                        <input type="text" id="auditSearchInput" placeholder="Cari berdasarkan aksi atau nama pengguna..." class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
                <!-- Date From -->
                <div class="w-full lg:w-44">
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Dari Tanggal</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">calendar_today</span>
                        <input type="date" id="auditDateFrom" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
                <!-- Date To -->
                <div class="w-full lg:w-44">
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Sampai Tanggal</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">event</span>
                        <input type="date" id="auditDateTo" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
                <!-- Table Name Filter -->
                <div class="w-full lg:w-52">
                    <label class="block text-xs font-bold text-on-surface-variant mb-2">Filter Tabel</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 text-lg">table_chart</span>
                        <select id="auditTableFilter" class="w-full bg-surface-container-low border border-outline-variant/30 text-on-surface text-sm rounded-xl pl-10 pr-10 py-2.5 appearance-none focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <option value="">Semua Tabel</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/50 pointer-events-none">expand_more</span>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button onclick="window.applyAuditFilters()" class="bg-primary hover:bg-primary/90 text-white font-bold text-sm py-2.5 px-4 rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                        Terapkan Filter
                    </button>
                    <button onclick="window.resetAuditFilters()" class="text-sm font-semibold text-on-surface-variant hover:text-primary py-2.5 px-3 rounded-xl hover:bg-surface-container-high transition-all">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse table-standardized" data-has-custom-pagination="true">
                <thead>
                    <tr class="bg-surface text-on-surface-variant border-b border-outline-variant/15">
                        <th class="no-col w-12 text-center py-4 px-6 text-[10px] font-extrabold uppercase tracking-wider">No</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Waktu</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Pengguna</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Aksi</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Tabel</th>
                        <th class="py-4 px-6 text-[11px] font-extrabold uppercase tracking-wider">Alamat IP</th>
                    </tr>
                </thead>
                <tbody id="auditLogsTableBody" class="divide-y divide-outline-variant/10 font-body">
                    <tr class="empty-row hidden">
                        <td colspan="6" class="px-6 py-16 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-5xl mb-3 opacity-40 block">shield</span>
                            <p class="font-bold text-base">Tidak ada log aktivitas</p>
                            <p class="text-sm mt-1 opacity-75">Belum ada catatan aktivitas yang ditemukan untuk filter yang dipilih.</p>
                        </td>
                    </tr>
                    <tr class="loading-row">
                        <td colspan="6" class="px-6 py-16 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-3xl mb-2 opacity-50 block animate-spin">autorenew</span>
                            <p class="text-sm font-semibold">Memuat data log...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="auditLogPagination" class="px-6 py-4 border-t border-outline-variant/15 flex items-center justify-between bg-surface-container-low/30 hidden">
            <p id="auditLogPaginationInfo" class="text-xs font-semibold text-on-surface-variant"></p>
            <div class="flex items-center gap-1">
                <button id="auditPrevBtn" onclick="window.prevAuditPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                <div id="auditPageNumbers" class="flex items-center gap-1"></div>
                <button id="auditNextBtn" onclick="window.nextAuditPage()" class="p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // ─── Module State ───────────────────────────────────────────────────────────
    var currentAuditPage = 1;
    var totalAuditPages  = 1;
    var totalAuditItems  = 0;
    var csrfToken        = <?= json_encode(csrf_token()) ?>;

    // ─── Helpers ────────────────────────────────────────────────────────────────
    function escHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, function(m) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
        });
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return '—';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return escHtml(dateStr);
        var day   = String(d.getDate()).padStart(2, '0');
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var year  = d.getFullYear();
        var hours = String(d.getHours()).padStart(2, '0');
        var mins  = String(d.getMinutes()).padStart(2, '0');
        var secs  = String(d.getSeconds()).padStart(2, '0');
        return day + '/' + month + '/' + year + ' ' + hours + ':' + mins + ':' + secs;
    }

    function formatNumber(num) {
        if (num === null || num === undefined) return '—';
        return Number(num).toLocaleString('id-ID');
    }

    // ─── Load Audit Stats ───────────────────────────────────────────────────────
    window.loadAuditStats = function() {
        fetch('/superadmin/audit/stats')
            .then(function(response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function(res) {
                if (res.success) {
                    var data = res.data || {};
                    var elTotal = document.getElementById('auditStatTotal');
                    var elToday = document.getElementById('auditStatToday');
                    var elWeek  = document.getElementById('auditStatWeek');
                    var elUsers = document.getElementById('auditStatUsers');

                    if (elTotal) elTotal.textContent = formatNumber(data.total || 0);
                    if (elToday) elToday.textContent = formatNumber(data.today || 0);
                    if (elWeek)  elWeek.textContent  = formatNumber(data.this_week || 0);
                    if (elUsers) elUsers.textContent  = formatNumber(data.active_users || 0);
                }
            })
            .catch(function(e) {
                console.error('Audit stats fetch error:', e);
            });
    };

    // ─── Load Audit Logs ────────────────────────────────────────────────────────
    window.loadAuditLogs = function(page) {
        if (typeof page === 'undefined' || page === null) page = 1;
        currentAuditPage = page;

        var tbody = document.getElementById('auditLogsTableBody');
        if (!tbody) return;

        // Show loading, hide empty
        var lr = tbody.querySelector('.loading-row');
        var er = tbody.querySelector('.empty-row');
        if (lr) lr.classList.remove('hidden');
        if (er) er.classList.add('hidden');

        // Remove old data rows
        var oldRows = tbody.querySelectorAll('tr.audit-data-row');
        oldRows.forEach(function(r) { r.remove(); });

        // Build query params from filters
        var searchVal    = (document.getElementById('auditSearchInput') || {}).value || '';
        var dateFromVal  = (document.getElementById('auditDateFrom') || {}).value || '';
        var dateToVal    = (document.getElementById('auditDateTo') || {}).value || '';
        var tableNameVal = (document.getElementById('auditTableFilter') || {}).value || '';

        var params = new URLSearchParams();
        params.set('page', page);
        if (searchVal)    params.set('search', searchVal);
        if (dateFromVal)  params.set('date_from', dateFromVal);
        if (dateToVal)    params.set('date_to', dateToVal);
        if (tableNameVal) params.set('table_name', tableNameVal);

        fetch('/superadmin/audit/data?' + params.toString())
            .then(function(response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function(res) {
                // Hide loading
                if (lr) lr.classList.add('hidden');

                if (res.success) {
                    var logs       = res.data || [];
                    totalAuditPages = res.total_pages || 1;
                    totalAuditItems = res.total || 0;
                    var perPage     = res.per_page || 10;

                    // Populate table name filter dropdown (if provided)
                    if (res.table_names && Array.isArray(res.table_names)) {
                        populateTableFilter(res.table_names);
                    }

                    if (logs.length === 0) {
                        if (er) er.classList.remove('hidden');
                    } else {
                        if (er) er.classList.add('hidden');
                        var startNum = (currentAuditPage - 1) * perPage;

                        logs.forEach(function(log, index) {
                            var tr = document.createElement('tr');
                            tr.className = 'audit-data-row hover:bg-surface-container-low/30 transition-colors' + (index % 2 === 1 ? ' bg-surface/30' : '');

                            var rowNum = startNum + index + 1;

                            tr.dataset.rowIndexed = 'true';

                            tr.innerHTML =
                                '<td class="no-col-cell px-6 py-4 text-center font-bold text-xs text-on-surface-variant">' + rowNum + '</td>' +
                                '<td class="px-6 py-4">' +
                                    '<div class="flex flex-col">' +
                                        '<span class="text-sm font-semibold text-on-surface">' + formatDateTime(log.created_at) + '</span>' +
                                    '</div>' +
                                '</td>' +
                                '<td class="px-6 py-4">' +
                                    '<span class="text-sm font-bold text-on-surface">' + escHtml(log.user_name || '—') + '</span>' +
                                '</td>' +
                                '<td class="px-6 py-4">' +
                                    '<span class="text-sm text-on-surface font-medium">' + escHtml(log.action || '—') + '</span>' +
                                '</td>' +
                                '<td class="px-6 py-4">' +
                                    (log.table_name
                                        ? '<span class="text-xs font-extrabold px-3 py-1.5 rounded-full border bg-primary/5 text-primary border-primary/10">' + escHtml(log.table_name) + '</span>'
                                        : '<span class="text-sm text-on-surface-variant">—</span>') +
                                '</td>' +
                                '<td class="px-6 py-4">' +
                                    '<span class="text-sm font-mono text-on-surface-variant font-medium">' + escHtml(log.ip_address || '—') + '</span>' +
                                '</td>';

                            tbody.appendChild(tr);
                        });
                    }

                    // Update pagination
                    renderAuditPagination();
                } else {
                    if (er) er.classList.remove('hidden');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', res.message || 'Gagal memuat data log', 'error');
                    }
                }
            })
            .catch(function(e) {
                if (lr) lr.classList.add('hidden');
                if (er) er.classList.remove('hidden');
                console.error('Audit logs fetch error:', e);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Gagal memuat data audit log: ' + e.message, 'error');
                }
            });
    };

    // ─── Populate Table Name Filter ─────────────────────────────────────────────
    function populateTableFilter(tableNames) {
        var select = document.getElementById('auditTableFilter');
        if (!select) return;

        var currentVal = select.value;
        // Only repopulate if option count differs (avoid flicker)
        if (select.options.length - 1 === tableNames.length) return;

        select.innerHTML = '<option value="">Semua Tabel</option>';
        tableNames.forEach(function(name) {
            var opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            select.appendChild(opt);
        });

        // Restore previous selection if still valid
        if (currentVal) select.value = currentVal;
    }

    // ─── Render Pagination ──────────────────────────────────────────────────────
    function renderAuditPagination() {
        var pagination = document.getElementById('auditLogPagination');
        var infoEl     = document.getElementById('auditLogPaginationInfo');
        var pageNumsEl = document.getElementById('auditPageNumbers');
        var prevBtn    = document.getElementById('auditPrevBtn');
        var nextBtn    = document.getElementById('auditNextBtn');

        if (!pagination) return;

        // Auto-hide when data fits in 1 page
        if (totalAuditPages <= 1) {
            pagination.classList.add('hidden');
            return;
        }

        pagination.classList.remove('hidden');

        // Info text
        if (infoEl) {
            infoEl.textContent = 'Halaman ' + currentAuditPage + ' dari ' + totalAuditPages + ' (' + formatNumber(totalAuditItems) + ' entri)';
        }

        // Prev/Next buttons
        if (prevBtn) prevBtn.disabled = currentAuditPage <= 1;
        if (nextBtn) nextBtn.disabled = currentAuditPage >= totalAuditPages;

        // Page number buttons
        if (pageNumsEl) {
            pageNumsEl.innerHTML = '';

            var startPage = Math.max(1, currentAuditPage - 2);
            var endPage   = Math.min(totalAuditPages, currentAuditPage + 2);

            // Ensure we always show at least 5 pages if available
            if (endPage - startPage < 4) {
                if (startPage === 1) {
                    endPage = Math.min(totalAuditPages, startPage + 4);
                } else if (endPage === totalAuditPages) {
                    startPage = Math.max(1, endPage - 4);
                }
            }

            // First page + ellipsis
            if (startPage > 1) {
                pageNumsEl.appendChild(createPageBtn(1));
                if (startPage > 2) {
                    var dots = document.createElement('span');
                    dots.className = 'px-1 text-on-surface-variant/50 text-xs font-bold';
                    dots.textContent = '…';
                    pageNumsEl.appendChild(dots);
                }
            }

            for (var p = startPage; p <= endPage; p++) {
                pageNumsEl.appendChild(createPageBtn(p));
            }

            // Last page + ellipsis
            if (endPage < totalAuditPages) {
                if (endPage < totalAuditPages - 1) {
                    var dots = document.createElement('span');
                    dots.className = 'px-1 text-on-surface-variant/50 text-xs font-bold';
                    dots.textContent = '…';
                    pageNumsEl.appendChild(dots);
                }
                pageNumsEl.appendChild(createPageBtn(totalAuditPages));
            }
        }
    }

    function createPageBtn(pageNum) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = pageNum;

        if (pageNum === currentAuditPage) {
            btn.className = 'w-9 h-9 flex items-center justify-center rounded-full bg-primary text-white text-xs font-extrabold shadow-sm';
            btn.disabled = true;
        } else {
            btn.className = 'w-9 h-9 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant text-xs font-bold transition-colors';
            btn.onclick = function() { window.loadAuditLogs(pageNum); };
        }
        return btn;
    }

    // ─── Pagination Navigation ──────────────────────────────────────────────────
    window.prevAuditPage = function() {
        if (currentAuditPage > 1) {
            window.loadAuditLogs(currentAuditPage - 1);
        }
    };

    window.nextAuditPage = function() {
        if (currentAuditPage < totalAuditPages) {
            window.loadAuditLogs(currentAuditPage + 1);
        }
    };

    // ─── Filter Functions ───────────────────────────────────────────────────────
    window.applyAuditFilters = function() {
        window.loadAuditLogs(1);
    };

    window.resetAuditFilters = function() {
        var searchEl    = document.getElementById('auditSearchInput');
        var dateFromEl  = document.getElementById('auditDateFrom');
        var dateToEl    = document.getElementById('auditDateTo');
        var tableEl     = document.getElementById('auditTableFilter');

        if (searchEl)   searchEl.value   = '';
        if (dateFromEl) dateFromEl.value  = '';
        if (dateToEl)   dateToEl.value    = '';
        if (tableEl)    tableEl.value     = '';

        window.loadAuditLogs(1);
    };

    // ─── Clear Audit Logs ───────────────────────────────────────────────────────
    window.clearAuditLogs = function() {
        if (typeof Swal === 'undefined') return;

        Swal.fire({
            title: 'Bersihkan Semua Log?',
            html: '<p class="text-sm text-on-surface-variant">Tindakan ini akan <strong class="text-red-600">menghapus seluruh catatan audit log</strong> secara permanen dari database. Data yang sudah dihapus tidak dapat dikembalikan.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Bersihkan Semua',
            cancelButtonText: 'Batal',
            focusCancel: true
        }).then(function(result) {
            if (result.isConfirmed) {
                // Second confirmation for safety
                Swal.fire({
                    title: 'Konfirmasi Terakhir',
                    text: 'Apakah Anda benar-benar yakin? Proses ini tidak dapat dibatalkan.',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#c6c5d4',
                    confirmButtonText: 'Hapus Permanen',
                    cancelButtonText: 'Batal'
                }).then(function(finalResult) {
                    if (finalResult.isConfirmed) {
                        var formData = new FormData();
                        formData.append('csrf_token', csrfToken);

                        Swal.fire({
                            title: 'Membersihkan...',
                            text: 'Sedang menghapus seluruh log audit.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: function() { Swal.showLoading(); }
                        });

                        fetch('/superadmin/audit/clear', {
                            method: 'POST',
                            body: formData
                        })
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: 'Seluruh log audit telah dibersihkan.',
                                    icon: 'success',
                                    confirmButtonColor: '#000666'
                                });
                                window.loadAuditLogs(1);
                                window.loadAuditStats();
                            } else {
                                Swal.fire('Error', data.message || 'Gagal membersihkan log.', 'error');
                            }
                        })
                        .catch(function(err) {
                            console.error('Clear audit logs error:', err);
                            Swal.fire('Error', 'Terjadi kesalahan sistem saat membersihkan log.', 'error');
                        });
                    }
                });
            }
        });
    };

    // ─── Enter key support on search ────────────────────────────────────────────
    var searchInput = document.getElementById('auditSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.applyAuditFilters();
            }
        });
    }

    // ─── Kick off data load ─────────────────────────────────────────────────────
    setTimeout(function() {
        window.loadAuditLogs(1);
        window.loadAuditStats();
    }, 100);

})();
</script>