<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Files;
use App\Helpers\JwtHelper;

class FilesController {
    // Extensiones y tamaño máximo de archivo (100 MB)
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'mp4', 'mov', 'webm', 'mkv', 'avi', '3gp', '3g2', 'm4v'];
    private $maxFileSize = 100 * 1024 * 1024; // 100 MB

    public function upload(Request $request, Response $response, array $args): Response {
        $auth = $request->getHeaderLine('Authorization');
        if (!$auth) {
            return $response->withStatus(401)->withJson(['error' => 'Authorization header missing']);
        }
        $token = str_replace('Bearer ', '', $auth);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $response->withStatus(401)->withJson(['error' => 'Invalid or expired token']);
        }

        $uploadedFiles = $request->getUploadedFiles();
        if (!isset($uploadedFiles['file'])) {
            return $response->withStatus(400)->withJson(['error' => 'No file uploaded']);
        }
        $file = $uploadedFiles['file'];

        if ($file->getSize() > $this->maxFileSize) {
            return $response->withStatus(400)->withJson(['error' => 'File exceeds maximum allowed size']);
        }

        $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return $response->withStatus(400)->withJson(['error' => 'File extension not allowed']);
        }

        $userId = $decoded->id;
        $uploadDir = __DIR__ . '/../../../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $storedName = uniqid('file_', true) . '.' . $extension;
        $filePath = $uploadDir . '/' . $storedName;
        $storagePath = 'uploads/' . $storedName;

        try {
            $file->moveTo($filePath);
        } catch (\Exception $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error moving uploaded file']);
        }

        $filesModel = new Files();
        $fileId = $filesModel->upload(
            $userId,
            $file->getClientFilename(),
            $storedName, // Nombre de archivo único
            $file->getClientMediaType(),
            $file->getSize(),
            $storagePath, // Ruta relativa para la BD
            $filePath    // Ruta absoluta en el servidor
        );

        if (!$fileId) {
            unlink($filePath);
            return $response->withStatus(500)->withJson(['error' => 'Error uploading file to database']);
        }

        $fileInfo = $filesModel->getFile($fileId);
        $response->getBody()->write(json_encode(['message' => 'File uploaded successfully', 'file' => $fileInfo]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function download(Request $request, Response $response, array $args): Response {
        $auth = $request->getHeaderLine('Authorization');
        if (!$auth) {
            return $response->withStatus(401)->withJson(['error' => 'Authorization header missing']);
        }
        $token = str_replace('Bearer ', '', $auth);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $response->withStatus(401)->withJson(['error' => 'Invalid or expired token']);
        }

        if (!isset($args['file_id'])) {
            return $response->withStatus(400)->withJson(['error' => 'Missing file_id']);
        }

        $fileId = $args['file_id'];
        $filesModel = new Files();
        $fileInfo = $filesModel->getFile($fileId);

        if (!$fileInfo) {
            return $response->withStatus(404)->withJson(['error' => 'File not found']);
        }
        
        // La columna 'storage_path' tiene la ruta absoluta
        $filePath = $fileInfo['storage_path'];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return $response->withStatus(404)->withJson(['error' => 'File not found on server']);
        }

        // Leer el contenido del archivo y codificarlo en base64
        $fileContent = file_get_contents($filePath);
        $base64Content = base64_encode($fileContent);

        // Devolver el JSON esperado por el frontend
        $data = [
            'file' => $fileInfo,
            'content' => $base64Content
        ];
        
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function toJson(array $data): \Slim\Psr7\Stream {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, json_encode($data));
        rewind($stream);
        return new \Slim\Psr7\Stream($stream);
    }
}