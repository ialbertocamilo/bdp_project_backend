<?php

namespace App\Http\Helpers;

class SecurityHelper
{
    public static function sanitizeInput($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        $patterns = [
            '/(\b(ALTER|CREATE|DELETE|DROP|EXEC(UTE){0,1}|INSERT( +INTO){0,1}|REPLACE|SELECT|TRUNCATE|UPDATE|UNION( +ALL){0,1})\b)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new \InvalidArgumentException('Potential SQL injection detected');
            }
        }
        
        return $input;
    }

    public static function validatePassword($password)
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[\W]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }

    public static function generateSecureToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    public static function hashData($data, $secret)
    {
        return hash_hmac('sha256', $data, $secret);
    }
}