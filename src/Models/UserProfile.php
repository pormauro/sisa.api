<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class UserProfile {
    private $conn;
    private $table = 'user_profile';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea un nuevo registro en user_profile y devuelve el nuevo ID
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} (user_id, full_name, phone, address, cuit, profile_file_id)
                VALUES (:user_id, :full_name, :phone, :address, :cuit, :profile_file_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':cuit', $data['cuit']);
        $profileFileId = $data['profile_file_id'] ?? null;
        $stmt->bindParam(':profile_file_id', $profileFileId);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtiene un perfil por su ID (sin restricción de usuario)
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtiene un perfil por user_id (para operaciones propias)
    public function findByUserId($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lista todos los perfiles (GET libre)
    public function listAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualiza el perfil indicado
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    full_name = :full_name,
                    phone = :phone,
                    address = :address,
                    cuit = :cuit,
                    profile_file_id = :profile_file_id,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':cuit', $data['cuit']);
        $profileFileId = $data['profile_file_id'] ?? null;
        $stmt->bindParam(':profile_file_id', $profileFileId);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Elimina físicamente el perfil
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
