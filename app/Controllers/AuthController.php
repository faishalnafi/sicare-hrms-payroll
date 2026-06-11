<?php

namespace App\Controllers;

use App\Models\User;

class AuthController {
    
    public function login() {
        header('Content-Type: application/json');
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember-me']) && ($_POST['remember-me'] === 'on' || $_POST['remember-me'] == '1' || $_POST['remember-me'] == 'true');
        
        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email dan kata sandi wajib diisi.']);
            return;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['login_just_occurred'] = true;
            
            // Set dynamic database flag for login tracking
            try {
                $db = \App\Config\Database::getInstance()->getConnection();
                $db->prepare("UPDATE users SET login_just_occurred = 1 WHERE id = ?")->execute([$user['id']]);
            } catch (\Exception $e) {
                // Fail-safe
            }
            
            // Set secure and samesite cookie parameters correctly
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            if (PHP_VERSION_ID >= 70300) {
                setcookie('login_just_occurred', '1', [
                    'expires' => time() + 300,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $isSecure,
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
            } else {
                setcookie('login_just_occurred', '1', time() + 300, '/; SameSite=Lax', '', $isSecure, false);
            }
            
            if ($remember) {
                $_SESSION['remember_me'] = true;
                $_SESSION['login_time'] = time(); // Store initial login time
                
                // Explicitly send a persistent cookie (7 days) to override the default session-only cookie
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    session_id(),
                    time() + 7 * 86400,
                    $params['path'],
                    $params['domain'] ?? '',
                    $params['secure'] ?? false,
                    $params['httponly'] ?? true
                );
            }
            
            session_write_close();
            
            $redirect = $_POST['redirect'] ?? '';
            if (empty($redirect) || !str_starts_with($redirect, '/')) {
                $redirect = '/dashboard';
            }
            
            echo json_encode(['success' => true, 'message' => 'Berhasil masuk!', 'redirect' => $redirect]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email atau kata sandi salah.']);
        }
    }

    public function register() {
        header('Content-Type: application/json');
        
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($firstName) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi.']);
            return;
        }

        $userModel = new User();
        
        // Check if email exists
        if ($userModel->findByEmail($email)) {
            echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar.']);
            return;
        }

        $id = $userModel->create($firstName, $lastName, $email, $password);
        
        if ($id) {
            echo json_encode(['success' => true, 'message' => 'Pendaftaran berhasil! Silakan masuk.', 'redirect' => '/signin']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem.']);
        }
    }

    private function getGoogleRedirectUri() {
        $envUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $protocol = 'https';
        }
        
        // Handle X-Forwarded-Host if behind a proxy
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        // Strip ports or multiple hosts if present in header
        if (strpos($host, ',') !== false) {
            $parts = explode(',', $host);
            $host = trim(end($parts));
        }
        
        if (!empty($envUri)) {
            $parsedEnv = parse_url($envUri);
            $envHost = $parsedEnv['host'] ?? '';
            
            // Check if current request host is different from the env host
            // and build redirect URI using current host's protocol and host, but retaining path
            $reqHostOnly = parse_url($protocol . '://' . $host, PHP_URL_HOST);
            
            if ($envHost !== $reqHostOnly && !empty($reqHostOnly)) {
                $path = $parsedEnv['path'] ?? '/auth/google/callback';
                $query = isset($parsedEnv['query']) ? '?' . $parsedEnv['query'] : '';
                return "{$protocol}://{$host}{$path}{$query}";
            }
            
            // Behind SSL terminating load balancers, automatically adjust http:// to https://
            if ($protocol === 'https' && str_starts_with($envUri, 'http://')) {
                $envHostUrl = parse_url($envUri, PHP_URL_HOST);
                $reqHostUrl = parse_url($protocol . '://' . $host, PHP_URL_HOST);
                if ($envHostUrl === $reqHostUrl) {
                    return str_replace('http://', 'https://', $envUri);
                }
            }
            return $envUri;
        }

        return "{$protocol}://{$host}/auth/google/callback";
    }

    public function googleRedirect() {
        $clientId = $_ENV['GOOGLE_CLIENT_ID'];
        $redirectUri = urlencode($this->getGoogleRedirectUri());
        $scope = urlencode('email profile');
        
        $url = "https://accounts.google.com/o/oauth2/v2/auth?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code&scope={$scope}&access_type=online";
        header("Location: $url");
        exit;
    }

    public function googleCallback() {
        $code = $_GET['code'] ?? null;
        if (!$code) {
            header('Location: /signin?error=auth_failed');
            exit;
        }

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
            'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
            'redirect_uri' => $this->getGoogleRedirectUri(),
            'grant_type' => 'authorization_code',
            'code' => $code
        ]));
        $response = curl_exec($ch);
        
        // Fail-safe: if cURL fails due to SSL peer verification issues on custom VPS systems
        if ($response === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
        }
        curl_close($ch);
        
        $tokenData = json_decode($response, true);
        if (isset($tokenData['access_token'])) {
            $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
            $profileResponse = curl_exec($ch);
            if ($profileResponse === false) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $profileResponse = curl_exec($ch);
            }
            curl_close($ch);
            
            $profile = json_decode($profileResponse, true);
            
            if (isset($profile['email'])) {
                $userModel = new User();
                $user = $userModel->findByEmail($profile['email']);
                $googlePic = $profile['picture'] ?? null;
                
                if (!$user) {
                    $infoMsg = "Akun dengan email " . $profile['email'] . " tidak ditemukan. Silakan mendaftar terlebih dahulu untuk menggunakan layanan siCare.";
                    header('Location: /signup?info=' . urlencode($infoMsg));
                    exit;
                } else if ($googlePic && empty($user['profile_picture'])) {
                    $userModel->updateProfilePicture($user['id'], $googlePic);
                    $user['profile_picture'] = $googlePic;
                }

                if ($user) {
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['profile_picture'] = $user['profile_picture'];
                    $_SESSION['login_just_occurred'] = true;
                    
                    // Set dynamic database flag for login tracking
                    try {
                        $db = \App\Config\Database::getInstance()->getConnection();
                        $db->prepare("UPDATE users SET login_just_occurred = 1 WHERE id = ?")->execute([$user['id']]);
                    } catch (\Exception $e) {
                        // Fail-safe
                    }
                    
                    // Set secure and samesite cookie parameters correctly
                    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
                    if (PHP_VERSION_ID >= 70300) {
                        setcookie('login_just_occurred', '1', [
                            'expires' => time() + 300,
                            'path' => '/',
                            'domain' => '',
                            'secure' => $isSecure,
                            'httponly' => false,
                            'samesite' => 'Lax'
                        ]);
                    } else {
                        setcookie('login_just_occurred', '1', time() + 300, '/; SameSite=Lax', '', $isSecure, false);
                    }
                    
                    if (isset($_COOKIE['remember_google']) && $_COOKIE['remember_google'] === '1') {
                        $_SESSION['remember_me'] = true;
                        $_SESSION['login_time'] = time();
                        
                        // Clear the cookie immediately
                        setcookie('remember_google', '', time() - 3600, '/');
                        
                        // Explicitly send a persistent cookie (7 days)
                        $params = session_get_cookie_params();
                        setcookie(
                            session_name(),
                            session_id(),
                            time() + 7 * 86400,
                            $params['path'],
                            $params['domain'] ?? '',
                            $params['secure'] ?? false,
                            $params['httponly'] ?? true
                        );
                    }
                    
                    session_write_close();
                    header('Location: /dashboard');
                    exit;
                }
            }
        }
        
        header('Location: /signin?error=google_auth_failed');
        exit;
    }

    public function saveProfile() {
        header('Content-Type: application/json');
        session_start();

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $email = $_POST['email'] ?? '';
        $phone = $_POST['no_telepon'] ?? '';
        $domisili = $_POST['alamat_domisili'] ?? '';
        $lat = isset($_POST['home_latitude']) && $_POST['home_latitude'] !== '' ? (float)$_POST['home_latitude'] : null;
        $lng = isset($_POST['home_longitude']) && $_POST['home_longitude'] !== '' ? (float)$_POST['home_longitude'] : null;

        if (empty($email) || empty($phone) || empty($domisili)) {
            echo json_encode(['success' => false, 'message' => 'Email, nomor telepon, dan alamat domisili wajib diisi.']);
            return;
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            
            // Check if email already taken by another user
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
            $stmt->execute(['email' => $email, 'id' => $userId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar oleh pengguna lain.']);
                return;
            }

            $password = $_POST['password'] ?? '';

            $query = "UPDATE users SET email = :email, no_telepon = :phone, alamat_domisili = :domisili, home_latitude = :lat, home_longitude = :lng, updated_at = NOW()";
            $params = [
                'email' => $email,
                'phone' => $phone,
                'domisili' => $domisili,
                'lat' => $lat,
                'lng' => $lng,
                'id' => $userId
            ];

            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $query .= ", password_hash = :password_hash";
                $params['password_hash'] = $password_hash;
            }

            $query .= " WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->execute($params);

            // Update session
            $_SESSION['email'] = $email;

            echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Kesalahan server: ' . $e->getMessage()]);
        }
    }

    public function logout() {
        session_start();
        session_destroy();
        header('Location: /');
        exit;
    }

    public function impersonate() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Impersonation is only allowed for Super Admins
        $isSuperAdmin = ($_SESSION['role'] ?? '') === 'superadmin' || ($_SESSION['original_role'] ?? '') === 'superadmin';
        if (!$isSuperAdmin) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Super Admin yang diizinkan melakukan simulasi login.']);
            return;
        }

        $targetUserId = $_POST['user_id'] ?? '';
        if (empty($targetUserId)) {
            echo json_encode(['success' => false, 'message' => 'ID pengguna wajib diisi.']);
            return;
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $targetUserId]);
            $targetUser = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$targetUser) {
                echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
                return;
            }

            // Save original session data if we aren't already impersonating
            if (!isset($_SESSION['original_user_id'])) {
                $_SESSION['original_user_id'] = $_SESSION['user_id'];
                $_SESSION['original_role']    = $_SESSION['role'];
                $_SESSION['original_name']    = $_SESSION['name'];
                $_SESSION['original_email']   = $_SESSION['email'];
                $_SESSION['original_profile_picture'] = $_SESSION['profile_picture'] ?? null;
            }

            // Set session to target user
            $_SESSION['user_id'] = $targetUser['id'];
            $_SESSION['role'] = $targetUser['role'];
            $_SESSION['name'] = $targetUser['first_name'] . ' ' . $targetUser['last_name'];
            $_SESSION['email'] = $targetUser['email'];
            
            $pp = $targetUser['profile_picture'];
            if (empty($pp)) {
                $hash = md5(strtolower(trim($targetUser['email'])));
                $pp = "https://www.gravatar.com/avatar/{$hash}?d=404&s=200";
            }
            $_SESSION['profile_picture'] = $pp;

            echo json_encode(['success' => true, 'redirect' => '/dashboard']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function stopImpersonating() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['original_user_id'])) {
            // Restore original session data
            $_SESSION['user_id'] = $_SESSION['original_user_id'];
            $_SESSION['role']    = $_SESSION['original_role'];
            $_SESSION['name']    = $_SESSION['original_name'];
            $_SESSION['email']   = $_SESSION['original_email'];
            $_SESSION['profile_picture'] = $_SESSION['original_profile_picture'];

            // Clean up original session markers
            unset($_SESSION['original_user_id']);
            unset($_SESSION['original_role']);
            unset($_SESSION['original_name']);
            unset($_SESSION['original_email']);
            unset($_SESSION['original_profile_picture']);

            header('Location: /superadmin/users');
            exit;
        }

        header('Location: /dashboard');
        exit;
    }

    public function recordLoginLocation() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan masuk terlebih dahulu.']);
            return;
        }

        // Parse JSON input or POST parameters
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $lat = (isset($input['lat']) && $input['lat'] !== '') ? (float)$input['lat'] : null;
        $lng = (isset($input['lng']) && $input['lng'] !== '') ? (float)$input['lng'] : null;

        $userId = $_SESSION['user_id'];
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Generate UUID
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Determine status
        $status = 'Akses Aplikasi';
        $db = \App\Config\Database::getInstance()->getConnection();
        
        // Check database flag as the primary source of truth for login event
        $loginJustOccurredDb = false;
        try {
            $stmtCheck = $db->prepare("SELECT login_just_occurred FROM users WHERE id = :id LIMIT 1");
            $stmtCheck->execute(['id' => $userId]);
            $loginJustOccurredDb = ((int)$stmtCheck->fetchColumn() === 1);
        } catch (\Exception $e) {
            // Fail-safe
        }

        if ($loginJustOccurredDb || (isset($_COOKIE['login_just_occurred']) && $_COOKIE['login_just_occurred'] === '1') || isset($_SESSION['login_just_occurred'])) {
            $status = 'Login';
            
            // Clear all flags (db, session, cookie)
            try {
                $db->prepare("UPDATE users SET login_just_occurred = 0 WHERE id = ?")->execute([$userId]);
            } catch (\Exception $e) {
                // Fail-safe
            }
            
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            if (PHP_VERSION_ID >= 70300) {
                setcookie('login_just_occurred', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $isSecure,
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
            } else {
                setcookie('login_just_occurred', '', time() - 3600, '/; SameSite=Lax', '', $isSecure, false);
            }
            unset($_SESSION['login_just_occurred']);
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO login_logs (id, user_id, latitude, longitude, ip_address, user_agent, status, login_at)
                VALUES (:id, :user_id, :latitude, :longitude, :ip_address, :user_agent, :status, NOW())
            ");
            $stmt->execute([
                'id' => $uuid,
                'user_id' => $userId,
                'latitude' => $lat,
                'longitude' => $lng,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'status' => $status
            ]);

            $_SESSION['login_location_recorded'] = true;
            echo json_encode(['success' => true, 'message' => 'Aktivitas akses berhasil direkam.']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal merekam aktivitas akses: ' . $e->getMessage()]);
        }
    }
}
