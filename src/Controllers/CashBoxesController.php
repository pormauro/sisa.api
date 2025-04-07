<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\CashBoxes;
use App\History\CashBoxesHistory;
use App\Helpers\JwtHelper;

class CashBoxesController {

    // Lista todas las cajas para el usuario autenticado
    public function listCashBoxes(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $cashBoxesModel = new CashBoxes();
        $cashBoxes = $cashBoxesModel->listAll();
        $data = ['cash_boxes' => $cashBoxes];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Obtiene una caja por su ID (verifica que pertenezca al usuario)
    public function getCashBox(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Cash box ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $cashBoxesModel = new CashBoxes();
        $cashBox = $cashBoxesModel->findById($id);
        if (!$cashBox || $cashBox['user_id'] != $userId) {
            $data = ['error' => 'Cash box not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $data = ['cash_box' => $cashBox];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Crea una nueva caja para el usuario autenticado
    public function addCashBox(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['name'])) {
            $data = ['error' => 'Missing required field: name'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $body['user_id'] = $userId;
        $body['image_file_id'] = $body['image_file_id'] ?? null;
        
        $cashBoxesModel = new CashBoxes();
        $newCashBoxId = $cashBoxesModel->create($body);
        if (!$newCashBoxId) {
            $data = ['error' => 'Error creating cash box'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        // Registrar historial de CREATION
        $history = new CashBoxesHistory();
        $history->insertHistory(
            $newCashBoxId,
            $userId,
            $body['name'],
            $body['image_file_id'],
            $userId,
            'CREATION'
        );
        
        $data = ['message' => 'Cash box created successfully', 'cash_box_id' => $newCashBoxId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Actualiza una caja (se verifica que pertenezca al usuario autenticado)
    public function updateCashBox(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Cash box ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $cashBoxesModel = new CashBoxes();
        $existingCashBox = $cashBoxesModel->findById($id);
        if (!$existingCashBox || $existingCashBox['user_id'] != $userId) {
            $data = ['error' => 'Cash box not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['name'])) {
            $data = ['error' => 'Missing required field: name'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $body['image_file_id'] = $body['image_file_id'] ?? null;
        $updateResult = $cashBoxesModel->update($id, $body);
        if (!$updateResult) {
            $data = ['error' => 'Error updating cash box'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de UPDATE
        $history = new CashBoxesHistory();
        $history->insertHistory(
            $id,
            $userId,
            $body['name'],
            $body['image_file_id'],
            $userId,
            'UPDATE'
        );
        $data = ['message' => 'Cash box updated successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Elimina una caja (registrando historial previo)
    public function deleteCashBox(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Cash box ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $cashBoxesModel = new CashBoxes();
        $existingCashBox = $cashBoxesModel->findById($id);
        if (!$existingCashBox || $existingCashBox['user_id'] != $userId) {
            $data = ['error' => 'Cash box not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de DELETION antes de eliminar
        $history = new CashBoxesHistory();
        $history->insertHistory(
            $id,
            $userId,
            $existingCashBox['name'],
            $existingCashBox['image_file_id'],
            $userId,
            'DELETION'
        );
        $deleteResult = $cashBoxesModel->delete($id);
        if (!$deleteResult) {
            $data = ['error' => 'Error deleting cash box'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        $data = ['message' => 'Cash box deleted successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Lista el historial de una caja por su ID
    public function listCashBoxHistory(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Cash box ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $cashBoxesModel = new CashBoxes();
        $cashBox = $cashBoxesModel->findById($id);
        if (!$cashBox || $cashBox['user_id'] != $userId) {
            $data = ['error' => 'Cash box not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $history = new CashBoxesHistory();
        $historyRecords = $history->listHistoryByCashBoxId($id);
        $data = ['history' => $historyRecords];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
