<?php
// Custom 403 Access Denied page with high-end premium aesthetics
?>
<div class="flex-grow flex flex-col items-center justify-center py-20 px-4 text-center animate-fade-in">
    <div class="relative mb-8">
        <!-- Floating glassmorphic circle -->
        <div class="w-48 h-48 rounded-full bg-gradient-to-tr from-red-500/10 to-red-500/30 flex items-center justify-center animate-float shadow-xl border border-white/20">
            <span class="material-symbols-outlined text-8xl text-red-600 font-bold" style="font-size: 80px;">shield_lock</span>
        </div>
        <div class="absolute -bottom-2 -right-2 bg-red-600 text-white font-headline font-extrabold text-2xl px-4 py-1.5 rounded-2xl shadow-lg border border-white/30">
            403
        </div>
    </div>

    <h1 class="font-headline text-4xl lg:text-5xl font-extrabold text-primary tracking-tight mb-4">Akses Ditolak</h1>
    <p class="text-on-surface-variant font-medium text-sm lg:text-base max-w-md leading-relaxed mb-8">
        Maaf, Anda tidak memiliki izin atau wewenang untuk mengakses halaman ini. Halaman ini hanya untuk peran tertentu yang berwenang.
    </p>

    <div class="flex flex-wrap items-center justify-center gap-4">
        <a href="/dashboard" class="flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-white font-bold text-sm shadow-md hover:bg-primary/95 transition-all">
            <span class="material-symbols-outlined text-lg">dashboard</span>
            Ke Dashboard
        </a>
        <a href="/" class="flex items-center gap-2 px-6 py-3 rounded-full bg-surface-container-high text-on-surface font-bold text-sm border border-outline-variant/30 hover:bg-surface-container transition-all">
            <span class="material-symbols-outlined text-lg">home</span>
            Ke Beranda
        </a>
    </div>
</div>
