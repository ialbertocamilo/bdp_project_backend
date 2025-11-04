<?php

namespace App\Http\Helpers;

class SecurityHelper
{
    public static function sanitizeInput($input)
    {
        $dangerous_patterns = [
            '/union\s+select/i',
            '/select\s+\*/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/update\s+/i',
            '/delete\s+from/i',
            '/exec\s*\(/i',
            '/script\s*>/i',
        ];

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new \Exception('Suspicious input detected: potential SQL injection or XSS attempt');
            }
        }

        return trim(stripslashes(htmlspecialchars($input, ENT_QUOTES, 'UTF-8')));
    }

    public static function validatePassword($password)
    {
        if (strlen($password) < 8) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            return false;
        }

        return true;
    }

    public static function generateHmacSignature($payload, $secret)
    {
        return hash_hmac('sha256', $payload, $secret);
    }
}
