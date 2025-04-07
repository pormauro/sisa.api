<?php
namespace App\History;

use App\Config\Database;
use PDO;

class UserProfileHistory {
    private $conn;
    private $table = 'user_profile_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de perfiles.
     */
    public function insertHistory($profileId, $userId, $fullName, $phone, $address, $cuit, $profileFileId, $changedBy, $operationType) {
        $sql = "INSERT INTO {$this->table} 
                (profile_id, user_id, full_name, phone, address, cuit, profile_file_id, changed_by, operation_type)
                VALUES (:profile_id, :user_id, :full_name, :phone, :address, :cuit, :profile_file_id, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':profile_id', $profileId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':cuit', $cuit);
        $profileFileId = isset($profileFileId) ? $profileFileId : null;
        $stmt->bindParam(':profile_file_id', $profileFileId, PDO::PARAM_INT);
        $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operationType);
        return $stmt->execute();
    }
    
    /**
     * Lista el historial de un perfil por su ID.
     */
    public function listHistoryByProfileId($profileId) {
        $sql = "SELECT * FROM {$this->table} WHERE profile_id = :profile_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':profile_id', $profileId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
