<?php
// app/cart/add.php - Agregar producto al carrito (con validación de stock)

// ========== CONEXIÓN CENTRALIZADA ==========
require_once '/var/www/html/includes/connection.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'guest_' . uniqid();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? null;
    $productName = $_POST['product_name'] ?? "Producto $productId";
    $productPrice = $_POST['product_price'] ?? 0;
    $quantity = intval($_POST['quantity'] ?? 1);
    $redirect = $_POST['redirect'] ?? '/cart.php';
    
    if ($productId && $quantity > 0) {
        try {
            // ========== CONEXIÓN A REDIS ==========
            $redis = new Redis();
            $redisConnected = $redis->connect('redis-cache', 6379, 2);
            
            if (!$redisConnected) {
                throw new Exception("No se pudo conectar a Redis");
            }
            
            // 1. Verificar stock disponible usando $pdo (ya disponible desde connection.php)
            $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = :id");
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                header("Location: $redirect?message=Producto no encontrado en la base de datos&type=error");
                exit;
            }
            
            $stockDisponible = intval($product['stock']);
            
            // 2. Verificar cantidad en carrito (Redis)
            $cartKey = "cart:$userId";
            $cantidadEnCarrito = intval($redis->hGet($cartKey, $productId) ?? 0);
            
            $cantidadTotalSolicitada = $cantidadEnCarrito + $quantity;
            
            if ($cantidadTotalSolicitada > $stockDisponible) {
                $disponible = max(0, $stockDisponible - $cantidadEnCarrito);
                header("Location: $redirect?message=Stock insuficiente. Disponible para agregar: $disponible unidades&type=error");
                exit;
            }
            
            // 3. Obtener más información del producto para guardar en Redis
            $stmt = $pdo->prepare("SELECT nombre, descripcion, categoria, imagen_url, precio FROM productos WHERE id = :id");
            $stmt->execute([':id' => $productId]);
            $productInfo = $stmt->fetch();
            
            // Usar precio de la base de datos si está disponible (más confiable)
            $precioFinal = $productInfo['precio'] ?? (float)$productPrice;
            
            // 4. Guardar info del producto en Redis (si no existe o actualizar)
            $productKey = "product:$productId";
            $productData = [
                'id' => $productId,
                'name' => $productInfo['nombre'] ?? $productName,
                'descripcion' => $productInfo['descripcion'] ?? '',
                'categoria' => $productInfo['categoria'] ?? 'General',
                'price' => $precioFinal,
                'stock' => $stockDisponible,
                'imagen_url' => $productInfo['imagen_url'] ?? '📦',
                'added_at' => date('Y-m-d H:i:s')
            ];
            $redis->set($productKey, json_encode($productData));
            
            // 5. Agregar al carrito
            $newQuantity = $cantidadEnCarrito + $quantity;
            $redis->hSet($cartKey, $productId, $newQuantity);
            
            // 6. Actualizar stock temporal en Redis (copia de seguridad)
            $productData['stock'] = $stockDisponible - $newQuantity;
            $redis->set("product_temp:$productId", json_encode($productData), 3600); // Cache por 1 hora
            
            // 7. Incrementar contador global
            $redis->incr("stats:total_cart_operations");
            
            // 8. Registrar operación en log
            $operationLog = [
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'new_total' => $newQuantity,
                'stock_available' => $stockDisponible,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $redis->rpush("logs:cart_add", json_encode($operationLog));
            
            // 9. Redirigir con éxito
            $message = $quantity > 1 ? 
                "✅ $quantity unidades agregadas al carrito" : 
                "✅ Producto agregado al carrito";
            header("Location: $redirect?message=" . urlencode($message) . "&type=success");
            exit;
            
        } catch (PDOException $e) {
            error_log("Error PDO en add.php: " . $e->getMessage());
            header("Location: $redirect?message=Error de base de datos: " . urlencode($e->getMessage()) . "&type=error");
            exit;
        } catch (Exception $e) {
            error_log("Error en add.php: " . $e->getMessage());
            header("Location: $redirect?message=Error: " . urlencode($e->getMessage()) . "&type=error");
            exit;
        }
    } else {
        header("Location: $redirect?message=ID de producto no válido o cantidad incorrecta&type=error");
        exit;
    }
} else {
    header("Location: /cart.php?message=Método no permitido&type=error");
    exit;
}
?>