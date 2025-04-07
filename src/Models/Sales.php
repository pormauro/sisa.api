<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Sales {
    private $conn;
    private $table = 'sales';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea una nueva venta y retorna el ID insertado
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, client_id, product_service_id, folder_id, invoice_number, amount, sale_date, attached_files)
                VALUES (:user_id, :client_id, :product_service_id, :folder_id, :invoice_number, :amount, :sale_date, :attached_files)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_service_id', $data['product_service_id'], PDO::PARAM_INT);
        $folderId = isset($data['folder_id']) ? $data['folder_id'] : null;
        $stmt->bindParam(':folder_id', $folderId);
        $stmt->bindParam(':invoice_number', $data['invoice_number']);
        $stmt->bindParam(':amount', $data['amount']);
        // Si se envía la fecha de venta, se usa; de lo contrario se usa la fecha actual
        $saleDate = isset($data['sale_date']) ? $data['sale_date'] : date('Y-m-d H:i:s');
        $stmt->bindParam(':sale_date', $saleDate);
        $attachedFiles = isset($data['attached_files']) ? $data['attached_files'] : null;
        $stmt->bindParam(':attached_files', $attachedFiles);
        if($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Devuelve una venta por su ID
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lista todas las ventas para el usuario autenticado
    public function listAllByUser($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Actualiza una venta
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    client_id = :client_id,
                    product_service_id = :product_service_id,
                    folder_id = :folder_id,
                    invoice_number = :invoice_number,
                    amount = :amount,
                    sale_date = :sale_date,
                    attached_files = :attached_files,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_service_id', $data['product_service_id'], PDO::PARAM_INT);
        $folderId = isset($data['folder_id']) ? $data['folder_id'] : null;
        $stmt->bindParam(':folder_id', $folderId);
        $stmt->bindParam(':invoice_number', $data['invoice_number']);
        $stmt->bindParam(':amount', $data['amount']);
        $saleDate = isset($data['sale_date']) ? $data['sale_date'] : date('Y-m-d H:i:s');
        $stmt->bindParam(':sale_date', $saleDate);
        $attachedFiles = isset($data['attached_files']) ? $data['attached_files'] : null;
        $stmt->bindParam(':attached_files', $attachedFiles);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Elimina una venta
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
