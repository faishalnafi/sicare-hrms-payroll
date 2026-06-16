<?php
// Custom 503 Service Temporarily Unavailable / Maintenance page with premium aesthetics
?>
<div class="flex-grow flex flex-col items-center justify-center py-20 px-4 text-center animate-fade-in">
    <div class="relative mb-8">
        <!-- Floating glassmorphic circle -->
        <div class="w-48 h-48 rounded-full bg-gradient-to-tr from-amber-600/10 to-amber-600/30 flex items-center justify-center animate-float shadow-xl border border-white/20">
            <span class="material-symbols-outlined text-8xl text-amber-600 font-bold" style="font-size: 80px;">engineering</span>
        </div>
        <div class="absolute -bottom-2 -right-2 bg-amber-700 text-white font-headline font-extrabold text-2xl px-4 py-1.5 rounded-2xl shadow-lg border border-white/30">
            503
        </div>
    </div>

    <h1 class="font-headline text-4xl lg:text-5xl font-extrabold text-primary tracking-tight mb-4">Layanan Sedang Dipelihara</h1>
    <p class="text-on-surface-variant font-medium text-sm lg:text-base max-w-md leading-relaxed mb-8">
        Sistem saat ini sedang menjalani pemeliharaan terjadwal atau sedang mengalami beban tinggi. Silakan coba beberapa saat lagi.
    </p>

    <div class="flex flex-wrap items-center justify-center gap-4">
        <button onclick="window.location.reload()" class="flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-white font-bold text-sm shadow-md hover:bg-primary/95 transition-all cursor-pointer">
            <span class="material-symbols-outlined text-lg">refresh</span>
            Segarkan Halaman
        </button>
        <a href="/" class="flex items-center gap-2 px-6 py-3 rounded-full bg-surface-container-high text-on-surface font-bold text-sm border border-outline-variant/30 hover:bg-surface-container transition-all">
            <span class="material-symbols-outlined text-lg">home</span>
            Ke Beranda
        </a>
    </div>
</div>
