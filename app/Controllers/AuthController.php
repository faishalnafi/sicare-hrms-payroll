<?php

namespace App\Controllers;

use App\Models\User;

/**
 * Class AuthController
 * 
 * Handles all authentication-related requests in the application.
 * Part of the MVC Controller layer, interacting with the User Model
 * and returning redirects or JSON responses to MVC Views.
 * 
 * @package App\Controllers
 */
class AuthController {
    
    /**
     * Authenticates a user using email and password.
     * 
     * - OOP: Instantiates the App\Models\User class to query database.
     * - MVC: Acting as Controller, processes HTTP POST inputs ("email", "password", "remember-me")
     *   and returns JSON output to sign-in views.
     * - Connections: Connects to App\Models\User (Model), App\Config\Database (Database Access),
     *   sets up session variables for global state, and sets the "login_just_occurred" cookie.
     * 
     * @return void
     */
    public function login() {
        header("Content-Type: application/json");
        
        $email = $_POST["email"] ?? "";
        $password = $_POST["password"] ?? "";
        $remember = isset($_POST["remember-me"]) && ($_POST["remember-me"] === "on" || $_POST["remember-me"] == "1" || $_POST["remember-me"] == "true");
        
        if (empty($email) || empty($password)) {
            echo json_encode(["success" => false, "message" => "Email dan kata sandi wajib diisi."]);
            return;
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $ipAddress = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";

            // 1. Check if user exists first. If not, bypass throttle and return early.
            $userModel = new User();
            $user = $userModel->findByEmail($email);

            if (!$user) {
                echo json_encode([
                    "success" => false,
                    "message" => "Email tidak terdaftar. Silakan mendaftar terlebih dahulu atau periksa kembali email yang Anda masukkan."
                ]);
                return;
            }

            // Check if user is soft deleted
            if (isset($user["is_deleted"]) && (int)$user["is_deleted"] === 1) {
                echo json_encode([
                    "success" => false,
                    "message" => "Akun Anda telah dihapus. Silakan hubungi administrator."
                ]);
                return;
            }

            // Check if user is suspended
            if (isset($user["is_suspended"]) && (int)$user["is_suspended"] === 1) {
                echo json_encode([
                    "success" => false,
                    "message" => "Akun Anda ditangguhkan (suspend). Silakan hubungi admin."
                ]);
                return;
            }

            // 2. Check current login attempts and locks
            $stmtAttempt = $db->prepare("SELECT attempts, locked_until FROM login_attempts WHERE email = ? AND ip_address = ?");
            $stmtAttempt->execute([$email, $ipAddress]);
            $record = $stmtAttempt->fetch(\PDO::FETCH_ASSOC);

            if ($record) {
                $attempts = (int)$record["attempts"];
                if ($attempts >= 21) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Akun diblokir, silakan hubungi admin.",
                        "attempts" => $attempts,
                        "is_blocked" => true
                    ]);
                    return;
                }

                if ($record["locked_until"] !== null) {
                    $lockedTime = strtotime($record["locked_until"]);
                    if ($lockedTime > time()) {
                        $remaining = $lockedTime - time();
                        echo json_encode([
                            "success" => false,
                            "message" => "Terlalu banyak percobaan. Harap tunggu {$remaining} detik sebelum mencoba lagi.",
                            "attempts" => $attempts,
                            "lockout_duration" => $remaining
                        ]);
                        return;
                    }
                }
            }

            if (password_verify($password, $user["password_hash"])) {
                // Success: clear login attempts
                $db->prepare("DELETE FROM login_attempts WHERE email = ? AND ip_address = ?")->execute([$email, $ipAddress]);

                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["role_id"] = $user["role_id"];
                $_SESSION["department_id"] = $user["department_id"];
                $_SESSION["name"] = $user["first_name"] . " " . $user["last_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["profile_picture"] = $user["profile_picture"];
                $_SESSION["login_just_occurred"] = true;

                // Generate JWT Token for CRUD authorization
                $jwtPayload = [
                    "user_id" => $user["id"],
                    "name" => $user["first_name"] . " " . $user["last_name"],
                    "email" => $user["email"],
                    "role" => $user["role"]
                ];
                $jwtToken = \App\Config\JwtHelper::createToken($jwtPayload);
                $_SESSION["jwt_token"] = $jwtToken;
                
                // Set dynamic database flag for login tracking
                try {
                    $db->prepare("UPDATE users SET login_just_occurred = 1 WHERE id = ?")->execute([$user["id"]]);
                } catch (\Exception $e) {
                    // Fail-safe
                }
                
                // Set secure and samesite cookie parameters correctly
                $isSecure = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https");
                
                // Set JWT token cookie
                if (PHP_VERSION_ID >= 70300) {
                    setcookie("jwt_token", $jwtToken, [
                        "expires" => time() + 604800,
                        "path" => "/",
                        "domain" => "",
                        "secure" => $isSecure,
                        "httponly" => false,
                        "samesite" => "Lax"
                    ]);
                    setcookie("login_just_occurred", "1", [
                        "expires" => time() + 300,
                        "path" => "/",
                        "domain" => "",
                        "secure" => $isSecure,
                        "httponly" => false,
                        "samesite" => "Lax"
                    ]);
                } else {
                    setcookie("jwt_token", $jwtToken, time() + 604800, "/; SameSite=Lax", "", $isSecure, false);
                    setcookie("login_just_occurred", "1", time() + 300, "/; SameSite=Lax", "", $isSecure, false);
                }
                
                if ($remember) {
                    $_SESSION["remember_me"] = true;
                    $_SESSION["login_time"] = time();
                    
                    // Generate remember token
                    $rememberToken = bin2hex(random_bytes(32));
                    
                    // Save to database
                    $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$rememberToken, $user["id"]]);
                    
                    // Set cookie remember_me_token (30 days)
                    $isSecure = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https");
                    if (PHP_VERSION_ID >= 70300) {
                        setcookie("remember_me_token", $rememberToken, [
                            "expires" => time() + 30 * 86400,
                            "path" => "/",
                            "domain" => "",
                            "secure" => $isSecure,
                            "httponly" => true,
                            "samesite" => "Lax"
                        ]);
                    } else {
                        setcookie("remember_me_token", $rememberToken, time() + 30 * 86400, "/; SameSite=Lax", "", $isSecure, true);
                    }
                }
                
                session_write_close();
                
                $redirect = $_POST["redirect"] ?? "";
                if (empty($redirect) || !str_starts_with($redirect, "/")) {
                    $redirect = "/dashboard";
                }
                
                echo json_encode(["success" => true, "message" => "Berhasil masuk!", "redirect" => $redirect]);
            } else {
                // Failure: increment login attempts
                $newAttempts = $record ? (int)$record["attempts"] + 1 : 1;
                $duration = $this->getLockoutDuration($newAttempts);
                
                if ($duration == -1) {
                    $lockedUntil = null;
                } elseif ($duration > 0) {
                    $lockedUntil = date("Y-m-d H:i:s", time() + $duration);
                } else {
                    $lockedUntil = null;
                }
                
                if ($record) {
                    $upStmt = $db->prepare("UPDATE login_attempts SET attempts = ?, locked_until = ? WHERE email = ? AND ip_address = ?");
                    $upStmt->execute([$newAttempts, $lockedUntil, $email, $ipAddress]);
                } else {
                    $insStmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, attempts, locked_until) VALUES (?, ?, ?, ?)");
                    $insStmt->execute([$email, $ipAddress, $newAttempts, $lockedUntil]);
                }
                
                if ($newAttempts >= 21) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Akun diblokir, silakan hubungi admin.",
                        "attempts" => $newAttempts,
                        "is_blocked" => true
                    ]);
                } elseif ($duration > 0) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Terlalu banyak percobaan. Harap tunggu {$duration} detik sebelum mencoba lagi.",
                        "attempts" => $newAttempts,
                        "lockout_duration" => $duration
                    ]);
                } else {
                    $errorDetail = "Kata sandi salah.";
                    $remaining = 9 - $newAttempts;
                    echo json_encode([
                        "success" => false,
                        "message" => "{$errorDetail} Percobaan ke-{$newAttempts}. Tersisa {$remaining} kali percobaan sebelum dikunci.",
                        "attempts" => $newAttempts,
                        "remaining_attempts" => $remaining
                    ]);
                }
            }
        } catch (\Exception $e) {
            echo json_encode(["success" => false, "message" => "Koneksi database bermasalah: " . $e->getMessage()]);
        }
    }

    /**
     * Registers a new user account.
     * 
     * - OOP: Utilizes the App\Models\User class to create records in the database.
     * - MVC: Acts as Controller to handle POST registration data, validate password strength
     *   via helpers, and output a JSON response.
     * - Connections: Interfaces with App\Models\User for user existence checks and insertion.
     *   Password strength helper "validate_password_strength()" is defined in helper files.
     * 
     * @return void
     */
    public function register() {
        header("Content-Type: application/json");
        
        $firstName = $_POST["first_name"] ?? "";
        $lastName = $_POST["last_name"] ?? "";
        $email = $_POST["email"] ?? "";
        $password = $_POST["password"] ?? "";
        
        if (empty($firstName) || empty($email) || empty($password)) {
            echo json_encode(["success" => false, "message" => "Semua kolom wajib diisi."]);
            return;
        }

        // Validate password strength
        $pwError = "";
        if (!validate_password_strength($password, $pwError)) {
            echo json_encode(["success" => false, "message" => $pwError]);
            return;
        }

        $userModel = new User();
        
        // Check if email exists
        if ($userModel->findByEmail($email)) {
            echo json_encode(["success" => false, "message" => "Email sudah terdaftar."]);
            return;
        }

        $id = $userModel->create($firstName, $lastName, $email, $password);
        
        if ($id) {
            echo json_encode(["success" => true, "message" => "Pendaftaran berhasil! Silakan masuk.", "redirect" => "/signin"]);
        } else {
            echo json_encode(["success" => false, "message" => "Terjadi kesalahan sistem."]);
        }
    }

    /**
     * Determines and returns the appropriate Google OAuth callback URL.
     * 
     * - OOP: Method encapsulation within the AuthController helper layer.
     * - MVC: Serves as a helper for authentication flow routing logic.
     * - Connections: Utilizes system $_ENV variables, $_SERVER request details, and proxy
     *   headers to resolve HTTPS protocols and correct hostnames.
     * 
     * @return string
     */
    private function getGoogleRedirectUri() {
        $envUri = $_ENV["GOOGLE_REDIRECT_URI"] ?? "";
        
        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https") {
            $protocol = "https";
        }
        
        // Handle X-Forwarded-Host if behind a proxy
        $host = $_SERVER["HTTP_X_FORWARDED_HOST"] ?? $_SERVER["HTTP_HOST"] ?? "localhost:8000";
        // Strip ports or multiple hosts if present in header
        if (strpos($host, ",") !== false) {
            $parts = explode(",", $host);
            $host = trim(end($parts));
        }
        
        if (!empty($envUri)) {
            $parsedEnv = parse_url($envUri);
            $envHost = $parsedEnv["host"] ?? "";
            
            // Check if current request host is different from the env host
            // and build redirect URI using current host's protocol and host, but retaining path
            $reqHostOnly = parse_url($protocol . "://" . $host, PHP_URL_HOST);
            
            if ($envHost !== $reqHostOnly && !empty($reqHostOnly)) {
                $path = $parsedEnv["path"] ?? "/auth/google/callback";
                $query = isset($parsedEnv["query"]) ? "?" . $parsedEnv["query"] : "";
                return "{$protocol}://{$host}{$path}{$query}";
            }
            
            // Behind SSL terminating load balancers, automatically adjust http:// to https://
            if ($protocol === "https" && str_starts_with($envUri, "http://")) {
                $envHostUrl = parse_url($envUri, PHP_URL_HOST);
                $reqHostUrl = parse_url($protocol . "://" . $host, PHP_URL_HOST);
                if ($envHostUrl === $reqHostUrl) {
                    return str_replace("http://", "https://", $envUri);
                }
            }
            return $envUri;
        }

        return "{$protocol}://{$host}/auth/google/callback";
    }

    /**
     * Redirects the user's browser to the Google OAuth Consent screen.
     * 
     * - OOP: Encapsulated within the AuthController class.
     * - MVC: Functions as a controller route handler directing the view client out-of-app.
     * - Connections: Reads Google credentials from $_ENV and redirects the browser using header().
     * 
     * @return void
     */
    public function googleRedirect() {
        $clientId = $_ENV["GOOGLE_CLIENT_ID"];
        $redirectUri = urlencode($this->getGoogleRedirectUri());
        $scope = urlencode("email profile");
        
        $url = "https://accounts.google.com/o/oauth2/v2/auth?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code&scope={$scope}&access_type=online";
        header("Location: $url");
        exit;
    }

    /**
     * Handles the callback request redirected back from Google OAuth.
     * 
     * - OOP: Instantiates and updates the App\Models\User model with profile pictures.
     * - MVC: Controller action that accepts OAuth authorization codes, exchanges them for tokens,
     *   fetches Google profiles, maps users, sets session variables, and redirects to dashboard.
     * - Connections: Interacts with external Google APIs via cURL, queries/updates the App\Models\User model,
     *   sets up user session, sets "login_just_occurred" tracking values, and handles "remember_google" cookie.
     * 
     * @return void
     */
    public function googleCallback() {
        $code = $_GET["code"] ?? null;
        if (!$code) {
            header("Location: /signin?error=auth_failed");
            exit;
        }

        $ch = curl_init("https://oauth2.googleapis.com/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "client_id" => $_ENV["GOOGLE_CLIENT_ID"],
            "client_secret" => $_ENV["GOOGLE_CLIENT_SECRET"],
            "redirect_uri" => $this->getGoogleRedirectUri(),
            "grant_type" => "authorization_code",
            "code" => $code
        ]));
        $response = curl_exec($ch);
        
        // Fail-safe: if cURL fails due to SSL peer verification issues on custom VPS systems
        if ($response === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
        }
        curl_close($ch);
        
        $tokenData = json_decode($response, true);
        if (isset($tokenData["access_token"])) {
            $ch = curl_init("https://www.googleapis.com/oauth2/v2/userinfo");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $tokenData["access_token"]]);
            $profileResponse = curl_exec($ch);
            if ($profileResponse === false) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $profileResponse = curl_exec($ch);
            }
            curl_close($ch);
            
            $profile = json_decode($profileResponse, true);
            
            if (isset($profile["email"])) {
                $userModel = new User();
                $user = $userModel->findByEmail($profile["email"]);
                $googlePic = $profile["picture"] ?? null;
                
                if (!$user) {
                    $infoMsg = "Akun dengan email " . $profile["email"] . " tidak ditemukan. Silakan mendaftar terlebih dahulu untuk menggunakan layanan siCare.";
                    header("Location: /signup?info=" . urlencode($infoMsg));
                    exit;
                } else {
                    $resolvedPic = resolveProfilePicture($profile["email"], $googlePic);
                    $userModel->updateProfilePicture($user["id"], $resolvedPic);
                    $user["profile_picture"] = $resolvedPic;
                }

                if ($user) {
                    if (session_status() === PHP_SESSION_NONE) session_start();
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["role"] = $user["role"];
                    $_SESSION["role_id"] = $user["role_id"];
                    $_SESSION["department_id"] = $user["department_id"];
                    $_SESSION["name"] = $user["first_name"] . " " . $user["last_name"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["profile_picture"] = $user["profile_picture"];
                    $_SESSION["login_just_occurred"] = true;
                    
                    // Generate JWT Token for CRUD authorization
                    $jwtPayload = [
                        "user_id" => $user["id"],
                        "name" => $user["first_name"] . " " . $user["last_name"],
                        "email" => $user["email"],
                        "role" => $user["role"]
                    ];
                    $jwtToken = \App\Config\JwtHelper::createToken($jwtPayload);
                    $_SESSION["jwt_token"] = $jwtToken;

                    // Set dynamic database flag for login tracking
                    try {
                        $db = \App\Config\Database::getInstance()->getConnection();
                        $db->prepare("UPDATE users SET login_just_occurred = 1 WHERE id = ?")->execute([$user["id"]]);
                    } catch (\Exception $e) {
                        // Fail-safe
                    }
                    
                    // Set secure and samesite cookie parameters correctly
                    $isSecure = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https");
                    
                    // Set JWT token cookie
                    if (PHP_VERSION_ID >= 70300) {
                        setcookie("jwt_token", $jwtToken, [
                            "expires" => time() + 604800,
                            "path" => "/",
                            "domain" => "",
                            "secure" => $isSecure,
                            "httponly" => false,
                            "samesite" => "Lax"
                        ]);
                        setcookie("login_just_occurred", "1", [
                            "expires" => time() + 300,
                            "path" => "/",
                            "domain" => "",
                            "secure" => $isSecure,
                            "httponly" => false,
                            "samesite" => "Lax"
                        ]);
                    } else {
                        setcookie("jwt_token", $jwtToken, time() + 604800, "/; SameSite=Lax", "", $isSecure, false);
                        setcookie("login_just_occurred", "1", time() + 300, "/; SameSite=Lax", "", $isSecure, false);
                    }
                    
                    if (isset($_COOKIE["remember_google"]) && $_COOKIE["remember_google"] === "1") {
                        $_SESSION["remember_me"] = true;
                        $_SESSION["login_time"] = time();
                        
                        // Clear the cookie immediately
                        setcookie("remember_google", "", time() - 3600, "/");
                        
                        // Explicitly send a persistent cookie (7 days)
                        $params = session_get_cookie_params();
                        setcookie(
                            session_name(),
                            session_id(),
                            time() + 7 * 86400,
                            $params["path"],
                            $params["domain"] ?? "",
                            $params["secure"] ?? false,
                            $params["httponly"] ?? true
                        );
                    }
    
                    header("Location: /dashboard");
                    exit;
                }
            }
        }
        
        header("Location: /signin?error=google_auth_failed");
        exit;
    }

    /**
     * Updates the authenticated user's profile information.
     * 
     * - OOP: Interacts with the shared Database connection singleton object.
     * - MVC: Controller handler for profile update forms, processing incoming fields
     *   and returning structured JSON response results.
     * - Connections: Authenticates session, validates if the email is taken by other users in the DB,
     *   optionally validates/updates password hash, and updates the local session variables.
     * 
     * @return void
     */
    public function saveProfile() {
        header("Content-Type: application/json");
        session_start();

        if (!isset($_SESSION["user_id"])) {
            echo json_encode(["success" => false, "message" => "Akses ditolak."]);
            return;
        }

        $userId = $_SESSION["user_id"];
        $email = $_POST["email"] ?? "";
        $phone = $_POST["no_telepon"] ?? "";
        $domisili = $_POST["alamat_domisili"] ?? "";
        $lat = isset($_POST["home_latitude"]) && $_POST["home_latitude"] !== "" ? (float)$_POST["home_latitude"] : null;
        $lng = isset($_POST["home_longitude"]) && $_POST["home_longitude"] !== "" ? (float)$_POST["home_longitude"] : null;

        if (empty($email) || empty($phone) || empty($domisili)) {
            echo json_encode(["success" => false, "message" => "Email, nomor telepon, dan alamat domisili wajib diisi."]);
            return;
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            
            // Check if email already taken by another user
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
            $stmt->execute(["email" => $email, "id" => $userId]);
            if ($stmt->fetch()) {
                echo json_encode(["success" => false, "message" => "Email sudah terdaftar oleh pengguna lain."]);
                return;
            }

            $password = $_POST["password"] ?? "";

            $query = "UPDATE users SET email = :email, no_telepon = :phone, alamat_domisili = :domisili, home_latitude = :lat, home_longitude = :lng, updated_at = NOW()";
            $params = [
                "email" => $email,
                "phone" => $phone,
                "domisili" => $domisili,
                "lat" => $lat,
                "lng" => $lng,
                "id" => $userId
            ];

            if (!empty($password)) {
                // Validate password strength
                $pwError = "";
                if (!validate_password_strength($password, $pwError)) {
                    echo json_encode(["success" => false, "message" => $pwError]);
                    return;
                }
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $query .= ", password_hash = :password_hash";
                $params["password_hash"] = $password_hash;
            }

            $query .= " WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->execute($params);

            // Update session
            $_SESSION["email"] = $email;

            echo json_encode(["success" => true, "message" => "Profil berhasil diperbarui."]);
        } catch (\Exception $e) {
            echo json_encode(["success" => false, "message" => "Kesalahan server: " . $e->getMessage()]);
        }
    }

    /**
     * Destroys the active session and logs out the current user.
     * 
     * - OOP: Standard routing controller action.
     * - MVC: Controller layer logic to clean session state and trigger browser redirects.
     * - Connections: Calls session_start(), session_destroy(), and issues a Location redirect.
     * 
     * @return void
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION["user_id"] ?? null;
        if ($userId) {
            try {
                $db = \App\Config\Database::getInstance()->getConnection();
                $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")->execute([$userId]);
            } catch (\Exception $e) {
                // Fail-safe
            }
        }
        
        // Hapus cookie remember_me_token
        setcookie("remember_me_token", "", time() - 3600, "/");
        
        // Hapus cookie jwt_token
        setcookie("jwt_token", "", time() - 3600, "/");
        
        // Hapus session
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: /");
        exit;
    }

    /**
     * Initiates user impersonation/simulation for Super Admins.
     * 
     * - OOP: Interacts with PDO database connection and session state models.
     * - MVC: Controller action executing authorization checks, querying user profiles,
     *   storing original admin states, and switching active session data to target users.
     * - Connections: Requires superadmin role/privileges, updates session variables, fetches target
     *   user records from the DB, generates Gravatar URL fallback if profile picture is empty,
     *   and responds with a redirection URL.
     * 
     * @return void
     */
    public function impersonate() {
        header("Content-Type: application/json");
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Impersonation is only allowed for Super Admins
        $isSuperAdmin = ($_SESSION["role"] ?? "") === "superadmin" || ($_SESSION["original_role"] ?? "") === "superadmin";
        if (!$isSuperAdmin) {
            echo json_encode(["success" => false, "message" => "Akses ditolak. Hanya Super Admin yang diizinkan melakukan simulasi login."]);
            return;
        }

        $targetUserId = $_POST["user_id"] ?? "";
        if (empty($targetUserId)) {
            echo json_encode(["success" => false, "message" => "ID pengguna wajib diisi."]);
            return;
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(["id" => $targetUserId]);
            $targetUser = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$targetUser) {
                echo json_encode(["success" => false, "message" => "Pengguna tidak ditemukan."]);
                return;
            }

            // Save original session data if we aren't already impersonating
            if (!isset($_SESSION["original_user_id"])) {
                $_SESSION["original_user_id"] = $_SESSION["user_id"];
                $_SESSION["original_role"]    = $_SESSION["role"];
                $_SESSION["original_role_id"] = $_SESSION["role_id"] ?? null;
                $_SESSION["original_department_id"] = $_SESSION["department_id"] ?? null;
                $_SESSION["original_name"]    = $_SESSION["name"];
                $_SESSION["original_email"]   = $_SESSION["email"];
                $_SESSION["original_profile_picture"] = $_SESSION["profile_picture"] ?? null;
            }

            // Set session to target user
            $_SESSION["user_id"] = $targetUser["id"];
            $_SESSION["role"] = $targetUser["role"];
            $_SESSION["role_id"] = $targetUser["role_id"];
            $_SESSION["department_id"] = $targetUser["department_id"];
            $_SESSION["name"] = $targetUser["first_name"] . " " . $targetUser["last_name"];
            $_SESSION["email"] = $targetUser["email"];
            
            $pp = $targetUser["profile_picture"];
            if (empty($pp)) {
                $hash = md5(strtolower(trim($targetUser["email"])));
                $pp = "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
            }
            $_SESSION["profile_picture"] = $pp;

            echo json_encode(["success" => true, "redirect" => "/dashboard"]);
        } catch (\Exception $e) {
            echo json_encode(["success" => false, "message" => "Terjadi kesalahan: " . $e->getMessage()]);
        }
    }

    /**
     * Terminates the simulation/impersonation mode and restores original Super Admin session.
     * 
     * - OOP: Controller encapsulated method.
     * - MVC: Controller action restoring system state models.
     * - Connections: Resets session values back to original Super Admin properties and cleans
     *   up temporary session storage markers, redirecting to user management page.
     * 
     * @return void
     */
    public function stopImpersonating() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION["original_user_id"])) {
            // Restore original session data
            $_SESSION["user_id"] = $_SESSION["original_user_id"];
            $_SESSION["role"]    = $_SESSION["original_role"];
            $_SESSION["role_id"] = $_SESSION["original_role_id"] ?? null;
            $_SESSION["department_id"] = $_SESSION["original_department_id"] ?? null;
            $_SESSION["name"]    = $_SESSION["original_name"];
            $_SESSION["email"]   = $_SESSION["original_email"];
            $_SESSION["profile_picture"] = $_SESSION["original_profile_picture"];

            // Clean up original session markers
            unset($_SESSION["original_user_id"]);
            unset($_SESSION["original_role"]);
            unset($_SESSION["original_role_id"]);
            unset($_SESSION["original_department_id"]);
            unset($_SESSION["original_name"]);
            unset($_SESSION["original_email"]);
            unset($_SESSION["original_profile_picture"]);

            header("Location: /superadmin/users");
            exit;
        }

        header("Location: /dashboard");
        exit;
    }

    /**
     * Records the user's login geographical location, IP address, and user agent.
     * 
     * - OOP: Interacts with Database connection singleton and PDO statements.
     * - MVC: Controller endpoint returning JSON response status.
     * - Connections: Reads request JSON inputs, gets server $_SERVER remote variables,
     *   inserts entries into the login_logs DB table, verifies session/cookie login status flags,
     *   and clears the login_just_occurred flags.
     * 
     * @return void
     */
    public function recordLoginLocation() {
        header("Content-Type: application/json");
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION["user_id"])) {
            echo json_encode(["success" => false, "message" => "Akses ditolak. Silakan masuk terlebih dahulu."]);
            return;
        }

        // Parse JSON input or POST parameters
        $input = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $lat = (isset($input["lat"]) && $input["lat"] !== "") ? (float)$input["lat"] : null;
        $lng = (isset($input["lng"]) && $input["lng"] !== "") ? (float)$input["lng"] : null;

        $userId = $_SESSION["user_id"];
        $ipAddress = $_SERVER["REMOTE_ADDR"] ?? null;
        $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? null;
        
        // Generate UUID
        $uuid = sprintf("%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Determine status
        $status = "Akses Aplikasi";
        $db = \App\Config\Database::getInstance()->getConnection();
        
        // Check database flag as the primary source of truth for login event
        $loginJustOccurredDb = false;
        try {
            $stmtCheck = $db->prepare("SELECT login_just_occurred FROM users WHERE id = :id LIMIT 1");
            $stmtCheck->execute(["id" => $userId]);
            $loginJustOccurredDb = ((int)$stmtCheck->fetchColumn() === 1);
        } catch (\Exception $e) {
            // Fail-safe
        }

        if ($loginJustOccurredDb || (isset($_COOKIE["login_just_occurred"]) && $_COOKIE["login_just_occurred"] === "1") || isset($_SESSION["login_just_occurred"])) {
            $status = "Login";
            
            // Clear all flags (db, session, cookie)
            try {
                $db->prepare("UPDATE users SET login_just_occurred = 0 WHERE id = ?")->execute([$userId]);
            } catch (\Exception $e) {
                // Fail-safe
            }
            
            $isSecure = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https");
            if (PHP_VERSION_ID >= 70300) {
                setcookie("login_just_occurred", "", [
                    "expires" => time() - 3600,
                    "path" => "/",
                    "domain" => "",
                    "secure" => $isSecure,
                    "httponly" => false,
                    "samesite" => "Lax"
                ]);
            } else {
                setcookie("login_just_occurred", "", time() - 3600, "/; SameSite=Lax", "", $isSecure, false);
            }
            unset($_SESSION["login_just_occurred"]);
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO login_logs (id, user_id, latitude, longitude, ip_address, user_agent, status, login_at)
                VALUES (:id, :user_id, :latitude, :longitude, :ip_address, :user_agent, :status, NOW())
            ");
            $stmt->execute([
                "id" => $uuid,
                "user_id" => $userId,
                "latitude" => $lat,
                "longitude" => $lng,
                "ip_address" => $ipAddress,
                "user_agent" => $userAgent,
                "status" => $status
            ]);

            $_SESSION["login_location_recorded"] = true;
            echo json_encode(["success" => true, "message" => "Aktivitas akses berhasil direkam."]);
        } catch (\Exception $e) {
            echo json_encode(["success" => false, "message" => "Gagal merekam aktivitas akses: " . $e->getMessage()]);
        }
    }

    /**
     * Calculates the lockout duration in seconds based on failed login attempts.
     * 
     * - 1-9 attempts: 0 seconds (no lockout)
     * - 10-12 attempts: 15s to 150s (10th: 15s, 11th: 60s, 12th: 150s)
     * - 13-16 attempts: 2x duration of previous attempt (13th: 300s, 14th: 600s, 15th: 1200s, 16th: 2400s)
     * - 17-20 attempts: 2x duration of previous attempt (17th: 4800s, 18th: 9600s, 19th: 19200s, 20th: 38400s)
     * - >=21 attempts: blocked (-1)
     * 
     * @param int $attempts Number of failed attempts
     * @return int Lockout duration in seconds, or -1 for permanent block
     */
    private function getLockoutDuration($attempts) {
        if ($attempts < 10) {
            return 0;
        }
        if ($attempts == 10) return 15;
        if ($attempts == 11) return 60;
        if ($attempts == 12) return 150;
        if ($attempts >= 13 && $attempts <= 16) {
            return 150 * pow(2, $attempts - 12);
        }
        if ($attempts >= 17 && $attempts <= 20) {
            return 2400 * pow(2, $attempts - 16);
        }
        return -1; // -1 represents blocked
    }
}
