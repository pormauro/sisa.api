<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Expenses;
use App\History\ExpensesHistory;
use App\Helpers\JwtHelper;

class ExpensesController {

    // Lista todos los expenses para el usuario autenticado
    public function listExpenses(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $expensesModel = new Expenses();
        $expenses = $expensesModel->listAllByUser($userId);
        $data = ['expenses' => $expenses];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Obtiene un expense por su ID (verifica que pertenezca al usuario)
    public function getExpense(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Expense ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $expensesModel = new Expenses();
        $expense = $expensesModel->findById($id);
        if(!$expense || $expense['user_id'] != $userId){
            $data = ['error' => 'Expense not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $data = ['expense' => $expense];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Crea un nuevo expense para el usuario autenticado
    public function addExpense(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $body = json_decode($request->getBody()->getContents(), true);
        // Campos requeridos: description, category, amount, invoice_number
        if (!isset($body['description'], $body['category'], $body['amount'], $body['invoice_number'])) {
            $data = ['error' => 'Missing required fields: description, category, amount, invoice_number'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $body['user_id'] = $userId;
        // Opcional: folder_id, attached_files, expense_date
        $body['folder_id'] = $body['folder_id'] ?? null;
        $body['attached_files'] = $body['attached_files'] ?? null;
        $body['expense_date'] = $body['expense_date'] ?? date('Y-m-d H:i:s');
        
        $expensesModel = new Expenses();
        $newExpenseId = $expensesModel->create($body);
        if(!$newExpenseId){
            $data = ['error' => 'Error creating expense'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        
        // Registrar historial de CREATION
        $history = new ExpensesHistory();
        $history->insertHistory(
            $newExpenseId,
            $userId,
            $body['description'],
            $body['category'],
            $body['amount'],
            $body['invoice_number'],
            $body['folder_id'],
            $body['attached_files'],
            $body['expense_date'],
            $userId,
            'CREATION'
        );
        
        $data = ['message' => 'Expense created successfully', 'expense_id' => $newExpenseId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Actualiza un expense; verifica que pertenezca al usuario autenticado
    public function updateExpense(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Expense ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $expensesModel = new Expenses();
        $existingExpense = $expensesModel->findById($id);
        if(!$existingExpense || $existingExpense['user_id'] != $userId){
            $data = ['error' => 'Expense not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['description'], $body['category'], $body['amount'], $body['invoice_number'])) {
            $data = ['error' => 'Missing required fields: description, category, amount, invoice_number'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $body['folder_id'] = $body['folder_id'] ?? null;
        $body['attached_files'] = $body['attached_files'] ?? null;
        $body['expense_date'] = $body['expense_date'] ?? date('Y-m-d H:i:s');
        $updateResult = $expensesModel->update($id, $body);
        if(!$updateResult){
            $data = ['error' => 'Error updating expense'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de UPDATE
        $history = new ExpensesHistory();
        $history->insertHistory(
            $id,
            $userId,
            $body['description'],
            $body['category'],
            $body['amount'],
            $body['invoice_number'],
            $body['folder_id'],
            $body['attached_files'],
            $body['expense_date'],
            $userId,
            'UPDATE'
        );
        $data = ['message' => 'Expense updated successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Elimina un expense; registra historial previo
    public function deleteExpense(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Expense ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $expensesModel = new Expenses();
        $existingExpense = $expensesModel->findById($id);
        if(!$existingExpense || $existingExpense['user_id'] != $userId){
            $data = ['error' => 'Expense not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de DELETION antes de eliminar
        $history = new ExpensesHistory();
        $history->insertHistory(
            $id,
            $userId,
            $existingExpense['description'],
            $existingExpense['category'],
            $existingExpense['amount'],
            $existingExpense['invoice_number'],
            $existingExpense['folder_id'],
            $existingExpense['attached_files'],
            $existingExpense['expense_date'],
            $userId,
            'DELETION'
        );
        $deleteResult = $expensesModel->delete($id);
        if(!$deleteResult){
            $data = ['error' => 'Error deleting expense'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json');
        }
        $data = ['message' => 'Expense deleted successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Lista el historial de un expense por su ID
    public function listExpenseHistory(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Expense ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $expensesModel = new Expenses();
        $expense = $expensesModel->findById($id);
        if(!$expense || $expense['user_id'] != $userId){
            $data = ['error' => 'Expense not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json');
        }
        $history = new ExpensesHistory();
        $historyRecords = $history->listHistoryByExpenseId($id);
        $data = ['history' => $historyRecords];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
