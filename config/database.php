<?php
// config/database.php

class Database {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                // Leer variables de .env (simplificado)
                $host = getenv('DB_HOST') ?: 'postgres-db';
                $port = getenv('DB_PORT') ?: '5432';
                $dbname = getenv('DB_NAME') ?: 'mi_app';
                $user = getenv('DB_USER') ?: 'postgres';
                $password = getenv('DB_PASSWORD') ?: 'postgres';
                
                // Cadena de conexión PostgreSQL
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
                
                // Crear conexión PDO
                self::$connection = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
                
                echo "<!-- Conexión a PostgreSQL establecida -->\n";
                
            } catch (PDOException $e) {
                die("Error de conexión: " . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
}
?>