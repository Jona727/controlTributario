<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Services\JwtService;
use Slim\Psr7\Response as SlimResponse;

class JwtMiddleware implements MiddlewareInterface
{
    /**
     * Verifica el JWT en la cookie o header Authorization.
     * Si es inválido, redirige al login.
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $jwt = new JwtService();

        // 1. Intentar obtener token de cookie
        $cookies = $request->getCookieParams();
        $token   = $cookies['access_token'] ?? null;

        // 2. Si no hay cookie, intentar header Authorization
        if (!$token) {
            $auth = $request->getHeaderLine('Authorization');
            if (str_starts_with($auth, 'Bearer ')) {
                $token = substr($auth, 7);
            }
        }

        if (!$token) {
            return $this->redirectToLogin();
        }

        $decoded = $jwt->decodeToken($token);

        if (!$decoded) {
            return $this->redirectToLogin();
        }

        // Agregar datos del usuario al request
        $request = $request->withAttribute('user_id', $decoded->sub);
        $request = $request->withAttribute('user_role', $decoded->role);
        $request = $request->withAttribute('user_name', $decoded->name);

        return $handler->handle($request);
    }

    private function redirectToLogin(): Response
    {
        $response = new SlimResponse();
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response
            ->withHeader('Location', $basePath . '/login')
            ->withStatus(302);
    }
}
