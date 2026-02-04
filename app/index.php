<?php
// app/index.php - App con PostgreSQL + Redis

echo "<h1 style='color: purple; font-family: Arial;'>¡Mi APP con DOCKER + PostgreSQL + Redis! 🚀</h1>";

// ========== SECCIÓN REDIS ==========
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 10px; margin-bottom: 20px;'>";
echo "<h2 style='color: #d63031;'>🧠 Redis Cache</h2>";

// Verificar si Redis está disponible
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $redisConnected = $redis->connect('redis', 6379, 2);
        
        if ($redisConnected) {
            echo "<p style='color: green;'>✅ ¡Conexión a Redis exitosa!</p>";
            
            // Contador de visitas en Redis
            $redisVisits = $redis->incr('total_visits');
            echo "<p>👥 Visitas totales (Redis): <strong>$redisVisits</strong></p>";
            
            // Cache de ejemplo
            $cacheKey = 'current_time';
            if (!$redis->exists($cacheKey)) {
                $currentTime = date('Y-m-d H:i:s');
                $redis->setex($cacheKey, 5, $currentTime);
                $source = "(generado nuevo)";
            } else {
                $currentTime = $redis->get($cacheKey);
                $source = "(desde cache Redis)";
            }
            
            echo "<p>🕐 Hora actual $source: <strong>$currentTime</strong></p>";
            
            echo "<p><a href='http://localhost:8082' target='_blank'>📊 Abrir Dashboard de Redis (puerto 8082)</a></p>";
            
        } else {
            echo "<p style='color: orange;'>⚠️ Redis no disponible</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Error Redis: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Extensión Redis no disponible</p>";
}
echo "</div>";

// ========== SECCIÓN POSTGRESQL ==========
echo "<div style='background: #f0fff0; padding: 15px; border-radius: 10px; margin-bottom: 20px;'>";
echo "<h2 style='color: #27ae60;'>🗄️ PostgreSQL Database</h2>";

try {
    // CREDENCIALES CORRECTAS (iguales que en docker-compose.yml)
    $host = 'database';
    $dbname = 'mi_app';
    $user = 'postgres';
    $password = 'postgres';
    
    $dsn = "pgsql:host=$host;port=5432;dbname=$dbname;";
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<p style='color: green;'>✅ ¡Conexión a PostgreSQL exitosa!</p>";
    
    // Crear tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitas (
        id SERIAL PRIMARY KEY,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip VARCHAR(45),
        user_agent TEXT
    )");
    
    // Insertar visita actual
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
    
    $stmt = $pdo->prepare("INSERT INTO visitas (ip, user_agent) VALUES (?, ?)");
    $stmt->execute([$ip, $userAgent]);
    
    // Contar visitas
    $count = $pdo->query("SELECT COUNT(*) as total FROM visitas")->fetch()['total'];
    echo "<p>📊 Total de visitas (PostgreSQL): <strong>$count</strong></p>";
    
    // Mostrar últimas 5 visitas
    $visitas = $pdo->query("SELECT fecha, ip FROM visitas ORDER BY fecha DESC LIMIT 5")->fetchAll();
    
    echo "<h4>Últimas visitas:</h4><ul>";
    foreach ($visitas as $visita) {
        echo "<li>" . $visita['fecha'] . " - " . $visita['ip'] . "</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='http://localhost:8081' target='_blank'>📈 Abrir phpPgAdmin (puerto 8081)</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error PostgreSQL: " . $e->getMessage() . "</p>";
    echo "<p><strong>Credenciales usadas:</strong></p>";
    echo "<ul>";
    echo "<li>Host: database</li>";
    echo "<li>DB: mi_app</li>";
    echo "<li>User: postgres</li>";
    echo "<li>Pass: postgres</li>";
    echo "</ul>";
}
echo "</div>";

// ========== SECCIÓN INFORMACIÓN ==========
echo "<div style='background: #fffaf0; padding: 15px; border-radius: 10px;'>";
echo "<h2 style='color: #2980b9;'>📊 Sistema</h2>";

echo "<p>🐋 Contenedores corriendo: <strong>5</strong></p>";
echo "<p>🔄 PHP Version: " . phpversion() . "</p>";
echo "<p>🔧 Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Apache') . "</p>";

// Info Redis
if (extension_loaded('redis')) {
    echo "<p>🧠 Extensión Redis: <span style='color: green;'>✅ Instalada</span></p>";
} else {
    echo "<p>🧠 Extensión Redis: <span style='color: red;'>❌ No disponible</span></p>";
}

echo "<p>💾 Memoria usada: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB</p>";
echo "</div>";

// ========== ENLACES RÁPIDOS ==========
echo "<div style='margin-top: 20px; padding: 15px; background: #e8f4f8; border-radius: 10px;'>";
echo "<h3>🔗 Accesos Rápidos:</h3>";
echo "<ul>";
echo "<li><a href='http://localhost:8080' target='_blank'>🏠 Esta App (puerto 8080)</a></li>";
echo "<li><a href='http://localhost:8081' target='_blank'>🐘 phpPgAdmin - Admin PostgreSQL</a></li>";
echo "<li><a href='http://localhost:8082' target='_blank'>🧠 Redis Admin - Dashboard Redis</a></li>";
echo "<li><a href='http://localhost:5432' target='_blank'>🗄️ PostgreSQL directo (puerto 5432)</a></li>";
echo "<li><a href='http://localhost:6379' target='_blank'>⚡ Redis directo (puerto 6379)</a></li>";
echo "</ul>";
echo "</div>";
?>