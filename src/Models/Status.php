<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Status {
    private $conn;
    private $table = 'statuses';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Lista todos los statuses ordenados por order_index
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY order_index ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene un status por su ID
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crea un nuevo status (aunque normalmente los iniciales ya existen)
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} (label, value, background_color, order_index)
                VALUES (:label, :value, :background_color, :order_index)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':label', $data['label']);
        $stmt->bindParam(':value', $data['value']);
        $stmt->bindParam(':background_color', $data['background_color']);
        $stmt->bindParam(':order_index', $data['order_index']);
        if ($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Actualiza un status existente
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET
                    label = :label,
                    value = :value,
                    background_color = :background_color,
                    order_index = :order_index,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':label', $data['label']);
        $stmt->bindParam(':value', $data['value']);
        $stmt->bindParam(':background_color', $data['background_color']);
        $stmt->bindParam(':order_index', $data['order_index']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Elimina un status
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Reordena los statuses según un arreglo de IDs (nuevo orden)
    public function reorder(array $orderedIds) {
        $sql = "UPDATE {$this->table} SET order_index = :order_index WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        foreach ($orderedIds as $order_index => $id) {
            $stmt->execute([
                ':order_index' => $order_index,
                ':id' => $id
            ]);
        }
        return true;
    }
}
?>
