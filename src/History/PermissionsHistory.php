<?php
namespace App\History;

use App\Config\Database;
use PDO;

class PermissionsHistory {
    private $conn;
    private $table = 'permissions_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de permisos.
     *
     * @param int    $permissionId  ID del permiso afectado.
     * @param int|null $userId      ID del usuario asociado al permiso (puede ser NULL).
     * @param string $sector        Sector del permiso.
     * @param int    $changedBy     ID del usuario que realizó la acción.
     * @param string $operationType Tipo de operación: 'CREATION', 'DELETION', etc.
     */
    public function insertHistory($permissionId, $userId, $sector, $changedBy, $operationType) {
        $sql = "INSERT INTO {$this->table} 
                (permission_id, user_id, sector, changed_by, operation_type)
                VALUES (:permission_id, :user_id, :sector, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':permission_id', $permissionId, PDO::PARAM_INT);
        if ($userId === null) {
            $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->bindParam(':sector', $sector);
        $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operationType);
        return $stmt->execute();
    }
}
