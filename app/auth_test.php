<?php
// app/auth_test.php - Punto de prueba para autenticación

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/controllers/AuthController.php';
require_once __DIR__ . '/utils/ResponseHelper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = $_GET['action'] ?? '';

$authController = new AuthController();

switch ($action) {
    case 'login':
        $authController->login();
        break;
        
    case 'register':
        $authController->register();
        break;
        
    case 'profile':
        $authController->profile();
        break;
        
    default:
        echo json_encode([
            'status' => 'success',
            'message' => 'Sistema de autenticación JWT funcionando',
            'endpoints' => [
                'POST /auth_test.php?action=login' => 'Iniciar sesión',
                'POST /auth_test.php?action=register' => 'Registrar usuario',
                'GET /auth_test.php?action=profile' => 'Obtener perfil (requiere token)'
            ],
            'test_credentials' => [
                'username' => 'admin',
                'password' => 'Admin123!'
            ]
        ]);
        break;
}
