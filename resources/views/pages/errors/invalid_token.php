<?php
// Custom invalid token error page with premium aesthetics
?>
<div class="flex-grow flex flex-col items-center justify-center py-20 px-4 text-center animate-fade-in">
    <div class="relative mb-8">
        <!-- Floating glassmorphic circle -->
        <div class="w-48 h-48 rounded-full bg-gradient-to-tr from-amber-500/10 to-amber-500/30 flex items-center justify-center animate-float shadow-xl border border-white/20">
            <span class="material-symbols-outlined text-8xl text-amber-600 font-bold" style="font-size: 80px;">lock_reset</span>
        </div>
        <div class="absolute -bottom-2 -right-2 bg-amber-600 text-white font-headline font-extrabold text-sm px-4 py-1.5 rounded-2xl shadow-lg border border-white/30">
            TOKEN INVALID
        </div>
    </div>

    <h1 class="font-headline text-4xl lg:text-5xl font-extrabold text-primary tracking-tight mb-4">Token Tidak Valid</h1>
    <p class="text-on-surface-variant font-medium text-sm lg:text-base max-w-md leading-relaxed mb-8">
        <?= htmlspecialchars($message ?? 'Tautan pengisian identitas tidak valid, sudah digunakan, atau kedaluwarsa.') ?>
    </p>

    <div class="flex flex-wrap items-center justify-center gap-4">
        <a href="/dashboard" class="flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-white font-bold text-sm shadow-md hover:bg-primary/95 transition-all">
            <span class="material-symbols-outlined text-lg">dashboard</span>
            Ke Dashboard
        </a>
        <form action="/auth/logout" method="POST" class="inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <button type="submit" class="flex items-center gap-2 px-6 py-3 rounded-full bg-surface-container-high text-on-surface font-bold text-sm border border-outline-variant/30 hover:bg-surface-container transition-all cursor-pointer">
                <span class="material-symbols-outlined text-lg">logout</span>
                Keluar (Logout)
            </button>
        </form>
    </div>
</div>
