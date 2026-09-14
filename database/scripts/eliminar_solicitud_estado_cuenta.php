<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$eliminarId = isset($_GET['eliminar_id']) ? (int) $_GET['eliminar_id'] : null;
$confirmar  = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== SOLICITUDES DE ESTADO DE CUENTA ===\n\n";

try {
    $db = \App\Config\Database::getConnection();

    if ($eliminarId !== null) {
        $stmt = $db->prepare("
            SELECT s.*, u.client_code, u.business_name
            FROM account_status_requests s
            JOIN users u ON s.user_id = u.id
            WHERE s.id = :id
        ");
        $stmt->execute([':id' => $eliminarId]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            echo "No existe ninguna solicitud con id={$eliminarId}.\n";
        } else {
            echo "Solicitud #{$eliminarId} — {$solicitud['client_code']} ({$solicitud['business_name']})\n";
            echo "Estado: {$solicitud['status']} — Pedida: {$solicitud['requested_at']}\n";
            if (!empty($solicitud['response_message'])) {
                echo "Respuesta: {$solicitud['response_message']}\n";
            }
            echo "\n";

            if (!$confirmar) {
                echo "*** MODO VISTA PREVIA — no se borró nada ***\n";
                echo "Para borrar de verdad esta solicitud, volvé a abrir esta URL agregando &confirmar=SI al final.\n";
            } else {
                $del = $db->prepare("DELETE FROM account_status_requests WHERE id = :id");
                $del->execute([':id' => $eliminarId]);
                echo "Borrada.\n";
            }
        }
        echo "\n--- Volver al listado completo: quitá '?eliminar_id=...' de la URL ---\n";
    } else {
        $stmt = $db->query("
            SELECT s.id, s.status, s.requested_at, s.resolved_at, s.response_message,
                   u.client_code, u.business_name
            FROM account_status_requests s
            JOIN users u ON s.user_id = u.id
            ORDER BY s.requested_at DESC
        ");
        $solicitudes = $stmt->fetchAll();

        if (empty($solicitudes)) {
            echo "No hay ninguna solicitud cargada.\n";
        } else {
            foreach ($solicitudes as $s) {
                $resp = $s['response_message'] ? substr($s['response_message'], 0, 60) : '(sin respuesta)';
                echo "#{$s['id']} — {$s['client_code']} ({$s['business_name']}) — {$s['status']} — pedida {$s['requested_at']} — {$resp}\n";
                echo "   Para borrar esta: agregá a la URL ?eliminar_id={$s['id']}\n\n";
            }
        }
    }

    echo "\n=== FIN ===\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
