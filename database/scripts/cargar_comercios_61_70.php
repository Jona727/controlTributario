<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CARGAR COMERCIOS 61 Y 70 ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

echo "Confirmado por el usuario el 10/09/2026:\n";
echo "- COM-000061 (Lencina Silvina Daniela) es la misma persona que ya tiene\n";
echo "  cargada en COM-000006, con un segundo negocio distinto — comparte el\n";
echo "  CUIT real de verdad, así que recibe un CUIT de acceso provisorio.\n";
echo "- COM-000070 (Schonfeldt Nestor): el CUIT que parecía compartir con\n";
echo "  COM-000017 en realidad era un error del padrón viejo — ya se corrigió\n";
echo "  el CUIT de COM-000017 aparte, así que a este SÍ se le carga su CUIT\n";
echo "  real directo (si por algún motivo todavía está en uso, corré primero\n";
echo "  corregir_comercio_17.php para liberarlo).\n\n";

// [codigo, registro, business_name, owner_name, address, cuit_real,
//  usar_cuit_real, comparte_con, rubro_code, cuota_fija, fecha_inicio]
$comercios = [
    [
        'codigo'         => 'COM-000061',
        'registro'       => '000061',
        'business_name'  => 'LENCINA SILVINA DANIELA',
        'owner_name'     => 'LENCINA SILVINA DANIELA',
        'address'        => '9 DE JULIO',
        'cuit_real'      => '27180005892',
        'usar_cuit_real' => false,
        'comparte_con'   => 'COM-000006 (FERIA DE ROPA), mismo titular',
        'rubro_code'     => '20635',
        'cuota_fija'     => 4000,
        'fecha_inicio'   => '2022-04-05',
    ],
    [
        'codigo'         => 'COM-000070',
        'registro'       => '000070',
        'business_name'  => 'SCHONFELDT NESTOR',
        'owner_name'     => 'SCHONFELDT NESTOR',
        'address'        => 'Buenos Aires 0, El Pingo',
        'cuit_real'      => '20354438519',
        'usar_cuit_real' => true,
        'comparte_con'   => null,
        'rubro_code'     => '20901',
        'cuota_fija'     => 13000,
        'fecha_inicio'   => '2022-05-11',
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
    $stmtDupCuit = $db->prepare("SELECT id, client_code FROM users WHERE cuit = :cuit");
    $stmtInsert = $db->prepare("
        INSERT INTO users (
            client_code, business_name, cuit, address, phone, email, password_hash, base_rate, role_id,
            owner_name, rubro_code, activity_start_date,
            needs_data_review, data_review_reason, legacy_registro
        ) VALUES (
            :code, :name, :cuit, :addr, '', :email, :pass, :base_rate, 3,
            :owner, :rubro_code, :inicio,
            :needs_review, :review_reason, :legacy
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
        $tempPassword = generarPasswordTemporal();

        $cuitAUsar = $cuitProvisorio;
        $needsReview = 1;
        $reviewReason = 'Comercio recién incorporado desde el padrón oficial; validar el resto de sus datos.';

        if ($c['usar_cuit_real']) {
            $stmtDupCuit->execute([':cuit' => $c['cuit_real']]);
            $dup = $stmtDupCuit->fetch();
            if ($dup) {
                echo "{$c['codigo']} ({$c['business_name']}): el CUIT real {$c['cuit_real']} todavía está en uso por {$dup['client_code']} — corré corregir_comercio_17.php primero. Se usa un CUIT provisorio mientras tanto.\n";
                $reviewReason = "Debería tener el CUIT real {$c['cuit_real']}, pero todavía estaba en uso por otro comercio al cargarlo — revisar y corregir.";
            } else {
                $cuitAUsar = $c['cuit_real'];
                $needsReview = 0;
                $reviewReason = null;
            }
        } else {
            $reviewReason = "Comparte el CUIT real {$c['cuit_real']} con {$c['comparte_con']}. "
                . "El sistema requiere un CUIT único por cuenta para iniciar sesión, así que se le asignó el CUIT de acceso provisorio "
                . "{$cuitProvisorio}. El CUIT real ante AFIP es {$c['cuit_real']}.";
        }

        echo "{$c['codigo']} ({$c['business_name']}): ";
        if ($confirmar) {
            $stmtInsert->execute([
                ':code'          => $c['codigo'],
                ':name'          => $c['business_name'],
                ':cuit'          => $cuitAUsar,
                ':addr'          => $c['address'],
                ':email'         => $email,
                ':pass'          => password_hash($tempPassword, PASSWORD_DEFAULT),
                ':base_rate'     => $c['cuota_fija'],
                ':owner'         => $c['owner_name'],
                ':rubro_code'    => $c['rubro_code'],
                ':inicio'        => $c['fecha_inicio'],
                ':needs_review'  => $needsReview,
                ':review_reason' => $reviewReason,
                ':legacy'        => $c['registro'],
            ]);
            $newId = (int) $db->lastInsertId();
            $stmtNotif->execute([':uid' => $newId]);
            echo "creado. CUIT: {$cuitAUsar}" . ($cuitAUsar === $c['cuit_real'] ? ' (real)' : ' (provisorio de acceso)') . " / Contraseña temporal: {$tempPassword}\n";
        } else {
            echo "se crearía con CUIT {$cuitAUsar}" . ($cuitAUsar === $c['cuit_real'] ? ' (real)' : ' (provisorio de acceso)') . " (vista previa, contraseña se genera al confirmar)\n";
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
        echo "El que quedó con CUIT provisorio inicia sesión con ese código hasta que se resuelva.\n";
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
