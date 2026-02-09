<?php
// app/index.php - Dashboard con Bootstrap
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/models/User.php';
require_once __DIR__ . '/utils/JWTService.php';

// Verificar autenticación
$token = $_SESSION['auth_token'] ?? $_COOKIE['auth_token'] ?? null;

if (!$token) {
    header('Location: login.php');
    exit;
}

$userData = JWTService::getUserFromToken($token);
if (!$userData) {
    header('Location: login.php');
    exit;
}

// Obtener información del usuario
$userModel = new User();
$currentUser = $userModel->getUserWithRole($userData['userId']);
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

// ========== INCLUIR CONEXIÓN CENTRALIZADA ==========
require_once '/var/www/html/includes/connection.php';

// Configuración para el layout
$pageTitle = "Dashboard - Mi App Docker";

// Capturar el contenido
ob_start();
?>
<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-1">�� ¡Hola, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h4>
                        <p class="text-muted mb-0">Bienvenido al panel de control de Mi App Docker</p>
                        <div class="mt-2">
                            <span class="badge bg-primary me-2">
                                <i class="fas fa-user-tag me-1"></i><?php echo htmlspecialchars($currentUser['role_name']); ?>
                            </span>
                            <span class="badge bg-secondary">
                                <i class="fas fa-clock me-1"></i>Token válido por 24h
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
                <h6 class="text-muted mb-3">Acciones rápidas</h6>
                <div class="d-grid gap-2">
                    <a href="/cart.php" class="btn btn-outline-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Ir al Carrito
                    </a>
                    <form method="post" class="d-grid">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon text-primary">
                    <i class="fas fa-database"></i>
                </div>
                <h3 class="stat-number">
                    <?php
                    try {
                        $productCount = $pdo->query("SELECT COUNT(*) as total FROM productos")->fetch()['total'] ?? 0;
                        echo $productCount;
                    } catch (Exception $e) {
                        echo "0";
                    }
                    ?>
                </h3>
                <p class="text-muted mb-0">Productos en DB</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon text-success">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="stat-number">
                    <?php
                    try {
                        $orderCount = $pdo->query("SELECT COUNT(*) as total FROM ordenes")->fetch()['total'] ?? 0;
                        echo $orderCount;
                    } catch (Exception $e) {
                        echo "0";
                    }
                    ?>
                </h3>
                <p class="text-muted mb-0">Órdenes totales</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon text-warning">
                    <i class="fas fa-eye"></i>
                </div>
                <h3 class="stat-number">
                    <?php
                    try {
                        $visitCount = $pdo->query("SELECT COUNT(*) as total FROM visitas")->fetch()['total'] ?? 0;
                        echo $visitCount;
                    } catch (Exception $e) {
                        echo "0";
                    }
                    ?>
                </h3>
                <p class="text-muted mb-0">Visitas totales</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-icon text-danger">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 class="stat-number">
                    <?php
                    if (extension_loaded('redis')) {
                        try {
                            $redis = new Redis();
                            if ($redis->connect('redis-cache', 6379, 2)) {
                                echo $redis->incr('total_visits');
                            } else {
                                echo "0";
                            }
                        } catch (Exception $e) {
                            echo "0";
                        }
                    } else {
                        echo "0";
                    }
                    ?>
                </h3>
                <p class="text-muted mb-0">Visitas Redis</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Columna Izquierda -->
    <div class="col-lg-8">
        <!-- Redis Card -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Redis Cache</h5>
                <span class="badge bg-light text-danger">En tiempo real</span>
            </div>
            <div class="card-body">
                <?php
                $redisConnected = false;
                if (extension_loaded('redis')) {
                    try {
                        $redis = new Redis();
                        $redisConnected = $redis->connect('redis-cache', 6379, 2);
                        
                        if ($redisConnected):
                ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Conexión exitosa</strong>
                            <p class="mb-0 mt-2">Redis está funcionando correctamente</p>
                        </div>
                        
                        <div class="mt-3">
                            <h6><i class="fas fa-chart-line me-2"></i>Estadísticas</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Memoria usada</span>
                                    <span class="fw-bold">
                                        <?php 
                                        $redisInfo = $redis->info();
                                        echo round(($redisInfo['used_memory'] ?? 0) / 1024 / 1024, 2) . " MB";
                                        ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Conexiones activas</span>
                                    <span class="fw-bold"><?php echo $redisInfo['connected_clients'] ?? 0; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6><i class="fas fa-clock me-2"></i>Cache de ejemplo</h6>
                                <?php
                                $cacheKey = 'current_time';
                                if (!$redis->exists($cacheKey)) {
                                    $currentTime = date('Y-m-d H:i:s');
                                    $redis->setex($cacheKey, 5, $currentTime);
                                    $source = "generado nuevo";
                                    $badgeClass = "bg-warning";
                                } else {
                                    $currentTime = $redis->get($cacheKey);
                                    $source = "desde cache";
                                    $badgeClass = "bg-success";
                                }
                                ?>
                                <div class="text-center py-3">
                                    <div class="display-6 mb-2"><?php echo date('H:i:s', strtotime($currentTime)); ?></div>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $source; ?></span>
                                    <p class="text-muted mt-2 small">Actualiza cada 5 segundos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="http://localhost:8082" target="_blank" class="btn btn-outline-danger">
                        <i class="fas fa-external-link-alt me-2"></i>Abrir Dashboard de Redis
                    </a>
                </div>
                <?php
                        else:
                ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Redis no está disponible
                </div>
                <?php
                        endif;
                    } catch (Exception $e) {
                ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error Redis: <?php echo htmlspecialchars($e->getMessage()); ?>
                </div>
                <?php
                    }
                } else {
                ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Extensión Redis no disponible en PHP
                </div>
                <?php
                }
                ?>
            </div>
        </div>

        <!-- PostgreSQL Card -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-database me-2"></i>PostgreSQL Database</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    // Ya tenemos $pdo desde includes/connection.php
                ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Conexión exitosa</strong>
                    <p class="mb-0 mt-2">Conectado a: <code><?php echo getenv('DB_NAME'); ?></code></p>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6><i class="fas fa-table me-2"></i>Tablas principales</h6>
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between">
                                <span><i class="fas fa-table text-primary me-2"></i>productos</span>
                                <span class="badge bg-primary"><?php echo $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn(); ?> registros</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between">
                                <span><i class="fas fa-table text-success me-2"></i>users</span>
                                <span class="badge bg-success"><?php echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?> usuarios</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between">
                                <span><i class="fas fa-table text-warning me-2"></i>ordenes</span>
                                <span class="badge bg-warning"><?php echo $pdo->query("SELECT COUNT(*) FROM ordenes")->fetchColumn(); ?> órdenes</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-chart-bar me-2"></i>Últimas visitas</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $visitas = $pdo->query("SELECT fecha, ip FROM visitas ORDER BY fecha DESC LIMIT 5")->fetchAll();
                                    foreach ($visitas as $visita):
                                    ?>
                                    <tr>
                                        <td><small><?php echo date('H:i', strtotime($visita['fecha'])); ?></small></td>
                                        <td><code><?php echo htmlspecialchars($visita['ip']); ?></code></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="http://localhost:8081" target="_blank" class="btn btn-outline-success">
                        <i class="fas fa-external-link-alt me-2"></i>Abrir pgAdmin
                    </a>
                    <a href="/cart.php?action=products" class="btn btn-outline-primary ms-2">
                        <i class="fas fa-box me-2"></i>Ver Productos
                    </a>
                </div>
                <?php
                } catch (PDOException $e) {
                ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Error de conexión</strong>
                    <p class="mb-0 mt-2"><?php echo htmlspecialchars($e->getMessage()); ?></p>
                </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Columna Derecha -->
    <div class="col-lg-4">
        <!-- Sistema Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Sistema</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fab fa-docker text-info me-2"></i>Contenedores</span>
                        <span class="fw-bold">5</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fab fa-php text-primary me-2"></i>PHP Version</span>
                        <span class="fw-bold"><?php echo phpversion(); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-server text-secondary me-2"></i>Servidor</span>
                        <span><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-memory text-warning me-2"></i>Memoria usada</span>
                        <span class="fw-bold"><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</span>
                    </li>
                </ul>
                
                <div class="mt-4">
                    <h6><i class="fas fa-plug me-2"></i>Extensiones cargadas</h6>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge <?php echo extension_loaded('redis') ? 'bg-success' : 'bg-danger'; ?>">
                            Redis <?php echo extension_loaded('redis') ? '✅' : '❌'; ?>
                        </span>
                        <span class="badge bg-success">PDO PostgreSQL ✅</span>
                        <span class="badge bg-success">JSON ✅</span>
                        <span class="badge bg-success">Session ✅</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enlaces rápidos -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-link me-2"></i>Enlaces Rápidos</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="http://localhost:8080" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i>Esta App (puerto 8080)
                    </a>
                    <a href="http://localhost:8080/test-connection.php" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fas fa-plug me-2"></i>Probar Conexión DB
                    </a>
                    <a href="http://localhost:8080/cart.php" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i>Carrito de Compras
                    </a>
                    <a href="http://localhost:8081" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fas fa-database me-2"></i>pgAdmin - PostgreSQL
                    </a>
                    <a href="http://localhost:8082" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fas fa-bolt me-2"></i>Redis Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Carrito Promo -->
        <div class="card mt-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Nueva Funcionalidad</h5>
            </div>
            <div class="card-body text-center">
                <div class="display-1 mb-3">🛒</div>
                <h5>Carrito de Compras</h5>
                <p class="text-muted">Completo con Redis + PostgreSQL</p>
                <a href="/cart.php" class="btn btn-warning btn-lg w-100">
                    <i class="fas fa-rocket me-2"></i>Probar Carrito
                </a>
                <p class="small text-muted mt-2 mb-0">
                    <i class="fas fa-lightbulb me-1"></i>Redis para temporal + PostgreSQL para permanente
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
