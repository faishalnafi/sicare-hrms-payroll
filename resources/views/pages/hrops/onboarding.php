<?php
/**
 * HR Operations - Verifikasi Onboarding Karyawan Baru (/hrops/onboarding)
 */
use App\Services\ViewHelper;

$candidates = ViewHelper::getPendingOnboardingCandidates();
$departments = ViewHelper::getDepartments();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/15 shadow-sm">
        <div class="space-y-1">
            <h1 class="font-headline text-2xl md:text-3xl font-extrabold text-primary tracking-tight">Verifikasi Onboarding Karyawan Baru</h1>
            <p class="text-on-surface-variant font-medium text-sm">
                Periksa kesesuaian data legal dan dokumen fisik pendaftar sebelum mengaktifkan status karyawan resmi.
            </p>
        </div>
        <div class="flex items-center gap-2 bg-blue-50 text-blue-800 text-xs font-bold px-4 py-2 rounded-xl border border-blue-200">
            <span class="material-symbols-outlined text-base">how_to_reg</span>
            <span>Total Antrean: <?= count($candidates) ?> Kandidat</span>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/15 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-outline-variant/15 flex items-center justify-between">
            <h3 class="font-bold text-on-surface text-base flex items-center gap-2 font-headline">
                <span class="material-symbols-outlined text-primary text-xl">fact_check</span>
                Daftar Pengajuan Onboarding Legal
            </h3>
        </div>

        <?php if (empty($candidates)): ?>
            <div class="p-12 text-center space-y-3">
                <span class="material-symbols-outlined text-5xl text-outline-variant">assignment_turned_in</span>
                <h4 class="text-lg font-bold text-on-surface">Tidak Ada Antrean Onboarding</h4>
                <p class="text-xs text-on-surface-variant max-w-sm mx-auto">
                    Saat ini belum ada pengajuan onboarding kandidat baru yang membutuhkan verifikasi HR Operations.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-surface border-b border-outline-variant/15 text-on-surface-variant font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Kandidat</th>
                            <th class="px-6 py-4">NIK KTP & NPWP</th>
                            <th class="px-6 py-4">Bank & Rekening</th>
                            <th class="px-6 py-4">Posisi / Dept</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 text-on-surface font-medium">
                        <?php foreach ($candidates as $c): ?>
                            <tr class="hover:bg-surface-container-lowest/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-sm text-primary"><?= htmlspecialchars(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?></div>
                                    <div class="text-[11px] text-on-surface-variant"><?= htmlspecialchars($c['email'] ?? '') ?> • <?= htmlspecialchars($c['no_telepon'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div><strong class="text-on-surface">NIK:</strong> <?= htmlspecialchars($c['ktp_nik'] ?? '-') ?></div>
                                    <div class="text-[11px] text-on-surface-variant"><strong class="text-on-surface">NPWP:</strong> <?= htmlspecialchars($c['npwp_number'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold"><?= htmlspecialchars($c['bank_name'] ?? '-') ?></div>
                                    <div class="text-[11px] text-on-surface-variant font-mono"><?= htmlspecialchars($c['bank_account_number'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold"><?= htmlspecialchars($c['job_title'] ?? 'Karyawan Baru') ?></div>
                                    <div class="text-[11px] text-on-surface-variant"><?= htmlspecialchars($c['department_name'] ?? 'Umum') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (($c['onboarding_status'] ?? '') === 'completed'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-full text-[10px] border border-emerald-200">
                                            <span class="material-symbols-outlined text-xs">check_circle</span> Aktif (<?= htmlspecialchars($c['employee_id'] ?? '') ?>)
                                        </span>
                                    <?php elseif (($c['onboarding_status'] ?? '') === 'submitted'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 font-bold rounded-full text-[10px] border border-amber-200">
                                            <span class="material-symbols-outlined text-xs">pending</span> Perlu Verifikasi
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 font-bold rounded-full text-[10px] border border-blue-200">
                                            <span class="material-symbols-outlined text-xs">edit_note</span> In Progress
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" onclick='openVerifyModal(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' class="px-3.5 py-2 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl text-xs shadow-sm transition-all inline-flex items-center gap-1.5 cursor-pointer">
                                        <span class="material-symbols-outlined text-sm">rule</span> Tinjau & Verifikasi
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Verifikasi & Aktivasi Karyawan -->
<div id="verify-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-surface-container-lowest w-full max-w-4xl rounded-2xl shadow-2xl border border-outline-variant/20 max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Modal Header -->
        <div class="p-5 border-b border-outline-variant/15 flex items-center justify-between bg-surface">
            <div>
                <h3 class="font-headline font-extrabold text-lg text-primary" id="modal-candidate-name">Verifikasi Onboarding Kandidat</h3>
                <p class="text-xs text-on-surface-variant" id="modal-candidate-email">Periksa data administrasi legal & tentukan penempatan kerja</p>
            </div>
            <button type="button" onclick="closeVerifyModal()" class="w-8 h-8 rounded-full bg-surface-container hover:bg-outline-variant/20 flex items-center justify-center text-on-surface-variant transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-6 flex-1 text-xs">
            <!-- Grid 2 Kolom: Left Data, Right Documents -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Data Administrasi -->
                <div class="bg-surface p-4 rounded-xl border border-outline-variant/30 space-y-3">
                    <h4 class="font-bold text-on-surface text-xs uppercase tracking-wider border-b border-outline-variant/15 pb-2 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-base">badge</span> Data Administrasi Kependudukan
                    </h4>
                    <div class="space-y-2 text-on-surface">
                        <div><strong class="text-on-surface-variant">Nama Sesuai KTP:</strong> <span id="v-nama-ktp">-</span></div>
                        <div><strong class="text-on-surface-variant">NIK KTP:</strong> <span id="v-nik">-</span></div>
                        <div><strong class="text-on-surface-variant">Tanggal Lahir:</strong> <span id="v-tgl-lahir">-</span></div>
                        <div><strong class="text-on-surface-variant">Jenis Kelamin / Status:</strong> <span id="v-jk-status">-</span></div>
                        <div><strong class="text-on-surface-variant">Alamat KTP:</strong> <span id="v-alamat-ktp">-</span></div>
                        <div><strong class="text-on-surface-variant">Alamat Domisili:</strong> <span id="v-alamat-domisili">-</span></div>
                    </div>

                    <h4 class="font-bold text-on-surface text-xs uppercase tracking-wider border-b border-outline-variant/15 pt-2 pb-2 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-base">account_balance</span> Data Finansial & Kontak Darurat
                    </h4>
                    <div class="space-y-2 text-on-surface">
                        <div><strong class="text-on-surface-variant">Bank / No Rekening:</strong> <span id="v-bank-rek">-</span></div>
                        <div><strong class="text-on-surface-variant">Nomor NPWP:</strong> <span id="v-npwp">-</span></div>
                        <div><strong class="text-on-surface-variant">BPJS TK / KES:</strong> <span id="v-bpjs">-</span></div>
                        <div><strong class="text-on-surface-variant">Kontak Darurat:</strong> <span id="v-emerg">-</span></div>
                    </div>
                </div>

                <!-- Dokumen Pendukung Uploaded -->
                <div class="bg-surface p-4 rounded-xl border border-outline-variant/30 space-y-3">
                    <h4 class="font-bold text-on-surface text-xs uppercase tracking-wider border-b border-outline-variant/15 pb-2 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-base">folder_open</span> Dokumen Berkas Diunggah
                    </h4>
                    <div class="space-y-2.5" id="v-docs-list">
                        <!-- Links generated dynamically -->
                    </div>
                </div>
            </div>

            <!-- Form Penempatan & Aktivasi Staff ID -->
            <form id="approve-onboarding-form" class="bg-primary/5 p-5 rounded-xl border border-primary/15 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="target_user_id" id="form-target-user-id">

                <h4 class="font-bold text-primary text-xs uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">work_history</span> Form Penempatan Kerja & Aktivasi Status Karyawan
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface mb-1.5">Alokasi Departemen / Divisi <span class="text-red-500">*</span></label>
                        <select name="department_id" id="form-dept-id" required class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/30 rounded-xl text-xs focus:outline-none focus:border-primary">
                            <option value="">-- Pilih Departemen --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= htmlspecialchars($d['id']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-on-surface mb-1.5">Jabatan Official (Job Title) <span class="text-red-500">*</span></label>
                        <input type="text" name="job_title" id="form-job-title" required placeholder="Contoh: Senior Software Engineer" class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/30 rounded-xl text-xs focus:outline-none focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-on-surface mb-1.5">Gaji Pokok Karyawan (IDR) <span class="text-red-500">*</span></label>
                        <input type="number" name="base_salary" id="form-base-salary" step="100000" min="0" required placeholder="Contoh: 8500000" class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/30 rounded-xl text-xs focus:outline-none focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-on-surface mb-1.5">Nomor Induk Karyawan (Staff ID) <span class="text-xs text-on-surface-variant font-normal">(Otomatis jika kosong)</span></label>
                        <input type="text" name="employee_id" id="form-employee-id" placeholder="Contoh: EM-2026-0034" class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/30 rounded-xl text-xs font-mono focus:outline-none focus:border-primary">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeVerifyModal()" class="px-4 py-2.5 bg-white hover:bg-surface text-on-surface font-bold text-xs rounded-xl border border-outline-variant/30">Batal</button>
                    <button type="submit" id="btn-submit-approve" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-base">verified</span> Setujui & Aktifkan Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openVerifyModal(cand) {
    document.getElementById('modal-candidate-name').textContent = `Verifikasi Onboarding: ${(cand.first_name || '')} ${(cand.last_name || '')}`;
    document.getElementById('modal-candidate-email').textContent = cand.email || '';

    document.getElementById('v-nama-ktp').textContent = cand.nama_sesuai_ktp || '-';
    document.getElementById('v-nik').textContent = cand.ktp_nik || '-';
    document.getElementById('v-tgl-lahir').textContent = cand.tanggal_lahir || '-';
    document.getElementById('v-jk-status').textContent = `${cand.jenis_kelamin || '-'} / ${cand.status_pernikahan || '-'}`;
    document.getElementById('v-alamat-ktp').textContent = cand.alamat_ktp || '-';
    document.getElementById('v-alamat-domisili').textContent = cand.alamat_domisili || '-';

    document.getElementById('v-bank-rek').textContent = `${cand.bank_name || '-'} (${cand.bank_account_number || '-'})`;
    document.getElementById('v-npwp').textContent = cand.npwp_number || '-';
    document.getElementById('v-bpjs').textContent = `TK: ${cand.bpjs_tk || '-'} / KES: ${cand.bpjs_kes || '-'}`;
    document.getElementById('v-emerg').textContent = `${cand.emergency_contact_name || '-'} (${cand.emergency_contact_relation || '-'}) - ${cand.emergency_contact_phone || '-'}`;

    // Render docs list
    const docsContainer = document.getElementById('v-docs-list');
    docsContainer.innerHTML = '';

    const docs = [
        { label: 'Foto KTP', file: cand.ktp_file },
        { label: 'Kartu Keluarga', file: cand.kk_file },
        { label: 'Kartu NPWP', file: cand.npwp_file },
        { label: 'Buku Tabungan', file: cand.bank_book_file }
    ];

    docs.forEach(doc => {
        const item = document.createElement('div');
        item.className = "flex items-center justify-between p-2.5 bg-white rounded-lg border border-outline-variant/20";
        if (doc.file) {
            item.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-base">description</span>
                    <span class="font-bold text-on-surface text-xs">${doc.label}</span>
                </div>
                <a href="/storage/secured_storage/onboarding/${doc.file}?download=1" target="_blank" class="px-3 py-1 bg-primary/10 hover:bg-primary/20 text-primary font-bold rounded-lg text-[10px] inline-flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-xs">download</span> Unduh Berkas
                </a>
            `;
        } else {
            item.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-outline-variant text-base">warning</span>
                    <span class="font-medium text-on-surface-variant text-xs">${doc.label}</span>
                </div>
                <span class="text-[10px] text-red-500 font-bold bg-red-50 px-2 py-0.5 rounded">Belum Ada File</span>
            `;
        }
        docsContainer.appendChild(item);
    });

    // Fill form
    document.getElementById('form-target-user-id').value = cand.id || '';
    document.getElementById('form-dept-id').value = cand.department_id || '';
    document.getElementById('form-job-title').value = cand.job_title || 'Software Engineer';
    document.getElementById('form-base-salary').value = cand.base_salary || '8500000';
    document.getElementById('form-employee-id').value = cand.employee_id || '';

    document.getElementById('verify-modal').classList.remove('hidden');
}

function closeVerifyModal() {
    document.getElementById('verify-modal').classList.add('hidden');
}

document.getElementById('approve-onboarding-form')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const btn = document.getElementById('btn-submit-approve');
    btn.disabled = true;
    btn.innerHTML = `<span class="material-symbols-outlined text-base animate-spin">sync</span> Memproses...`;

    fetch('/hrops/onboarding/approve', {
        method: 'POST',
        headers: {
            'X-JWT-Token': window.jwtToken || ''
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = `<span class="material-symbols-outlined text-base">verified</span> Setujui & Aktifkan Karyawan`;

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Aktivasi Berhasil!',
                text: data.message,
                confirmButtonColor: '#000666'
            }).then(() => {
                closeVerifyModal();
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memproses',
                text: data.message,
                confirmButtonColor: '#000666'
            });
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = `<span class="material-symbols-outlined text-base">verified</span> Setujui & Aktifkan Karyawan`;
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Terjadi kesalahan jaringan saat memproses permintaan.',
            confirmButtonColor: '#000666'
        });
    });
});
</script>