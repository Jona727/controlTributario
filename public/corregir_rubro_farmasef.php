<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== ASIGNAR RUBRO A FARMASEF S.R.L. (COM-000072) ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

$rubroCode = '20638';

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->prepare("SELECT id, business_name, rubro_code, base_rate FROM users WHERE legacy_registro = '000072' AND role_id = 3");
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        echo "🚨 No se encontró COM-000072 (legacy_registro 000072) en la base.\n";
        exit;
    }

    $stmtTarifa = $db->prepare("SELECT rubro, cuota_fija FROM tarifas WHERE codigo = :codigo");
    $stmtTarifa->execute([':codigo' => $rubroCode]);
    $tarifa = $stmtTarifa->fetch();

    if (!$tarifa) {
        echo "🚨 No se encontró el código {$rubroCode} en la tabla de tarifas.\n";
        exit;
    }

    echo "COM-000072 ({$user['business_name']}) — antes:\n";
    echo "  rubro_code: " . ($user['rubro_code'] ?? '(sin asignar)') . "\n";
    echo "  base_rate: {$user['base_rate']}\n\n";

    echo "COM-000072 — después:\n";
    echo "  rubro_code: {$rubroCode} ({$tarifa['rubro']})\n";
    echo "  base_rate: {$tarifa['cuota_fija']}\n\n";

    if ($confirmar) {
        $stmt = $db->prepare("UPDATE users SET rubro_code = :rubro_code, base_rate = :base_rate WHERE id = :id");
        $stmt->execute([
            ':rubro_code' => $rubroCode,
            ':base_rate'  => $tarifa['cuota_fija'],
            ':id'         => $user['id'],
        ]);

        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, entity_id, details)
            VALUES ('users.assign_rubro_farmasef', 'user', :eid, :details)
        ");
        $auditStmt->execute([
            ':eid'     => $user['id'],
            ':details' => json_encode(['rubro_code' => $rubroCode, 'base_rate' => $tarifa['cuota_fija']]),
        ]);

        echo "=== CAMBIOS APLICADOS ===\n";
        echo "Ojo: esto solo actualiza la Tasa Base para facturas NUEVAS que se generen de acá en adelante.\n";
        echo "Las facturas pendientes que ya tiene FARMASEF no se tocan (van a seguir mostrando el monto con el que se cargaron).\n";
        echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";
    } else {
        echo "=== ESTO FUE SOLO UNA VISTA PREVIA — NO SE GUARDÓ NADA ===\n";
        echo "Si te parece bien, volvé a abrir esta URL agregando ?confirmar=SI\n";
    }

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
