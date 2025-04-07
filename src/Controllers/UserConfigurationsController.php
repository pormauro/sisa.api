<?php
namespace App\Controllers;

use App\Models\UserConfigurations;
use App\History\UserConfigurationsHistory;
use App\Helpers\JwtHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserConfigurationsController {
    
    public function myConfigurations(Request $request, Response $response, array $args): Response {
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
        
        // Obtiene la configuración del usuario autenticado
        $userId = $decoded->id;
        $configModel = new UserConfigurations();
        $configuration = $configModel->findByUserId($userId);
        
        if (!$configuration) {
            $result = ['error' => 'Configuración no encontrada'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode(['configuration' => $configuration]));
        return $response->withHeader('Content-Type', 'application/json');
    }


    // GET /user_configurations -> Lista todas las configuraciones (sin restricción)
    public function listConfigurations(Request $request, Response $response, array $args): Response {
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !JwtHelper::verifyToken(str_replace('Bearer ', '', $authHeader))) {
            $data = ['error' => 'Token inválido o ausente'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $configModel = new UserConfigurations();
        $configs = $configModel->listAll();
        $response->getBody()->write(json_encode(['configurations' => $configs]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // GET /user_configurations/{id} -> Devuelve la configuración específica (sin restricción de propietario)
    public function getConfiguration(Request $request, Response $response, array $args): Response {
        $configId = $args['id'] ?? null;
        if (!$configId) {
            $data = ['error' => 'Falta el ID de la configuración'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !JwtHelper::verifyToken(str_replace('Bearer ', '', $authHeader))) {
            $data = ['error' => 'Token inválido o ausente'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $configModel = new UserConfigurations();
        $config = $configModel->findByUserId($configId);
        if (!$config) {
            $data = ['error' => 'Configuración no encontrada'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['configuration' => $config]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /user_configurations -> Crea la configuración para el usuario autenticado
    public function addConfiguration(Request $request, Response $response, array $args): Response {
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
        if (!isset($data['role'], $data['view_type'], $data['theme'], $data['font_size'])) {
            $result = ['error' => 'Faltan campos requeridos: role, view_type, theme, font_size'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $data['user_id'] = $userId;
        $configModel = new UserConfigurations();
        if ($configModel->findByUserId($userId)) {
            $result = ['error' => 'Ya existe una configuración para este usuario'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $newConfigId = $configModel->create($data);
        if (!$newConfigId) {
            $result = ['error' => 'Error al crear la configuración'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial
        $history = new UserConfigurationsHistory();
        $history->insertHistory(
            $newConfigId,
            $userId,
            $data['role'],
            $data['view_type'],
            $data['theme'],
            $data['font_size'],
            $userId,
            'CREATION'
        );
        $result = ['message' => 'Configuración creada exitosamente', 'configuration_id' => $newConfigId];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // PUT /user_configurations -> Actualiza la configuración del usuario autenticado
    public function updateConfiguration(Request $request, Response $response, array $args): Response {
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
        if (!isset($data['role'], $data['view_type'], $data['theme'], $data['font_size'])) {
            $result = ['error' => 'Faltan campos requeridos: role, view_type, theme, font_size'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $configModel = new UserConfigurations();
        $existingConfig = $configModel->findByUserId($userId);
        if (!$existingConfig) {
            $result = ['error' => 'Configuración no encontrada'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $updateResult = $configModel->update($existingConfig['id'], $data);
        if (!$updateResult) {
            $result = ['error' => 'Error al actualizar la configuración'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial
        $history = new UserConfigurationsHistory();
        $history->insertHistory(
            $existingConfig['id'],
            $userId,
            $data['role'],
            $data['view_type'],
            $data['theme'],
            $data['font_size'],
            $userId,
            'UPDATE'
        );
        $result = ['message' => 'Configuración actualizada exitosamente'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // DELETE /user_configurations -> Elimina la configuración del usuario autenticado
    public function deleteConfiguration(Request $request, Response $response, array $args): Response {
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
        $configModel = new UserConfigurations();
        $existingConfig = $configModel->findByUserId($userId);
        if (!$existingConfig) {
            $result = ['error' => 'Configuración no encontrada'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $deleteResult = $configModel->delete($existingConfig['id']);
        if (!$deleteResult) {
            $result = ['error' => 'Error al eliminar la configuración'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial
        $history = new UserConfigurationsHistory();
        $history->insertHistory(
            $existingConfig['id'],
            $userId,
            $existingConfig['role'],
            $existingConfig['view_type'],
            $existingConfig['theme'],
            $existingConfig['font_size'],
            $userId,
            'DELETION'
        );
        $result = ['message' => 'Configuración eliminada exitosamente'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
