<?php
// app/auth/index.php - Punto de entrada para rutas de autenticación

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/middlewares/AuthMiddleware.php';

// Habilitar CORS si es necesario
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener la ruta solicitada
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/auth';
$authController = new AuthController();

// Extraer la parte específica de la ruta después de /auth
$route = str_replace($base_path, '', $request_uri);
$route = explode('?', $route)[0]; // Remover query string
$route = rtrim($route, '/');

// Enrutamiento básico
switch ($route) {
    case '':
    case '/':
        // POST /auth -> login
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
        }
        break;
        
    case '/login':
        // POST /auth/login -> login (alias)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
        }
        break;
        
    case '/register':
        // POST /auth/register -> registro
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->register();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
        }
        break;
        
    case '/profile':
        // GET /auth/profile -> perfil del usuario
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $authController->profile();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
        }
        break;
        
    case '/check':
        // GET /auth/check -> verificar token
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $userData = AuthMiddleware::getCurrentUser();
            if ($userData) {
                echo json_encode([
                    'authenticated' => true,
                    'user' => $userData
                ]);
            } else {
                echo json_encode([
                    'authenticated' => false
                ]);
            }
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada: ' . $route]);
        break;
}
