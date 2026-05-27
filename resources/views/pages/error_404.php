<?php
// Custom 404 page with high-end premium aesthetics
?>
<div class="flex-grow flex flex-col items-center justify-center py-20 px-4 text-center animate-fade-in">
    <div class="relative mb-8">
        <!-- Floating glassmorphic circle -->
        <div class="w-48 h-48 rounded-full bg-gradient-to-tr from-primary/10 to-primary/30 flex items-center justify-center animate-float shadow-xl border border-white/20">
            <span class="material-symbols-outlined text-8xl text-primary font-bold">error</span>
        </div>
        <div class="absolute -bottom-2 -right-2 bg-error text-white font-headline font-extrabold text-2xl px-4 py-1.5 rounded-2xl shadow-lg border border-white/30">
            404
        </div>
    </div>

    <h1 class="font-headline text-4xl lg:text-5xl font-extrabold text-primary tracking-tight mb-4">Halaman Tidak Ditemukan</h1>
    <p class="text-on-surface-variant font-medium text-sm lg:text-base max-w-md leading-relaxed mb-8">
        Maaf, tautan atau halaman yang Anda akses tidak tersedia atau telah dipindahkan ke alamat lain.
    </p>

    <div class="flex flex-wrap items-center justify-center gap-4">
        <a href="/dashboard" class="flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-white font-bold text-sm shadow-md hover:bg-primary-fixed-dim hover:text-on-primary-container transition-all">
            <span class="material-symbols-outlined text-lg">dashboard</span>
            Ke Dashboard
        </a>
        <a href="/" class="flex items-center gap-2 px-6 py-3 rounded-full bg-surface-container-high text-on-surface font-bold text-sm border border-outline-variant/30 hover:bg-surface-container transition-all">
            <span class="material-symbols-outlined text-lg">home</span>
            Ke Beranda
        </a>
    </div>
</div>
