<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

class NotificationController
{
    /**
     * Marcar una notificación propia como leída (POST /notificaciones/leer/{id}).
     */
    public function markAsRead(Request $request, Response $response, array $args): Response
    {
        $id     = (int) $args['id'];
        $userId = $request->getAttribute('user_id');
        $db     = Database::getConnection();

        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $userId]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Marcar todas las notificaciones propias como leídas (POST /notificaciones/leer-todas).
     */
    public function markAllAsRead(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $db     = Database::getConnection();

        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0");
        $stmt->execute([':uid' => $userId]);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
