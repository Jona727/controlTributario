<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CARGAR CUIT REAL (respuesta del municipio) ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

// registro => CUIT real (sin guiones), tal como lo contestó el municipio
// sobre la planilla "Comercios sin CUIT registrado"
$cuits = [
    '000035' => '20106810258',
    '000036' => '20333136253',
    '000037' => '30584823069',
    '000039' => '23325090294',
    '000040' => '27163502076',
    '000045' => '27392579686',
    '000050' => '20409910913',
    '000058' => '27352992831',
    '000077' => '27390334368',
    '000083' => '27381331593',
    '000090' => '23247136304',
    '000092' => '27412282618',
    '000104' => '27357166344',
    '000106' => '23360117154',
];

try {
    $db = \App\Config\Database::getConnection();

    if ($confirmar) {
        $db->beginTransaction();
    }

    $stmtUser = $db->prepare("SELECT id, business_name, cuit FROM users WHERE legacy_registro = :reg AND role_id = 3");
    $stmtDupCuit = $db->prepare("SELECT id, client_code FROM users WHERE cuit = :cuit AND id != :id");
    $stmtUpdate = $db->prepare("UPDATE users SET cuit = :cuit, needs_data_review = 0, data_review_reason = NULL WHERE id = :id");

    $actualizados = 0;
    $sinComercio = [];
    $conflictos = [];

    foreach ($cuits as $reg => $cuit) {
        if (strlen($cuit) !== 11) {
            echo "COM-{$reg}: CUIT '{$cuit}' no tiene 11 dígitos — se omite.\n";
            continue;
        }

        $stmtUser->execute([':reg' => $reg]);
        $user = $stmtUser->fetch();
        if (!$user) {
            $sinComercio[] = $reg;
            continue;
        }

        $stmtDupCuit->execute([':cuit' => $cuit, ':id' => $user['id']]);
        $dup = $stmtDupCuit->fetch();
        if ($dup) {
            echo "COM-{$reg} ({$user['business_name']}): el CUIT {$cuit} ya está en uso por {$dup['client_code']} — se omite.\n";
            $conflictos[] = $reg;
            continue;
        }

        echo "COM-{$reg} ({$user['business_name']}): {$user['cuit']} → {$cuit}";
        if ($confirmar) {
            $stmtUpdate->execute([':cuit' => $cuit, ':id' => $user['id']]);
            echo " (aplicado)\n";
        } else {
            echo " (se aplicaría)\n";
        }
        $actualizados++;
    }

    if (!empty($sinComercio)) {
        echo "\n⚠ No se encontró comercio activo para estos registros (se omitieron): " . implode(', ', $sinComercio) . "\n";
    }
    if (!empty($conflictos)) {
        echo "⚠ CUIT en conflicto con otro comercio, revisar a mano: " . implode(', ', $conflictos) . "\n";
    }

    if ($confirmar) {
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, details)
            VALUES ('users.cuit_respuesta_municipio', 'user', :details)
        ");
        $auditStmt->execute([
            ':details' => json_encode(['actualizados' => $actualizados]),
        ]);
        $db->commit();
    }

    echo "\nComercios actualizados: {$actualizados}\n";

    echo "\n";
    if ($confirmar) {
        echo "=== CAMBIOS APLICADOS ===\n";
        echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";
    } else {
        echo "=== ESTO FUE SOLO UNA VISTA PREVIA — NO SE GUARDÓ NADA ===\n";
        echo "Si los datos de arriba te parecen correctos, volvé a abrir esta URL agregando ?confirmar=SI\n";
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
