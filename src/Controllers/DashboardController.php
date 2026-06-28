<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

class DashboardController
{
    /**
     * Dashboard del usuario/comercio.
     */
    public function index(Request $request, Response $response): Response
    {
        $db     = Database::getConnection();
        $userId = $request->getAttribute('user_id');

        // Datos del usuario
        $stmt = $db->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        // Facturas del usuario
        $stmt = $db->prepare("
            SELECT i.*, p.id AS payment_id 
            FROM invoices i 
            LEFT JOIN payments p ON i.id = p.invoice_id 
            WHERE i.user_id = :uid 
            ORDER BY i.due_date DESC
        ");
        $stmt->execute([':uid' => $userId]);
        $facturas = $stmt->fetchAll();

        // Resumen
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE user_id = :uid AND status IN ('pending','overdue')");
        $stmt->execute([':uid' => $userId]);
        $deudaTotal = $stmt->fetch()['total'];

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM invoices WHERE user_id = :uid AND status = 'paid'");
        $stmt->execute([':uid' => $userId]);
        $facturasPagadas = $stmt->fetch()['total'];

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM invoices WHERE user_id = :uid AND status IN ('pending','overdue')");
        $stmt->execute([':uid' => $userId]);
        $facturasPendientes = $stmt->fetch()['total'];

        // Notificaciones
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([':uid' => $userId]);
        $notificaciones = $stmt->fetchAll();

        $stmtN = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmtN->execute([':uid' => $userId]);
        $notifCount = $stmtN->fetch()['total'];

        $userName = $user['business_name'];
        $userRole = $user['role_name'];

        ob_start();
        require __DIR__ . '/../../public/views/user/dashboard.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }
}
