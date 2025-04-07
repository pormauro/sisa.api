<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    private $conn;
    private $table = 'users';

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Crear usuario: requiere username, email y password hash
    public function createUser($username, $email, $passwordHash)
    {
        $sql = "INSERT INTO {$this->table} (username, email, password, activated) VALUES (:username, :email, :pass, 0)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':pass', $passwordHash);
        return $stmt->execute();
    }

    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    // Buscar usuario por id
    public function findById($userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Buscar usuario por email
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar usuario por username
    public function findByUsername($username)
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar intentos fallidos
    public function updateFailedAttempts($userId, $failedAttempts, $lockedUntil = null)
    {
        $sql = "UPDATE {$this->table} SET failed_attempts = :failed, locked_until = :locked WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':failed', $failedAttempts);
        $stmt->bindParam(':locked', $lockedUntil);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }

    // Actualizar contraseña
    public function updatePassword($userId, $newHash)
    {
        $sql = "UPDATE {$this->table} SET password = :pass WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pass', $newHash);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }

    // Guardar token de reseteo
    public function setResetToken($userId, $token, $expires)
    {
        $sql = "UPDATE {$this->table} SET password_reset_token = :token, password_reset_expires = :expires WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }

    // Buscar usuario por token de reseteo
    public function findByResetToken($token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE password_reset_token = :token AND password_reset_expires > NOW() LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Métodos para activación de cuenta (asegúrate de que la tabla tenga las columnas activation_token y activated)
    public function setActivationToken($userId, $token)
    {
        $sql = "UPDATE {$this->table} SET activation_token = :token, activated = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }

    public function activateUser($token)
    {
        $sql = "UPDATE {$this->table} SET activated = 1, activation_token = NULL WHERE activation_token = :token";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        return $stmt->execute();
    }

    public function findByActivationToken($token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE activation_token = :token LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // NUEVO: Guardar el api_token actual del usuario
    public function setApiToken($userId, $apiToken)
    {
        $sql = "UPDATE {$this->table} SET api_token = :token WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $apiToken);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }
    
    // Obtener todos los perfiles (usuarios)
    public function getAllProfiles()
    {
        $sql = "SELECT id, username, email, activated FROM {$this->table} ORDER BY username ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
