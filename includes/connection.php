<?php
// includes/connection.php

// 1. Cargar configuración de entorno
require_once __DIR__ . '/../config/env.php';

// 2. Cargar configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// 3. Obtener conexión
try {
    $pdo = Database::getConnection();
    
    // Configurar zona horaria si es necesario
    $pdo->exec("SET TIME ZONE 'America/Mexico_City'");
    
} catch (PDOException $e) {
    // Manejo de errores
    error_log("Error de base de datos: " . $e->getMessage());
    
    // Mostrar error amigable
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['error'] = "Error de conexión a la base de datos. Por favor, intente más tarde.";
    
    if (!headers_sent()) {
        header("Location: /error.php");
    }
    exit;
}
?>