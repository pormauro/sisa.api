<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Folders {
    private $conn;
    private $table = 'folders';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea un nuevo folder y devuelve el ID insertado.
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, client_id, name, parent_id, folder_image_file_id)
                VALUES (:user_id, :client_id, :name, :parent_id, :folder_image_file_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name']);
        $parentId = isset($data['parent_id']) ? $data['parent_id'] : null;
        $stmt->bindParam(':parent_id', $parentId);
        $folderImageFileId = isset($data['folder_image_file_id']) ? $data['folder_image_file_id'] : null;
        $stmt->bindParam(':folder_image_file_id', $folderImageFileId);
        if($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Devuelve la información de un folder por su ID.
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lista todos los folders pertenecientes al usuario.
    public function listAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Lista los folders de un cliente específico y que pertenezcan al usuario.
    public function listByClientId($clientId) {
        $sql = "SELECT * FROM {$this->table} WHERE client_id = :client_id AND parent_id IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function listByParentId($parentId) {
        $sql = "SELECT * FROM {$this->table} WHERE parent_id = :parent_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualiza los datos de un folder.
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    name = :name,
                    parent_id = :parent_id,
                    folder_image_file_id = :folder_image_file_id,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $parentId = isset($data['parent_id']) ? $data['parent_id'] : null;
        $stmt->bindParam(':parent_id', $parentId);
        $folderImageFileId = isset($data['folder_image_file_id']) ? $data['folder_image_file_id'] : null;
        $stmt->bindParam(':folder_image_file_id', $folderImageFileId);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Elimina un folder por su ID.
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
