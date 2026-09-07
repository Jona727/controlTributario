<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

class ProfileController
{
    /**
     * Cambiar la contraseña del usuario actualmente logueado (admin o comercio).
     * POST /perfil/password
     */
    public function changePassword(Request $request, Response $response): Response
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        $redirectBack = $request->getHeaderLine('Referer') ?: ($basePath . '/login');

        $userId = $request->getAttribute('user_id');
        $data   = $request->getParsedBody();

        $current  = $data['current_password'] ?? '';
        $new      = $data['new_password'] ?? '';
        $confirm  = $data['new_password_confirm'] ?? '';

        if (empty($current) || empty($new) || empty($confirm)) {
            $_SESSION['flash_error'] = 'Completá todos los campos.';
            return $response->withHeader('Location', $redirectBack)->withStatus(302);
        }

        if (strlen($new) < 6) {
            $_SESSION['flash_error'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
            return $response->withHeader('Location', $redirectBack)->withStatus(302);
        }

        if ($new !== $confirm) {
            $_SESSION['flash_error'] = 'La confirmación no coincide con la nueva contraseña.';
            return $response->withHeader('Location', $redirectBack)->withStatus(302);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'La contraseña actual es incorrecta.';
            return $response->withHeader('Location', $redirectBack)->withStatus(302);
        }

        $upStmt = $db->prepare("UPDATE users SET password_hash = :pass WHERE id = :id");
        $upStmt->execute([':pass' => password_hash($new, PASSWORD_DEFAULT), ':id' => $userId]);

        $auditStmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address)
            VALUES (:uid, 'user.change_own_password', 'user', :eid, :ip)
        ");
        $auditStmt->execute([
            ':uid' => $userId,
            ':eid' => $userId,
            ':ip'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $_SESSION['flash_success'] = 'Contraseña actualizada correctamente.';
        return $response->withHeader('Location', $redirectBack)->withStatus(302);
    }
}
