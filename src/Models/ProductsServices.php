<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class ProductsServices {
    private $conn;
    private $table = 'products_services';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea un nuevo producto/servicio y retorna su ID
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, description, category, price, cost, difficulty, item_type, product_image_file_id, stock)
                VALUES (:user_id, :description, :category, :price, :cost, :difficulty, :item_type, :product_image_file_id, :stock)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':cost', $data['cost']);
        $stmt->bindParam(':difficulty', $data['difficulty']);
        $stmt->bindParam(':item_type', $data['item_type']);
        $productImageFileId = isset($data['product_image_file_id']) ? $data['product_image_file_id'] : null;
        $stmt->bindParam(':product_image_file_id', $productImageFileId);
        $stmt->bindParam(':stock', $data['stock']);
        if($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Devuelve un producto/servicio por su ID
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lista todos los productos/servicios del usuario autenticado
    public function listAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualiza un registro de producto/servicio
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    description = :description,
                    category = :category,
                    price = :price,
                    cost = :cost,
                    difficulty = :difficulty,
                    item_type = :item_type,
                    product_image_file_id = :product_image_file_id,
                    stock = :stock,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':cost', $data['cost']);
        $stmt->bindParam(':difficulty', $data['difficulty']);
        $stmt->bindParam(':item_type', $data['item_type']);
        $productImageFileId = isset($data['product_image_file_id']) ? $data['product_image_file_id'] : null;
        $stmt->bindParam(':product_image_file_id', $productImageFileId);
        $stmt->bindParam(':stock', $data['stock']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Elimina un producto/servicio
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
