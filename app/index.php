<?php
// app/index.php - App principal (requiere autenticación)
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/models/User.php';
require_once __DIR__ . '/utils/JWTService.php';

// Verificar autenticación
$token = $_SESSION['auth_token'] ?? $_COOKIE['auth_token'] ?? null;

if (!$token) {
    // No autenticado, redirigir a login
    header('Location: login.php');
    exit;
}

$userData = JWTService::getUserFromToken($token);
if (!$userData) {
    // Token inválido, redirigir a login
    header('Location: login.php');
    exit;
}

// Obtener información del usuario
$userModel = new User();
$currentUser = $userModel->getUserWithRole($userData['userId']);
if (!$currentUser) {
    // Usuario no existe, redirigir
    header('Location: login.php');
    exit;
}

// ========== Manejar logout ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    setcookie('auth_token', '', time() - 3600, '/');
    session_destroy();
    header('Location: login.php');
    exit;
}

// ========== INCLUIR CONEXIÓN CENTRALIZADA ==========
require_once '/var/www/html/includes/connection.php';

// ========== HTML COMENZANDO AQUÍ ==========
echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Dashboard - Mi App Docker</title>";
echo "<style>";
echo "  body { font-family: Arial, sans-serif; margin: 0; background: #f5f5f5; }";
echo "  .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }";
echo "  .user-info { background: white; padding: 15px; margin: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo "  .container { padding: 20px; }";
echo "  .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo "  .btn-logout { background: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }";
echo "  .btn-logout:hover { background: #d32f2f; }";
echo "  .role-badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }";
echo "  .role-admin { background: #ffebee; color: #c62828; }";
echo "  .role-user { background: #e8eaf6; color: #283593; }";
echo "  .role-manager { background: #e8f5e8; color: #2e7d32; }";
echo "</style>";
echo "</head>";
echo "<body>";

// ========== HEADER CON INFO DE USUARIO ==========
echo "<div class='header'>";
echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
echo "<h1>🚀 Dashboard - Mi App Docker</h1>";
echo "<form method='post' style='display: inline;'>";
echo "<input type='hidden' name='action' value='logout'>";
echo "<button type='submit' class='btn-logout'>🚪 Cerrar Sesión</button>";
echo "</form>";
echo "</div>";
echo "</div>";

// ========== INFORMACIÓN DEL USUARIO ==========
echo "<div class='user-info'>";
echo "<h2>👤 " . htmlspecialchars($currentUser['full_name']) . "</h2>";
echo "<p><strong>Usuario:</strong> " . htmlspecialchars($currentUser['username']) . "</p>";
echo "<p><strong>Email:</strong> " . htmlspecialchars($currentUser['email']) . "</p>";

$roleClass = 'role-' . $currentUser['role_name'];
echo "<p><strong>Rol:</strong> <span class='role-badge $roleClass'>" . htmlspecialchars($currentUser['role_name']) . "</span></p>";

echo "<p><small>Token JWT válido hasta: " . date('Y-m-d H:i:s', time() + 24*3600) . "</small></p>";
echo "</div>";

echo "<div class='container'>";

// ========== EL RESTO DE TU CÓDIGO ORIGINAL AQUÍ ==========
// (Copiamos todo tu contenido original desde aquí)
// ========== SECCIÓN REDIS ==========
echo "<div class='section'>";
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
echo "<div class='section'>";
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
echo "<div class='section'>";
echo "<h2 style='color: #2980b9;'>📊 Sistema</h2>";

echo "<p>🐋 Contenedores corriendo: <strong>5</strong></p>";
echo "<p>🔄 PHP Version: " . phpversion() . "</p>";
echo "<p>🔧 Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Apache') . "</p>";

// Info Redis
if (extension_loaded('redis')) {
    echo "<p>🧠 Extensión Redis: <span style='color: green;'>✅ Instalada</span></p>";
} else {
    echo "<p>�� Extensión Redis: <span style='color: red;'>❌ No disponible</span></p>";
}

echo "<p>💾 Memoria usada: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB</p>";

// Info PostgreSQL PDO
echo "<p>🗄️ PDO PostgreSQL: <span style='color: green;'>✅ Disponible</span></p>";
echo "<p>🔗 Conexión DB: " . getenv('DB_HOST') . "/" . getenv('DB_NAME') . "</p>";
echo "</div>";

// ========== ENLACES RÁPIDOS ==========
echo "<div class='section'>";
echo "<h3>🔗 Accesos Rápidos:</h3>";
echo "<ul>";
echo "<li><a href='http://localhost:8080' target='_blank'>�� Esta App (puerto 8080)</a></li>";
echo "<li><a href='http://localhost:8080/test-connection.php' target='_blank'>🔌 Probar Conexión DB</a></li>";
echo "<li><a href='http://localhost:8080/cart.php' target='_blank'>🛒 Carrito de Compras</a></li>";
echo "<li><a href='http://localhost:8081' target='_blank'>🐘 pgAdmin - Admin PostgreSQL</a></li>";
echo "<li><a href='http://localhost:8082' target='_blank'>🧠 Redis Admin - Dashboard Redis</a></li>";
echo "<li><a href='http://localhost:5432' target='_blank'>🗄️ PostgreSQL directo (puerto 5432)</a></li>";
echo "<li><a href='http://localhost:6379' target='_blank'>⚡ Redis directo (puerto 6379)</a></li>";
echo "</ul>";
echo "</div>";

echo "</div>"; // Cierre del container
echo "</body>";
echo "</html>";
