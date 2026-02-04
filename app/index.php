<?php
// app/index.php - Una app SUPER simple que usa PostgreSQL

echo "<h1 style='color: purple; font-family: Arial;'>¡Mi PRIMERA APP con DOCKER funciona! 🎉</h1>";

// Intentar conectar a PostgreSQL
try {
    // Estas variables vienen de docker-compose.yml
    $host = getenv('DB_HOST') ?: 'database';  // 'database' es el nombre del servicio en docker-compose
    $dbname = getenv('DB_NAME') ?: 'mi_app';
    $user = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: 'postgres';
    
    $dsn = "pgsql:host=$host;port=5432;dbname=$dbname;";
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<p style='color: green;'>✅ ¡Conexión a PostgreSQL exitosa!</p>";
    
    // Crear una tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitas (
        id SERIAL PRIMARY KEY,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip VARCHAR(45)
    )");
    
    // Insertar visita actual
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare("INSERT INTO visitas (ip) VALUES (?)");
    $stmt->execute([$ip]);
    
    // Contar visitas
    $count = $pdo->query("SELECT COUNT(*) as total FROM visitas")->fetch()['total'];
    
    echo "<p>📊 Total de visitas: <strong>$count</strong></p>";
    
    // Mostrar últimas 5 visitas
    $visitas = $pdo->query("SELECT fecha, ip FROM visitas ORDER BY fecha DESC LIMIT 5")->fetchAll();
    
    echo "<h3>Últimas visitas:</h3><ul>";
    foreach ($visitas as $visita) {
        echo "<li>" . $visita['fecha'] . " - " . $visita['ip'] . "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
    echo "<p>Variables de entorno:<br>";
    echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'No definido') . "<br>";
    echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'No definido') . "<br>";
    echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'No definido') . "</p>";
}

// Mostrar info del servidor
echo "<hr>";
echo "<h3>Información del servidor:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Mostrar variables de entorno (solo para debug)
if (getenv('SHOW_ENV') === 'true') {
    echo "<h3>Variables de entorno:</h3><pre>";
    print_r($_ENV);
    echo "</pre>";
}
?>