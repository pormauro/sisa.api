<?php
namespace App\History;

use App\Config\Database;
use PDO;

class AppointmentsHistory {
    private $conn;
    private $table = 'appointments_history';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro en el historial de appointments.
     *
     * Parámetros:
     * - appointment_id: ID del appointment afectado.
     * - user_id: ID del dueño.
     * - client_id: ID del cliente.
     * - job_id: ID del job asociado (opcional).
     * - appointment_date: Fecha de la cita.
     * - appointment_time: Hora de la cita.
     * - location: Ubicación de la cita.
     * - site_image_file_id: ID de la imagen del sitio (opcional).
     * - attached_files: Archivos adjuntos (opcional).
     * - changed_by: ID del usuario que realizó la operación.
     * - operation_type: 'CREATION', 'UPDATE' o 'DELETION'.
     */
    public function insertHistory($appointment_id, $user_id, $client_id, $job_id, $appointment_date, $appointment_time, $location, $site_image_file_id, $attached_files, $changed_by, $operation_type) {
        $sql = "INSERT INTO {$this->table}
                (appointment_id, user_id, client_id, job_id, appointment_date, appointment_time, location, site_image_file_id, attached_files, changed_by, operation_type)
                VALUES (:appointment_id, :user_id, :client_id, :job_id, :appointment_date, :appointment_time, :location, :site_image_file_id, :attached_files, :changed_by, :operation_type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
        $job_id = isset($job_id) ? $job_id : null;
        $stmt->bindParam(':job_id', $job_id);
        $stmt->bindParam(':appointment_date', $appointment_date);
        $stmt->bindParam(':appointment_time', $appointment_time);
        $stmt->bindParam(':location', $location);
        $siteImageFileId = isset($site_image_file_id) ? $site_image_file_id : null;
        $stmt->bindParam(':site_image_file_id', $siteImageFileId);
        $attached_files = isset($attached_files) ? $attached_files : null;
        $stmt->bindParam(':attached_files', $attached_files);
        $stmt->bindParam(':changed_by', $changed_by, PDO::PARAM_INT);
        $stmt->bindParam(':operation_type', $operation_type);
        return $stmt->execute();
    }

    // Lista el historial de un appointment por su ID, ordenado de más reciente a más antiguo
    public function listHistoryByAppointmentId($appointment_id) {
        $sql = "SELECT * FROM {$this->table} WHERE appointment_id = :appointment_id ORDER BY changed_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
