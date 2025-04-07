<?php
namespace App\Controllers;

use App\Models\UserProfile;
use App\History\UserProfileHistory;
use App\Helpers\JwtHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserProfileController {
    
    public function myProfile(Request $request, Response $response, array $args): Response {
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $result = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        // Obtiene el perfil del usuario autenticado
        $userId = $decoded->id;
        $profileModel = new UserProfile();
        $profile = $profileModel->findByUserId($userId);
        
        if (!$profile) {
            $result = ['error' => 'Perfil no encontrado'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode(['profile' => $profile]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // GET /user_profile -> Lista todos los perfiles (sin restricción)
    public function listProfiles(Request $request, Response $response, array $args): Response {
        // Se requiere token válido (aunque la consulta sea libre)
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !JwtHelper::verifyToken(str_replace('Bearer ', '', $authHeader))) {
            $data = ['error' => 'Token inválido o ausente'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $profileModel = new UserProfile();
        $profiles = $profileModel->listAll();
        $response->getBody()->write(json_encode(['profiles' => $profiles]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // GET /user_profile/{id} -> Devuelve el perfil específico (sin restricción de propietario)
    public function getProfile(Request $request, Response $response, array $args): Response {
        $profileId = $args['id'] ?? null;
        if (!$profileId) {
            $data = ['error' => 'Falta el ID del perfil'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Se requiere token válido
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !JwtHelper::verifyToken(str_replace('Bearer ', '', $authHeader))) {
            $data = ['error' => 'Token inválido o ausente'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $profileModel = new UserProfile();
        $profile = $profileModel->findByUserId($profileId);
        if (!$profile) {
            $data = ['error' => 'Perfil no encontrado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['profile' => $profile]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /user_profile -> Crea el perfil para el usuario autenticado (se toma el ID del token)
    public function addProfile(Request $request, Response $response, array $args): Response {
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $result = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $data = json_decode($request->getBody()->getContents(), true);
        if (empty($data['full_name'])) {
            $result = ['error' => 'El campo full_name es obligatorio'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $profileModel = new UserProfile();
        if ($profileModel->findByUserId($userId)) {
            $result = ['error' => 'Ya existe un perfil para este usuario'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $data['user_id'] = $userId;
        $newProfileId = $profileModel->create($data);
        if (!$newProfileId) {
            $result = ['error' => 'Error al crear el perfil'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial
        $history = new UserProfileHistory();
        $history->insertHistory(
            $newProfileId,
            $userId,
            $data['full_name'],
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['cuit'] ?? '',
            $data['profile_file_id'] ?? null,
            $userId,
            'CREATION'
        );
        $result = ['message' => 'Perfil creado exitosamente', 'profile_id' => $newProfileId];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // PUT /user_profile -> Actualiza el perfil del usuario autenticado (se toma el ID del token)
    public function updateProfile(Request $request, Response $response, array $args): Response {
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $result = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $data = json_decode($request->getBody()->getContents(), true);
        if (empty($data['full_name'])) {
            $result = ['error' => 'El campo full_name es obligatorio'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $profileModel = new UserProfile();
        $existingProfile = $profileModel->findByUserId($userId);
        if (!$existingProfile) {
            $result = ['error' => 'Perfil no encontrado'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $updateResult = $profileModel->update($existingProfile['id'], $data);
        if (!$updateResult) {
            $result = ['error' => 'Error al actualizar el perfil'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial
        $history = new UserProfileHistory();
        $history->insertHistory(
            $existingProfile['id'],
            $userId,
            $data['full_name'],
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['cuit'] ?? '',
            $data['profile_file_id'] ?? null,
            $userId,
            'UPDATE'
        );
        $result = ['message' => 'Perfil actualizado exitosamente'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // DELETE /user_profile -> Elimina el perfil del usuario autenticado (se toma el ID del token)
    public function deleteProfile(Request $request, Response $response, array $args): Response {
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $result = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $profileModel = new UserProfile();
        $existingProfile = $profileModel->findByUserId($userId);
        if (!$existingProfile) {
            $result = ['error' => 'Perfil no encontrado'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $deleteResult = $profileModel->delete($existingProfile['id']);
        if (!$deleteResult) {
            $result = ['error' => 'Error al eliminar el perfil'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial
        $history = new UserProfileHistory();
        $history->insertHistory(
            $existingProfile['id'],
            $userId,
            $existingProfile['full_name'],
            $existingProfile['phone'],
            $existingProfile['address'],
            $existingProfile['cuit'],
            $existingProfile['profile_file_id'],
            $userId,
            'DELETION'
        );
        $result = ['message' => 'Perfil eliminado exitosamente'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
