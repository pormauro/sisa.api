<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Clients;
use App\History\ClientsHistory;
use App\Helpers\JwtHelper;

class ClientsController {

    // Lista TODOS los clientes (sin filtrar por user_id)
    public function listClients(Request $request, Response $response, array $args): Response {
        // Verificar token del header Authorization (se usa para autenticación, pero no filtra por usuario)
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
        
        $clientsModel = new Clients();
        $clients = $clientsModel->listAll();
        $data = ['clients' => $clients];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    // Obtiene un cliente en particular
    public function getClient(Request $request, Response $response, array $args): Response {
        $clientId = $args['id'] ?? null;
        if (!$clientId) {
            $data = ['error' => 'Client ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Verificar token
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
        
        $clientsModel = new Clients();
        $client = $clientsModel->findById($clientId);
        if (!$client) {
            $data = ['error' => 'Client not found'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = ['client' => $client];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    // Crea un nuevo cliente y registra el historial (operación CREATION)
    public function addClient(Request $request, Response $response, array $args): Response {
        // Verificar token
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
        
        $body = json_decode($request->getBody()->getContents(), true);
        // Campos requeridos: business_name, tax_id, email. Los demás son opcionales.
        if (!isset($body['business_name']) || !isset($body['tax_id']) || !isset($body['email'])) {
            $data = ['error' => 'Missing required fields: business_name, tax_id, email'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Asignar el user_id del token
        $body['user_id'] = $userId;
        if (!isset($body['brand_file_id'])) {
            $body['brand_file_id'] = null;
        }
        if (!isset($body['phone'])) {
            $body['phone'] = '';
        }
        if (!isset($body['address'])) {
            $body['address'] = '';
        }
        
        $clientsModel = new Clients();
        $newClientId = $clientsModel->create($body);
        if (!$newClientId) {
            $data = ['error' => 'Error creating client'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
        // Registrar el historial de creación
        $history = new ClientsHistory();
        $history->insertHistory(
            $newClientId,
            $userId,
            $body['business_name'],
            $body['tax_id'],
            $body['email'],
            $body['brand_file_id'],
            $body['phone'],
            $body['address'],
            $userId,       // changed_by: quien realiza la acción
            'CREATION'
        );
        
        $data = ['message' => 'Client created successfully', 'client_id' => $newClientId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    // Actualiza un cliente existente y registra la operación en el historial (UPDATE)
    public function updateClient(Request $request, Response $response, array $args): Response {
        // Verificar token
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
        
        $clientId = $args['id'] ?? null;
        if (!$clientId) {
            $data = ['error' => 'Client ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $clientsModel = new Clients();
        $existingClient = $clientsModel->findById($clientId);
        if (!$existingClient) {
            $data = ['error' => 'Client not found'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['business_name']) || !isset($body['tax_id']) || !isset($body['email'])) {
            $data = ['error' => 'Missing required fields: business_name, tax_id, email'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $updateResult = $clientsModel->update($clientId, $body);
        if (!$updateResult) {
            $data = ['error' => 'Error updating client'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
        // Registrar el historial de actualización
        $history = new ClientsHistory();
        $history->insertHistory(
            $clientId,
            $existingClient['user_id'],
            $body['business_name'],
            $body['tax_id'],
            $body['email'],
            isset($body['brand_file_id']) ? $body['brand_file_id'] : null,
            $body['phone'],
            $body['address'],
            $userId,
            'UPDATE'
        );
        
        $data = ['message' => 'Client updated successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    // Elimina un cliente y registra la operación en el historial (DELETION)
    public function deleteClient(Request $request, Response $response, array $args): Response {
        // Verificar token
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
        
        $clientId = $args['id'] ?? null;
        if (!$clientId) {
            $data = ['error' => 'Client ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $clientsModel = new Clients();
        $existingClient = $clientsModel->findById($clientId);
        if (!$existingClient) {
            $data = ['error' => 'Client not found'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
     /*   if ($existingClient['user_id'] != $userId) {
            $data = ['error' => 'Access denied 3'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }*/
        
        // Registrar el historial de eliminación antes de borrar
        $history = new ClientsHistory();
        $history->insertHistory(
            $clientId,
            $existingClient['user_id'],
            $existingClient['business_name'],
            $existingClient['tax_id'],
            $existingClient['email'],
            $existingClient['brand_file_id'],
            $existingClient['phone'],
            $existingClient['address'],
            $userId,
            'DELETION'
        );
        
        $deleteResult = $clientsModel->delete($clientId);
        if (!$deleteResult) {
            $data = ['error' => 'Error deleting client'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
        $data = ['message' => 'Client deleted successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    // Lista el historial de un cliente específico
    public function listClientHistory(Request $request, Response $response, array $args): Response {
        // Verificar token
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
        
        $clientId = $args['id'] ?? null;
        if (!$clientId) {
            $data = ['error' => 'Client ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $clientsModel = new Clients();
        $client = $clientsModel->findById($clientId);
        if (!$client) {
            $data = ['error' => 'Client not found'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    /*    if ($client['user_id'] != $userId) {
            $data = ['error' => 'Access denied 4'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }*/
        
        $history = new ClientsHistory();
        $historyRecords = $history->listHistoryByClientId($clientId);
        $data = ['history' => $historyRecords];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}




