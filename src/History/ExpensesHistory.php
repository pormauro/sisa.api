<?php
namespace App\History;

use App\Config\Database;
use PDO;

class ExpensesHistory {
    private $conn;
    private $table = 'expenses_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de expenses.
     *
     * Parámetros:
     * - expense_id: ID del expense afectado.
     * - user_id: ID del dueño.
     * - description: Descripción del expense.
     * - category: Categoría.
     * - amount: Monto.
     * - invoice_number: Número de factura.
     * - folder_id: ID del folder (opcional).
     * - attached_files: Archivos adjuntos (opcional).
     * - expense_date: Fecha del expense.
     * - changed_by: ID del usuario que realizó la operación.
     * - operation_type: 'CREATION', 'UPDATE' o 'DELETION'.
     */
    public function insertHistory($expense_id, $user_id, $description, $category, $amount, $invoice_number, $folder_id, $attached_files, $expense_date, $changed_by, $operation_type) {
        $sql = "INSERT INTO {$this->table}
                (expense_id, user_id, description, category, amount, invoice_number, folder_id, attached_files, expense_date, changed_by, operation_type)
                VALUES (:expense_id, :user_id, :description, :category, :amount, :invoice_number, :folder_id, :attached_files, :expense_date, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':expense_id', $expense_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':invoice_number', $invoice_number);
        $folder_id = isset($folder_id) ? $folder_id : null;
        $stmt->bindParam(':folder_id', $folder_id);
        $attached_files = isset($attached_files) ? $attached_files : null;
        $stmt->bindParam(':attached_files', $attached_files);
        $stmt->bindParam(':expense_date', $expense_date);
        $stmt->bindParam(':changed_by', $changed_by, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operation_type);
        return $stmt->execute();
    }

    // Lista el historial de un expense por su ID, ordenado de más reciente a más antiguo
    public function listHistoryByExpenseId($expense_id) {
        $sql = "SELECT * FROM {$this->table} WHERE expense_id = :expense_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':expense_id', $expense_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
