<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Files;
use App\Helpers\JwtHelper;

class FilesController {
    // Allowed extensions and maximum file size (2 MB)
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    private $maxFileSize = 2 * 1024 * 1024; // 2 MB

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
        // Verificar que se reciba el header Authorization
        $auth = $request->getHeaderLine('Authorization');
        if (!$auth) {
            $data = ['error' => 'Authorization header missing'];
            if (ob_get_contents()) { 
                ob_clean(); 
            }
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = str_replace('Bearer ', '', $auth);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            $data = ['error' => 'Invalid or expired token'];
            if (ob_get_contents()) { 
                ob_clean(); 
            }
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Obtener file_id desde los argumentos de la ruta (/files/{file_id})
        if (!isset($args['file_id'])) {
            $data = ['error' => 'Missing file_id'];
            if (ob_get_contents()) { 
                ob_clean(); 
            }
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $fileId = $args['file_id'];
        $userId = $decoded->id;

        // Obtener el archivo usando el modelo Files
        $filesModel = new Files();
        $file = $filesModel->getFile($fileId);

        // Verificar que el archivo exista y que pertenezca al usuario autenticado
        if (!$file || $file['user_id'] != $userId) {
            $data = ['error' => 'File not found or access denied'];
            if (ob_get_contents()) { 
                ob_clean(); 
            }
            $response->getBody()->write(json_encode($data));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Limpiar el buffer de salida antes de enviar datos binarios
        if (ob_get_contents()) { 
            ob_clean(); 
        }

        // Establecer las cabeceras correspondientes y enviar el contenido del archivo para la descarga
        $response = $response->withHeader('Content-Type', $file['file_type'])
                             ->withHeader('Content-Disposition', 'attachment; filename="' . $file['original_name'] . '"');
        $response->getBody()->write($file['file_data']);
        return $response;
    }
}
