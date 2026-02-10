<?php
// app/cart.php - Carrito con Bootstrap
session_start();
require_once '/var/www/html/includes/connection.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/models/User.php';
require_once __DIR__ . '/utils/JWTService.php';

// ========== AUTENTICACIÓN JWT ==========
$token = $_SESSION['auth_token'] ?? $_COOKIE['auth_token'] ?? null;
$userModel = new User();

if ($token) {
    $userData = JWTService::getUserFromToken($token);
    if ($userData) {
        $user = $userModel->getUserWithRole($userData['userId']);
        if ($user) {
            $userId = $user['id'];
            $userName = $user['full_name'] ?: $user['username'];
            $isAuthenticated = true;
            $currentUser = $user;
        } else {
            $userId = 'guest_' . uniqid();
            $userName = 'Invitado (Usuario no encontrado)';
            $isAuthenticated = false;
            $currentUser = null;
        }
    } else {
        $userId = 'guest_' . uniqid();
        $userName = 'Invitado';
        $isAuthenticated = false;
        $currentUser = null;
    }
} else {
    $userId = 'guest_' . uniqid();
    $userName = 'Invitado';
    $isAuthenticated = false;
    $currentUser = null;
}

// Conexión a Redis
$redis = new Redis();
$redisConnected = $redis->connect('redis-cache', 6379, 2);

// Configuración para el layout
$pageTitle = "Carrito de Compras - Mi App Docker";

// Capturar el contenido
ob_start();
?>

<!-- Header del Carrito -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-shopping-cart fa-2x text-white"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-1">🛒 Carrito de Compras</h4>
                        <p class="text-muted mb-0">Gestión de productos con Redis + PostgreSQL</p>
                        <div class="mt-2">
                            <span class="badge <?php echo $isAuthenticated ? 'bg-success' : 'bg-warning'; ?> me-2">
                                <i class="fas fa-user me-1"></i><?php echo $isAuthenticated ? 'Autenticado' : 'Invitado'; ?>
                            </span>
                            <span class="badge bg-info">
                                <i class="fas fa-id-card me-1"></i>ID: <?php echo htmlspecialchars($userId); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h6 class="text-muted mb-3">Estado de conexiones</h6>
                <div class="d-grid gap-2">
                    <span class="badge <?php echo $pdo ? 'bg-success' : 'bg-danger'; ?> p-2">
                        <i class="fas fa-database me-1"></i>PostgreSQL: <?php echo $pdo ? '✅' : '❌'; ?>
                    </span>
                    <span class="badge <?php echo $redisConnected ? 'bg-success' : 'bg-danger'; ?> p-2">
                        <i class="fas fa-bolt me-1"></i>Redis: <?php echo $redisConnected ? '✅' : '❌'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Nota para invitados -->
<?php if (!$isAuthenticated): ?>
<div class="alert alert-warning alert-dismissible fade show">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Modo Invitado:</strong> Tu carrito se guardará temporalmente en esta sesión.
    <a href="/login.php" class="alert-link fw-bold">Inicia sesión</a> para guardarlo permanentemente.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Mensajes flash -->
<?php if (isset($_GET['message'])): ?>
<div class="alert alert-<?php echo $_GET['type'] ?? 'success'; ?> alert-dismissible fade show">
    <?php echo htmlspecialchars($_GET['message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Contenido principal -->
    <div class="col-lg-8">
        <?php if (isset($_GET['action']) && $_GET['action'] == 'products'): ?>
        
        <!-- LISTA DE PRODUCTOS -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Productos Disponibles</h5>
                <a href="/cart.php" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left me-1"></i>Volver al Carrito
                </a>
            </div>
            <div class="card-body">
                <?php
                try {
                    $stmt = $pdo->query("
                        SELECT id, nombre, descripcion, precio, categoria, stock, imagen_url 
                        FROM productos 
                        ORDER BY categoria, nombre
                    ");
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($products)):
                ?>
                <div class="text-center py-5">
                    <div class="display-1 text-muted mb-3">📦</div>
                    <h4>No hay productos disponibles</h4>
                    <p class="text-muted">La tabla 'productos' está vacía o no existe.</p>
                    <a href="/test-connection.php" class="btn btn-outline-primary">
                        <i class="fas fa-plug me-2"></i>Verificar conexión
                    </a>
                </div>
                <?php
                    else:
                        $categorias = [];
                        foreach ($products as $product) {
                            $categoria = $product['categoria'] ?? 'General';
                            $categorias[$categoria][] = $product;
                        }
                        
                        foreach ($categorias as $categoria => $productosCategoria):
                ?>
                <h5 class="mt-4 mb-3 border-bottom pb-2">
                    <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($categoria); ?>
                </h5>
                
                <div class="row">
                    <?php foreach ($productosCategoria as $product): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 product-card">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="display-4"><?php echo $product['imagen_url'] ?? '📦'; ?></div>
                                </div>
                                
                                <h6 class="card-title"><?php echo htmlspecialchars($product['nombre']); ?></h6>
                                
                                <?php if (!empty($product['descripcion'])): ?>
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars(substr($product['descripcion'], 0, 80)); ?>
                                    <?php if (strlen($product['descripcion']) > 80): ?>...<?php endif; ?>
                                </p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <span class="h5 text-success">$<?php echo number_format($product['precio'], 2); ?></span>
                                        <div class="small text-muted">
                                            <i class="fas fa-box me-1"></i>Stock: <?php echo $product['stock']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <form action="/cart/add.php" method="POST" class="mt-3">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['nombre']); ?>">
                                    <input type="hidden" name="product_price" value="<?php echo $product['precio']; ?>">
                                    <input type="hidden" name="redirect" value="/cart.php">
                                    
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">Cantidad</span>
                                        <input type="number" name="quantity" value="1" min="1" 
                                               max="<?php echo $product['stock']; ?>" 
                                               class="form-control">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100" 
                                            <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-cart-plus me-2"></i>
                                        <?php echo ($product['stock'] > 0) ? 'Agregar al Carrito' : 'Sin Stock'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php
                        endforeach;
                    endif;
                    
                } catch (PDOException $e) {
                ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Error al cargar productos:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
                    <p class="mb-0 mt-2"><strong>Tabla 'productos' no encontrada.</strong></p>
                </div>
                
                <div class="text-center mt-3">
                    <a href="/tools/execute-sql.php" target="_blank" class="btn btn-success">
                        <i class="fas fa-redo me-2"></i>Ejecutar Script SQL
                    </a>
                </div>
                <?php
                }
                ?>
            </div>
        </div>
        
        <?php else: ?>
        
        <!-- CARRITO ACTUAL -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Mi Carrito</h5>
                <a href="/cart.php?action=products" class="btn btn-sm btn-light">
                    <i class="fas fa-plus me-1"></i>Agregar Productos
                </a>
            </div>
            <div class="card-body">
                <?php if ($redisConnected): ?>
                    <?php
                    $cartKey = "cart:$userId";
                    $cartItems = $redis->hGetAll($cartKey);
                    
                    if (empty($cartItems)):
                    ?>
                    <div class="text-center py-5">
                        <div class="display-1 text-muted mb-3">🛒</div>
                        <h4>Tu carrito está vacío</h4>
                        <p class="text-muted">¡Agrega algunos productos para comenzar!</p>
                        <a href="/cart.php?action=products" class="btn btn-primary btn-lg">
                            <i class="fas fa-boxes me-2"></i>Ver Productos
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Precio</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-center">Subtotal</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total = 0;
                                $hasStockIssues = false;
                                
                                foreach ($cartItems as $productId => $quantity):
                                    $productData = null;
                                    try {
                                        $stmt = $pdo->prepare("SELECT nombre, precio, stock FROM productos WHERE id = :id");
                                        $stmt->execute([':id' => $productId]);
                                        $productData = $stmt->fetch(PDO::FETCH_ASSOC);
                                    } catch (PDOException $e) {
                                        $productData = json_decode($redis->get("product:$productId"), true);
                                    }
                                    
                                    $productName = $productData['nombre'] ?? $productData['name'] ?? "Producto $productId";
                                    $productPrice = $productData['precio'] ?? $productData['price'] ?? 0;
                                    $productStock = $productData['stock'] ?? $productData['stock'] ?? 999;
                                    $itemTotal = $productPrice * $quantity;
                                    $total += $itemTotal;
                                    
                                    $stockOk = ($quantity <= $productStock);
                                    if (!$stockOk) $hasStockIssues = true;
                                ?>
                                <tr <?php echo !$stockOk ? 'class="table-warning"' : ''; ?>>
                                    <td>
                                        <strong><?php echo htmlspecialchars($productName); ?></strong>
                                        <?php if (!$stockOk): ?>
                                        <div class="small text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Stock máximo: <?php echo $productStock; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">$<?php echo number_format($productPrice, 2); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <form action="/cart/remove.php" method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                                <input type="hidden" name="redirect" value="/cart.php">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">-</button>
                                            </form>
                                            <span class="px-3"><?php echo $quantity; ?></span>
                                            <form action="/cart/add.php" method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <input type="hidden" name="redirect" value="/cart.php">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" 
                                                        <?php echo ($quantity >= $productStock) ? 'disabled' : ''; ?>>+</button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold">$<?php echo number_format($itemTotal, 2); ?></td>
                                    <td class="text-center">
                                        <form action="/cart/remove.php" method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                            <input type="hidden" name="remove_all" value="1">
                                            <input type="hidden" name="redirect" value="/cart.php">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-center fw-bold h5 text-success">$<?php echo number_format($total, 2); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if ($hasStockIssues): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Algunos productos exceden el stock disponible. Ajusta las cantidades antes de proceder al pago.
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <form action="/cart/clear.php" method="POST" class="me-2">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                            <input type="hidden" name="redirect" value="/cart.php">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-trash me-2"></i>Vaciar Carrito
                            </button>
                        </form>
                        
                        <?php if ($isAuthenticated): ?>
                        <form action="/cart/checkout.php" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                            <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($userName); ?>">
                            <button type="submit" class="btn btn-success btn-lg" <?php echo $hasStockIssues ? 'disabled' : ''; ?>>
                                <i class="fas fa-credit-card me-2"></i>Proceder al Pago
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="text-center">
                            <div class="alert alert-info">
                                <i class="fas fa-lock me-2"></i>
                                <strong>Autenticación requerida</strong>
                                <p class="mb-0">Para proceder al pago, debes estar autenticado.</p>
                                <a href="/login.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Redis no disponible</strong>
                    <p class="mb-0">El carrito no puede funcionar sin Redis. Verifica que el servicio esté corriendo.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Estadísticas -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estadísticas</h5>
            </div>
            <div class="card-body">
                <?php if ($redisConnected): ?>
                    <?php
                    $cartKey = "cart:$userId";
                    $itemCount = $redis->hLen($cartKey);
                    $totalItems = array_sum($redis->hGetAll($cartKey));
                    $redisInfo = $redis->info();
                    ?>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-shopping-cart me-2"></i>Tu Carrito</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Productos diferentes</span>
                                <span class="badge bg-primary rounded-pill"><?php echo $itemCount; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total de items</span>
                                <span class="badge bg-success rounded-pill"><?php echo $totalItems; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Estado</span>
                                <span class="badge <?php echo $isAuthenticated ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $isAuthenticated ? 'Autenticado' : 'Invitado'; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-bolt me-2"></i>Redis Stats</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Memoria usada</span>
                                <span><?php echo round(($redisInfo['used_memory'] ?? 0) / 1024 / 1024, 2); ?> MB</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Conexiones</span>
                                <span><?php echo $redisInfo['connected_clients'] ?? 0; ?></span>
                            </li>
                        </ul>
                    </div>
                    
                <?php else: ?>
                <p class="text-muted text-center py-3">Redis no disponible para estadísticas</p>
                <?php endif; ?>
                
                <?php
                try {
                    $productCount = $pdo->query("SELECT COUNT(*) as total FROM productos")->fetch()['total'] ?? 0;
                    $orderCount = $pdo->query("SELECT COUNT(*) as total FROM ordenes")->fetch()['total'] ?? 0;
                ?>
                <div>
                    <h6><i class="fas fa-database me-2"></i>PostgreSQL Stats</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Productos en DB</span>
                            <span class="badge bg-primary rounded-pill"><?php echo $productCount; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Órdenes totales</span>
                            <span class="badge bg-success rounded-pill"><?php echo $orderCount; ?></span>
                        </li>
                    </ul>
                </div>
                <?php } catch (Exception $e) { ?>
                <div class="alert alert-warning mt-3">
                    <small><?php echo htmlspecialchars($e->getMessage()); ?></small>
                </div>
                <?php } ?>
            </div>
        </div>
        
        <!-- Acciones rápidas -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/cart.php?action=products" class="btn btn-outline-primary">
                        <i class="fas fa-boxes me-2"></i>Agregar Productos
                    </a>
                    <a href="http://localhost:8082" target="_blank" class="btn btn-outline-danger">
                        <i class="fas fa-external-link-alt me-2"></i>Redis Dashboard
                    </a>
                    <a href="http://localhost:8081" target="_blank" class="btn btn-outline-success">
                        <i class="fas fa-database me-2"></i>pgAdmin
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Info del sistema -->
        <div class="card mt-4">
            <div class="card-body">
                <h6><i class="fas fa-info-circle me-2"></i>Información</h6>
                <p class="small text-muted mb-2">
                    <i class="fas fa-bolt text-danger me-1"></i>
                    <strong>Redis:</strong> Carrito temporal
                </p>
                <p class="small text-muted mb-2">
                    <i class="fas fa-database text-primary me-1"></i>
                    <strong>PostgreSQL:</strong> Productos y órdenes
                </p>
                <p class="small text-muted">
                    <i class="fas fa-lock text-success me-1"></i>
                    <strong>JWT Auth:</strong> Autenticación segura
                </p>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

// Incluir el layout
require_once __DIR__ . '/includes/layout.php';
?>
