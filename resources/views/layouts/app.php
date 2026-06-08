<?php
$db = \App\Config\Database::getInstance()->getConnection();
$appName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1")->fetchColumn() ?: 'siCare';
$appLogoImage = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_image' LIMIT 1")->fetchColumn() ?: '';

// Determine dynamic page title
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($requestUri, '/');

$roleMapping = [
    'candidate' => 'Candidate',
    'employee' => 'Employee',
    'recruiter' => 'Recruiter',
    'manager' => 'Hiring Manager',
    'hiring_manager' => 'Hiring Manager',
    'hrops' => 'HR Ops',
    'hr_ops' => 'HR Ops',
    'admin' => 'Admin',
    'executive' => 'Executive',
    'superadmin' => 'Superadmin'
];

$pageMapping = [
    'dashboard' => 'Dashboard',
    'onboarding' => 'On Boarding',
    'profile' => 'Profile',
    'attendance' => 'Attendance',
    'leaves' => 'Leaves',
    'finance' => 'Finance',
    'reimbursements' => 'Reimbursements',
    'reflection' => 'Reflection',
    'jobs' => 'Jobs',
    'ats' => 'ATS Pipeline',
    'interviews' => 'Interviews',
    'offerings' => 'Offerings',
    'team' => 'Team',
    'requisitions' => 'Requisitions',
    'candidates' => 'Candidates',
    'approvals' => 'Approvals',
    'employees' => 'Employees',
    'verifications' => 'Verifications',
    'payroll' => 'Payroll',
    'departments' => 'Departments',
    'users' => 'Users',
    'settings' => 'Settings',
    'analytics' => 'Analytics',
    'budgets' => 'Budgets',
    'audit' => 'Audit'
];

$rolePart = '';
$pagePart = '';

if ($path === '' || $path === 'index.php' || $path === 'dashboard') {
    $pagePart = 'Dashboard';
    $rolePart = $_SESSION['role'] ?? '';
} else {
    $parts = explode('/', $path);
    if (count($parts) >= 2) {
        $rolePart = $parts[0];
        $pagePart = $parts[1];
    } else {
        $pagePart = $parts[0];
        $rolePart = $_SESSION['role'] ?? '';
    }
}

$resolvedRole = isset($roleMapping[strtolower($rolePart)]) ? $roleMapping[strtolower($rolePart)] : ucwords(str_replace(['_', '-'], ' ', $rolePart));
$resolvedPage = isset($pageMapping[strtolower($pagePart)]) ? $pageMapping[strtolower($pagePart)] : ucwords(str_replace(['_', '-'], ' ', $pagePart));

$pageTitle = $appName;
if (!empty($resolvedPage)) {
    $pageTitle .= ' | ' . $resolvedPage;
    if (!empty($resolvedRole)) {
        $pageTitle .= ' - ' . $resolvedRole;
    }
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

    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
          theme: {
            extend: {
              colors: {
                "primary": "var(--color-primary, #000666)",
                "on-surface": "var(--color-on-surface, #191c1d)",
                "on-surface-variant": "var(--color-on-surface-variant, #454652)",
                "surface": "var(--color-surface, #f8f9fa)",
                "surface-container-lowest": "var(--color-surface-container-lowest, #ffffff)",
                "surface-container-low": "var(--color-surface-container-low, #f3f4f5)",
                "surface-container-high": "var(--color-surface-container-high, #e7e8e9)",
                "outline-variant": "var(--color-outline-variant, #c6c5d4)"
              },
              fontFamily: {
                "headline": ["Manrope"],
                "body": ["Inter"],
              }
            }
          }
        }
    </script>
    <!-- Global Avatar Helper Functions -->
    <script>
        // Global Avatar Fallback Error Handler
        window.handleAvatarError = function(img, emailHash) {
            if (img.src.indexOf('d=404') !== -1) {
                img.src = 'https://www.gravatar.com/avatar/' + emailHash + '?d=identicon&s=200';
                img.onerror = null;
            } else {
                img.src = 'https://www.gravatar.com/avatar/' + emailHash + '?d=404&s=200';
            }
        };

        // Global MD5 helper for non-letter Gravatar avatars
        window.md5 = function(string) {
            function rotateLeft(lValue, iShiftBits) { return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits)); }
            function addUnsigned(lX, lY) {
                var lX4, lY4, lX8, lY8, lResult;
                lX8 = (lX & 0x80000000); lY8 = (lY & 0x80000000);
                lX4 = (lX & 0x40000000); lY4 = (lY & 0x40000000);
                lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
                if (lX4 & lY4) return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
                if (lX4 | lY4) {
                    if (lResult & 0x40000000) return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                    else return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                } else return (lResult ^ lX8 ^ lY8);
            }
            function F(x, y, z) { return (x & y) | (~x & z); }
            function G(x, y, z) { return (x & z) | (y & ~z); }
            function H(x, y, z) { return (x ^ y ^ z); }
            function I(x, y, z) { return (y ^ (x | ~z)); }
            function FF(a, b, c, d, x, s, ac) {
                a = addUnsigned(a, addUnsigned(addUnsigned(F(b, c, d), x), ac));
                return addUnsigned(rotateLeft(a, s), b);
            }
            function GG(a, b, c, d, x, s, ac) {
                a = addUnsigned(a, addUnsigned(addUnsigned(G(b, c, d), x), ac));
                return addUnsigned(rotateLeft(a, s), b);
            }
            function HH(a, b, c, d, x, s, ac) {
                a = addUnsigned(a, addUnsigned(addUnsigned(H(b, c, d), x), ac));
                return addUnsigned(rotateLeft(a, s), b);
            }
            function II(a, b, c, d, x, s, ac) {
                a = addUnsigned(a, addUnsigned(addUnsigned(I(b, c, d), x), ac));
                return addUnsigned(rotateLeft(a, s), b);
            }
            function convertToWordArray(string) {
                var lWordCount;
                var lMessageLength = string.length;
                var lNumberOfWords_temp1 = lMessageLength + 8;
                var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
                var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
                var lWordArray = Array(lNumberOfWords - 1);
                var lBytePosition = 0;
                var lByteCount = 0;
                while (lByteCount < lMessageLength) {
                    lWordCount = (lByteCount - (lByteCount % 4)) / 4;
                    lBytePosition = (lByteCount % 4) * 8;
                    lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
                    lByteCount++;
                }
                lWordCount = (lByteCount - (lByteCount % 4)) / 4;
                lBytePosition = (lByteCount % 4) * 8;
                lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
                lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
                lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
                return lWordArray;
            }
            function wordToHex(lValue) {
                var WordToHexValue = "", WordToHexValue_temp = "", lByte, lCount;
                for (lCount = 0; lCount <= 3; lCount++) {
                    lByte = (lValue >>> (lCount * 8)) & 255;
                    WordToHexValue_temp = "0" + lByte.toString(16);
                    WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2);
                }
                return WordToHexValue;
            }
            function utf8Encode(string) {
                string = string.replace(/\r\n/g, "\n");
                var utftext = "";
                for (var n = 0; n < string.length; n++) {
                    var c = string.charCodeAt(n);
                    if (c < 128) {
                        utftext += String.fromCharCode(c);
                    } else if ((c > 127) && (c < 2048)) {
                        utftext += String.fromCharCode((c >> 6) | 192);
                        utftext += String.fromCharCode((c & 63) | 128);
                    } else {
                        utftext += String.fromCharCode((c >> 12) | 224);
                        utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                        utftext += String.fromCharCode((c & 63) | 128);
                    }
                }
                return utftext;
            }
            var x = Array();
            var k, AA, BB, CC, DD, a, b, c, d;
            var S11 = 7, S12 = 12, S13 = 17, S14 = 22;
            var S21 = 5, S22 = 9, S23 = 14, S24 = 20;
            var S31 = 4, S32 = 11, S33 = 16, S34 = 23;
            var S41 = 6, S42 = 10, S43 = 15, S44 = 21;
            string = utf8Encode(string);
            x = convertToWordArray(string);
            a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
            for (k = 0; k < x.length; k += 16) {
                AA = a; BB = b; CC = c; DD = d;
                a = FF(a, b, c, d, x[k + 0], S11, 0xD76AA478); d = FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756); c = FF(c, d, a, b, x[k + 2], S13, 0x242070DB); b = FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
                a = FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF); d = FF(d, a, b, c, x[k + 5], S12, 0x4787C62A); c = FF(c, d, a, b, x[k + 6], S13, 0xA8304613); b = FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
                a = FF(a, b, c, d, x[k + 8], S11, 0x698098D8); d = FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF); c = FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1); b = FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
                a = FF(a, b, c, d, x[k + 12], S11, 0x6B901122); d = FF(d, a, b, c, x[k + 13], S12, 0xFD987193); c = FF(c, d, a, b, x[k + 14], S13, 0xA679438E); b = FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
                a = GG(a, b, c, d, x[k + 1], S21, 0xF61E2562); d = GG(d, a, b, c, x[k + 6], S22, 0xC040B340); c = GG(c, d, a, b, x[k + 11], S23, 0x265E5A51); b = GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
                a = GG(a, b, c, d, x[k + 5], S21, 0xD62F105D); d = GG(d, a, b, c, x[k + 10], S22, 0x2441453); c = GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681); b = GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
                a = GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6); d = GG(d, a, b, c, x[k + 14], S22, 0xC33707D6); c = GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87); b = GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
                a = GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905); d = GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8); c = GG(c, d, a, b, x[k + 7], S23, 0x676F02D9); b = GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
                a = HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942); d = HH(d, a, b, c, x[k + 8], S32, 0x8771F681); c = HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122); b = HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
                a = HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44); d = HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9); c = HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60); b = HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
                a = HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6); d = HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA); c = HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085); b = HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
                a = HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039); d = HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5); c = HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8); b = HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
                a = II(a, b, c, d, x[k + 0], S41, 0xF4292244); d = II(d, a, b, c, x[k + 7], S42, 0x432AFF97); c = II(c, d, a, b, x[k + 14], S43, 0xAB9423A7); b = II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
                a = II(a, b, c, d, x[k + 12], S41, 0x655B59C3); d = II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92); c = II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D); b = II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
                a = II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F); d = II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0); c = II(c, d, a, b, x[k + 6], S43, 0xA3014314); b = II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
                a = II(a, b, c, d, x[k + 4], S41, 0xF7537E82); d = II(d, a, b, c, x[k + 11], S42, 0xBD3AF235); c = II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB); b = II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
                a = addUnsigned(a, AA); b = addUnsigned(b, BB); c = addUnsigned(c, CC); d = addUnsigned(d, DD);
            }
            var temp = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);
            return temp.toLowerCase();
        };
    </script>
    <style>
        :root {
            --color-primary: #000666;
            --color-on-surface: #191c1d;
            --color-on-surface-variant: #454652;
            --color-surface: #f8f9fa;
            --color-surface-container-lowest: #ffffff;
            --color-surface-container-low: #f3f4f5;
            --color-surface-container-high: #e7e8e9;
            --color-outline-variant: #c6c5d4;
        }



        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Manrope', sans-serif; }

        /* Premium Gemini-style Sidebar transitions & overrides on Desktop */
        @media (min-width: 1024px) {
            #appSidebar {
                transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), padding 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
            .content-container {
                transition: padding-left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            /* Synced collapsed state (to prevent FOUC layout shift) */
            html.sync-sidebar-collapsed #appSidebar,
            #appSidebar.sidebar-collapsed {
                width: 5rem !important; /* w-20 */
                padding-left: 0.5rem !important; /* px-2 */
                padding-right: 0.5rem !important; /* px-2 */
            }

            html.sync-sidebar-collapsed .content-container,
            .content-container.sidebar-collapsed-padding {
                padding-left: 7rem !important; /* matches lg:pl-28 but exact spacing */
            }

            /* Pinned Expanded State overrides */
            #appSidebar:not(.sidebar-collapsed):not(.sync-sidebar-collapsed-temp) {
                width: 19.5rem !important; /* Spacious w-78 equivalent */
                padding-left: 1rem !important; /* px-4 */
                padding-right: 1rem !important; /* px-4 */
            }
            .content-container:not(.sidebar-collapsed-padding):not(.sync-sidebar-collapsed-padding-temp) {
                padding-left: 21.5rem !important; /* Sidebar width + 2rem spacing */
            }

            /* Hide elements when collapsed */
            html.sync-sidebar-collapsed #appSidebar .brand-text,
            html.sync-sidebar-collapsed #appSidebar nav a div span:nth-child(2),
            html.sync-sidebar-collapsed #appSidebar nav a div span:nth-child(3),
            html.sync-sidebar-collapsed #appSidebar .profile-details,
            html.sync-sidebar-collapsed #appSidebar button div span:nth-child(2),
            html.sync-sidebar-collapsed #appSidebar button div span:nth-child(3),
            #appSidebar.sidebar-collapsed .brand-text,
            #appSidebar.sidebar-collapsed nav a div span:nth-child(2),
            #appSidebar.sidebar-collapsed nav a div span:nth-child(3),
            #appSidebar.sidebar-collapsed .profile-details,
            #appSidebar.sidebar-collapsed button div span:nth-child(2),
            #appSidebar.sidebar-collapsed button div span:nth-child(3) {
                opacity: 0 !important;
                width: 0 !important;
                max-width: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: hidden !important;
                display: none !important;
            }

            /* Collapsed Brand Container Adjustments */
            html.sync-sidebar-collapsed #appSidebar .brand-logo-container,
            #appSidebar.sidebar-collapsed .brand-logo-container {
                position: relative !important;
                justify-content: center !important;
                width: 100% !important;
                height: 40px !important;
                padding: 0 !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }

            /* Collapsed Brand Logo Link */
            html.sync-sidebar-collapsed #appSidebar .brand-logo-link,
            #appSidebar.sidebar-collapsed .brand-logo-link {
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                width: 100% !important;
                height: 100% !important;
                opacity: 1 !important;
                pointer-events: auto !important;
                transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Collapsed Desktop Toggle Button */
            html.sync-sidebar-collapsed #appSidebar #desktopSidebarToggle,
            #appSidebar.sidebar-collapsed #desktopSidebarToggle {
                display: flex !important;
                position: absolute !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) scale(0.8) !important;
                margin: 0 !important;
                opacity: 0 !important;
                pointer-events: none !important;
                transition: opacity 0.2s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                background-color: transparent !important;
            }

            /* Hover states on collapsed sidebar - brand logo swap */
            html.sync-sidebar-collapsed #appSidebar:hover .brand-logo-link,
            #appSidebar.sidebar-collapsed:hover .brand-logo-link {
                opacity: 0 !important;
                pointer-events: none !important;
            }

            html.sync-sidebar-collapsed #appSidebar:hover #desktopSidebarToggle,
            #appSidebar.sidebar-collapsed:hover #desktopSidebarToggle {
                opacity: 1 !important;
                pointer-events: auto !important;
                transform: translate(-50%, -50%) scale(1) !important;
            }

            /* Center align icons and remove gaps */
            html.sync-sidebar-collapsed #appSidebar nav a div,
            #appSidebar.sidebar-collapsed nav a div,
            html.sync-sidebar-collapsed #appSidebar button div,
            #appSidebar.sidebar-collapsed button div {
                justify-content: center !important;
                gap: 0 !important;
            }

            html.sync-sidebar-collapsed #appSidebar .profile-widget-container,
            #appSidebar.sidebar-collapsed .profile-widget-container {
                justify-content: center !important;
            }

            /* Enable overflow visible for collapsed sidebar elements to allow tooltips to show */
            html.sync-sidebar-collapsed #appSidebar,
            #appSidebar.sidebar-collapsed {
                overflow: visible !important;
            }
            html.sync-sidebar-collapsed #appSidebar nav,
            #appSidebar.sidebar-collapsed nav {
                overflow: visible !important;
            }
            html.sync-sidebar-collapsed #appSidebar > div,
            #appSidebar.sidebar-collapsed > div {
                overflow: visible !important;
            }

            /* Tooltip container element relative positioning */
            html.sync-sidebar-collapsed #appSidebar a[data-tooltip],
            #appSidebar.sidebar-collapsed a[data-tooltip],
            html.sync-sidebar-collapsed #appSidebar button[data-tooltip],
            #appSidebar.sidebar-collapsed button[data-tooltip] {
                position: relative !important;
            }

            /* The Floating Tooltip Box */
            html.sync-sidebar-collapsed #appSidebar a[data-tooltip]::after,
            #appSidebar.sidebar-collapsed a[data-tooltip]::after,
            html.sync-sidebar-collapsed #appSidebar button[data-tooltip]::after,
            #appSidebar.sidebar-collapsed button[data-tooltip]::after {
                content: attr(data-tooltip);
                position: absolute;
                left: calc(100% + 12px);
                top: 50%;
                transform: translateY(-50%) scale(0.9);
                background-color: rgba(25, 28, 29, 0.95);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                color: #ffffff;
                padding: 6px 12px;
                border-radius: 8px;
                font-size: 12px;
                font-weight: 600;
                white-space: nowrap;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.15s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                border: 1px solid rgba(255, 255, 255, 0.1);
                z-index: 100;
            }

            /* The Tooltip Indicator Arrow */
            html.sync-sidebar-collapsed #appSidebar a[data-tooltip]::before,
            #appSidebar.sidebar-collapsed a[data-tooltip]::before,
            html.sync-sidebar-collapsed #appSidebar button[data-tooltip]::before,
            #appSidebar.sidebar-collapsed button[data-tooltip]::before {
                content: "";
                position: absolute;
                left: calc(100% + 6px);
                top: 50%;
                transform: translateY(-50%) scale(0.9);
                border-width: 5px;
                border-style: solid;
                border-color: transparent rgba(25, 28, 29, 0.95) transparent transparent;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.15s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 100;
            }

            /* Custom red glassmorphism for logout button tooltip */
            html.sync-sidebar-collapsed #appSidebar button[data-tooltip]::after,
            #appSidebar.sidebar-collapsed button[data-tooltip]::after {
                background-color: rgba(186, 26, 26, 0.95) !important;
                border: 1px solid rgba(255, 255, 255, 0.15) !important;
            }
            html.sync-sidebar-collapsed #appSidebar button[data-tooltip]::before,
            #appSidebar.sidebar-collapsed button[data-tooltip]::before {
                border-color: transparent rgba(186, 26, 26, 0.95) transparent transparent !important;
            }

            /* Show Tooltip & Arrow on Hover */
            html.sync-sidebar-collapsed #appSidebar a[data-tooltip]:hover::after,
            #appSidebar.sidebar-collapsed a[data-tooltip]:hover::after,
            html.sync-sidebar-collapsed #appSidebar button[data-tooltip]:hover::after,
            #appSidebar.sidebar-collapsed button[data-tooltip]:hover::after {
                opacity: 1;
                transform: translateY(-50%) scale(1);
            }

            html.sync-sidebar-collapsed #appSidebar a[data-tooltip]:hover::before,
            #appSidebar.sidebar-collapsed a[data-tooltip]:hover::before,
            html.sync-sidebar-collapsed #appSidebar button[data-tooltip]:hover::before,
            #appSidebar.sidebar-collapsed button[data-tooltip]:hover::before {
                opacity: 1;
                transform: translateY(-50%) scale(1);
            }
        }
    </style>
</head>
<body class="bg-surface text-on-surface flex flex-col min-h-screen overflow-x-hidden">
    <script>
        // Immediate check to prevent layout shift (FOUC)
        (function() {
            const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            if (isCollapsed && window.innerWidth >= 1024) {
                document.documentElement.classList.add('sync-sidebar-collapsed');
            }
        })();
    </script>

    <!-- Backdrop for Mobile Drawer -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/40 z-30 hidden transition-opacity duration-300 lg:hidden animate-fade-in"></div>

    <!-- Floating Hamburger for Mobile (Always visible since global header is removed) -->
    <button id="mobileSidebarToggle" class="lg:hidden fixed top-4 left-4 z-30 p-3 bg-surface-container-lowest border border-outline-variant/20 shadow-md rounded-full text-on-surface-variant hover:bg-surface-container-high transition-colors flex items-center justify-center">
        <span class="material-symbols-outlined">menu</span>
    </button>
    
    <!-- Middle Container -->
    <div class="flex-grow flex relative min-w-0 w-full">
        <!-- Sidebar -->
        <?php require __DIR__ . '/../parts/app_sidebar.php'; ?>
        
        <!-- Right Content Container (Pushed by collapsed sidebar) -->
        <div id="contentContainer" class="content-container flex-grow flex flex-col min-w-0 lg:pl-28 xl:pl-80 transition-all duration-300">
            <?php if (isset($_SESSION['original_user_id'])): ?>
                <div class="bg-amber-500 text-white px-6 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm z-30 text-sm font-semibold sticky top-0 border-b border-amber-600/30 backdrop-blur-md bg-amber-500/95">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-xl animate-pulse">supervised_user_circle</span>
                        <span>Anda sedang melakukan simulasi login sebagai <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> (<?= htmlspecialchars($_SESSION['email']) ?>).</span>
                    </div>
                    <a href="/auth/stop-impersonating" class="bg-white/20 hover:bg-white/30 text-white px-4 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm border border-white/10 hover:scale-105 active:scale-95">
                        <span class="material-symbols-outlined text-xs">logout</span> Kembali ke Super Admin
                    </a>
                </div>
            <?php endif; ?>
            <main class="flex-grow p-4 pt-16 sm:p-6 sm:pt-20 lg:p-8 max-w-[1600px] w-full mx-auto space-y-6">
                <!-- Identity Chip -->
                <div class="flex items-center gap-2">
                    <span class="bg-green-100 text-green-800 text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full">AKUN TERVERIFIKASI</span>
                    <span class="text-on-surface-variant text-sm font-medium">Portal Karyawan</span>
                </div>
                
                <!-- SPA Content Container -->
                <div id="app-content" class="transition-opacity duration-200">
                    <?php echo $content; ?>
                </div>
            </main>
        </div>
    </div>
    <!-- SPA Router Script -->
    <script>
        // Synchronize Active Sidebar Links, Header, Footer & Dynamic Sidebar Offset
        function updateActiveSidebarMenu() {
            let pathname = window.location.pathname.toLowerCase();
            let currentPath = pathname.replace(/\/$/, '');
            let isDashboard = currentPath.endsWith('/dashboard') || currentPath === '/' || currentPath === '' || currentPath.endsWith('/public') || currentPath.endsWith('/index.php');
            

            // 3. Highlight Active Sidebar Item (robust to folder prefixes and trailing slashes)
            document.querySelectorAll('#appSidebar nav a').forEach(a => {
                let linkPath = a.getAttribute('href');
                let div = a.querySelector('div');
                let icon = a.querySelector('.material-symbols-outlined');
                let text = a.querySelector('span:not(.material-symbols-outlined)');
                let chevron = a.querySelector('span.material-symbols-outlined:last-child');
                
                let cleanCurrent = currentPath;
                let cleanLink = linkPath.toLowerCase().replace(/\/$/, '');
                
                let isActive = (cleanCurrent === cleanLink || cleanCurrent.endsWith(cleanLink));
                
                if (isActive) {
                    if (div) div.className = "rounded-xl p-3 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-3 group cursor-pointer transition-all duration-200 bg-primary/10 text-primary font-bold shadow-[0_4px_12px_rgba(0,6,102,0.02)]";
                    if (icon) icon.className = "material-symbols-outlined text-primary";
                    if (text) text.className = "text-sm font-bold text-primary transition-all duration-300 lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden whitespace-nowrap";
                    if (chevron) chevron.className = "material-symbols-outlined text-sm ml-auto transition-all duration-300 lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden text-primary opacity-100";
                } else {
                    if (div) div.className = "rounded-xl p-3 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-3 group cursor-pointer transition-all duration-200 text-on-surface-variant hover:bg-surface-container-low hover:text-primary transition-all duration-200";
                    if (icon) icon.className = "material-symbols-outlined text-on-surface-variant group-hover:text-primary";
                    if (text) text.className = "text-sm font-medium text-on-surface-variant group-hover:text-primary transition-all duration-300 lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden whitespace-nowrap";
                    if (chevron) chevron.className = "material-symbols-outlined text-sm ml-auto transition-all duration-300 lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden text-primary/50 opacity-0 group-hover:opacity-100";
                }
            });
        }

        // Close sidebar on mobile
        function closeSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (backdrop) {
                backdrop.classList.add('hidden');
                backdrop.classList.remove('block');
            }
        }

        // Open sidebar on mobile
        function openSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.remove('-translate-x-full');
            if (backdrop) {
                backdrop.classList.remove('hidden');
                backdrop.classList.add('block');
            }
        }

        // Setup DOM Event Listeners for Mobile Menu
        document.addEventListener('DOMContentLoaded', function() {
            const backdrop = document.getElementById('sidebarBackdrop');
            const toggleBtn = document.getElementById('mobileSidebarToggle');
            const dashboardToggleBtn = document.getElementById('mobileDashboardToggle');
            const closeBtn = document.getElementById('mobileSidebarClose');
            
            if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
            if (dashboardToggleBtn) dashboardToggleBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (backdrop) backdrop.addEventListener('click', closeSidebar);
            
            // Desktop Sidebar Toggle (Gemini Style)
            const desktopToggleBtn = document.getElementById('desktopSidebarToggle');
            const appSidebar = document.getElementById('appSidebar');
            const contentContainer = document.getElementById('contentContainer');
            
            // Function to set and persist sidebar state
            function setSidebarCollapsed(collapsed) {
                if (collapsed) {
                    if (appSidebar) appSidebar.classList.add('sidebar-collapsed');
                    if (contentContainer) contentContainer.classList.add('sidebar-collapsed-padding');
                    localStorage.setItem('sidebar-collapsed', 'true');
                } else {
                    if (appSidebar) appSidebar.classList.remove('sidebar-collapsed');
                    if (contentContainer) contentContainer.classList.remove('sidebar-collapsed-padding');
                    localStorage.setItem('sidebar-collapsed', 'false');
                }
            }
            
            // On DOM load, sync from the immediate check class
            const isInitiallyCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            setSidebarCollapsed(isInitiallyCollapsed);
            document.documentElement.classList.remove('sync-sidebar-collapsed');
            
            if (desktopToggleBtn) {
                desktopToggleBtn.addEventListener('click', function() {
                    const isCurrentlyCollapsed = appSidebar.classList.contains('sidebar-collapsed');
                    setSidebarCollapsed(!isCurrentlyCollapsed);
                });
            }
            
            updateActiveSidebarMenu();
        });

        function injectHtmlAndRunScripts(html) {
            const contentEl = document.getElementById('app-content');
            contentEl.innerHTML = html;
            
            const scripts = contentEl.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                Array.from(script.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                // Wrap in IIFE to prevent top-level const/let re-declaration errors
                // when SPA navigates back to the same page or between pages with shared variable names
                if (script.textContent.trim()) {
                    newScript.textContent = '(function(){\n' + script.textContent + '\n})();';
                }
                script.parentNode.replaceChild(newScript, script);
            });
        }

        function updatePageTitle(url) {
            const appName = <?= json_encode($appName) ?>;
            const roleMapping = {
                'candidate': 'Candidate',
                'employee': 'Employee',
                'recruiter': 'Recruiter',
                'manager': 'Hiring Manager',
                'hiring_manager': 'Hiring Manager',
                'hrops': 'HR Ops',
                'hr_ops': 'HR Ops',
                'admin': 'Admin',
                'executive': 'Executive',
                'superadmin': 'Superadmin'
            };

            const pageMapping = {
                'dashboard': 'Dashboard',
                'onboarding': 'On Boarding',
                'profile': 'Profile',
                'attendance': 'Attendance',
                'leaves': 'Leaves',
                'finance': 'Finance',
                'reimbursements': 'Reimbursements',
                'reflection': 'Reflection',
                'jobs': 'Jobs',
                'ats': 'ATS Pipeline',
                'interviews': 'Interviews',
                'offerings': 'Offerings',
                'team': 'Team',
                'requisitions': 'Requisitions',
                'candidates': 'Candidates',
                'approvals': 'Approvals',
                'employees': 'Employees',
                'verifications': 'Verifications',
                'payroll': 'Payroll',
                'departments': 'Departments',
                'users': 'Users',
                'settings': 'Settings',
                'analytics': 'Analytics',
                'budgets': 'Budgets',
                'audit': 'Audit'
            };

            let path = url;
            if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/')) {
                try {
                    const urlObj = new URL(url, window.location.origin);
                    path = urlObj.pathname;
                } catch(e) {}
            }
            path = path.replace(/^\/|\/$/g, '');
            
            let rolePart = '';
            let pagePart = '';
            
            if (path === '' || path === 'index.php' || path === 'dashboard') {
                pagePart = 'Dashboard';
                rolePart = <?= json_encode($_SESSION['role'] ?? '') ?>;
            } else {
                const parts = path.split('/');
                if (parts.length >= 2) {
                    rolePart = parts[0];
                    pagePart = parts[1];
                } else {
                    pagePart = parts[0];
                    rolePart = <?= json_encode($_SESSION['role'] ?? '') ?>;
                }
            }

            const cleanRole = rolePart.toLowerCase();
            const cleanPage = pagePart.toLowerCase();

            const resolvedRole = roleMapping[cleanRole] || rolePart.replace(/[_-]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            const resolvedPage = pageMapping[cleanPage] || pagePart.replace(/[_-]/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

            let title = appName;
            if (resolvedPage) {
                title += ' | ' + resolvedPage;
                if (resolvedRole) {
                    title += ' - ' + resolvedRole;
                }
            }
            document.title = title;
        }

        window.loadPage = function(url) {
            document.getElementById('app-content').style.opacity = '0.5';
            
            // Add a cache-busting timestamp to prevent aggressive browser caching of SPA fetches
            const urlObj = new URL(url, window.location.origin);
            urlObj.searchParams.set('_t', Date.now());
            const fetchUrl = urlObj.pathname + urlObj.search;
            
            return fetch(fetchUrl, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => {
                if (res.redirected) {
                    window.location.href = res.url;
                    return null;
                }
                if (!res.ok) throw new Error('Network error');
                return res.text();
            })
            .then(html => {
                if (html) {
                    injectHtmlAndRunScripts(html);
                    document.getElementById('app-content').style.opacity = '1';
                    window.history.pushState(null, '', url);
                    
                    // Update dynamic page title
                    updatePageTitle(url);

                    // Update active state in sidebar, header, and footer
                    updateActiveSidebarMenu();
                    
                    // Close sidebar on mobile
                    closeSidebar();
                }
            })
            .catch(err => {
                document.getElementById('app-content').style.opacity = '1';
                Swal.fire('Error', 'Gagal memuat halaman', 'error');
            });
        };

        document.addEventListener('click', function(e) {
            let target = e.target.closest('a[data-spa]');
            if (target) {
                e.preventDefault();
                let url = target.getAttribute('href');
                window.loadPage(url);
            }
        });
        
        window.addEventListener('popstate', function() {
            document.getElementById('app-content').style.opacity = '0.5';
            const urlObj = new URL(window.location.href);
            urlObj.searchParams.set('_t', Date.now());
            const fetchUrl = urlObj.pathname + urlObj.search;
            
            fetch(fetchUrl, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => { 
                injectHtmlAndRunScripts(html);
                document.getElementById('app-content').style.opacity = '1';
                
                // Update dynamic page title
                updatePageTitle(window.location.pathname);

                updateActiveSidebarMenu();
            });
        });

        // Global Helper for SweetAlert2 Action
        window.confirmAction = function(title, text, url, confirmText = 'Ya, lanjutkan!') {
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ba1a1a',
                cancelButtonColor: '#c6c5d4',
                confirmButtonText: confirmText,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        };

        // Global Dynamic File Preview Overlay with Fullscreen Zoom Capabilities
        window.viewAttachmentGlobal = function(url, title, subtitle = '') {
            const modal = document.getElementById('globalFilePreviewModal');
            const titleEl = document.getElementById('globalFilePreviewTitle');
            const subtitleEl = document.getElementById('globalFilePreviewSubtitle');
            const bodyEl = document.getElementById('globalFilePreviewBody');
            
            titleEl.innerText = title;
            if (subtitle) {
                subtitleEl.innerText = subtitle;
                subtitleEl.classList.remove('hidden');
            } else {
                subtitleEl.classList.add('hidden');
            }
            
            bodyEl.innerHTML = '';
            
            const lowerUrl = url.toLowerCase();
            if (lowerUrl.includes('.pdf') || lowerUrl.endsWith('.pdf')) {
                // PDF Document Stream Viewer
                const iframe = document.createElement('iframe');
                iframe.src = url;
                iframe.className = "w-full h-[420px] rounded-xl border-0 bg-white";
                bodyEl.appendChild(iframe);
            } else {
                // Image Receipt/Surat Dokter Viewer
                const img = document.createElement('img');
                img.src = url;
                img.className = "max-w-full max-h-[380px] object-contain rounded-xl shadow-sm border border-outline-variant/20 cursor-zoom-in hover:scale-[1.01] transition-transform select-none";
                img.alt = title;
                img.onclick = function() {
                    window.openFullscreenImage(url);
                };
                bodyEl.appendChild(img);
            }
            
            // Configure download button
            const downloadBtn = document.getElementById('globalFilePreviewDownloadBtn');
            if (downloadBtn) {
                downloadBtn.href = url;
                let filename = url.substring(url.lastIndexOf('/') + 1);
                if (!filename || filename.indexOf('.') === -1) {
                    const isPdf = lowerUrl.includes('.pdf') || lowerUrl.endsWith('.pdf');
                    filename = title.replace(/[^a-zA-Z0-9]/g, '_') + (isPdf ? '.pdf' : '.jpg');
                }
                downloadBtn.download = filename;
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeGlobalFilePreview = function() {
            const modal = document.getElementById('globalFilePreviewModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('globalFilePreviewBody').innerHTML = '';
        };

        window.openFullscreenImage = function(url) {
            const overlay = document.getElementById('fullscreenImageOverlay');
            const img = document.getElementById('fullscreenImageSrc');
            img.src = url;
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            document.body.style.overflow = 'hidden'; // Lock scrolling
        };

        window.closeFullscreenImage = function() {
            const overlay = document.getElementById('fullscreenImageOverlay');
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            document.getElementById('fullscreenImageSrc').src = '';
            document.body.style.overflow = ''; // Unlock scrolling
        };
    </script>

    <!-- Global File Preview Modal -->
    <div id="globalFilePreviewModal" class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-surface-container-lowest w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl flex flex-col border border-outline-variant/10 animate-fade-in relative p-6">
            <!-- Close Button at top-right of modal -->
            <button onclick="closeGlobalFilePreview()" class="absolute top-4 right-4 p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-high transition-all cursor-pointer z-10">
                <span class="material-symbols-outlined">close</span>
            </button>
            
            <div class="flex flex-col items-center w-full">
                <!-- Modal Title -->
                <h3 id="globalFilePreviewTitle" class="text-lg font-extrabold text-on-surface font-headline mb-1 text-center">Pratinjau Berkas</h3>
                <!-- Modal Subtitle -->
                <p id="globalFilePreviewSubtitle" class="text-xs font-semibold text-on-surface-variant mb-5 text-center">Detail lampiran berkas</p>
                
                <!-- Preview Body Container -->
                <div id="globalFilePreviewBody" class="w-full flex justify-center items-center bg-surface-container-low rounded-2xl overflow-hidden relative border border-outline-variant/10 p-2 min-h-[200px]">
                    <!-- Populated dynamically via JS -->
                </div>
                
                <!-- Centered TUTUP and DOWNLOAD buttons -->
                <div class="flex justify-center gap-3 mt-6 w-full">
                    <button onclick="closeGlobalFilePreview()" class="px-6 py-2.5 bg-surface-container-high hover:bg-surface-container-high/80 text-on-surface-variant font-extrabold text-xs rounded-xl shadow-sm transition-all cursor-pointer">
                        Tutup
                    </button>
                    <a id="globalFilePreviewDownloadBtn" href="" download class="px-6 py-2.5 bg-[#000666] hover:bg-[#000666]/95 text-white font-extrabold text-xs rounded-xl shadow transition-all cursor-pointer flex items-center gap-1.5 justify-center">
                        <span class="material-symbols-outlined text-sm font-bold">download</span> Download
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Fullscreen Image Zoom Overlay -->
    <div id="fullscreenImageOverlay" class="fixed inset-0 z-[110] bg-black/95 hidden flex items-center justify-center animate-fade-in">
        <!-- Close Button at top-right of page -->
        <button onclick="closeFullscreenImage()" class="absolute top-6 right-6 p-3 bg-white/10 hover:bg-white/20 text-white rounded-full transition-all cursor-pointer z-[120] flex items-center justify-center shadow-lg">
            <span class="material-symbols-outlined text-2xl font-bold">close</span>
        </button>
        <img id="fullscreenImageSrc" src="" class="max-w-full max-h-full object-contain p-4 select-none" />
    </div>
</body>
</html>
