<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== DESACTIVAR COMERCIOS CERRADOS (padrón oficial) ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

echo "El padrón oficial trae la fecha de cierre real de cada comercio que dejó\n";
echo "de operar. No se borra nada — solo se marca is_active = 0, así dejan de\n";
echo "poder facturarse pero conservan todo su historial de facturas y pagos.\n\n";

// registro_legado => 'fecha de cierre (motivo)', tal como figura en el padrón oficial
$cerrados = [
    '000003' => '2023-12-05 (cierre)',
    '000008' => '2022-10-27 (cierre)',
    '000015' => '2023-03-15 (cierre)',
    '000017' => '2026-08-12 (cierre)',
    '000019' => '2024-09-10 (cierre)',
    '000022' => '2026-01-26 (cierre)',
    '000023' => '2023-12-10 (cierre)',
    '000028' => '2022-02-15 (cierre)',
    '000029' => '2026-02-01 (cierre)',
    '000031' => '2024-06-01 (cierre)',
    '000032' => '2023-11-21 (cierre)',
    '000034' => '2022-02-01 (cierre)',
    '000043' => '2024-03-01 (cierre)',
    '000044' => '2026-01-03 (cierre)',
    '000046' => '2022-07-01 (cierre)',
    '000051' => '2024-03-18 (cierre)',
    '000052' => '2024-02-06 (por cierre)',
    '000056' => '2025-06-19 (cierre)',
    '000060' => '2023-12-05 (cierre)',
    '000065' => '2023-04-28 (cierre)',
    '000066' => '2022-04-28 (cierre)',
    '000067' => '2022-04-28 (cierre)',
    '000068' => '2022-04-28 (cierre)',
    '000074' => '2024-03-18 (cierre)',
    '000076' => '2024-02-01 (cierre)',
    '000087' => '2024-10-09 (cierre)',
];

try {
    $db = \App\Config\Database::getConnection();

    if ($confirmar) {
        $db->beginTransaction();
    }

    $stmtSelect = $db->prepare("SELECT id, business_name, is_active FROM users WHERE legacy_registro = :reg AND role_id = 3");
    $stmtUpdate = $db->prepare("UPDATE users SET is_active = 0 WHERE id = :id");

    $desactivados = 0;
    $yaEstaban = 0;
    $sinComercio = [];

    foreach ($cerrados as $reg => $detalle) {
        $stmtSelect->execute([':reg' => $reg]);
        $user = $stmtSelect->fetch();
        if (!$user) {
            $sinComercio[] = $reg;
            continue;
        }
        if ((int) $user['is_active'] === 0) {
            $yaEstaban++;
            continue;
        }

        echo "COM-{$reg} ({$user['business_name']}): cerrado el {$detalle}";
        if ($confirmar) {
            $stmtUpdate->execute([':id' => $user['id']]);
            echo " → desactivado.\n";
        } else {
            echo " → se desactivaría.\n";
        }
        $desactivados++;
    }

    if (!empty($sinComercio)) {
        echo "\n⚠ No se encontró comercio activo para estos registros (se omitieron): " . implode(', ', $sinComercio) . "\n";
    }

    if ($confirmar) {
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, details)
            VALUES ('users.bulk_deactivate_padron_oficial', 'user', :details)
        ");
        $auditStmt->execute([
            ':details' => json_encode(['desactivados' => $desactivados, 'ya_estaban_inactivos' => $yaEstaban]),
        ]);
        $db->commit();
    }

    echo "\nComercios desactivados: {$desactivados}\n";
    echo "Ya estaban inactivos: {$yaEstaban}\n";

    echo "\n";
    if ($confirmar) {
        echo "=== CAMBIOS APLICADOS ===\n";
        echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";
    } else {
        echo "=== ESTO FUE SOLO UNA VISTA PREVIA — NO SE GUARDÓ NADA ===\n";
        echo "Si los números de arriba te parecen correctos, volvé a abrir esta URL agregando ?confirmar=SI\n";
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
