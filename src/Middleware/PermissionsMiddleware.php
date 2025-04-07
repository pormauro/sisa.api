<?php
namespace App\Middleware;

use App\Models\Permission;
use App\Helpers\JwtHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class PermissionsMiddleware {
    private $requiredPermission;

    public function __construct(string $requiredPermission) {
        $this->requiredPermission = $requiredPermission;
    }

    public function __invoke(Request $request, RequestHandlerInterface $handler): ResponseInterface {
        $responseFactory = new ResponseFactory();

        // Obtener token de Authorization
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            return $this->jsonResponse($responseFactory->createResponse(), 'Falta header Authorization', 401);
        }
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = JwtHelper::verifyToken($token);
        if (!$decoded) {
            return $this->jsonResponse($responseFactory->createResponse(), 'Token inválido o expirado', 401);
        }
        // Si el usuario es superadmin (id=1), permitir sin restricciones
        if (isset($decoded->id) && $decoded->id == 1) {
            return $handler->handle($request);
        }

        // Verificar permiso en la base de datos
        $permissionModel = new Permission();
        if ($permissionModel->hasPermission($decoded->id, $this->requiredPermission)) {
            return $handler->handle($request);
        } else {
            return $this->jsonResponse($responseFactory->createResponse(), 'Acceso denegado: permiso insuficiente', 403);
        }
    }

    private function jsonResponse(ResponseInterface $response, string $message, int $statusCode): ResponseInterface {
        $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));
        return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }
}
