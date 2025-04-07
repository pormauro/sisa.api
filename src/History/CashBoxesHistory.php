<?php
namespace App\History;

use App\Config\Database;
use PDO;

class CashBoxesHistory {
    private $conn;
    private $table = 'cash_boxes_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de cash_boxes.
     *
     * Parámetros:
     * - cash_box_id: ID de la caja afectada.
     * - user_id: ID del dueño de la caja.
     * - name: nombre de la caja.
     * - image_file_id: ID de la imagen asociada (opcional).
     * - changed_by: ID del usuario que realizó la operación.
     * - operation_type: 'CREATION', 'UPDATE' o 'DELETION'.
     */
    public function insertHistory($cashBoxId, $userId, $name, $imageFileId, $changedBy, $operationType) {
        $sql = "INSERT INTO {$this->table}
                (cash_box_id, user_id, name, image_file_id, changed_by, operation_type)
                VALUES (:cash_box_id, :user_id, :name, :image_file_id, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cash_box_id', $cashBoxId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':image_file_id', $imageFileId);
        $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operationType);
        return $stmt->execute();
    }

    // Lista el historial de una caja por su ID, ordenado de más reciente a más antiguo
    public function listHistoryByCashBoxId($cashBoxId) {
        $sql = "SELECT * FROM {$this->table} WHERE cash_box_id = :cash_box_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cash_box_id', $cashBoxId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
