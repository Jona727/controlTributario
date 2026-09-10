<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CARGAR LOS 2 COMERCIOS CON CUIT COMPARTIDO ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

echo "El padrón legado tiene dos comercios (COM-000061 y COM-000070) cuyo CUIT\n";
echo "real está repetido en otro comercio que ya está cargado (misma persona,\n";
echo "dos negocios distintos). El sistema usa el CUIT para iniciar sesión, así\n";
echo "que no puede haber dos cuentas con el mismo CUIT — se les asigna un CUIT\n";
echo "de acceso provisorio (mismo formato que se usa cuando falta el CUIT en\n";
echo "una importación) y se los marca para revisión, dejando anotado cuál es\n";
echo "el CUIT real y con qué otro comercio lo comparten.\n\n";

// [codigo, registro_legado, razon_social, titular, domicilio, cuit_real,
//  comercio_con_el_que_comparte_cuit, rubro_code, cuota_fija, fecha_inicio]
$comercios = [
    [
        'codigo'          => 'COM-000061',
        'registro'        => '000061',
        'razon_social'    => 'DANIELA',
        'titular'         => 'LENCINA SILVINA',
        'domicilio'       => '9 DE JULIO',
        'cuit_real'       => '27180005892',
        'comparte_con'    => 'COM-000006 (DANIELA FERIA DE ROPA)',
        'rubro_code'      => '20635',
        'cuota_fija'      => 4000,
        'fecha_inicio'    => '2022-04-05',
    ],
    [
        'codigo'          => 'COM-000070',
        'registro'        => '000070',
        'razon_social'    => 'NESTOR ADRIAN',
        'titular'         => 'SCHONFELD',
        'domicilio'       => 'BUENOS AIRES',
        'cuit_real'       => '20354438519',
        'comparte_con'    => 'COM-000017 (AGUSTINA KIOSCO)',
        'rubro_code'      => '20901',
        'cuota_fija'      => 13000,
        'fecha_inicio'    => '2022-05-11',
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
            owner_name, activity_category, rubro_code, activity_start_date,
            needs_data_review, data_review_reason, legacy_registro
        ) VALUES (
            :code, :name, :cuit, :addr, '', :email, :pass, :base_rate, 3,
            :owner, :rubro, :rubro_code, :inicio,
            1, :review_reason, :legacy
        )
    ");
    $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Bienvenido', 'Su cuenta ha sido creada exitosamente en el Sistema de Control Tributario Municipal.')");

    $creados = 0;
    $yaExistian = 0;
    $credenciales = [];

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
        $reviewReason = "Comparte el CUIT real {$c['cuit_real']} con {$c['comparte_con']} (mismo titular, dos negocios distintos). "
            . "El sistema requiere un CUIT único por cuenta para iniciar sesión, así que se le asignó el CUIT de acceso provisorio "
            . "{$cuitProvisorio}. El CUIT real ante AFIP sigue siendo {$c['cuit_real']}.";
        $tempPassword = generarPasswordTemporal();

        echo "{$c['codigo']} ({$c['razon_social']}): ";
        if ($confirmar) {
            $stmtInsert->execute([
                ':code'          => $c['codigo'],
                ':name'          => $c['razon_social'],
                ':cuit'          => $cuitProvisorio,
                ':addr'          => $c['domicilio'],
                ':email'         => $email,
                ':pass'          => password_hash($tempPassword, PASSWORD_DEFAULT),
                ':base_rate'     => $c['cuota_fija'],
                ':owner'         => $c['titular'],
                ':rubro'         => null,
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
        $credenciales[] = ['code' => $c['codigo'], 'cuit_acceso' => $cuitProvisorio];
        $creados++;
    }

    if ($confirmar) {
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, details)
            VALUES ('users.create_shared_cuit_comercios', 'user', :details)
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
