<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    public static function generateToken($userId, $username, $email)
    {
        $secretKey = $_ENV['JWT_SECRET'] ?? 'fallbackSecret';
        $payload = [
            'iss'      => 'mi_sistema',
            'iat'      => time(),
            'exp'      => time() + 3600,  // Token válido por xx segundos
            'id'       => $userId,
            'username' => $username,
            'email'    => $email
        ];
        return JWT::encode($payload, $secretKey, 'HS256');
    }

    public static function verifyToken($token)
    {
        $secretKey = $_ENV['JWT_SECRET'] ?? 'fallbackSecret';
        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}
