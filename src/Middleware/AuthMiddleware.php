<?php
// Archivo: src/Middleware/AuthMiddleware.php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public function __invoke(Request $request, $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'No se proporcionó token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Se asume el formato "Bearer token..."
        $token = str_replace('Bearer ', '', $authHeader);

        try {
            // Decodificar el token utilizando la clave secreta del archivo .env
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        } catch (\Exception $e) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token inválido']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Se asume que el token contiene el ID del usuario en 'user_id'
        $userId = $decoded->user_id ?? null;
        if (!$userId) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token sin información de usuario']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Realizar consulta a la base de datos para obtener el usuario (ejemplo)
        $user = $this->getUserFromDb($userId);

        // Verificar si el usuario está bloqueado
        if ($user && isset($user['locked_until'])) {
            $lockedUntil = new \DateTime($user['locked_until']);
            $now = new \DateTime();
            if ($lockedUntil > $now) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode(['error' => 'El usuario está bloqueado']));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }
        }

        // Puedes añadir el usuario a los atributos de la solicitud para usarlo en los controladores
        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
    }

    /**
     * Función simulada para obtener un usuario de la base de datos.
     * En un caso real, se debería utilizar un modelo o capa de acceso a datos.
     */
    private function getUserFromDb($userId)
    {
        // Ejemplo simulado; reemplaza con consulta real
        // Supongamos que $user contiene un array con datos del usuario
        // Ejemplo:
        // [
        //    'id' => 1,
        //    'username' => 'usuario1',
        //    'locked_until' => '2025-03-03 12:00:00'
        // ]
        
        // Aquí deberías conectar a la base de datos y obtener la información
        // Para este ejemplo, simulamos que el usuario con id 1 está bloqueado hasta el futuro
        if ($userId == 1) {
            return [
                'id' => 1,
                'username' => 'usuario1',
                'locked_until' => '2099-01-01 00:00:00' // bloqueado indefinidamente para la demo
            ];
        }
        
        // Para otros usuarios, asumimos que no están bloqueados
        return [
            'id' => $userId,
            'username' => 'usuario' . $userId,
            'locked_until' => null
        ];
    }
}
