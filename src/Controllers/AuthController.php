<?php
namespace App\Controllers;

use App\Models\User;
use App\Helpers\JwtHelper;
use App\Mail\EmailSender;
use App\Mail\Templates\ActivationEmail;
use App\Mail\Templates\ResetPasswordEmail;

class AuthController
{
    // Registro: se requiere username, email y password
    public function register($data)
    {
        $username = $data['username'] ?? null;
        $email    = $data['email'] ?? null;
        $password = $data['password'] ?? null;
    
        if (!$username || !$email || !$password) {
            return ['error' => 'Faltan campos (username, email, password)'];
        }
    
        $userModel = new User();
        // Verificar si ya existe usuario con ese username o email
        if ($userModel->findByEmail($email) || $userModel->findByUsername($username)) {
            return ['error' => 'El usuario ya existe'];
        }
    
        // Hashear la contraseña
        $hash = password_hash($password, PASSWORD_BCRYPT);
    
        // Crear el usuario (sin activar aún)
        $ok = $userModel->createUser($username, $email, $hash);
        if ($ok) {
            // Obtener el ID del usuario insertado
            $userId = $userModel->getLastInsertId();
    
            // ***** CREAR AUTOMÁTICAMENTE EL PERFIL DEL USUARIO *****
            $profileModel = new \App\Models\UserProfile();
            $profileData = [
                'user_id' => $userId,
                'full_name' => $username, // Puedes usar el nombre de usuario o dejarlo vacío
                'phone' => '',
                'address' => '',
                'cuit' => '',
                'profile_file_id' => null
            ];
            $profileModel->create($profileData);
            
            // ***** CREAR AUTOMÁTICAMENTE LA CONFIGURACIÓN DEL USUARIO *****
            $configModel = new \App\Models\UserConfigurations();
            $configData = [
                'user_id' => $userId,
                'role' => 'usuario',       // Define el rol por defecto
                'view_type' => 'default',  // Valor por defecto para la vista
                'theme' => 'light',        // Tema por defecto (o el que prefieras)
                'font_size' => 'medium'    // Tamaño de fuente por defecto
            ];
            $configModel->create($configData);
    
            // Generar token de activación y enviarlo por email
            $activationToken = bin2hex(random_bytes(16));
            $userModel->setActivationToken($userId, $activationToken);
    
            // Construir el link de activación
            $activationLink = "https://" . $_SERVER['HTTP_HOST'] . "/activate.php?token=" . $activationToken;
    
            // Enviar email de activación
            $subject = "Activa tu cuenta";
            $body = \App\Mail\Templates\ActivationEmail::getBody($username, $activationLink);
            $emailSender = new \App\Mail\EmailSender();
            $sendResult = $emailSender->sendEmail($email, $subject, $body);
            if ($sendResult === true) {
                return ['message' => 'Usuario registrado. Se envió email de activación.'];
            } else {
                return ['error' => $sendResult];
            }
        }
        return ['error' => 'No se pudo registrar al usuario'];
    }

    // Login: se requiere username y password
    public function login($data)
    {
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$password) {
            return ['error' => 'Faltan campos (username, password)'];
        }

        $userModel = new User();
        $user = $userModel->findByUsername($username);
        if (!$user) {
            return ['error' => 'Credenciales inválidas (usuario no existe)'];
        }

        if (!$user['activated']) {
            return ['error' => 'La cuenta no está activada. Por favor, revisa tu correo para activar tu cuenta.'];
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $tiempoRestante = strtotime($user['locked_until']) - time();
            return ['error' => "Usuario bloqueado. Intenta de nuevo en {$tiempoRestante} seg."];
        }

        if (!password_verify($password, $user['password'])) {
            $failed = $user['failed_attempts'] + 1;
            $lockTime = $_ENV['LOCK_TIME_MINUTES']+180 ?? 15;
            $maxAttempts = $_ENV['MAX_FAILED_ATTEMPTS'] ?? 3;

            if ($failed >= $maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', strtotime("+{$lockTime} minutes"));
                $userModel->updateFailedAttempts($user['id'], $failed, $lockedUntil);
                $userModel->setApiToken($user['id'], "");
                return ['error' => "Cuenta bloqueada. Intenta de nuevo después de $lockTime minutos."];
            } else {
                $userModel->updateFailedAttempts($user['id'], $failed);
                return ['error' => "Credenciales inválidas. Intentos fallidos: $failed"];
            }
        }

        // Reseteamos los intentos fallidos
        $userModel->updateFailedAttempts($user['id'], 0, null);

        // Generar el token JWT
        $token = JwtHelper::generateToken($user['id'], $user['username'], $user['email']);

        // NUEVO: Guardar el token en la base de datos
        $userModel->setApiToken($user['id'], $token);

        // Enviar el token en la cabecera de la respuesta (opcional)
        header("Authorization: Bearer " . $token);

        return [
            'message' => 'Login correcto'
        ];
    }

    // Perfil: retorna información del usuario basándose en el token decodificado
    public function profile($decodedToken)
    {
        return [
            'message'  => 'Perfil de usuario',
            'user_id'  => $decodedToken->id,
            'username' => $decodedToken->username,
            'email'    => $decodedToken->email
        ];
    }

    // Olvidé mi contraseña: genera token de recuperación y envía email real
    public function forgotPassword($data)
    {
        $email = $data['email'] ?? null;
        if (!$email) {
            return ['error' => 'Falta el email'];
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if (!$user) {
            return ['error' => 'No existe un usuario con ese email'];
        }

        // Generar token único para resetear contraseña
        $resetToken = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $ok = $userModel->setResetToken($user['id'], $resetToken, $expires);
        if ($ok) {
            $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password_form.php?token=" . $resetToken;
            $subject = "Recuperación de contraseña";
            $body = ResetPasswordEmail::getBody($user['username'], $resetLink);
            $emailSender = new EmailSender();
            $sendResult = $emailSender->sendEmail($email, $subject, $body);
            if ($sendResult === true) {
                return ['message' => 'Se generó el token de recuperación y se envió el email'];
            } else {
                return ['error' => $sendResult];
            }
        }
        return ['error' => 'No se pudo generar el token'];
    }

    // Resetear contraseña: recibe el token de recuperación (del header Bearer) y el nuevo password
    public function resetPassword($resetToken, $newPassword)
    {
        if (!$resetToken || !$newPassword) {
            return ['error' => 'Faltan token y/o new_password'];
        }

        $userModel = new User();
        $user = $userModel->findByResetToken($resetToken);
        if (!$user) {
            return ['error' => 'Token inválido o expirado'];
        }

        // Actualizar la contraseña
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $ok = $userModel->updatePassword($user['id'], $hash);

        if ($ok) {
            // Esto elimina el bloqueo al poner intentos fallidos en 0 y locked_until en NULL
            $userModel->updateFailedAttempts($user['id'], 0, null);

            // Limpiar el token de recuperación
            $userModel->setResetToken($user['id'], null, null);

            return ['message' => 'Contraseña actualizada con éxito'];
        }
        return ['error' => 'No se pudo actualizar la contraseña'];
    }
}
