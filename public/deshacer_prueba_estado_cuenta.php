<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/_auth_guard.php';

header('Content-Type: text/plain; charset=utf-8');

$clientCode = trim($_GET['client_code'] ?? '');
$horas      = max(1, (int) ($_GET['horas'] ?? 6));
$confirmar  = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== DESHACER PRUEBA DE ESTADO DE CUENTA ===\n";
echo "(Solo toca al comercio indicado, y solo lo que se movió en las últimas {$horas} hora(s))\n\n";

if ($clientCode === '') {
    echo "Falta indicar el comercio. Agregá a la URL: ?client_code=COM-000XXX\n";
    echo "Opcional: &horas=N (por defecto 6) para ajustar la ventana de tiempo.\n";
    exit;
}

if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se toca nada) ***\n";
    echo "Para aplicar de verdad, agregá &confirmar=SI al final de la URL.\n\n";
}

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->prepare("SELECT id, business_name FROM users WHERE client_code = :code AND role_id = 3");
    $stmt->execute([':code' => $clientCode]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "No existe ningún comercio con código '{$clientCode}'.\n";
        exit;
    }

    $userId = (int) $user['id'];
    echo "Comercio: {$clientCode} — {$user['business_name']}\n\n";

    // 1. Solicitudes de estado de cuenta recientes de este comercio
    $stmt = $db->prepare("
        SELECT id, status, requested_at FROM account_status_requests
        WHERE user_id = :uid AND requested_at >= NOW() - INTERVAL {$horas} HOUR
        ORDER BY requested_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $solicitudes = $stmt->fetchAll();

    echo "--- Solicitudes a borrar (" . count($solicitudes) . ") ---\n";
    foreach ($solicitudes as $s) {
        echo "  #{$s['id']} — {$s['status']} — {$s['requested_at']}\n";
    }

    // 2. Facturas nuevas (creadas al conciliar) recientes de este comercio.
    // Se excluyen las que ya quedaron 'cancelled': esas las maneja el paso
    // siguiente (restaurar), no este (borrar) — si coincidieran acá también
    // se borrarían antes de poder restaurarlas.
    $stmt = $db->prepare("
        SELECT id, invoice_number, period, subtotal, status, created_at FROM invoices
        WHERE user_id = :uid AND created_at >= NOW() - INTERVAL {$horas} HOUR AND status != 'cancelled'
        ORDER BY created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $facturasNuevas = $stmt->fetchAll();

    echo "\n--- Facturas nuevas a borrar (" . count($facturasNuevas) . ") ---\n";
    foreach ($facturasNuevas as $f) {
        echo "  {$f['invoice_number']} — {$f['period']} — \$ {$f['subtotal']} — {$f['status']} — creada {$f['created_at']}\n";
    }

    // 3. Facturas viejas que quedaron canceladas por el checkbox "cancelar anteriores", a restaurar
    $stmt = $db->prepare("
        SELECT id, invoice_number, period, subtotal, updated_at FROM invoices
        WHERE user_id = :uid AND status = 'cancelled' AND updated_at >= NOW() - INTERVAL {$horas} HOUR
        ORDER BY updated_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $facturasACancelar = $stmt->fetchAll();

    echo "\n--- Facturas viejas a restaurar a 'pending' (" . count($facturasACancelar) . ") ---\n";
    foreach ($facturasACancelar as $f) {
        echo "  {$f['invoice_number']} — {$f['period']} — \$ {$f['subtotal']} — cancelada {$f['updated_at']}\n";
    }

    // 4. Notificación generada para el comercio
    $stmt = $db->prepare("
        SELECT id, title, created_at FROM notifications
        WHERE user_id = :uid AND title = 'Tu estado de cuenta real' AND created_at >= NOW() - INTERVAL {$horas} HOUR
        ORDER BY created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $notifs = $stmt->fetchAll();

    echo "\n--- Notificaciones a borrar (" . count($notifs) . ") ---\n";
    foreach ($notifs as $n) {
        echo "  #{$n['id']} — {$n['created_at']}\n";
    }

    if ($confirmar) {
        $db->beginTransaction();

        if (!empty($solicitudes)) {
            $ids = implode(',', array_map('intval', array_column($solicitudes, 'id')));
            $db->exec("DELETE FROM account_status_requests WHERE id IN ({$ids})");
        }
        if (!empty($facturasNuevas)) {
            $ids = implode(',', array_map('intval', array_column($facturasNuevas, 'id')));
            $db->exec("DELETE FROM invoices WHERE id IN ({$ids})"); // cascada a invoice_items
        }
        if (!empty($facturasACancelar)) {
            $ids = implode(',', array_map('intval', array_column($facturasACancelar, 'id')));
            $db->exec("UPDATE invoices SET status = 'pending' WHERE id IN ({$ids})");
        }
        if (!empty($notifs)) {
            $ids = implode(',', array_map('intval', array_column($notifs, 'id')));
            $db->exec("DELETE FROM notifications WHERE id IN ({$ids})");
        }

        $db->commit();
        echo "\n=== LISTO — se deshizo la prueba ===\n";
        echo "Las facturas restauradas quedan como 'pending'; si su vencimiento ya pasó, el sistema las va a volver a marcar 'overdue' solas la próxima vez que se abra cualquier pantalla (eso ya es automático).\n";
    } else {
        echo "\n=== ESTO FUE SOLO UNA VISTA PREVIA — NO SE TOCÓ NADA ===\n";
        echo "Si la lista de arriba es lo que esperabas borrar/restaurar, volvé a abrir esta URL agregando &confirmar=SI\n";
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
