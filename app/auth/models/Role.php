<?php
// app/auth/models/Role.php

require_once __DIR__ . '/../../config/database.php';

class Role {
    private $conn;
    private $table = 'roles';
    
    public function __construct() {
        $this->conn = Database::getConnection();
    }
    
    // Obtener todos los roles
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener permisos de un rol
    public function getPermissions($roleId) {
        $query = "SELECT p.* FROM permissions p
                  JOIN role_permissions rp ON p.id = rp.permission_id
                  WHERE rp.role_id = :role_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $roleId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Verificar si rol tiene permiso
    public function hasPermission($roleId, $permissionName) {
        $query = "SELECT COUNT(*) as count FROM permissions p
                  JOIN role_permissions rp ON p.id = rp.permission_id
                  WHERE rp.role_id = :role_id AND p.name = :permission_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role_id', $roleId);
        $stmt->bindParam(':permission_name', $permissionName);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}
