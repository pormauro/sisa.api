<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Status;
use App\Helpers\JwtHelper;

class StatusController {

    // Lista todos los statuses
    public function listStatuses(Request $request, Response $response, array $args): Response {
        // Verificar token de autorización
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Falta header Authorization'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $statusModel = new Status();
        $statuses = $statusModel->getAll();
        $data = ['statuses' => $statuses];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Obtiene un status por su ID
    public function getStatus(Request $request, Response $response, array $args): Response {
        $statusId = $args['id'] ?? null;
        if (!$statusId) {
            $data = ['error' => 'Se requiere el ID del status'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Verificar token
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Falta header Authorization'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        $statusModel = new Status();
        $status = $statusModel->getById($statusId);
        if (!$status) {
            $data = ['error' => 'Status no encontrado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $data = ['status' => $status];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Crea un nuevo status
    public function addStatus(Request $request, Response $response, array $args): Response {
        // Verificar token
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Falta header Authorization'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $body = json_decode($request->getBody()->getContents(), true);
        // Campos requeridos: label, value, background_color, order_index
        if (!isset($body['label']) || !isset($body['value']) || !isset($body['background_color']) || !isset($body['order_index'])) {
            $data = ['error' => 'Faltan campos requeridos'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $statusModel = new Status();
        $newStatusId = $statusModel->create($body);
        if (!$newStatusId) {
            $data = ['error' => 'Error al crear el status'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        $data = ['message' => 'Status creado correctamente', 'status_id' => $newStatusId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Actualiza un status existente
    public function updateStatus(Request $request, Response $response, array $args): Response {
        $statusId = $args['id'] ?? null;
        if (!$statusId) {
            $data = ['error' => 'Se requiere el ID del status'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Verificar token
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Falta header Authorization'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['label']) || !isset($body['value']) || !isset($body['background_color']) || !isset($body['order_index'])) {
            $data = ['error' => 'Faltan campos requeridos'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $statusModel = new Status();
        $existingStatus = $statusModel->getById($statusId);
        if (!$existingStatus) {
            $data = ['error' => 'Status no encontrado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $updateResult = $statusModel->update($statusId, $body);
        if (!$updateResult) {
            $data = ['error' => 'Error al actualizar el status'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        $data = ['message' => 'Status actualizado correctamente'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Elimina un status
    public function deleteStatus(Request $request, Response $response, array $args): Response {
        $statusId = $args['id'] ?? null;
        if (!$statusId) {
            $data = ['error' => 'Se requiere el ID del status'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Verificar token
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Falta header Authorization'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $statusModel = new Status();
        $existingStatus = $statusModel->getById($statusId);
        if (!$existingStatus) {
            $data = ['error' => 'Status no encontrado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $deleteResult = $statusModel->delete($statusId);
        if (!$deleteResult) {
            $data = ['error' => 'Error al eliminar el status'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        $data = ['message' => 'Status eliminado correctamente'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Reordena los statuses según el arreglo enviado
    public function reorderStatuses(Request $request, Response $response, array $args): Response {
        // Verificar token
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Falta header Authorization'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Se espera recibir un arreglo con los IDs en el nuevo orden
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['ordered_ids']) || !is_array($body['ordered_ids'])) {
            $data = ['error' => 'Campo ordered_ids faltante o inválido'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $statusModel = new Status();
        $statusModel->reorder($body['ordered_ids']);
        $data = ['message' => 'Statuses reordenados correctamente'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
?>
