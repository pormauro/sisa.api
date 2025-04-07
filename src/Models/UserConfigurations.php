<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class UserConfigurations {
    private $conn;
    private $table = 'user_configurations';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea un nuevo registro en user_configurations y devuelve el nuevo ID
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} (user_id, role, view_type, theme, font_size)
                VALUES (:user_id, :role, :view_type, :theme, :font_size)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':view_type', $data['view_type']);
        $stmt->bindParam(':theme', $data['theme']);
        $stmt->bindParam(':font_size', $data['font_size']);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtiene una configuración por su ID (sin restricción de usuario)
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtiene una configuración por user_id (para operaciones propias)
    public function findByUserId($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lista todas las configuraciones (GET libre)
    public function listAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualiza la configuración del registro indicado
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    role = :role,
                    view_type = :view_type,
                    theme = :theme,
                    font_size = :font_size,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':view_type', $data['view_type']);
        $stmt->bindParam(':theme', $data['theme']);
        $stmt->bindParam(':font_size', $data['font_size']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Elimina físicamente la configuración
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
