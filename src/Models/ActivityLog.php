<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class ActivityLog
{
    private $conn;
    private $table = 'activity_log';

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Inserta un registro de actividad en la tabla activity_log.
     *
     * @param int|null $userId      ID de usuario o null si no hay usuario logueado.
     * @param string   $route       Ruta/endpoint accedido.
     * @param string   $method      Método HTTP (GET, POST, etc.).
     * @param string   $ipAddress   IP del cliente.
     * @param string   $userAgent   Agente de usuario (navegador/cliente).
     * @param int      $statusCode  Código de estado de la respuesta.
     * @param string   $message     Texto libre con información adicional (ej: error, success, etc.).
     */
    public function insert($userId, $route, $method, $ipAddress, $userAgent, $statusCode, $message = '')
    {
        $sql = "INSERT INTO {$this->table}
                (user_id, route, method, ip_address, user_agent, status_code, message)
                VALUES (:user_id, :route, :method, :ip_address, :user_agent, :status_code, :message)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':route', $route);
        $stmt->bindParam(':method', $method);
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->bindParam(':user_agent', $userAgent);
        $stmt->bindParam(':status_code', $statusCode);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    }
}
