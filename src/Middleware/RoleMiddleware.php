<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class RoleMiddleware implements MiddlewareInterface
{
    private array $allowedRoles;

    /**
     * @param string[] $allowedRoles Roles permitidos para la ruta
     */
    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $role = $request->getAttribute('user_role');

        if (!in_array($role, $this->allowedRoles, true)) {
            $response = new SlimResponse();
            $response->getBody()->write(
                '<h1>403 – Acceso Denegado</h1><p>No tiene permisos para acceder a esta sección.</p>'
            );
            return $response->withStatus(403);
        }

        return $handler->handle($request);
    }
}
