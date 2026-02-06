<?php
// app/auth/middlewares/AuthMiddleware.php

require_once __DIR__ . '/../../utils/JWTService.php';
require_once __DIR__ . '/../../utils/ResponseHelper.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';

class AuthMiddleware {
    
    // Verificar autenticación (cualquier usuario logueado)
    public static function requireAuth() {
        $token = JWTService::getBearerToken();
        
        if (!$token) {
            ResponseHelper::unauthorized('Token no proporcionado');
        }
        
        $userData = JWTService::getUserFromToken($token);
        
        if (!$userData) {
            ResponseHelper::unauthorized('Token inválido o expirado');
        }
        
        return $userData;
    }
    
    // Verificar rol específico
    public static function requireRole($requiredRole) {
        $userData = self::requireAuth();
        
        if ($userData['roleName'] !== $requiredRole) {
            ResponseHelper::forbidden('Se requiere rol: ' . $requiredRole);
        }
        
        return $userData;
    }
    
    // Verificar si tiene al menos uno de los roles
    public static function requireAnyRole($allowedRoles) {
        $userData = self::requireAuth();
        
        if (!in_array($userData['roleName'], $allowedRoles)) {
            $rolesStr = implode(', ', $allowedRoles);
            ResponseHelper::forbidden('Se requiere uno de los roles: ' . $rolesStr);
        }
        
        return $userData;
    }
    
    // Verificar permiso específico
    public static function requirePermission($permissionName) {
        $userData = self::requireAuth();
        
        $roleModel = new Role();
        $hasPermission = $roleModel->hasPermission($userData['roleId'], $permissionName);
        
        if (!$hasPermission) {
            ResponseHelper::forbidden('Se requiere permiso: ' . $permissionName);
        }
        
        return $userData;
    }
    
    // Obtener usuario actual (sin fallar si no hay token)
    public static function getCurrentUser() {
        $token = JWTService::getBearerToken();
        
        if (!$token) {
            return null;
        }
        
        return JWTService::getUserFromToken($token);
    }
}
