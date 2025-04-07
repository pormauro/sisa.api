<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Jobs;
use App\History\JobsHistory;
use App\Helpers\JwtHelper;

class JobsController {

    // Lista todos los jobs del usuario autenticado
    public function listJobs(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $jobsModel = new Jobs();
        $jobs = $jobsModel->listAll();
        $data = ['jobs' => $jobs];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Obtiene un job por su ID (verifica que pertenezca al usuario)
    public function getJob(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Job ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type','application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $jobsModel = new Jobs();
        $job = $jobsModel->findById($id);
        if(!$job || $job['user_id'] != $userId){
            $data = ['error' => 'Job not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type','application/json');
        }
        $data = ['job' => $job];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Crea un nuevo job para el usuario autenticado
    public function addJob(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $body = json_decode($request->getBody()->getContents(), true);
        // Campos requeridos: client_id, type_of_work, description, status
       /* if (!isset($body['client_id'], $body['type_of_work'], $body['description'], $body['status'])) {
            $data = ['error' => 'Missing required fields: client_id, type_of_work, description, status'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type','application/json');
        }*/
        $body['user_id'] = $userId;
        // Opcionales: product_service_id, folder_id, start_datetime, end_datetime, multiplicative_value, attached_files
        $body['product_service_id'] = $body['product_service_id'] ?? null;
        $body['folder_id'] = $body['folder_id'] ?? null;
        $body['start_datetime'] = $body['start_datetime'] ?? null;
        $body['end_datetime'] = $body['end_datetime'] ?? null;
        $body['multiplicative_value'] = $body['multiplicative_value'] ?? 1.00;
        $body['attached_files'] = $body['attached_files'] ?? null;
        
        $jobsModel = new Jobs();
        $newJobId = $jobsModel->create($body);
        if(!$newJobId){
            $data = ['error' => 'Error creating job'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type','application/json');
        }
        
        // Registrar historial de CREATION
        $history = new JobsHistory();
        $history->insertHistory(
            $newJobId,
            $userId,
            $body['client_id'],
            $body['product_service_id'],
            $body['folder_id'],
            $body['type_of_work'],
            $body['description'],
            $body['status'],
            $body['start_datetime'],
            $body['end_datetime'],
            $body['multiplicative_value'],
            $body['attached_files'],
            $userId,
            'CREATION'
        );
        
        $data = ['message' => 'Job created successfully', 'job_id' => $newJobId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Actualiza un job; verifica que pertenezca al usuario autenticado
    public function updateJob(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Job ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type','application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $jobsModel = new Jobs();
        $existingJob = $jobsModel->findById($id);
        if(!$existingJob || $existingJob['user_id'] != $userId){
            $data = ['error' => 'Job not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type','application/json');
        }
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['client_id'], $body['type_of_work'], $body['description'], $body['status'])) {
            $data = ['error' => 'Missing required fields: client_id, type_of_work, description, status'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type','application/json');
        }
        $body['product_service_id'] = $body['product_service_id'] ?? null;
        $body['folder_id'] = $body['folder_id'] ?? null;
        $body['start_datetime'] = $body['start_datetime'] ?? null;
        $body['end_datetime'] = $body['end_datetime'] ?? null;
        $body['multiplicative_value'] = $body['multiplicative_value'] ?? 1.00;
        $body['attached_files'] = $body['attached_files'] ?? null;
        
        $updateResult = $jobsModel->update($id, $body);
        if(!$updateResult){
            $data = ['error' => 'Error updating job'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type','application/json');
        }
        // Registrar historial de UPDATE
        $history = new JobsHistory();
        $history->insertHistory(
            $id,
            $userId,
            $body['client_id'],
            $body['product_service_id'],
            $body['folder_id'],
            $body['type_of_work'],
            $body['description'],
            $body['status'],
            $body['start_datetime'],
            $body['end_datetime'],
            $body['multiplicative_value'],
            $body['attached_files'],
            $userId,
            'UPDATE'
        );
        $data = ['message' => 'Job updated successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Elimina un job, registrando historial previo
    public function deleteJob(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Job ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type','application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $jobsModel = new Jobs();
        $existingJob = $jobsModel->findById($id);
        if(!$existingJob || $existingJob['user_id'] != $userId){
            $data = ['error' => 'Job not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type','application/json');
        }
        // Registrar historial de DELETION antes de eliminar
        $history = new JobsHistory();
        $history->insertHistory(
            $id,
            $userId,
            $existingJob['client_id'],
            $existingJob['product_service_id'],
            $existingJob['folder_id'],
            $existingJob['type_of_work'],
            $existingJob['description'],
            $existingJob['status'],
            $existingJob['start_datetime'],
            $existingJob['end_datetime'],
            $existingJob['multiplicative_value'],
            $existingJob['attached_files'],
            $userId,
            'DELETION'
        );
        $deleteResult = $jobsModel->delete($id);
        if(!$deleteResult){
            $data = ['error' => 'Error deleting job'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)
                            ->withHeader('Content-Type','application/json');
        }
        $data = ['message' => 'Job deleted successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Lista el historial de un job por su ID
    public function listJobHistory(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Job ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)
                            ->withHeader('Content-Type','application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)
                            ->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $jobsModel = new Jobs();
        $job = $jobsModel->findById($id);
        if(!$job || $job['user_id'] != $userId){
            $data = ['error' => 'Job not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)
                            ->withHeader('Content-Type','application/json');
        }
        $history = new JobsHistory();
        $historyRecords = $history->listHistoryByJobId($id);
        $data = ['history' => $historyRecords];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }
}
