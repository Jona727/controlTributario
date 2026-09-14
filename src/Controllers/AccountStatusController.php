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

        $userId = (int) $solicitud['user_id'];
        $cancelarAnteriores = !empty($data['cancelar_anteriores']);

        // Filas de facturas reales a cargar, como arrays paralelos
        // (facturas[period][], facturas[issue_date][], etc. del formulario).
        $periods    = $data['facturas']['period'] ?? [];
        $issueDates = $data['facturas']['issue_date'] ?? [];
        $dueDates   = $data['facturas']['due_date'] ?? [];
        $amounts    = $data['facturas']['amount'] ?? [];

        $facturasCargadas = 0;

        $db->beginTransaction();
        try {
            // Evita que la deuda quede duplicada: lo que el sistema tenía
            // cargado (y podía estar mal) se anula antes de cargar lo real.
            if ($cancelarAnteriores) {
                $stmt = $db->prepare("UPDATE invoices SET status = 'cancelled' WHERE user_id = :uid AND status IN ('pending', 'overdue')");
                $stmt->execute([':uid' => $userId]);
            }

            $stmtCheckNum = $db->prepare("SELECT id FROM invoices WHERE invoice_number = :num");
            $stmtMaxSeq   = $db->prepare("SELECT invoice_number FROM invoices WHERE invoice_number LIKE :prefix ORDER BY id DESC LIMIT 1");
            $secuenciaPorAnio = [];

            for ($i = 0; $i < count($periods); $i++) {
                $period    = trim($periods[$i] ?? '');
                $issueDate = trim($issueDates[$i] ?? '');
                $dueDate   = trim($dueDates[$i] ?? '');
                $subtotal  = floatval($amounts[$i] ?? 0);

                if ($period === '' || $issueDate === '' || $dueDate === '' || $subtotal <= 0) {
                    continue; // fila incompleta, se ignora
                }

                // Mismo esquema de numeración que la facturación por lote: F-{año}-{correlativo}.
                $year = date('Y', strtotime($issueDate));
                $prefix = "F-{$year}-";

                if (!isset($secuenciaPorAnio[$year])) {
                    $stmtMaxSeq->execute([':prefix' => $prefix . '%']);
                    $ultima = $stmtMaxSeq->fetch();
                    $secuencia = 0;
                    if ($ultima) {
                        $partes = explode('-', $ultima['invoice_number']);
                        if (isset($partes[2])) {
                            $secuencia = (int) $partes[2];
                        }
                    }
                    $secuenciaPorAnio[$year] = $secuencia;
                }

                $secuenciaPorAnio[$year]++;
                $invoiceNumber = $prefix . str_pad((string) $secuenciaPorAnio[$year], 4, '0', STR_PAD_LEFT);
                $stmtCheckNum->execute([':num' => $invoiceNumber]);
                while ($stmtCheckNum->fetch()) {
                    $secuenciaPorAnio[$year]++;
                    $invoiceNumber = $prefix . str_pad((string) $secuenciaPorAnio[$year], 4, '0', STR_PAD_LEFT);
                    $stmtCheckNum->execute([':num' => $invoiceNumber]);
                }

                $stmtInsert = $db->prepare("
                    INSERT INTO invoices (user_id, invoice_number, period, issue_date, due_date, subtotal, surcharge, total_amount, status, notes, created_by)
                    VALUES (:uid, :num, :period, :issue, :due, :sub, 0.00, :sub2, 'pending', 'Cargada al conciliar estado de cuenta', :admin)
                ");
                $stmtInsert->execute([
                    ':uid'    => $userId,
                    ':num'    => $invoiceNumber,
                    ':period' => $period,
                    ':issue'  => $issueDate,
                    ':due'    => $dueDate,
                    ':sub'    => $subtotal,
                    ':sub2'   => $subtotal,
                    ':admin'  => $adminId,
                ]);
                $invoiceId = (int) $db->lastInsertId();

                $stmtItem = $db->prepare("
                    INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total)
                    VALUES (:iid, :desc, 1, :price, :total)
                ");
                $stmtItem->execute([
                    ':iid'   => $invoiceId,
                    ':desc'  => "Tasa de Higiene y Profilaxis - {$period} (conciliada)",
                    ':price' => $subtotal,
                    ':total' => $subtotal,
                ]);

                $facturasCargadas++;
            }

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
            $stmt->execute([':uid' => $userId, ':msg' => $message]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Error al responder la solicitud: ' . $e->getMessage();
            return $response->withHeader('Location', $basePath . '/admin/estado-cuenta')->withStatus(302);
        }

        $extra = $facturasCargadas > 0 ? " Se cargaron {$facturasCargadas} factura(s) real(es) en su cuenta." : '';
        $_SESSION['flash_success'] = 'Respuesta enviada. El comercio la va a ver como notificación.' . $extra;
        return $response->withHeader('Location', $basePath . '/admin/estado-cuenta')->withStatus(302);
    }
}
