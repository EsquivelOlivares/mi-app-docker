<?php
// app/auth/controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../../config/jwt.php';  // Ruta CORREGIDA
require_once __DIR__ . '/../../utils/JWTService.php';
require_once __DIR__ . '/../../utils/ResponseHelper.php';

class AuthController {
    
    private $userModel;
    private $roleModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->roleModel = new Role();
    }
    
    // Endpoint: POST /auth/login
    public function login() {
        // Solo aceptar POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ResponseHelper::error('Método no permitido', null, 405);
        }
        
        // Obtener datos del request
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['username']) || !isset($input['password'])) {
            ResponseHelper::error('Datos incompletos. Se requiere username y password');
        }
        
        $username = trim($input['username']);
        $password = $input['password'];
        
        // Buscar usuario por username o email
        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            $user = $this->userModel->findByEmail($username);
        }
        
        // Verificar si usuario existe y está activo
        if (!$user) {
            ResponseHelper::error('Usuario o contraseña incorrectos');
        }
        
        if (!$user['is_active']) {
            ResponseHelper::error('Cuenta desactivada. Contacta al administrador');
        }
        
        // Verificar contraseña
        if (!$this->userModel->verifyPassword($password, $user['password_hash'])) {
            ResponseHelper::error('Usuario o contraseña incorrectos');
        }
        
        // Obtener información del rol
        $userWithRole = $this->userModel->getUserWithRole($user['id']);
        
        // Actualizar último login
        $this->userModel->updateLastLogin($user['id']);
        
        // Generar token JWT
        $token = JWTService::generateToken(
            $user['id'],
            $user['username'],
            $user['role_id'],
            $userWithRole['role_name']
        );
        
        // Responder con token y datos de usuario
        ResponseHelper::success('Login exitoso', [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role_id' => $user['role_id'],
                'role_name' => $userWithRole['role_name']
            ],
            'expires_in' => 24 * 3600 // 24 horas en segundos
        ]);
    }
    
    // Endpoint: POST /auth/register
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ResponseHelper::error('Método no permitido', null, 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validar campos requeridos
        $required = ['username', 'email', 'password', 'full_name'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty(trim($input[$field]))) {
                ResponseHelper::error("El campo '$field' es requerido");
            }
        }
        
        $username = trim($input['username']);
        $email = trim($input['email']);
        $password = $input['password'];
        $full_name = trim($input['full_name']);
        
        // Validaciones básicas
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::error('Email inválido');
        }
        
        if (strlen($password) < 6) {
            ResponseHelper::error('La contraseña debe tener al menos 6 caracteres');
        }
        
        // Verificar si usuario ya existe
        if ($this->userModel->findByUsername($username)) {
            ResponseHelper::error('El nombre de usuario ya está en uso');
        }
        
        if ($this->userModel->findByEmail($email)) {
            ResponseHelper::error('El email ya está registrado');
        }
        
        // Crear usuario (rol por defecto: user = 2)
        $userData = [
            'username' => $username,
            'email' => $email,
            'password_hash' => $this->userModel->hashPassword($password),
            'full_name' => $full_name,
            'role_id' => 2 // user
        ];
        
        $userId = $this->userModel->create($userData);
        
        if (!$userId) {
            ResponseHelper::error('Error al crear el usuario');
        }
        
        // Obtener usuario creado
        $newUser = $this->userModel->getUserWithRole($userId);
        
        // Generar token automáticamente
        $token = JWTService::generateToken(
            $userId,
            $username,
            $userData['role_id'],
            $newUser['role_name']
        );
        
        ResponseHelper::success('Usuario registrado exitosamente', [
            'token' => $token,
            'user' => [
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'role_id' => $userData['role_id'],
                'role_name' => $newUser['role_name']
            ]
        ], 201); // Código 201: Created
    }
    
    // Endpoint: GET /auth/profile
    public function profile() {
        $this->requireAuth();
        
        $token = JWTService::getBearerToken();
        $userData = JWTService::getUserFromToken($token);
        
        if (!$userData) {
            ResponseHelper::unauthorized('Token inválido');
        }
        
        // Obtener información actualizada del usuario
        $user = $this->userModel->getUserWithRole($userData['userId']);
        
        if (!$user) {
            ResponseHelper::error('Usuario no encontrado');
        }
        
        // Remover datos sensibles
        unset($user['password_hash']);
        
        ResponseHelper::success('Perfil obtenido', [
            'user' => $user
        ]);
    }
    
    // Middleware interno: requerir autenticación
    private function requireAuth() {
        $token = JWTService::getBearerToken();
        
        if (!$token) {
            ResponseHelper::unauthorized('Token no proporcionado');
        }
        
        $userData = JWTService::getUserFromToken($token);
        
        if (!$userData) {
            ResponseHelper::unauthorized('Token inválido o expirado');
        }
        
        return $userData;
    }
}
