<?php
use App\Config\Database;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'] ?? null;

// Fetch scheduled interviews for this manager
$stmtInts = $db->prepare("
    SELECT ji.*, ja.candidate_name, ja.candidate_email, ja.candidate_phone, r.title AS job_title
    FROM job_interviews ji
    LEFT JOIN job_applications ja ON ji.application_id = ja.id
    LEFT JOIN job_requisitions r ON ja.requisition_id = r.id
    WHERE ji.interviewer_id = :uid
    ORDER BY ji.interview_date DESC, ji.interview_time DESC
");
$stmtInts->execute(['uid' => $userId]);
$myInterviews = $stmtInts->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl lg:text-3xl font-extrabold text-primary tracking-tight">Lembar Evaluasi Wawancara</h1>
            <p class="text-on-surface-variant font-medium text-xs lg:text-sm">Lakukan penilaian rubrik evaluasi dan berikan umpan balik hasil wawancara kandidat.</p>
        </div>
    </div>

    <!-- Interviews List -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-4 md:p-6 border-b border-outline-variant/15 flex justify-between items-center">
            <h3 class="font-bold text-sm text-on-surface">Jadwal Wawancara Saya</h3>
            <span class="text-xs text-on-surface-variant font-medium">Total <?= count($myInterviews) ?> sesi</span>
        </div>

        <?php if (empty($myInterviews)): ?>
            <div class="p-12 text-center text-on-surface-variant space-y-2">
                <span class="material-symbols-outlined text-5xl text-outline-variant/60">assignment_ind</span>
                <p class="text-sm font-bold text-on-surface">Belum ada sesi wawancara yang dijadwalkan dengan Anda</p>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">Setiap sesi wawancara yang dijadwalkan oleh tim Rekruter untuk Anda akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-on-surface-variant font-bold border-b border-outline-variant/15 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Kandidat & Posisi</th>
                            <th class="py-3.5 px-4">Waktu & Tempat</th>
                            <th class="py-3.5 px-4 text-center">Status Kandidat</th>
                            <th class="py-3.5 px-4 text-right">Aksi Penilaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 font-medium">
                        <?php foreach ($myInterviews as $i): ?>
                            <tr class="hover:bg-surface-container-lowest">
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-on-surface block"><?= htmlspecialchars($i['candidate_name'] ?? 'Kandidat Terhapus') ?></span>
                                    <span class="text-[10px] text-gray-500 block"><?= htmlspecialchars($i['candidate_email']) ?> • <?= htmlspecialchars($i['candidate_phone'] ?? '-') ?></span>
                                    <span class="text-[10px] text-primary font-bold mt-0.5 block"><?= htmlspecialchars($i['job_title'] ?? '') ?></span>
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
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
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
                                <td class="py-3.5 px-4 text-right">
                                    <?php if ($i['manager_decision'] === 'pending'): ?>
                                        <button type="button" onclick="openEvaluateModal('<?= htmlspecialchars($i['id']) ?>', '<?= htmlspecialchars(addslashes($i['candidate_name'])) ?>')" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] px-3 py-1.5 rounded-xl transition-all shadow-sm cursor-pointer">
                                            <span class="material-symbols-outlined text-[14px]">edit_note</span> Isi Penilaian
                                        </button>
                                    <?php else: ?>
                                        <div class="inline-flex flex-col items-end gap-1">
                                            <div class="flex items-center gap-1.5">
                                                <?php if ($i['manager_decision'] === 'pass'): ?>
                                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded font-bold bg-emerald-100 text-emerald-800 text-[10px]">
                                                        Lulus (Pass)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded font-bold bg-rose-100 text-rose-800 text-[10px]">
                                                        Gagal (Fail)
                                                    </span>
                                                <?php endif; ?>
                                                <span class="text-xs font-extrabold text-primary">Skor: <?= (int)$i['manager_score'] ?></span>
                                            </div>
                                            <p class="text-[10px] text-gray-500 italic max-w-[200px] truncate" title="<?= htmlspecialchars($i['manager_feedback']) ?>">"<?= htmlspecialchars($i['manager_feedback']) ?>"</p>
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

<!-- Modal Lembar Penilaian -->
<div id="evaluateModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-white border border-outline-variant/20 rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="evaluateModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-[#000666] font-headline flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">edit_note</span> Lembar Penilaian Wawancara
                </h3>
                <p id="evalCandidateName" class="text-[10px] text-gray-500 font-medium mt-0.5"></p>
            </div>
            <button type="button" onclick="closeEvaluateModal()" class="p-1.5 rounded-full text-gray-500 hover:bg-gray-100 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">close</span>
            </button>
        </div>
        
        <!-- Modal Form Body -->
        <form id="evaluateForm" onsubmit="saveEvaluation(event)" class="p-6 space-y-4 text-xs font-semibold text-on-surface overflow-y-auto flex-grow">
            <input type="hidden" name="interview_id" id="eval_interview_id" value="">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="space-y-1">
                <label class="block text-gray-700 font-bold">Nilai Evaluasi Teknis (1 - 100) <span class="text-red-600">*</span></label>
                <input type="number" name="score" required min="1" max="100" placeholder="contoh: 85" class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-bold text-gray-800" />
            </div>

            <div class="space-y-1">
                <label class="block text-gray-700">Rekomendasi Keputusan <span class="text-red-600">*</span></label>
                <select name="decision" required class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-bold text-gray-800">
                    <option value="">-- Pilih Keputusan --</option>
                    <option value="pass" class="text-green-700 font-bold">LULUS (Rekomendasi Offering)</option>
                    <option value="fail" class="text-red-700 font-bold">GAGAL (Ditolak)</option>
                </select>
            </div>

            <div class="space-y-1">
                <label class="block text-gray-700">Catatan Umpan Balik / Feedback Teknis <span class="text-red-600">*</span></label>
                <textarea name="feedback" required rows="4" placeholder="Tuliskan ulasan detail tentang kecakapan teknis, sikap, dan portofolio kandidat..." class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs text-gray-800 resize-none font-medium"></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-gray-200">
                <button type="button" onclick="closeEvaluateModal()" class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 transition-all text-xs font-bold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-[#000666] hover:bg-[#0c145e] text-white rounded-xl font-bold text-xs shadow-md transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-sm">save</span> Simpan Penilaian
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEvaluateModal(id, candidateName) {
        document.getElementById('eval_interview_id').value = id;
        document.getElementById('evalCandidateName').innerText = 'Kandidat: ' + candidateName;
        const modal = document.getElementById('evaluateModal');
        const container = document.getElementById('evaluateModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
        }, 10);
    }

    function closeEvaluateModal() {
        const modal = document.getElementById('evaluateModal');
        const container = document.getElementById('evaluateModalContainer');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function saveEvaluation(e) {
        e.preventDefault();
        const form = document.getElementById('evaluateForm');
        const formData = new FormData(form);

        Swal.fire({ title: 'Menyimpan Penilaian...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('/manager/interviews/evaluate', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeEvaluateModal();
                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                    if (window.loadPage) window.loadPage('/manager/interviews');
                    else window.location.reload();
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
    }
</script>