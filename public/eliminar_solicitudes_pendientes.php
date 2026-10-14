<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/_auth_guard.php';

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== ELIMINAR SOLICITUDES DE ESTADO DE CUENTA PENDIENTES ===\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("
        SELECT s.id, s.requested_at, u.client_code, u.business_name
        FROM account_status_requests s
        JOIN users u ON s.user_id = u.id
        WHERE s.status = 'pending'
        ORDER BY s.requested_at ASC
    ");
    $pendientes = $stmt->fetchAll();

    if (empty($pendientes)) {
        echo "No hay solicitudes pendientes — no hay nada para eliminar.\n";
        exit;
    }

    echo "Se van a eliminar " . count($pendientes) . " solicitud(es) pendiente(s):\n\n";
    foreach ($pendientes as $p) {
        echo "  #{$p['id']}  {$p['client_code']} — {$p['business_name']}  (pedida el " . date('d/m/Y H:i', strtotime($p['requested_at'])) . ")\n";
    }
    echo "\nEsto no afecta las facturas ni los datos del comercio — solo borra el pedido. El comercio va a poder volver a solicitar su estado de cuenta si lo necesita.\n\n";

    if (!$confirmar) {
        echo "*** MODO VISTA PREVIA (no se eliminó nada) ***\n";
        echo "Para aplicar de verdad, agregá &confirmar=SI al final de la URL.\n";
        exit;
    }

    $ids = array_column($pendientes, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtDelete = $db->prepare("DELETE FROM account_status_requests WHERE id IN ({$placeholders}) AND status = 'pending'");
    $stmtDelete->execute($ids);

    echo "=== LISTO — " . $stmtDelete->rowCount() . " solicitud(es) eliminada(s) ===\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
