<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\File;
use App\Helpers\JwtHelper;

class FileController {
    // Extensiones permitidas y tamaño máximo de archivo (2 MB)
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    private $maxFileSize = 2 * 1024 * 1024; // 2 MB

    public function upload(Request $request, Response $response, array $args): Response {
        // Verificar el header Authorization
        $auth = $request->getHeaderLine('Authorization');
        if (!$auth) {
            $data = ['error' => 'Falta header Authorization'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $auth);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        // Obtener los archivos subidos
        $uploadedFiles = $request->getUploadedFiles();
        if (!isset($uploadedFiles['file'])) {
            $data = ['error' => 'No se envió ningún archivo'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $file = $uploadedFiles['file'];
        // Validar el tamaño del archivo
        if ($file->getSize() > $this->maxFileSize) {
            $data = ['error' => 'El archivo excede el tamaño permitido'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Validar la extensión del archivo
        $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $data = ['error' => 'Extensión de archivo no permitida'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Leer el contenido del archivo
        $fileData = $file->getStream()->getContents();
        $originalName = $file->getClientFilename();
        $fileType = $file->getClientMediaType();
        $fileSize = $file->getSize();
        // Obtener el ID del usuario a partir del token
        $userId = $decoded->id;
        // Subir el archivo usando el modelo File
        $fileModel = new File();
        $fileId = $fileModel->upload($userId, $originalName, $fileType, $fileSize, $fileData);
        if (!$fileId) {
            $data = ['error' => 'Error al subir el archivo'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        // Retornar la información del archivo, excluyendo el contenido binario
        $fileInfo = $fileModel->getFile($fileId);
        unset($fileInfo['file_data']);
        $data = ['message' => 'Archivo subido con éxito', 'file' => $fileInfo];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function download(Request $request, Response $response, array $args): Response {
        // Verificar el header Authorization
        $auth = $request->getHeaderLine('Authorization');
        if (!$auth) {
            $data = ['error' => 'Falta header Authorization'];
            // Limpiar el buffer de salida, si lo hubiera, para evitar contenido previo
            if (ob_get_contents()) {
                ob_clean();
            }
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $auth);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Token inválido o expirado'];
            if (ob_get_contents()) {
                ob_clean();
            }
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        // Validar que se haya enviado el parámetro file_id
        $params = $request->getQueryParams();
        if (!isset($params['file_id'])) {
            $data = ['error' => 'Falta file_id'];
            if (ob_get_contents()) {
                ob_clean();
            }
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $fileId = $params['file_id'];
        $userId = $decoded->id;
        $fileModel = new File();
        $file = $fileModel->getFile($fileId);
        // Validar que el archivo exista y pertenezca al usuario autenticado
        if (!$file || $file['user_id'] != $userId) {
            $data = ['error' => 'Archivo no encontrado o no tienes permiso para acceder'];
            if (ob_get_contents()) {
                ob_clean();
            }
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        // Limpiar el buffer de salida antes de enviar el contenido binario
        if (ob_get_contents()) {
            ob_clean();
        }
        // Establecer cabeceras y retornar el contenido del archivo
        $response = $response->withHeader('Content-Type', $file['file_type'])
            ->withHeader('Content-Disposition', 'attachment; filename="' . $file['original_name'] . '"');
        $response->getBody()->write($file['file_data']);
        return $response;
    }
}
