<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Jobs {
    private $conn;
    private $table = 'jobs';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea un nuevo registro de job y retorna el ID insertado
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, client_id, product_service_id, folder_id, type_of_work, description, status, start_datetime, end_datetime, multiplicative_value, attached_files)
                VALUES (:user_id, :client_id, :product_service_id, :folder_id, :type_of_work, :description, :status, :start_datetime, :end_datetime, :multiplicative_value, :attached_files)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_service_id', $data['product_service_id'], PDO::PARAM_INT);
        $folderId = isset($data['folder_id']) ? $data['folder_id'] : null;
        $stmt->bindParam(':folder_id', $folderId);
        $stmt->bindParam(':type_of_work', $data['type_of_work']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':status', $data['status']);
        $startDatetime = isset($data['start_datetime']) ? $data['start_datetime'] : null;
        $stmt->bindParam(':start_datetime', $startDatetime);
        $endDatetime = isset($data['end_datetime']) ? $data['end_datetime'] : null;
        $stmt->bindParam(':end_datetime', $endDatetime);
        $multiplicativeValue = isset($data['multiplicative_value']) ? $data['multiplicative_value'] : 1.00;
        $stmt->bindParam(':multiplicative_value', $multiplicativeValue);
        $attachedFiles = isset($data['attached_files']) ? $data['attached_files'] : null;
        $stmt->bindParam(':attached_files', $attachedFiles);
        
        if($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtiene un registro de job por su ID
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lista todos los jobs de un usuario
    public function listAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Actualiza un job
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    client_id = :client_id,
                    product_service_id = :product_service_id,
                    folder_id = :folder_id,
                    type_of_work = :type_of_work,
                    description = :description,
                    status = :status,
                    start_datetime = :start_datetime,
                    end_datetime = :end_datetime,
                    multiplicative_value = :multiplicative_value,
                    attached_files = :attached_files,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_service_id', $data['product_service_id'], PDO::PARAM_INT);
        $folderId = isset($data['folder_id']) ? $data['folder_id'] : null;
        $stmt->bindParam(':folder_id', $folderId);
        $stmt->bindParam(':type_of_work', $data['type_of_work']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':status', $data['status']);
        $startDatetime = isset($data['start_datetime']) ? $data['start_datetime'] : null;
        $stmt->bindParam(':start_datetime', $startDatetime);
        $endDatetime = isset($data['end_datetime']) ? $data['end_datetime'] : null;
        $stmt->bindParam(':end_datetime', $endDatetime);
        $multiplicativeValue = isset($data['multiplicative_value']) ? $data['multiplicative_value'] : 1.00;
        $stmt->bindParam(':multiplicative_value', $multiplicativeValue);
        $attachedFiles = isset($data['attached_files']) ? $data['attached_files'] : null;
        $stmt->bindParam(':attached_files', $attachedFiles);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Elimina un job por su ID
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
