<?php
// Custom 500 Internal Server Error page with premium aesthetics
?>
<div class="flex-grow flex flex-col items-center justify-center py-20 px-4 text-center animate-fade-in">
    <div class="relative mb-8">
        <!-- Floating glassmorphic circle -->
        <div class="w-48 h-48 rounded-full bg-gradient-to-tr from-red-600/10 to-red-600/30 flex items-center justify-center animate-float shadow-xl border border-white/20">
            <span class="material-symbols-outlined text-8xl text-red-600 font-bold" style="font-size: 80px;">dns</span>
        </div>
        <div class="absolute -bottom-2 -right-2 bg-red-700 text-white font-headline font-extrabold text-2xl px-4 py-1.5 rounded-2xl shadow-lg border border-white/30">
            500
        </div>
    </div>

    <h1 class="font-headline text-4xl lg:text-5xl font-extrabold text-primary tracking-tight mb-4">Kesalahan Server Internal</h1>
    <p class="text-on-surface-variant font-medium text-sm lg:text-base max-w-md leading-relaxed mb-8">
        Terjadi masalah internal pada server kami saat memproses permintaan Anda. Kami sedang berupaya memperbaikinya secepat mungkin.
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
