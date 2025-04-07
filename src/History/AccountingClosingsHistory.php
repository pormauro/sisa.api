<?php
namespace App\History;

use App\Config\Database;
use PDO;

class AccountingClosingsHistory {
    private $conn;
    private $table = 'accounting_closings_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de cierres contables.
     *
     * Parámetros:
     * - closing_id: ID del cierre afectado.
     * - user_id: ID del dueño.
     * - cash_box_id: ID de la caja de efectivo asociada.
     * - closing_date: Fecha del cierre.
     * - final_balance: Saldo final.
     * - total_income: Total de ingresos.
     * - total_expenses: Total de egresos.
     * - comments: Comentarios (opcional).
     * - changed_by: ID del usuario que realizó la operación.
     * - operation_type: 'CREATION', 'UPDATE' o 'DELETION'.
     */
    public function insertHistory($closing_id, $user_id, $cash_box_id, $closing_date, $final_balance, $total_income, $total_expenses, $comments, $changed_by, $operation_type) {
        $sql = "INSERT INTO {$this->table}
                (closing_id, user_id, cash_box_id, closing_date, final_balance, total_income, total_expenses, comments, changed_by, operation_type)
                VALUES (:closing_id, :user_id, :cash_box_id, :closing_date, :final_balance, :total_income, :total_expenses, :comments, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':closing_id', $closing_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':cash_box_id', $cash_box_id, PDO::PARAM_INT);
        $stmt->bindParam(':closing_date', $closing_date);
        $stmt->bindParam(':final_balance', $final_balance);
        $stmt->bindParam(':total_income', $total_income);
        $stmt->bindParam(':total_expenses', $total_expenses);
        $comments = isset($comments) ? $comments : null;
        $stmt->bindParam(':comments', $comments);
        $stmt->bindParam(':changed_by', $changed_by, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operation_type);
        return $stmt->execute();
    }

    // Lista el historial de un cierre contable por su ID, ordenado de más reciente a más antiguo
    public function listHistoryByClosingId($closing_id) {
        $sql = "SELECT * FROM {$this->table} WHERE closing_id = :closing_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':closing_id', $closing_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
