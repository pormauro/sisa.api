<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Files;
use App\Helpers\JwtHelper;

class FilesController {
    // Allowed extensions and maximum file size (100 MB)
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'mp4', 'mov', 'webm', 'mkv', 'avi', '3gp', '3g2', 'm4v'];
    private $maxFileSize = 100 * 1024 * 1024; // 100 MB

    public function upload(Request $request, Response $response, array $args): Response {
        // Verificar que se reciba el header Authorization
        $auth = $request->getHeaderLine('Authorization');
        if (!$auth) {
            $data = ['error' => 'Authorization header missing'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $auth);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Obtener los archivos enviados
        $uploadedFiles = $request->getUploadedFiles();
        if (!isset($uploadedFiles['file'])) {
            $data = ['error' => 'No file uploaded'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $file = $uploadedFiles['file'];

        // Validar el tamaño del archivo
        if ($file->getSize() > $this->maxFileSize) {
            $data = ['error' => 'File exceeds maximum allowed size'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validar la extensión del archivo
        $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $data = ['error' => 'File extension not allowed'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Leer el contenido del archivo y extraer sus propiedades
        $fileData = $file->getStream()->getContents();
        $originalName = $file->getClientFilename();
        $fileType = $file->getClientMediaType();
        $fileSize = $file->getSize();

        // Obtener el ID del usuario a partir del token
        $userId = $decoded->id;

        // Subir el archivo en la base de datos usando el modelo Files
        $filesModel = new Files();
        $fileId = $filesModel->upload($userId, $originalName, $fileType, $fileSize, $fileData);
        if (!$fileId) {
            $data = ['error' => 'Error uploading file'];
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        // Recuperar la información del archivo (excluyendo el contenido binario)
        $fileInfo = $filesModel->getFile($fileId);
        unset($fileInfo['file_data']);
        $data = ['message' => 'File uploaded successfully', 'file' => $fileInfo];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
public function download(Request $request, Response $response, array $args): Response {
    $auth = $request->getHeaderLine('Authorization');
    if (!$auth) {
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json')
                        ->withBody($this->toJson(['error' => 'Authorization header missing']));
    }

    $token = str_replace('Bearer ', '', $auth);
    $decoded = JwtHelper::verifyToken($token);
    if (!$decoded) {
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json')
                        ->withBody($this->toJson(['error' => 'Invalid or expired token']));
    }

    if (!isset($args['file_id'])) {
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json')
                        ->withBody($this->toJson(['error' => 'Missing file_id']));
    }

    $fileId = $args['file_id'];
    $userId = $decoded->id;

    $filesModel = new Files();
    $file = $filesModel->getFile($fileId);
/*
    if (!$file || $file['user_id'] != $userId) {
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json')
                        ->withBody($this->toJson(['error' => 'File not found or access denied']));
    }*/

    // Codificar el binario como base64 y devolverlo junto con la metadata
    $base64Content = base64_encode($file['file_data']);
    unset($file['file_data']);

    $response->getBody()->write(json_encode([
        'file' => $file,
        'content' => $base64Content
    ]));
    return $response->withHeader('Content-Type', 'application/json');
}

private function toJson(array $data): \Slim\Psr7\Stream {
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, json_encode($data));
    rewind($stream);
    return new \Slim\Psr7\Stream($stream);
}

}
