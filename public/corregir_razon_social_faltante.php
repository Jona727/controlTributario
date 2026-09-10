<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CORREGIR RAZÓN SOCIAL DE COMERCIOS SIN NOMBRE COMERCIAL PROPIO ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

echo "El padrón oficial trae 'razón social' vacía para estos 14 comercios —\n";
echo "no tienen un nombre de fantasía propio, así que su nombre completo\n";
echo "(columna 'nombre' del padrón, que en estos casos es el nombre del\n";
echo "titular o de la razón social completa como en EL EMPALME \"EL PINGO\"\n";
echo "S.R.L.) es lo que hay que mostrar como Razón Social. La corrección\n";
echo "anterior (corregir_datos_comercios.php) ya había actualizado el\n";
echo "Titular en estos casos, pero dejaba la Razón Social vieja (recortada\n";
echo "por el OCR de las fotos) sin tocar.\n\n";

// registro => nombre completo (se usa como business_name Y owner_name)
$nombres = [
    '000003' => 'FRUTOS MIRTA',
    '000007' => 'ALVAREZ ERCILIA GRACIELA',
    '000014' => 'BENITEZ PAULA CAROLINA',
    '000058' => 'ALDANA MARIA EMILIA',
    '000063' => 'EL EMPALME "EL PINGO" S.R.L.',
    '000069' => 'GONZALEZ ALBANA SOLEDAD',
    '000074' => 'GOMEZ CARINA',
    '000075' => 'HABERKON HORACIO',
    '000081' => 'RODRIGUEZ MARCELO ALEJANDRO',
    '000083' => 'MORENO DAIANA',
    '000095' => 'CACERES MARIA MARGARITA',
    '000096' => 'CHAJUD LEILA',
    '000104' => 'ALARCON ANTONELLLA DESIREE',
    '000105' => 'VALDEZ ANGELICA DANIELA',
];

try {
    $db = \App\Config\Database::getConnection();

    if ($confirmar) {
        $db->beginTransaction();
    }

    $stmtSelect = $db->prepare("SELECT id, business_name, owner_name FROM users WHERE legacy_registro = :reg AND role_id = 3");
    $stmtUpdate = $db->prepare("UPDATE users SET business_name = :name, owner_name = :name2 WHERE id = :id");

    $corregidos = 0;
    $sinComercio = [];

    foreach ($nombres as $reg => $nombre) {
        $stmtSelect->execute([':reg' => $reg]);
        $user = $stmtSelect->fetch();
        if (!$user) {
            $sinComercio[] = $reg;
            continue;
        }

        echo "COM-{$reg}: '{$user['business_name']}' → '{$nombre}'";
        if ($confirmar) {
            $stmtUpdate->execute([':name' => $nombre, ':name2' => $nombre, ':id' => $user['id']]);
            echo " (aplicado)\n";
        } else {
            echo " (se aplicaría)\n";
        }
        $corregidos++;
    }

    if (!empty($sinComercio)) {
        echo "\n⚠ No se encontró comercio activo para estos registros (se omitieron, probablemente todavía no cargados): " . implode(', ', $sinComercio) . "\n";
    }

    if ($confirmar) {
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, details)
            VALUES ('users.fix_razon_social_faltante', 'user', :details)
        ");
        $auditStmt->execute([
            ':details' => json_encode(['corregidos' => $corregidos]),
        ]);
        $db->commit();
    }

    echo "\nComercios corregidos: {$corregidos}\n";

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
