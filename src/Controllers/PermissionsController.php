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
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $permissionModel = new Permission();
        $permissions = $permissionModel->listByUser($userId);
        $result = ['permissions' => $permissions];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    // Método para agregar un permiso (ya existente)
    public function addPermission(Request $request, Response $response, array $args): Response {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!isset($data['sector'])) {
            $result = ['error' => 'El campo sector es obligatorio'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $permissionModel = new Permission();
    
        // Se valida tanto para usuarios específicos como para permisos globales (user_id null)
        // Para evitar duplicados, se verifica si ya existe el permiso
        // Nota: user_id puede venir como null o número
        $userId = $data['user_id'] ?? null;
        if ($permissionModel->exists($userId, $data['sector'])) {
            $result = ['error' => 'El permiso ya existe para este usuario'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $insertId = $permissionModel->create($data);
        if ($insertId) {
            $result = ['message' => 'Permiso creado exitosamente', 'permission_id' => $insertId];
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $result = ['error' => 'Error al crear el permiso'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }



    // Método para eliminar un permiso
    public function deletePermission(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $result = ['error' => 'Falta el ID del permiso'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $permissionModel = new Permission();
        $deleted = $permissionModel->delete($id);
        if ($deleted) {
            $result = ['message' => 'Permiso eliminado exitosamente'];
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $result = ['error' => 'Error al eliminar el permiso'];
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
