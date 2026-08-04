<?php
use App\Config\Database;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();

// Fetch scheduled interviews
$stmtInts = $db->query("
    SELECT ji.*, ja.candidate_name, ja.candidate_email, ja.candidate_phone, r.title AS job_title,
           u.first_name AS interviewer_first, u.last_name AS interviewer_last, u.email AS interviewer_email
    FROM job_interviews ji
    LEFT JOIN job_applications ja ON ji.application_id = ja.id
    LEFT JOIN job_requisitions r ON ja.requisition_id = r.id
    LEFT JOIN users u ON ji.interviewer_id = u.id
    ORDER BY ji.interview_date DESC, ji.interview_time DESC
");
$interviews = $stmtInts->fetchAll(PDO::FETCH_ASSOC);

// Fetch candidates eligible for interview (shortlisted or already in interview stage)
$stmtCand = $db->query("
    SELECT ja.id, ja.candidate_name, r.title AS job_title, r.req_number
    FROM job_applications ja
    LEFT JOIN job_requisitions r ON ja.requisition_id = r.id
    WHERE ja.status IN ('shortlisted', 'interview')
    ORDER BY ja.candidate_name ASC
");
$candidates = $stmtCand->fetchAll(PDO::FETCH_ASSOC);

// Fetch all hiring managers/interviewer options
$stmtMgrs = $db->query("
    SELECT id, first_name, last_name, email
    FROM users
    WHERE role IN ('manager', 'hiring_manager')
    ORDER BY first_name ASC, last_name ASC
");
$interviewers = $stmtMgrs->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl lg:text-3xl font-extrabold text-primary tracking-tight">Penjadwalan & Sesi Wawancara</h1>
            <p class="text-on-surface-variant font-medium text-xs lg:text-sm">Jadwalkan pertemuan wawancara antara pelamar terpilih dengan Hiring Manager serta pantau statusnya.</p>
        </div>
        <button type="button" onclick="openScheduleModal()" class="inline-flex items-center gap-1.5 bg-[#000666] hover:bg-[#0c145e] text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition-all cursor-pointer">
            <span class="material-symbols-outlined text-[16px]">calendar_today</span> Buat Jadwal Baru
        </button>
    </div>

    <!-- Stats summary cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-amber-200/50 bg-amber-50/20 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-amber-800 uppercase tracking-wider block">Menunggu Konfirmasi</span>
                <span class="text-2xl font-extrabold text-amber-700">
                    <?= count(array_filter($interviews, fn($i) => $i['candidate_status'] === 'pending')) ?>
                </span>
            </div>
            <span class="material-symbols-outlined text-amber-600 text-2xl">hourglass_empty</span>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-emerald-200/50 bg-emerald-50/20 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider block">Jadwal Disetujui</span>
                <span class="text-2xl font-extrabold text-emerald-700">
                    <?= count(array_filter($interviews, fn($i) => $i['candidate_status'] === 'accepted')) ?>
                </span>
            </div>
            <span class="material-symbols-outlined text-emerald-600 text-2xl">check_circle</span>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-rose-200/50 bg-rose-50/20 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-rose-800 uppercase tracking-wider block">Minta Reschedule</span>
                <span class="text-2xl font-extrabold text-rose-700">
                    <?= count(array_filter($interviews, fn($i) => $i['candidate_status'] === 'reschedule_requested')) ?>
                </span>
            </div>
            <span class="material-symbols-outlined text-rose-600 text-2xl">update</span>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-blue-200/50 bg-blue-50/20 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-blue-800 uppercase tracking-wider block">Total Wawancara</span>
                <span class="text-2xl font-extrabold text-blue-700"><?= count($interviews) ?></span>
            </div>
            <span class="material-symbols-outlined text-blue-600 text-2xl">forum</span>
        </div>
    </div>

    <!-- Interviews List -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-4 md:p-6 border-b border-outline-variant/15 flex justify-between items-center">
            <h3 class="font-bold text-sm text-on-surface">Riwayat & Daftar Sesi Wawancara</h3>
            <span class="text-xs text-on-surface-variant font-medium">Total <?= count($interviews) ?> sesi</span>
        </div>

        <?php if (empty($interviews)): ?>
            <div class="p-12 text-center text-on-surface-variant space-y-2">
                <span class="material-symbols-outlined text-5xl text-outline-variant/60">event_busy</span>
                <p class="text-sm font-bold text-on-surface">Belum ada sesi wawancara yang dijadwalkan</p>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">Gunakan tombol di pojok kanan atas untuk membuat jadwal sesi wawancara dengan kandidat terpilih.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-on-surface-variant font-bold border-b border-outline-variant/15 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Kandidat & Posisi</th>
                            <th class="py-3.5 px-4">Pewawancara (Manager)</th>
                            <th class="py-3.5 px-4">Waktu & Tempat</th>
                            <th class="py-3.5 px-4 text-center">Status Kandidat</th>
                            <th class="py-3.5 px-4">Hasil & Keputusan Wawancara</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 font-medium">
                        <?php foreach ($interviews as $i): ?>
                            <tr class="hover:bg-surface-container-lowest">
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-on-surface block"><?= htmlspecialchars($i['candidate_name'] ?? 'Kandidat Terhapus') ?></span>
                                    <span class="text-[10px] text-gray-500 block"><?= htmlspecialchars($i['candidate_email']) ?> • <?= htmlspecialchars($i['candidate_phone'] ?? '-') ?></span>
                                    <span class="text-[10px] text-primary font-bold mt-0.5 block"><?= htmlspecialchars($i['job_title'] ?? '') ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-on-surface block"><?= htmlspecialchars($i['interviewer_first'] . ' ' . $i['interviewer_last']) ?></span>
                                    <span class="text-[10px] text-gray-500 block"><?= htmlspecialchars($i['interviewer_email']) ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-on-surface block"><?= date('d F Y', strtotime($i['interview_date'])) ?></span>
                                    <span class="text-[10px] text-gray-600 font-semibold block">Pukul: <?= htmlspecialchars($i['interview_time']) ?> WIB</span>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <?php if (($i['interview_type'] ?? 'online') === 'online'): ?>
                                            <span class="px-1.5 py-0.5 rounded font-extrabold text-[8px] bg-blue-50 text-blue-700 border border-blue-100 flex items-center gap-0.5 whitespace-nowrap">
                                                <span class="material-symbols-outlined text-[10px] font-normal">video_call</span> ONLINE (Jitsi)
                                            </span>
                                            <a href="<?= htmlspecialchars($i['location_or_link']) ?>" target="_blank" class="text-blue-600 hover:underline font-bold text-[9px] flex items-center gap-0.5">
                                                 Gabung
                                            </a>
                                        <?php else: ?>
                                            <span class="px-1.5 py-0.5 rounded font-extrabold text-[8px] bg-amber-50 text-amber-700 border border-amber-100 flex items-center gap-0.5 whitespace-nowrap">
                                                <span class="material-symbols-outlined text-[10px] font-normal">location_on</span> OFFLINE
                                            </span>
                                            <span class="text-[9px] text-gray-500 truncate max-w-[120px]" title="<?= htmlspecialchars($i['location_or_link']) ?>">
                                                <?= htmlspecialchars($i['location_or_link']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php if ($i['candidate_status'] === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200" title="Kandidat belum merespon jadwal">
                                            <span class="material-symbols-outlined text-xs">hourglass_empty</span> Menunggu Respon
                                        </span>
                                    <?php elseif ($i['candidate_status'] === 'accepted'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200">
                                            <span class="material-symbols-outlined text-xs">check_circle</span> Disetujui
                                        </span>
                                    <?php elseif ($i['candidate_status'] === 'reschedule_requested'): ?>
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                <span class="material-symbols-outlined text-xs">update</span> Minta Reschedule
                                            </span>
                                            <?php if (!empty($i['candidate_notes'])): ?>
                                                <p class="text-[9px] text-rose-600 italic block font-sans max-w-[150px] mx-auto">"<?= htmlspecialchars($i['candidate_notes']) ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($i['manager_decision'] === 'pending'): ?>
                                        <span class="text-neutral-400 italic text-[11px] flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs">schedule</span> Belum Ada Penilaian
                                        </span>
                                    <?php else: ?>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                <?php if ($i['manager_decision'] === 'pass'): ?>
                                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded font-bold bg-emerald-100 text-emerald-800 text-[10px]">
                                                        LULUS (Pass)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded font-bold bg-rose-100 text-rose-800 text-[10px]">
                                                        GAGAL (Fail)
                                                    </span>
                                                <?php endif; ?>
                                                <span class="text-xs font-extrabold text-primary">Skor: <?= (int)$i['manager_score'] ?>/100</span>
                                            </div>
                                            <p class="text-[10px] text-gray-600 font-sans italic max-w-xs">"<?= htmlspecialchars($i['manager_feedback'] ?? '-') ?>"</p>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Jadwal Baru -->
<div id="scheduleModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-white border border-outline-variant/20 rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="scheduleModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-[#000666] font-headline flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">calendar_today</span> Atur Jadwal Wawancara Baru
            </h3>
            <button type="button" onclick="closeScheduleModal()" class="p-1.5 rounded-full text-gray-500 hover:bg-gray-100 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">close</span>
            </button>
        </div>
        
        <!-- Modal Form Body -->
        <form id="scheduleForm" onsubmit="saveSchedule(event)" class="p-6 space-y-4 text-xs font-semibold text-on-surface overflow-y-auto flex-grow">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="space-y-1">
                <label class="block text-gray-700">Pilih Pelamar / Kandidat <span class="text-red-600">*</span></label>
                <select name="application_id" required class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-bold text-gray-800">
                    <option value="">-- Pilih Pelamar --</option>
                    <?php foreach ($candidates as $c): ?>
                        <option value="<?= htmlspecialchars($c['id']) ?>">
                            <?= htmlspecialchars($c['candidate_name']) ?> (Lowongan: <?= htmlspecialchars($c['job_title']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="space-y-1">
                <label class="block text-gray-700">Pilih Pewawancara (Hiring Manager) <span class="text-red-600">*</span></label>
                <select name="interviewer_id" required class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-bold text-gray-800">
                    <option value="">-- Pilih Hiring Manager --</option>
                    <?php foreach ($interviewers as $m): ?>
                        <option value="<?= htmlspecialchars($m['id']) ?>">
                            <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?> (<?= htmlspecialchars($m['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-gray-700">Tanggal Wawancara <span class="text-red-600">*</span></label>
                    <input type="date" name="interview_date" required min="<?= date('Y-m-d') ?>" class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs text-gray-800 font-bold" />
                </div>
                <div class="space-y-1">
                    <label class="block text-gray-700">Waktu / Jam (WIB) <span class="text-red-600">*</span></label>
                    <input type="text" name="interview_time" placeholder="contoh: 10:00 - 11:00" required class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs text-gray-800" />
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-gray-700">Tipe Wawancara <span class="text-red-600">*</span></label>
                <select name="interview_type" id="sched_interview_type" onchange="toggleInterviewType()" class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-bold text-gray-800 cursor-pointer">
                    <option value="online">Online (Video Meeting - Jitsi)</option>
                    <option value="offline">Offline (Tatap Muka)</option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="block text-gray-700 font-bold" id="linkLabel">Tautan Rapat Video (Jitsi Meet) <span class="text-red-600">*</span></label>
                <input type="text" name="location_or_link" id="linkInput" required class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs text-gray-800" />
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-gray-200">
                <button type="button" onclick="closeScheduleModal()" class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 transition-all text-xs font-bold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-[#000666] hover:bg-[#0c145e] text-white rounded-xl font-bold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">send</span> Kirim Undangan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function generateJitsiLink() {
        const randomId = Math.random().toString(36).substring(2, 10).toUpperCase();
        return `https://meet.jit.si/siCare-Interview-${randomId}`;
    }

    function toggleInterviewType() {
        const type = document.getElementById('sched_interview_type').value;
        const linkLabel = document.getElementById('linkLabel');
        const linkInput = document.getElementById('linkInput');

        if (type === 'online') {
            if (linkLabel) linkLabel.innerHTML = 'Tautan Rapat Video (Jitsi Meet) <span class="text-red-600">*</span>';
            linkInput.value = generateJitsiLink();
            linkInput.placeholder = 'https://meet.jit.si/...';
        } else {
            if (linkLabel) linkLabel.innerHTML = 'Lokasi Pertemuan / Ruangan Kantor <span class="text-red-600">*</span>';
            linkInput.value = '';
            linkInput.placeholder = 'contoh: Ruang Rapat Lt. 2, Gedung Utama';
        }
    }

    function openScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        const container = document.getElementById('scheduleModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
            document.getElementById('sched_interview_type').value = 'online';
            toggleInterviewType();
        }, 10);
    }

    function closeScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        const container = document.getElementById('scheduleModalContainer');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function saveSchedule(e) {
        e.preventDefault();
        const form = document.getElementById('scheduleForm');
        const formData = new FormData(form);

        Swal.fire({ title: 'Mengirim Undangan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('/recruiter/interviews/store', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeScheduleModal();
                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                    if (window.loadPage) window.loadPage('/recruiter/interviews');
                    else window.location.reload();
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
    }
</script>