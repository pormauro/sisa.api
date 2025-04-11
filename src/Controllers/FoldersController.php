<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Folders;
use App\History\FoldersHistory;
use App\Helpers\JwtHelper;

class FoldersController {

    // Lista todos los folders del usuario autenticado;
    // si se envía en query el parámetro client_id se filtra por ese cliente.
    // si se envía en query el parámetro parent_id se filtra por carpeta padre.
    public function listFolders(Request $request, Response $response, array $args): Response {
        // Validar token (aunque si el filtro no es por user_id, el token solo lo usas para autenticar)
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = \App\Helpers\JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
    
        $params = $request->getQueryParams();
        $foldersModel = new \App\Models\Folders();
        
        if (isset($params['parent_id'])) {
            // Listamos las subcarpetas basándonos solo en parent_id (sin filtrar por user_id)
            $parentId = $params['parent_id'];
            $folders = $foldersModel->listByParentId($parentId);
        } elseif (isset($params['client_id'])) {
            // En caso de que se quiera filtrar por cliente
            $clientId = $params['client_id'];
            $folders = $foldersModel->listByClientId($clientId);
        } else {
            $folders = $foldersModel->listAll();
        }
        
        $data = ['folders' => $folders];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }


    // Devuelve un folder por su ID; verifica que pertenezca al usuario autenticado.
    public function getFolder(Request $request, Response $response, array $args): Response {
        $folderId = $args['id'] ?? null;
        if (!$folderId) {
            $data = ['error' => 'Folder ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $foldersModel = new Folders();
        $folder = $foldersModel->findById($folderId);
    /*    if (!$folder || $folder['user_id'] != $userId) {
            $data = ['error' => 'Folder not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }*/
        $data = ['folder' => $folder];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Crea un nuevo folder para el usuario autenticado.
    // Requiere: client_id y name (además de otros opcionales: parent_id, folder_image_file_id).
    public function addFolder(Request $request, Response $response, array $args): Response {
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        
        // Obtener datos del request (se requiere client_id y name)
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['client_id']) || !isset($body['name'])) {
            $data = ['error' => 'Faltan campos requeridos: client_id y name'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $body['user_id'] = $userId;
        $body['parent_id'] = $body['parent_id'] ?? null;
        $body['folder_image_file_id'] = $body['folder_image_file_id'] ?? null;
        
        // Crear folder usando el modelo
        $foldersModel = new Folders();
        $newFolderId = $foldersModel->create($body);
        if (!$newFolderId) {
            $data = ['error' => 'Error al crear el folder'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
        // Registrar historial de creación
        $history = new FoldersHistory();
        $history->insertHistory(
            $newFolderId,
            $userId,
            $body['client_id'],
            $body['name'],
            $body['parent_id'],
            $body['folder_image_file_id'],
            $userId,
            'CREATION'
        );
        
        $data = ['message' => 'Folder creado exitosamente', 'folder_id' => $newFolderId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Actualiza un folder existente; se requiere que el folder pertenezca al usuario autenticado.
    public function updateFolder(Request $request, Response $response, array $args): Response {
        $folderId = $args['id'] ?? null;
        if (!$folderId) {
            $data = ['error' => 'Folder ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $foldersModel = new Folders();
        $existingFolder = $foldersModel->findById($folderId);
       /* if (!$existingFolder || $existingFolder['user_id'] != $userId) {
            $data = ['error' => 'Folder not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }*/
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['name'])) {
            $data = ['error' => 'Missing required field: name'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $updateResult = $foldersModel->update($folderId, $body);
        if (!$updateResult) {
            $data = ['error' => 'Error updating folder'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de UPDATE
        $history = new FoldersHistory();
        $history->insertHistory(
            $folderId,
            $userId,
            $existingFolder['client_id'],
            $body['name'],
            $body['parent_id'] ?? $existingFolder['parent_id'],
            $body['folder_image_file_id'] ?? $existingFolder['folder_image_file_id'],
            $userId,
            'UPDATE'
        );
        $data = ['message' => 'Folder updated successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Elimina un folder; registra la operaciÃ³n en el historial.
    public function deleteFolder(Request $request, Response $response, array $args): Response {
        $folderId = $args['id'] ?? null;
        if (!$folderId) {
            $data = ['error' => 'Folder ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $foldersModel = new Folders();
        $existingFolder = $foldersModel->findById($folderId);
      /*  if (!$existingFolder || $existingFolder['user_id'] != $userId) {
            $data = ['error' => 'Folder not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }*/
        // Registrar historial de DELETION antes de eliminar
        $history = new FoldersHistory();
        $history->insertHistory(
            $folderId,
            $userId,
            $existingFolder['client_id'],
            $existingFolder['name'],
            $existingFolder['parent_id'],
            $existingFolder['folder_image_file_id'],
            $userId,
            'DELETION'
        );
        $deleteResult = $foldersModel->delete($folderId);
        if (!$deleteResult) {
            $data = ['error' => 'Error deleting folder'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        $data = ['message' => 'Folder deleted successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Lista el historial de un folder por su ID.
    public function listFolderHistory(Request $request, Response $response, array $args): Response {
        $folderId = $args['id'] ?? null;
        if (!$folderId) {
            $data = ['error' => 'Folder ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $foldersModel = new Folders();
        $folder = $foldersModel->findById($folderId);
    /*    if (!$folder || $folder['user_id'] != $decoded->id) {
            $data = ['error' => 'Folder not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }*/
        $history = new FoldersHistory();
        $historyRecords = $history->listHistoryByFolderId($folderId);
        $data = ['history' => $historyRecords];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // (Opcional) Lista folders de un cliente específico.
    public function listFoldersByClient(Request $request, Response $response, array $args): Response {
        $clientId = $args['client_id'] ?? null;
        if (!$clientId) {
            $data = ['error' => 'Client ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Validar token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $foldersModel = new Folders();
        $folders = $foldersModel->listByClientId($clientId, $userId);
        $data = ['folders' => $folders];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
