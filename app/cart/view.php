<?php
// app/cart/clear.php - Vaciar carrito completo

// ========== CONEXIÓN CENTRALIZADA ==========
// Nota: Aunque clear.php no usa PostgreSQL directamente,
// incluimos la conexión para mantener consistencia
require_once '/var/www/html/includes/connection.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /cart.php");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = $_POST['redirect'] ?? '/cart.php';
    
    try {
        // ========== CONEXIÓN A REDIS ==========
        $redis = new Redis();
        $redisConnected = $redis->connect('redis-cache', 6379, 2);
        
        if (!$redisConnected) {
            throw new Exception("No se pudo conectar a Redis");
        }
        
        $cartKey = "cart:$userId";
        
        // Obtener información del carrito antes de vaciar (para logging)
        $cartItems = $redis->hGetAll($cartKey);
        $itemCount = count($cartItems);
        $totalItems = array_sum($cartItems);
        
        // Eliminar carrito
        $deleted = $redis->del($cartKey);
        
        if ($deleted > 0) {
            // Registrar estadísticas
            $redis->incr("stats:cart_cleared");
            
            // Registrar log de la operación
            $clearLog = [
                'user_id' => $userId,
                'items_removed' => $itemCount,
                'total_quantity' => $totalItems,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            $redis->rpush("logs:cart_clear", json_encode($clearLog));
            
            // Opcional: También eliminar datos de productos en cache si ya no se usan
            foreach (array_keys($cartItems) as $productId) {
                $redis->del("product_temp:$productId");
            }
            
            $message = "✅ Carrito vaciado correctamente";
            if ($itemCount > 0) {
                $message .= " (eliminados $itemCount productos, $totalItems unidades)";
            }
            
            header("Location: $redirect?message=" . urlencode($message) . "&type=success");
        } else {
            header("Location: $redirect?message=El carrito ya estaba vacío&type=info");
        }
        exit;
        
    } catch (Exception $e) {
        error_log("Error en clear.php: " . $e->getMessage());
        header("Location: $redirect?message=Error: " . urlencode($e->getMessage()) . "&type=error");
        exit;
    }
} else {
    header("Location: /cart.php?message=Método no permitido&type=error");
    exit;
}
?>