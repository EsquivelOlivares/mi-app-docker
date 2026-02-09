<?php
// includes/layout.php - Layout base con Bootstrap
ob_start();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Mi App Docker'; ?></title>
    
    <!-- Bootstrap 5 CSS local -->
    <link href="/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome 6 -->
    <link href="/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/public/css/app.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/public/images/favicon.ico">
    
    <?php if (isset($customCss)): ?>
        <?php foreach ($customCss as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-docker me-2"></i>Mi App Docker
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/"><i class="fas fa-home"></i> Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/cart.php"><i class="fas fa-shopping-cart"></i> Carrito</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/cart.php?action=products"><i class="fas fa-box"></i> Productos</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isset($currentUser) && $currentUser): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><span class="dropdown-item-text">
                                    <small class="text-muted">Rol: <?php echo htmlspecialchars($currentUser['role_name'] ?? 'Usuario'); ?></small>
                                </span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="post" action="/login.php">
                                        <input type="hidden" name="action" value="logout">
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-success ms-2" href="/login.php">Registrarse</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4">
        <div class="container">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>
            
            <?php echo $content ?? ''; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-docker"></i> Mi App Docker</h5>
                    <p class="text-muted">Aplicación demostrativa con Docker, PostgreSQL, Redis y autenticación JWT.</p>
                </div>
                <div class="col-md-4">
                    <h5>Tecnologías</h5>
                    <ul class="list-unstyled">
                        <li><i class="fab fa-docker text-info"></i> Docker</li>
                        <li><i class="fas fa-database text-primary"></i> PostgreSQL</li>
                        <li><i class="fas fa-bolt text-danger"></i> Redis</li>
                        <li><i class="fas fa-lock text-success"></i> JWT Auth</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Enlaces</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-white-50"><i class="fas fa-home"></i> Inicio</a></li>
                        <li><a href="/cart.php" class="text-white-50"><i class="fas fa-shopping-cart"></i> Carrito</a></li>
                        <li><a href="/login.php" class="text-white-50"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    </ul>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center text-muted">
                <small>&copy; <?php echo date('Y'); ?> Mi App Docker. Todos los derechos reservados.</small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS + Popper local -->
    <script src="/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery local -->
    <script src="/public/vendor/jquery/jquery.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/public/js/app.js"></script>
    
    <?php if (isset($customJs)): ?>
        <?php foreach ($customJs as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inlineScript)): ?>
        <script>
            <?php echo $inlineScript; ?>
        </script>
    <?php endif; ?>
</body>
</html>
<?php
echo ob_get_clean();
?>
