<?php
use App\Config\Database;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();

// Fetch applications that are in offering stage, hired, or have active offers
$stmtOffers = $db->query("
    SELECT ja.id AS application_id, ja.candidate_name, ja.candidate_email, ja.candidate_phone, ja.status AS app_status,
           r.title AS job_title, r.req_number,
           jo.id AS offer_id, jo.offered_salary, jo.offering_letter_path, jo.status AS offer_status, jo.signed_at, jo.candidate_signature,
           ji.manager_score, ji.manager_feedback
    FROM job_applications ja
    LEFT JOIN job_requisitions r ON ja.requisition_id = r.id
    LEFT JOIN job_offers jo ON ja.id = jo.application_id
    LEFT JOIN job_interviews ji ON ja.id = ji.application_id AND ji.manager_decision = 'pass'
    WHERE ja.status IN ('offering', 'hired') OR jo.id IS NOT NULL
    ORDER BY ja.updated_at DESC
");
$offers = $stmtOffers->fetchAll(PDO::FETCH_ASSOC);

// Fetch candidates who are in offering stage but don't have an offer letter sent yet
$stmtEligible = $db->query("
    SELECT ja.id, ja.candidate_name, r.title AS job_title, r.req_number
    FROM job_applications ja
    LEFT JOIN job_requisitions r ON ja.requisition_id = r.id
    LEFT JOIN job_offers jo ON ja.id = jo.application_id
    WHERE ja.status = 'offering' AND jo.id IS NULL
    ORDER BY ja.candidate_name ASC
");
$eligibleCandidates = $stmtEligible->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl lg:text-3xl font-extrabold text-primary tracking-tight">Manajemen Offering & Kontrak</h1>
            <p class="text-on-surface-variant font-medium text-xs lg:text-sm">Terbitkan Surat Penawaran Kerja (Offering Letter) resmi dan pantau status tanda tangan kontrak kandidat.</p>
        </div>
        <?php if (!empty($eligibleCandidates)): ?>
            <button type="button" onclick="openOfferModal()" class="inline-flex items-center gap-1.5 bg-[#000666] hover:bg-[#0c145e] text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition-all cursor-pointer">
                <span class="material-symbols-outlined text-[16px]">verified</span> Terbitkan Offering Letter
            </button>
        <?php endif; ?>
    </div>

    <!-- Offers List -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-4 md:p-6 border-b border-outline-variant/15 flex justify-between items-center">
            <h3 class="font-bold text-sm text-on-surface">Daftar Penawaran Kerja Terbit</h3>
            <span class="text-xs text-on-surface-variant font-medium">Total <?= count($offers) ?> penawaran</span>
        </div>

        <?php if (empty($offers)): ?>
            <div class="p-12 text-center text-on-surface-variant space-y-2">
                <span class="material-symbols-outlined text-5xl text-outline-variant/60">drafts</span>
                <p class="text-sm font-bold text-on-surface">Belum ada offering letter yang diterbitkan</p>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">Kandidat yang lulus tahap evaluasi wawancara oleh Hiring Manager akan muncul sebagai kandidat layak terbit offering.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-on-surface-variant font-bold border-b border-outline-variant/15 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Kandidat & Posisi</th>
                            <th class="py-3.5 px-4">Nilai Interview</th>
                            <th class="py-3.5 px-4">Gaji Ditawarkan (Gross)</th>
                            <th class="py-3.5 px-4">Berkas Offering</th>
                            <th class="py-3.5 px-4 text-center">Status Offering</th>
                            <th class="py-3.5 px-4 text-right">Tanda Tangan Pelamar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 font-medium">
                        <?php foreach ($offers as $o): ?>
                            <tr class="hover:bg-surface-container-lowest">
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-on-surface block"><?= htmlspecialchars($o['candidate_name']) ?></span>
                                    <span class="text-[10px] text-gray-500 block"><?= htmlspecialchars($o['candidate_email']) ?> • <?= htmlspecialchars($o['candidate_phone'] ?? '-') ?></span>
                                    <span class="text-[10px] text-primary font-bold mt-0.5 block"><?= htmlspecialchars($o['job_title'] ?? '') ?> (<?= htmlspecialchars($o['req_number']) ?>)</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($o['manager_score'])): ?>
                                        <div class="space-y-0.5">
                                            <span class="text-xs font-extrabold text-emerald-700">Skor: <?= (int)$o['manager_score'] ?>/100</span>
                                            <p class="text-[9px] text-gray-500 truncate max-w-[150px]" title="<?= htmlspecialchars($o['manager_feedback']) ?>">"<?= htmlspecialchars($o['manager_feedback']) ?>"</p>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-neutral-400 italic text-[10px]">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-on-surface text-sm">
                                    <?php if (!empty($o['offered_salary'])): ?>
                                        Rp <?= number_format($o['offered_salary'], 0, ',', '.') ?>
                                    <?php else: ?>
                                        <span class="text-neutral-400 italic text-[10px]">Belum Ditentukan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($o['offering_letter_path'])): ?>
                                        <a href="<?= htmlspecialchars($o['offering_letter_path']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[10px] bg-red-50 hover:bg-red-100 text-red-700 font-bold px-2.5 py-1.5 rounded-lg border border-red-200 transition-colors">
                                            <span class="material-symbols-outlined text-[13px] font-bold">picture_as_pdf</span> Lihat Dokumen
                                        </a>
                                    <?php else: ?>
                                        <span class="text-neutral-400 italic text-[10px]">Belum Dikirim</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php if (empty($o['offer_status'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 border border-gray-200" title="Offering belum dibuat">
                                            Belum Dibuat
                                        </span>
                                    <?php elseif ($o['offer_status'] === 'sent'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200" title="Offering sudah terkirim ke portal pelamar">
                                            <span class="material-symbols-outlined text-xs">send</span> Terkirim
                                        </span>
                                    <?php elseif ($o['offer_status'] === 'signed'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200" title="Kandidat menyetujui kontrak penawaran">
                                            <span class="material-symbols-outlined text-xs">task_alt</span> Kontrak Ditandatangani
                                        </span>
                                    <?php elseif ($o['offer_status'] === 'rejected'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200" title="Kandidat menolak penawaran kerja">
                                            <span class="material-symbols-outlined text-xs">cancel</span> Ditolak
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <?php if ($o['offer_status'] === 'signed' && !empty($o['candidate_signature'])): ?>
                                        <div class="inline-flex flex-col items-end gap-0.5">
                                            <span class="text-[9px] text-green-700 font-bold flex items-center gap-0.5"><span class="material-symbols-outlined text-xs">draw</span> Tanda Tangan:</span>
                                            <span class="font-mono text-[9px] bg-gray-50 border px-1.5 py-0.5 rounded font-bold text-gray-700" title="Kode Signature Pelamar"><?= htmlspecialchars(substr($o['candidate_signature'], 0, 15)) ?>...</span>
                                            <span class="text-[9px] text-neutral-400 font-medium block">Pada: <?= date('d M Y H:i', strtotime($o['signed_at'])) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-neutral-400 italic text-[10px]">-</span>
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

<!-- Modal Terbitkan Offering -->
<div id="offerModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-white border border-outline-variant/20 rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="offerModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-[#000666] font-headline flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">verified</span> Terbitkan Offering Letter
            </h3>
            <button type="button" onclick="closeOfferModal()" class="p-1.5 rounded-full text-gray-500 hover:bg-gray-100 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">close</span>
            </button>
        </div>
        
        <!-- Modal Form Body -->
        <form id="offerForm" onsubmit="saveOffer(event)" class="p-6 space-y-4 text-xs font-semibold text-on-surface overflow-y-auto flex-grow">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="space-y-1">
                <label class="block text-gray-700">Pilih Kandidat (Status Offering) <span class="text-red-600">*</span></label>
                <select name="application_id" required class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-bold text-gray-800">
                    <option value="">-- Pilih Kandidat --</option>
                    <?php foreach ($eligibleCandidates as $ec): ?>
                        <option value="<?= htmlspecialchars($ec['id']) ?>">
                            <?= htmlspecialchars($ec['candidate_name']) ?> (<?= htmlspecialchars($ec['job_title']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="space-y-1">
                <label class="block text-gray-700">Nominal Gaji Bulanan Ditawarkan (Gross / Rp) <span class="text-red-600">*</span></label>
                <input type="number" name="offered_salary" placeholder="contoh: 12000000" required min="100000" class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-bold text-gray-800" />
            </div>

            <div class="space-y-1">
                <label class="block text-gray-700">Unggah File Offering Letter / Kontrak <span class="text-red-600">*</span> <span class="text-neutral-400 font-medium">(PDF, maks 10MB)</span></label>
                <div id="offer_dropzone" class="relative border border-dashed border-gray-300 hover:border-blue-500 rounded-xl p-4 bg-gray-50 flex flex-col items-center justify-center text-center cursor-pointer transition-all" onclick="document.getElementById('offering_letter_file').click()">
                    <input type="file" name="offering_letter_file" id="offering_letter_file" required accept=".pdf" class="hidden" onchange="handleOfferFileSelect(this)" />
                    <div id="offer_drop_prompt" class="flex flex-col items-center gap-1 text-gray-600 text-[10px] font-bold">
                        <span class="material-symbols-outlined text-2xl text-[#000666]">upload_file</span>
                        <span>Pilih file PDF atau seret ke sini</span>
                    </div>
                    <div id="offer_drop_filename" class="hidden flex items-center gap-1.5 text-primary text-[10px] font-bold w-full px-2 justify-center">
                        <span class="material-symbols-outlined text-sm text-green-600 flex-shrink-0">check_circle</span>
                        <span id="offer_uploaded_filename_text" class="truncate max-w-[200px]">No file chosen</span>
                        <button type="button" onclick="clearOfferFileSelection(event)" class="text-red-500 hover:text-red-700 ml-1 flex items-center justify-center flex-shrink-0"><span class="material-symbols-outlined text-xs">close</span></button>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-gray-200">
                <button type="button" onclick="closeOfferModal()" class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 transition-all text-xs font-bold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-[#000666] hover:bg-[#0c145e] text-white rounded-xl font-bold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">send</span> Terbitkan & Kirim
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openOfferModal() {
        const modal = document.getElementById('offerModal');
        const container = document.getElementById('offerModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
        }, 10);
    }

    function closeOfferModal() {
        const modal = document.getElementById('offerModal');
        const container = document.getElementById('offerModalContainer');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function handleOfferFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('offer_uploaded_filename_text').textContent = file.name;
            document.getElementById('offer_drop_prompt').classList.add('hidden');
            document.getElementById('offer_drop_filename').classList.remove('hidden');
        }
    }

    function clearOfferFileSelection(e) {
        if (e) e.stopPropagation();
        document.getElementById('offering_letter_file').value = '';
        document.getElementById('offer_drop_prompt').classList.remove('hidden');
        document.getElementById('offer_drop_filename').classList.add('hidden');
    }

    function saveOffer(e) {
        e.preventDefault();
        const form = document.getElementById('offerForm');
        const formData = new FormData(form);

        Swal.fire({ title: 'Menerbitkan Surat Penawaran...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('/recruiter/offerings/send', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeOfferModal();
                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                    if (window.loadPage) window.loadPage('/recruiter/offerings');
                    else window.location.reload();
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
    }
</script>