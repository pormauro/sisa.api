<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Sales;
use App\History\SalesHistory;
use App\Helpers\JwtHelper;

class SalesController {

    // Lista todas las ventas para el usuario autenticado
    public function listSales(Request $request, Response $response, array $args): Response {
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
        $salesModel = new Sales();
        $sales = $salesModel->listAllByUser($userId);
        $data = ['sales' => $sales];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Obtiene una venta por su ID (verificando que pertenezca al usuario)
    public function getSale(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Sale ID required'];
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
        $salesModel = new Sales();
        $sale = $salesModel->findById($id);
    /*    if (!$sale || $sale['user_id'] != $userId) {
            $data = ['error' => 'Sale not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }*/
        $data = ['sale' => $sale];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Crea una nueva venta para el usuario autenticado
    public function addSale(Request $request, Response $response, array $args): Response {
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
        // Campos requeridos: client_id, product_service_id, invoice_number, amount
        if (!isset($body['client_id'], $body['product_service_id'], $body['invoice_number'], $body['amount'])) {
            $data = ['error' => 'Missing required fields: client_id, product_service_id, invoice_number, amount'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $body['user_id'] = $userId;
        // Opcionales:
        $body['folder_id'] = $body['folder_id'] ?? null;
        $body['sale_date'] = $body['sale_date'] ?? date('Y-m-d H:i:s');
        $body['attached_files'] = $body['attached_files'] ?? null;
        
        $salesModel = new Sales();
        $newSaleId = $salesModel->create($body);
        if (!$newSaleId) {
            $data = ['error' => 'Error creating sale'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        // Registrar historial de CREATION
        $history = new SalesHistory();
        $history->insertHistory(
            $newSaleId,
            $userId,
            $body['client_id'],
            $body['product_service_id'],
            $body['folder_id'],
            $body['invoice_number'],
            $body['amount'],
            $body['sale_date'],
            $body['attached_files'],
            $userId,
            'CREATION'
        );
        
        $data = ['message' => 'Sale created successfully', 'sale_id' => $newSaleId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Actualiza una venta; verifica que pertenezca al usuario autenticado
    public function updateSale(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Sale ID required'];
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
        $salesModel = new Sales();
        $existingSale = $salesModel->findById($id);
    /*    if (!$existingSale || $existingSale['user_id'] != $userId) {
            $data = ['error' => 'Sale not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }*/
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['client_id'], $body['product_service_id'], $body['invoice_number'], $body['amount'])) {
            $data = ['error' => 'Missing required fields: client_id, product_service_id, invoice_number, amount'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $body['folder_id'] = $body['folder_id'] ?? null;
        $body['sale_date'] = $body['sale_date'] ?? date('Y-m-d H:i:s');
        $body['attached_files'] = $body['attached_files'] ?? null;
        $updateResult = $salesModel->update($id, $body);
        if (!$updateResult) {
            $data = ['error' => 'Error updating sale'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de UPDATE
        $history = new SalesHistory();
        $history->insertHistory(
            $id,
            $userId,
            $body['client_id'],
            $body['product_service_id'],
            $body['folder_id'],
            $body['invoice_number'],
            $body['amount'],
            $body['sale_date'],
            $body['attached_files'],
            $userId,
            'UPDATE'
        );
        $data = ['message' => 'Sale updated successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Elimina una venta (registrando historial previo)
    public function deleteSale(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Sale ID required'];
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
        $salesModel = new Sales();
        $existingSale = $salesModel->findById($id);
   /*     if (!$existingSale || $existingSale['user_id'] != $userId) {
            $data = ['error' => 'Sale not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }*/
        // Registrar historial de DELETION antes de eliminar
        $history = new SalesHistory();
        $history->insertHistory(
            $id,
            $userId,
            $existingSale['client_id'],
            $existingSale['product_service_id'],
            $existingSale['folder_id'],
            $existingSale['invoice_number'],
            $existingSale['amount'],
            $existingSale['sale_date'],
            $existingSale['attached_files'],
            $userId,
            'DELETION'
        );
        $deleteResult = $salesModel->delete($id);
        if (!$deleteResult) {
            $data = ['error' => 'Error deleting sale'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        $data = ['message' => 'Sale deleted successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Lista el historial de una venta por su ID
    public function listSaleHistory(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Sale ID required'];
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
        $salesModel = new Sales();
        $sale = $salesModel->findById($id);
    /*    if (!$sale || $sale['user_id'] != $userId) {
            $data = ['error' => 'Sale not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }*/
        $history = new SalesHistory();
        $historyRecords = $history->listHistoryBySaleId($id);
        $data = ['history' => $historyRecords];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
