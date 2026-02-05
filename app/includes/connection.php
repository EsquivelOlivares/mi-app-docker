<?php
// includes/connection.php - VERSIÓN CORREGIDA

// Configuración directa
$host = 'postgres-db';
$port = '5432';
$dbname = 'mi_app';
$user = 'postgres';
$password = 'postgres';

// Variable global $pdo
global $pdo;

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // IMPORTANTE: NO imprimir nada aquí
    // Solo asignar $pdo
    
} catch (PDOException $e) {
    // Guardar error en log, no mostrar directamente
    error_log("❌ Error de conexión PostgreSQL: " . $e->getMessage());
    $pdo = null;
}
?>