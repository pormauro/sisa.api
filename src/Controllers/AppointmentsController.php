<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Appointments;
use App\History\AppointmentsHistory;
use App\Helpers\JwtHelper;

class AppointmentsController {

    // Lista todos los appointments para el usuario autenticado
    public function listAppointments(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $appointmentsModel = new Appointments();
        $appointments = $appointmentsModel->listAllByUser($userId);
        $data = ['appointments' => $appointments];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Obtiene un appointment por su ID (verificando que pertenezca al usuario)
    public function getAppointment(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Appointment ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type','application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $appointmentsModel = new Appointments();
        $appointment = $appointmentsModel->findById($id);
   /*     if(!$appointment || $appointment['user_id'] != $userId){
            $data = ['error' => 'Appointment not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type','application/json');
        }*/
        $data = ['appointment' => $appointment];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Crea un nuevo appointment para el usuario autenticado
    public function addAppointment(Request $request, Response $response, array $args): Response {
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $body = json_decode($request->getBody()->getContents(), true);
        // Campos requeridos: client_id, appointment_date, appointment_time, location
        if (!isset($body['client_id'], $body['appointment_date'], $body['appointment_time'], $body['location'])) {
            $data = ['error' => 'Missing required fields: client_id, appointment_date, appointment_time, location'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type','application/json');
        }
        $body['user_id'] = $userId;
        // Opcionales: job_id, site_image_file_id, attached_files
        $body['job_id'] = $body['job_id'] ?? null;
        $body['site_image_file_id'] = $body['site_image_file_id'] ?? null;
        $body['attached_files'] = $body['attached_files'] ?? null;
        
        $appointmentsModel = new Appointments();
        $newAppointmentId = $appointmentsModel->create($body);
        if(!$newAppointmentId){
            $data = ['error' => 'Error creating appointment'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type','application/json');
        }
        
        // Registrar historial de CREATION
        $history = new AppointmentsHistory();
        $history->insertHistory(
            $newAppointmentId,
            $userId,
            $body['client_id'],
            $body['job_id'],
            $body['appointment_date'],
            $body['appointment_time'],
            $body['location'],
            $body['site_image_file_id'],
            $body['attached_files'],
            $userId,
            'CREATION'
        );
        
        $data = ['message' => 'Appointment created successfully', 'appointment_id' => $newAppointmentId];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Actualiza un appointment; verifica que pertenezca al usuario autenticado
    public function updateAppointment(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Appointment ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type','application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $appointmentsModel = new Appointments();
        $existingAppointment = $appointmentsModel->findById($id);
     /*   if(!$existingAppointment || $existingAppointment['user_id'] != $userId){
            $data = ['error' => 'Appointment not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type','application/json');
        }*/
        $body = json_decode($request->getBody()->getContents(), true);
        if (!isset($body['client_id'], $body['appointment_date'], $body['appointment_time'], $body['location'])) {
            $data = ['error' => 'Missing required fields: client_id, appointment_date, appointment_time, location'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type','application/json');
        }
        $body['job_id'] = $body['job_id'] ?? null;
        $body['site_image_file_id'] = $body['site_image_file_id'] ?? null;
        $body['attached_files'] = $body['attached_files'] ?? null;
        $updateResult = $appointmentsModel->update($id, $body);
        if(!$updateResult){
            $data = ['error' => 'Error updating appointment'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type','application/json');
        }
        // Registrar historial de UPDATE
        $history = new AppointmentsHistory();
        $history->insertHistory(
            $id,
            $userId,
            $body['client_id'],
            $body['job_id'],
            $body['appointment_date'],
            $body['appointment_time'],
            $body['location'],
            $body['site_image_file_id'],
            $body['attached_files'],
            $userId,
            'UPDATE'
        );
        $data = ['message' => 'Appointment updated successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Elimina un appointment; registra historial previo
    public function deleteAppointment(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Appointment ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type','application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if(!$authHeader){
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $appointmentsModel = new Appointments();
        $existingAppointment = $appointmentsModel->findById($id);
     /*   if(!$existingAppointment || $existingAppointment['user_id'] != $userId){
            $data = ['error' => 'Appointment not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type','application/json');
        }*/
        // Registrar historial de DELETION antes de eliminar
        $history = new AppointmentsHistory();
        $history->insertHistory(
            $id,
            $userId,
            $existingAppointment['client_id'],
            $existingAppointment['job_id'],
            $existingAppointment['appointment_date'],
            $existingAppointment['appointment_time'],
            $existingAppointment['location'],
            $existingAppointment['site_image_file_id'],
            $existingAppointment['attached_files'],
            $userId,
            'DELETION'
        );
        $deleteResult = $appointmentsModel->delete($id);
        if(!$deleteResult){
            $data = ['error' => 'Error deleting appointment'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type','application/json');
        }
        $data = ['message' => 'Appointment deleted successfully'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }

    // Lista el historial de un appointment por su ID
    public function listAppointmentHistory(Request $request, Response $response, array $args): Response {
        $id = $args['id'] ?? null;
        if(!$id){
            $data = ['error' => 'Appointment ID required'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type','application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if(!$decoded){
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type','application/json');
        }
        $userId = $decoded->id;
        $appointmentsModel = new Appointments();
        $appointment = $appointmentsModel->findById($id);
     /*   if(!$appointment || $appointment['user_id'] != $userId){
            $data = ['error' => 'Appointment not found or access denied'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type','application/json');
        }*/
        $history = new AppointmentsHistory();
        $historyRecords = $history->listHistoryByAppointmentId($id);
        $data = ['history' => $historyRecords];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type','application/json');
    }
}
