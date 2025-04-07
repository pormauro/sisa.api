<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class AccountingClosings {
    private $conn;
    private $table = 'accounting_closings';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea un nuevo registro de cierre contable y retorna su ID
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table}
                (user_id, cash_box_id, closing_date, final_balance, total_income, total_expenses, comments)
                VALUES (:user_id, :cash_box_id, :closing_date, :final_balance, :total_income, :total_expenses, :comments)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':cash_box_id', $data['cash_box_id'], PDO::PARAM_INT);
        $stmt->bindParam(':closing_date', $data['closing_date']);
        $stmt->bindParam(':final_balance', $data['final_balance']);
        $stmt->bindParam(':total_income', $data['total_income']);
        $stmt->bindParam(':total_expenses', $data['total_expenses']);
        $comments = isset($data['comments']) ? $data['comments'] : null;
        $stmt->bindParam(':comments', $comments);
        if ($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtiene un cierre contable por su ID
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lista todos los cierres contables para un usuario
    public function listAllByUser($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualiza un cierre contable
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    cash_box_id = :cash_box_id,
                    closing_date = :closing_date,
                    final_balance = :final_balance,
                    total_income = :total_income,
                    total_expenses = :total_expenses,
                    comments = :comments,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cash_box_id', $data['cash_box_id'], PDO::PARAM_INT);
        $stmt->bindParam(':closing_date', $data['closing_date']);
        $stmt->bindParam(':final_balance', $data['final_balance']);
        $stmt->bindParam(':total_income', $data['total_income']);
        $stmt->bindParam(':total_expenses', $data['total_expenses']);
        $comments = isset($data['comments']) ? $data['comments'] : null;
        $stmt->bindParam(':comments', $comments);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Elimina un cierre contable por su ID
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
