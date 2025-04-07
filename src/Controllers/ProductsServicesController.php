<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ProductsServices;
use App\History\ProductsServicesHistory;
use App\Helpers\JwtHelper;

class ProductsServicesController {

    // Lista todos los productos/servicios para el usuario autenticado
    public function listProductsServices(Request $request, Response $response, array $args): Response {
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
        $psModel = new ProductsServices();
        $records = $psModel->listAll();
        $data = ['products_services' => $records];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Devuelve un producto/servicio por su ID (verificando que pertenezca al usuario)
    public function getProductService(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Product/Service ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
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
        $psModel = new ProductsServices();
        $record = $psModel->findById($id);
        if (!$record || $record['user_id'] != $userId) {
            $data = ['error' => 'Record not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $data = ['product_service' => $record];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Crea un nuevo producto/servicio para el usuario autenticado
    public function addProductService(Request $request, Response $response, array $args): Response {
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
        // Campos requeridos: description, category, price, cost, difficulty, item_type, stock
        // if (!isset($body['description'], $body['category'], $body['price'], $body['cost'], $body['difficulty'], $body['item_type'], $body['stock'])) {
        //     $data = ['error' => 'Missing required fields: description, category, price, cost, difficulty, item_type, stock'];
        //     $response->getBody()->write(json_encode($data));
        //     return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        // }
        $body['user_id'] = $userId;
        $body['product_image_file_id'] = $body['product_image_file_id'] ?? null;
        
        $psModel = new ProductsServices();
        $newId = $psModel->create($body);
        if (!$newId) {
            $data = ['error' => 'Error creating product/service'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        
        // Registrar historial de CREATION
        $history = new ProductsServicesHistory();
        $history->insertHistory(
            $newId,
            $userId,
            $body['description'],
            $body['category'],
            $body['price'],
            $body['cost'],
            $body['difficulty'],
            $body['item_type'],
            $body['product_image_file_id'],
            $body['stock'],
            $userId,
            'CREATION'
        );
        
        $data = ['message' => 'Product/Service created successfully', 'id' => $newId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Actualiza un registro de producto/servicio (verificando que pertenezca al usuario)
    public function updateProductService(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Product/Service ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
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
        $psModel = new ProductsServices();
        $existing = $psModel->findById($id);
        if (!$existing || $existing['user_id'] != $userId) {
            $data = ['error' => 'Record not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $body = json_decode($request->getBody()->getContents(), true);
        // if (!isset($body['description'], $body['category'], $body['price'], $body['cost'], $body['difficulty'], $body['item_type'], $body['stock'])) {
        //     $data = ['error' => 'Missing required fields: description, category, price, cost, difficulty, item_type, stock'];
        //     $response->getBody()->write(json_encode($data));
        //     return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        // }
        $body['product_image_file_id'] = $body['product_image_file_id'] ?? null;
        $updateResult = $psModel->update($id, $body);
        if (!$updateResult) {
            $data = ['error' => 'Error updating record'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de UPDATE
        $history = new ProductsServicesHistory();
        $history->insertHistory(
            $id,
            $userId,
            $body['description'],
            $body['category'],
            $body['price'],
            $body['cost'],
            $body['difficulty'],
            $body['item_type'],
            $body['product_image_file_id'],
            $body['stock'],
            $userId,
            'UPDATE'
        );
        $data = ['message' => 'Record updated successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Elimina un registro de producto/servicio (registrando historial previo)
    public function deleteProductService(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Product/Service ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
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
        $psModel = new ProductsServices();
        $existing = $psModel->findById($id);
        if (!$existing || $existing['user_id'] != $userId) {
            $data = ['error' => 'Record not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        // Registrar historial de DELETION antes de eliminar
        $history = new ProductsServicesHistory();
        $history->insertHistory(
            $id,
            $userId,
            $existing['description'],
            $existing['category'],
            $existing['price'],
            $existing['cost'],
            $existing['difficulty'],
            $existing['item_type'],
            $existing['product_image_file_id'],
            $existing['stock'],
            $userId,
            'DELETION'
        );
        $deleteResult = $psModel->delete($id);
        if (!$deleteResult) {
            $data = ['error' => 'Error deleting record'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        $data = ['message' => 'Record deleted successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Lista el historial de un producto/servicio por su ID
    public function listProductServiceHistory(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if (!$id) {
            $data = ['error' => 'Product/Service ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $userId = $decoded->id;
        $psModel = new ProductsServices();
        $record = $psModel->findById($id);
        if (!$record || $record['user_id'] != $userId) {
            $data = ['error' => 'Record not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        $history = new ProductsServicesHistory();
        $historyRecords = $history->listHistoryByProductServiceId($id);
        $data = ['history' => $historyRecords];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
