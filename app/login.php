<?php
ob_start(); // Buffer para headers
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
// Continuar con el HTML...
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Mi App Docker</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            width: 100%;
            max-width: 900px;
            display: flex;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border-radius: 15px;
            overflow: hidden;
        }
        .left-section {
            flex: 1;
            background: white;
            padding: 40px;
        }
        .right-section {
            flex: 1;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        h1 { 
            color: #333; 
            margin-bottom: 30px;
            font-size: 28px;
        }
        h2 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 8px; 
            color: #555;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        .success {
            background: #efe;
            color: #3a3;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3a3;
        }
        .credentials {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <h1>🔐 Sistema de Autenticación JWT</h1>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <h2>🔑 Iniciar Sesión</h2>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="login-username">Usuario o Email</label>
                    <input type="text" id="login-username" name="username" placeholder="admin o admin@tienda.com" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Contraseña</label>
                    <input type="password" id="login-password" name="password" placeholder="Admin123!" required>
                </div>
                <button type="submit">✅ Ingresar</button>
            </form>
            
            <div class="credentials">
                <h3>👑 Credenciales de Prueba:</h3>
                <p><strong>Usuario:</strong> admin</p>
                <p><strong>Contraseña:</strong> Admin123!</p>
            </div>
        </div>
        
        <div class="right-section">
            <h2>🚀 Mi App Docker</h2>
            <p>Sistema de autenticación con:</p>
            <ul style="margin-top: 20px; list-style: none;">
                <li>✅ JWT (JSON Web Tokens)</li>
                <li>✅ PostgreSQL + Redis</li>
                <li>✅ Roles y permisos</li>
                <li>✅ Carrito de compras</li>
            </ul>
        </div>
    </div>
</body>
</html>
