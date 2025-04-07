<?php
namespace App\Mail\Templates;

class ActivationEmail {
    /**
     * Retorna el cuerpo del email para activar la cuenta.
     *
     * @param string $username
     * @param string $activationLink
     * @return string HTML del email
     */
    public static function getBody($username, $activationLink) {
        return "
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Activa tu cuenta</title>
            </head>
            <body>
                <p>Hola {$username},</p>
                <p>Gracias por registrarte. Para activar tu cuenta, por favor haz clic en el siguiente enlace:</p>
                <p><a href='{$activationLink}'>Activar Cuenta</a></p>
                <p>Si no te registraste en nuestro sistema, ignora este mensaje.</p>
            </body>
            </html>
        ";
    }
}
