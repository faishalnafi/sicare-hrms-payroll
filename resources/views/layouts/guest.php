<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$db = \App\Config\Database::getInstance()->getConnection();
$appName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1")->fetchColumn() ?: 'siCare';
$appLogoImage = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_image' LIMIT 1")->fetchColumn() ?: '';

// Determine dynamic guest page title
$page = $page ?? '';
$guestPageMapping = [
    'landing' => 'HRIS & ATS System',
    'signin' => 'Sign In',
    'signup' => 'Sign Up',
    'privacy' => 'Privacy Policy',
    'terms' => 'Terms of Service',
    'security' => 'Security Info',
    'support' => 'Support Center'
];
$resolvedGuestPage = $guestPageMapping[$page] ?? ucwords(str_replace(['_', '-'], ' ', $page));
$pageTitle = $appName;
if (!empty($resolvedGuestPage)) {
    $pageTitle .= ' | ' . $resolvedGuestPage;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Favicon from Uploaded Logo -->
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($appLogoImage ?: '/favicon.ico') ?>"/>
    
    <!-- PWA Meta Tags & Manifest -->
    <meta name="theme-color" content="#000666"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($appName) ?>"/>
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($appLogoImage ?: '/images/icons/icon-192x192.png') ?>"/>
    <link rel="manifest" href="/manifest.php"/>
    

    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    
    <!-- Icons (Material Symbols) -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Tailwind via CDN (From Provided Template) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              "tertiary-container": "#fef0d4",
              "on-error-container": "#410002",
              "surface-container-lowest": "var(--color-surface-container-lowest, #ffffff)",
              "on-surface-variant": "var(--color-on-surface-variant, #454652)",
              "on-primary-fixed-variant": "#1a5bbf",
              "inverse-primary": "#a6c8ff",
              "tertiary-fixed-dim": "#fcd05d",
              "secondary-container": "#c4ecd1",
              "on-error": "#ffffff",
              "tertiary-fixed": "#fde293",
              "tertiary": "var(--color-tertiary, #FBBC05)", // Google Yellow
              "on-secondary-container": "#0b3c1b",
              "primary": "var(--color-primary, #4285F4)", // Google Blue
              "surface-tint": "var(--color-primary, #4285F4)",
              "background": "var(--color-surface, #f8f9fa)",
              "secondary": "var(--color-secondary, #34A853)", // Google Green
              "on-tertiary-fixed": "#4a3600",
              "surface-variant": "#e1e3e4",
              "surface-container-high": "var(--color-surface-container-high, #e7e8e9)",
              "primary-fixed-dim": "#8ab4f8",
              "on-primary-container": "#d6e4ff",
              "on-tertiary-fixed-variant": "#5f4600",
              "on-secondary-fixed": "#0d4d23",
              "on-primary": "#ffffff",
              "outline-variant": "var(--color-outline-variant, #c6c5d4)",
              "on-tertiary": "#ffffff",
              "on-tertiary-container": "#4a3600",
              "surface": "var(--color-surface, #f8f9fa)",
              "surface-bright": "var(--color-surface, #f8f9fa)",
              "surface-container": "#edeeef",
              "secondary-fixed-dim": "#81c995",
              "error": "var(--color-error, #EA4335)", // Google Red
              "on-secondary-fixed-variant": "#13602d",
              "on-secondary": "#ffffff",
              "secondary-fixed": "#a8dab5",
              "primary-container": "#aecbfa",
              "surface-container-low": "var(--color-surface-container-low, #f3f4f5)",
              "inverse-on-surface": "#f0f1f2",
              "on-background": "var(--color-on-surface, #191c1d)",
              "error-container": "#fce8e6",
              "surface-dim": "#d9dadb",
              "primary-fixed": "#d2e3fc",
              "on-surface": "var(--color-on-surface, #191c1d)",
              "inverse-surface": "#2e3132",
              "surface-container-highest": "#e1e3e4",
              "on-primary-fixed": "#174ea6",
              "outline": "#767683"
            },
            fontFamily: {
              "headline": ["Manrope"],
              "body": ["Inter"],
              "label": ["Inter"]
            },
            borderRadius: {"DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem"},
          },
        },
      }
    </script>
    <style>
      :root {
        --color-primary: #4285F4;
        --color-secondary: #34A853;
        --color-tertiary: #FBBC05;
        --color-error: #EA4335;
        --color-surface: #f8f9fa;
        --color-surface-container-lowest: #ffffff;
        --color-surface-container-low: #f3f4f5;
        --color-surface-container-high: #e7e8e9;
        --color-on-surface: #191c1d;
        --color-on-surface-variant: #454652;
        --color-outline-variant: #c6c5d4;
      }


      @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
      }
      .animate-float {
        animation: float 4s ease-in-out infinite;
      }
      .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      }
      .signature-gradient {
        background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
      }
      .frosted-nav {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
      }
      .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
      }
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased flex flex-col min-h-screen">
    <?php if (isset($page) && !in_array($page, ['signin', 'signup'])): ?>
    <?php require __DIR__ . '/../parts/guest_header.php'; ?>
    <?php endif; ?>
    <!-- Main Content -->
    <main class="flex-grow flex flex-col">
        <?php echo $content; ?>
    </main>

    <?php require __DIR__ . '/../parts/guest_footer.php'; ?>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    console.log('ServiceWorker registration successful with scope: ', reg.scope);
                }, function(err) {
                    console.error('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>
</html>
