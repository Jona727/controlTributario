<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

class AdminController
{
    /**
     * Dashboard del administrador.
     */
    public function dashboard(Request $request, Response $response): Response
    {
        $db = Database::getConnection();

        // Métricas rápidas
        $stats = [];

        // Total comercios activos
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role_id = 3 AND is_active = 1");
        $stats['total_comercios'] = $stmt->fetch()['total'];

        // Facturas pendientes
        $stmt = $db->query("SELECT COUNT(*) as total FROM invoices WHERE status = 'pending'");
        $stats['facturas_pendientes'] = $stmt->fetch()['total'];

        // Facturas vencidas
        $stmt = $db->query("SELECT COUNT(*) as total FROM invoices WHERE status = 'overdue'");
        $stats['facturas_vencidas'] = $stmt->fetch()['total'];

        // Deuda total (pendientes + vencidas)
        $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status IN ('pending', 'overdue')");
        $stats['deuda_total'] = $stmt->fetch()['total'];

        // Facturas pagadas (para el gráfico)
        $stmt = $db->query("SELECT COUNT(*) as total FROM invoices WHERE status = 'paid'");
        $stats['facturas_pagadas'] = $stmt->fetch()['total'];

        // Recaudación del mes actual
        $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status = 'paid' AND MONTH(issue_date) = MONTH(CURDATE()) AND YEAR(issue_date) = YEAR(CURDATE())");
        $stats['recaudacion_mes'] = $stmt->fetch()['total'];

        // Últimas facturas
        $stmt = $db->query("
            SELECT i.*, u.business_name, u.client_code 
            FROM invoices i 
            JOIN users u ON i.user_id = u.id 
            ORDER BY i.created_at DESC 
            LIMIT 10
        ");
        $facturas = $stmt->fetchAll();

        // Comercios morosos (con facturas vencidas)
        $stmt = $db->query("
            SELECT u.id, u.client_code, u.business_name, u.cuit,
                   COUNT(i.id) as facturas_vencidas,
                   SUM(i.total_amount) as deuda
            FROM users u
            JOIN invoices i ON u.id = i.user_id AND i.status = 'overdue'
            WHERE u.role_id = 3
            GROUP BY u.id
            ORDER BY deuda DESC
            LIMIT 5
        ");
        $morosos = $stmt->fetchAll();

        // Cobros recientes en ventanilla (últimos 5 pagos registrados)
        $stmt = $db->query("
            SELECT p.*, i.invoice_number, i.period, u.business_name, u.client_code
            FROM payments p
            JOIN invoices i ON p.invoice_id = i.id
            JOIN users u ON i.user_id = u.id
            ORDER BY p.payment_date DESC
            LIMIT 5
        ");
        $cobrosRecientes = $stmt->fetchAll();

        // Datos del usuario logueado
        $userName = $request->getAttribute('user_name');
        $userRole = $request->getAttribute('user_role');

        // Notificaciones no leídas (para el badge)
        $userId = $request->getAttribute('user_id');
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmt->execute([':uid' => $userId]);
        $notifCount = $stmt->fetch()['total'];

        ob_start();
        require __DIR__ . '/../../public/views/admin/dashboard.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Gestión de comercios.
     */
    public function comercios(Request $request, Response $response): Response
    {
        $db = Database::getConnection();

        $stmt = $db->query("
            SELECT u.*, r.name as role_name,
                   (SELECT COUNT(*) FROM invoices WHERE user_id = u.id) as total_facturas,
                   (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE user_id = u.id AND status IN ('pending','overdue')) as deuda_pendiente
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.role_id = 3
            ORDER BY u.business_name ASC
        ");
        $comercios = $stmt->fetchAll();

        $userName = $request->getAttribute('user_name');
        $userRole = $request->getAttribute('user_role');
        $userId   = $request->getAttribute('user_id');

        $stmtN = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmtN->execute([':uid' => $userId]);
        $notifCount = $stmtN->fetch()['total'];

        ob_start();
        require __DIR__ . '/../../public/views/admin/comercios.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Gestión de facturas.
     */
    public function facturas(Request $request, Response $response): Response
    {
        $db = Database::getConnection();

        $queryParams = $request->getQueryParams();
        $filterUserId = $queryParams['user_id'] ?? '';
        $filterPeriod = $queryParams['period'] ?? '';
        $tab = $queryParams['tab'] ?? 'pendientes';

        $sql = "
            SELECT i.*, u.business_name, u.client_code, u.cuit, p.id AS payment_id,
                   EXISTS (
                       SELECT 1 FROM invoices i2 
                       WHERE i2.user_id = i.user_id 
                         AND i2.status IN ('pending', 'overdue') 
                         AND i2.issue_date < i.issue_date
                   ) as has_older_debt
            FROM invoices i
            JOIN users u ON i.user_id = u.id
            LEFT JOIN payments p ON i.id = p.invoice_id
            WHERE 1=1
        ";
        
        $params = [];
        if ($filterUserId !== '') {
            $sql .= " AND i.user_id = :uid";
            $params[':uid'] = $filterUserId;
        }
        if ($filterPeriod !== '') {
            $sql .= " AND i.period = :period";
            $params[':period'] = $filterPeriod;
        }

        if ($tab === 'pagadas') {
            $sql .= " AND i.status = 'paid'";
        } elseif ($tab === 'anuladas') {
            $sql .= " AND i.status = 'cancelled'";
        } else {
            $sql .= " AND i.status IN ('pending', 'overdue')";
        }
        $sql .= " ORDER BY u.business_name ASC, i.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $facturas = $stmt->fetchAll();

        // Comercios para el selector
        $stmt = $db->query("SELECT id, client_code, business_name FROM users WHERE role_id = 3 AND is_active = 1 ORDER BY business_name");
        $comerciosSelect = $stmt->fetchAll();

        $userName = $request->getAttribute('user_name');
        $userRole = $request->getAttribute('user_role');
        $userId   = $request->getAttribute('user_id');

        $stmtN = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmtN->execute([':uid' => $userId]);
        $notifCount = $stmtN->fetch()['total'];

        ob_start();
        require __DIR__ . '/../../public/views/admin/facturas.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Deuda & Indicadores (GET /admin/deuda).
     */
    public function deuda(Request $request, Response $response): Response
    {
        $db = Database::getConnection();

        // 1. Estadísticas globales de deuda
        $stats = [];
        
        $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status = 'overdue'");
        $stats['vencido'] = $stmt->fetch()['total'];

        $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status = 'pending'");
        $stats['pendiente'] = $stmt->fetch()['total'];

        $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status = 'paid'");
        $stats['pagado'] = $stmt->fetch()['total'];

        $stats['deuda_total'] = $stats['vencido'] + $stats['pendiente'];

        // Índices de cobrabilidad
        $totalEmitido = $stats['deuda_total'] + $stats['pagado'];
        $stats['cobrabilidad'] = $totalEmitido > 0 ? round(($stats['pagado'] / $totalEmitido) * 100, 1) : 0;

        // 2. Ranking de Deudores
        $stmt = $db->query("
            SELECT u.id, u.client_code, u.business_name, u.cuit, u.phone, u.email,
                   COUNT(i.id) as total_facturas,
                   SUM(CASE WHEN i.status = 'overdue' THEN i.total_amount ELSE 0 END) as monto_vencido,
                   SUM(CASE WHEN i.status = 'pending' THEN i.total_amount ELSE 0 END) as monto_pendiente,
                   SUM(CASE WHEN i.status IN ('pending', 'overdue') THEN i.total_amount ELSE 0 END) as deuda_total
            FROM users u
            JOIN invoices i ON u.id = i.user_id
            WHERE u.role_id = 3
            GROUP BY u.id
            HAVING deuda_total > 0
            ORDER BY deuda_total DESC
        ");
        $deudores = $stmt->fetchAll();

        // 3. Recaudación histórica por meses (últimos 6 meses)
        $stmt = $db->query("
            SELECT DATE_FORMAT(issue_date, '%Y-%m') as mes,
                   SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as pagado,
                   SUM(CASE WHEN status IN ('pending', 'overdue') THEN total_amount ELSE 0 END) as pendiente
            FROM invoices
            GROUP BY mes
            ORDER BY mes ASC
            LIMIT 6
        ");
        $historico = $stmt->fetchAll();

        $userName = $request->getAttribute('user_name');
        $userRole = $request->getAttribute('user_role');
        $userId   = $request->getAttribute('user_id');

        $stmtN = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmtN->execute([':uid' => $userId]);
        $notifCount = $stmtN->fetch()['total'];

        ob_start();
        require __DIR__ . '/../../public/views/admin/deuda.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Cierre de Caja Diario (GET /admin/cierre-caja).
     */
    public function cierreCaja(Request $request, Response $response): Response
    {
        $db = Database::getConnection();

        // Obtener los cobros realizados en el día de la fecha
        $stmt = $db->query("
            SELECT p.*, i.invoice_number, i.period, u.business_name, u.cuit, u.client_code
            FROM payments p
            JOIN invoices i ON p.invoice_id = i.id
            JOIN users u ON i.user_id = u.id
            WHERE DATE(p.payment_date) = CURDATE()
            ORDER BY p.payment_date DESC
        ");
        $cobros = $stmt->fetchAll();

        // Calcular los totales del día
        $totalBase = 0.0;
        $totalMora = 0.0;
        $totalPaid = 0.0;

        foreach ($cobros as $c) {
            $totalPaid += floatval($c['amount_paid']);
            $totalMora += floatval($c['surcharge_paid']);
            $totalBase += (floatval($c['amount_paid']) - floatval($c['surcharge_paid']));
        }

        // Datos de sesión del usuario logueado
        $userName = $request->getAttribute('user_name');
        $userRole = $request->getAttribute('user_role');
        $userId   = $request->getAttribute('user_id');

        // Notificaciones no leídas
        $stmtN = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmtN->execute([':uid' => $userId]);
        $notifCount = $stmtN->fetch()['total'];

        ob_start();
        require __DIR__ . '/../../public/views/admin/cierre_caja.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }
}
