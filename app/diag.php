<?php
$host = 'postgres-db';
$port = '5432';
$dbname = 'mi_app';  // ¿Está usando esta?
$user = 'postgres';
$password = 'postgres';

try {
    // Intentar conectar a mi_app
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    
    $stmt = $pdo->query("SELECT current_database()");
    $current_db = $stmt->fetchColumn();
    
    echo "<h2>✅ Conectado a: <span style='color: green;'>$current_db</span></h2>";
    
    // Probar productos
    $stmt = $pdo->query("SELECT COUNT(*) FROM productos");
    $count = $stmt->fetchColumn();
    echo "<p>Productos en la tabla: <strong>$count</strong></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error conectando a '$dbname':</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // Intentar conectar sin especificar base de datos (va a 'postgres' por defecto)
    try {
        $dsn_default = "pgsql:host=$host;port=$port";
        $pdo_default = new PDO($dsn_default, $user, $password);
        
        $stmt = $pdo_default->query("SELECT current_database()");
        $default_db = $stmt->fetchColumn();
        
        echo "<p>Por defecto te conectarías a: <strong>$default_db</strong></p>";
        
        // Listar bases de datos disponibles
        $stmt = $pdo_default->query("SELECT datname FROM pg_database WHERE datistemplate = false");
        $dbs = $stmt->fetchAll();
        
        echo "<h3>Bases de datos disponibles:</h3>";
        echo "<ul>";
        foreach ($dbs as $db) {
            echo "<li>" . $db['datname'] . "</li>";
        }
        echo "</ul>";
        
    } catch (PDOException $e2) {
        echo "<p>Error en conexión por defecto: " . $e2->getMessage() . "</p>";
    }
}
?>
