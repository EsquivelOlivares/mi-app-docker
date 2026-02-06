<?php
// app/config/jwt.php

class JWTConfig {
    public static function getSecret() {
        return getenv('JWT_SECRET') ?: 'TuClaveSecretaSuperSegura2026!@#';
    }
    
    public static function getExpiration() {
        return getenv('JWT_EXPIRE_HOURS') ?: 24; // horas
    }
    
    public static function getAlgorithm() {
        return 'HS256';
    }
}
