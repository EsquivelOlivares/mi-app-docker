<?php
// app/cart.php - Página principal del carrito (actualizada para JWT)

// ========== CONEXIÓN CENTRALIZADA ==========
require_once '/var/www/html/includes/connection.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/models/User.php';
require_once __DIR__ . '/utils/JWTService.php';

session_start();

// ========== AUTENTICACIÓN JWT ==========
$token = $_SESSION['auth_token'] ?? $_COOKIE['auth_token'] ?? null;
$userModel = new User();

if ($token) {
    // Usuario autenticado con JWT
    $userData = JWTService::getUserFromToken($token);
    if ($userData) {
        $user = $userModel->getUserWithRole($userData['userId']);
        if ($user) {
            $userId = $user['id'];
            $userName = $user['full_name'] ?: $user['username'];
            $isAuthenticated = true;
        } else {
            // Token válido pero usuario no existe en BD
            $userId = 'guest_' . uniqid();
            $userName = 'Invitado (Usuario no encontrado)';
            $isAuthenticated = false;
        }
    } else {
        // Token inválido
        $userId = 'guest_' . uniqid();
        $userName = 'Invitado';
        $isAuthenticated = false;
    }
} else {
    // No hay token, usuario invitado
    $userId = 'guest_' . uniqid();
    $userName = 'Invitado';
    $isAuthenticated = false;
}

// Conexión a Redis
$redis = new Redis();
$redisConnected = $redis->connect('redis-cache', 6379, 2);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛒 Carrito de Compras</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .auth-status {
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.9em;
            margin-bottom: 10px;
            display: inline-block;
        }
        
        .auth-authenticated {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .auth-guest {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .nav-links .home {
            background: #4CAF50;
            color: white;
        }
        
        .nav-links .cart {
            background: #2196F3;
            color: white;
        }
        
        .nav-links .products {
            background: #FF9800;
            color: white;
        }
        
        .nav-links .login {
            background: #9C27B0;
            color: white;
        }
        
        .nav-links .logout {
            background: #f44336;
            color: white;
        }
        
        .nav-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        /* ... [EL RESTO DE TU CSS SE MANTIENE IGUAL] ... */
        
        .connection-status {
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .status-connected {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🛒 Carrito de Compras</h1>
            
            <!-- Estado de autenticación -->
            <div class="auth-status <?php echo $isAuthenticated ? 'auth-authenticated' : 'auth-guest'; ?>">
                <?php if ($isAuthenticated): ?>
                    ✅ Autenticado con JWT
                <?php else: ?>
                    🔓 Modo Invitado - <a href="/login.php" style="color: #856404; font-weight: bold;">Iniciar Sesión</a>
                <?php endif; ?>
            </div>
            
            <!-- Estado de conexiones -->
            <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                <?php if ($pdo): ?>
                <div class="connection-status status-connected">
                    ✅ PostgreSQL: <?php echo getenv('DB_NAME'); ?>
                </div>
                <?php else: ?>
                <div class="connection-status status-error">
                    ❌ PostgreSQL: Error de conexión
                </div>
                <?php endif; ?>
                
                <?php if ($redisConnected): ?>
                <div class="connection-status status-connected">
                    ✅ Redis: Conectado
                </div>
                <?php else: ?>
                <div class="connection-status status-error">
                    ❌ Redis: No disponible
                </div>
                <?php endif; ?>
            </div>
            
            <div class="user-info">
                <p>👤 Usuario: <strong><?php echo htmlspecialchars($userName); ?></strong></p>
                <p>🆔 ID: <code><?php echo htmlspecialchars($userId); ?></code></p>
                <?php if ($isAuthenticated && isset($user['role_name'])): ?>
                    <p>🎭 Rol: <strong><?php echo htmlspecialchars($user['role_name']); ?></strong></p>
                <?php endif; ?>
            </div>
            
            <div class="nav-links">
                <a href="/" class="home">🏠 Inicio</a>
                <a href="/cart.php" class="cart">🛒 Mi Carrito</a>
                <a href="/cart.php?action=products" class="products">📦 Productos</a>
                <?php if ($isAuthenticated): ?>
                    <a href="/login.php?action=logout" class="logout">🚪 Cerrar Sesión</a>
                <?php else: ?>
                    <a href="/login.php" class="login">🔑 Iniciar Sesión</a>
                <?php endif; ?>
                <a href="/test-connection.php" style="background: #607D8B; color: white;">🔌 Test DB</a>
            </div>
        </header>
        
        <?php if (isset($_GET['message'])): ?>
            <div class="message <?php echo $_GET['type'] ?? 'success'; ?>">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Nota para usuarios invitados -->
        <?php if (!$isAuthenticated): ?>
            <div style="background: #fff3cd; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                <p>🔓 <strong>Modo Invitado:</strong> Tu carrito se guardará temporalmente, pero si cierras el navegador se perderá.</p>
                <p>👉 <a href="/login.php" style="color: #856404; font-weight: bold;">Inicia sesión</a> para guardar tu carrito permanentemente y acceder a más funciones.</p>
            </div>
        <?php endif; ?>
        
        <div class="content">
            <!-- COLUMNA IZQUIERDA: Productos o Carrito -->
            <div class="card">
                <?php if (isset($_GET['action']) && $_GET['action'] == 'products'): ?>
                    <!-- LISTA DE PRODUCTOS DESDE POSTGRESQL -->
                    <h2>📦 Productos Disponibles</h2>
                    
                    <?php
                    try {
                        // Obtener productos de la base de datos usando $pdo
                        $stmt = $pdo->query("
                            SELECT id, nombre, descripcion, precio, categoria, stock, imagen_url 
                            FROM productos 
                            ORDER BY categoria, nombre
                        ");
                        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($products)) {
                            echo "<div class='empty-cart'>";
                            echo "<div style='font-size: 4em;'>📦</div>";
                            echo "<h3>No hay productos disponibles</h3>";
                            echo "<p>La tabla 'productos' está vacía o no existe.</p>";
                            echo "<p><a href='/test-connection.php' style='color: #2196F3;'>🔧 Verificar conexión</a></p>";
                            echo "</div>";
                        } else {
                            // Mostrar productos por categoría
                            $categorias = [];
                            foreach ($products as $product) {
                                $categoria = $product['categoria'] ?? 'General';
                                $categorias[$categoria][] = $product;
                            }
                            
                            foreach ($categorias as $categoria => $productosCategoria):
                            ?>
                                <h3 class="category-header">
                                    <?php echo htmlspecialchars($categoria); ?>
                                </h3>
                                
                                <div class="product-list">
                                    <?php foreach ($productosCategoria as $product): ?>
                                    <div class="product-card">
                                        <div style="font-size: 2em; text-align: center; margin-bottom: 10px;">
                                            <?php echo $product['imagen_url'] ?? '📦'; ?>
                                        </div>
                                        <h3><?php echo htmlspecialchars($product['nombre']); ?></h3>
                                        
                                        <?php if (!empty($product['descripcion'])): ?>
                                            <p style="color: #666; font-size: 0.9em; margin: 10px 0;">
                                                <?php echo htmlspecialchars($product['descripcion']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="price">$<?php echo number_format($product['precio'], 2); ?></div>
                                        
                                        <div class="stock-info">
                                            📦 Stock: <?php echo $product['stock']; ?> unidades
                                        </div>
                                        
                                        <form action="/app/cart/add.php" method="POST" style="margin-top: 10px;">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['nombre']); ?>">
                                            <input type="hidden" name="product_price" value="<?php echo $product['precio']; ?>">
                                            <input type="hidden" name="redirect" value="/cart.php">
                                            
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                                <label style="font-size: 0.9em;">Cantidad:</label>
                                                <input type="number" name="quantity" value="1" min="1" 
                                                       max="<?php echo $product['stock']; ?>" 
                                                       class="quantity-input">
                                            </div>
                                            
                                            <button type="submit" class="add-to-cart" 
                                                    <?php echo ($product['stock'] <= 0) ? 'disabled style="background: #ccc;"' : ''; ?>>
                                                <?php echo ($product['stock'] > 0) ? '➕ Agregar al Carrito' : '⛔ Sin Stock'; ?>
                                            </button>
                                        </form>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php 
                            endforeach;
                        }
                        
                    } catch (PDOException $e) {
                        echo "<div class='error message'>";
                        echo "❌ Error al cargar productos: " . htmlspecialchars($e->getMessage());
                        echo "<p><strong>Tabla 'productos' no encontrada.</strong></p>";
                        echo "<p>Ejecuta: <code>docker exec -i postgres-db psql -U postgres -d mi_app -f init.sql</code></p>";
                        echo "</div>";
                        
                        // Mostrar botón para ejecutar script
                        echo '<div style="text-align: center; margin-top: 20px;">';
                        echo '<a href="/tools/execute-sql.php" target="_blank" style="padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">';
                        echo '🔄 Ejecutar Script SQL';
                        echo '</a>';
                        echo '</div>';
                    }
                    ?>
                    
                <?php else: ?>
                    <!-- CARRITO ACTUAL -->
                    <h2>🛒 Mi Carrito</h2>
                    
                    <?php if ($redisConnected): ?>
                        <?php
                        // Obtener carrito de Redis
                        $cartKey = "cart:$userId";
                        $cartItems = $redis->hGetAll($cartKey);
                        
                        if (empty($cartItems)):
                        ?>
                            <div class="empty-cart">
                                <div style="font-size: 4em;">🛒</div>
                                <h3>Tu carrito está vacío</h3>
                                <p>¡Agrega algunos productos para comenzar!</p>
                                <a href="/cart.php?action=products" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px;">
                                    📦 Ver Productos
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="cart-items">
                                <?php
                                $total = 0;
                                $hasStockIssues = false;
                                
                                foreach ($cartItems as $productId => $quantity):
                                    // Intentar obtener datos de PostgreSQL primero
                                    $productData = null;
                                    try {
                                        $stmt = $pdo->prepare("SELECT nombre, precio, stock FROM productos WHERE id = :id");
                                        $stmt->execute([':id' => $productId]);
                                        $productData = $stmt->fetch(PDO::FETCH_ASSOC);
                                    } catch (PDOException $e) {
                                        // Si falla, usar Redis como fallback
                                        $productData = json_decode($redis->get("product:$productId"), true);
                                    }
                                    
                                    $productName = $productData['nombre'] ?? $productData['name'] ?? "Producto $productId";
                                    $productPrice = $productData['precio'] ?? $productData['price'] ?? 0;
                                    $productStock = $productData['stock'] ?? $productData['stock'] ?? 999;
                                    $itemTotal = $productPrice * $quantity;
                                    $total += $itemTotal;
                                    
                                    // Verificar stock
                                    $stockOk = ($quantity <= $productStock);
                                    if (!$stockOk) $hasStockIssues = true;
                                ?>
                                <div class="cart-item" style="<?php echo !$stockOk ? 'background: #fff8e1;' : ''; ?>">
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($productName); ?></h4>
                                        <p>Precio: $<?php echo number_format($productPrice, 2); ?></p>
                                        <?php if (!$stockOk): ?>
                                            <p style="color: #f44336; font-size: 0.9em;">
                                                ⚠️ Stock máximo: <?php echo $productStock; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="item-actions">
                                        <span>Cantidad: <?php echo $quantity; ?></span>
                                        <form action="/app/cart/add.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <input type="hidden" name="redirect" value="/cart.php">
                                            <button type="submit" class="quantity-btn" title="Aumentar" 
                                                    <?php echo ($quantity >= $productStock) ? 'disabled' : ''; ?>>+</button>
                                        </form>
                                        <form action="/app/cart/remove.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                            <input type="hidden" name="redirect" value="/cart.php">
                                            <button type="submit" class="quantity-btn" title="Disminuir">-</button>
                                        </form>
                                        <form action="/app/cart/remove.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                            <input type="hidden" name="remove_all" value="1">
                                            <input type="hidden" name="redirect" value="/cart.php">
                                            <button type="submit" class="remove-btn" title="Eliminar">✕</button>
                                        </form>
                                    </div>
                                    
                                    <div class="item-total">
                                        <strong>$<?php echo number_format($itemTotal, 2); ?></strong>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="cart-total">
                                <h3>Total del Carrito</h3>
                                <p style="font-size: 1.5em; color: #4CAF50;">
                                    <strong>$<?php echo number_format($total, 2); ?></strong>
                                </p>
                                
                                <?php if ($hasStockIssues): ?>
                                    <div style="background: #fff8e1; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                                        ⚠️ Algunos productos exceden el stock disponible.
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($isAuthenticated): ?>
                                    <form action="/app/cart/checkout.php" method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                        <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($userName); ?>">
                                        <button type="submit" class="checkout-btn" <?php echo $hasStockIssues ? 'disabled style="background: #ccc;"' : ''; ?>>
                                            🛍️ Proceder al Pago (Autenticado)
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div style="background: #e3f2fd; padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 15px;">
                                        <p>🔐 <strong>Para proceder al pago, necesitas estar autenticado.</strong></p>
                                        <a href="/login.php" style="display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;">
                                            🔑 Iniciar Sesión
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <form action="/app/cart/clear.php" method="POST" style="margin-top: 10px;">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                    <input type="hidden" name="redirect" value="/cart.php">
                                    <button type="submit" style="background: #ff4444; color: white; border: none; padding: 10px; border-radius: 5px; width: 100%; cursor: pointer;">
                                        🗑️ Vaciar Carrito
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="error message">
                            <p>⚠️ Redis no disponible. El carrito no puede funcionar sin Redis.</p>
                            <p>Verifica que el servicio Redis esté corriendo.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- COLUMNA DERECHA: Información y stats -->
            <div class="card">
                <h2>📊 Estadísticas</h2>
                
                <?php if ($redisConnected): ?>
                    <?php
                    // Estadísticas del carrito
                    $cartKey = "cart:$userId";
                    $itemCount = $redis->hLen($cartKey);
                    $totalItems = array_sum($redis->hGetAll($cartKey));
                    
                    // Estadísticas generales de Redis
                    $redisInfo = $redis->info();
                    ?>
                    
                    <div style="margin-bottom: 20px;">
                        <h3>Tu Carrito</h3>
                        <p>📦 Productos diferentes: <strong><?php echo $itemCount; ?></strong></p>
                        <p>🔢 Total de items: <strong><?php echo $totalItems; ?></strong></p>
                        <p>👤 Estado: <strong><?php echo $isAuthenticated ? '✅ Autenticado' : '🔓 Invitado'; ?></strong></p>
                    </div>
                    
                    <!-- ... [EL RESTO DEL CÓDIGO SE MANTIENE IGUAL] ... -->
                    
                <?php else: ?>
                    <p>Redis no está disponible para mostrar estadísticas.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <footer style="text-align: center; margin-top: 30px; color: white; padding: 20px;">
            <p>🛒 Carrito de Compras usando <strong>Redis</strong> + <strong>PostgreSQL</strong> + <strong>JWT Auth</strong></p>
            <p>🔐 Sistema de autenticación JWT integrado</p>
        </footer>
    </div>
</body>
</html>
