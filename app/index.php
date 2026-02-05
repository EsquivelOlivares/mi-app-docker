<?php
// app/index.php - App con PostgreSQL + Redis

// ========== INCLUIR CONEXIÓN CENTRALIZADA ==========
require_once '/var/www/html/includes/connection.php';

echo "<h1 style='color: purple; font-family: Arial;'>¡Mi APP con DOCKER + PostgreSQL + Redis! 🚀</h1>";

// ========== SECCIÓN REDIS ==========
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 10px; margin-bottom: 20px;'>";
echo "<h2 style='color: #d63031;'>🧠 Redis Cache</h2>";

// ========== ENLACE AL CARRITO ==========
echo "<div style='margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #FF9800, #F57C00); border-radius: 10px; text-align: center;'>";
echo "<h3>🛒 ¡Nueva Funcionalidad!</h3>";
echo "<p>Ahora tienes un <strong>carrito de compras completo</strong> usando Redis + PostgreSQL</p>";
echo "<p><a href='/cart.php' style='display: inline-block; padding: 12px 24px; background: white; color: #FF9800; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px;'>Ir al Carrito</a></p>";
echo "<p><small>💡 Redis para carrito temporal + PostgreSQL para órdenes permanentes</small></p>";
echo "</div>";

// Verificar si Redis está disponible
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $redisConnected = $redis->connect('redis-cache', 6379, 2);
        
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
    // Ya tenemos $pdo desde includes/connection.php
    echo "<p style='color: green;'>✅ ¡Conexión a PostgreSQL exitosa! (usando conexión centralizada)</p>";
    
    // Crear tabla visitas si no existe (por si acaso)
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
    
    // ========== MOSTRAR PRODUCTOS ==========
    echo "<h3>🛍️ Productos Disponibles</h3>";
    
    $stmt = $pdo->query("
        SELECT id, nombre, descripcion, precio, categoria, stock, imagen_url 
        FROM productos 
        WHERE stock > 0 
        ORDER BY creado_en DESC
    ");
    $productos = $stmt->fetchAll();
    
    if (count($productos) > 0) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #4CAF50; color: white;'>";
        echo "<th>ID</th><th>Producto</th><th>Descripción</th><th>Precio</th><th>Categoría</th><th>Stock</th>";
        echo "</tr>";
        
        foreach ($productos as $producto) {
            $colorFila = $producto['stock'] > 20 ? '' : 'background-color: #FFF3CD;';
            echo "<tr style='$colorFila'>";
            echo "<td>" . htmlspecialchars($producto['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($producto['nombre']) . "</strong> " . htmlspecialchars($producto['imagen_url']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($producto['descripcion'], 0, 50)) . "...</td>";
            echo "<td style='color: #E91E63; font-weight: bold;'>$" . number_format($producto['precio'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($producto['categoria']) . "</td>";
            echo "<td style='color: " . ($producto['stock'] > 10 ? 'green' : 'orange') . ";'>" . htmlspecialchars($producto['stock']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>Total de productos: <strong>" . count($productos) . "</strong></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No hay productos en la base de datos.</p>";
        echo "<p><a href='/test-connection.php' target='_blank'>Probar conexión</a></p>";
    }
    
    echo "<p><a href='http://localhost:8081' target='_blank'>📈 Abrir pgAdmin (puerto 8081)</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error PostgreSQL: " . $e->getMessage() . "</p>";
    echo "<p><strong>Credenciales usadas:</strong></p>";
    echo "<ul>";
    echo "<li>Host: " . getenv('DB_HOST') . "</li>";
    echo "<li>DB: " . getenv('DB_NAME') . "</li>";
    echo "<li>User: " . getenv('DB_USER') . "</li>";
    echo "</ul>";
    echo "<p><a href='/test-connection.php' target='_blank'>🔧 Probar configuración</a></p>";
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

// Info PostgreSQL PDO
echo "<p>🗄️ PDO PostgreSQL: <span style='color: green;'>✅ Disponible</span></p>";
echo "<p>🔗 Conexión DB: " . getenv('DB_HOST') . "/" . getenv('DB_NAME') . "</p>";
echo "</div>";

// ========== ENLACES RÁPIDOS ==========
echo "<div style='margin-top: 20px; padding: 15px; background: #e8f4f8; border-radius: 10px;'>";
echo "<h3>🔗 Accesos Rápidos:</h3>";
echo "<ul>";
echo "<li><a href='http://localhost:8080' target='_blank'>🏠 Esta App (puerto 8080)</a></li>";
echo "<li><a href='http://localhost:8080/test-connection.php' target='_blank'>🔌 Probar Conexión DB</a></li>";
echo "<li><a href='http://localhost:8080/cart.php' target='_blank'>🛒 Carrito de Compras</a></li>";
echo "<li><a href='http://localhost:8081' target='_blank'>🐘 pgAdmin - Admin PostgreSQL</a></li>";
echo "<li><a href='http://localhost:8082' target='_blank'>🧠 Redis Admin - Dashboard Redis</a></li>";
echo "<li><a href='http://localhost:5432' target='_blank'>🗄️ PostgreSQL directo (puerto 5432)</a></li>";
echo "<li><a href='http://localhost:6379' target='_blank'>⚡ Redis directo (puerto 6379)</a></li>";
echo "</ul>";
echo "</div>";

// ========== BOTÓN PARA EJECUTAR SCRIPT SQL ==========
echo "<div style='margin-top: 20px; padding: 15px; background: #FFF3CD; border-radius: 10px;'>";
echo "<h3>⚙️ Herramientas de Base de Datos:</h3>";
echo "<form action='/tools/execute-sql.php' method='post' target='_blank' style='margin: 10px 0;'>";
echo "<button type='submit' style='padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
echo "🔄 Ejecutar Script SQL (crear tablas)";
echo "</button>";
echo "</form>";
echo "<p><small>💡 Ejecuta el script init.sql para crear todas las tablas necesarias</small></p>";
echo "</div>";
?>