<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Files {
    private $conn;
    private $table = 'files';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function upload($userId, $originalName, $storedName, $fileType, $fileSize, $filePath, $storagePath) {
        $sql = "INSERT INTO {$this->table} (user_id, original_name, stored_name, file_type, file_size, file_path, storage_path) 
                VALUES (:user_id, :original_name, :stored_name, :file_type, :file_size, :file_path, :storage_path)";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':original_name', $originalName);
        $stmt->bindParam(':stored_name', $storedName);
        $stmt->bindParam(':file_type', $fileType);
        $stmt->bindParam(':file_size', $fileSize, PDO::PARAM_INT);
        $stmt->bindParam(':file_path', $filePath); 
        $stmt->bindParam(':storage_path', $storagePath);

        if($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getFile($fileId) {
        $sql = "SELECT id, user_id, original_name, stored_name, file_type, file_size, file_path, storage_path
                FROM {$this->table} 
                WHERE id = :file_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}