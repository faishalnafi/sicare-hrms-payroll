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
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            
            if ($remember) {
                $_SESSION['remember_me'] = true;
                
                // Explicitly send a persistent cookie (30 days) to override the default session-only cookie
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    session_id(),
                    time() + 30 * 86400,
                    $params['path'],
                    $params['domain'],
                    $params['secure'] ?? false,
                    $params['httponly'] ?? true
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Berhasil masuk!', 'redirect' => '/dashboard']);
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

    public function googleRedirect() {
        $clientId = $_ENV['GOOGLE_CLIENT_ID'];
        $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];
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
            'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
            'grant_type' => 'authorization_code',
            'code' => $code
        ]));
        $response = curl_exec($ch);
        curl_close($ch);
        
        $tokenData = json_decode($response, true);
        if (isset($tokenData['access_token'])) {
            $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
            $profileResponse = curl_exec($ch);
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
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['profile_picture'] = $user['profile_picture'];
                    
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: /');
        exit;
    }
}
