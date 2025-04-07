<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Permission {
    private $conn;
    private $table = 'permissions';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Lista solo los permisos globales (user_id IS NULL)
    public function listGlobal() {
        $sql = "SELECT * FROM {$this->table} WHERE user_id IS NULL ORDER BY created_at DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lista todos los permisos
    public function listAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Lista los permisos para un usuario específico (incluyendo los permisos globales: user_id IS NULL)
    public function listByUser($userId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Inserta un nuevo permiso
    public function create(array $data) {
        // Verificar si ya existe el permiso para ese usuario y sector
        $userId = isset($data['user_id']) ? $data['user_id'] : null;
        if ($this->exists($userId, $data['sector'])) {
            // Si ya existe, no se inserta y se retorna false.
            return false;
        }
        
        $sql = "INSERT INTO {$this->table} (user_id, sector)
                VALUES (:user_id, :sector)";
        $stmt = $this->conn->prepare($sql);
        // Si user_id es null, se enlaza correctamente con PDO::PARAM_NULL
        if ($userId === null) {
            $stmt->bindValue(':user_id', null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        }
        $stmt->bindParam(':sector', $data['sector']);
        if ($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }


    // Consulta si existe un permiso para un sector y un usuario (o para todos)
    public function hasPermission($userId, $sector) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE sector = :sector AND (user_id = :user_id OR user_id IS NULL) 
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':sector', $sector);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    // Método para eliminar un permiso
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function exists($userId, $sector) {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE sector = :sector AND ((user_id = :user_id) OR (user_id IS NULL AND :user_id IS NULL))";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':sector', $sector);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }



}
