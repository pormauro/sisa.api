<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Clients {
    private $conn;
    private $table = 'clients';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea un nuevo cliente
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, business_name, tax_id, email, brand_file_id, phone, address)
                VALUES (:user_id, :business_name, :tax_id, :email, :brand_file_id, :phone, :address)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':business_name', $data['business_name']);
        $stmt->bindParam(':tax_id', $data['tax_id']);
        $stmt->bindParam(':email', $data['email']);
        $brandFileId = isset($data['brand_file_id']) ? $data['brand_file_id'] : null;
        $stmt->bindParam(':brand_file_id', $brandFileId, PDO::PARAM_INT);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        if ($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Retorna un cliente por su ID
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lista todos los clientes (sin filtrar por user_id)
    public function listAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Actualiza los datos de un cliente
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    business_name = :business_name,
                    tax_id = :tax_id,
                    email = :email,
                    brand_file_id = :brand_file_id,
                    phone = :phone,
                    address = :address,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':business_name', $data['business_name']);
        $stmt->bindParam(':tax_id', $data['tax_id']);
        $stmt->bindParam(':email', $data['email']);
        $brandFileId = isset($data['brand_file_id']) ? $data['brand_file_id'] : null;
        $stmt->bindParam(':brand_file_id', $brandFileId, PDO::PARAM_INT);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Elimina un cliente de forma física
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
