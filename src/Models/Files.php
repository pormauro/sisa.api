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

    // Obtiene la información del archivo almacenado (incluyendo el archivo binario)
    public function getFile($fileId) {
        $sql = "SELECT id, user_id, original_name, file_type, file_size, file_data
                FROM {$this->table} 
                WHERE id = :file_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
