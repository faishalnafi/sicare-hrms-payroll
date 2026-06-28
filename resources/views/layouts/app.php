<?php
$db = \App\Config\Database::getInstance()->getConnection();
$appName = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_name' LIMIT 1")->fetchColumn() ?: 'siCare';
$appLogoImage = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_logo_image' LIMIT 1")->fetchColumn() ?: '';
$appIdleTimeoutSec = (int)($db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_idle_timeout_sec' LIMIT 1")->fetchColumn() ?: 0);
$appIdleCountdownSec = (int)($db->query("SELECT `value` FROM global_settings WHERE `key` = 'app_idle_countdown_sec' LIMIT 1")->fetchColumn() ?: 0);
$googleMapsApiKey = $db->query("SELECT `value` FROM global_settings WHERE `key` = 'google_maps_api_key' LIMIT 1")->fetchColumn() ?: '';


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
    'system-settings' => 'Pengaturan Sistem',
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
    
    <!-- PWA Meta Tags & Manifest -->
    <meta name="theme-color" content="#000666"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($appName) ?>"/>
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($appLogoImage ?: '/images/icons/icon-192x192.png') ?>"/>
    <link rel="manifest" href="/manifest.php"/>

    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- Google Maps API -->
    <?php if (!empty($googleMapsApiKey)): ?>
        <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($googleMapsApiKey) ?>&libraries=places"></script>
    <?php endif; ?>
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
    <style>
        .att-stat-card, .kpi-card, .stat-card-scale {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .att-stat-card:hover, .kpi-card:hover, .stat-card-scale:hover {
            transform: translateY(-3px) scale(1.025) !important;
            box-shadow: 0 10px 25px rgba(0, 6, 102, 0.08) !important;
        }
    </style>
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION["user_id"]) && empty($_SESSION["jwt_token"])) {
        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, first_name, last_name, email, role FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION["user_id"]]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user) {
                $jwtPayload = [
                    "user_id" => $user["id"],
                    "name" => $user["first_name"] . " " . $user["last_name"],
                    "email" => $user["email"],
                    "role" => $user["role"]
                ];
                $_SESSION["jwt_token"] = \App\Config\JwtHelper::createToken($jwtPayload);
            }
        } catch (\Exception $e) {
            // Fail-safe fallback
        }
    }
    ?>
    <!-- Global JWT Token for CRUD authorization -->
    <script>
        window.jwtToken  = <?php echo json_encode($_SESSION["jwt_token"] ?? ""); ?>;
        window.csrfToken = <?php echo json_encode(csrf_token()); ?>;
    </script>
    <!-- Global Avatar Helper Functions -->
    <script>
        // Global Avatar Fallback Error Handler
        window.handleAvatarError = function(img, emailHash) {
            img.src = 'https://www.gravatar.com/avatar/' + emailHash + '?d=identicon&s=200';
            img.onerror = null;
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

        // Global DOM table sorter for static/PHP-rendered tables
        window.sortDomTable = function(thElement, columnIndex, dataType) {
            var table = thElement.closest('table');
            var tbody = table.querySelector('tbody');
            var rows = Array.from(tbody.querySelectorAll('tr:not(.empty-row):not(.loading-row):not(.empty-table-row)'));
            
            var dir = thElement.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';
            thElement.setAttribute('data-sort-dir', dir);
            
            // Reset and update sort direction classes
            var headers = thElement.parentNode.querySelectorAll('th');
            headers.forEach(function(th) {
                th.classList.remove('sort-active-asc', 'sort-active-desc');
            });
            thElement.classList.add(dir === 'asc' ? 'sort-active-asc' : 'sort-active-desc');
            
            rows.sort(function(rowA, rowB) {
                var cellA = rowA.cells[columnIndex];
                var cellB = rowB.cells[columnIndex];
                
                var valA = cellA ? cellA.innerText.trim() : '';
                var valB = cellB ? cellB.innerText.trim() : '';
                
                if (dataType === 'number') {
                    var numA = parseFloat(valA.replace(/[^\d.-]/g, '')) || 0;
                    var numB = parseFloat(valB.replace(/[^\d.-]/g, '')) || 0;
                    return dir === 'asc' ? numA - numB : numB - numA;
                } else if (dataType === 'date') {
                    var parseDateVal = function(s) {
                        if (!s) return 0;
                        s = s.trim();
                        // Support DD/MM/YYYY HH:MM:SS or DD-MM-YYYY HH:MM:SS
                        var match = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?/);
                        if (match) {
                            var day = parseInt(match[1]);
                            var month = parseInt(match[2]) - 1;
                            var year = parseInt(match[3]);
                            var hour = parseInt(match[4]) || 0;
                            var minute = parseInt(match[5]) || 0;
                            var second = parseInt(match[6]) || 0;
                            return new Date(year, month, day, hour, minute, second).getTime();
                        }
                        var monthsMap = {
                            'januari': 0, 'februari': 1, 'maret': 2, 'april': 3, 'mei': 4, 'juni': 5,
                            'juli': 6, 'agustus': 7, 'september': 8, 'oktober': 9, 'november': 10, 'desember': 11
                        };
                        var parts = s.toLowerCase().replace(/,/g, '').split(/\s+/);
                        if (parts.length >= 3) {
                            var day = parseInt(parts[0]) || 1;
                            var month = monthsMap[parts[1]] !== undefined ? monthsMap[parts[1]] : (parseInt(parts[1]) - 1 || 0);
                            var year = parseInt(parts[2]) || 1970;
                            return new Date(year, month, day).getTime();
                        }
                        var d = Date.parse(s);
                        return isNaN(d) ? 0 : d;
                    };
                    return dir === 'asc' ? parseDateVal(valA) - parseDateVal(valB) : parseDateVal(valB) - parseDateVal(valA);
                } else {
                    return dir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
                }
            });
            
            // Re-append rows in sorted order
            rows.forEach(function(row) {
                tbody.appendChild(row);
            });
            
            // Re-index the sequence number if there is a 'No' column!
            var hasNoColumn = table.querySelector('thead th.no-col') !== null;
            if (hasNoColumn) {
                var startIdx = 1;
                rows.forEach(function(row) {
                    if (row.cells[0]) {
                        row.cells[0].innerText = startIdx++;
                    }
                });
            }
        };

        // Automatic Table Standardizer Framework
        // Automatic Table Standardizer Framework
        (function() {
            var isStandardizing = false;

            function standardizeAllTables() {
                if (isStandardizing) return;
                isStandardizing = true;

                try {
                    document.querySelectorAll('table').forEach(function(table) {
                        // 1. Ensure table has table-standardized class
                        table.classList.add('table-standardized');

                        // 2. Ensure parent has proper responsive wrapper
                        var parent = table.parentNode;
                        if (parent && !parent.classList.contains('overflow-x-auto') && !parent.classList.contains('table-wrapper-standardized')) {
                            var wrapper = document.createElement('div');
                            wrapper.className = 'table-wrapper-standardized overflow-x-auto w-full max-w-full rounded-2xl border border-outline-variant/15';
                            parent.insertBefore(wrapper, table);
                            wrapper.appendChild(table);
                            parent = wrapper;
                        }
                        if (parent) {
                            parent.classList.add('table-wrapper-standardized');
                            parent.style.setProperty('overflow-x', 'auto', 'important');
                            parent.style.setProperty('width', '100%', 'important');
                            parent.style.setProperty('max-width', '100%', 'important');
                        }

                        // 3. Ensure thead has 'No' column & clean header titles
                        if (table.dataset.headersProcessed !== 'true') {
                            var thead = table.querySelector('thead');
                            if (thead) {
                                var headerRows = thead.querySelectorAll('tr');
                                headerRows.forEach(function(hr) {
                                    if (!hr.querySelector('.no-col')) {
                                        var th = document.createElement('th');
                                        th.className = 'no-col w-12 text-center py-4 px-6 text-[10px] font-extrabold uppercase tracking-wider select-none';
                                        th.innerText = 'No';
                                        hr.insertBefore(th, hr.firstChild);
                                    }

                                    // Add/recreate sort arrows to other headers
                                    var headers = Array.from(hr.querySelectorAll('th:not(.no-col)'));
                                    headers.forEach(function(th, idx) {
                                        // Clone th and remove any sort icon containers to get clean title
                                        var tempTh = th.cloneNode(true);
                                        var oldIcons = tempTh.querySelector('.sort-icon-container');
                                        if (oldIcons) oldIcons.remove();
                                        
                                        var title = tempTh.innerText.trim();
                                        if (!title) return;

                                        // Split header with parenthesis into exactly two lines
                                        var parenMatch = title.match(/^([^(]+)\s*(\([^)]+\))$/);
                                        var innerHtml = '';
                                        if (parenMatch) {
                                            var mainText = parenMatch[1].trim();
                                            var parenText = parenMatch[2].trim();
                                            innerHtml = '<div class="flex flex-col text-left">' +
                                                            '<span class="whitespace-nowrap">' + mainText + '</span>' +
                                                            '<span class="text-[9px] font-normal text-on-surface-variant/70 normal-case whitespace-nowrap">' + parenText + '</span>' +
                                                        '</div>';
                                        } else {
                                            innerHtml = '<span class="whitespace-nowrap">' + title + '</span>';
                                        }

                                        th.innerHTML = '<div class="flex items-center ' + (th.classList.contains('text-right') || th.style.textAlign === 'right' ? 'justify-end' : '') + ' gap-1">' + 
                                            innerHtml + 
                                            '<span class="sort-icon-container"></span></div>';

                                        var dataType = 'string';
                                        var lowerTitle = title.toLowerCase();
                                        if (lowerTitle.indexOf('gaji') !== -1 || lowerTitle.indexOf('jumlah') !== -1 || lowerTitle.indexOf('nominal') !== -1 || lowerTitle.indexOf('durasi') !== -1 || lowerTitle.indexOf('kuota') !== -1 || lowerTitle.indexOf('total') !== -1 || lowerTitle.indexOf('potongan') !== -1) {
                                            dataType = 'number';
                                        } else if (lowerTitle.indexOf('tanggal') !== -1 || lowerTitle.indexOf('waktu') !== -1 || lowerTitle.indexOf('periode') !== -1 || lowerTitle.indexOf('clock') !== -1) {
                                            dataType = 'date';
                                        }

                                        if (!th.getAttribute('onclick')) {
                                            th.addEventListener('click', function() {
                                                window.sortDomTable(th, idx + 1, dataType);
                                            });
                                            th.classList.add('cursor-pointer', 'select-none');
                                        }
                                    });
                                });
                            }
                            table.dataset.headersProcessed = 'true';
                        }

                        // 4. Force inline nowrap on all cells and descendants to ensure max 2 lines
                        // Note: Handled efficiently via CSS rules to prevent layout thrashing

                        // 5. Prepend No cell to tbody rows
                        var tbody = table.querySelector('tbody');
                        if (tbody) {
                            var rows = Array.from(tbody.querySelectorAll('tr'));
                            var validIndex = 1;
                            var thCount = table.querySelectorAll('thead th').length;
                            rows.forEach(function(row) {
                                if (row.querySelector('[colspan]') || row.classList.contains('empty-row') || row.classList.contains('loading-row') || row.classList.contains('empty-table-row')) return;
                                
                                if (!row.querySelector('.no-col-cell') && !row.dataset.rowIndexed) {
                                    if (row.children.length >= thCount && thCount > 0) {
                                        var firstTd = row.firstElementChild;
                                        if (firstTd) {
                                            firstTd.classList.add('no-col-cell');
                                            firstTd.innerText = validIndex++;
                                        }
                                    } else {
                                        var td = document.createElement('td');
                                        td.className = 'no-col-cell px-6 py-4 text-center font-bold text-xs text-on-surface-variant';
                                        td.innerText = validIndex++;
                                        row.insertBefore(td, row.firstChild);
                                    }
                                    row.dataset.rowIndexed = 'true';
                                } else {
                                    var td = row.querySelector('.no-col-cell');
                                    if (td) {
                                        td.innerText = validIndex++;
                                    }
                                }
                            });
                        }

                        // 6. Default Sort Logic (jika belum disort)
                        var validRows = Array.from(tbody.querySelectorAll('tr:not(.empty-row):not(.loading-row):not(.empty-table-row)'));
                        if (table.dataset.defaultSorted !== 'true' && validRows.length > 0) {
                            var firstDataHeader = table.querySelector('thead th:not(.no-col)');
                            if (firstDataHeader) {
                                var dataType = 'string';
                                var title = firstDataHeader.innerText.trim();
                                var lowerTitle = title.toLowerCase();
                                if (lowerTitle.indexOf('gaji') !== -1 || lowerTitle.indexOf('jumlah') !== -1 || lowerTitle.indexOf('nominal') !== -1 || lowerTitle.indexOf('durasi') !== -1 || lowerTitle.indexOf('kuota') !== -1 || lowerTitle.indexOf('total') !== -1 || lowerTitle.indexOf('potongan') !== -1) {
                                    dataType = 'number';
                                } else if (lowerTitle.indexOf('tanggal') !== -1 || lowerTitle.indexOf('waktu') !== -1 || lowerTitle.indexOf('periode') !== -1 || lowerTitle.indexOf('clock') !== -1) {
                                    dataType = 'date';
                                }

                                // Tentukan arah default
                                var defaultDir = (dataType === 'string') ? 'asc' : 'desc';
                                // Atur agar window.sortDomTable menghasilkan defaultDir
                                firstDataHeader.setAttribute('data-sort-dir', defaultDir === 'asc' ? 'desc' : 'asc');
                                
                                // Jalankan sort
                                window.sortDomTable(firstDataHeader, 1, dataType);
                            }
                            table.dataset.defaultSorted = 'true';
                        }

                        // 7. Global Pagination (max 10 rows per page)
                        if (table.classList.contains('no-pagination') || table.dataset.hasCustomPagination === 'true') {
                            table.dataset.tableStandardized = 'true';
                            return;
                        }
                        
                        var rowsAfterSort = Array.from(tbody.querySelectorAll('tr:not(.empty-row):not(.loading-row):not(.empty-table-row)'));
                        var wrapper = table.closest('.table-wrapper-standardized');

                        // Jika data <= 10 atau 1 page, tampilkan semua & sembunyikan detail pagination
                        if (rowsAfterSort.length <= 10) {
                            rowsAfterSort.forEach(function(row) {
                                row.style.removeProperty('display');
                            });
                            if (wrapper) {
                                var oldPagination = wrapper.nextElementSibling;
                                if (oldPagination && oldPagination.classList.contains('table-pagination-container')) {
                                    oldPagination.remove();
                                }
                            }
                            table.dataset.paginated = 'false';
                            table.dataset.tableStandardized = 'true';
                            return;
                        }
                        
                        table.dataset.paginated = 'true';
                        var currentPage = parseInt(table.dataset.currentPage) || 1;
                        var totalRows = rowsAfterSort.length;
                        var totalPages = Math.ceil(totalRows / 10) || 1;
                        if (currentPage > totalPages) currentPage = totalPages;
                        table.dataset.currentPage = currentPage;

                        // Tampilkan baris sesuai halaman aktif
                        rowsAfterSort.forEach(function(row, idx) {
                            var start = (currentPage - 1) * 10;
                            var end = start + 10;
                            if (idx >= start && idx < end) {
                                row.style.removeProperty('display');
                            } else {
                                row.style.setProperty('display', 'none', 'important');
                            }
                        });

                        // Hapus pagination container lama jika ada
                        if (wrapper) {
                            var oldPagination = wrapper.nextElementSibling;
                            if (oldPagination && oldPagination.classList.contains('table-pagination-container')) {
                                oldPagination.remove();
                            }

                            // Buat pagination container baru
                            var paginationContainer = document.createElement('div');
                            paginationContainer.className = 'table-pagination-container flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 border-t border-outline-variant/15 bg-surface-container-low/30 rounded-b-2xl mt-[-1px]';

                            var startIdx = totalRows === 0 ? 0 : (currentPage - 1) * 10 + 1;
                            var endIdx = Math.min(currentPage * 10, totalRows);
                            var infoText = 'Menampilkan data ' + startIdx + ' sampai ' + endIdx + ' dari ' + totalRows;

                            var infoDiv = document.createElement('div');
                            infoDiv.className = 'table-pagination-info text-sm text-on-surface-variant font-medium';
                            infoDiv.innerText = infoText;
                            paginationContainer.appendChild(infoDiv);

                            var btnDiv = document.createElement('div');
                            btnDiv.className = 'table-pagination-buttons flex items-center gap-1.5';

                            // Tombol First Page
                            var btnFirst = document.createElement('button');
                            btnFirst.type = 'button';
                            btnFirst.className = 'p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent';
                            btnFirst.innerHTML = '<span class="material-symbols-outlined text-sm">first_page</span>';
                            btnFirst.title = 'Halaman Pertama';
                            btnFirst.disabled = currentPage === 1;
                            btnFirst.onclick = function() {
                                table.dataset.currentPage = 1;
                                standardizeAllTables();
                            };
                            btnDiv.appendChild(btnFirst);

                            // Tombol Previous
                            var btnPrev = document.createElement('button');
                            btnPrev.type = 'button';
                            btnPrev.className = 'p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent';
                            btnPrev.innerHTML = '<span class="material-symbols-outlined text-sm">chevron_left</span>';
                            btnPrev.title = 'Halaman Sebelumnya';
                            btnPrev.disabled = currentPage === 1;
                            btnPrev.onclick = function() {
                                table.dataset.currentPage = currentPage - 1;
                                standardizeAllTables();
                            };
                            btnDiv.appendChild(btnPrev);

                            // Halaman nomor (max 3, centered)
                            var startPage = currentPage - 1;
                            var endPage = currentPage + 1;
                            if (startPage < 1) {
                                startPage = 1;
                                endPage = Math.min(3, totalPages);
                            }
                            if (endPage > totalPages) {
                                endPage = totalPages;
                                startPage = Math.max(1, totalPages - 2);
                            }

                            for (var p = startPage; p <= endPage; p++) {
                                (function(pageNumber) {
                                    var btnPage = document.createElement('button');
                                    btnPage.type = 'button';
                                    btnPage.className = 'w-8 h-8 flex items-center justify-center rounded-full text-xs font-semibold transition-all border ';
                                    if (pageNumber === currentPage) {
                                        btnPage.className += 'bg-primary text-white border-primary shadow-sm';
                                    } else {
                                        btnPage.className += 'hover:bg-surface-container-high text-on-surface border-transparent';
                                    }
                                    btnPage.innerText = pageNumber;
                                    btnPage.onclick = function() {
                                        table.dataset.currentPage = pageNumber;
                                        standardizeAllTables();
                                    };
                                    btnDiv.appendChild(btnPage);
                                })(p);
                            }

                            // Tombol Next
                            var btnNext = document.createElement('button');
                            btnNext.type = 'button';
                            btnNext.className = 'p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent';
                            btnNext.innerHTML = '<span class="material-symbols-outlined text-sm">chevron_right</span>';
                            btnNext.title = 'Halaman Berikutnya';
                            btnNext.disabled = currentPage === totalPages;
                            btnNext.onclick = function() {
                                table.dataset.currentPage = currentPage + 1;
                                standardizeAllTables();
                            };
                            btnDiv.appendChild(btnNext);

                            // Tombol Last Page
                            var btnLast = document.createElement('button');
                            btnLast.type = 'button';
                            btnLast.className = 'p-2 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant disabled:opacity-30 disabled:cursor-not-allowed transition-colors border border-transparent disabled:hover:bg-transparent';
                            btnLast.innerHTML = '<span class="material-symbols-outlined text-sm">last_page</span>';
                            btnLast.title = 'Halaman Terakhir';
                            btnLast.disabled = currentPage === totalPages;
                            btnLast.onclick = function() {
                                table.dataset.currentPage = totalPages;
                                standardizeAllTables();
                            };
                            btnDiv.appendChild(btnLast);

                            paginationContainer.appendChild(btnDiv);
                            wrapper.parentNode.insertBefore(paginationContainer, wrapper.nextSibling);
                        }
                        
                        table.dataset.tableStandardized = 'true';
                    });
                } finally {
                    isStandardizing = false;
                }
            }

            window.standardizeAllTables = standardizeAllTables;

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', standardizeAllTables);
            } else {
                standardizeAllTables();
            }

            var standardizeTimeout;
            var tableObserver = new MutationObserver(function(mutations) {
                if (isStandardizing) return;
                var needsUpdate = false;
                for (var i = 0; i < mutations.length; i++) {
                    var added = mutations[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var node = added[j];
                        if (node.nodeType === 1) {
                            if (node.classList.contains('table-pagination-container') || 
                                node.closest('.table-pagination-container') ||
                                node.classList.contains('sort-icon-container')) {
                                continue;
                            }
                            needsUpdate = true;
                            break;
                        }
                    }
                    if (needsUpdate) break;
                }
                if (needsUpdate) {
                    clearTimeout(standardizeTimeout);
                    standardizeTimeout = setTimeout(standardizeAllTables, 30);
                }
            });
            tableObserver.observe(document.body, { childList: true, subtree: true });

            // Wheel event listener for horizontal table scroll
            document.addEventListener('wheel', function(e) {
                var wrapper = e.target.closest('.table-wrapper-standardized');
                if (wrapper) {
                    if (wrapper.scrollWidth > wrapper.clientWidth) {
                        e.preventDefault();
                        wrapper.scrollLeft += e.deltaY;
                    }
                }
            }, { passive: false });

            // Dynamic scrolling indicator for premium scrollbar visibility
            (function() {
                var scrollTimeout;
                window.addEventListener('scroll', function(e) {
                    var target = e.target;
                    if (target === document) {
                        document.documentElement.classList.add('is-scrolling');
                    } else if (target && target.classList) {
                        target.classList.add('is-scrolling');
                    }
                    
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(function() {
                        document.documentElement.classList.remove('is-scrolling');
                        document.querySelectorAll('.is-scrolling').forEach(function(el) {
                            el.classList.remove('is-scrolling');
                        });
                    }, 1000);
                }, { capture: true, passive: true });
            })();
        })();
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

            /* Enable overflow hidden/scroll for collapsed sidebar to keep bounds clean, nav remains scrollable */
            html.sync-sidebar-collapsed #appSidebar,
            #appSidebar.sidebar-collapsed {
                overflow: hidden !important;
            }
            html.sync-sidebar-collapsed #appSidebar nav,
            #appSidebar.sidebar-collapsed nav {
                overflow-y: auto !important;
                overflow-x: hidden !important;
            }
            html.sync-sidebar-collapsed #appSidebar > div,
            #appSidebar.sidebar-collapsed > div {
                overflow: visible !important;
            }
        }

        /* Floating Tooltip styling (placed outside desktop media query for clean global access) */
        #sidebar-floating-tooltip {
            position: fixed;
            pointer-events: none;
            background-color: rgba(25, 28, 29, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 9999;
            opacity: 0;
            transform: translateY(-50%) scale(0.9);
            transition: opacity 0.15s cubic-bezier(0.4, 0, 0.2, 1), transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            display: none;
        }
        #sidebar-floating-tooltip.show {
            display: block;
        }
        #sidebar-floating-tooltip.visible {
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }
        #sidebar-floating-tooltip.logout-tooltip {
            background-color: rgba(186, 26, 26, 0.95);
            border-color: rgba(255, 255, 255, 0.15);
        }
        #sidebar-floating-tooltip::before {
            content: "";
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: transparent rgba(25, 28, 29, 0.95) transparent transparent;
        }
        #sidebar-floating-tooltip.logout-tooltip::before {
            border-color: transparent rgba(186, 26, 26, 0.95) transparent transparent;
        }

        /* Custom Leaflet Control Layer Styling */
        .leaflet-control-layers {
            border: none !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
        }
        .leaflet-control-layers-toggle {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="%23454652"><path d="M12 16L1 9.5 12 3l11 6.5L12 16zm0 2.76l-9-5.33v2.85l9 5.33 9-5.33v-2.85l-9 5.33z"/></svg>') !important;
            background-size: 20px 20px !important;
            background-position: center !important;
            width: 40px !important;
            height: 40px !important;
        }
        .leaflet-control-layers-expanded {
            padding: 12px !important;
            background-color: #ffffff !important;
            border-radius: 12px !important;
            font-family: inherit !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            color: #334155 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
        }
        .leaflet-control-layers-expanded label {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            cursor: pointer !important;
            margin-bottom: 6px !important;
        }
        .leaflet-control-layers-expanded label:last-child {
            margin-bottom: 0 !important;
        }
        .leaflet-control-layers-expanded input[type="radio"] {
            cursor: pointer !important;
            accent-color: #000666 !important;
        }

        /* Map Type Selector Buttons */
        .map-type-btn {
            background-color: #ffffff;
            color: #454652;
            border-color: #c6c5d4;
        }
        .map-type-btn:hover {
            background-color: #f3f4f5;
            color: #000666;
            border-color: rgba(0,6,102,0.3);
        }
        .map-type-btn.map-type-active {
            box-shadow: 0 2px 8px rgba(0,6,102,0.25);
        }

        /* Standardized Table UI Styles */
        .table-standardized {
            min-width: 1100px !important;
            width: 100% !important;
            border-collapse: collapse !important;
            text-align: left !important;
            table-layout: auto !important;
        }
        .table-standardized td,
        .table-standardized th,
        .table-standardized td *,
        .table-standardized th * {
            white-space: nowrap !important;
            vertical-align: middle !important;
        }
        .table-standardized td .flex,
        .table-standardized td .grid,
        .table-standardized th .flex,
        .table-standardized th .grid {
            flex-wrap: nowrap !important;
        }
        .table-standardized th:not(.no-col) {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s ease;
        }
        .table-standardized th:not(.no-col):hover {
            background-color: var(--color-surface-container-high, #e7e8e9) !important;
        }
        .sort-icon-container {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            vertical-align: middle;
            margin-left: 6px;
            width: 10px;
            height: 14px;
            justify-content: center;
            line-height: 1;
            font-size: 8px;
            gap: 1px;
            opacity: 0.6;
        }
        .sort-icon-container::before {
            content: '▲';
            font-size: 7px;
            color: #b0b2c3;
            transition: all 0.2s ease;
        }
        .sort-icon-container::after {
            content: '▼';
            font-size: 7px;
            color: #b0b2c3;
            transition: all 0.2s ease;
        }
        
        th:hover .sort-icon-container {
            opacity: 1;
        }
        th:hover .sort-icon-container::before,
        th:hover .sort-icon-container::after {
            color: #82859f;
        }

        .sort-active-asc .sort-icon-container {
            opacity: 1 !important;
        }
        .sort-active-asc .sort-icon-container::before {
            color: var(--color-primary, #000666) !important;
            font-weight: 900 !important;
        }
        .sort-active-asc .sort-icon-container::after {
            color: #b0b2c3 !important;
        }

        .sort-active-desc .sort-icon-container {
            opacity: 1 !important;
        }
        .sort-active-desc .sort-icon-container::after {
            color: var(--color-primary, #000666) !important;
            font-weight: 900 !important;
        }
        .sort-active-desc .sort-icon-container::before {
            color: #b0b2c3 !important;
        }

        /* Premium Custom Scrollbars */
        ::-webkit-scrollbar {
            width: 6px !important;
            height: 6px !important;
        }
        ::-webkit-scrollbar-track {
            background: transparent !important;
        }
        ::-webkit-scrollbar-thumb {
            background-color: transparent !important;
            border-radius: 10px !important;
            border: 1px solid transparent !important;
            background-clip: padding-box !important;
            transition: background-color 0.3s ease !important;
        }
        /* Show on hover or scroll activity */
        *:hover::-webkit-scrollbar-thumb,
        html:hover::-webkit-scrollbar-thumb,
        body:hover::-webkit-scrollbar-thumb,
        .is-scrolling::-webkit-scrollbar-thumb,
        html.is-scrolling::-webkit-scrollbar-thumb,
        body.is-scrolling::-webkit-scrollbar-thumb {
            background-color: rgba(0, 6, 102, 0.15) !important;
        }
        /* Darker on thumb hover */
        ::-webkit-scrollbar-thumb:hover {
            background-color: rgba(0, 6, 102, 0.45) !important;
        }
        ::-webkit-scrollbar-thumb:active {
            background-color: rgba(0, 6, 102, 0.6) !important;
        }

        /* Firefox Support */
        * {
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
        }
        *:hover,
        .is-scrolling {
            scrollbar-color: rgba(0, 6, 102, 0.15) transparent;
        }

        .table-wrapper-standardized {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: auto !important;
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
    <?php if (!isset($_SESSION['original_user_id'])): ?>
        <button id="mobileSidebarToggle" class="lg:hidden fixed top-4 left-4 z-40 p-3 bg-surface-container-lowest border border-outline-variant/20 shadow-md rounded-full text-on-surface-variant hover:bg-surface-container-high transition-colors flex items-center justify-center">
            <span class="material-symbols-outlined">menu</span>
        </button>
    <?php endif; ?>
    
    <!-- Middle Container -->
    <div class="flex-grow flex relative min-w-0 w-full">
        <!-- Sidebar -->
        <?php require __DIR__ . '/../parts/app_sidebar.php'; ?>
        
        <!-- Right Content Container (Pushed by collapsed sidebar) -->
        <div id="contentContainer" class="content-container flex-grow flex flex-col min-w-0 lg:pl-28 xl:pl-80 transition-all duration-300">
            <?php if (isset($_SESSION['original_user_id'])): ?>
                <div class="mx-4 mt-4 lg:mx-8 lg:mt-4 bg-gradient-to-r from-amber-500 to-orange-600 text-white px-6 py-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-lg rounded-2xl border border-orange-600/20 z-30 text-sm font-semibold sticky top-4 backdrop-blur-md bg-opacity-95">
                    <div class="flex items-center gap-2.5">
                        <!-- Mobile Hamburger inside the banner -->
                        <button onclick="openSidebar()" class="lg:hidden p-1.5 bg-white/20 hover:bg-white/35 text-white rounded-lg transition-colors flex items-center justify-center mr-1">
                            <span class="material-symbols-outlined text-xl">menu</span>
                        </button>
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
                    <?php
                    $roleLabel = 'Portal Karyawan';
                    if (isset($_SESSION['role'])) {
                        $role = $_SESSION['role'];
                        if ($role === 'superadmin') {
                            $roleLabel = 'Portal Superadmin';
                        } elseif ($role === 'admin') {
                            $roleLabel = 'Portal Admin';
                        } elseif ($role === 'hr_ops') {
                            $roleLabel = 'Portal HR Ops';
                        } elseif ($role === 'hiring_manager') {
                            $roleLabel = 'Portal Manager';
                        } elseif ($role === 'executive') {
                            $roleLabel = 'Portal Eksekutif';
                        } elseif ($role === 'candidate') {
                            $roleLabel = 'Portal Kandidat';
                        }
                    }
                    ?>
                    <span class="text-on-surface-variant text-sm font-medium"><?= htmlspecialchars($roleLabel) ?></span>
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
            

            // 1. Reset & Highlight Submenu Groups
            document.querySelectorAll('.submenu-group').forEach(group => {
                let btn = group.querySelector('button');
                let container = group.querySelector('.submenu-container');
                let parentDiv = btn ? btn.querySelector('div') : null;
                let parentIcon = parentDiv ? parentDiv.querySelector('.material-symbols-outlined:first-child') : null;
                let parentText = parentDiv ? parentDiv.querySelector('span:not(.material-symbols-outlined)') : null;
                let arrow = parentDiv ? parentDiv.querySelector('.submenu-arrow') : null;
                
                let hasActiveChild = false;
                group.querySelectorAll('a').forEach(a => {
                    let linkPath = a.getAttribute('href');
                    if (!linkPath) return;
                    let cleanLink = linkPath.toLowerCase().replace(/\/$/, '');
                    if (currentPath === cleanLink || currentPath.endsWith(cleanLink)) {
                        hasActiveChild = true;
                    }
                });
                
                if (hasActiveChild) {
                    if (container) container.classList.remove('hidden');
                    if (parentDiv) parentDiv.className = "rounded-xl p-3 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-3 group cursor-pointer transition-all duration-200 bg-primary/5 text-primary font-bold";
                    if (parentIcon) parentIcon.className = "material-symbols-outlined flex-shrink-0 transition-colors text-primary";
                    if (parentText) parentText.className = "text-sm font-medium flex-grow whitespace-nowrap transition-colors text-primary font-bold lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden";
                    if (arrow) arrow.className = "submenu-arrow material-symbols-outlined text-sm flex-shrink-0 ml-auto transition-transform duration-200 lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden rotate-180 text-primary";
                } else {
                    if (parentDiv) parentDiv.className = "rounded-xl p-3 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-3 group cursor-pointer transition-all duration-200 text-on-surface-variant hover:bg-surface-container-low hover:text-primary transition-all duration-200";
                    if (parentIcon) parentIcon.className = "material-symbols-outlined flex-shrink-0 transition-colors text-on-surface-variant group-hover:text-primary";
                    if (parentText) parentText.className = "text-sm font-medium flex-grow whitespace-nowrap transition-colors text-on-surface-variant group-hover:text-primary lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden";
                    if (arrow) arrow.className = "submenu-arrow material-symbols-outlined text-sm flex-shrink-0 ml-auto transition-transform duration-200 lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden text-on-surface-variant/50";
                }
            });

            // 2. Highlight Active Sidebar Links
            document.querySelectorAll('#appSidebar nav a').forEach(a => {
                let linkPath = a.getAttribute('href');
                if (!linkPath) return;
                let div = a.querySelector('div');
                let icon = a.querySelector('.material-symbols-outlined');
                let text = a.querySelector('span:not(.material-symbols-outlined)');
                let chevron = a.querySelector('span.material-symbols-outlined:last-child');
                
                let cleanCurrent = currentPath;
                let cleanLink = linkPath.toLowerCase().replace(/\/$/, '');
                
                let isActive = (cleanCurrent === cleanLink || cleanCurrent.endsWith(cleanLink));
                let isChild = a.closest('.submenu-container') !== null;
                
                if (isChild) {
                    if (isActive) {
                        if (div) div.className = "rounded-xl px-3 py-2 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-2.5 group cursor-pointer transition-all duration-200 bg-primary/10 text-primary font-bold shadow-[0_2px_8px_rgba(0,6,102,0.02)]";
                        if (icon) icon.className = "material-symbols-outlined text-[18px] flex-shrink-0 transition-colors text-primary";
                        if (text) text.className = "text-xs font-medium flex-grow whitespace-nowrap transition-colors text-primary font-bold lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden";
                    } else {
                        if (div) div.className = "rounded-xl px-3 py-2 flex items-center justify-start lg:justify-center lg:group-hover:justify-start xl:justify-start gap-2.5 group cursor-pointer transition-all duration-200 text-on-surface-variant hover:bg-surface-container-low hover:text-primary";
                        if (icon) icon.className = "material-symbols-outlined text-[18px] flex-shrink-0 transition-colors text-on-surface-variant/70 group-hover:text-primary";
                        if (text) text.className = "text-xs font-medium flex-grow whitespace-nowrap transition-colors text-on-surface-variant group-hover:text-primary lg:opacity-0 lg:group-hover:opacity-100 lg:w-0 lg:group-hover:w-auto xl:opacity-100 xl:w-auto overflow-hidden";
                    }
                } else {
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

            // Floating Tooltip for collapsed sidebar
            const tooltipEl = document.createElement('div');
            tooltipEl.id = 'sidebar-floating-tooltip';
            document.body.appendChild(tooltipEl);
            
            let hoverTimeout;
            
            document.addEventListener('mouseover', function(e) {
                const sidebar = document.getElementById('appSidebar');
                if (!sidebar || !sidebar.classList.contains('sidebar-collapsed')) {
                    tooltipEl.classList.remove('visible', 'show');
                    return;
                }
                
                const target = e.target.closest('#appSidebar [data-tooltip]');
                if (!target) {
                    hideTooltip();
                    return;
                }
                
                const tooltipText = target.getAttribute('data-tooltip');
                if (!tooltipText) {
                    hideTooltip();
                    return;
                }
                
                clearTimeout(hoverTimeout);
                tooltipEl.textContent = tooltipText;
                
                if (tooltipText === 'Keluar') {
                    tooltipEl.classList.add('logout-tooltip');
                } else {
                    tooltipEl.classList.remove('logout-tooltip');
                }
                
                tooltipEl.classList.add('show');
                
                // Position the tooltip next to the target
                const rect = target.getBoundingClientRect();
                const x = rect.right + 12;
                const y = rect.top + rect.height / 2;
                
                tooltipEl.style.left = `${x}px`;
                tooltipEl.style.top = `${y}px`;
                
                requestAnimationFrame(() => {
                    tooltipEl.classList.add('visible');
                });
            });
            
            document.addEventListener('mouseout', function(e) {
                const sidebar = document.getElementById('appSidebar');
                if (!sidebar || !sidebar.classList.contains('sidebar-collapsed')) return;
                
                const target = e.target.closest('#appSidebar [data-tooltip]');
                if (target) {
                    const related = e.relatedTarget ? e.relatedTarget.closest('#appSidebar [data-tooltip]') : null;
                    if (related === target) {
                        return;
                    }
                }
                hideTooltip();
            });
            
            function hideTooltip() {
                clearTimeout(hoverTimeout);
                tooltipEl.classList.remove('visible');
                hoverTimeout = setTimeout(() => {
                    if (!tooltipEl.classList.contains('visible')) {
                        tooltipEl.classList.remove('show');
                    }
                }, 150);
            }
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

            // Trigger table standardization explicitly after SPA content injection
            if (typeof window.standardizeAllTables === 'function') {
                window.standardizeAllTables();
            }
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
                if (!res.ok) {
                    if (res.status >= 500) {
                        throw new Error('SERVER_ERROR_HANDLED');
                    }
                    throw new Error('Network error: ' + res.status);
                }
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
                if (err.message !== 'SERVER_ERROR_HANDLED') {
                    Swal.fire('Error', 'Gagal memuat halaman (' + err.message + ')', 'error');
                }
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
            .then(res => {
                if (!res.ok) {
                    if (res.status >= 500) {
                        throw new Error('SERVER_ERROR_HANDLED');
                    }
                    throw new Error('Network error: ' + res.status);
                }
                return res.text();
            })
            .then(html => { 
                injectHtmlAndRunScripts(html);
                document.getElementById('app-content').style.opacity = '1';
                
                // Update dynamic page title
                updatePageTitle(window.location.pathname);

                updateActiveSidebarMenu();
            })
            .catch(err => {
                document.getElementById('app-content').style.opacity = '1';
                if (err.message !== 'SERVER_ERROR_HANDLED') {
                    Swal.fire('Error', 'Gagal memuat halaman (' + err.message + ')', 'error');
                }
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
            const lowerUrl = url.toLowerCase();
            const isPdf = lowerUrl.includes('.pdf') || lowerUrl.endsWith('.pdf');
            
            let fileName = url.split('/').pop().split('?')[0];
            if (url.includes('file=')) {
                fileName = decodeURIComponent(url.split('file=')[1].split('&')[0]);
            }
            if (!fileName || fileName.length > 40 || fileName.indexOf('.') === -1) {
                fileName = title.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase() + (isPdf ? '.pdf' : '.jpg');
            }

            let metaHtml = `
                <div class="text-left space-y-2 mt-2">
                    ${subtitle ? `<p class="text-xs text-on-surface-variant"><strong>Keterangan:</strong> ${subtitle}</p>` : ''}
                    <p class="text-xs text-on-surface-variant font-mono"><strong>Nama Berkas:</strong> ${fileName}</p>
                    <p class="text-[10px] text-green-700 bg-green-50 px-2 py-1 rounded inline-block font-bold">MIME TYPE VALID: Verified via Server finfo</p>
                </div>
            `;

            const swalConfig = {
                title: title,
                showDenyButton: true,
                confirmButtonText: 'Tutup Dokumen',
                denyButtonText: '<span class="material-symbols-outlined text-xs" style="vertical-align: middle; margin-right: 4px;">download</span>Unduh Berkas',
                confirmButtonColor: '#000666',
                denyButtonColor: '#ff6f00',
            };

            if (isPdf) {
                swalConfig.html = metaHtml + `
                    <div class="mt-4 p-6 bg-surface-container-low rounded-xl border border-outline-variant/10 flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-6xl text-red-600 mb-2">picture_as_pdf</span>
                        <p class="text-xs font-bold text-on-surface">Dokumen PDF Terdeteksi</p>
                        <a href="${url}" target="_blank" class="mt-3 bg-primary hover:bg-primary/95 text-white font-bold text-xs py-2 px-4 rounded flex items-center gap-1.5 transition-all">
                            <span class="material-symbols-outlined text-sm">open_in_new</span> Buka PDF di Tab Baru
                        </a>
                    </div>
                `;
            } else {
                const escapedUrl = url.replace(/'/g, "\\'");
                swalConfig.html = metaHtml + `
                    <div class="mt-4 bg-surface-container-low/50 p-2 rounded-xl border border-outline-variant/15 text-center group cursor-pointer" onclick="window.openFullscreenImage('${escapedUrl}')" title="Klik untuk memperbesar layar penuh">
                        <div class="relative overflow-hidden rounded-lg">
                            <img src="${url}" alt="${title}" class="max-h-[45vh] max-w-full mx-auto object-contain rounded-lg shadow-sm transition-transform duration-300 group-hover:scale-[1.02]" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300?text=Berkas+Tidak+Ditemukan';" />
                            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 text-white text-xs font-bold backdrop-blur-[1px]">
                                <span class="material-symbols-outlined text-base">zoom_in</span> Klik Untuk Perbesar (Full Screen)
                            </div>
                        </div>
                    </div>
                `;
            }

            Swal.fire(swalConfig).then((result) => {
                if (result.isDenied) {
                    window.location.href = url + (url.includes('?') ? '&' : '?') + 'download=1';
                }
            });
        };

        window.closeGlobalFilePreview = function() {
            const modal = document.getElementById('globalFilePreviewModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
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
    <div id="fullscreenImageOverlay" onclick="closeFullscreenImage()" class="fixed inset-0 z-[99999] bg-black/95 hidden flex items-center justify-center animate-fade-in cursor-zoom-out select-none p-4">
        <!-- Close Button at top-right of page -->
        <button onclick="closeFullscreenImage()" class="absolute top-6 right-6 w-12 h-12 aspect-square flex-shrink-0 bg-white/15 hover:bg-white/30 text-white rounded-full transition-all cursor-pointer z-[100000] flex items-center justify-center shadow-lg hover:scale-110 active:scale-95">
            <span class="material-symbols-outlined text-2xl font-bold">close</span>
        </button>
        <img id="fullscreenImageSrc" src="" onclick="event.stopPropagation()" class="max-h-[92vh] max-w-[92vw] object-contain shadow-2xl rounded-xl border border-white/10 cursor-default" />
    </div>

    <!-- Global Leaflet Map Modal -->
    <div id="globalLeafletMapModal" class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-surface-container-lowest w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl flex flex-col border border-outline-variant/10 animate-fade-in relative">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-outline-variant/15 bg-surface-container-low/10 flex items-center justify-between">
                <div>
                    <h3 id="globalLeafletMapTitle" class="text-sm font-extrabold text-on-surface font-headline flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">map</span>
                        Peta Lokasi Presensi
                    </h3>
                    <p id="globalLeafletMapSubtitle" class="text-[11px] text-on-surface-variant font-semibold">Detail lokasi check-in/out karyawan</p>
                </div>
                <button onclick="closeGlobalLeafletMap()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-high transition-all cursor-pointer">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <!-- Map Container -->
            <div class="p-6">
                <div class="relative w-full h-[350px]">
                    <div id="leafletMapElement" class="w-full h-full rounded-2xl border border-outline-variant/15 overflow-hidden shadow-inner bg-surface-container-low"></div>
                    
                    <!-- Premium Custom Map Controls (Google Maps Style) -->
                    <div class="absolute bottom-4 right-4 z-[1000] flex flex-col gap-2">
                        <!-- Geolocation Button -->
                        <button onclick="recenterLeafletMap()" type="button" title="Pusatkan Peta" class="w-10 h-10 bg-white hover:bg-slate-50 active:bg-slate-100 text-[#1a73e8] rounded-full shadow-[0_2px_8px_rgba(0,0,0,0.15)] border border-slate-100 flex items-center justify-center transition-all duration-200 cursor-pointer">
                            <span class="material-symbols-outlined" style="font-size: 20px; font-weight: 600; font-variation-settings: 'FILL' 1;">my_location</span>
                        </button>
                        
                        <!-- Zoom Controls -->
                        <div class="w-10 bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.15)] border border-slate-100 flex flex-col items-center overflow-hidden">
                            <button onclick="zoomInMap()" type="button" title="Perbesar" class="w-10 h-10 text-slate-600 hover:text-slate-800 hover:bg-slate-50 active:bg-slate-100 flex items-center justify-center transition-all duration-200 cursor-pointer">
                                <span class="material-symbols-outlined" style="font-size: 20px; font-weight: 600;">add</span>
                            </button>
                            <div class="w-6 h-[1px] bg-slate-100"></div>
                            <button onclick="zoomOutMap()" type="button" title="Perkecil" class="w-10 h-10 text-slate-600 hover:text-slate-800 hover:bg-slate-50 active:bg-slate-100 flex items-center justify-center transition-all duration-200 cursor-pointer">
                                <span class="material-symbols-outlined" style="font-size: 20px; font-weight: 600;">remove</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Map Type Selector -->
                <div class="mt-3 flex items-center gap-1.5" id="mapTypeSelector">
                    <button id="mapType_roadmap" onclick="switchMapType('roadmap')" type="button"
                        class="map-type-btn map-type-active flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-200 cursor-pointer border">
                        <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 0,'wght' 600;">map</span>
                        Jalan
                    </button>
                    <button id="mapType_satellite" onclick="switchMapType('satellite')" type="button"
                        class="map-type-btn flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-200 cursor-pointer border">
                        <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 0,'wght' 600;">satellite_alt</span>
                        Satelit
                    </button>
                    <button id="mapType_terrain" onclick="switchMapType('terrain')" type="button"
                        class="map-type-btn flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-200 cursor-pointer border">
                        <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 0,'wght' 600;">terrain</span>
                        Terrain
                    </button>
                </div>

                <!-- Map Legend -->
                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs font-semibold text-on-surface-variant">
                    <div id="leafletMapLegendClockIn" class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white shadow-sm"></span>
                        <span>Clock-In (Masuk)</span>
                    </div>
                    <div id="leafletMapLegendClockOut" class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded-full bg-rose-500 border-2 border-white shadow-sm"></span>
                        <span>Clock-Out (Pulang)</span>
                    </div>
                    <div id="leafletMapLegendAccess" class="flex items-center gap-1.5 hidden">
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white shadow-sm"></span>
                        <span>Lokasi Akses</span>
                    </div>
                    <div id="leafletMapLegendOffice" class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-sm border-2 border-white">
                            <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">corporate_fare</span>
                        </div>
                        <span>Kantor Pusat</span>
                    </div>
                    <div id="leafletMapLegendHome" class="flex items-center gap-2 hidden">
                        <div class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-sm border-2 border-white">
                            <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">home</span>
                        </div>
                        <span>Rumah Karyawan</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-outline-variant/15 flex justify-end bg-surface-container-low/10">
                <button onclick="closeGlobalLeafletMap()" class="px-5 py-2.5 bg-surface-container-high hover:bg-surface-container-high/80 text-on-surface-variant font-bold text-xs rounded-xl shadow-sm transition-all cursor-pointer">
                    Tutup Peta
                </button>
            </div>
        </div>
    </div>

    <script>
        let globalLeafletMapInstance = null;
        let globalGoogleMapInstance = null;
        let globalGoogleMarkers = [];
        let globalLeafletTileLayer = null;
        window.currentMapType = 'roadmap'; // 'roadmap' | 'satellite' | 'terrain'

        window.recenterLeafletMap = function() {
            if (globalGoogleMapInstance && globalGoogleMarkers && globalGoogleMarkers.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                globalGoogleMarkers.forEach(m => bounds.extend(m.getPosition()));
                globalGoogleMapInstance.fitBounds(bounds);
            } else if (globalLeafletMapInstance && globalLeafletMapInstance._markers && globalLeafletMapInstance._markers.length > 0) {
                const group = new L.featureGroup(globalLeafletMapInstance._markers);
                globalLeafletMapInstance.fitBounds(group.getBounds().pad(0.15));
            }
        };

        window.zoomInMap = function() {
            if (globalGoogleMapInstance) {
                globalGoogleMapInstance.setZoom(globalGoogleMapInstance.getZoom() + 1);
            } else if (globalLeafletMapInstance) {
                globalLeafletMapInstance.zoomIn();
            }
        };

        window.zoomOutMap = function() {
            if (globalGoogleMapInstance) {
                globalGoogleMapInstance.setZoom(globalGoogleMapInstance.getZoom() - 1);
            } else if (globalLeafletMapInstance) {
                globalLeafletMapInstance.zoomOut();
            }
        };

        // Switch map type/style (works for both Google Maps and Leaflet)
        window.switchMapType = function(type) {
            window.currentMapType = type;

            // Update button active states
            ['roadmap','satellite','terrain'].forEach(t => {
                const btn = document.getElementById('mapType_' + t);
                if (!btn) return;
                if (t === type) {
                    btn.classList.add('map-type-active');
                } else {
                    btn.classList.remove('map-type-active');
                }
            });

            if (globalGoogleMapInstance) {
                const typeMap = { roadmap: 'roadmap', satellite: 'hybrid', terrain: 'terrain' };
                globalGoogleMapInstance.setMapTypeId(typeMap[type] || 'roadmap');
            } else if (globalLeafletMapInstance) {
                if (globalLeafletTileLayer) {
                    globalLeafletMapInstance.removeLayer(globalLeafletTileLayer);
                }
                const tileLayers = {
                    roadmap: L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                        maxZoom: 20,
                        subdomains: ['mt0','mt1','mt2','mt3'],
                        attribution: '&copy; <a href="https://maps.google.com" target="_blank">Google Maps</a>'
                    }),
                    satellite: L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                        maxZoom: 20,
                        subdomains: ['mt0','mt1','mt2','mt3'],
                        attribution: '&copy; <a href="https://maps.google.com" target="_blank">Google Maps</a>'
                    }),
                    terrain: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
                    })
                };
                globalLeafletTileLayer = tileLayers[type] || tileLayers.roadmap;
                globalLeafletTileLayer.addTo(globalLeafletMapInstance);
                globalLeafletTileLayer.bringToBack();
            }
        };

        window.showLeafletMap = function(indexOrEmployeeName, config) {
            let employeeName = '';
            let mapConfig = {};

            if (typeof indexOrEmployeeName === 'number') {
                mapConfig = window.mapConfigs[indexOrEmployeeName];
                employeeName = mapConfig.employee_name || 'Staf';
            } else {
                employeeName = indexOrEmployeeName;
                mapConfig = config || {};
            }

            const isAccessTracker = mapConfig.work_mode === 'Access Log' || mapConfig.work_mode === 'Login Log';

            if (isAccessTracker) {
                document.getElementById('globalLeafletMapTitle').innerHTML = '<span class="material-symbols-outlined text-primary">map</span>Peta Lokasi Akses';
                document.getElementById('globalLeafletMapSubtitle').innerText = 'Detail lokasi akses aplikasi';
                document.getElementById('leafletMapLegendClockIn').classList.add('hidden');
                document.getElementById('leafletMapLegendClockOut').classList.add('hidden');
                document.getElementById('leafletMapLegendAccess').classList.remove('hidden');
            } else {
                document.getElementById('globalLeafletMapTitle').innerHTML = '<span class="material-symbols-outlined text-primary">map</span>Peta Lokasi Presensi';
                document.getElementById('globalLeafletMapSubtitle').innerText = `Detail lokasi presensi untuk: ${employeeName}`;
                document.getElementById('leafletMapLegendClockIn').classList.remove('hidden');
                document.getElementById('leafletMapLegendClockOut').classList.remove('hidden');
                document.getElementById('leafletMapLegendAccess').classList.add('hidden');
            }

            const modal = document.getElementById('globalLeafletMapModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Sync map type button states
            ['roadmap','satellite','terrain'].forEach(t => {
                const btn = document.getElementById('mapType_' + t);
                if (!btn) return;
                if (t === (window.currentMapType || 'roadmap')) {
                    btn.classList.add('map-type-active');
                } else {
                    btn.classList.remove('map-type-active');
                }
            });

            // Wait until modal is fully visible so map can calculate dimensions correctly
            setTimeout(() => {
                // Clear any existing map instance
                if (globalLeafletMapInstance) {
                    globalLeafletMapInstance.remove();
                    globalLeafletMapInstance = null;
                }
                if (globalGoogleMapInstance) {
                    globalGoogleMapInstance = null;
                    globalGoogleMarkers = [];
                }
                document.getElementById('leafletMapElement').innerHTML = '';

                // If coordinates are missing, show error message
                const inLat = mapConfig.in_lat ? parseFloat(mapConfig.in_lat) : null;
                const inLng = mapConfig.in_lng ? parseFloat(mapConfig.in_lng) : null;
                const outLat = mapConfig.out_lat ? parseFloat(mapConfig.out_lat) : null;
                const outLng = mapConfig.out_lng ? parseFloat(mapConfig.out_lng) : null;
                const officeLat = mapConfig.office_lat ? parseFloat(mapConfig.office_lat) : null;
                const officeLng = mapConfig.office_lng ? parseFloat(mapConfig.office_lng) : null;
                const officeRadius = mapConfig.office_radius ? parseInt(mapConfig.office_radius) : 50;
                const homeLat = mapConfig.home_lat ? parseFloat(mapConfig.home_lat) : null;
                const homeLng = mapConfig.home_lng ? parseFloat(mapConfig.home_lng) : null;
                const homeRadius = mapConfig.home_radius ? parseInt(mapConfig.home_radius) : 100;

                // Center view on available coordinates
                let centerLat = officeLat || -7.4561879;
                let centerLng = officeLng || 112.4465263;
                if (inLat && inLng) {
                    centerLat = inLat;
                    centerLng = inLng;
                }

                const hasGoogleMaps = typeof google !== 'undefined' && typeof google.maps !== 'undefined';

                if (hasGoogleMaps) {
                    // Initialize Google Map
                    const gmapTypeId = window.currentMapType === 'satellite' ? 'hybrid'
                        : window.currentMapType === 'terrain' ? 'terrain' : 'roadmap';
                    globalGoogleMapInstance = new google.maps.Map(document.getElementById('leafletMapElement'), {
                        center: { lat: centerLat, lng: centerLng },
                        zoom: 15,
                        mapTypeId: gmapTypeId,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false
                    });

                    globalGoogleMarkers = [];

                    // Office marker & circle
                    if (officeLat && officeLng) {
                        const officeMarker = new google.maps.Marker({
                            position: { lat: officeLat, lng: officeLng },
                            map: globalGoogleMapInstance,
                            title: "Kantor Pusat PT SI CARE",
                            icon: { url: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png" }
                        });
                        const info = new google.maps.InfoWindow({ content: "<b>Kantor Pusat PT SI CARE</b>" });
                        officeMarker.addListener('click', () => info.open(globalGoogleMapInstance, officeMarker));
                        
                        new google.maps.Circle({
                            strokeColor: '#3b82f6',
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                            fillColor: '#3b82f6',
                            fillOpacity: 0.1,
                            map: globalGoogleMapInstance,
                            center: { lat: officeLat, lng: officeLng },
                            radius: officeRadius
                        });
                        globalGoogleMarkers.push(officeMarker);
                    }

                    // Home marker & circle
                    if (!isAccessTracker && homeLat && homeLng) {
                        document.getElementById('leafletMapLegendHome').classList.remove('hidden');
                        const homeMarker = new google.maps.Marker({
                            position: { lat: homeLat, lng: homeLng },
                            map: globalGoogleMapInstance,
                            title: `Rumah Karyawan (${employeeName})`,
                            icon: { url: "https://maps.google.com/mapfiles/ms/icons/purple-dot.png" }
                        });
                        const info = new google.maps.InfoWindow({ content: `<b>Rumah Karyawan (${employeeName})</b>` });
                        homeMarker.addListener('click', () => info.open(globalGoogleMapInstance, homeMarker));

                        new google.maps.Circle({
                            strokeColor: '#6366f1',
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                            fillColor: '#6366f1',
                            fillOpacity: 0.1,
                            map: globalGoogleMapInstance,
                            center: { lat: homeLat, lng: homeLng },
                            radius: homeRadius
                        });
                        globalGoogleMarkers.push(homeMarker);
                    } else {
                        document.getElementById('leafletMapLegendHome').classList.add('hidden');
                    }

                    // Clock-in / Access marker
                    if (inLat && inLng) {
                        const popupText = isAccessTracker 
                            ? `<b>Lokasi Akses</b><br>Waktu: ${mapConfig.clock_in || '--:--'}`
                            : `<b>Clock-In (Masuk)</b><br>Waktu: ${mapConfig.clock_in || '--:--'}<br>Mode: ${mapConfig.work_mode || 'WFO'}`;

                        const inMarker = new google.maps.Marker({
                            position: { lat: inLat, lng: inLng },
                            map: globalGoogleMapInstance,
                            title: isAccessTracker ? "Lokasi Akses" : "Clock-In (Masuk)",
                            icon: { url: "https://maps.google.com/mapfiles/ms/icons/green-dot.png" }
                        });
                        const info = new google.maps.InfoWindow({ content: popupText });
                        inMarker.addListener('click', () => info.open(globalGoogleMapInstance, inMarker));
                        globalGoogleMarkers.push(inMarker);
                    }

                    // Clock-out marker
                    if (outLat && outLng) {
                        const outMarker = new google.maps.Marker({
                            position: { lat: outLat, lng: outLng },
                            map: globalGoogleMapInstance,
                            title: "Clock-Out (Pulang)",
                            icon: { url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png" }
                        });
                        const info = new google.maps.InfoWindow({ content: `<b>Clock-Out (Pulang)</b><br>Waktu: ${mapConfig.clock_out || '--:--'}<br>Mode: ${mapConfig.work_mode_out || '—'}` });
                        outMarker.addListener('click', () => info.open(globalGoogleMapInstance, outMarker));
                        globalGoogleMarkers.push(outMarker);
                    }

                    // Fit bounds to show all markers
                    if (globalGoogleMarkers.length > 0) {
                        const bounds = new google.maps.LatLngBounds();
                        globalGoogleMarkers.forEach(m => bounds.extend(m.getPosition()));
                        globalGoogleMapInstance.fitBounds(bounds);
                    }
                } else {
                    // Initialize Leaflet Map
                    globalLeafletMapInstance = L.map('leafletMapElement', { zoomControl: false }).setView([centerLat, centerLng], 15);

                    // Define tile layers for custom switcher
                    const leafletTileLayers = {
                        roadmap: L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                            maxZoom: 20,
                            subdomains: ['mt0','mt1','mt2','mt3'],
                            attribution: '&copy; <a href="https://maps.google.com" target="_blank">Google Maps</a>'
                        }),
                        satellite: L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                            maxZoom: 20,
                            subdomains: ['mt0','mt1','mt2','mt3'],
                            attribution: '&copy; <a href="https://maps.google.com" target="_blank">Google Maps</a>'
                        }),
                        terrain: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
                        })
                    };

                    // Apply current map type
                    const initType = window.currentMapType || 'roadmap';
                    globalLeafletTileLayer = leafletTileLayers[initType] || leafletTileLayers.roadmap;
                    globalLeafletTileLayer.addTo(globalLeafletMapInstance);

                    const markers = [];

                    // Office marker & circle
                    if (officeLat && officeLng) {
                        const officeMarker = L.marker([officeLat, officeLng], {
                            icon: L.divIcon({
                                className: '',
                                html: `
                                    <div class="flex items-center justify-center bg-blue-600 text-white rounded-full border-2 border-white shadow-[0_2px_8px_rgba(0,0,0,0.3)]" style="width: 32px; height: 32px;">
                                        <span class="material-symbols-outlined" style="font-size: 18px; font-weight: 600; font-variation-settings: 'FILL' 1;">corporate_fare</span>
                                    </div>
                                `,
                                iconSize: [32, 32],
                                iconAnchor: [16, 16],
                                popupAnchor: [0, -16]
                            })
                        }).addTo(globalLeafletMapInstance).bindPopup("<b>Kantor Pusat PT SI CARE</b>");
                        
                        L.circle([officeLat, officeLng], {
                            color: '#3b82f6',
                            fillColor: '#3b82f6',
                            fillOpacity: 0.1,
                            radius: officeRadius
                        }).addTo(globalLeafletMapInstance);
                        markers.push(officeMarker);
                    }

                    // Home marker & circle
                    if (!isAccessTracker && homeLat && homeLng) {
                        document.getElementById('leafletMapLegendHome').classList.remove('hidden');
                        const homeMarker = L.marker([homeLat, homeLng], {
                            icon: L.divIcon({
                                className: '',
                                html: `
                                    <div class="flex items-center justify-center bg-indigo-600 text-white rounded-full border-2 border-white shadow-[0_2px_8px_rgba(0,0,0,0.3)]" style="width: 32px; height: 32px;">
                                        <span class="material-symbols-outlined" style="font-size: 18px; font-weight: 600; font-variation-settings: 'FILL' 1;">home</span>
                                    </div>
                                `,
                                iconSize: [32, 32],
                                iconAnchor: [16, 16],
                                popupAnchor: [0, -16]
                            })
                        }).addTo(globalLeafletMapInstance).bindPopup(`<b>Rumah Karyawan (${employeeName})</b>`);
                        
                        L.circle([homeLat, homeLng], {
                            color: '#6366f1',
                            fillColor: '#6366f1',
                            fillOpacity: 0.1,
                            radius: homeRadius
                        }).addTo(globalLeafletMapInstance);
                        markers.push(homeMarker);
                    } else {
                        document.getElementById('leafletMapLegendHome').classList.add('hidden');
                    }

                    // Clock-in / Access marker
                    if (inLat && inLng) {
                        const popupText = isAccessTracker 
                            ? `<b>Lokasi Akses</b><br>Waktu: ${mapConfig.clock_in || '--:--'}`
                            : `<b>Clock-In (Masuk)</b><br>Waktu: ${mapConfig.clock_in || '--:--'}<br>Mode: ${mapConfig.work_mode || 'WFO'}`;

                        const inMarker = L.marker([inLat, inLng], {
                            icon: L.divIcon({
                                className: '',
                                html: `<div style="background-color: #10b981; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 8px rgba(0,0,0,0.5);"></div>`,
                                iconSize: [16, 16],
                                iconAnchor: [8, 8]
                            })
                        }).addTo(globalLeafletMapInstance).bindPopup(popupText);
                        markers.push(inMarker);
                    }

                    // Clock-out marker
                    if (outLat && outLng) {
                        const outMarker = L.marker([outLat, outLng], {
                            icon: L.divIcon({
                                className: '',
                                html: `<div style="background-color: #f43f5e; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 8px rgba(0,0,0,0.5);"></div>`,
                                iconSize: [16, 16],
                                iconAnchor: [8, 8]
                            })
                        }).addTo(globalLeafletMapInstance).bindPopup(`<b>Clock-Out (Pulang)</b><br>Waktu: ${mapConfig.clock_out || '--:--'}<br>Mode: ${mapConfig.work_mode_out || '—'}`);
                        markers.push(outMarker);
                    }

                    // Fit bounds to show all markers
                    if (markers.length > 0) {
                        const group = new L.featureGroup(markers);
                        globalLeafletMapInstance.fitBounds(group.getBounds().pad(0.15));
                    }
                    globalLeafletMapInstance._markers = markers;
                }
            }, 250);
        };

        window.closeGlobalLeafletMap = function() {
            const modal = document.getElementById('globalLeafletMapModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            
            if (globalLeafletMapInstance) {
                globalLeafletMapInstance.remove();
                globalLeafletMapInstance = null;
            }
            if (globalGoogleMapInstance) {
                globalGoogleMapInstance = null;
                globalGoogleMarkers = [];
            }
            globalLeafletTileLayer = null;
        };
    </script>
    
    <!-- PWA Service Worker Registration & Offline Handler -->
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

        // Global network status listeners
        window.addEventListener('offline', function() {
            Swal.fire({
                title: 'Koneksi Terputus',
                text: 'Koneksi internet Anda terputus. Aplikasi siCare akan menggunakan data cache lokal sementara.',
                icon: 'warning',
                confirmButtonColor: '#000666',
                confirmButtonText: 'Mengerti',
                allowOutsideClick: false,
                backdrop: 'rgba(0, 6, 102, 0.25)'
            });
        });

        window.addEventListener('online', function() {
            Swal.fire({
                title: 'Koneksi Terhubung',
                text: 'Koneksi internet telah aktif kembali. Mensinkronkan data dengan server...',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                confirmButtonColor: '#000666'
            });
        });

        // Global Fetch Interceptor to handle Backend/Server/Database errors
        const originalFetch = window.fetch;
        window.fetch = async function(...args) {
            let [resource, config] = args;
            if (window.jwtToken) {
                config = config || {};
                config.headers = config.headers || {};
                if (config.headers instanceof Headers) {
                    if (!config.headers.has('X-JWT-Token')) {
                        config.headers.set('X-JWT-Token', window.jwtToken);
                    }
                } else {
                    if (!config.headers['X-JWT-Token']) {
                        config.headers['X-JWT-Token'] = window.jwtToken;
                    }
                }
                args = [resource, config];
            }
            try {
                const response = await originalFetch(...args);
                if (!response.ok && response.status >= 500) {
                    Swal.fire({
                        title: 'Gangguan Layanan Server',
                        text: 'Terjadi kegagalan komunikasi pada server (Error ' + response.status + ') atau database server sedang sibuk. Silakan coba beberapa saat lagi.',
                        icon: 'error',
                        confirmButtonColor: '#000666',
                        confirmButtonText: 'Tutup'
                    });
                }
                return response;
            } catch (error) {
                // Only show backend connection error if the client browser itself is online
                if (navigator.onLine) {
                    Swal.fire({
                        title: 'Koneksi Server Gagal',
                        text: 'Tidak dapat terhubung ke server utama siCare. Layanan backend atau database mungkin sedang dinonaktifkan atau dalam masa pemeliharaan.',
                        icon: 'error',
                        confirmButtonColor: '#000666',
                        confirmButtonText: 'Tutup'
                    });
                }
                throw error;
            }
        };
    </script>

    <!-- Idle Timeout Modal Overlay -->
    <div id="idleTimeoutModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-md hidden transition-all duration-300">
        <style>
            @keyframes idleScaleUp {
                from { transform: scale(0.95); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            .animate-idle-scale-up {
                animation: idleScaleUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
        </style>
        <div class="bg-white border border-neutral-100 rounded-[28px] shadow-[0_24px_50px_-12px_rgba(0,0,0,0.25)] p-8 flex flex-col items-center justify-center text-center max-w-[400px] w-full mx-auto relative overflow-hidden backdrop-blur-xl animate-idle-scale-up">
            
            <!-- Glowing Icon Section -->
            <div class="relative mb-6">
                <!-- Outer pulsating glow ring -->
                <div class="absolute inset-0 rounded-full bg-amber-400/20 blur-xl animate-pulse"></div>
                <!-- Inner modern container -->
                <div class="relative w-20 h-20 rounded-3xl bg-gradient-to-tr from-amber-500 to-yellow-400 flex items-center justify-center text-white shadow-lg shadow-amber-500/30 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                    <span class="material-symbols-outlined text-4xl font-light select-none">hourglass_empty</span>
                </div>
            </div>
            
            <h3 class="text-2xl font-bold text-neutral-900 mb-3 tracking-tight font-headline">Deteksi Sesi Hening</h3>
            
            <p class="text-sm text-neutral-500 mb-6 leading-relaxed max-w-[320px]">
                Halo <strong class="text-neutral-800 font-semibold"><?= htmlspecialchars($_SESSION['name'] ?? 'Karyawan') ?></strong>, Anda terdeteksi tidak ada aktivitas pada aplikasi selama <span id="idleTimeDisplay" class="font-bold text-primary"></span>.
            </p>
            
            <div class="w-full mb-6">
                <p class="text-[14px] font-medium text-neutral-700 mb-4 leading-normal">
                    Apakah Anda ingin melanjutkan sesi ini?
                </p>
                <div class="flex gap-3 w-full">
                    <button id="idleNoBtn" class="flex-grow bg-white border border-neutral-200 hover:bg-neutral-50 text-neutral-700 font-semibold py-3 px-4 rounded-xl transition-all duration-200 active:scale-95 flex items-center justify-center gap-2 cursor-pointer text-xs md:text-sm whitespace-nowrap">
                        <span class="material-symbols-outlined text-base">logout</span> Keluar
                    </button>
                    <button id="idleYesBtn" class="flex-grow bg-gradient-to-r from-primary to-[#1c2480] hover:shadow-lg hover:shadow-primary/20 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 active:scale-95 flex items-center justify-center gap-2 cursor-pointer text-xs md:text-sm whitespace-nowrap">
                        <span class="material-symbols-outlined text-base">check_circle</span> Lanjutkan Sesi
                    </button>
                </div>
            </div>

            <!-- Sleek Countdown & Progress Bar -->
            <div class="w-full" id="idleCountdownContainer">
                <div class="w-full bg-neutral-100 rounded-full h-1.5 mb-2 overflow-hidden">
                    <div id="idleProgressBar" class="bg-gradient-to-r from-red-500 to-amber-500 h-full w-full rounded-full transition-all duration-1000 ease-linear"></div>
                </div>
                <div class="flex justify-between items-center w-full px-1 text-[11px] font-medium text-neutral-400">
                    <span>Sesi otomatis ditutup</span>
                    <span class="text-red-500 font-semibold flex items-center gap-1">
                        <span class="material-symbols-outlined text-[13px] font-bold">alarm</span>
                        <span id="idleCountdownDisplay" class="font-mono">30</span> detik
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Idle Timeout Detection Logic -->
    <script>
        (function() {
            let idleTimeoutSec = <?= (int)$appIdleTimeoutSec ?>;
            if (idleTimeoutSec <= 0) return; // Feature disabled

            let idleTimer = null;
            let countdownInterval = null;
            let maxCountdownSeconds = <?= (int)$appIdleCountdownSec ?>;
            let countdownSeconds = maxCountdownSeconds; // Seconds to auto-logout after warning
            const modal = document.getElementById('idleTimeoutModal');
            const idleTimeDisplay = document.getElementById('idleTimeDisplay');
            const countdownDisplay = document.getElementById('idleCountdownDisplay');
            const countdownContainer = document.getElementById('idleCountdownContainer');
            const progressBar = document.getElementById('idleProgressBar');
            const yesBtn = document.getElementById('idleYesBtn');
            const noBtn = document.getElementById('idleNoBtn');

            // Format seconds into dynamic Indonesian text (e.g., 150 seconds -> "2 menit 30 detik")
            function formatIdleTime(totalSeconds) {
                if (totalSeconds < 60) {
                    return totalSeconds + " detik";
                }
                const minutes = Math.floor(totalSeconds / 60);
                const remainingSeconds = totalSeconds % 60;
                if (remainingSeconds === 0) {
                    return minutes + " menit";
                }
                return minutes + " menit " + remainingSeconds + " detik";
            }

            // Function to reset the main activity timeout timer
            function resetIdleTimer() {
                // If warning is already showing, do not reset it on random background events
                if (modal && !modal.classList.contains('hidden')) return;

                clearTimeout(idleTimer);
                idleTimer = setTimeout(showIdleWarning, idleTimeoutSec * 1000);
            }

            // Show warning modal and begin countdown
            function showIdleWarning() {
                if (idleTimeDisplay) {
                    idleTimeDisplay.innerText = formatIdleTime(idleTimeoutSec);
                }
                if (modal) {
                    modal.classList.remove('hidden');
                }
                
                // If no countdown is configured, hide the countdown UI and don't trigger auto-logout
                if (maxCountdownSeconds <= 0) {
                    if (countdownContainer) {
                        countdownContainer.classList.add('hidden');
                    }
                    return;
                }

                if (countdownContainer) {
                    countdownContainer.classList.remove('hidden');
                }

                countdownSeconds = maxCountdownSeconds;
                if (countdownDisplay) {
                    countdownDisplay.innerText = countdownSeconds;
                }
                if (progressBar) {
                    progressBar.style.transition = 'none';
                    progressBar.style.width = '100%';
                    // Force reflow
                    progressBar.offsetHeight;
                    progressBar.style.transition = 'width 1s linear';
                }

                clearInterval(countdownInterval);
                countdownInterval = setInterval(() => {
                    countdownSeconds--;
                    if (countdownDisplay) {
                        countdownDisplay.innerText = countdownSeconds;
                    }
                    if (progressBar) {
                        const pct = (countdownSeconds / maxCountdownSeconds) * 100;
                        progressBar.style.width = pct + '%';
                    }
                    if (countdownSeconds <= 0) {
                        clearInterval(countdownInterval);
                        logoutUser();
                    }
                }, 1000);
            }

            // Logout user by redirecting to logout page
            function logoutUser() {
                window.location.href = '/auth/logout';
            }

            // Hide warning modal, clear countdown, and restart idle timer
            function resumeSession() {
                if (modal) {
                    modal.classList.add('hidden');
                }
                clearInterval(countdownInterval);
                resetIdleTimer();
            }

            // Bind activity event listeners to reset timer
            const activityEvents = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];
            activityEvents.forEach(evt => {
                window.addEventListener(evt, resetIdleTimer, true);
            });

            // Button actions
            if (yesBtn) {
                yesBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    resumeSession();
                });
            }
            if (noBtn) {
                noBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    logoutUser();
                });
            }

            // Expose a public function to update idle settings in real-time
            window.updateIdleSettings = function(timeoutSec, countdownSec) {
                idleTimeoutSec = parseInt(timeoutSec, 10) || 0;
                maxCountdownSeconds = parseInt(countdownSec, 10) || 0;
                
                if (idleTimeoutSec <= 0) {
                    clearTimeout(idleTimer);
                    clearInterval(countdownInterval);
                    if (modal) modal.classList.add('hidden');
                    activityEvents.forEach(evt => {
                        window.removeEventListener(evt, resetIdleTimer, true);
                    });
                    return;
                }

                // Bind activity listeners if they were not bound or reset
                activityEvents.forEach(evt => {
                    window.addEventListener(evt, resetIdleTimer, true);
                });

                resumeSession();
            };

            // Initialize on load
            resetIdleTimer();
        })();

        // Real-time app branding update
        function escapeHtml(text) {
            if (!text) return '';
            return text.toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        window.updateAppAppearance = function(name, logoType, logoIcon, logoImage) {
            const appNameElements = document.querySelectorAll('.brand-text');
            appNameElements.forEach(el => {
                el.textContent = name;
            });

            const originalTitle = document.title;
            if (originalTitle.includes(' | ')) {
                const parts = originalTitle.split(' | ');
                document.title = name + ' | ' + parts[1];
            } else {
                document.title = name;
            }

            const logoLinks = document.querySelectorAll('.brand-logo-link');
            logoLinks.forEach(link => {
                if (logoType === 'image' && logoImage) {
                    link.innerHTML = `<img src="${escapeHtml(logoImage)}" class="brand-logo-icon h-9 w-auto object-contain flex-shrink-0" alt="Logo" /><span class="brand-text bg-gradient-to-r from-primary to-blue-800 bg-clip-text text-transparent whitespace-nowrap">${escapeHtml(name)}</span>`;
                } else {
                    link.innerHTML = `<span class="brand-logo-icon material-symbols-outlined text-primary text-3xl font-bold flex-shrink-0">${escapeHtml(logoIcon)}</span><span class="brand-text bg-gradient-to-r from-primary to-blue-800 bg-clip-text text-transparent whitespace-nowrap">${escapeHtml(name)}</span>`;
                }
        // Universal Full-Screen Lightbox Image Viewer
        window.openFullScreenLightbox = function(imageUrl) {
            let overlay = document.getElementById('globalLightboxOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'globalLightboxOverlay';
                overlay.className = 'fixed inset-0 z-[99999] bg-black/90 backdrop-blur-md flex items-center justify-center p-4 cursor-zoom-out select-none';
                overlay.onclick = function() {
                    overlay.classList.add('hidden');
                };
                overlay.innerHTML = `
                    <button type="button" onclick="document.getElementById('globalLightboxOverlay').classList.add('hidden')" class="absolute top-5 right-5 w-12 h-12 aspect-square flex-shrink-0 bg-white/15 hover:bg-white/30 text-white rounded-full transition-all hover:scale-110 active:scale-95 shadow-lg flex items-center justify-center z-50 cursor-pointer" title="Tutup (Esc)">
                        <span class="material-symbols-outlined text-2xl font-bold">close</span>
                    </button>
                    <div class="relative max-w-full max-h-full flex items-center justify-center" onclick="event.stopPropagation()">
                        <img id="globalLightboxImage" src="" alt="Full Screen Preview" class="max-h-[92vh] max-w-[92vw] object-contain shadow-2xl rounded-xl border border-white/10" />
                    </div>
                `;
                document.body.appendChild(overlay);

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !overlay.classList.contains('hidden')) {
                        overlay.classList.add('hidden');
                    }
                });
            }
            document.getElementById('globalLightboxImage').src = imageUrl;
            overlay.classList.remove('hidden');
        };
    </script>
</body>
</html>
