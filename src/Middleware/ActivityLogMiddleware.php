<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use App\Helpers\JwtHelper;
use App\Models\ActivityLog;

class ActivityLogMiddleware
{
    public function __invoke(Request $request, RequestHandlerInterface $handler): Response
    {
        // Primero procesamos la petición
        $response = $handler->handle($request);

        // Obtener info de la request
        $ipAddress = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $request->getHeaderLine('User-Agent') ?: 'unknown';
        $method    = $request->getMethod();
        $route     = (string) $request->getUri()->getPath();

        // Intentar decodificar el token (para obtener user_id si existe)
        $userId = null;
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader) {
            $token = str_replace('Bearer ', '', $authHeader);
            $decoded = JwtHelper::verifyToken($token);
            if ($decoded && isset($decoded->id)) {
                $userId = $decoded->id;
            }
        }

        // Obtener info de la respuesta
        $statusCode = $response->getStatusCode();

        // Opcional: capturar el body final para ver si hay error o mensaje
        $bodyContent = (string) $response->getBody();

        // Guardar en tabla activity_log
        $log = new ActivityLog();
        $log->insert(
            $userId,
            $route,
            $method,
            $ipAddress,
            $userAgent,
            $statusCode,
            $bodyContent
        );

        // Retornar la respuesta al cliente
        return $response;
    }
}
