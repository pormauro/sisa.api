<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Appointments {
    private $conn;
    private $table = 'appointments';

    public function __construct(){
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crea un nuevo appointment y retorna el ID insertado
    public function create(array $data) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, client_id, job_id, appointment_date, appointment_time, location, site_image_file_id, attached_files)
                VALUES (:user_id, :client_id, :job_id, :appointment_date, :appointment_time, :location, :site_image_file_id, :attached_files)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_INT);
        $jobId = isset($data['job_id']) ? $data['job_id'] : null;
        $stmt->bindParam(':job_id', $jobId);
        $stmt->bindParam(':appointment_date', $data['appointment_date']);
        $stmt->bindParam(':appointment_time', $data['appointment_time']);
        $stmt->bindParam(':location', $data['location']);
        $siteImageFileId = isset($data['site_image_file_id']) ? $data['site_image_file_id'] : null;
        $stmt->bindParam(':site_image_file_id', $siteImageFileId);
        $attachedFiles = isset($data['attached_files']) ? $data['attached_files'] : null;
        $stmt->bindParam(':attached_files', $attachedFiles);
        if($stmt->execute()){
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Obtiene un appointment por su ID
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lista todos los appointments para un usuario
    public function listAllByUser($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Actualiza un appointment
    public function update($id, array $data) {
        $sql = "UPDATE {$this->table} SET 
                    client_id = :client_id,
                    job_id = :job_id,
                    appointment_date = :appointment_date,
                    appointment_time = :appointment_time,
                    location = :location,
                    site_image_file_id = :site_image_file_id,
                    attached_files = :attached_files,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_INT);
        $jobId = isset($data['job_id']) ? $data['job_id'] : null;
        $stmt->bindParam(':job_id', $jobId);
        $stmt->bindParam(':appointment_date', $data['appointment_date']);
        $stmt->bindParam(':appointment_time', $data['appointment_time']);
        $stmt->bindParam(':location', $data['location']);
        $siteImageFileId = isset($data['site_image_file_id']) ? $data['site_image_file_id'] : null;
        $stmt->bindParam(':site_image_file_id', $siteImageFileId);
        $attachedFiles = isset($data['attached_files']) ? $data['attached_files'] : null;
        $stmt->bindParam(':attached_files', $attachedFiles);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Elimina un appointment por su ID
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
