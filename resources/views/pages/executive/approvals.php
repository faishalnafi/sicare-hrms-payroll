<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-3xl font-extrabold text-primary tracking-tight">Persetujuan Mutasi Jabatan &amp; Akses</h1>
            <p class="text-on-surface-variant font-medium text-sm font-body">Review dan berikan persetujuan (ACC) atas pengajuan mutasi divisi, posisi, dan perubahan akses sistem pengguna.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="bg-primary/5 text-primary text-xs font-extrabold px-3.5 py-2 rounded-full border border-primary/10 flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-[14px]">shield</span>
                Otoritas Eksekutif
            </span>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 overflow-hidden shadow-sm flex flex-col font-body">
        <!-- Tab Headers -->
        <div class="border-b border-outline-variant/15 bg-surface-container-low/30 px-6 py-3 flex gap-4">
            <button id="tabPendingBtn" onclick="switchApprovalTab('pending')" class="px-4 py-2 text-sm font-bold text-primary border-b-2 border-primary transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">pending_actions</span>
                Menunggu Persetujuan
                <span id="pendingBadgeCount" class="bg-primary text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full hidden">0</span>
            </button>
            <button id="tabHistoryBtn" onclick="switchApprovalTab('history')" class="px-4 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">history</span>
                Riwayat Keputusan
            </button>
        </div>

        <!-- Pending Tab Panel -->
        <div id="panelPending" class="p-6">
            <div id="pendingContainer" class="space-y-6">
                <!-- Dynamically populated or loading state -->
                <div class="text-center py-12 text-on-surface-variant" id="pendingLoadingState">
                    <span class="material-symbols-outlined text-4xl animate-spin mb-2 opacity-50">autorenew</span>
                    <p class="text-sm font-semibold">Memuat data pengajuan mutasi...</p>
                </div>
            </div>
        </div>

        <!-- History Tab Panel -->
        <div id="panelHistory" class="p-6 hidden">
            <div id="historyContainer" class="space-y-6">
                <!-- Dynamically populated or loading state -->
                <div class="text-center py-12 text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl mb-2 opacity-35">history</span>
                    <p class="text-sm font-semibold">Belum ada riwayat mutasi.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var allRequests = [];
    var activeTab = 'pending';

    function escHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, function(m) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
        });
    }

    function getRoleBadge(role) {
        var map = {
            'superadmin': 'bg-red-100 text-red-700 border border-red-200',
            'admin':      'bg-purple-100 text-purple-700 border border-purple-200',
            'executive':  'bg-amber-100 text-amber-700 border border-amber-200',
            'hr_ops':     'bg-blue-100 text-blue-700 border border-blue-200',
            'hiring_manager': 'bg-teal-100 text-teal-700 border border-teal-200',
            'recruiter':  'bg-indigo-100 text-indigo-700 border border-indigo-200',
            'employee':   'bg-green-100 text-green-700 border border-green-200',
            'candidate':  'bg-gray-100 text-gray-600 border border-gray-200'
        };
        var cls = map[role] || 'bg-gray-100 text-gray-600';
        return '<span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full inline-block ' + cls + '">' + escHtml(role) + '</span>';
    }

    window.switchApprovalTab = function(tab) {
        activeTab = tab;
        var pendingBtn = document.getElementById('tabPendingBtn');
        var historyBtn = document.getElementById('tabHistoryBtn');
        var panelPending = document.getElementById('panelPending');
        var panelHistory = document.getElementById('panelHistory');

        if (tab === 'pending') {
            pendingBtn.className = "px-4 py-2 text-sm font-bold text-primary border-b-2 border-primary transition-all flex items-center gap-2";
            historyBtn.className = "px-4 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary transition-all flex items-center gap-2";
            panelPending.classList.remove('hidden');
            panelHistory.classList.add('hidden');
        } else {
            pendingBtn.className = "px-4 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary transition-all flex items-center gap-2";
            historyBtn.className = "px-4 py-2 text-sm font-bold text-primary border-b-2 border-primary transition-all flex items-center gap-2";
            panelPending.classList.add('hidden');
            panelHistory.classList.remove('hidden');
        }
        renderLists();
    };

    window.loadApprovalRequests = function() {
        fetch('/executive/approvals/list')
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (res.success) {
                    allRequests = res.data || [];
                    renderLists();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', res.message, 'error');
                }
            })
            .catch(function(err) {
                console.error('Fetch approvals error:', err);
            });
    };

    function renderLists() {
        var pendingContainer = document.getElementById('pendingContainer');
        var historyContainer = document.getElementById('historyContainer');
        var badge = document.getElementById('pendingBadgeCount');

        var pendings = allRequests.filter(function(r) { return r.status === 'PENDING'; });
        var histories = allRequests.filter(function(r) { return r.status !== 'PENDING'; });

        // Update badge count
        if (badge) {
            if (pendings.length > 0) {
                badge.textContent = pendings.length;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        // Render Pendings
        if (pendings.length === 0) {
            pendingContainer.innerHTML = 
                '<div class="text-center py-12 text-on-surface-variant">' +
                    '<span class="material-symbols-outlined text-5xl mb-2 text-outline-variant/60">task_alt</span>' +
                    '<h3 class="text-base font-bold text-on-surface">Semua Beres!</h3>' +
                    '<p class="text-xs text-on-surface-variant mt-1">Tidak ada pengajuan mutasi yang memerlukan persetujuan saat ini.</p>' +
                '</div>';
        } else {
            var html = '';
            pendings.forEach(function(r) {
                var reqName = escHtml(r.req_first_name + (r.req_last_name ? ' ' + r.req_last_name : ''));
                var tarName = escHtml(r.tar_first_name + (r.tar_last_name ? ' ' + r.tar_last_name : ''));
                var tarEmpId = r.tar_employee_id ? escHtml(r.tar_employee_id) : 'Belum Ada NIK';
                
                var oldR = r.parsed_data && r.parsed_data.old ? r.parsed_data.old.role : 'candidate';
                var newR = r.parsed_data && r.parsed_data.new ? r.parsed_data.new.role : 'employee';
                var oldD = r.parsed_data && r.parsed_data.old ? r.parsed_data.old.department_name : 'Tanpa Departemen';
                var newD = r.parsed_data && r.parsed_data.new ? r.parsed_data.new.department_name : 'Tanpa Departemen';
                var oldJ = r.parsed_data && r.parsed_data.old ? r.parsed_data.old.job_title : '—';
                var newJ = r.parsed_data && r.parsed_data.new ? r.parsed_data.new.job_title : '—';
                
                var date = new Date(r.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

                html += 
                    '<div class="p-5 rounded-2xl bg-surface border border-outline-variant/20 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 hover:shadow-md transition-shadow">' +
                        '<div class="space-y-4 flex-1 w-full">' +
                            '<!-- Meta Info -->' +
                            '<div class="flex items-center justify-between md:justify-start gap-4 flex-wrap">' +
                                '<span class="text-xs font-semibold text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-sm text-primary">calendar_today</span> ' + date + '</span>' +
                                '<span class="text-xs font-semibold text-on-surface-variant flex items-center gap-1"><span class="material-symbols-outlined text-sm text-teal-600">person_add</span> Diajukan oleh: ' + reqName + '</span>' +
                            '</div>' +
                            
                            '<!-- Comparison Grid -->' +
                            '<div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4 rounded-xl bg-surface-container-low/50 border border-outline-variant/10">' +
                                '<!-- Subject Employee -->' +
                                '<div class="md:border-r border-outline-variant/10 pr-2">' +
                                    '<span class="text-[10px] font-extrabold uppercase text-primary/75 tracking-wider block mb-1">Karyawan</span>' +
                                    '<h4 class="text-base font-extrabold text-on-surface font-headline leading-tight">' + tarName + '</h4>' +
                                    '<span class="text-xs text-on-surface-variant font-mono mt-1 inline-block">' + tarEmpId + '</span>' +
                                '</div>' +
                                
                                '<!-- Old Position -->' +
                                '<div class="md:border-r border-outline-variant/10 pr-2 space-y-1.5">' +
                                    '<span class="text-[10px] font-extrabold uppercase text-on-surface-variant/75 tracking-wider block">Posisi Lama</span>' +
                                    '<div class="text-xs font-bold text-on-surface capitalize">' + escHtml(oldJ) + '</div>' +
                                    '<div class="text-xs font-medium text-on-surface-variant">' + escHtml(oldD) + '</div>' +
                                    '<div>' + getRoleBadge(oldR) + '</div>' +
                                '</div>' +
                                
                                '<!-- Proposed Position -->' +
                                '<div class="space-y-1.5">' +
                                    '<span class="text-[10px] font-extrabold uppercase text-primary tracking-wider block flex items-center gap-1">Posisi Baru <span class="material-symbols-outlined text-[10px]">trending_up</span></span>' +
                                    '<div class="text-xs font-black text-primary capitalize flex items-center gap-1.5">' +
                                        escHtml(newJ) +
                                    '</div>' +
                                    '<div class="text-xs font-bold text-primary">' + escHtml(newD) + '</div>' +
                                    '<div>' + getRoleBadge(newR) + '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        
                        '<!-- Actions -->' +
                        '<div class="flex items-center gap-3 w-full md:w-auto md:flex-col justify-end flex-shrink-0">' +
                            '<button onclick="actionReject(\'' + r.id + '\')" class="flex-1 md:w-28 px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-full text-xs font-bold border border-red-100 transition-colors flex items-center justify-center gap-1.5 shadow-sm">' +
                                '<span class="material-symbols-outlined text-[16px]">cancel</span> Tolak' +
                            '</button>' +
                            '<button onclick="actionApprove(\'' + r.id + '\', \'' + tarName + '\')" class="flex-1 md:w-28 px-4 py-2.5 bg-primary hover:bg-primary/95 text-white rounded-full text-xs font-bold transition-colors flex items-center justify-center gap-1.5 shadow-sm">' +
                                '<span class="material-symbols-outlined text-[16px]">check_circle</span> Setujui (ACC)' +
                            '</button>' +
                        '</div>' +
                    '</div>';
            });
            pendingContainer.innerHTML = html;
        }

        // Render History
        if (histories.length === 0) {
            historyContainer.innerHTML = 
                '<div class="text-center py-12 text-on-surface-variant">' +
                    '<span class="material-symbols-outlined text-5xl mb-2 text-outline-variant/60">history</span>' +
                    '<h3 class="text-base font-bold text-on-surface">Belum Ada Riwayat</h3>' +
                    '<p class="text-xs text-on-surface-variant mt-1">Seluruh riwayat persetujuan atau penolakan mutasi akan tercatat di sini.</p>' +
                '</div>';
        } else {
            var html = '';
            histories.forEach(function(r) {
                var reqName = escHtml(r.req_first_name + (r.req_last_name ? ' ' + r.req_last_name : ''));
                var tarName = escHtml(r.tar_first_name + (r.tar_last_name ? ' ' + r.tar_last_name : ''));
                var appName = r.app_first_name ? escHtml(r.app_first_name + (r.app_last_name ? ' ' + r.app_last_name : '')) : 'Eksekutif';
                
                var oldR = r.parsed_data && r.parsed_data.old ? r.parsed_data.old.role : 'candidate';
                var newR = r.parsed_data && r.parsed_data.new ? r.parsed_data.new.role : 'employee';
                var oldD = r.parsed_data && r.parsed_data.old ? r.parsed_data.old.department_name : 'Tanpa Departemen';
                var newD = r.parsed_data && r.parsed_data.new ? r.parsed_data.new.department_name : 'Tanpa Departemen';
                var oldJ = r.parsed_data && r.parsed_data.old ? r.parsed_data.old.job_title : '—';
                var newJ = r.parsed_data && r.parsed_data.new ? r.parsed_data.new.job_title : '—';
                
                var date = new Date(r.created_at).toLocaleString('id-ID', { dateStyle: 'medium' });
                var decDate = new Date(r.updated_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

                var badgeClass = '';
                var statusLabel = '';
                var reasonSection = '';

                if (r.status === 'APPROVED') {
                    badgeClass = 'bg-green-50 text-green-700 border border-green-200';
                    statusLabel = '<span class="material-symbols-outlined text-[14px]">check_circle</span> APPROVED';
                } else {
                    badgeClass = 'bg-red-50 text-red-700 border border-red-200';
                    statusLabel = '<span class="material-symbols-outlined text-[14px]">cancel</span> REJECTED';
                    if (r.rejection_reason) {
                        reasonSection = '<div class="p-3 bg-red-50/50 border border-red-100 rounded-xl text-xs text-red-700 mt-2 font-medium flex gap-1.5 items-start"><span class="material-symbols-outlined text-sm mt-0.5">info</span> <span>Alasan Penolakan: ' + escHtml(r.rejection_reason) + '</span></div>';
                    }
                }

                html += 
                    '<div class="p-5 rounded-2xl bg-surface border border-outline-variant/15 flex flex-col gap-4 group hover:bg-surface-container-lowest/30 transition-colors">' +
                        '<div class="flex items-center justify-between flex-wrap gap-3">' +
                            '<div class="flex items-center gap-3">' +
                                '<span class="text-[10px] font-black tracking-wider uppercase inline-flex items-center gap-1 py-1 px-3 rounded-full ' + badgeClass + '">' + statusLabel + '</span>' +
                                '<span class="text-xs text-on-surface-variant font-medium">Keputusan pada: ' + decDate + ' oleh ' + appName + '</span>' +
                            '</div>' +
                            '<span class="text-xs text-on-surface-variant font-mono">Pengajuan: ' + date + ' by ' + reqName + '</span>' +
                        '</div>' +

                        '<div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4 rounded-xl bg-surface-container-low/30 border border-outline-variant/10">' +
                            '<div>' +
                                '<span class="text-[9px] font-extrabold uppercase text-on-surface-variant/70 block mb-0.5">Karyawan</span>' +
                                '<h4 class="text-sm font-extrabold text-on-surface font-headline leading-tight">' + tarName + '</h4>' +
                                '<span class="text-xs text-on-surface-variant font-mono mt-0.5 inline-block">' + (r.tar_employee_id ? escHtml(r.tar_employee_id) : 'Tanpa NIK') + '</span>' +
                            '</div>' +
                            '<div>' +
                                '<span class="text-[9px] font-extrabold uppercase text-on-surface-variant/70 block mb-0.5">Lama</span>' +
                                '<div class="text-xs font-semibold text-on-surface capitalize">' + escHtml(oldJ) + '</div>' +
                                '<div class="text-[11px] text-on-surface-variant">' + escHtml(oldD) + '</div>' +
                                '<div class="mt-1">' + getRoleBadge(oldR) + '</div>' +
                            '</div>' +
                            '<div>' +
                                '<span class="text-[9px] font-extrabold uppercase text-primary/70 block mb-0.5">Baru (Mutasi)</span>' +
                                '<div class="text-xs font-bold text-primary capitalize">' + escHtml(newJ) + '</div>' +
                                '<div class="text-[11px] text-primary">' + escHtml(newD) + '</div>' +
                                '<div class="mt-1">' + getRoleBadge(newR) + '</div>' +
                            '</div>' +
                        '</div>' +
                        reasonSection +
                    '</div>';
            });
            historyContainer.innerHTML = html;
        }
    }

    window.actionApprove = function(id, name) {
        if (typeof Swal === 'undefined') return;

        Swal.fire({
            title: 'Setujui Mutasi?',
            text: 'Apakah Anda yakin ingin menyetujui (ACC) mutasi jabatan dan perubahan role sistem untuk ' + name + '?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000666',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                var fd = new FormData();
                fd.append('id', id);

                fetch('/executive/approvals/approve', { method: 'POST', body: fd })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ title: 'Disetujui', text: data.message, icon: 'success', confirmButtonColor: '#000666' });
                            window.loadApprovalRequests();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(function(err) {
                        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                    });
            }
        });
    };

    window.actionReject = function(id) {
        if (typeof Swal === 'undefined') return;

        Swal.fire({
            title: 'Tolak Mutasi',
            input: 'textarea',
            inputLabel: 'Masukkan Alasan Penolakan:',
            inputPlaceholder: 'Tuliskan alasan penolakan secara mendetail...',
            inputAttributes: {
                'aria-label': 'Tuliskan alasan penolakan secara mendetail'
            },
            showCancelButton: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#c6c5d4',
            confirmButtonText: 'Tolak Pengajuan',
            cancelButtonText: 'Batal',
            preConfirm: function(text) {
                if (!text || text.trim() === '') {
                    Swal.showValidationMessage('Alasan penolakan wajib diisi!');
                }
                return text;
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                var fd = new FormData();
                fd.append('id', id);
                fd.append('rejection_reason', result.value);

                fetch('/executive/approvals/reject', { method: 'POST', body: fd })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ title: 'Ditolak', text: data.message, icon: 'success', confirmButtonColor: '#000666' });
                            window.loadApprovalRequests();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(function(err) {
                        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                    });
            }
        });
    };

    // Auto-load on load
    setTimeout(function() {
        window.loadApprovalRequests();
    }, 100);

})();
</script>
