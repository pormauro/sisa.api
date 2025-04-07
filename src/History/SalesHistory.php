<?php
namespace App\History;

use App\Config\Database;
use PDO;

class SalesHistory {
    private $conn;
    private $table = 'sales_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de ventas.
     *
     * Parámetros:
     * - sale_id: ID de la venta afectada.
     * - user_id: ID del dueño de la venta.
     * - client_id: ID del cliente.
     * - product_service_id: ID del producto o servicio vendido.
     * - folder_id: ID del folder (si se relaciona) o NULL.
     * - invoice_number: Número de factura.
     * - amount: Monto de la venta.
     * - sale_date: Fecha de la venta.
     * - attached_files: Archivos adjuntos (opcional).
     * - changed_by: ID del usuario que realizó la operación.
     * - operation_type: 'CREATION', 'UPDATE' o 'DELETION'.
     */
    public function insertHistory($saleId, $userId, $clientId, $productServiceId, $folderId, $invoiceNumber, $amount, $saleDate, $attachedFiles, $changedBy, $operationType) {
        $sql = "INSERT INTO {$this->table}
                (sale_id, user_id, client_id, product_service_id, folder_id, invoice_number, amount, sale_date, attached_files, changed_by, operation_type)
                VALUES (:sale_id, :user_id, :client_id, :product_service_id, :folder_id, :invoice_number, :amount, :sale_date, :attached_files, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->bindParam(':product_service_id', $productServiceId, PDO::PARAM_INT);
        $folderId = isset($folderId) ? $folderId : null;
        $stmt->bindParam(':folder_id', $folderId);
        $stmt->bindParam(':invoice_number', $invoiceNumber);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':sale_date', $saleDate);
        $attachedFiles = isset($attachedFiles) ? $attachedFiles : null;
        $stmt->bindParam(':attached_files', $attachedFiles);
        $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operationType);
        return $stmt->execute();
    }

    // Lista el historial de una venta por su ID, ordenado de más reciente a más antiguo
    public function listHistoryBySaleId($saleId) {
        $sql = "SELECT * FROM {$this->table} WHERE sale_id = :sale_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
