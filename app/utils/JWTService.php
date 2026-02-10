<?php
// app/utils/JWTService.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt.php';


use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class JWTService {
    
    // Generar token JWT
    public static function generateToken($userId, $username, $roleId, $roleName) {
        $secret = JWTConfig::getSecret();
        $expireHours = JWTConfig::getExpiration();
        
        $issuedAt = time();
        $expirationTime = $issuedAt + ($expireHours * 3600);
        
        $payload = [
            'iss' => 'mi-app-docker', // Emisor
            'aud' => 'mi-app-client', // Audiencia
            'iat' => $issuedAt,       // Tiempo de emisión
            'exp' => $expirationTime, // Tiempo de expiración
            'data' => [
                'userId' => $userId,
                'username' => $username,
                'roleId' => $roleId,
                'roleName' => $roleName
            ]
        ];
        
        return JWT::encode($payload, $secret, JWTConfig::getAlgorithm());
    }
    
    // Validar y decodificar token
    public static function validateToken($token) {
        try {
            $secret = JWTConfig::getSecret();
            $decoded = JWT::decode($token, new Key($secret, JWTConfig::getAlgorithm()));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Extraer datos del usuario del token
    public static function getUserFromToken($token) {
        $decoded = self::validateToken($token);
        if ($decoded && isset($decoded['data'])) {
            return (array) $decoded['data'];
        }
        return null;
    }
    
    // Verificar si token está a punto de expirar (útil para refresh)
    public static function isTokenExpiringSoon($token, $thresholdMinutes = 30) {
        $decoded = self::validateToken($token);
        if (!$decoded) return true;
        
        $exp = $decoded['exp'];
        $currentTime = time();
        $timeLeft = $exp - $currentTime;
        
        return $timeLeft < ($thresholdMinutes * 60);
    }
    
    // Obtener header Authorization
    public static function getBearerToken() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        // HEADER: Bearer token
        if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
