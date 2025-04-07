<?php
namespace App\History;

use App\Config\Database;
use PDO;

class ClientsHistory {
    private $conn;
    private $table = 'clients_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Inserta un registro en el historial de clientes
    // Parámetros: 
    //   client_id: ID del cliente afectado
    //   user_id: ID del usuario dueño del cliente
    //   business_name, tax_id, email, brand_file_id, phone, address: datos actuales del cliente
    //   changed_by: ID del usuario que realizó el cambio (normalmente el mismo)
    //   operation_type: 'CREATION', 'UPDATE' o 'DELETION'
    public function insertHistory($clientId, $userId, $businessName, $taxId, $email, $brandFileId, $phone, $address, $changedBy, $operationType) {
        $sql = "INSERT INTO {$this->table} 
                (client_id, user_id, business_name, tax_id, email, brand_file_id, phone, address, changed_by, operation_type)
                VALUES (:client_id, :user_id, :business_name, :tax_id, :email, :brand_file_id, :phone, :address, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':business_name', $businessName);
        $stmt->bindParam(':tax_id', $taxId);
        $stmt->bindParam(':email', $email);
        $brandFileId = isset($brandFileId) ? $brandFileId : null;
        $stmt->bindParam(':brand_file_id', $brandFileId, PDO::PARAM_INT);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':changed_by', $changedBy, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operationType);
        return $stmt->execute();
    }
    
    // Lista todos los registros de historial de un cliente, ordenados por fecha de cambio descendente
    public function listHistoryByClientId($clientId) {
        $sql = "SELECT * FROM {$this->table} WHERE client_id = :client_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
