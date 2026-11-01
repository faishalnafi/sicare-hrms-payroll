<?php
use App\Config\Database;
use App\Services\ViewHelper;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();

// Fetch active & closed job requisitions for filter dropdown
$stmtJobs = $db->query("
    SELECT id, title, req_number, status, headcount
    FROM job_requisitions
    WHERE status IN ('published', 'closed', 'approved')
    ORDER BY created_at DESC
");
$jobsList = $stmtJobs->fetchAll(PDO::FETCH_ASSOC);

// Fetch all candidates/applications
$stmtApps = $db->query("
    SELECT ja.*, r.title AS job_title, r.req_number AS job_req_number
    FROM job_applications ja
    LEFT JOIN job_requisitions r ON ja.requisition_id = r.id
    ORDER BY ja.created_at DESC
");
$applications = $stmtApps->fetchAll(PDO::FETCH_ASSOC);

// Define ATS stages
$stages = [
    'submitted' => [
        'title' => 'Submitted',
        'color' => 'border-blue-200 text-blue-700 bg-blue-50/50',
        'badge' => 'bg-blue-100 text-blue-800',
        'icon' => 'inbox',
        'apps' => []
    ],
    'screening' => [
        'title' => 'Screening',
        'color' => 'border-amber-200 text-amber-700 bg-amber-50/50',
        'badge' => 'bg-amber-100 text-amber-800',
        'icon' => 'find_in_page',
        'apps' => []
    ],
    'shortlisted' => [
        'title' => 'Shortlisted',
        'color' => 'border-indigo-200 text-indigo-700 bg-indigo-50/50',
        'badge' => 'bg-indigo-100 text-indigo-800',
        'icon' => 'thumb_up',
        'apps' => []
    ],
    'interview' => [
        'title' => 'Interview',
        'color' => 'border-purple-200 text-purple-700 bg-purple-50/50',
        'badge' => 'bg-purple-100 text-purple-800',
        'icon' => 'forum',
        'apps' => []
    ],
    'offering' => [
        'title' => 'Offering',
        'color' => 'border-orange-200 text-orange-700 bg-orange-50/50',
        'badge' => 'bg-orange-100 text-orange-800',
        'icon' => 'drafts',
        'apps' => []
    ],
    'hired' => [
        'title' => 'Hired',
        'color' => 'border-emerald-200 text-emerald-700 bg-emerald-50/50',
        'badge' => 'bg-emerald-100 text-emerald-800',
        'icon' => 'check_circle',
        'apps' => []
    ],
    'rejected' => [
        'title' => 'Rejected',
        'color' => 'border-rose-200 text-rose-700 bg-rose-50/50',
        'badge' => 'bg-rose-100 text-rose-800',
        'icon' => 'cancel',
        'apps' => []
    ]
];

// Group applications by status
foreach ($applications as $app) {
    $status = $app['status'] ?? 'submitted';
    if (!isset($stages[$status])) {
        $status = 'submitted';
    }
    $stages[$status]['apps'][] = $app;
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl lg:text-3xl font-extrabold text-primary tracking-tight">Pipeline ATS Pelamar</h1>
            <p class="text-on-surface-variant font-medium text-xs lg:text-sm">Kelola dan pantau tahapan seleksi kandidat secara interaktif dengan drag-and-drop.</p>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-surface-container-lowest border border-outline-variant/15 p-4 rounded-2xl shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
            <!-- Filter Job Requisition -->
            <div class="relative min-w-[240px]">
                <select id="filterJobReq" onchange="filterAtsBoard()" class="w-full pl-3 pr-8 py-2 rounded-xl border border-outline-variant/30 bg-surface-container-low focus:outline-none focus:border-primary text-xs font-bold text-gray-700 appearance-none cursor-pointer">
                    <option value="">Semua Posisi Lowongan</option>
                    <?php foreach ($jobsList as $job): ?>
                        <option value="<?= htmlspecialchars($job['id']) ?>">
                            <?= htmlspecialchars($job['req_number']) ?> - <?= htmlspecialchars($job['title']) ?> (<?= htmlspecialchars($job['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none text-lg">arrow_drop_down</span>
            </div>

            <!-- Search Input -->
            <div class="relative min-w-[200px]">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base">search</span>
                <input type="text" id="searchCandidate" onkeyup="filterAtsBoard()" placeholder="Cari nama kandidat..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-outline-variant/30 bg-surface-container-low focus:outline-none focus:border-primary text-xs font-semibold text-on-surface" />
            </div>
        </div>
        <div class="text-xs text-on-surface-variant font-semibold flex items-center gap-1.5 shrink-0">
            <span class="material-symbols-outlined text-sm text-primary">groups</span>
            <span id="filteredCountText">Total <?= count($applications) ?> Pelamar Terdaftar</span>
        </div>
    </div>

    <!-- Kanban Board Wrapper -->
    <div class="flex gap-4 overflow-x-auto pb-6 items-start scrollbar-thin select-none">
        <?php foreach ($stages as $statusKey => $stage): ?>
            <!-- Column -->
            <div class="flex-1 min-w-[290px] max-w-[340px] bg-surface-container-lowest border border-outline-variant/10 rounded-2xl p-4 flex flex-col max-h-[75vh]" 
                 ondragover="allowDrop(event)" 
                 ondrop="drop(event, '<?= $statusKey ?>')">
                
                <!-- Column Header -->
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-outline-variant/10">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg <?= $stage['color'] ?> p-1.5 rounded-lg font-normal"><?= $stage['icon'] ?></span>
                        <h3 class="font-bold text-xs text-on-surface uppercase tracking-wider"><?= $stage['title'] ?></h3>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-extrabold <?= $stage['badge'] ?>" id="count-<?= $statusKey ?>">
                        <?= count($stage['apps']) ?>
                    </span>
                </div>

                <!-- Column Content / Cards Container -->
                <div class="space-y-3 overflow-y-auto pr-1 flex-grow scrollbar-thin min-h-[250px]" id="column-<?= $statusKey ?>">
                    <?php if (empty($stage['apps'])): ?>
                        <div class="empty-state-placeholder py-8 text-center text-on-surface-variant/40 text-[10px] font-bold border border-dashed border-outline-variant/10 rounded-xl">
                            Drag & drop di sini
                        </div>
                    <?php endif; ?>

                    <?php foreach ($stage['apps'] as $app): ?>
                        <!-- Candidate Card -->
                        <div class="candidate-card bg-white p-4 rounded-xl border border-outline-variant/10 hover:border-primary/30 hover:shadow-md transition-all cursor-grab active:cursor-grabbing"
                             id="card-<?= htmlspecialchars($app['id']) ?>"
                             draggable="true"
                             ondragstart="drag(event)"
                             ondragend="dragEnd(event)"
                             data-app-id="<?= htmlspecialchars($app['id']) ?>"
                             data-requisition-id="<?= htmlspecialchars($app['requisition_id']) ?>"
                             data-candidate-name="<?= htmlspecialchars(strtolower($app['candidate_name'])) ?>"
                             data-status="<?= $statusKey ?>">
                            
                            <div class="space-y-2">
                                <!-- Requisition Number & Date -->
                                <div class="flex items-center justify-between text-[9px] font-bold text-primary">
                                    <span><?= htmlspecialchars($app['job_req_number'] ?? 'REQ') ?></span>
                                    <span class="text-neutral-400 font-medium"><?= date('d M Y', strtotime($app['created_at'])) ?></span>
                                </div>

                                <!-- Candidate Name & Job Title -->
                                <div>
                                    <h4 class="font-bold text-xs text-[#0c145e] line-clamp-1"><?= htmlspecialchars($app['candidate_name']) ?></h4>
                                    <p class="text-[10px] text-gray-500 font-medium line-clamp-1" title="<?= htmlspecialchars($app['job_title'] ?? '') ?>"><?= htmlspecialchars($app['job_title'] ?? 'Posisi Terhapus') ?></p>
                                </div>

                                <!-- Contact Info -->
                                <div class="text-[10px] text-gray-600 space-y-0.5 pt-1 border-t border-gray-50">
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[12px] text-gray-400">mail</span>
                                        <span class="truncate" title="<?= htmlspecialchars($app['candidate_email']) ?>"><?= htmlspecialchars($app['candidate_email']) ?></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[12px] text-gray-400">phone</span>
                                        <span><?= htmlspecialchars($app['candidate_phone'] ?? '-') ?></span>
                                    </div>
                                </div>

                                <!-- File Attachments (CV/Resume, Portfolio) -->
                                <div class="flex items-center gap-2 pt-2">
                                    <?php if (!empty($app['resume_path'])): ?>
                                        <a href="<?= htmlspecialchars($app['resume_path']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[9px] bg-red-50 hover:bg-red-100 text-red-700 font-bold px-2 py-1 rounded border border-red-100 transition-colors">
                                            <span class="material-symbols-outlined text-[11px] font-bold">picture_as_pdf</span> CV/Resume
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($app['portfolio_url'])): ?>
                                        <a href="<?= htmlspecialchars($app['portfolio_url']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[9px] bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold px-2 py-1 rounded border border-blue-100 transition-colors">
                                            <span class="material-symbols-outlined text-[11px] font-bold">link</span> Portfolio
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons (Fallback & Quick Stage Transition) -->
                                <div class="flex items-center gap-1.5 pt-2 mt-1 border-t border-gray-100 justify-end">
                                    <!-- Delete Button for Rejected Cards -->
                                    <?php if ($statusKey === 'rejected'): ?>
                                        <button type="button" onclick="deleteApplication('<?= htmlspecialchars($app['id']) ?>', '<?= htmlspecialchars(addslashes($app['candidate_name'])) ?>')" class="p-1 rounded bg-gray-100 hover:bg-red-50 text-gray-500 hover:text-red-700 border border-gray-200 hover:border-red-200 transition-colors cursor-pointer" title="Hapus Berkas Pelamar">
                                            <span class="material-symbols-outlined text-[13px] font-bold">delete</span>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Reject Quick Button -->
                                    <?php if ($statusKey !== 'rejected' && $statusKey !== 'hired'): ?>
                                        <button type="button" onclick="quickReject('<?= htmlspecialchars($app['id']) ?>', '<?= htmlspecialchars(addslashes($app['candidate_name'])) ?>')" class="p-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition-colors cursor-pointer" title="Tolak Pelamar">
                                            <span class="material-symbols-outlined text-[13px] font-bold">cancel</span>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Promote Quick Button -->
                                    <?php 
                                        $nextStages = [
                                            'submitted' => 'screening',
                                            'screening' => 'shortlisted',
                                            'shortlisted' => 'interview',
                                            'interview' => 'offering',
                                            'offering' => 'hired'
                                        ];
                                        if (isset($nextStages[$statusKey])):
                                            $nextKey = $nextStages[$statusKey];
                                            $nextLabel = $stages[$nextKey]['title'];
                                    ?>
                                        <button type="button" onclick="quickPromote('<?= htmlspecialchars($app['id']) ?>', '<?= $nextKey ?>')" class="inline-flex items-center gap-1 text-[9px] bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2 py-1 rounded shadow-xs transition-colors cursor-pointer" title="Pindahkan ke <?= $nextLabel ?>">
                                            <span>Lanjut</span> <span class="material-symbols-outlined text-[11px] font-bold">arrow_forward</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function filterAtsBoard() {
        const selectedReqId = document.getElementById('filterJobReq').value;
        const searchQuery = document.getElementById('searchCandidate').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.candidate-card');
        
        let visibleCount = 0;
        
        // Dictionary to count visible cards per stage
        const stageCounts = {
            submitted: 0,
            screening: 0,
            shortlisted: 0,
            interview: 0,
            offering: 0,
            hired: 0,
            rejected: 0
        };

        cards.forEach(card => {
            const reqId = card.getAttribute('data-requisition-id');
            const candidateName = card.getAttribute('data-candidate-name');
            const stage = card.getAttribute('data-status');

            const matchesJob = selectedReqId === '' || reqId === selectedReqId;
            const matchesSearch = searchQuery === '' || candidateName.includes(searchQuery);

            if (matchesJob && matchesSearch) {
                card.style.display = 'block';
                visibleCount++;
                if (stageCounts[stage] !== undefined) {
                    stageCounts[stage]++;
                }
            } else {
                card.style.display = 'none';
            }
        });

        // Update counts in column badges
        for (const [stageKey, count] of Object.entries(stageCounts)) {
            const badge = document.getElementById('count-' + stageKey);
            if (badge) {
                badge.innerText = count;
            }
            
            // Check if column is empty
            const column = document.getElementById('column-' + stageKey);
            if (column) {
                let placeholder = column.querySelector('.empty-state-placeholder');
                // Count visible children cards
                const visibleCards = column.querySelectorAll(`.candidate-card[data-status="${stageKey}"]:not([style*="display: none"])`);
                
                if (visibleCards.length === 0) {
                    if (!placeholder) {
                        placeholder = document.createElement('div');
                        placeholder.className = 'empty-state-placeholder py-8 text-center text-on-surface-variant/40 text-[10px] font-bold border border-dashed border-outline-variant/10 rounded-xl';
                        placeholder.innerText = 'Drag & drop di sini';
                        column.appendChild(placeholder);
                    } else {
                        placeholder.style.display = 'block';
                    }
                } else {
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                }
            }
        }

        // Update total counter
        document.getElementById('filteredCountText').innerText = `Menampilkan ${visibleCount} dari <?= count($applications) ?> Pelamar`;
    }

    // HTML5 Drag and Drop handlers
    function allowDrop(ev) {
        ev.preventDefault();
        // Highlight background when hovering
        const targetColumn = ev.target.closest('[ondragover]');
        if (targetColumn) {
            targetColumn.classList.add('bg-surface-container-high/40');
        }
    }

    // Reset styles on drag leave
    document.addEventListener('dragleave', function(ev) {
        const targetColumn = ev.target.closest('[ondragover]');
        if (targetColumn && !targetColumn.contains(ev.relatedTarget)) {
            targetColumn.classList.remove('bg-surface-container-high/40');
        }
    });

    function drag(ev) {
        ev.dataTransfer.setData("text/plain", ev.target.id);
        ev.target.classList.add('opacity-40');
    }

    function dragEnd(ev) {
        ev.target.classList.remove('opacity-40');
        const columns = document.querySelectorAll('[ondragover]');
        columns.forEach(col => col.classList.remove('bg-surface-container-high/40'));
    }

    function drop(ev, newStatus) {
        ev.preventDefault();
        const columns = document.querySelectorAll('[ondragover]');
        columns.forEach(col => col.classList.remove('bg-surface-container-high/40'));

        const id = ev.dataTransfer.getData("text/plain");
        const card = document.getElementById(id);
        if (!card) return;
        
        const oldStatus = card.getAttribute('data-status');
        if (oldStatus === newStatus) return;

        const appId = card.getAttribute('data-app-id');
        updateCandidateStatus(appId, newStatus);
    }

    // Fallback/Quick click handlers
    function quickPromote(appId, nextStatus) {
        updateCandidateStatus(appId, nextStatus);
    }

    function quickReject(appId, candidateName) {
        Swal.fire({
            title: 'Tolak Pelamar?',
            text: `Apakah Anda yakin ingin menolak kandidat ${candidateName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Tolak!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                updateCandidateStatus(appId, 'rejected');
            }
        });
    }

    // Delete application
    function deleteApplication(appId, candidateName) {
        Swal.fire({
            title: 'Hapus Berkas Pelamar?',
            text: `Hapus data pelamar ${candidateName} dan bersihkan berkas CV dari sistem secara permanen?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Permanen',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('application_id', appId);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                Swal.showLoading();
                fetch('/recruiter/ats/delete-application', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Terhapus!',
                            text: data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            if (window.loadPage) window.loadPage('/recruiter/ats');
                            else window.location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
            }
        });
    }

    // Update status fetch request
    function updateCandidateStatus(appId, newStatus) {
        const formData = new FormData();
        formData.append('application_id', appId);
        formData.append('status', newStatus);
        formData.append('csrf_token', '<?= csrf_token() ?>');

        Swal.fire({
            title: 'Memproses...',
            text: 'Memperbarui tahapan kandidat...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('/recruiter/ats/update-status', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: data.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    if (window.loadPage) window.loadPage('/recruiter/ats');
                    else window.location.reload();
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Gagal menyambung ke server.', 'error');
        });
    }
</script>