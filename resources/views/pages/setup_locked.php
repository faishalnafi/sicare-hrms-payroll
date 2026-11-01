<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi Terkunci | siCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-headline { font-family: 'Manrope', sans-serif; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden p-6 md:p-8 text-center space-y-6">
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto shadow-inner">
            <span class="material-symbols-outlined text-3xl">lock</span>
        </div>
        
        <div class="space-y-2">
            <h1 class="font-headline text-2xl font-extrabold text-slate-900 tracking-tight">Instalasi Database Terkunci</h1>
            <p class="text-xs md:text-sm text-slate-600 leading-relaxed">
                Sistem Rekrutmen & Manajemen Ekstrakurikuler <strong>siCare</strong> telah berhasil diinstal sebelumnya pada <span class="font-bold text-slate-800"><?= htmlspecialchars($lockDate ?? 'Waktu Terdaftar') ?></span>.
            </p>
        </div>

        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-left text-xs text-slate-600 space-y-1.5">
            <div class="flex items-center gap-2 font-bold text-slate-800">
                <span class="material-symbols-outlined text-sm text-amber-500">security</span>
                Perlindungan Keamanan Sistem:
            </div>
            <p>Halaman instalasi dikunci secara otomatis untuk mencegah reset data dan perubahan skema database tanpa izin.</p>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row gap-3 items-center justify-center">
            <a href="/signin" class="w-full sm:w-auto px-6 py-3 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-xl text-xs transition-all shadow-md">
                Masuk ke Portal Login &rarr;
            </a>
            <a href="/signup" class="w-full sm:w-auto px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl text-xs transition-all">
                Pendaftaran Kandidat
            </a>
        </div>

        <p class="text-[11px] text-slate-500 italic border-t border-slate-100 pt-4">
            <strong>Hak Akses Administrator:</strong> Untuk menjalankan ulang instalasi/migrasi, silakan login dengan akun Super Admin (<code>superadmin@mail.com</code>) lalu buka URL <code class="text-blue-600 font-bold font-mono">/setup?force=1</code>.
        </p>
    </div>
</body>
</html>
