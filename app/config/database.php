<?php
// app/config/database.php

class Database {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $host = getenv("DB_HOST") ?: "database";
                $dbname = getenv("DB_NAME") ?: "mi_app";
                $user = getenv("DB_USER") ?: "postgres";
                $password = getenv("DB_PASSWORD") ?: "postgres";
                
                $dsn = "pgsql:host=$host;dbname=$dbname;port=5432";
                self::$connection = new PDO($dsn, $user, $password);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                error_log("Error de conexión a PostgreSQL: " . $e->getMessage());
                throw new Exception("Error de conexión a la base de datos");
            }
        }
        return self::$connection;
    }
    
    public static function closeConnection() {
        self::$connection = null;
    }
}
