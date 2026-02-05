<?php
// app/cart/checkout.php - Procesar compra (Redis → PostgreSQL)

// ========== CONEXIÓN CENTRALIZADA ==========
require_once '/var/www/html/includes/connection.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /cart.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Invitado';

try {
    // ========== CONEXIÓN A REDIS ==========
    $redis = new Redis();
    $redisConnected = $redis->connect('redis-cache', 6379, 2);
    
    if (!$redisConnected) {
        throw new Exception("No se pudo conectar a Redis");
    }
    
    $cartKey = "cart:$userId";
    $cartItems = $redis->hGetAll($cartKey);
    
    if (empty($cartItems)) {
        header("Location: /cart.php?message=El carrito está vacío&type=error");
        exit;
    }
    
    // $pdo ya está disponible desde includes/connection.php
    
    // ========== VERIFICAR STOCK ANTES DE PROCEDER ==========
    $pdo->beginTransaction();
    
    $total = 0;
    $orderItems = [];
    $hasStockIssue = false;
    
    foreach ($cartItems as $productId => $quantity) {
        // Verificar stock en PostgreSQL usando $pdo
        $stmt = $pdo->prepare("SELECT stock, nombre, precio FROM productos WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $hasStockIssue = true;
            $pdo->rollBack();
            header("Location: /cart.php?message=Producto ID $productId ya no existe&type=error");
            exit;
        }
        
        if ($quantity > $product['stock']) {
            $hasStockIssue = true;
            $pdo->rollBack();
            $disponible = $product['stock'];
            header("Location: /cart.php?message=Stock insuficiente para " . $product['nombre'] . ". Disponible: $disponible unidades&type=error");
            exit;
        }
        
        $price = $product['precio'];
        $subtotal = $price * $quantity;
        $total += $subtotal;
        
        $orderItems[] = [
            'product_id' => $productId,
            'product_name' => $product['nombre'],
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
    
    // ========== CREAR TABLAS SI NO EXISTEN ==========
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ordenes (
                id SERIAL PRIMARY KEY,
                user_id VARCHAR(100),
                user_name VARCHAR(100),
                total DECIMAL(10, 2),
                estado VARCHAR(20) DEFAULT 'completada',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS orden_items (
                id SERIAL PRIMARY KEY,
                orden_id INTEGER REFERENCES ordenes(id) ON DELETE CASCADE,
                product_id INTEGER,
                product_name VARCHAR(255),
                price DECIMAL(10, 2),
                quantity INTEGER,
                subtotal DECIMAL(10, 2)
            )
        ");
    } catch (PDOException $e) {
        // Si ya existen las tablas, continuar normalmente
        error_log("Info: Las tablas ya existen - " . $e->getMessage());
    }
    
    // ========== INSERTAR ORDEN PRINCIPAL ==========
    $stmt = $pdo->prepare("
        INSERT INTO ordenes (user_id, user_name, total, estado) 
        VALUES (:user_id, :user_name, :total, 'completada')
        RETURNING id
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':user_name' => $userName,
        ':total' => $total
    ]);
    $ordenId = $stmt->fetchColumn();
    
    // ========== INSERTAR ITEMS Y ACTUALIZAR STOCK ==========
    $stmtItem = $pdo->prepare("
        INSERT INTO orden_items (orden_id, product_id, product_name, price, quantity, subtotal)
        VALUES (:orden_id, :product_id, :product_name, :price, :quantity, :subtotal)
    ");
    
    $stmtUpdateStock = $pdo->prepare("
        UPDATE productos SET stock = stock - :quantity WHERE id = :product_id
    ");
    
    foreach ($orderItems as $item) {
        // Insertar item de orden
        $stmtItem->execute([
            ':orden_id' => $ordenId,
            ':product_id' => $item['product_id'],
            ':product_name' => $item['product_name'],
            ':price' => $item['price'],
            ':quantity' => $item['quantity'],
            ':subtotal' => $item['subtotal']
        ]);
        
        // Actualizar stock en productos
        $stmtUpdateStock->execute([
            ':quantity' => $item['quantity'],
            ':product_id' => $item['product_id']
        ]);
    }
    
    $pdo->commit();
    
    // ========== LIMPIAR CARRITO EN REDIS ==========
    $redis->del($cartKey);
    
    // ========== REGISTRAR ESTADÍSTICAS ==========
    $redis->incr("stats:total_checkouts");
    $redis->incrBy("stats:total_revenue", intval($total * 100)); // En centavos
    
    // Guardar resumen de la orden en Redis por 24h
    $orderSummary = [
        'order_id' => $ordenId,
        'user_id' => $userId,
        'total' => $total,
        'items_count' => count($orderItems),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    $redis->setex("order:$ordenId:summary", 86400, json_encode($orderSummary));
    
    // ========== MOSTRAR CONFIRMACIÓN ==========
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>✅ Compra Exitosa</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #4CAF50, #45a049);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            
            .success-card {
                background: white;
                border-radius: 15px;
                padding: 40px;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                text-align: center;
            }
            
            .success-icon {
                font-size: 4em;
                color: #4CAF50;
                margin-bottom: 20px;
            }
            
            h1 {
                color: #333;
                margin-bottom: 20px;
            }
            
            .order-info {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                text-align: left;
            }
            
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: #2196F3;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px;
                font-weight: bold;
                transition: transform 0.3s;
            }
            
            .btn:hover {
                transform: translateY(-2px);
            }
            
            .btn-home {
                background: #4CAF50;
            }
            
            .btn-cart {
                background: #FF9800;
            }
            
            .items-list {
                margin: 15px 0;
                max-height: 200px;
                overflow-y: auto;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 10px;
            }
            
            .item-row {
                display: flex;
                justify-content: space-between;
                padding: 5px 0;
                border-bottom: 1px solid #eee;
            }
            
            .item-row:last-child {
                border-bottom: none;
            }
            
            .tech-info {
                margin-top: 30px;
                padding: 15px;
                background: #e8f5e9;
                border-radius: 8px;
                text-align: left;
                font-size: 0.9em;
            }
            
            .tech-info ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            
            .tech-info li {
                margin-bottom: 5px;
            }
        </style>
    </head>
    <body>
        <div class="success-card">
            <div class="success-icon">✅</div>
            <h1>¡Compra Exitosa!</h1>
            <p>Gracias por tu compra, <strong><?php echo htmlspecialchars($userName); ?></strong>.</p>
            
            <div class="order-info">
                <h3>📋 Detalles de la Orden #<?php echo $ordenId; ?></h3>
                <p><strong>ID de Usuario:</strong> <?php echo htmlspecialchars($userId); ?></p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                <p><strong>Total:</strong> $<?php echo number_format($total, 2); ?></p>
                <p><strong>Items comprados:</strong> <?php echo count($orderItems); ?></p>
                
                <div class="items-list">
                    <?php foreach ($orderItems as $item): ?>
                    <div class="item-row">
                        <span><?php echo htmlspecialchars($item['product_name']); ?> x<?php echo $item['quantity']; ?></span>
                        <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="tech-info">
                <p><strong>💡 Proceso técnico completado:</strong></p>
                <ul>
                    <li>✅ <strong>Redis → PostgreSQL:</strong> Carrito temporal → Orden permanente</li>
                    <li>✅ <strong>Transacción ACID:</strong> Todo o nada (commit/rollback)</li>
                    <li>✅ <strong>Stock Management:</strong> Validación y actualización automática</li>
                    <li>✅ <strong>ON DELETE CASCADE:</strong> Orden_items se elimina con orden</li>
                    <li>✅ <strong>Cache:</strong> Resumen guardado en Redis por 24h</li>
                </ul>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="/" class="btn btn-home">🏠 Volver al Inicio</a>
                <a href="/cart.php" class="btn btn-cart">🛒 Ver Carrito</a>
                <a href="http://localhost:8081" target="_blank" class="btn">🗄️ Ver en pgAdmin</a>
                <a href="/test-connection.php" target="_blank" class="btn" style="background: #9C27B0;">🔌 Ver Conexiones</a>
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background: #fff8e1; border-radius: 8px; font-size: 0.9em;">
                <p><strong>🔧 Configuración usada:</strong></p>
                <p>PostgreSQL: <?php echo getenv('DB_HOST'); ?>/<?php echo getenv('DB_NAME'); ?></p>
                <p>Redis: redis-cache:6379</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    // Error handling
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
        } catch (Exception $rollbackError) {
            error_log("Error en rollback: " . $rollbackError->getMessage());
        }
    }
    
    // Log del error
    error_log("Error en checkout.php: " . $e->getMessage());
    
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>❌ Error en Checkout</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 40px;
                text-align: center;
                background: #ffebee;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .error {
                color: #d32f2f;
                background: white;
                padding: 30px;
                border-radius: 10px;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            .btn {
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                background: #2196F3;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 5px;
            }
            .error-details {
                background: #f5f5f5;
                padding: 15px;
                border-radius: 5px;
                margin: 15px 0;
                text-align: left;
                font-family: monospace;
                font-size: 0.9em;
                overflow-x: auto;
            }
        </style>
    </head>
    <body>
        <div class="error">
            <h1>❌ Error en el Checkout</h1>
            <p>Ocurrió un error al procesar tu compra.</p>
            
            <div class="error-details">
                <strong>Mensaje:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
            </div>
            
            <p>Por favor, intenta nuevamente o contacta al soporte.</p>
            
            <div>
                <a href="/cart.php" class="btn">← Volver al Carrito</a>
                <a href="/" class="btn" style="background: #4CAF50;">🏠 Ir al Inicio</a>
                <a href="/test-connection.php" class="btn" style="background: #9C27B0;">🔌 Verificar Conexión</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>