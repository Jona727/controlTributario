<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

/**
 * Solicitudes de "estado de cuenta real": el comercio pide que Catastro y
 * Rentas le confirme su situación conciliada a mano con el sistema anterior,
 * en vez de mostrarle el total que calcula el sistema (que puede tener
 * huecos heredados de la facturación reactiva del sistema viejo).
 */
class AccountStatusController
{
    /**
     * El comercio pide su estado de cuenta (POST /user/estado-cuenta/solicitar).
     */
    public function request(Request $request, Response $response): Response
    {
        $db = Database::getConnection();
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        $userId = $request->getAttribute('user_id');

        $stmt = $db->prepare("SELECT id FROM account_status_requests WHERE user_id = :uid AND status = 'pending'");
        $stmt->execute([':uid' => $userId]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Ya tenés una solicitud en trámite. Te vamos a avisar apenas esté respondida.';
            return $response->withHeader('Location', $basePath . '/user/dashboard')->withStatus(302);
        }

        $stmt = $db->prepare("INSERT INTO account_status_requests (user_id) VALUES (:uid)");
        $stmt->execute([':uid' => $userId]);

        $_SESSION['flash_success'] = 'Solicitud enviada. Catastro y Rentas va a revisar tu situación y te va a avisar por acá.';
        return $response->withHeader('Location', $basePath . '/user/dashboard')->withStatus(302);
    }

    /**
     * El admin responde una solicitud (POST /admin/estado-cuenta/responder/{id}).
     */
    public function resolve(Request $request, Response $response, array $args): Response
    {
        $db = Database::getConnection();
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        $requestId = (int) $args['id'];
        $data = $request->getParsedBody();
        $adminId = $request->getAttribute('user_id');

        $message = trim($data['response_message'] ?? '');
        if ($message === '') {
            $_SESSION['flash_error'] = 'Escribí el estado de cuenta real antes de enviarlo.';
            return $response->withHeader('Location', $basePath . '/admin/estado-cuenta')->withStatus(302);
        }

        $stmt = $db->prepare("SELECT user_id, status FROM account_status_requests WHERE id = :id");
        $stmt->execute([':id' => $requestId]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            $_SESSION['flash_error'] = 'La solicitud no existe.';
            return $response->withHeader('Location', $basePath . '/admin/estado-cuenta')->withStatus(302);
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE account_status_requests
                SET status = 'resolved', resolved_at = NOW(), resolved_by = :admin, response_message = :msg
                WHERE id = :id
            ");
            $stmt->execute([':admin' => $adminId, ':msg' => $message, ':id' => $requestId]);

            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message)
                VALUES (:uid, 'info', 'Tu estado de cuenta real', :msg)
            ");
            $stmt->execute([':uid' => $solicitud['user_id'], ':msg' => $message]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Error al responder la solicitud: ' . $e->getMessage();
            return $response->withHeader('Location', $basePath . '/admin/estado-cuenta')->withStatus(302);
        }

        $_SESSION['flash_success'] = 'Respuesta enviada. El comercio la va a ver como notificación.';
        return $response->withHeader('Location', $basePath . '/admin/estado-cuenta')->withStatus(302);
    }
}
