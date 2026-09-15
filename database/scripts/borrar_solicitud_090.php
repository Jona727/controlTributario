<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== BORRAR SOLICITUD DE ESTADO DE CUENTA DE PRUEBA — COM-000090 ===\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->prepare("SELECT id, business_name FROM users WHERE client_code = 'COM-000090' AND role_id = 3");
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        echo "No existe ningún comercio con código COM-000090.\n";
        exit;
    }

    $userId = (int) $user['id'];
    echo "Comercio: COM-000090 — {$user['business_name']}\n\n";

    $stmt = $db->prepare("
        SELECT id, status, requested_at FROM account_status_requests
        WHERE user_id = :uid
        ORDER BY requested_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $solicitudes = $stmt->fetchAll();

    echo "--- Solicitudes encontradas para este comercio (" . count($solicitudes) . ") ---\n";
    foreach ($solicitudes as $s) {
        echo "  #{$s['id']} — {$s['status']} — {$s['requested_at']}\n";
    }

    if (empty($solicitudes)) {
        echo "\nNo hay ninguna solicitud para borrar.\n";
        exit;
    }

    if (!$confirmar) {
        echo "\n*** MODO VISTA PREVIA (no se borró nada) ***\n";
        echo "Para aplicar de verdad, agregá &confirmar=SI al final de la URL.\n";
        exit;
    }

    $ids = implode(',', array_map('intval', array_column($solicitudes, 'id')));
    $db->exec("DELETE FROM account_status_requests WHERE id IN ({$ids})");

    echo "\n=== LISTO — se borraron " . count($solicitudes) . " solicitud(es) ===\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
