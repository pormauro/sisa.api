<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\JwtHelper;

class ProfileController {
    public function getProfile(Request $request, Response $response, array $args): Response {
        // Limpiar el buffer de salida para evitar contenido previo
        if (ob_get_contents()) {
            ob_clean();
        }

        // Verificar el header Authorization y validar el token
        $auth = $request->getHeaderLine('Authorization');
        if (!$auth) {
            $data = ['error' => 'Falta header Authorization'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $auth);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        // Si el token tiene el claim "exp", formatearlo a una fecha legible
        $expiration = isset($decoded->exp) ? date('Y-m-d H:i:s', $decoded->exp) : 'N/A';
        
        // Retornar la información del usuario y la expiración de la sesión
        $data = [
            'user' => $decoded,
            'session_expires' => $expiration
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
