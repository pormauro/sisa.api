<?php
namespace App\Middleware;

use App\Models\User;
use App\Helpers\JwtHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class CheckUserBlockedMiddleware
{
    public function __invoke(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Creamos una instancia de la fábrica de respuestas.
        $responseFactory = new ResponseFactory();

        // Obtener el token del header Authorization
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);

        if (!$token) {
            return $this->jsonResponse(
                $responseFactory->createResponse(),
                'Token no proporcionado',
                401
            );
        }

        // Decodificar el token para obtener el user_id
        $decodedToken = JwtHelper::verifyToken($token);
        if (!$decodedToken) {
            return $this->jsonResponse(
                $responseFactory->createResponse(),
                'Token inválido',
                401
            );
        }

        // Buscar al usuario en la base de datos
        $userModel = new User();
        $user = $userModel->findById($decodedToken->id);

        // Verificar que el token recibido coincida con el token guardado
        if ($user && isset($user['api_token']) && $user['api_token'] !== $token) {
            return $this->jsonResponse(
                $responseFactory->createResponse(),
                'El token no coincide con el almacenado en la base de datos',
                403
            );
        }

        // Verificar si el usuario está bloqueado
        if ($user && $user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            return $this->jsonResponse(
                $responseFactory->createResponse(),
                'Usuario bloqueado',
                403
            );
        }

        // Si pasa todas las validaciones, continuamos el flujo normal
        return $handler->handle($request);
    }

    /**
     * Retorna una respuesta JSON con el status y mensaje indicados.
     */
    private function jsonResponse(ResponseInterface $response, string $message, int $statusCode): ResponseInterface
    {
        $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }
}
