<?php
namespace App\Mail\Templates;

class ResetPasswordEmail {
    /**
     * Retorna el cuerpo del email para restablecer la contraseña.
     *
     * @param string $username
     * @param string $resetLink
     * @return string HTML del email
     */
    public static function getBody($username, $resetLink) {
        return "
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Resetear Contraseña</title>
            </head>
            <body>
                <p>Hola {$username},</p>
                <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                <p>Por favor, haz clic en el siguiente enlace para acceder a la página donde podrás ingresar tu nueva contraseña de forma segura:</p>
                <p><a href='{$resetLink}'>Restablecer Contraseña</a></p>
                <p>Si no solicitaste este cambio, ignora este mensaje.</p>
            </body>
            </html>
        ";
    }
}
