<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class CsrfMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Generar token CSRF en sesión si no existe
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $method = $request->getMethod();
        
        // Solo validamos peticiones que modifiquen estado
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = '';
            
            // Buscar token en el body POST
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody) && isset($parsedBody['csrf_token'])) {
                $token = $parsedBody['csrf_token'];
            }
            
            // Si no está en el body, buscar en Headers (para fetch/AJAX)
            if (!$token) {
                $token = $request->getHeaderLine('X-CSRF-Token');
            }

            // Validar
            if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                $response = new SlimResponse();
                
                // Responder JSON o Redirect según el accept
                $accept = $request->getHeaderLine('Accept');
                if (str_contains($accept, 'application/json') || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'Token CSRF inválido o ausente. Recargue la página y vuelva a intentar.'
                    ]));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(403);
                } else {
                    $response->getBody()->write('<h1>403 Forbidden</h1><p>Token CSRF inv&aacute;lido o expirado. Vuelva atr&aacute;s y recargue la p&aacute;gina.</p>');
                    return $response->withStatus(403);
                }
            }
        }

        return $handler->handle($request);
    }
}
