<?php
namespace App\Controllers;

use App\Models\Permission;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PermissionsController {

    // Método para listar solo los permisos globales
    public function listGlobalPermissions(Request $request, Response $response, array $args): Response {
        $permissionModel = new Permission();
        $permissions = $permissionModel->listGlobal();
        $result = ['permissions' => $permissions];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Método para listar todos los permisos
    public function listPermissions(Request $request, Response $response, array $args): Response {
        $permissionModel = new Permission();
        $permissions = $permissionModel->listAll();
        $result = ['permissions' => $permissions];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Método para listar permisos filtrados por usuario (pasando el user_id por la URL)
    public function listPermissionsByUser(Request $request, Response $response, array $args): Response {
        $userId = $args['user_id'] ?? null;
        if (!$userId) {
            $result = ['error' => 'Falta el parámetro user_id'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $permissionModel = new Permission();
        $permissions = $permissionModel->listByUser($userId);
        $result = ['permissions' => $permissions];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
            
    public function addPermission(Request $request, Response $response, array $args): Response {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!isset($data['sector'])) {
            $result = ['error' => 'El campo sector es obligatorio'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        $permissionModel = new Permission();
        $assignedUserId = $data['user_id'] ?? null;
        
        // Verificar si ya existe el permiso para evitar duplicados
        if ($permissionModel->exists($assignedUserId, $data['sector'])) {
            $result = ['error' => 'El permiso ya existe para este usuario'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        // Crear el permiso
        $insertId = $permissionModel->create($data);
        
        // Obtener el token desde el header y decodificarlo para extraer el id del usuario que realiza la acción
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = \App\Helpers\JwtHelper::verifyToken($token);
        $tokenUserId = $decoded ? $decoded->id : null;
        
        if ($insertId) {
            // Registrar en historial la operación de creación con el id extraído del token
            $permissionHistory = new \App\History\PermissionsHistory();
            $permissionHistory->insertHistory(
                $insertId,        // permissionId
                $assignedUserId,  // userId (puede ser null para permisos globales)
                $data['sector'],  // sector
                $tokenUserId,     // changedBy (ID del usuario que hace la modificación)
                'CREATION'        // operationType
            );
            
            $result = ['message' => 'Permiso creado exitosamente', 'permission_id' => $insertId];
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $result = ['error' => 'Error al crear el permiso'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
    }
    
    public function deletePermission(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $result = ['error' => 'Falta el ID del permiso'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        $permissionModel = new Permission();
        
        // Obtener detalles del permiso a eliminar
        $permissionDetails = $permissionModel->getById($id);
        if (!$permissionDetails) {
            $result = ['error' => 'Permiso no encontrado'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        // Proceder a eliminar el permiso
        $deleted = $permissionModel->delete($id);
        if ($deleted) {
            // Extraer el id del usuario que realiza la acción desde el token
            $authHeader = $request->getHeaderLine('Authorization');
            $token = str_replace('Bearer ', '', $authHeader);
            $decoded = \App\Helpers\JwtHelper::verifyToken($token);
            $tokenUserId = $decoded ? $decoded->id : null;
            
            // Registrar en historial la operación de eliminación
            $permissionHistory = new \App\History\PermissionsHistory();
            $permissionHistory->insertHistory(
                $id,                              // permissionId
                $permissionDetails['user_id'],    // userId del permiso (puede ser null para global)
                $permissionDetails['sector'],     // sector
                $tokenUserId,                     // changedBy (ID del usuario que hace la modificación)
                'DELETION'                        // operationType
            );
            
            $result = ['message' => 'Permiso eliminado exitosamente'];
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $result = ['error' => 'Error al eliminar el permiso'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
    }

}
