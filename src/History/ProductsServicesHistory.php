<?php
namespace App\History;

use App\Config\Database;
use PDO;

class ProductsServicesHistory {
    private $conn;
    private $table = 'products_services_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de productos/servicios.
     *
     * Parámetros:
     * - ps_id: ID del producto/servicio afectado.
     * - user_id: ID del dueño.
     * - description: descripción actual.
     * - category: categoría.
     * - price: precio.
     * - cost: costo.
     * - difficulty: dificultad.
     * - item_type: 'product' o 'service'.
     * - product_image_file_id: ID de la imagen asociada (opcional).
     * - stock: cantidad de stock.
     * - changed_by: ID del usuario que realizó la operación.
     * - operation_type: 'CREATION', 'UPDATE' o 'DELETION'.
     */
    public function insertHistory($psId, $userId, $description, $category, $price, $cost, $difficulty, $itemType, $productImageFileId, $stock, $changedBy, $operationType) {
        $sql = "INSERT INTO {$this->table}
                (ps_id, user_id, description, category, price, cost, difficulty, item_type, product_image_file_id, stock, changed_by, operation_type)
                VALUES (:ps_id, :user_id, :description, :category, :price, :cost, :difficulty, :item_type, :product_image_file_id, :stock, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':ps_id', $psId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':cost', $cost);
        $stmt->bindParam(':difficulty', $difficulty);
        $stmt->bindParam(':item_type', $itemType);
        $productImageFileId = isset($productImageFileId) ? $productImageFileId : null;
        $stmt->bindParam(':product_image_file_id', $productImageFileId);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operationType);
        return $stmt->execute();
    }

    // Lista el historial de un producto/servicio por su ID (ps_id)
    public function listHistoryByProductServiceId($psId) {
        $sql = "SELECT * FROM {$this->table} WHERE ps_id = :ps_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':ps_id', $psId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
