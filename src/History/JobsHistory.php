<?php
namespace App\History;

use App\Config\Database;
use PDO;

class JobsHistory {
    private $conn;
    private $table = 'jobs_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de jobs.
     *
     * Parámetros:
     * - job_id: ID del job afectado.
     * - user_id: ID del dueño.
     * - client_id: ID del cliente.
     * - product_service_id: ID del producto/servicio asociado (puede ser NULL).
     * - folder_id: ID del folder (puede ser NULL).
     * - type_of_work: Tipo de trabajo.
     * - description: Descripción del job.
     * - status: Estado del job.
     * - schedule: Fecha y hora de inicio (puede ser NULL).
     * - multiplicative_value: Valor multiplicador (por defecto 1.00).
     * - attached_files: Archivos adjuntos (opcional).
     * - changed_by: ID del usuario que realizó la operación.
     * - operation_type: 'CREATION', 'UPDATE' o 'DELETION'.
     */
    public function insertHistory($job_id, $user_id, $client_id, $product_service_id, $folder_id, $type_of_work, $description, $status, $schedule, $multiplicative_value, $attached_files, $changed_by, $operation_type) {
        $sql = "INSERT INTO {$this->table}
                (job_id, user_id, client_id, product_service_id, folder_id, type_of_work, description, status, schedule, multiplicative_value, attached_files, changed_by, operation_type)
                VALUES (:job_id, :user_id, :client_id, :product_service_id, :folder_id, :type_of_work, :description, :status, :schedule, :multiplicative_value, :attached_files, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_service_id', $product_service_id, PDO::PARAM_INT);
        $folder_id = isset($folder_id) ? $folder_id : null;
        $stmt->bindParam(':folder_id', $folder_id);
        $stmt->bindParam(':type_of_work', $type_of_work);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':schedule', $schedule);
        $stmt->bindParam(':multiplicative_value', $multiplicative_value);
        $attached_files = isset($attached_files) ? $attached_files : null;
        $stmt->bindParam(':attached_files', $attached_files);
        $stmt->bindParam(':changed_by', $changed_by, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operation_type);
        return $stmt->execute();
    }

    // Lista el historial de un job por su ID, ordenado de más reciente a más antiguo
    public function listHistoryByJobId($job_id) {
        $sql = "SELECT * FROM {$this->table} WHERE job_id = :job_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
