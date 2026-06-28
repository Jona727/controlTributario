<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;

class InvoiceController
{
    /**
     * Crear factura (POST /admin/facturas/crear).
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $db   = Database::getConnection();

        $required = ['user_id', 'invoice_number', 'issue_date', 'due_date', 'total_amount'];
        foreach ($required as $field) {
            if (empty($data[$field] ?? '')) {
                $_SESSION['flash_error'] = "El campo {$field} es obligatorio.";
                $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
                return $response->withHeader('Location', $basePath . '/admin/facturas')->withStatus(302);
            }
        }

        // Verificar número único
        $stmt = $db->prepare("SELECT id FROM invoices WHERE invoice_number = :num");
        $stmt->execute([':num' => $data['invoice_number']]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Ya existe una factura con ese número.';
            $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
            return $response->withHeader('Location', $basePath . '/admin/facturas')->withStatus(302);
        }

        $adminId   = $request->getAttribute('user_id');
        $subtotal  = floatval($data['total_amount']);
        $surcharge = floatval($data['surcharge'] ?? 0);
        $total     = $subtotal + $surcharge;

        $stmt = $db->prepare("
            INSERT INTO invoices (user_id, invoice_number, period, issue_date, due_date, subtotal, surcharge, total_amount, status, notes, created_by)
            VALUES (:uid, :num, :period, :issue, :due, :sub, :sur, :total, 'pending', :notes, :admin)
        ");
        $stmt->execute([
            ':uid'    => (int) $data['user_id'],
            ':num'    => trim($data['invoice_number']),
            ':period' => trim($data['period'] ?? ''),
            ':issue'  => $data['issue_date'],
            ':due'    => $data['due_date'],
            ':sub'    => $subtotal,
            ':sur'    => $surcharge,
            ':total'  => $total,
            ':notes'  => trim($data['notes'] ?? ''),
            ':admin'  => $adminId,
        ]);

        $invoiceId = (int) $db->lastInsertId();

        // Crear item por defecto
        $desc = $data['item_description'] ?? 'Tasa de Seguridad e Higiene - ' . ($data['period'] ?? '');
        $stmt = $db->prepare("
            INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total)
            VALUES (:iid, :desc, 1, :price, :total)
        ");
        $stmt->execute([
            ':iid'   => $invoiceId,
            ':desc'  => $desc,
            ':price' => $subtotal,
            ':total' => $subtotal,
        ]);

        // Notificar al comercio
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'info', 'Nueva factura disponible', :msg)");
        $stmt->execute([
            ':uid' => (int) $data['user_id'],
            ':msg' => "Se ha generado la factura {$data['invoice_number']} por un total de \${$total}. Vencimiento: {$data['due_date']}.",
        ]);

        $_SESSION['flash_success'] = 'Factura creada exitosamente.';
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response->withHeader('Location', $basePath . '/admin/facturas')->withStatus(302);
    }

    /**
     * Cambiar estado de factura (POST /admin/facturas/estado/{id}).
     */
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $id   = (int) $args['id'];
        $data = $request->getParsedBody();
        $db   = Database::getConnection();

        $validStatus = ['pending', 'paid', 'overdue', 'cancelled'];
        $newStatus   = $data['status'] ?? '';

        if (!in_array($newStatus, $validStatus, true)) {
            $_SESSION['flash_error'] = 'Estado inválido.';
            $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
            return $response->withHeader('Location', $basePath . '/admin/facturas')->withStatus(302);
        }

        $stmt = $db->prepare("UPDATE invoices SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $id]);

        // Si se marca como pagada, notificar
        if ($newStatus === 'paid') {
            $stmt = $db->prepare("SELECT user_id, invoice_number FROM invoices WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $inv = $stmt->fetch();

            if ($inv) {
                $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'info', 'Pago confirmado', :msg)");
                $stmt->execute([
                    ':uid' => $inv['user_id'],
                    ':msg' => "Su pago para la factura {$inv['invoice_number']} ha sido registrado exitosamente.",
                ]);
            }
        }

        $_SESSION['flash_success'] = 'Estado de factura actualizado.';
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response->withHeader('Location', $basePath . '/admin/facturas')->withStatus(302);
    }

    /**
     * Descargar factura en PDF (GET /admin/facturas/pdf/{id} o /user/facturas/pdf/{id}).
     */
    public function downloadPdf(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $db = Database::getConnection();

        // 1. Obtener la factura
        $stmt = $db->prepare("SELECT * FROM invoices WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $invoice = $stmt->fetch();

        if (!$invoice) {
            $response->getBody()->write('<h1>404 - Factura no encontrada</h1>');
            return $response->withStatus(404);
        }

        // 2. Control de seguridad
        $loggedUserId   = (int) $request->getAttribute('user_id');
        $loggedUserRole = $request->getAttribute('user_role');

        if ($loggedUserRole === 'user' && (int)$invoice['user_id'] !== $loggedUserId) {
            $response->getBody()->write('<h1>403 - Acceso Denegado</h1><p>No tiene permisos para descargar esta factura.</p>');
            return $response->withStatus(403);
        }

        // Calcular mora dinámica al día de hoy si está pendiente o vencida
        if ($invoice['status'] !== 'paid' && $invoice['status'] !== 'cancelled') {
            $moraData = self::calculateMora($invoice);
            $invoice['surcharge'] = $moraData['surcharge'];
            $invoice['total_amount'] = $moraData['total_amount'];
            $invoice['status'] = $moraData['status'];

            // Persistir la mora calculada en DB para que sea consistente
            $upStmt = $db->prepare("UPDATE invoices SET surcharge = :sur, total_amount = :tot, status = :status WHERE id = :id");
            $upStmt->execute([
                ':sur' => $moraData['surcharge'],
                ':tot' => $moraData['total_amount'],
                ':status' => $moraData['status'],
                ':id' => $id
            ]);
        }

        // 3. Obtener datos del contribuyente/comercio
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $invoice['user_id']]);
        $user = $stmt->fetch();

        // 4. Obtener ítems de la factura
        $stmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id");
        $stmt->execute([':id' => $id]);
        $items = $stmt->fetchAll();

        // 5. Generar PDF
        $pdfService = new \App\Services\PdfService();
        $pdfBinary  = $pdfService->generateInvoicePdf($invoice, $user, $items);

        // 6. Configurar respuesta
        $response->getBody()->write($pdfBinary);
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="boleta_' . $invoice['invoice_number'] . '.pdf"')
            ->withHeader('Cache-Control', 'private, max-age=0, must-revalidate');
    }

    /**
     * Calcula dinámicamente los recargos por mora acumulados a la fecha (3% mensual, 0.1% diario).
     */
    public static function calculateMora(array $invoice, float $tasaMensual = 3.0): array
    {
        if ($invoice['status'] === 'paid' || $invoice['status'] === 'cancelled') {
            return [
                'dias_mora' => 0,
                'surcharge' => floatval($invoice['surcharge']),
                'total_amount' => floatval($invoice['total_amount']),
                'status' => $invoice['status']
            ];
        }

        $vencimiento = new \DateTime($invoice['due_date']);
        $hoy = new \DateTime('today');

        if ($hoy > $vencimiento) {
            $diff = $hoy->diff($vencimiento);
            $diasMora = $diff->days;
            $tasaDiaria = ($tasaMensual / 100) / 30;
            $surcharge = floatval($invoice['subtotal']) * ($tasaDiaria * $diasMora);
            $total = floatval($invoice['subtotal']) + $surcharge;

            return [
                'dias_mora' => $diasMora,
                'surcharge' => round($surcharge, 2),
                'total_amount' => round($total, 2),
                'status' => 'overdue'
            ];
        }

        return [
            'dias_mora' => 0,
            'surcharge' => 0.00,
            'total_amount' => floatval($invoice['subtotal']),
            'status' => $invoice['status']
        ];
    }

    /**
     * Registrar Pago en Ventanilla (POST /admin/facturas/pagar/{id}).
     */
    public function payInVentanilla(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $db = Database::getConnection();
        $adminId = $request->getAttribute('user_id');

        try {
            $db->beginTransaction();

            // 0. Validar contraseña de seguridad
            $data = $request->getParsedBody();
            $adminStmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id AND role_id IN (1, 2)");
            $adminStmt->execute([':id' => $adminId]);
            $adminData = $adminStmt->fetch();
            
            if (!$adminData || !password_verify($data['admin_password'] ?? '', $adminData['password_hash'])) {
                $db->rollBack();
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Contraseña de seguridad incorrecta.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // 1. Obtener la factura
            $stmt = $db->prepare("SELECT * FROM invoices WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            $invoice = $stmt->fetch();

            if (!$invoice) {
                $db->rollBack();
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Factura no encontrada.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($invoice['status'] === 'paid') {
                $db->rollBack();
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Esta boleta ya se encuentra pagada.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Validar control cronológico
            $checkStmt = $db->prepare("
                SELECT invoice_number, period 
                FROM invoices 
                WHERE user_id = :uid 
                  AND status IN ('pending', 'overdue') 
                  AND issue_date < :issue 
                ORDER BY issue_date ASC 
                LIMIT 1
            ");
            $checkStmt->execute([
                ':uid' => $invoice['user_id'],
                ':issue' => $invoice['issue_date']
            ]);
            $olderInvoice = $checkStmt->fetch();
            if ($olderInvoice) {
                $db->rollBack();
                $response->getBody()->write(json_encode([
                    'success' => false, 
                    'error' => "Control Cronológico: No puede pagar este período. Existe deuda anterior impaga (Factura {$olderInvoice['invoice_number']} - Período {$olderInvoice['period']}). Debe cancelarla primero o realizar un pago en lote."
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // 2. Calcular mora al día de la cobranza
            $moraData = self::calculateMora($invoice);
            $finalSurcharge = $moraData['surcharge'];
            $finalTotal = $moraData['total_amount'];

            // 3. Generar número de recibo correlativo único para este año
            $year = date('Y');
            $countStmt = $db->prepare("SELECT COUNT(*) FROM payments WHERE receipt_number LIKE :pattern");
            $countStmt->execute([':pattern' => "REC-{$year}-%"]);
            $count = (int) $countStmt->fetchColumn();
            $receiptNum = sprintf("REC-%s-%05d", $year, $count + 1);

            // 4. Actualizar factura a pagada con los recargos consolidados
            $upStmt = $db->prepare("
                UPDATE invoices 
                SET status = 'paid', surcharge = :sur, total_amount = :tot 
                WHERE id = :id
            ");
            $upStmt->execute([
                ':sur' => $finalSurcharge,
                ':tot' => $finalTotal,
                ':id'  => $id
            ]);

            // 5. Insertar el pago
            $payStmt = $db->prepare("
                INSERT INTO payments (invoice_id, receipt_number, payment_date, amount_paid, surcharge_paid, registered_by)
                VALUES (:iid, :receipt, NOW(), :amount, :surcharge, :admin)
            ");
            $payStmt->execute([
                ':iid'       => $id,
                ':receipt'   => $receiptNum,
                ':amount'    => $finalTotal,
                ':surcharge' => $finalSurcharge,
                ':admin'     => $adminId
            ]);

            $paymentId = (int) $db->lastInsertId();

            // 6. Registrar item de recargo si hubo mora cobrada
            if ($finalSurcharge > 0) {
                $itemStmt = $db->prepare("
                    INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total)
                    VALUES (:iid, :desc, 1, :price, :total)
                ");
                $itemStmt->execute([
                    ':iid'   => $id,
                    ':desc'  => 'Recargo por mora (Tasa resarcitoria cobrada en caja)',
                    ':price' => $finalSurcharge,
                    ':total' => $finalSurcharge
                ]);
            }

            // 7. Generar notificación para el contribuyente
            $notifStmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message) 
                VALUES (:uid, 'info', 'Pago Registrado en Ventanilla', :msg)
            ");
            $notifStmt->execute([
                ':uid' => $invoice['user_id'],
                ':msg' => "Se registró con éxito el cobro en efectivo de la factura {$invoice['invoice_number']} por \${$finalTotal} en la ventanilla municipal. Recibo oficial: {$receiptNum}."
            ]);

            $db->commit();

            $response->getBody()->write(json_encode([
                'success' => true,
                'payment_id' => $paymentId,
                'receipt_number' => $receiptNum,
                'message' => 'Cobro en ventanilla registrado exitosamente.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $db->rollBack();
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Pago en Lote de Múltiples Facturas (POST /admin/facturas/pagar-lote).
     */
    public function payBulkInVentanilla(Request $request, Response $response): Response
    {
        $db = Database::getConnection();
        $adminId = $request->getAttribute('user_id');

        try {
            $db->beginTransaction();

            $data = $request->getParsedBody();
            $invoiceIds = $data['invoice_ids'] ?? [];
            if (empty($invoiceIds) || !is_array($invoiceIds)) {
                $db->rollBack();
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'No se seleccionaron facturas.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Validar contraseña de seguridad
            $adminStmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id AND role_id IN (1, 2)");
            $adminStmt->execute([':id' => $adminId]);
            $adminData = $adminStmt->fetch();
            if (!$adminData || !password_verify($data['admin_password'] ?? '', $adminData['password_hash'])) {
                $db->rollBack();
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Contraseña de seguridad incorrecta.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Obtener todas las facturas
            $inMarks = str_repeat('?,', count($invoiceIds) - 1) . '?';
            $stmt = $db->prepare("SELECT * FROM invoices WHERE id IN ($inMarks) FOR UPDATE");
            $stmt->execute($invoiceIds);
            $invoices = $stmt->fetchAll();

            if (count($invoices) !== count($invoiceIds)) {
                $db->rollBack();
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Algunas facturas no existen o no se encontraron.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Verificar que todas pertenezcan al mismo usuario y estén pendientes/vencidas
            $userId = $invoices[0]['user_id'];
            $earliestIssue = null;
            
            foreach ($invoices as $inv) {
                if ($inv['user_id'] != $userId) {
                    $db->rollBack();
                    $response->getBody()->write(json_encode(['success' => false, 'error' => 'No se pueden pagar en lote facturas de distintos comercios.']));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
                if ($inv['status'] === 'paid' || $inv['status'] === 'cancelled') {
                    $db->rollBack();
                    $response->getBody()->write(json_encode(['success' => false, 'error' => "La factura {$inv['invoice_number']} ya está pagada o cancelada."]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
                if ($earliestIssue === null || $inv['issue_date'] < $earliestIssue) {
                    $earliestIssue = $inv['issue_date'];
                }
            }

            // Validar control cronológico para el lote
            $checkStmt = $db->prepare("
                SELECT id, invoice_number, period 
                FROM invoices 
                WHERE user_id = :uid 
                  AND status IN ('pending', 'overdue') 
                  AND issue_date < :issue 
                ORDER BY issue_date ASC
            ");
            $checkStmt->execute([
                ':uid' => $userId,
                ':issue' => $earliestIssue
            ]);
            $olderInvoices = $checkStmt->fetchAll();
            foreach ($olderInvoices as $older) {
                if (!in_array($older['id'], $invoiceIds)) {
                    $db->rollBack();
                    $response->getBody()->write(json_encode([
                        'success' => false, 
                        'error' => "Control Cronológico: Existe deuda anterior impaga (Factura {$older['invoice_number']} - {$older['period']}) que no está incluida en este lote. Debe seleccionarla también."
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // Generar 1 solo número de recibo para el lote
            $year = date('Y');
            $countStmt = $db->prepare("SELECT COUNT(DISTINCT receipt_number) FROM payments WHERE receipt_number LIKE :pattern");
            $countStmt->execute([':pattern' => "REC-{$year}-%"]);
            $count = (int) $countStmt->fetchColumn();
            $receiptNum = sprintf("REC-%s-%05d", $year, $count + 1);

            $paymentIdToReturn = null;

            foreach ($invoices as $inv) {
                $moraData = self::calculateMora($inv);
                $finalSurcharge = $moraData['surcharge'];
                $finalTotal = $moraData['total_amount'];

                $upStmt = $db->prepare("UPDATE invoices SET status = 'paid', surcharge = :sur, total_amount = :tot WHERE id = :id");
                $upStmt->execute([':sur' => $finalSurcharge, ':tot' => $finalTotal, ':id' => $inv['id']]);

                $payStmt = $db->prepare("
                    INSERT INTO payments (invoice_id, receipt_number, payment_date, amount_paid, surcharge_paid, registered_by)
                    VALUES (:iid, :receipt, NOW(), :amount, :surcharge, :admin)
                ");
                $payStmt->execute([
                    ':iid'       => $inv['id'],
                    ':receipt'   => $receiptNum,
                    ':amount'    => $finalTotal,
                    ':surcharge' => $finalSurcharge,
                    ':admin'     => $adminId
                ]);

                if ($paymentIdToReturn === null) {
                    $paymentIdToReturn = (int) $db->lastInsertId();
                }

                if ($finalSurcharge > 0) {
                    $itemStmt = $db->prepare("
                        INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total)
                        VALUES (:iid, :desc, 1, :price, :total)
                    ");
                    $itemStmt->execute([
                        ':iid'   => $inv['id'],
                        ':desc'  => 'Recargo por mora (Tasa resarcitoria cobrada en caja)',
                        ':price' => $finalSurcharge,
                        ':total' => $finalSurcharge
                    ]);
                }
            }

            $notifStmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'info', 'Pago en Lote Registrado', :msg)");
            $notifStmt->execute([
                ':uid' => $userId,
                ':msg' => "Se registró con éxito el cobro en efectivo de " . count($invoices) . " facturas bajo el recibo oficial: {$receiptNum}."
            ]);

            $db->commit();

            $response->getBody()->write(json_encode([
                'success' => true,
                'payment_id' => $paymentIdToReturn,
                'receipt_number' => $receiptNum,
                'message' => 'Cobro en lote registrado exitosamente.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $db->rollBack();
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Revertir un pago registrado (POST /admin/facturas/revertir/{id}).
     */
    public function revertPayment(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id']; // ID de la factura
        $db = Database::getConnection();
        $adminId = $request->getAttribute('user_id');

        try {
            $db->beginTransaction();

            // 0. Validar contraseña de seguridad
            $data = $request->getParsedBody();
            $adminStmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id AND role_id IN (1, 2)");
            $adminStmt->execute([':id' => $adminId]);
            $adminData = $adminStmt->fetch();
            
            if (!$adminData || !password_verify($data['admin_password'] ?? '', $adminData['password_hash'])) {
                $db->rollBack();
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Contraseña de seguridad incorrecta.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // 1. Bloquear factura para actualización
            $stmt = $db->prepare("SELECT * FROM invoices WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            $invoice = $stmt->fetch();

            if (!$invoice) {
                $db->rollBack();
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Factura no encontrada.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($invoice['status'] !== 'paid') {
                $db->rollBack();
                $response->getBody()->write(json_encode(['success' => false, 'error' => 'Esta boleta no se encuentra pagada.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // 2. Eliminar el registro del pago (recibo oficial)
            $payStmt = $db->prepare("DELETE FROM payments WHERE invoice_id = :iid");
            $payStmt->execute([':iid' => $id]);

            // 3. Eliminar los ítems de recargo por mora asociados a esta factura
            $itemStmt = $db->prepare("DELETE FROM invoice_items WHERE invoice_id = :iid AND description LIKE 'Recargo por mora%'");
            $itemStmt->execute([':iid' => $id]);

            // 4. Volver a estado pendiente y quitar el recargo
            $upStmt = $db->prepare("
                UPDATE invoices 
                SET status = 'pending', surcharge = 0.00, total_amount = subtotal 
                WHERE id = :id
            ");
            $upStmt->execute([':id' => $id]);

            // 5. Registrar en log de auditoría
            $auditStmt = $db->prepare("
                INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address) 
                VALUES (:uid, 'invoice.revert_payment', 'invoice', :eid, :details, :ip)
            ");
            $auditStmt->execute([
                ':uid' => $adminId,
                ':eid' => $id,
                ':details' => json_encode(['invoice_number' => $invoice['invoice_number'], 'reason' => 'Reversión solicitada por administrador']),
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            $db->commit();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'El pago fue revertido y la factura ha vuelto a estado Pendiente.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $db->rollBack();
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Descargar Recibo Oficial de Pago en PDF (GET /admin/facturas/recibo/{id} o /user/facturas/recibo/{id}).
     */
    public function downloadReceiptPdf(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id']; // ID del PAGO (payment_id)
        $db = Database::getConnection();

        // 1. Obtener el registro de pago
        $stmt = $db->prepare("SELECT * FROM payments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $payment = $stmt->fetch();

        if (!$payment) {
            $response->getBody()->write('<h1>404 - Recibo de pago no encontrado</h1>');
            return $response->withStatus(404);
        }

        // 2. Obtener la factura
        $stmt = $db->prepare("SELECT * FROM invoices WHERE id = :id");
        $stmt->execute([':id' => $payment['invoice_id']]);
        $invoice = $stmt->fetch();

        // 3. Control de seguridad
        $loggedUserId   = (int) $request->getAttribute('user_id');
        $loggedUserRole = $request->getAttribute('user_role');

        if ($loggedUserRole === 'user' && (int)$invoice['user_id'] !== $loggedUserId) {
            $response->getBody()->write('<h1>403 - Acceso Denegado</h1><p>No tiene permisos para descargar este recibo.</p>');
            return $response->withStatus(403);
        }

        // 4. Obtener datos del contribuyente
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $invoice['user_id']]);
        $user = $stmt->fetch();

        // 5. Generar recibo oficial en PDF (Verificar si es lote o individual)
        $pdfService = new \App\Services\PdfService();
        
        $stmtBatch = $db->prepare("SELECT * FROM payments WHERE receipt_number = :rec");
        $stmtBatch->execute([':rec' => $payment['receipt_number']]);
        $batchPayments = $stmtBatch->fetchAll();
        
        if (count($batchPayments) > 1) {
            // Es un recibo por lote
            $batchInvoices = [];
            foreach ($batchPayments as $bp) {
                $invStmt = $db->prepare("SELECT * FROM invoices WHERE id = :id");
                $invStmt->execute([':id' => $bp['invoice_id']]);
                $batchInvoices[] = $invStmt->fetch();
            }
            $pdfBinary = $pdfService->generateBatchReceiptPdf($batchPayments, $batchInvoices, $user);
        } else {
            // Es un recibo individual
            $pdfBinary = $pdfService->generateReceiptPdf($payment, $invoice, $user);
        }

        // 6. Configurar respuesta
        $response->getBody()->write($pdfBinary);
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="recibo_' . $payment['receipt_number'] . '.pdf"')
            ->withHeader('Cache-Control', 'private, max-age=0, must-revalidate');
    }

    /**
     * Generar lote de facturas masivas (POST /admin/facturas/generar-lote).
     */
    public function generateBatchInvoices(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $db   = Database::getConnection();

        $required = ['period', 'issue_date', 'due_date'];
        foreach ($required as $field) {
            if (empty($data[$field] ?? '')) {
                $_SESSION['flash_error'] = "El campo {$field} es obligatorio para generar el lote.";
                $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
                return $response->withHeader('Location', $basePath . '/admin/facturas')->withStatus(302);
            }
        }

        $period    = trim($data['period']);
        $issueDate = $data['issue_date'];
        $dueDate   = $data['due_date'];
        $adminId   = $request->getAttribute('user_id');

        try {
            $db->beginTransaction();

            // 1. Obtener todos los comercios activos (role_id = 3 y is_active = 1)
            $stmt = $db->query("SELECT id, client_code, business_name, base_rate FROM users WHERE role_id = 3 AND is_active = 1");
            $comercios = $stmt->fetchAll();

            if (empty($comercios)) {
                throw new \Exception('No hay comercios activos registrados para facturar.');
            }

            // 2. Obtener el número correlativo máximo actual de factura para el año actual
            $year = date('Y', strtotime($issueDate));
            $prefix = "F-" . $year . "-";
            
            $stmt = $db->prepare("SELECT invoice_number FROM invoices WHERE invoice_number LIKE :prefix ORDER BY id DESC LIMIT 1");
            $stmt->execute([':prefix' => $prefix . '%']);
            $lastInvoice = $stmt->fetch();
            
            $sequence = 0;
            if ($lastInvoice) {
                $parts = explode('-', $lastInvoice['invoice_number']);
                if (isset($parts[2])) {
                    $sequence = (int)$parts[2];
                }
            }

            $count = 0;
            foreach ($comercios as $comercio) {
                if (floatval($comercio['base_rate']) <= 0) {
                    continue;
                }

                $sequence++;
                $invoiceNumber = $prefix . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);

                $stmtCheck = $db->prepare("SELECT id FROM invoices WHERE invoice_number = :num");
                $stmtCheck->execute([':num' => $invoiceNumber]);
                if ($stmtCheck->fetch()) {
                    do {
                        $sequence++;
                        $invoiceNumber = $prefix . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);
                        $stmtCheck->execute([':num' => $invoiceNumber]);
                    } while ($stmtCheck->fetch());
                }

                $subtotal = floatval($comercio['base_rate']);

                $stmtInsert = $db->prepare("
                    INSERT INTO invoices (user_id, invoice_number, period, issue_date, due_date, subtotal, surcharge, total_amount, status, notes, created_by)
                    VALUES (:uid, :num, :period, :issue, :due, :sub, 0.00, :total, 'pending', '', :admin)
                ");
                $stmtInsert->execute([
                    ':uid'    => (int)$comercio['id'],
                    ':num'    => $invoiceNumber,
                    ':period' => $period,
                    ':issue'  => $issueDate,
                    ':due'    => $dueDate,
                    ':sub'    => $subtotal,
                    ':total'  => $subtotal,
                    ':admin'  => $adminId
                ]);

                $invoiceId = (int)$db->lastInsertId();

                $stmtItem = $db->prepare("
                    INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total)
                    VALUES (:iid, :desc, 1, :price, :total)
                ");
                $stmtItem->execute([
                    ':iid'   => $invoiceId,
                    ':desc'  => "Tasa de Seguridad e Higiene - Período " . $period,
                    ':price' => $subtotal,
                    ':total' => $subtotal
                ]);

                $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'info', 'Nueva factura disponible (Lote)', :msg)");
                $stmtNotif->execute([
                    ':uid' => (int)$comercio['id'],
                    ':msg' => "Se ha generado la tasa del período {$period} (Factura {$invoiceNumber}) por un importe de \${$subtotal}. Vence el {$dueDate}."
                ]);

                $count++;
            }

            if ($count === 0) {
                throw new \Exception('Ningún comercio activo tiene configurada una Tasa Base superior a $0.00.');
            }

            $db->commit();
            $_SESSION['flash_success'] = "Se generó exitosamente el lote con {$count} facturas.";

        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = "Error al generar lote: " . $e->getMessage();
        }

        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response->withHeader('Location', $basePath . '/admin/facturas')->withStatus(302);
    }

    /**
     * Descargar reporte de Cierre de Caja del día en PDF (GET /admin/cierre-caja/pdf).
     */
    public function downloadCierreCajaPdf(Request $request, Response $response): Response
    {
        $db = Database::getConnection();

        // 1. Obtener cobros de hoy
        $stmt = $db->query("
            SELECT p.*, i.invoice_number, i.period, u.business_name, u.cuit, u.client_code
            FROM payments p
            JOIN invoices i ON p.invoice_id = i.id
            JOIN users u ON i.user_id = u.id
            WHERE DATE(p.payment_date) = CURDATE()
            ORDER BY p.payment_date DESC
        ");
        $payments = $stmt->fetchAll();

        // 2. Calcular los totales
        $totalBase = 0.0;
        $totalMora = 0.0;
        $totalPaid = 0.0;

        foreach ($payments as $p) {
            $totalPaid += floatval($p['amount_paid']);
            $totalMora += floatval($p['surcharge_paid']);
            $totalBase += (floatval($p['amount_paid']) - floatval($p['surcharge_paid']));
        }

        // 3. Generar PDF de cierre de caja
        $pdfService = new \App\Services\PdfService();
        $pdfBinary  = $pdfService->generateCashClosurePdf($payments, $totalBase, $totalMora, $totalPaid, date('Y-m-d'));

        // 4. Configurar respuesta
        $response->getBody()->write($pdfBinary);
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="cierre_caja_' . date('Ymd') . '.pdf"')
            ->withHeader('Cache-Control', 'private, max-age=0, must-revalidate');
    }
}

