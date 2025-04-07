<?php

namespace App\Models;

use App\Config\Database;  // Esto es clave

use PDO;

class File {
    private $conn;
    private $table = 'files';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Inserta un archivo en la base de datos y devuelve el ID insertado
    public function upload($userId, $originalName, $fileType, $fileSize, $fileData) {
        $sql = "INSERT INTO {$this->table} (user_id, original_name, file_type, file_size, file_data) 
                VALUES (:user_id, :original_name, :file_type, :file_size, :file_data)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':original_name', $originalName);
        $stmt->bindParam(':file_type', $fileType);
        $stmt->bindParam(':file_size', $fileSize, PDO::PARAM_INT);
        $stmt->bindParam(':file_data', $fileData, PDO::PARAM_LOB);
        if($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtiene la información (sin el archivo binario) del archivo almacenado
    public function getFile($fileId)
    {
        $database = new Database();
        $pdo = $database->connect();
        
        $stmt = $pdo->prepare("
            SELECT id, user_id, original_name, file_type, file_size, file_data
            FROM files 
            WHERE id = ?
        ");
        $stmt->execute([$fileId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
}
