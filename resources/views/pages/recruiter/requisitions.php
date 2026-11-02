<?php
use App\Config\Database;
use App\Services\ViewHelper;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = Database::getInstance()->getConnection();

// Fetch ALL requisitions so recruiter can monitor approval progress
$stmtReqs = $db->query("
    SELECT r.*, d.name AS dept_name, u.first_name, u.last_name, u.email AS requester_email
    FROM job_requisitions r
    LEFT JOIN departments d ON r.department_id = d.id
    LEFT JOIN users u ON r.requested_by = u.id
    ORDER BY r.created_at DESC
");
$requisitions = $stmtReqs->fetchAll(PDO::FETCH_ASSOC);

$inProgressCount = 0;
$readyToPublishCount = 0;
$publishedCount = 0;
$rejectedCount = 0;

foreach ($requisitions as $r) {
    if (in_array($r['status'], ['pending_dept_clevel', 'pending_cfo_chro', 'pending_ceo'])) $inProgressCount++;
    if ($r['status'] === 'approved') $readyToPublishCount++;
    if ($r['status'] === 'published') $publishedCount++;
    if ($r['status'] === 'rejected') $rejectedCount++;
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl lg:text-3xl font-extrabold text-primary tracking-tight">Manajemen & Publikasi Lowongan (Talent Acquisition)</h1>
            <p class="text-on-surface-variant font-medium text-xs lg:text-sm">Pantau progres persetujuan FPTK oleh C-Level dan publikasikan lowongan yang telah disetujui ke Portal Karir.</p>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-amber-200/50 bg-amber-50/20 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-amber-800 uppercase tracking-wider block">Proses Approval C-Level</span>
                <span class="text-2xl font-extrabold text-amber-700"><?= $inProgressCount ?></span>
            </div>
            <span class="material-symbols-outlined text-amber-600 text-2xl">hourglass_top</span>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-emerald-200/50 bg-emerald-50/20 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider block">Siap Dipublikasi (CEO Approved)</span>
                <span class="text-2xl font-extrabold text-emerald-700"><?= $readyToPublishCount ?></span>
            </div>
            <span class="material-symbols-outlined text-emerald-600 text-2xl">verified_user</span>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-blue-200/50 bg-blue-50/20 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-blue-800 uppercase tracking-wider block">Tayang di Portal Karir (Published)</span>
                <span class="text-2xl font-extrabold text-blue-700"><?= $publishedCount ?></span>
            </div>
            <span class="material-symbols-outlined text-blue-600 text-2xl">public</span>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/15 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block">Total Permintaan FPTK</span>
                <span class="text-2xl font-extrabold text-on-surface"><?= count($requisitions) ?></span>
            </div>
            <span class="material-symbols-outlined text-primary text-2xl">assignment</span>
        </div>
    </div>

    <!-- Requisitions Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-4 md:p-6 border-b border-outline-variant/15 flex justify-between items-center">
            <h3 class="font-bold text-sm text-on-surface">Monitoring Permintaan Tenaga Kerja (FPTK)</h3>
            <span class="text-xs text-on-surface-variant font-medium">Total <?= count($requisitions) ?> FPTK</span>
        </div>

        <?php if (empty($requisitions)): ?>
            <div class="p-12 text-center text-on-surface-variant space-y-2">
                <span class="material-symbols-outlined text-5xl text-outline-variant/60">pending_actions</span>
                <p class="text-sm font-bold text-on-surface">Belum ada pengajuan FPTK</p>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">Setiap pengajuan penambahan staf baru dari Hiring Manager akan muncul di sini sehingga Recruiter dapat memantau progres approval C-Level.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-on-surface-variant font-bold border-b border-outline-variant/15 uppercase tracking-wider">
                            <th class="py-3.5 px-4">No. FPTK</th>
                            <th class="py-3.5 px-4">Posisi & Dept</th>
                            <th class="py-3.5 px-4 text-center">Headcount</th>
                            <th class="py-3.5 px-4">Progress Approval C-Level</th>
                            <th class="py-3.5 px-4 text-right">Aksi Recruiter</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 font-medium">
                        <?php foreach ($requisitions as $r): ?>
                            <tr class="hover:bg-surface-container-lowest">
                                <td class="py-3.5 px-4 font-bold text-primary">
                                    <?= htmlspecialchars($r['req_number']) ?>
                                    <span class="block text-[10px] text-on-surface-variant font-normal"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-on-surface block"><?= htmlspecialchars($r['title']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant"><?= htmlspecialchars($r['dept_name'] ?? 'Umum') ?> (Pemohon: <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>)</span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-extrabold text-sm">
                                    <?= (int)$r['headcount'] ?> Orang
                                </td>
                                <td class="py-3.5 px-4">
                                    <!-- Multi-stage Stepper Badges for Recruiter Monitor -->
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1 text-[10px]">
                                            <span class="px-2 py-0.5 rounded font-bold <?= !empty($r['dept_clevel_approved_at']) ? 'bg-emerald-100 text-emerald-800' : ($r['status'] === 'pending_dept_clevel' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-400') ?>">
                                                1. Dept C-Level <?= !empty($r['dept_clevel_approved_at']) ? '✓' : '' ?>
                                            </span>
                                            →
                                            <span class="px-2 py-0.5 rounded font-bold <?= (!empty($r['cfo_approved_at']) && !empty($r['chro_approved_at'])) ? 'bg-emerald-100 text-emerald-800' : ($r['status'] === 'pending_cfo_chro' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-400') ?>">
                                                2. CFO & COO <?= (!empty($r['cfo_approved_at']) && !empty($r['chro_approved_at'])) ? '✓' : '' ?>
                                            </span>
                                            →
                                            <span class="px-2 py-0.5 rounded font-bold <?= !empty($r['ceo_approved_at']) ? 'bg-emerald-100 text-emerald-800' : ($r['status'] === 'pending_ceo' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-400') ?>">
                                                3. CEO <?= !empty($r['ceo_approved_at']) ? '✓' : '' ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <?php 
                                        $pubArgs = sprintf(
                                            "'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'",
                                            $r['id'],
                                            htmlspecialchars($r['title'] ?? '', ENT_QUOTES),
                                            htmlspecialchars($r['job_description'] ?? '', ENT_QUOTES),
                                            htmlspecialchars($r['icon'] ?? 'work', ENT_QUOTES),
                                            htmlspecialchars($r['card_color'] ?? '#3b82f6', ENT_QUOTES),
                                            htmlspecialchars($r['logo_url'] ?? '', ENT_QUOTES),
                                            (int)($r['headcount'] ?? 1),
                                            htmlspecialchars($r['employment_type'] ?? 'full_time', ENT_QUOTES),
                                            (float)($r['min_salary'] ?? 0),
                                            (float)($r['max_salary'] ?? 0),
                                            htmlspecialchars($r['requirements'] ?? '', ENT_QUOTES),
                                            htmlspecialchars($r['dept_name'] ?? '', ENT_QUOTES),
                                            htmlspecialchars($r['reason'] ?? '', ENT_QUOTES)
                                        );
                                    ?>
                                    <?php if ($r['status'] === 'approved'): ?>
                                        <button type="button" onclick="openPublishModal(<?= $pubArgs ?>)" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl transition-all shadow-sm cursor-pointer">
                                            <span class="material-symbols-outlined text-[16px]">publish</span> Publikasikan Lowongan
                                        </button>
                                    <?php elseif ($r['status'] === 'published'): ?>
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                <span class="material-symbols-outlined text-[14px]">public</span> Tayang
                                            </span>
                                            <button type="button" onclick="openPublishModal(<?= $pubArgs ?>)" class="inline-flex items-center gap-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-[11px] px-2 py-1 rounded-lg transition-colors cursor-pointer border border-gray-300" title="Edit Tampilan Lowongan">
                                                <span class="material-symbols-outlined text-[14px]">edit</span> Edit
                                            </button>
                                            <button type="button" onclick="unpublishJob('<?= $r['id'] ?>')" class="inline-flex items-center gap-1 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[11px] px-2 py-1 rounded-lg transition-colors cursor-pointer border border-rose-200" title="Sembunyikan dari Portal Publik">
                                                <span class="material-symbols-outlined text-[14px]">visibility_off</span> Nonaktifkan
                                            </button>
                                        </div>
                                    <?php elseif ($r['status'] === 'closed'): ?>
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-[11px] font-bold bg-gray-200 text-gray-700 border border-gray-300" title="Lowongan tidak ditayangkan di portal kandidat">
                                                <span class="material-symbols-outlined text-[14px]">visibility_off</span> Nonaktif
                                            </span>
                                            <button type="button" onclick="openPublishModal(<?= $pubArgs ?>)" class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-lg transition-colors cursor-pointer shadow-sm" title="Tayangkan Kembali Lowongan">
                                                <span class="material-symbols-outlined text-[14px]">publish</span> Aktifkan Kembali
                                            </button>
                                        </div>
                                    <?php elseif ($r['status'] === 'rejected'): ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-[11px] font-bold bg-red-100 text-red-800 border border-red-200">
                                            <span class="material-symbols-outlined text-[14px]">cancel</span> Ditolak C-Level
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="Belum dapat dipublikasikan hingga disetujui CEO">
                                            <span class="material-symbols-outlined text-[14px]">hourglass_top</span> Dalam Proses C-Level
                                        </span>
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

<!-- Modal Publikasi Lowongan (Exact Layout matching Gambar 1) -->
<div id="publishModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 pointer-events-none transition-all duration-300">
    <div class="bg-white border border-outline-variant/20 rounded-2xl w-full max-w-xl mx-4 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="publishModalContainer">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-surface border-b border-outline-variant/15 flex items-center justify-between">
            <h3 id="publishModalTitle" class="text-sm font-extrabold text-[#000666] font-headline flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">public</span> Publikasi Lowongan Karir
            </h3>
            <button type="button" onclick="closePublishModal()" class="p-1.5 rounded-full text-gray-500 hover:bg-gray-100 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-sm font-bold">close</span>
            </button>
        </div>
        
        <!-- Modal Form Body -->
        <form id="publishForm" onsubmit="savePublish(event)" class="p-6 space-y-4 text-xs font-semibold text-on-surface overflow-y-auto flex-grow">
            <input type="hidden" name="requisition_id" id="pub_req_id" value="">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="space-y-1">
                <label class="block text-gray-700">Nama Lowongan / Posisi <span class="text-red-600">*</span></label>
                <input type="text" name="title" id="pub_title" required placeholder="contoh: Senior Backend Engineer" class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-bold text-gray-800" />
            </div>

            <!-- Reference Card: Data dari Pemohon / Manager -->
            <div id="ref_data_box" class="bg-blue-50/80 border border-blue-200 rounded-xl p-3 text-[11px] space-y-1.5 font-sans">
                <div class="flex items-center justify-between font-bold text-[#000666]">
                    <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-blue-700">info</span> Data Pemohon / Manager (FPTK)</span>
                    <span id="ref_dept_type" class="text-[10px] bg-blue-200/70 text-blue-900 px-2 py-0.5 rounded-full font-bold"></span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[10px] text-gray-700 font-medium pt-0.5">
                    <div>• Headcount: <b id="ref_headcount" class="text-gray-900"></b></div>
                    <div>• Kisaran Gaji: <b id="ref_salary" class="text-gray-900"></b></div>
                </div>
                <div id="ref_reqs_container" class="text-[10px] text-gray-700 pt-1 border-t border-blue-200/60">
                    <b>Persyaratan / Kualifikasi Manager:</b>
                    <p id="ref_reqs" class="text-gray-600 italic whitespace-pre-line mt-0.5 text-[10px]"></p>
                </div>
                <div class="pt-1 flex justify-end">
                    <button type="button" onclick="autoFillDescFromRef()" class="text-[10px] text-blue-800 hover:text-blue-950 font-extrabold flex items-center gap-1 bg-blue-100/90 hover:bg-blue-200 px-2.5 py-1 rounded-lg transition-colors cursor-pointer border border-blue-300 shadow-2xs">
                        <span class="material-symbols-outlined text-xs text-blue-700">auto_awesome</span> Sisipkan Data Ini ke Deskripsi
                    </button>
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-gray-700">Deskripsi Singkat</label>
                <textarea name="job_description" id="pub_description" rows="3" placeholder="Deskripsi singkat yang tampil di kartu portal..." class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs resize-none text-gray-800"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-gray-700">URL Logo <span class="text-neutral-400 font-medium">(opsional)</span></label>
                    <input type="text" name="logo_url" id="pub_logo_url" placeholder="https://example.com/logo.png" class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs text-gray-800" />
                    <p class="text-[9px] text-neutral-400 font-medium mt-0.5">Jika kosong, akan menggunakan icon Material di bawah.</p>
                </div>
                <div class="space-y-1">
                    <label class="block text-gray-700">Unggah Logo <span class="text-neutral-400 font-medium">(opsional, maks 1MB)</span></label>
                    <div id="pub_logo_dropzone" class="relative border border-dashed border-gray-300 hover:border-blue-500 rounded-xl p-1 bg-gray-50 flex flex-col items-center justify-center text-center cursor-pointer transition-all h-[42px] overflow-hidden" onclick="document.getElementById('pub_logo_file').click()">
                        <input type="file" name="logo_file" id="pub_logo_file" accept="image/png, image/jpeg, image/gif, image/svg+xml, image/webp" class="hidden" onchange="handlePubFileSelect(this)" />
                        <div id="pub_dropzone_prompt" class="flex items-center gap-1.5 text-gray-600 text-[10px] font-bold">
                            <span class="material-symbols-outlined text-sm text-[#000666]">upload_file</span>
                            <span>Choose File atau drag & drop file</span>
                        </div>
                        <div id="pub_dropzone_filename" class="hidden flex items-center gap-1.5 text-primary text-[10px] font-bold w-full px-2 justify-center">
                            <span class="material-symbols-outlined text-sm text-green-600 flex-shrink-0">check_circle</span>
                            <span id="pub_uploaded_file_name_text" class="truncate max-w-[120px]">No file chosen</span>
                            <button type="button" onclick="clearPubFileSelection(event)" class="text-red-500 hover:text-red-700 ml-1 flex items-center justify-center flex-shrink-0"><span class="material-symbols-outlined text-xs">close</span></button>
                        </div>
                    </div>
                    <p class="text-[9px] text-neutral-400 font-medium mt-0.5">Format: PNG, JPG, JPEG, GIF, SVG, WebP.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-gray-700">Icon Material <span class="text-neutral-400 font-medium">(fallback)</span></label>
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-300 flex items-center justify-center shrink-0">
                            <span id="pub_icon_preview" class="material-symbols-outlined text-lg text-gray-700 font-normal">work</span>
                        </div>
                        <input type="text" name="icon" id="pub_icon" value="work" oninput="document.getElementById('pub_icon_preview').innerText = this.value.trim() || 'work'" class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-mono text-gray-800" placeholder="work" />
                    </div>
                    <p class="text-[10px] text-neutral-400 font-medium mt-0.5">Lihat: <a href="https://fonts.google.com/icons" target="_blank" class="text-blue-600 hover:underline">Material Icons</a></p>
                </div>
                <div class="space-y-1">
                    <label class="block text-gray-700">Warna Icon (Tidak Terbatas)</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="pub_card_color_picker" oninput="document.getElementById('pub_card_color').value = this.value" class="w-12 h-10 border border-gray-300 rounded-xl p-0.5 cursor-pointer bg-gray-50" value="#3b82f6">
                        <input type="text" name="card_color" id="pub_card_color" value="#3b82f6" oninput="document.getElementById('pub_card_color_picker').value = this.value" class="w-full p-2.5 border border-gray-300 rounded-xl bg-gray-50 focus:outline-none focus:border-blue-600 text-xs font-mono text-gray-800" placeholder="#3b82f6" />
                    </div>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-2 border-t border-gray-200">
                <button type="button" onclick="closePublishModal()" class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 transition-all text-xs font-bold cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-[#000666] hover:bg-[#0c145e] text-white rounded-xl font-bold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">public</span> Simpan & Publikasikan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentRefData = {};

    function openPublishModal(reqId, title, desc, icon, color, logoUrl, headcount, empType, minSal, maxSal, requirements, deptName, reason) {
        document.getElementById('pub_req_id').value = reqId || '';
        document.getElementById('pub_title').value = title || '';
        document.getElementById('pub_description').value = desc || '';
        document.getElementById('pub_logo_url').value = logoUrl || '';
        
        icon = icon || 'work';
        document.getElementById('pub_icon').value = icon;
        document.getElementById('pub_icon_preview').innerText = icon;
        
        color = color || '#3b82f6';
        if (!color.startsWith('#')) color = '#3b82f6';
        document.getElementById('pub_card_color').value = color;
        document.getElementById('pub_card_color_picker').value = color;
        
        // Save current manager reference data
        currentRefData = {
            title: title || '',
            deptName: deptName || 'General',
            empType: empType || 'full_time',
            headcount: headcount || 1,
            minSal: minSal || 0,
            maxSal: maxSal || 0,
            requirements: requirements || '-',
            reason: reason || ''
        };

        // Format employment type text
        let empTypeText = 'Full Time';
        if (empType === 'contract') empTypeText = 'Kontrak';
        else if (empType === 'internship' || empType === 'magang') empTypeText = 'Magang / Internship';
        else if (empType === 'part_time') empTypeText = 'Part Time';

        // Populate reference card UI
        document.getElementById('ref_dept_type').innerText = (deptName || 'Dept') + ' • ' + empTypeText;
        document.getElementById('ref_headcount').innerText = (headcount || 1) + ' Orang';
        
        let salaryFormatted = 'Tidak Dicantumkan';
        if (minSal > 0 || maxSal > 0) {
            salaryFormatted = 'Rp ' + Number(minSal).toLocaleString('id-ID') + ' - Rp ' + Number(maxSal).toLocaleString('id-ID');
        }
        document.getElementById('ref_salary').innerText = salaryFormatted;
        document.getElementById('ref_reqs').innerText = requirements || '-';

        const modal = document.getElementById('publishModal');
        const container = document.getElementById('publishModalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-95');
            container.classList.add('scale-100');
        }, 10);
    }

    function autoFillDescFromRef() {
        if (!currentRefData) return;
        const descField = document.getElementById('pub_description');
        let currentText = descField.value.trim();

        let empTypeText = 'Full Time';
        if (currentRefData.empType === 'contract') empTypeText = 'Kontrak';
        else if (currentRefData.empType === 'internship' || currentRefData.empType === 'magang') empTypeText = 'Magang / Internship';
        else if (currentRefData.empType === 'part_time') empTypeText = 'Part Time';

        let salaryFormatted = '';
        if (currentRefData.minSal > 0 || currentRefData.maxSal > 0) {
            salaryFormatted = 'Rp ' + Number(currentRefData.minSal).toLocaleString('id-ID') + ' - Rp ' + Number(currentRefData.maxSal).toLocaleString('id-ID');
        }

        let refSummary = `Membuka lowongan untuk posisi ${currentRefData.title} di departemen ${currentRefData.deptName} dengan status ${empTypeText}. Pengalaman dan kualifikasi yang dibutuhkan: ${currentRefData.requirements}.`;
        if (salaryFormatted) {
            refSummary += ` Kisaran kompensasi: ${salaryFormatted}.`;
        }

        if (currentText === '') {
            descField.value = refSummary;
        } else {
            descField.value = currentText + '\n\n' + refSummary;
        }
    }

    function closePublishModal() {
        const modal = document.getElementById('publishModal');
        const container = document.getElementById('publishModalContainer');
        container.classList.remove('scale-100');
        container.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function handlePubFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('pub_uploaded_file_name_text').textContent = file.name;
            document.getElementById('pub_dropzone_prompt').classList.add('hidden');
            document.getElementById('pub_dropzone_filename').classList.remove('hidden');
        }
    }

    function clearPubFileSelection(e) {
        if (e) e.stopPropagation();
        document.getElementById('pub_logo_file').value = '';
        document.getElementById('pub_dropzone_prompt').classList.remove('hidden');
        document.getElementById('pub_dropzone_filename').classList.add('hidden');
    }

    function savePublish(e) {
        e.preventDefault();
        const form = document.getElementById('publishForm');
        const formData = new FormData(form);

        Swal.fire({ title: 'Mempublikasikan Lowongan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('/recruiter/requisitions/publish', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closePublishModal();
                Swal.fire('Berhasil Tayang!', data.message, 'success').then(() => {
                    if (window.loadPage) window.loadPage('/recruiter/requisitions');
                    else window.location.reload();
                });
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Gagal menyambung ke server.', 'error'));
    }

    function unpublishJob(reqId) {
        Swal.fire({
            title: 'Nonaktifkan Lowongan?',
            text: 'Lowongan ini tidak akan ditampilkan lagi di portal publik / kandidat.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Sembunyikan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('requisition_id', reqId);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                fetch('/recruiter/requisitions/unpublish', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil Dinonaktifkan', data.message, 'success').then(() => {
                            if (window.loadPage) window.loadPage('/recruiter/requisitions');
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

    window.openPublishModal = openPublishModal;
    window.closePublishModal = closePublishModal;
    window.autoFillDescFromRef = autoFillDescFromRef;
    window.execPublish = openPublishModal;
    window.unpublishJob = unpublishJob;
</script>
