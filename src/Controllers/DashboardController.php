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

        // Sincroniza mora/estado antes de calcular el resumen, para que la
        // "Deuda Pendiente" de arriba coincida con el detalle de facturas de abajo.
        \App\Controllers\InvoiceController::refreshOverdueStatuses();

        // Datos del usuario
        $stmt = $db->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        // Facturas del usuario: por período ascendente (más vieja primero,
        // más reciente al final) — el período es más confiable que la fecha
        // de vencimiento para ordenar cronológicamente, porque en facturas
        // migradas del sistema anterior el vencimiento no siempre coincide
        // con el período real que facturan.
        $stmt = $db->prepare("
            SELECT i.*, p.id AS payment_id
            FROM invoices i
            LEFT JOIN payments p ON i.id = p.invoice_id
            WHERE i.user_id = :uid
            ORDER BY i.period ASC, i.due_date ASC
        ");
        $stmt->execute([':uid' => $userId]);
        $facturas = $stmt->fetchAll();

        // Una vez que Catastro y Rentas concilió la cuenta al menos una vez
        // (respondió una solicitud de estado de cuenta), se confía en las
        // facturas del sistema de ahí en adelante y se muestran normales.
        $stmt = $db->prepare("SELECT 1 FROM account_status_requests WHERE user_id = :uid AND status = 'resolved' LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $cuentaVerificada = (bool) $stmt->fetchColumn();

        // Si el comercio ya está clasificado "al día", su deuda (si tiene)
        // es facturación nueva y real del sistema actual — no hace falta
        // ocultarle nada ni pedirle conciliación, aunque todavía no haya
        // pasado nunca por el flujo de estado de cuenta.
        $esConDeuda = ($user['payment_status'] ?? 'con_deuda') === 'con_deuda';
        $sinVerificar = !$cuentaVerificada && $esConDeuda;

        // Facturas confiables para "Mis Facturas": nunca lo legacy (sin
        // conciliar del sistema anterior). Mientras el comercio esté
        // con_deuda y sin verificar, tampoco se cuenta lo YA VENCIDO — esa
        // deuda vieja tiene que pasar por la conciliación manual de
        // Catastro y Rentas, no mostrarse como si fuera un saldo confiable.
        // Lo pendiente (todavía no vencido) sí se muestra: es facturación
        // real y actual, como el período en curso.
        $facturasConfiables = array_values(array_filter($facturas, function ($f) use ($sinVerificar) {
            if ($f['status'] === 'cancelled') {
                return false;
            }
            if ((int) ($f['is_legacy'] ?? 0) === 1) {
                return false;
            }
            if ($sinVerificar && $f['status'] === 'overdue') {
                return false;
            }
            return true;
        }));

        // El resumen de arriba se calcula sobre el mismo conjunto que se
        // muestra en la tabla, para que nunca haya un número que no
        // coincida con lo que el comercio ve abajo.
        $deudaTotal = array_sum(array_map(
            fn($f) => (float) $f['total_amount'],
            array_filter($facturasConfiables, fn($f) => in_array($f['status'], ['pending', 'overdue'], true))
        ));
        $facturasPendientes = count(array_filter($facturasConfiables, fn($f) => in_array($f['status'], ['pending', 'overdue'], true)));
        $facturasPagadas = count(array_filter($facturasConfiables, fn($f) => $f['status'] === 'paid'));

        // Un comercio "con deuda" sin verificar y sin ninguna factura
        // confiable para mostrar todavía no es lo mismo que estar al día —
        // simplemente no se concilió su cuenta con el sistema anterior.
        $sinDatosAun = $sinVerificar && empty($facturasConfiables);

        // Se consulta siempre (no solo cuando falta conciliar): incluso con
        // la cuenta ya verificada, el comercio puede querer marcar que algo
        // dejó de coincidir, y hace falta saber si ya tiene una solicitud
        // en trámite para no dejarlo pedir dos veces.
        $stmt = $db->prepare("
            SELECT * FROM account_status_requests
            WHERE user_id = :uid
            ORDER BY requested_at DESC
            LIMIT 1
        ");
        $stmt->execute([':uid' => $userId]);
        $solicitudEstadoCuenta = $stmt->fetch() ?: null;

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
