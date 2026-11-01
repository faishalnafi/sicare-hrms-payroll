<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi & Migrasi Database | siCare HRMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-headline { font-family: 'Manrope', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Glassmorphism effects */
        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .terminal-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .terminal-scroll::-webkit-scrollbar-track {
            background: #090d16;
        }
        .terminal-scroll::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 4px;
        }
        .terminal-scroll::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }
    </style>
</head>
<body class="min-h-full bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-slate-100 p-4 sm:p-6 md:p-10 flex flex-col justify-center items-center">
    
    <div class="max-w-5xl w-full mx-auto space-y-6">
        
        <!-- Header Brand & Status -->
        <div class="glass-card rounded-3xl p-6 md:p-8 shadow-2xl overflow-hidden relative border border-slate-800">
            <div class="absolute -top-24 -right-24 w-72 h-72 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/30 text-white shrink-0">
                        <span class="material-symbols-outlined text-3xl">database</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="font-headline text-2xl md:text-3xl font-extrabold tracking-tight text-white">Instalasi & Migrasi Database</h1>
                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-blue-500/20 text-blue-300 border border-blue-400/30 font-mono">v2.5</span>
                        </div>
                        <p class="text-slate-400 text-xs md:text-sm mt-1">Sistem Rekrutmen & Manajemen Ekstrakurikuler siCare</p>
                    </div>
                </div>

                <div>
                    <?php if (($status ?? 'success') === 'success'): ?>
                        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-bold text-xs shadow-lg shadow-emerald-500/10">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                            Selesai & 100% Ready
                        </div>
                    <?php else: ?>
                        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 font-bold text-xs shadow-lg shadow-rose-500/10">
                            <span class="material-symbols-outlined text-base">warning</span>
                            Perhatian: Cek Migration Log
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Status Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-3.5 hover:border-slate-700 transition-all">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                    <span class="material-symbols-outlined">table_chart</span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-200">Skema & Tabel</h3>
                    <p class="text-[11px] text-emerald-400 font-medium mt-0.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">check_circle</span> Terverifikasi
                    </p>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-3.5 hover:border-slate-700 transition-all">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
                    <span class="material-symbols-outlined">diversity_3</span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-200">5 Main Divisi</h3>
                    <p class="text-[11px] text-blue-400 font-medium mt-0.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">check_circle</span> BPH + 4 Divisi
                    </p>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-3.5 hover:border-slate-700 transition-all">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                    <span class="material-symbols-outlined">work</span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-200">Katalog Lowongan</h3>
                    <p class="text-[11px] text-indigo-400 font-medium mt-0.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">check_circle</span> Siap Menerima Candidate
                    </p>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-5 border border-slate-800 flex items-center gap-3.5 hover:border-slate-700 transition-all">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 shrink-0">
                    <span class="material-symbols-outlined">group</span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-200">Akun Default</h3>
                    <p class="text-[11px] text-purple-400 font-medium mt-0.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">check_circle</span> 11 User Seeded
                    </p>
                </div>
            </div>
        </div>

        <!-- Terminal Console Log Viewer -->
        <div class="glass-card rounded-3xl overflow-hidden border border-slate-800 shadow-2xl">
            <div class="bg-slate-950 px-5 py-3.5 border-b border-slate-800/80 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-rose-500/80"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                    </div>
                    <span class="text-xs font-mono font-medium text-slate-400 flex items-center gap-1.5 ml-2">
                        <span class="material-symbols-outlined text-sm text-blue-400">terminal</span>
                        Live Execution Output Console
                    </span>
                </div>

                <button onclick="copyConsoleLog()" class="px-3 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 text-xs font-mono transition-all flex items-center gap-1 border border-slate-700/50">
                    <span class="material-symbols-outlined text-xs">content_copy</span>
                    <span id="copyBtnText">Salin Log</span>
                </button>
            </div>

            <div class="bg-[#070b12] p-5 font-mono text-xs leading-relaxed text-slate-300 terminal-scroll max-h-72 overflow-y-auto whitespace-pre-wrap selection:bg-blue-500/30 selection:text-blue-200" id="terminalOutput">
<?php
$logText = $rawLog ?? 'Instalasi selesai.';
$lines = explode("\n", $logText);
foreach ($lines as $line) {
    if (trim($line) === '') continue;
    $safeLine = htmlspecialchars($line);
    if (str_contains($line, 'ERROR') || str_contains($line, 'FAILED') || str_contains($line, 'Exception')) {
        echo "<span class=\"text-rose-400 font-bold bg-rose-500/10 px-1 py-0.5 rounded\">[!] {$safeLine}</span>\n";
    } elseif (str_contains($line, 'Migration completed') || str_contains($line, 'Seeded') || str_contains($line, 'Inserted user') || str_contains($line, 'successfully')) {
        echo "<span class=\"text-emerald-400\">✓ {$safeLine}</span>\n";
    } elseif (str_contains($line, 'Running migration') || str_contains($line, 'Executed')) {
        echo "<span class=\"text-blue-400/90\">➜ {$safeLine}</span>\n";
    } else {
        echo "<span class=\"text-slate-400\">  {$safeLine}</span>\n";
    }
}
?>
            </div>
        </div>

        <!-- Default Credentials Section -->
        <div class="glass-card rounded-3xl p-6 md:p-8 border border-slate-800 shadow-2xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-5">
                <div>
                    <h2 class="font-headline text-lg font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-400">badge</span>
                        Daftar Akun Default Sistem
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Password seragam untuk seluruh akun bawaan: <code class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-400/30 font-bold font-mono">@Password2025</code></p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-800">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-950/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3.5">Peran / Role</th>
                            <th class="px-5 py-3.5">Email Log In</th>
                            <th class="px-5 py-3.5">Password</th>
                            <th class="px-5 py-3.5 text-right">Aksi Singkat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 bg-slate-900/40">
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-blue-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                Super Admin
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-200">superadmin@mail.com</td>
                            <td class="px-5 py-3.5 font-mono text-slate-400 font-bold">@Password2025</td>
                            <td class="px-5 py-3.5 text-right">
                                <button onclick="copyText('superadmin@mail.com')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] transition-all">Salin Email</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-indigo-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                                Admin Utama
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-200">admin@mail.com</td>
                            <td class="px-5 py-3.5 font-mono text-slate-400 font-bold">@Password2025</td>
                            <td class="px-5 py-3.5 text-right">
                                <button onclick="copyText('admin@mail.com')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] transition-all">Salin Email</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-cyan-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                                Pengurus BPH (hr_ops)
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-200">hrops@mail.com</td>
                            <td class="px-5 py-3.5 font-mono text-slate-400 font-bold">@Password2025</td>
                            <td class="px-5 py-3.5 text-right">
                                <button onclick="copyText('hrops@mail.com')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] transition-all">Salin Email</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-teal-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                                Rekruter / Tim Perekrut
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-200">recruiter@mail.com</td>
                            <td class="px-5 py-3.5 font-mono text-slate-400 font-bold">@Password2025</td>
                            <td class="px-5 py-3.5 text-right">
                                <button onclick="copyText('recruiter@mail.com')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] transition-all">Salin Email</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-amber-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                Kadiv Post Production (Editor)
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-200">kadiv.postprod@mail.com</td>
                            <td class="px-5 py-3.5 font-mono text-slate-400 font-bold">@Password2025</td>
                            <td class="px-5 py-3.5 text-right">
                                <button onclick="copyText('kadiv.postprod@mail.com')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] transition-all">Salin Email</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-amber-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                Kadiv Editorial (Penulis)
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-200">kadiv.editorial@mail.com</td>
                            <td class="px-5 py-3.5 font-mono text-slate-400 font-bold">@Password2025</td>
                            <td class="px-5 py-3.5 text-right">
                                <button onclick="copyText('kadiv.editorial@mail.com')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] transition-all">Salin Email</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-amber-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                Kadiv Broadcast (Host)
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-200">kadiv.broadcast@mail.com</td>
                            <td class="px-5 py-3.5 font-mono text-slate-400 font-bold">@Password2025</td>
                            <td class="px-5 py-3.5 text-right">
                                <button onclick="copyText('kadiv.broadcast@mail.com')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] transition-all">Salin Email</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-amber-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                Kadiv Visual Production (Kameramen)
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-200">kadiv.visprod@mail.com</td>
                            <td class="px-5 py-3.5 font-mono text-slate-400 font-bold">@Password2025</td>
                            <td class="px-5 py-3.5 text-right">
                                <button onclick="copyText('kadiv.visprod@mail.com')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] transition-all">Salin Email</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Action Footer -->
        <div class="glass-card rounded-3xl p-6 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-slate-400 flex items-center gap-2">
                <span class="material-symbols-outlined text-base text-emerald-400">verified</span>
                Instalasi telah selesai secara otomatis dan siap dipakai.
            </p>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <a href="/signup" class="w-full sm:w-auto px-5 py-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-2xl text-xs transition-all text-center border border-slate-700">
                    Form Pendaftaran Candidate
                </a>
                <a href="/signin" class="w-full sm:w-auto px-7 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-blue-600/30 text-center flex items-center justify-center gap-2">
                    <span>Masuk ke Portal Login</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>

    </div>

    <script>
        function copyConsoleLog() {
            const text = document.getElementById('terminalOutput').innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btnText = document.getElementById('copyBtnText');
                btnText.innerText = 'Tersalin!';
                setTimeout(() => { btnText.innerText = 'Salin Log'; }, 2000);
            });
        }

        function copyText(val) {
            navigator.clipboard.writeText(val).then(() => {
                alert('Email "' + val + '" berhasil disalin ke clipboard!');
            });
        }
    </script>
</body>
</html>
