<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CORREGIR COMERCIO 17 (CUIT real, no el que chocaba con el 70) ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

echo "El CUIT que tenía cargado COM-000017 (20-35443851-9) en realidad era el\n";
echo "de COM-000070 — el usuario confirmó el CUIT real de este comercio.\n";
echo "También lo marca desactivado.\n\n";

$owner    = 'BAEZ AGUSTINA';
$cuit     = '27404054509';
$address  = 'Bv 9 de Julio 47';
$rubroCode = '20613';

try {
    $db = \App\Config\Database::getConnection();

    $stmtUser = $db->prepare("SELECT id, business_name, cuit, address, rubro_code, is_active FROM users WHERE legacy_registro = '000017' AND role_id = 3");
    $stmtUser->execute();
    $user = $stmtUser->fetch();

    if (!$user) {
        echo "🚨 No se encontró COM-000017 (legacy_registro 000017) en la base.\n";
        exit;
    }

    $stmtDupCuit = $db->prepare("SELECT id, client_code FROM users WHERE cuit = :cuit AND id != :id");
    $stmtDupCuit->execute([':cuit' => $cuit, ':id' => $user['id']]);
    $dup = $stmtDupCuit->fetch();
    if ($dup) {
        echo "🚨 El CUIT {$cuit} ya está en uso por {$dup['client_code']}. No se aplica ningún cambio.\n";
        exit;
    }

    $stmtTarifa = $db->prepare("SELECT cuota_fija FROM tarifas WHERE codigo = :codigo");
    $stmtTarifa->execute([':codigo' => $rubroCode]);
    $tarifa = $stmtTarifa->fetch();
    $cuotaFija = $tarifa ? (float) $tarifa['cuota_fija'] : null;

    echo "COM-000017 — antes:\n";
    echo "  business_name: {$user['business_name']}\n";
    echo "  cuit: {$user['cuit']}\n";
    echo "  address: {$user['address']}\n";
    echo "  rubro_code: " . ($user['rubro_code'] ?? '(sin asignar)') . "\n";
    echo "  is_active: {$user['is_active']}\n\n";

    echo "COM-000017 — después:\n";
    echo "  owner_name: {$owner}\n";
    echo "  cuit: {$cuit}\n";
    echo "  address: {$address}\n";
    echo "  rubro_code: {$rubroCode} (cuota \${$cuotaFija})\n";
    echo "  is_active: 0\n\n";

    if ($confirmar) {
        $stmt = $db->prepare("
            UPDATE users SET
                owner_name  = :owner,
                cuit        = :cuit,
                address     = :addr,
                rubro_code  = :rubro_code,
                base_rate   = :base_rate,
                is_active   = 0
            WHERE id = :id
        ");
        $stmt->execute([
            ':owner'      => $owner,
            ':cuit'       => $cuit,
            ':addr'       => $address,
            ':rubro_code' => $rubroCode,
            ':base_rate'  => $cuotaFija,
            ':id'         => $user['id'],
        ]);

        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, entity_id, details)
            VALUES ('users.correct_com_000017', 'user', :eid, :details)
        ");
        $auditStmt->execute([
            ':eid'     => $user['id'],
            ':details' => json_encode(['owner_name' => $owner, 'cuit' => $cuit, 'address' => $address, 'rubro_code' => $rubroCode, 'is_active' => 0]),
        ]);

        echo "=== CAMBIOS APLICADOS ===\n";
        echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";
    } else {
        echo "=== ESTO FUE SOLO UNA VISTA PREVIA — NO SE GUARDÓ NADA ===\n";
        echo "Si te parece bien, volvé a abrir esta URL agregando ?confirmar=SI\n";
    }

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
