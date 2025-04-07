<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\AccountingClosings;
use App\History\AccountingClosingsHistory;
use App\Helpers\JwtHelper;

class AccountingClosingsController {

    // Lista todos los cierres contables del usuario autenticado
    public function listClosings(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $closingsModel = new AccountingClosings();
        $closings = $closingsModel->listAllByUser($userId);
        $data = ['accounting_closings' => $closings];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Obtiene un cierre contable por su ID (verificando que pertenezca al usuario)
    public function getClosing(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Closing ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $closingsModel = new AccountingClosings();
        $closing = $closingsModel->findById($id);
        if(!$closing || $closing['user_id'] != $userId){
            $data = ['error' => 'Closing not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $data = ['accounting_closing' => $closing];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Crea un nuevo cierre contable para el usuario autenticado
    public function addClosing(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $body = json_decode($request->getBody()->getContents(), true);
        // Campos requeridos: cash_box_id, closing_date, final_balance, total_income, total_expenses
        if (!isset($body['cash_box_id'], $body['closing_date'], $body['final_balance'], $body['total_income'], $body['total_expenses'])) {
            $data = ['error' => 'Missing required fields: cash_box_id, closing_date, final_balance, total_income, total_expenses'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $body['user_id'] = $userId;
        $body['comments'] = $body['comments'] ?? null;
        
        $closingsModel = new AccountingClosings();
        $newClosingId = $closingsModel->create($body);
        if(!$newClosingId){
            $data = ['error' => 'Error creating closing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
        // Registrar historial de CREATION
        $history = new AccountingClosingsHistory();
        $history->insertHistory(
            $newClosingId,
            $userId,
            $body['cash_box_id'],
            $body['closing_date'],
            $body['final_balance'],
            $body['total_income'],
            $body['total_expenses'],
            $body['comments'],
            $userId,
            'CREATION'
        );
        
        $data = ['message' => 'Accounting closing created successfully', 'closing_id' => $newClosingId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Actualiza un cierre contable; verifica que pertenezca al usuario autenticado
    public function updateClosing(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Closing ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $closingsModel = new AccountingClosings();
        $existingClosing = $closingsModel->findById($id);
        if(!$existingClosing || $existingClosing['user_id'] != $userId){
            $data = ['error' => 'Closing not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['cash_box_id'], $body['closing_date'], $body['final_balance'], $body['total_income'], $body['total_expenses'])) {
            $data = ['error' => 'Missing required fields: cash_box_id, closing_date, final_balance, total_income, total_expenses'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $body['comments'] = $body['comments'] ?? null;
        $updateResult = $closingsModel->update($id, $body);
        if(!$updateResult){
            $data = ['error' => 'Error updating closing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de UPDATE
        $history = new AccountingClosingsHistory();
        $history->insertHistory(
            $id,
            $userId,
            $body['cash_box_id'],
            $body['closing_date'],
            $body['final_balance'],
            $body['total_income'],
            $body['total_expenses'],
            $body['comments'],
            $userId,
            'UPDATE'
        );
        $data = ['message' => 'Accounting closing updated successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Elimina un cierre contable; registra historial previo
    public function deleteClosing(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Closing ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $closingsModel = new AccountingClosings();
        $existingClosing = $closingsModel->findById($id);
        if(!$existingClosing || $existingClosing['user_id'] != $userId){
            $data = ['error' => 'Closing not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de DELETION antes de eliminar
        $history = new AccountingClosingsHistory();
        $history->insertHistory(
            $id,
            $userId,
            $existingClosing['cash_box_id'],
            $existingClosing['closing_date'],
            $existingClosing['final_balance'],
            $existingClosing['total_income'],
            $existingClosing['total_expenses'],
            $existingClosing['comments'],
            $userId,
            'DELETION'
        );
        $deleteResult = $closingsModel->delete($id);
        if(!$deleteResult){
            $data = ['error' => 'Error deleting closing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        $data = ['message' => 'Accounting closing deleted successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Lista el historial de un cierre contable por su ID
    public function listClosingHistory(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Closing ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $closingsModel = new AccountingClosings();
        $closing = $closingsModel->findById($id);
        if(!$closing || $closing['user_id'] != $userId){
            $data = ['error' => 'Closing not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $history = new AccountingClosingsHistory();
        $historyRecords = $history->listHistoryByClosingId($id);
        $data = ['history' => $historyRecords];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
