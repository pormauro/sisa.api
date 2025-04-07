<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class CashBoxes {
    private $conn;
    private $table = 'cash_boxes';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea una nueva caja de efectivo y retorna su ID
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} (user_id, name, image_file_id)
                VALUES (:user_id, :name, :image_file_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name']);
        $imageFileId = isset($data['image_file_id']) ? $data['image_file_id'] : null;
        $stmt->bindParam(':image_file_id', $imageFileId);
        if($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtiene una caja por su ID
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lista todas las cajas del usuario
    public function listAllByUser($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Actualiza una caja
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET name = :name, image_file_id = :image_file_id, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $imageFileId = isset($data['image_file_id']) ? $data['image_file_id'] : null;
        $stmt->bindParam(':image_file_id', $imageFileId);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Elimina una caja
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
