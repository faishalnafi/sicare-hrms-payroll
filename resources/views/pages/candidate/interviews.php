<?php
use App\Config\Database;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();
$userEmail = $_SESSION['email'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

// Fetch scheduled interviews for this candidate
$stmtInts = $db->prepare("
    SELECT ji.*, r.title AS job_title, r.req_number,
           u.first_name AS interviewer_first, u.last_name AS interviewer_last, u.email AS interviewer_email
    FROM job_interviews ji
    LEFT JOIN job_applications ja ON ji.application_id = ja.id
    LEFT JOIN job_requisitions r ON ja.requisition_id = r.id
    LEFT JOIN users u ON ji.interviewer_id = u.id
    WHERE ja.candidate_id = :uid OR ja.candidate_email = :email
    ORDER BY ji.interview_date DESC, ji.interview_time DESC
");
$stmtInts->execute(['uid' => $userId, 'email' => $userEmail]);
$myInterviews = $stmtInts->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl lg:text-3xl font-extrabold text-primary tracking-tight">Jadwal Wawancara Saya</h1>
            <p class="text-on-surface-variant font-medium text-xs lg:text-sm">Konfirmasi kehadiran atau ajukan reschedule untuk undangan wawancara aktif dari tim Rekruter.</p>
        </div>
    </div>

    <!-- Sesi Wawancara Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if (empty($myInterviews)): ?>
            <div class="col-span-full bg-surface-container-lowest p-12 rounded-3xl border border-outline-variant/15 text-center space-y-3 shadow-2xs">
                <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-3xl">calendar_today</span>
                </div>
                <h3 class="text-base font-headline font-bold text-on-surface">Belum Ada Undangan Wawancara</h3>
                <p class="text-xs text-on-surface-variant max-w-md mx-auto">Saat ini belum ada jadwal wawancara yang ditetapkan untuk lamaran pekerjaan Anda. Kami akan menghubungi Anda jika berkas Anda lolos saringan awal.</p>
            </div>
        <?php else: ?>
            <?php foreach ($myInterviews as $i): ?>
                <?php 
                    $cardBorder = 'border-gray-100 hover:border-blue-300';
                    $statusBadge = 'bg-amber-100 text-amber-800';
                    $statusText = 'Menunggu Konfirmasi';
                    $statusIcon = 'hourglass_empty';

                    if ($i['candidate_status'] === 'accepted') {
                        $cardBorder = 'border-green-100 hover:border-green-300';
                        $statusBadge = 'bg-green-100 text-green-800';
                        $statusText = 'Jadwal Disetujui';
                        $statusIcon = 'check_circle';
                    } elseif ($i['candidate_status'] === 'reschedule_requested') {
                        $cardBorder = 'border-rose-100 hover:border-rose-300';
                        $statusBadge = 'bg-rose-100 text-rose-800';
                        $statusText = 'Pengajuan Reschedule';
                        $statusIcon = 'update';
                    }
                ?>
                <div class="bg-white p-6 rounded-3xl border <?= $cardBorder ?> shadow-2xs transition-all flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-primary uppercase bg-blue-50 px-2 py-0.5 rounded"><?= htmlspecialchars($i['req_number']) ?></span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $statusBadge ?>">
                                <span class="material-symbols-outlined text-xs font-normal"><?= $statusIcon ?></span> <?= $statusText ?>
                            </span>
                        </div>

                        <div>
                            <div class="flex items-center gap-1.5 mb-1">
                                <?php if (($i['interview_type'] ?? 'online') === 'online'): ?>
                                    <span class="px-1.5 py-0.5 rounded font-extrabold text-[8px] bg-blue-50 text-blue-700 border border-blue-100 flex items-center gap-0.5">
                                        <span class="material-symbols-outlined text-[10px] font-normal">video_call</span> ONLINE (Jitsi Meet)
                                    </span>
                                <?php else: ?>
                                    <span class="px-1.5 py-0.5 rounded font-extrabold text-[8px] bg-amber-50 text-amber-700 border border-amber-100 flex items-center gap-0.5">
                                        <span class="material-symbols-outlined text-[10px] font-normal">location_on</span> OFFLINE (Tatap Muka)
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-sm font-bold text-on-surface"><?= htmlspecialchars($i['job_title']) ?></h3>
                            <p class="text-[11px] text-gray-500 font-medium">Pewawancara: <b><?= htmlspecialchars($i['interviewer_first'] . ' ' . $i['interviewer_last']) ?></b> (Hiring Manager)</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-gray-50 text-[10px] text-gray-700 font-semibold font-sans">
                            <div>
                                <span class="text-neutral-400 block font-medium">TANGGAL</span>
                                <span class="text-on-surface"><?= date('d F Y', strtotime($i['interview_date'])) ?></span>
                            </div>
                            <div>
                                <span class="text-neutral-400 block font-medium">WAKTU (WIB)</span>
                                <span class="text-on-surface"><?= htmlspecialchars($i['interview_time']) ?> WIB</span>
                            </div>
                        </div>

                        <div class="pt-1">
                            <span class="text-[10px] text-neutral-400 block font-bold uppercase tracking-wider mb-1">Lokasi / Tautan Pertemuan:</span>
                            <?php if (str_starts_with($i['location_or_link'], 'http')): ?>
                                <?php if ($i['candidate_status'] === 'accepted'): ?>
                                    <a href="<?= htmlspecialchars($i['location_or_link']) ?>" target="_blank" class="inline-flex items-center gap-1 text-xs bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1.5 rounded-xl transition-all shadow-sm">
                                        <span class="material-symbols-outlined text-base">video_call</span> Klik untuk Gabung Video Meeting
                                    </a>
                                <?php else: ?>
                                    <span class="text-[11px] text-amber-800 font-medium bg-amber-50 p-2 rounded-lg border border-amber-100 flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-sm">lock</span> Setujui jadwal terlebih dahulu untuk membuka tautan rapat.
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-xs text-on-surface font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm text-primary">location_on</span> <?= htmlspecialchars($i['location_or_link']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($i['candidate_status'] === 'reschedule_requested' && !empty($i['candidate_notes'])): ?>
                            <div class="bg-rose-50/50 border border-rose-100 p-2.5 rounded-xl text-[10px] text-rose-700">
                                <b>Catatan Reschedule Anda:</b>
                                <p class="italic font-medium mt-0.5">"<?= htmlspecialchars($i['candidate_notes']) ?>"</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <?php if ($i['candidate_status'] === 'pending'): ?>
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                            <button type="button" onclick="openRescheduleModal('<?= htmlspecialchars($i['id']) ?>')" class="px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold transition-all cursor-pointer">
                                Minta Reschedule
                            </button>
                            <button type="button" onclick="respondInterview('<?= htmlspecialchars($i['id']) ?>', 'accept')" class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-[11px] font-bold transition-all cursor-pointer shadow-xs">
                                Setujui Jadwal
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Reschedule -->
<div id="rescheduleModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-white border border-outline-variant/20 rounded-2xl w-full max-w-sm mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="rescheduleModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-[#000666] font-headline flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">update</span> Ajukan Penjadwalan Ulang
            </h3>
            <button type="button" onclick="closeRescheduleModal()" class="p-1.5 rounded-full text-gray-500 hover:bg-gray-100 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">close</span>
            </button>
        </div>
        
        <!-- Modal Form Body -->
        <form id="rescheduleForm" onsubmit="saveReschedule(event)" class="p-6 space-y-4 text-xs font-semibold text-on-surface overflow-y-auto flex-grow">
            <input type="hidden" name="interview_id" id="resch_interview_id" value="">
            <input type="hidden" name="action" value="reschedule">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="space-y-1">
                <label class="block text-gray-700">Alasan & Waktu Alternatif yang Diusulkan <span class="text-red-600">*</span></label>
                <textarea name="candidate_notes" required rows="4" placeholder="Sebutkan alasan reschedule serta saran hari dan jam alternatif Anda..." class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs text-gray-800 resize-none"></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-gray-200">
                <button type="button" onclick="closeRescheduleModal()" class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 transition-all text-xs font-bold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-xs shadow-md transition-all cursor-pointer">
                    Kirim Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRescheduleModal(id) {
        document.getElementById('resch_interview_id').value = id;
        const modal = document.getElementById('rescheduleModal');
        const container = document.getElementById('rescheduleModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
        }, 10);
    }

    function closeRescheduleModal() {
        const modal = document.getElementById('rescheduleModal');
        const container = document.getElementById('rescheduleModalContainer');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function respondInterview(interviewId, action) {
        const title = action === 'accept' ? 'Setujui Jadwal Wawancara?' : 'Ajukan Reschedule?';
        const confirmText = action === 'accept' ? 'Ya, Konfirmasi Hadir' : 'Ya, Kirim Pengajuan';

        Swal.fire({
            title: title,
            text: action === 'accept' ? 'Apakah Anda siap menghadiri wawancara ini sesuai jadwal yang tertera?' : '',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: action === 'accept' ? '#16a34a' : '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('interview_id', interviewId);
                formData.append('action', action);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                fetch('/candidate/interviews/respond', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil!', data.message, 'success').then(() => {
                            if (window.loadPage) window.loadPage('/candidate/interviews');
                            else window.location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
            }
        });
    }

    function saveReschedule(e) {
        e.preventDefault();
        const form = document.getElementById('rescheduleForm');
        const formData = new FormData(form);
        formData.set('csrf_token', '<?= csrf_token() ?>');

        Swal.fire({ title: 'Mengirim...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('/candidate/interviews/respond', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeRescheduleModal();
                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                    if (window.loadPage) window.loadPage('/candidate/interviews');
                    else window.location.reload();
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
    }
</script>