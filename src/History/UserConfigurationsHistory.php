<?php
namespace App\History;

use App\Config\Database;
use PDO;

class UserConfigurationsHistory {
    private $conn;
    private $table = 'user_configurations_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de configuraciones.
     */
    public function insertHistory($configId, $userId, $role, $viewType, $theme, $fontSize, $changedBy, $operationType) {
        $sql = "INSERT INTO {$this->table} 
                (config_id, user_id, role, view_type, theme, font_size, changed_by, operation_type)
                VALUES (:config_id, :user_id, :role, :view_type, :theme, :font_size, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':config_id', $configId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':view_type', $viewType);
        $stmt->bindParam(':theme', $theme);
        $stmt->bindParam(':font_size', $fontSize);
        $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operationType);
        return $stmt->execute();
    }
    
    /**
     * Lista el historial de una configuración por su ID.
     */
    public function listHistoryByConfigId($configId) {
        $sql = "SELECT * FROM {$this->table} WHERE config_id = :config_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':config_id', $configId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
