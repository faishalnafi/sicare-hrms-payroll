<?php

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $db   = $_ENV['DB_DATABASE'] ?? 'sicare_db';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Check if request is AJAX or expects JSON
            $isJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json') !== false);
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || $isJson;

            if ($isAjax) {
                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menghubungkan ke database server: ' . $e->getMessage()
                ]);
                exit;
            }

            // Otherwise, render a premium HTML database outage error page
            self::renderDatabaseErrorPage($e);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Renders a premium, user-friendly HTML error page for database outages.
     */
    private static function renderDatabaseErrorPage($exception) {
        $errorMessage = $exception->getMessage();
        $errorCode = $exception->getCode();
        $appEnv = $_ENV['APP_ENV'] ?? 'local';
        $isProduction = (strtolower($appEnv) === 'production');
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Gangguan Database | siCare</title>
            <!-- Fonts & Icons -->
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@800&display=swap" rel="stylesheet"/>
            <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
            <!-- Tailwind CDN -->
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                  theme: {
                    extend: {
                      colors: {
                        "primary": "#000666",
                        "on-surface": "#191c1d",
                        "on-surface-variant": "#454652",
                        "surface": "#f8f9fa",
                        "outline-variant": "#c6c5d4"
                      }
                    }
                  }
                }
            </script>
            <style>
                body {
                    font-family: 'Inter', sans-serif;
                }
                h1, h2 {
                    font-family: 'Manrope', sans-serif;
                }
            </style>
        </head>
        <body class="bg-surface text-on-surface min-h-screen flex items-center justify-center p-4">
            <div class="max-w-md w-full bg-white border border-outline-variant/30 p-8 rounded-[32px] shadow-[0_20px_50px_-20px_rgba(0,6,102,0.12)] relative overflow-hidden">
                <!-- Decorative background subtle glow (very soft) -->
                <div class="absolute -top-20 -left-20 w-48 h-48 bg-red-500/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -right-20 w-48 h-48 bg-primary/5 rounded-full blur-3xl"></div>
                
                <div class="relative z-10 flex flex-col items-center text-center">
                    <!-- Outage Icon Container -->
                    <div class="relative mb-6">
                        <div class="absolute inset-0 rounded-full bg-red-500/10 blur-xl animate-pulse"></div>
                        <div class="relative w-20 h-20 bg-red-50 text-red-600 border border-red-100 rounded-3xl flex items-center justify-center shadow-sm">
                            <span class="material-symbols-outlined text-4xl select-none font-light">database_off</span>
                        </div>
                    </div>

                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-on-surface mb-3">
                        Koneksi Database Gagal
                    </h1>
                    
                    <p class="text-on-surface-variant text-sm leading-relaxed mb-8">
                        <?php if ($isProduction): ?>
                            Aplikasi <strong class="text-primary font-bold">siCare</strong> tidak dapat terhubung ke pangkalan data utama. Silakan hubungi administrator sistem Anda untuk memulihkan layanan.
                        <?php else: ?>
                            Aplikasi <strong class="text-primary font-bold">siCare</strong> tidak dapat terhubung ke server database utama. Mohon periksa kembali berkas konfigurasi <code class="bg-neutral-100 border border-neutral-200 px-1.5 py-0.5 rounded text-red-600 font-mono text-xs font-semibold">.env</code> Anda atau pastikan server MySQL/MariaDB lokal sudah dalam keadaan aktif.
                        <?php endif; ?>
                    </p>

                    <!-- Action Buttons -->
                    <div class="w-full space-y-3 mb-6">
                        <button onclick="window.location.reload()" class="w-full bg-primary hover:bg-primary/90 text-white font-bold text-sm py-3.5 px-4 rounded-xl transition-all shadow-md shadow-primary/10 flex items-center justify-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-base">refresh</span>
                            Coba Hubungkan Kembali
                        </button>
                    </div>

                    <!-- Technical Details Collapse -->
                    <?php if (!$isProduction): ?>
                    <div class="w-full text-left">
                        <button onclick="document.getElementById('tech-details').classList.toggle('hidden')" class="w-full flex items-center justify-between text-[11px] text-on-surface-variant/70 hover:text-on-surface py-3 border-t border-outline-variant/20 font-semibold focus:outline-none transition-colors">
                            <span>Informasi Teknis (Developer Log)</span>
                            <span class="material-symbols-outlined text-base">expand_more</span>
                        </button>
                        <div id="tech-details" class="hidden mt-2 bg-neutral-50 border border-outline-variant/30 p-4 rounded-xl font-mono text-[10px] text-red-700 max-h-40 overflow-y-auto leading-relaxed">
                            <p class="font-bold mb-1">PDOException [Code <?= htmlspecialchars($errorCode) ?>]:</p>
                            <p class="break-words"><?= htmlspecialchars($errorMessage) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}
