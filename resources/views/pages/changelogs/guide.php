<?php
/**
 * siCare Versioning and Numbering Guidelines Page
 */
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<div class="p-6 max-w-6xl mx-auto font-body">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-primary via-indigo-950 to-slate-900 p-8 md:p-12 shadow-xl mb-10 text-white">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff0a_1px,transparent_1px),linear-gradient(to_bottom,#ffffff0a_1px,transparent_1px)] bg-[size:24px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
        <div class="relative z-10">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-bold text-indigo-200 border border-white/10 mb-4 animate-pulse">
                <span class="material-symbols-outlined text-xs">verified</span>
                Standard Operating Procedure (SOP)
            </span>
            <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight font-headline">
                Pedoman Tata Kelola Versi & Rilis
            </h1>
            <p class="mt-3 text-base md:text-lg text-indigo-100 max-w-2xl font-normal leading-relaxed">
                Panduan resmi penomoran rilis (*SemVer*), arsitektur repositori, kompilasi log, dan mekanisme sinkronisasi database platform **siCare**.
            </p>
        </div>
    </div>

    <!-- Grid Layout with Sticky Nav -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sticky Sidebar Navigation -->
        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-2 bg-surface-container-lowest border border-outline-variant/15 p-4 rounded-2xl shadow-sm">
                <p class="text-[10px] font-black text-on-surface-variant uppercase tracking-wider px-3 mb-2">Daftar Isi</p>
                <a href="#section-fork" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-primary/5 hover:text-primary transition-all">
                    <span class="material-symbols-outlined text-lg">fork_left</span>
                    Edisi & Fork Repo
                </a>
                <a href="#section-tracks" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-primary/5 hover:text-primary transition-all">
                    <span class="material-symbols-outlined text-lg">alt_route</span>
                    Jalur Versi Rilis
                </a>
                <a href="#section-semver" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-primary/5 hover:text-primary transition-all">
                    <span class="material-symbols-outlined text-lg">calculate</span>
                    Kalkulasi SemVer
                </a>
                <a href="#section-process" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-primary/5 hover:text-primary transition-all">
                    <span class="material-symbols-outlined text-lg">history_edu</span>
                    Pencatatan & CLI
                </a>
                <a href="#section-validation" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-on-surface-variant hover:bg-primary/5 hover:text-primary transition-all">
                    <span class="material-symbols-outlined text-lg">gavel</span>
                    Validasi & Mismatch
                </a>
            </div>
        </div>

        <!-- Guidelines Content -->
        <div class="lg:col-span-3 space-y-12">
            <!-- 1. Edisi & Arsitektur Fork -->
            <section id="section-fork" class="bg-surface-container-lowest border border-outline-variant/15 p-6 md:p-8 rounded-3xl shadow-sm scroll-mt-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-primary/10 rounded-xl text-primary">
                        <span class="material-symbols-outlined text-2xl">fork_left</span>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-on-surface font-headline">1. Edisi & Arsitektur Fork</h2>
                </div>

                <p class="text-sm text-on-surface-variant leading-relaxed mb-6">
                    Setiap rilis platform memiliki 2 variabel identitas utama yang menentukan target pemasaran dan skema integrasi kode sumber:
                </p>

                <!-- Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-surface border border-outline-variant/10 p-5 rounded-2xl">
                        <h3 class="text-base font-bold text-on-surface mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">corporate_fare</span>
                            Edisi Aplikasi
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-start gap-2 text-xs">
                                <span class="bg-indigo-100 text-indigo-800 font-extrabold px-2 py-0.5 rounded-full uppercase">Enterprise</span>
                                <span class="text-on-surface-variant leading-relaxed">Untuk korporasi skala menengah/besar. Mendukung fork **Mono** & **Multi** repositori. Tersedia rilis LTS dan STS.</span>
                            </div>
                            <div class="flex items-start gap-2 text-xs border-t border-outline-variant/10 pt-3">
                                <span class="bg-neutral-100 text-neutral-800 font-extrabold px-2 py-0.5 rounded-full uppercase">Community</span>
                                <span class="text-on-surface-variant leading-relaxed">Versi open-source gratis untuk komunitas. Hanya mendukung arsitektur **Mono** repositori. Tersedia rilis LTS dan STS.</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-surface border border-outline-variant/10 p-5 rounded-2xl">
                        <h3 class="text-base font-bold text-on-surface mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">account_tree</span>
                            Arsitektur Repositori
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-start gap-2 text-xs">
                                <span class="bg-blue-100 text-blue-800 font-extrabold px-2 py-0.5 rounded-full uppercase">Mono</span>
                                <span class="text-on-surface-variant leading-relaxed">**Monorepo** — seluruh struktur kode inti dan modul pendukung terintegrasi dalam satu repositori tunggal.</span>
                            </div>
                            <div class="flex items-start gap-2 text-xs border-t border-outline-variant/10 pt-3">
                                <span class="bg-purple-100 text-purple-800 font-extrabold px-2 py-0.5 rounded-full uppercase">Multi</span>
                                <span class="text-on-surface-variant leading-relaxed">**Multirepo** — modul-modul sistem dipisah ke repositori eksternal independen (hanya untuk edisi Enterprise).</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. Jalur Versi Rilis -->
            <section id="section-tracks" class="bg-surface-container-lowest border border-outline-variant/15 p-6 md:p-8 rounded-3xl shadow-sm scroll-mt-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-primary/10 rounded-xl text-primary">
                        <span class="material-symbols-outlined text-2xl">alt_route</span>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-on-surface font-headline">2. Jalur Versi Rilis</h2>
                </div>

                <p class="text-sm text-on-surface-variant leading-relaxed mb-6">
                    siCare mendistribusikan kode melalui 4 jalur versi yang melayani siklus hidup produk yang berbeda:
                </p>

                <!-- Tracks Timeline-like layout -->
                <div class="space-y-6">
                    <!-- Track 1: Stable -->
                    <div class="relative pl-6 border-l-2 border-green-500/30">
                        <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-green-500 border-2 border-white shadow-sm"></span>
                        <div class="bg-surface border border-outline-variant/10 p-5 rounded-2xl">
                            <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                                <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                                    Versi Stabil (Production)
                                </h3>
                                <span class="bg-green-100 text-green-800 text-xs font-mono font-bold px-2 py-0.5 rounded border border-green-200">Format: yy.mm-SUPPORT</span>
                            </div>
                            <p class="text-xs text-on-surface-variant leading-relaxed mb-3">
                                Rilis production untuk pengguna akhir yang memiliki siklus update terjadwal 2 kali setahun (Bulan 05 dan Bulan 11).
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                <div class="bg-white border border-outline-variant/10 p-3 rounded-xl">
                                    <p class="font-extrabold text-green-800 mb-1">LTS (Long Term Support) — Bulan 05 (Mei)</p>
                                    <p class="text-on-surface-variant">Model lisensi berlangganan tahunan, menjamin rilis stabil berkesinambungan dan pembaruan berkala gratis.</p>
                                </div>
                                <div class="bg-white border border-outline-variant/10 p-3 rounded-xl">
                                    <p class="font-extrabold text-blue-800 mb-1">STS (Short Term Support) — Bulan 11 (November)</p>
                                    <p class="text-on-surface-variant">Model blueprint masa depan untuk pembelian lifetime sekali bayar (tanpa biaya langganan tahunan).</p>
                                </div>
                            </div>
                            <div class="mt-3 bg-primary/5 p-3 rounded-xl border border-primary/10 text-xs text-primary leading-relaxed">
                                <span class="font-bold flex items-center gap-1 mb-1">
                                    <span class="material-symbols-outlined text-sm">label</span>
                                    Wajib Menggunakan Nama Alias
                                </span>
                                Setiap rilis stabil wajib memiliki nama alias (misal: **Ammonite**, **Basalt**). Compiler CLI akan menolak pembuatan rilis stabil tanpa nama alias.
                            </div>
                        </div>
                    </div>

                    <!-- Track 2: Beta -->
                    <div class="relative pl-6 border-l-2 border-amber-500/30">
                        <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-amber-500 border-2 border-white shadow-sm"></span>
                        <div class="bg-surface border border-outline-variant/10 p-5 rounded-2xl">
                            <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                                <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                                    Versi Beta (Public Testing)
                                </h3>
                                <span class="bg-amber-100 text-amber-800 text-xs font-mono font-bold px-2 py-0.5 rounded border border-amber-200">Format: X.Y.Z (SemVer)</span>
                            </div>
                            <p class="text-xs text-on-surface-variant leading-relaxed">
                                Jalur rilis SemVer 3-digit murni (tanpa embel-embel LTS/STS) untuk uji coba publik fitur baru. Digit dinaikkan secara otomatis oleh compiler berdasarkan tingkat keparahan dampak teknis perubahan log yang dikompilasi.
                            </p>
                        </div>
                    </div>

                    <!-- Track 3: Pre-release -->
                    <div class="relative pl-6 border-l-2 border-purple-500/30">
                        <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-purple-500 border-2 border-white shadow-sm"></span>
                        <div class="bg-surface border border-outline-variant/10 p-5 rounded-2xl">
                            <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                                <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                                    Versi Pre-Rilis (Continuous Dev)
                                </h3>
                                <span class="bg-purple-100 text-purple-800 text-xs font-mono font-bold px-2 py-0.5 rounded border border-purple-200">Format: yy.mm.nnnnn</span>
                            </div>
                            <p class="text-xs text-on-surface-variant leading-relaxed">
                                Rilis berlanjut **hanya untuk edisi Enterprise**. Angka `nnnnn` adalah counter incremental log rilis yang akan otomatis di-reset menjadi 1 saat bulan atau tahun berganti.
                            </p>
                        </div>
                    </div>

                    <!-- Track 4: Pra-Production -->
                    <div class="relative pl-6 border-l-2 border-neutral-500/30">
                        <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-neutral-500 border-2 border-white shadow-sm"></span>
                        <div class="bg-surface border border-outline-variant/10 p-5 rounded-2xl">
                            <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                                <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                                    Versi Pra-Production (Environment)
                                </h3>
                                <span class="bg-neutral-100 text-neutral-800 text-xs font-mono font-bold px-2 py-0.5 rounded border border-neutral-200">Format: [env]-yy.mm.nnnnn</span>
                            </div>
                            <p class="text-xs text-on-surface-variant leading-relaxed mb-3">
                                Rilis khusus internal yang mencakup prefix nama server tujuan pengujian. Berlaku **hanya untuk edisi Enterprise**.
                            </p>
                            <div class="flex flex-wrap gap-2 text-[10px] font-bold uppercase">
                                <span class="bg-neutral-200 text-neutral-800 px-2 py-0.5 rounded">local - Development</span>
                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded">tqa - QA Testing</span>
                                <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded">stg - Staging</span>
                                <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded">mtc - Maintenance</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. Aturan Kalkulasi Otomatis SemVer -->
            <section id="section-semver" class="bg-surface-container-lowest border border-outline-variant/15 p-6 md:p-8 rounded-3xl shadow-sm scroll-mt-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-primary/10 rounded-xl text-primary">
                        <span class="material-symbols-outlined text-2xl">calculate</span>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-on-surface font-headline">3. Kalkulasi SemVer Otomatis (Jalur Beta)</h2>
                </div>

                <p class="text-sm text-on-surface-variant leading-relaxed mb-6">
                    Ketika compiler mengompilasi entri developer menjadi versi **Beta**, compiler akan menganalisis semua log perubahan baru di `tracker.md` dan menentukan kenaikan digit SemVer (`X.Y.Z`) berdasarkan dampak teknis tertinggi:
                </p>

                <!-- Impact Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="border border-red-200 bg-red-50/50 p-4 rounded-xl text-xs">
                        <p class="font-extrabold text-red-800 text-sm mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">trending_up</span>
                            Major (X)
                        </p>
                        <p class="text-red-900 leading-relaxed">
                            Menaikkan digit **X** jika ada perubahan bertipe `Removed` atau `Changed` yang merusak kompatibilitas modul bawaan atau mengubah logika utama.
                        </p>
                    </div>

                    <div class="border border-amber-200 bg-amber-50/50 p-4 rounded-xl text-xs">
                        <p class="font-extrabold text-amber-800 text-sm mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">trending_up</span>
                            Minor (Y)
                        </p>
                        <p class="text-amber-900 leading-relaxed">
                            Menaikkan digit **Y** jika ada perubahan bertipe `Added` (fitur baru) atau `Deprecated`. Digit **Z** di-reset ke 0.
                        </p>
                    </div>

                    <div class="border border-blue-200 bg-blue-50/50 p-4 rounded-xl text-xs">
                        <p class="font-extrabold text-blue-800 text-sm mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">trending_up</span>
                            Patch (Z)
                        </p>
                        <p class="text-blue-900 leading-relaxed">
                            Menaikkan digit **Z** jika hanya berisi perbaikan minor: `Fixed`, `Security`, `Improved`, atau pembersihan kode `Refactored`.
                        </p>
                    </div>
                </div>

                <!-- 8 Update Types -->
                <p class="text-xs font-extrabold uppercase text-on-surface-variant tracking-wider mb-3">Pemetaan Dampak 8 Tipe Pembaruan</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-surface p-3 rounded-xl border border-outline-variant/10 text-center flex flex-col items-center">
                        <span class="material-symbols-outlined text-green-600 mb-1">add_circle</span>
                        <p class="text-xs font-bold text-on-surface">Added</p>
                        <p class="text-[10px] text-on-surface-variant font-mono">Minor (Y)</p>
                    </div>
                    <div class="bg-surface p-3 rounded-xl border border-outline-variant/10 text-center flex flex-col items-center">
                        <span class="material-symbols-outlined text-blue-600 mb-1">trending_up</span>
                        <p class="text-xs font-bold text-on-surface">Improved</p>
                        <p class="text-[10px] text-on-surface-variant font-mono">Minor/Patch</p>
                    </div>
                    <div class="bg-surface p-3 rounded-xl border border-outline-variant/10 text-center flex flex-col items-center">
                        <span class="material-symbols-outlined text-red-600 mb-1">bug_report</span>
                        <p class="text-xs font-bold text-on-surface">Fixed</p>
                        <p class="text-[10px] text-on-surface-variant font-mono">Patch (Z)</p>
                    </div>
                    <div class="bg-surface p-3 rounded-xl border border-outline-variant/10 text-center flex flex-col items-center">
                        <span class="material-symbols-outlined text-purple-600 mb-1">shield</span>
                        <p class="text-xs font-bold text-on-surface">Security</p>
                        <p class="text-[10px] text-on-surface-variant font-mono">Patch (Z)</p>
                    </div>
                    <div class="bg-surface p-3 rounded-xl border border-outline-variant/10 text-center flex flex-col items-center">
                        <span class="material-symbols-outlined text-amber-600 mb-1">warning</span>
                        <p class="text-xs font-bold text-on-surface">Deprecated</p>
                        <p class="text-[10px] text-on-surface-variant font-mono">Minor (Y)</p>
                    </div>
                    <div class="bg-surface p-3 rounded-xl border border-outline-variant/10 text-center flex flex-col items-center">
                        <span class="material-symbols-outlined text-neutral-600 mb-1">delete</span>
                        <p class="text-xs font-bold text-on-surface">Removed</p>
                        <p class="text-[10px] text-on-surface-variant font-mono">Major (X)</p>
                    </div>
                    <div class="bg-surface p-3 rounded-xl border border-outline-variant/10 text-center flex flex-col items-center">
                        <span class="material-symbols-outlined text-indigo-600 mb-1">edit_note</span>
                        <p class="text-xs font-bold text-on-surface">Changed</p>
                        <p class="text-[10px] text-on-surface-variant font-mono">Major (X)</p>
                    </div>
                    <div class="bg-surface p-3 rounded-xl border border-outline-variant/10 text-center flex flex-col items-center">
                        <span class="material-symbols-outlined text-teal-600 mb-1">code</span>
                        <p class="text-xs font-bold text-on-surface">Refactored</p>
                        <p class="text-[10px] text-on-surface-variant font-mono">Patch (Z)</p>
                    </div>
                </div>
            </section>

            <!-- 4. Mekanisme Pencatatan & Kompilasi -->
            <section id="section-process" class="bg-surface-container-lowest border border-outline-variant/15 p-6 md:p-8 rounded-3xl shadow-sm scroll-mt-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-primary/10 rounded-xl text-primary">
                        <span class="material-symbols-outlined text-2xl">history_edu</span>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-on-surface font-headline">4. Mekanisme Pencatatan & Kompilasi</h2>
                </div>

                <p class="text-sm text-on-surface-variant leading-relaxed mb-6">
                    Pencatatan rilis dikelola melalui kombinasi berkas log pengembangan, berkas data rilis JSON terstruktur, dan compiler CLI otomatis:
                </p>

                <!-- Files Table -->
                <div class="overflow-x-auto border border-outline-variant/10 rounded-2xl mb-6">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/10 text-on-surface font-bold">
                                <th class="p-3">Nama Berkas</th>
                                <th class="p-3">Fungsi Utama</th>
                                <th class="p-3">Perilaku File</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-on-surface-variant">
                            <tr>
                                <td class="p-3 font-mono font-bold text-primary">tracker.md</td>
                                <td class="p-3">Catatan perubahan aktif pengembang dalam format tabel Markdown.</td>
                                <td class="p-3">Dibersihkan (reset) setelah kompilasi rilis sukses.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono font-bold text-primary">tracker.txt</td>
                                <td class="p-3">Log permanen seluruh riwayat perubahan mentah dari awal.</td>
                                <td class="p-3"><strong class="text-red-700">Append-Only (Tidak pernah dihapus).</strong></td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono font-bold text-primary">tracker_archive.md</td>
                                <td class="p-3">Arsip visual entri log `tracker.md` lama pasca kompilasi.</td>
                                <td class="p-3">Append-Only.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono font-bold text-primary">changelog.json</td>
                                <td class="p-3">Penyimpanan riwayat rilis terstruktur untuk sinkronisasi database.</td>
                                <td class="p-3">Menampung entri terkompilasi dalam format JSON.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono font-bold text-primary">changelog.md</td>
                                <td class="p-3">Berkas dokumentasi riwayat rilis publik di repositori.</td>
                                <td class="p-3">Prepend (versi terbaru disisipkan di baris atas).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- CLI Section -->
                <p class="text-xs font-extrabold uppercase text-on-surface-variant tracking-wider mb-3">Perintah CLI Compiler (changelog_compiler.php)</p>
                <div class="relative bg-slate-950 p-4 rounded-xl border border-slate-800 text-white font-mono text-xs overflow-x-auto leading-relaxed space-y-2.5">
                    <div>
                        <span class="text-slate-500"># Kompilasi Pre-Rilis Enterprise (dengan increment counter otomatis)</span><br>
                        <span class="text-indigo-400">php</span> changelog_compiler.php --type PRERELEASE --edition Enterprise --repo mono --yes
                    </div>
                    <div class="border-t border-slate-900 pt-2.5">
                        <span class="text-slate-500"># Kompilasi Staging Pra-Production Enterprise</span><br>
                        <span class="text-indigo-400">php</span> changelog_compiler.php --type STG --edition Enterprise --repo mono --yes
                    </div>
                    <div class="border-t border-slate-900 pt-2.5">
                        <span class="text-slate-500"># Kompilasi Beta (SemVer naik otomatis)</span><br>
                        <span class="text-indigo-400">php</span> changelog_compiler.php --type BETA --edition Enterprise --repo mono --yes
                    </div>
                    <div class="border-t border-slate-900 pt-2.5">
                        <span class="text-slate-500"># Kompilasi Stabil LTS Enterprise (wajib lampirkan alias name)</span><br>
                        <span class="text-indigo-400">php</span> changelog_compiler.php --type LTS --edition Enterprise --repo mono --alias "Ammonite" --yes
                    </div>
                </div>
            </section>

            <!-- 5. Validasi & Fork Mismatch -->
            <section id="section-validation" class="bg-surface-container-lowest border border-outline-variant/15 p-6 md:p-8 rounded-3xl shadow-sm scroll-mt-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-primary/10 rounded-xl text-primary">
                        <span class="material-symbols-outlined text-2xl">gavel</span>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-on-surface font-headline">5. Pembaruan & Validasi Fork</h2>
                </div>

                <p class="text-sm text-on-surface-variant leading-relaxed mb-6">
                    Untuk menjamin keutuhan operasional, sistem pembaruan mengadopsi terminologi dan validasi ketat untuk menghindari salah pasang konfigurasi (*fork mismatch*):
                </p>

                <!-- Terminology Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-surface p-4 rounded-xl border border-outline-variant/10 text-xs">
                        <p class="font-extrabold text-on-surface mb-2 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm text-primary">analytics</span>
                            Terminologi Korporat Baru
                        </p>
                        <ul class="list-disc pl-5 space-y-1 text-on-surface-variant">
                            <li><strong class="text-on-surface">Live Schema Connection</strong> menggantikan istilah "Database Connection".</li>
                            <li><strong class="text-on-surface">Versi Skema Terinstal</strong> menggantikan istilah "Versi Database Saat Ini".</li>
                        </ul>
                    </div>

                    <div class="bg-surface p-4 rounded-xl border border-outline-variant/10 text-xs">
                        <p class="font-extrabold text-on-surface mb-2 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm text-primary">today</span>
                            Pelacakan Tanggal Ganda
                        </p>
                        <ul class="list-disc pl-5 space-y-1 text-on-surface-variant">
                            <li><strong class="text-on-surface">Tanggal Rilis Versi (`compiled_date`):</strong> Tanggal kompilasi kode di server dev.</li>
                            <li><strong class="text-on-surface">Tanggal Update Aplikasi (`created_at`):</strong> Tanggal dilakukannya migrasi skema database.</li>
                        </ul>
                    </div>
                </div>

                <!-- SweetAlert Warning Flow -->
                <div class="bg-amber-50 border border-amber-200 text-amber-900 p-5 rounded-2xl mb-6 text-xs leading-relaxed">
                    <p class="font-extrabold text-amber-800 text-sm mb-2 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">warning</span>
                        Pendeteksian Perpindahan Jalur (Fork Mismatch Warning)
                    </p>
                    Saat Superadmin memicu pembaruan aplikasi lewat panel kontrol, JavaScript akan membandingkan data internal database dengan data JSON yang diunggah. Jika terjadi perbedaan pada salah satu dari 3 parameter berikut, SweetAlert2 kritis akan memblokir update konvensional dan meminta konfirmasi risiko:
                    <ul class="list-decimal pl-5 mt-2 space-y-1 font-semibold">
                        <li>Perpindahan jalur versi (Stabil ↔ Beta / Pre-release / Environment).</li>
                        <li>Perbedaan Edisi aplikasi (Enterprise ↔ Community).</li>
                        <li>Perubahan Arsitektur repositori (Mono ↔ Multi).</li>
                    </ul>
                </div>

                <!-- Validation constraints list -->
                <p class="text-xs font-extrabold uppercase text-on-surface-variant tracking-wider mb-3">Aturan Batasan Validasi (Constraint Validation Rules)</p>
                <div class="overflow-x-auto border border-outline-variant/10 rounded-2xl">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/10 text-on-surface font-bold">
                                <th class="p-3 w-1/3">Kombinasi Kondisi</th>
                                <th class="p-3 w-1/6">Hasil</th>
                                <th class="p-3">Keterangan Teknis</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-on-surface-variant">
                            <tr>
                                <td class="p-3 font-mono font-bold">Community + Multi Repo</td>
                                <td class="p-3 text-red-600 font-bold">❌ Ditolak</td>
                                <td class="p-3 text-xs">Community Edition secara mutlak hanya tersedia dalam mode Monorepo.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono font-bold">Community + Pre-Release</td>
                                <td class="p-3 text-red-600 font-bold">❌ Ditolak</td>
                                <td class="p-3 text-xs">Jalur Pre-Release terus-menerus hanya didukung pada Edisi Enterprise.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono font-bold">Community + Pra-Production</td>
                                <td class="p-3 text-red-600 font-bold">❌ Ditolak</td>
                                <td class="p-3 text-xs">Jalur Pra-Production internal server (local, tqa, stg, mtc) dikunci hanya untuk Enterprise.</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono font-bold">Versi Stabil (Tanpa Alias)</td>
                                <td class="p-3 text-red-600 font-bold">❌ Ditolak</td>
                                <td class="p-3 text-xs">Setiap rilis stabil LTS/STS wajib melampirkan parameter `--alias`.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>
