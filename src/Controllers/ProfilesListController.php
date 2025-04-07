<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\JwtHelper;
use App\Models\User;

class ProfilesListController {
    public function listAllProfiles(Request $request, Response $response, array $args): Response {
        // Limpiar el buffer de salida para evitar contenido previo
        if (ob_get_contents()) {
            ob_clean();
        }

        // Obtener el token, si se envía, y decodificarlo para obtener el ID del usuario actual
        $auth = $request->getHeaderLine('Authorization');
        $currentUserId = null;
        if ($auth) {
            $token = str_replace('Bearer ', '', $auth);
            $decoded = JwtHelper::verifyToken($token);
            if ($decoded && isset($decoded->id)) {
                $currentUserId = $decoded->id;
            }
        }

        // Instanciar el modelo y obtener los perfiles
        $userModel = new User();
        $profiles = $userModel->getAllProfiles();

        // Filtrar para que no se incluya el perfil del usuario actual
        if ($currentUserId !== null) {
            $profiles = array_filter($profiles, function($profile) use ($currentUserId) {
                return $profile['id'] != $currentUserId;
            });
        }

        // Reindexar el array para devolver una lista limpia
        $data = ['profiles' => array_values($profiles)];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
