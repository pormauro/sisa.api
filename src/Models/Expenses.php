<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Expenses {
    private $conn;
    private $table = 'expenses';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea un nuevo registro de expense y retorna el ID insertado
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, description, category, amount, invoice_number, folder_id, attached_files, expense_date)
                VALUES (:user_id, :description, :category, :amount, :invoice_number, :folder_id, :attached_files, :expense_date)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':invoice_number', $data['invoice_number']);
        $folderId = isset($data['folder_id']) ? $data['folder_id'] : null;
        $stmt->bindParam(':folder_id', $folderId);
        $attachedFiles = isset($data['attached_files']) ? $data['attached_files'] : null;
        $stmt->bindParam(':attached_files', $attachedFiles);
        // Si no se especifica expense_date, se usa la fecha actual
        $expenseDate = isset($data['expense_date']) ? $data['expense_date'] : date('Y-m-d H:i:s');
        $stmt->bindParam(':expense_date', $expenseDate);
        if($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtiene un expense por su ID
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lista todos los expenses pertenecientes al usuario
    public function listAllByUser($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualiza un expense
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    description = :description,
                    category = :category,
                    amount = :amount,
                    invoice_number = :invoice_number,
                    folder_id = :folder_id,
                    attached_files = :attached_files,
                    expense_date = :expense_date,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':invoice_number', $data['invoice_number']);
        $folderId = isset($data['folder_id']) ? $data['folder_id'] : null;
        $stmt->bindParam(':folder_id', $folderId);
        $attachedFiles = isset($data['attached_files']) ? $data['attached_files'] : null;
        $stmt->bindParam(':attached_files', $attachedFiles);
        $expenseDate = isset($data['expense_date']) ? $data['expense_date'] : date('Y-m-d H:i:s');
        $stmt->bindParam(':expense_date', $expenseDate);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Elimina un expense por su ID
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
