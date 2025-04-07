<?php
namespace App\History;

use App\Config\Database;
use PDO;

class FoldersHistory {
    private $conn;
    private $table = 'folders_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de folders.
     *
     * Parámetros:
     * - folder_id: ID del folder afectado
     * - user_id: ID del dueño del folder
     * - client_id: ID del cliente al que pertenece el folder
     * - name: nombre actual del folder
     * - parent_id: ID del folder padre (si existe)
     * - folder_image_file_id: ID de la imagen asociada (opcional)
     * - changed_by: ID del usuario que realizó el cambio
     * - operation_type: 'CREATION', 'UPDATE' o 'DELETION'
     */
    public function insertHistory($folderId, $userId, $clientId, $name, $parentId, $folderImageFileId, $changedBy, $operationType) {
        $sql = "INSERT INTO {$this->table} 
                (folder_id, user_id, client_id, name, parent_id, folder_image_file_id, changed_by, operation_type)
                VALUES (:folder_id, :user_id, :client_id, :name, :parent_id, :folder_image_file_id, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':folder_id', $folderId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':parent_id', $parentId);
        $stmt->bindParam(':folder_image_file_id', $folderImageFileId);
        $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operationType);
        return $stmt->execute();
    }
    
    // Lista el historial de un folder por su ID, ordenado de más reciente a más antiguo.
    public function listHistoryByFolderId($folderId) {
        $sql = "SELECT * FROM {$this->table} WHERE folder_id = :folder_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':folder_id', $folderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
