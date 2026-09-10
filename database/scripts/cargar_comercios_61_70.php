<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CARGAR COMERCIOS 61 Y 70 (CUIT compartido, confirmado) ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

echo "Confirmado por el usuario el 10/09/2026:\n";
echo "- COM-000061 (Lencina Silvina Daniela) es la misma persona que ya tiene\n";
echo "  cargada en COM-000006, con un segundo negocio distinto.\n";
echo "- COM-000070 (Schonfeldt Nestor) comparte el CUIT real con COM-000017,\n";
echo "  que se da de baja aparte (ya está en el script de comercios cerrados).\n";
echo "El sistema usa el CUIT para iniciar sesión, así que cada uno recibe un\n";
echo "CUIT de acceso provisorio; el CUIT real queda anotado para revisión.\n\n";

// [codigo, registro, business_name, owner_name, address, cuit_real,
//  comparte_con, rubro_code, cuota_fija, fecha_inicio]
$comercios = [
    [
        'codigo'       => 'COM-000061',
        'registro'     => '000061',
        'business_name'=> 'LENCINA SILVINA DANIELA',
        'owner_name'   => 'LENCINA SILVINA DANIELA',
        'address'      => '9 DE JULIO',
        'cuit_real'    => '27180005892',
        'comparte_con' => 'COM-000006 (FERIA DE ROPA), mismo titular',
        'rubro_code'   => '20635',
        'cuota_fija'   => 4000,
        'fecha_inicio' => '2022-04-05',
    ],
    [
        'codigo'       => 'COM-000070',
        'registro'     => '000070',
        'business_name'=> 'SCHONFELDT NESTOR',
        'owner_name'   => 'SCHONFELDT NESTOR',
        'address'      => 'Buenos Aires 0, El Pingo',
        'cuit_real'    => '20354438519',
        'comparte_con' => 'COM-000017, dado de baja',
        'rubro_code'   => '20901',
        'cuota_fija'   => 13000,
        'fecha_inicio' => '2022-05-11',
    ],
];

function generarPasswordTemporal(int $length = 8): string
{
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $password;
}

try {
    $db = \App\Config\Database::getConnection();

    if ($confirmar) {
        $db->beginTransaction();
    }

    $stmtCheck = $db->prepare("SELECT id FROM users WHERE legacy_registro = :reg AND role_id = 3");
    $stmtInsert = $db->prepare("
        INSERT INTO users (
            client_code, business_name, cuit, address, phone, email, password_hash, base_rate, role_id,
            owner_name, rubro_code, activity_start_date,
            needs_data_review, data_review_reason, legacy_registro
        ) VALUES (
            :code, :name, :cuit, :addr, '', :email, :pass, :base_rate, 3,
            :owner, :rubro_code, :inicio,
            1, :review_reason, :legacy
        )
    ");
    $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Bienvenido', 'Su cuenta ha sido creada exitosamente en el Sistema de Control Tributario Municipal.')");

    $creados = 0;
    $yaExistian = 0;

    foreach ($comercios as $c) {
        $stmtCheck->execute([':reg' => $c['registro']]);
        if ($stmtCheck->fetch()) {
            echo "{$c['codigo']}: ya existe (legacy_registro {$c['registro']}). Se omite.\n";
            $yaExistian++;
            continue;
        }

        $codeAlnum = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $c['codigo']));
        $cuitProvisorio = 'SC-' . substr($codeAlnum, -10);
        $email = 'sin-email.' . preg_replace('/[^a-z0-9]+/', '-', strtolower($c['codigo'])) . '@controltributario.local';
        $reviewReason = "Comparte el CUIT real {$c['cuit_real']} con {$c['comparte_con']}. "
            . "El sistema requiere un CUIT único por cuenta para iniciar sesión, así que se le asignó el CUIT de acceso provisorio "
            . "{$cuitProvisorio}. El CUIT real ante AFIP es {$c['cuit_real']}.";
        $tempPassword = generarPasswordTemporal();

        echo "{$c['codigo']} ({$c['business_name']}): ";
        if ($confirmar) {
            $stmtInsert->execute([
                ':code'          => $c['codigo'],
                ':name'          => $c['business_name'],
                ':cuit'          => $cuitProvisorio,
                ':addr'          => $c['address'],
                ':email'         => $email,
                ':pass'          => password_hash($tempPassword, PASSWORD_DEFAULT),
                ':base_rate'     => $c['cuota_fija'],
                ':owner'         => $c['owner_name'],
                ':rubro_code'    => $c['rubro_code'],
                ':inicio'        => $c['fecha_inicio'],
                ':review_reason' => $reviewReason,
                ':legacy'        => $c['registro'],
            ]);
            $newId = (int) $db->lastInsertId();
            $stmtNotif->execute([':uid' => $newId]);
            echo "creado. CUIT de acceso: {$cuitProvisorio} / Contraseña temporal: {$tempPassword}\n";
        } else {
            echo "se crearía con CUIT de acceso {$cuitProvisorio} (vista previa, contraseña se genera al confirmar)\n";
        }
        $creados++;
    }

    if ($confirmar) {
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, details)
            VALUES ('users.create_comercios_61_70', 'user', :details)
        ");
        $auditStmt->execute([
            ':details' => json_encode(['creados' => $creados, 'ya_existian' => $yaExistian]),
        ]);
        $db->commit();
    }

    echo "\n";
    echo "Comercios creados: {$creados}\n";
    echo "Ya existían (se omitieron): {$yaExistian}\n\n";

    if ($confirmar) {
        echo "=== CAMBIOS APLICADOS ===\n";
        echo "Anotá las contraseñas temporales de arriba — no se pueden volver a ver.\n";
        echo "Cada comercio inicia sesión con el CUIT de acceso (no el CUIT real) hasta que cambien la contraseña.\n";
        echo "Recordá también correr (o confirmar que ya corriste) el script de comercios cerrados, que incluye a COM-000017.\n";
        echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";
    } else {
        echo "=== ESTO FUE SOLO UNA VISTA PREVIA — NO SE GUARDÓ NADA ===\n";
        echo "Si te parece bien, volvé a abrir esta URL agregando ?confirmar=SI\n";
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
