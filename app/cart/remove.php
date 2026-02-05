<?php
// app/cart/remove.php - Remover producto del carrito

// ========== CONEXIÓN CENTRALIZADA ==========
// Incluimos la conexión para mantener consistencia
require_once '/var/www/html/includes/connection.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /cart.php");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? null;
    $removeAll = isset($_POST['remove_all']);
    $redirect = $_POST['redirect'] ?? '/cart.php';
    
    if ($productId) {
        try {
            // ========== CONEXIÓN A REDIS ==========
            $redis = new Redis();
            $redisConnected = $redis->connect('redis-cache', 6379, 2);
            
            if (!$redisConnected) {
                throw new Exception("No se pudo conectar a Redis");
            }
            
            $cartKey = "cart:$userId";
            
            // Obtener información antes de remover (para logging)
            $currentQuantity = intval($redis->hGet($cartKey, $productId) ?? 0);
            
            if ($currentQuantity <= 0) {
                header("Location: $redirect?message=El producto no está en el carrito&type=info");
                exit;
            }
            
            // Obtener nombre del producto para el mensaje
            $productName = "Producto $productId";
            try {
                // Intentar obtener nombre de PostgreSQL
                $stmt = $pdo->prepare("SELECT nombre FROM productos WHERE id = :id");
                $stmt->execute([':id' => $productId]);
                $product = $stmt->fetch();
                if ($product && isset($product['nombre'])) {
                    $productName = $product['nombre'];
                }
            } catch (Exception $e) {
                // Fallback: intentar obtener de Redis
                $productData = json_decode($redis->get("product:$productId"), true);
                if ($productData && isset($productData['name'])) {
                    $productName = $productData['name'];
                }
            }
            
            if ($removeAll) {
                // Remover completamente el producto
                $redis->hDel($cartKey, $productId);
                $message = "🗑️ Producto '" . htmlspecialchars($productName) . "' removido del carrito";
                
                // Registrar log
                $removeLog = [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'action' => 'remove_all',
                    'quantity_removed' => $currentQuantity,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $redis->rpush("logs:cart_remove", json_encode($removeLog));
                
            } else {
                // Disminuir cantidad
                if ($currentQuantity > 1) {
                    $newQuantity = $currentQuantity - 1;
                    $redis->hSet($cartKey, $productId, $newQuantity);
                    $message = "➖ Cantidad disminuida de '" . htmlspecialchars($productName) . "' (ahora: $newQuantity)";
                    
                    // Registrar log
                    $removeLog = [
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'action' => 'decrease',
                        'old_quantity' => $currentQuantity,
                        'new_quantity' => $newQuantity,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    $redis->rpush("logs:cart_remove", json_encode($removeLog));
                    
                } else {
                    $redis->hDel($cartKey, $productId);
                    $message = "🗑️ Producto '" . htmlspecialchars($productName) . "' removido del carrito";
                    
                    // Registrar log
                    $removeLog = [
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'action' => 'remove_last',
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    $redis->rpush("logs:cart_remove", json_encode($removeLog));
                }
            }
            
            // Actualizar estadísticas
            $redis->incr("stats:cart_removals");
            
            header("Location: $redirect?message=" . urlencode($message) . "&type=success");
            exit;
            
        } catch (Exception $e) {
            error_log("Error en remove.php: " . $e->getMessage());
            header("Location: $redirect?message=Error: " . urlencode($e->getMessage()) . "&type=error");
            exit;
        }
    } else {
        header("Location: $redirect?message=ID de producto no válido&type=error");
        exit;
    }
} else {
    header("Location: /cart.php?message=Método no permitido&type=error");
    exit;
}
?>