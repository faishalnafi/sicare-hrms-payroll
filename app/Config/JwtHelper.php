<?php

namespace App\Config;

/**
 * Class JwtHelper
 * 
 * Provides JSON Web Token (JWT) generation and validation utilities 
 * using HS256 algorithm for CRUD write operations authorization.
 */
class JwtHelper {
    private static function getSecret(): string {
        // Read from environment variable — NEVER hardcode secrets in source code
        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? '';
        if (empty($secret)) {
            // Fallback: derive from APP_KEY if JWT_SECRET not set, or use a strong default
            $secret = $_ENV['APP_KEY'] ?? 'siCare_jwt_fallback_key_change_me_in_dotenv';
        }
        return $secret;
    }

    /**
     * Encode payload data to base64url format.
     * 
     * @param string $data
     * @return string
     */
    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Decode base64url formatted string.
     * 
     * @param string $data
     * @return string
     */
    private static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    /**
     * Create a new JWT token signed with HMAC-SHA256.
     * 
     * @param array $payload
     * @param int $expirySeconds (Default: 7 days)
     * @return string
     */
    public static function createToken(array $payload, int $expirySeconds = 604800) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expirySeconds;

        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Payload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, self::getSecret(), true);
        $base64Signature = self::base64UrlEncode($signature);

        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    /**
     * Validate a JWT token and return its payload if valid.
     * 
     * @param string $jwt
     * @return array|false
     */
    public static function validateToken(string $jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        // Verify signature
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, self::getSecret(), true);
        if (!hash_equals(self::base64UrlDecode($base64Signature), $signature)) {
            return false;
        }

        // Verify expiration
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        if (isset($payload['exp']) && time() > $payload['exp']) {
            return false; // Token has expired
        }

        return $payload;
    }
}
