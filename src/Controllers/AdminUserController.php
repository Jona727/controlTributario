<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

/**
 * Gestión de cuentas internas (admin/super) del municipio.
 * Reservado al rol "super" — es la única diferencia funcional real
 * entre "admin" y "super" en todo el sistema.
 */
class AdminUserController
{
    /**
     * Listado de usuarios internos (GET /admin/usuarios).
     */
    public function index(Request $request, Response $response): Response
    {
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT u.*, r.name AS role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.role_id IN (1, 2)
            ORDER BY u.business_name ASC
        ");
        $usuarios = $stmt->fetchAll();

        $userName = $request->getAttribute('user_name');
        $userRole = $request->getAttribute('user_role');
        $userId   = $request->getAttribute('user_id');

        $stmtN = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmtN->execute([':uid' => $userId]);
        $notifCount = $stmtN->fetch()['total'];

        ob_start();
        require __DIR__ . '/../../public/views/admin/usuarios.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Crear un usuario interno (POST /admin/usuarios/crear).
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $db   = Database::getConnection();
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';

        $required = ['client_code', 'business_name', 'cuit', 'email', 'password', 'role_id'];
        foreach ($required as $field) {
            if (empty(trim((string) ($data[$field] ?? '')))) {
                $_SESSION['flash_error'] = "El campo {$field} es obligatorio.";
                return $response->withHeader('Location', $basePath . '/admin/usuarios')->withStatus(302);
            }
        }

        $roleId = (int) $data['role_id'];
        if (!in_array($roleId, [1, 2], true)) {
            $_SESSION['flash_error'] = 'Rol inválido.';
            return $response->withHeader('Location', $basePath . '/admin/usuarios')->withStatus(302);
        }

        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR cuit = :cuit OR client_code = :code");
        $stmt->execute([':email' => $data['email'], ':cuit' => $data['cuit'], ':code' => $data['client_code']]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Ya existe un usuario con ese email, CUIT/DNI o código.';
            return $response->withHeader('Location', $basePath . '/admin/usuarios')->withStatus(302);
        }

        $stmt = $db->prepare("
            INSERT INTO users (client_code, business_name, cuit, address, phone, email, password_hash, base_rate, role_id)
            VALUES (:code, :name, :cuit, :addr, :phone, :email, :pass, 0.00, :role)
        ");
        $stmt->execute([
            ':code'  => trim($data['client_code']),
            ':name'  => trim($data['business_name']),
            ':cuit'  => trim($data['cuit']),
            ':addr'  => 'Municipalidad',
            ':phone' => trim($data['phone'] ?? ''),
            ':email' => trim($data['email']),
            ':pass'  => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role'  => $roleId,
        ]);

        $newUserId = (int) $db->lastInsertId();
        $adminId   = $request->getAttribute('user_id');

        $auditStmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address)
            VALUES (:uid, 'admin_user.create', 'user', :eid, :details, :ip)
        ");
        $auditStmt->execute([
            ':uid'     => $adminId,
            ':eid'     => $newUserId,
            ':details' => json_encode(['role_id' => $roleId, 'email' => trim($data['email'])]),
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $_SESSION['flash_success'] = 'Usuario creado exitosamente.';
        return $response->withHeader('Location', $basePath . '/admin/usuarios')->withStatus(302);
    }

    /**
     * Desactivar un usuario interno (POST /admin/usuarios/eliminar/{id}).
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id       = (int) $args['id'];
        $db       = Database::getConnection();
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        $adminId  = (int) $request->getAttribute('user_id');

        if ($id === $adminId) {
            $_SESSION['flash_error'] = 'No podés desactivar tu propia cuenta.';
            return $response->withHeader('Location', $basePath . '/admin/usuarios')->withStatus(302);
        }

        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = :id AND role_id IN (1, 2)");
        $stmt->execute([':id' => $id]);

        $auditStmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, ip_address)
            VALUES (:uid, 'admin_user.deactivate', 'user', :eid, :ip)
        ");
        $auditStmt->execute([':uid' => $adminId, ':eid' => $id, ':ip' => $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = 'Usuario desactivado exitosamente.';
        return $response->withHeader('Location', $basePath . '/admin/usuarios')->withStatus(302);
    }
}
