<?php
// app/test_auth.php - Prueba del sistema de autenticación

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/models/User.php';

echo "=== Prueba del Sistema de Autenticación ===\n\n";

// 1. Probar conexión a base de datos
echo "1. Probando conexión a PostgreSQL...\n";
try {
    $user = new User();
    echo "   ✅ Conexión exitosa\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit;
}

// 2. Buscar usuario admin
echo "\n2. Buscando usuario admin...\n";
$admin = $user->findByUsername('admin');
if ($admin) {
    echo "   ✅ Usuario admin encontrado\n";
    echo "   ID: " . $admin['id'] . "\n";
    echo "   Email: " . $admin['email'] . "\n";
    echo "   Rol ID: " . $admin['role_id'] . "\n";
    
    // 3. Verificar contraseña
    echo "\n3. Verificando contraseña 'Admin123!'...\n";
    if ($user->verifyPassword('Admin123!', $admin['password_hash'])) {
        echo "   ✅ Contraseña correcta\n";
    } else {
        echo "   ❌ Contraseña incorrecta\n";
    }
} else {
    echo "   ❌ Usuario admin no encontrado\n";
}

// 4. Probar hash de nueva contraseña
echo "\n4. Probando hash de contraseña...\n";
$testPassword = 'Test123!';
$hashed = $user->hashPassword($testPassword);
echo "   Contraseña original: " . $testPassword . "\n";
echo "   Hash generado: " . substr($hashed, 0, 30) . "...\n";
echo "   Longitud hash: " . strlen($hashed) . " caracteres\n";

// 5. Verificar instalación de Firebase JWT
echo "\n5. Verificando Firebase JWT...\n";
if (class_exists('Firebase\JWT\JWT')) {
    echo "   ✅ Firebase JWT instalado correctamente\n";
} else {
    echo "   ❌ Firebase JWT NO está instalado\n";
    echo "   Ejecuta: docker exec -it app-php composer require firebase/php-jwt\n";
}

echo "\n=== Prueba completada ===\n";
