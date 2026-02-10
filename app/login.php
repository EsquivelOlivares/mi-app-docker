<?php
// login.php - Versión con Bootstrap
ob_start();
session_start();
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/auth/models/User.php";
require_once __DIR__ . "/utils/JWTService.php";

$userModel = new User();
$error = "";
$success = "";

// Si ya está autenticado, redirigir a index
if (isset($_SESSION["auth_token"]) || isset($_COOKIE["auth_token"])) {
    $token = $_SESSION["auth_token"] ?? $_COOKIE["auth_token"];
    if (JWTService::getUserFromToken($token)) {
        ob_end_clean();
        header("Location: index.php");
        exit;
    }
}

// Manejar login
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    if ($_POST["action"] === "login") {
        $username = $_POST["username"] ?? "";
        $password = $_POST["password"] ?? "";
        
        $user = $userModel->findByUsername($username);
        if (!$user) {
            $user = $userModel->findByEmail($username);
        }
        
        if ($user && $userModel->verifyPassword($password, $user["password_hash"])) {
            $userWithRole = $userModel->getUserWithRole($user["id"]);
            
            // Generar JWT
            $token = JWTService::generateToken(
                $user["id"],
                $user["username"],
                $user["role_id"],
                $userWithRole["role_name"]
            );
            
            // Guardar en session y cookie
            $_SESSION["auth_token"] = $token;
            $_SESSION["user_id"] = $user["id"];
            
            ob_end_clean();
            setcookie("auth_token", $token, time() + (24 * 3600), "/");
            header("Location: index.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    }
    
    // Manejar registro
    if ($_POST["action"] === "register") {
        $username = $_POST["username"] ?? "";
        $email = $_POST["email"] ?? "";
        $password = $_POST["password"] ?? "";
        $full_name = $_POST["full_name"] ?? "";
        
        if ($username && $email && $password && $full_name) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Email inválido";
            } elseif (strlen($password) < 6) {
                $error = "La contraseña debe tener al menos 6 caracteres";
            } elseif ($userModel->findByUsername($username)) {
                $error = "El nombre de usuario ya existe";
            } elseif ($userModel->findByEmail($email)) {
                $error = "El email ya está registrado";
            } else {
                $userData = [
                    "username" => $username,
                    "email" => $email,
                    "password_hash" => $userModel->hashPassword($password),
                    "full_name" => $full_name,
                    "role_id" => 2 // user
                ];
                
                $userId = $userModel->create($userData);
                if ($userId) {
                    $success = "¡Registro exitoso! Ahora puedes iniciar sesión.";
                } else {
                    $error = "Error al registrar usuario";
                }
            }
        } else {
            $error = "Todos los campos son requeridos";
        }
    }
}

// Configurar variables para el layout
$pageTitle = "Iniciar Sesión - Mi App Docker";
$currentUser = null; // Para el nav

// Capturar contenido específico de login
ob_start();
?>
<div class="row justify-content-center login-container">
    <div class="col-md-8 col-lg-6">
        <div class="card login-card shadow">
            <div class="card-header text-center">
                <h3 class="mb-0"><i class="fas fa-lock me-2"></i>Autenticación JWT</h3>
            </div>
            
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Pestañas Login/Registro -->
                <ul class="nav nav-tabs nav-fill mb-4" id="authTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">
                            <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button">
                            <i class="fas fa-user-plus me-2"></i>Registrarse
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="authTabsContent">
                    <!-- Tab Login -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="login">
                            
                            <div class="mb-3">
                                <label for="login-username" class="form-label">
                                    <i class="fas fa-user me-1"></i>Usuario o Email
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" class="form-control" id="login-username" name="username" 
                                           placeholder="admin o admin@tienda.com" required>
                                    <div class="invalid-feedback">
                                        Por favor ingresa tu usuario o email.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="login-password" class="form-label">
                                    <i class="fas fa-key me-1"></i>Contraseña
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="login-password" name="password" 
                                           placeholder="Admin123!" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Por favor ingresa tu contraseña.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Ingresar al Sistema
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white py-2">
                                    <i class="fas fa-crown me-2"></i>Credenciales de Prueba
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Usuario:</strong></p>
                                            <code class="d-block p-2 bg-light rounded">admin</code>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Contraseña:</strong></p>
                                            <code class="d-block p-2 bg-light rounded">Admin123!</code>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center">
                                        <small class="text-muted">
                                            <i class="fas fa-shield-alt me-1"></i>Rol: Administrador Completo
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Registro -->
                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="register">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reg-username" class="form-label">
                                        <i class="fas fa-user-tag me-1"></i>Nombre de usuario
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input type="text" class="form-control" id="reg-username" name="username" 
                                               placeholder="juan123" required minlength="3">
                                        <div class="invalid-feedback">
                                            Mínimo 3 caracteres.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="reg-email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email
                                    </label>
                                    <input type="email" class="form-control" id="reg-email" name="email" 
                                           placeholder="juan@example.com" required>
                                    <div class="invalid-feedback">
                                        Ingresa un email válido.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg-fullname" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nombre completo
                                </label>
                                <input type="text" class="form-control" id="reg-fullname" name="full_name" 
                                       placeholder="Juan Pérez" required>
                                <div class="invalid-feedback">
                                    Ingresa tu nombre completo.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg-password" class="form-label">
                                    <i class="fas fa-key me-1"></i>Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="reg-password" name="password" 
                                           placeholder="Mínimo 6 caracteres" required minlength="6">
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Mínimo 6 caracteres.
                                    </div>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>La contraseña debe tener al menos 6 caracteres.
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Crear Cuenta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card-footer text-center text-muted">
                <small>
                    <i class="fas fa-shield-alt me-1"></i>Sistema seguro con JWT (JSON Web Tokens)
                </small>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-database fa-2x text-primary mb-2"></i>
                            <h6>PostgreSQL</h6>
                            <small class="text-muted">Base de datos principal</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-bolt fa-2x text-danger mb-2"></i>
                            <h6>Redis</h6>
                            <small class="text-muted">Caché y carrito</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-lock fa-2x text-success mb-2"></i>
                            <h6>JWT Auth</h6>
                            <small class="text-muted">Autenticación segura</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

// Incluir el layout
require_once __DIR__ . '/includes/layout.php';
?>
