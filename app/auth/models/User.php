<?php
// app/auth/models/User.php

require_once __DIR__ . '/../../config/database.php';

class User {
    private $conn;
    private $table = 'users';
    
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $full_name;
    public $role_id;
    public $is_active;
    public $last_login;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    // Buscar usuario por email
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Buscar usuario por username
    public function findByUsername($username) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Crear nuevo usuario
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (username, email, password_hash, full_name, role_id) 
                  VALUES (:username, :email, :password_hash, :full_name, :role_id) 
                  RETURNING id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password_hash', $data['password_hash']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':role_id', $data['role_id']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    // Actualizar último login
    public function updateLastLogin($userId) {
        $query = "UPDATE " . $this->table . " 
                  SET last_login = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }
    
    // Verificar contraseña
    public function verifyPassword($inputPassword, $storedHash) {
        return password_verify($inputPassword, $storedHash);
    }
    
    // Hash contraseña
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    // Obtener usuario con rol
    public function getUserWithRole($userId) {
        $query = "SELECT u.*, r.name as role_name, r.description as role_description 
                  FROM " . $this->table . " u
                  JOIN roles r ON u.role_id = r.id
                  WHERE u.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
