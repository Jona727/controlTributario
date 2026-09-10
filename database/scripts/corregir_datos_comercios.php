<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CORREGIR DATOS DE COMERCIOS (padrón oficial actualizado) ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

// ─────────────────────────────────────────────────────────────
// 1. Correcciones de business_name / owner_name / address / phone,
//    tomadas del padrón oficial actualizado (archivo subido el
//    10/09/2026). Cada comercio solo trae los campos que realmente
//    cambiaron respecto a lo cargado — el resto queda tal cual está.
// ─────────────────────────────────────────────────────────────
$correcciones = [
    '000001' => ['business_name' => 'TITO', 'owner_name' => 'FLER Vicente Antonio'],
    '000002' => ['business_name' => 'DON CHAYA', 'owner_name' => 'CHAJUD NERI YAMIL'],
    '000003' => ['phone' => '3434383268'],
    '000004' => ['business_name' => '"MISIGI"', 'owner_name' => 'BLANCO WALQUIRIA Y CHAJUD RUBEN OSCAR'],
    '000005' => ['business_name' => 'DON TOMAS', 'owner_name' => 'SCHONFELDT MAURO EXEQUIEL', 'address' => 'SANTIAGO DEL ESTERO 255', 'phone' => '3434630957'],
    '000006' => ['business_name' => 'FERIA DE ROPA', 'owner_name' => 'LENCINA SILVINA DANIELA'],
    '000007' => ['owner_name' => 'ALVAREZ ERCILIA GRACIELA', 'address' => 'NICOLAS AVELLANEDA 147'],
    '000008' => ['business_name' => 'TIENDA', 'owner_name' => 'BARBERIS JOHANA ELIZABETH'],
    '000009' => ['business_name' => 'AUTOSERVICIO CENTRO', 'owner_name' => 'MASLEIN FACUNDO YAIR'],
    '000010' => ['business_name' => 'ROTISERIA GRANJA LA PAISANITA', 'owner_name' => 'OJEDA MARIA DE LOS ANGELES', 'address' => 'SANTA FE 548'],
    '000011' => ['owner_name' => 'ALVAREZ JUAN ORLANDO'],
    '000012' => ['business_name' => 'AUTOSERVICIO CENTRO', 'owner_name' => 'ROMERO ANTONIA CRISTINA'],
    '000013' => ['business_name' => '"LA FAMILIA"', 'owner_name' => 'RAUSCH FLAVIA SILVINA ANABELLA', 'address' => 'JUAN DOMINGO PERON 139'],
    '000014' => ['owner_name' => 'BENITEZ PAULA CAROLINA'],
    '000015' => ['business_name' => 'LOS DOS HERMANOS', 'owner_name' => 'SCHONFELDT GLADYS LILIANA'],
    '000016' => ['business_name' => 'TOMBOLA', 'owner_name' => 'SCHONFELDT MAURO EZEQUIEL', 'phone' => '3434684122'],
    '000017' => ['business_name' => 'KIOSCO', 'owner_name' => 'BAEZ MARIA AGUSTINA'],
    '000018' => ['business_name' => 'PELUQUERIA', 'owner_name' => 'GODOY MARIA GRACIELA', 'phone' => '3435353225'],
    '000019' => ['business_name' => 'ROTISERIA', 'owner_name' => 'QUIROGA LEANDRO EMANUEL', 'phone' => '3434619472'],
    '000021' => ['business_name' => 'BAR', 'owner_name' => 'DITTLER RUBEN GERMAN', 'phone' => '3436234828'],
    '000022' => ['business_name' => 'PANADERIA', 'owner_name' => 'MARTINEZ, JOAQUIN WALTER JESUS', 'phone' => '3434624302'],
    '000023' => ['business_name' => 'BAR', 'owner_name' => 'RODRIGUEZ CESAR', 'phone' => '3434609413'],
    '000024' => ['business_name' => 'KIOSCO Y ROTISERIA', 'owner_name' => 'VAZQUEZ DELIA MARINA', 'address' => 'DOLORES DE URRUTIA 187'],
    '000025' => ['owner_name' => 'SOSA  FABIANA', 'phone' => '343154658199'],
    '000026' => ['business_name' => 'PANADERIA', 'owner_name' => 'RODRIGUEZ HUGO ABEL'],
    '000027' => ['business_name' => '"KISA"', 'owner_name' => 'BLANCO SABRINA BEATRIZ', 'phone' => '3435195300'],
    '000028' => ['business_name' => 'REMIS', 'owner_name' => 'MARIONI JOAQUIN ANTONIO', 'phone' => '3434194857'],
    '000029' => ['business_name' => 'REMIS', 'owner_name' => 'VALDEMARIN, JACINTO BAUTISTA', 'phone' => '3434533073'],
    '000030' => ['business_name' => 'REMIS', 'owner_name' => 'MIÑO AURELIO ULISES', 'phone' => '3434535297'],
    '000031' => ['business_name' => 'REMIS', 'owner_name' => 'PAEZ JORGE RAUL', 'phone' => '3434058902'],
    '000032' => ['phone' => '3434470125'],
    '000033' => ['phone' => '3436114190'],
    '000034' => ['phone' => '3436985210'],
    '000035' => ['business_name' => 'FERRETERIA LA UNICA', 'owner_name' => 'DITTLER CARLOS OSCAR'],
    '000037' => ['business_name' => 'COOPERATIVA DE AGUA POTABLE', 'owner_name' => 'COOPERATIVA DE AGUA POTABLE Y OTROS SERVICIOS', 'address' => 'JUAN DE PERON'],
    '000038' => ['business_name' => 'POLIRRUBRO,  COMERCIOS NO ESPECIALIZADOS Y KIOSCO', 'owner_name' => 'HABERKON MARCELO MAXIMILIANO', 'address' => 'JUAN DE PERON', 'phone' => '3435000518'],
    '000039' => ['business_name' => 'VENTA AL POR MENOR DE ARTICULOS DE FERRETERIA Y MATERIALES E', 'owner_name' => 'SCARAFIA JESICA ELIZABETH', 'address' => 'ENTRE RIOS'],
    '000040' => ['business_name' => 'ALQUILERES', 'owner_name' => 'ROMERO ANTONIA CRISTINA'],
    '000041' => ['business_name' => 'MULTIRRUBRO PAÑALERA', 'owner_name' => 'ROLDAN, ANDREA FABIANA'],
    '000043' => ['business_name' => 'REMIS', 'owner_name' => 'ESPINDOLA JUAN EDUARDO'],
    '000044' => ['business_name' => 'EXPLOTACION DE INSTALACIONES DEPORTIVAS, EXCEPTO CLUBES', 'owner_name' => 'ALBRECHT MATIAS EZEQUIEL', 'address' => 'PARANÁ'],
    '000045' => ['business_name' => '"N.I TOQUES DE ENCANTO"', 'owner_name' => 'CHAJUD XIOMARA LEYLEN', 'phone' => '3435176851'],
    '000046' => ['business_name' => 'MINIMERCADO', 'owner_name' => 'RODRIGUEZ MATIAS'],
    '000048' => ['business_name' => 'DESPENSA', 'owner_name' => 'GAUNA MARIA DEL CARMEN'],
    '000049' => ['address' => 'BV  ARGENTINA', 'phone' => '3435162372'],
    '000050' => ['business_name' => 'KIOSCO "LA MILAGROSA"', 'owner_name' => 'SAMANIEGO SERGIO'],
    '000051' => ['business_name' => 'PASTELERIA', 'owner_name' => 'GOMEZ, CARINA Y LUISINA'],
    '000052' => ['business_name' => 'PASTELERIA', 'owner_name' => 'WERNER SOFIA ALEJANDRA'],
    '000053' => ['phone' => '3434608034'],
    '000054' => ['business_name' => 'PELUQUERIA', 'owner_name' => 'RODRIGUEZ  ADRIANA', 'phone' => '3434542920'],
    '000055' => ['business_name' => 'GOMERIA', 'owner_name' => 'FELTES CLAUDIO RAUL', 'phone' => '3436104016'],
    '000056' => ['business_name' => 'TALLER DE MOTOS', 'owner_name' => 'CEPARO LUCAS ADRIAN', 'phone' => '3434603857'],
    '000057' => ['business_name' => 'PANIFICACION M Y V', 'owner_name' => 'ALBORNOZ ANGELA VICTORIA', 'address' => 'NICOLAS AVELLANEDA'],
    '000058' => ['owner_name' => 'ALDANA  MARIA  EMILIA'],
    '000060' => ['business_name' => 'TALLER MECANICO "LOS GAUCHITOS"', 'owner_name' => 'GAINZA ALDO RAMON', 'address' => '9 DE JULIO'],
    '000061' => ['owner_name' => 'LENCINA SILVINA DANIELA'],
    '000062' => ['business_name' => 'ZAPATERIA', 'owner_name' => 'DITTLER  RUBEN GERMAN', 'address' => 'JUAN DOMINGO  PERON  50'],
    '000063' => ['owner_name' => 'EL EMPALME "EL PINGO" S.R.L.'],
    '000064' => ['business_name' => 'PANIFICACION', 'owner_name' => 'GOMEZ ADRIANA MARIA DEL LUJAN', 'phone' => '3435136867'],
    '000065' => ['business_name' => 'POLLERIA', 'owner_name' => 'ACOSTA PATRICIA ELIZABETH', 'phone' => '3435361643'],
    '000066' => ['owner_name' => 'MAIDANA  YANINA', 'phone' => '3434542817'],
    '000067' => ['business_name' => '"DON CHARO"', 'owner_name' => 'CORONA VICTOR FABIO'],
    '000068' => ['business_name' => 'COMEDOR "EL EMPALME"', 'owner_name' => 'SCHONFELDT RAUL'],
    '000069' => ['owner_name' => 'GONZALEZ ALBANA SOLEDAD', 'phone' => '343154069209'],
    '000070' => ['owner_name' => 'SCHONFELD NESTOR ADRIAN', 'phone' => '3435010213'],
    '000071' => ['phone' => '3436126366'],
    '000074' => ['phone' => '3434596850'],
    '000075' => ['owner_name' => 'HABERKON HORACIO'],
    '000076' => ['business_name' => 'REMIS', 'owner_name' => 'BORDON HECTOR JOSE'],
    '000077' => ['business_name' => 'PANIFICACION', 'owner_name' => 'RUIZ LUCIANA MABEL'],
    '000078' => ['business_name' => 'LOS DOS HERMANOS', 'owner_name' => 'SCHONFELDT GLADYS LILIANA'],
    '000079' => ['business_name' => '" ALGO BONITO"', 'owner_name' => 'BAEZ  ELIANA VICTORIA', 'address' => 'BUENOS AIRES ESQ. SAN LORENZO', 'phone' => '3435027274'],
    '000080' => ['business_name' => '" V Y B"', 'owner_name' => 'AGUILAR ANTONELLA Y HABERKON DAMIAN'],
    '000081' => ['owner_name' => 'RODRIGUEZ  MARCELO  ALEJANDRO', 'phone' => '3434050665'],
    '000082' => ['business_name' => 'SAMAR', 'owner_name' => 'SAMANIEGO ROSA'],
    '000083' => ['phone' => '3413060640'],
    '000084' => ['business_name' => '"RINCON DULCE"', 'owner_name' => 'DITTLER MONICA GRACIELA', 'phone' => '3434578293'],
    '000085' => ['business_name' => '"SABORES RIOS"', 'owner_name' => 'RIOS MARIA CRISTINA', 'address' => 'JUAN J. PASO'],
    '000086' => ['phone' => '3434254075'],
    '000087' => ['business_name' => '"HOME NURSING S.A."', 'owner_name' => 'AVILA NORBERTO EZEQUIEL Y OTROS'],
    '000088' => ['address' => 'MARIA ELENA WALSH  62', 'phone' => '3435008555'],
    '000090' => ['business_name' => '"SC"', 'owner_name' => 'CISNERO MARIA SOLEDAD', 'address' => 'NESTOR KIRCHNER  416', 'phone' => '3434633392'],
    '000091' => ['phone' => '3434706222'],
    '000093' => ['business_name' => '"LA MESA DE LOS SABORES"', 'owner_name' => 'ROMERO JESICA ANALIA', 'address' => 'SANTIAGO DEL ESTERO 502', 'phone' => '3434556323'],
    '000094' => ['phone' => '3435302808'],
    '000095' => ['owner_name' => 'CACERES MARIA MARGARITA'],
    '000097' => ['business_name' => 'MICRO EMPRENDIMIENTO', 'owner_name' => 'FISHER MILAGROS ARACELI', 'address' => 'DOLORES URRUTIA  177', 'phone' => '3436119290'],
    '000098' => ['business_name' => '" CODE"', 'owner_name' => 'FELTES ROLDAN CONRADO JAEL'],
    '000099' => ['phone' => '3436221933'],
    '000100' => ['business_name' => '" VIKINGO GYM"', 'owner_name' => 'CABRERA ARTURO RUBEN', 'phone' => '3436221933'],
    '000101' => ['business_name' => '" MALENA"', 'owner_name' => 'ROMERO LUCIA BEATRIZ'],
    '000102' => ['business_name' => '"PUNTOS Y  PUNTADAS"', 'owner_name' => 'GOMEZ ESTELA GRISELDA'],
    '000103' => ['business_name' => '"PRODUCTOS SAPHIRUS"', 'owner_name' => 'YANZ  JOHANA MARILIN', 'phone' => '3434577277'],
    '000104' => ['owner_name' => 'ALARCON ANTONELLLA DESIREE'],
    '000105' => ['owner_name' => 'VALDEZ ANGELICA DANIELA'],
    '000106' => ['business_name' => '"DMD SABORES CASEROS"', 'owner_name' => 'MANSILLA YOANA BEATRIZ', 'phone' => '3434716081'],
    '000107' => ['business_name' => '"HELADERIA"', 'owner_name' => 'ROMERO MARIA GRACIELA', 'phone' => '3435141685'],
    '000109' => ['business_name' => '"HELADOS CANDY"', 'owner_name' => 'GOMEZ LUISINA BEATRIZ', 'phone' => '3434506457'],
    '000110' => ['business_name' => '"FRUTAS Y VERDURAS GERMAN"', 'address' => '9 DE JULIO', 'phone' => '3436131693'],
    '000111' => ['business_name' => '"GOMITAS LOCAS"', 'owner_name' => 'RABBIA JAZMIN  Y VALDEZ LEANDRO', 'phone' => '3435134268'],
];

// ─────────────────────────────────────────────────────────────
// 2. Comercios que nunca se habían cargado (no estaban en el padrón
//    fotografiado original, pero sí en el padrón oficial). Rubro y
//    cuota confirmados: 20901 (taller) y 21101 (remis) son códigos
//    sin ambigüedad en el tarifario.
// ─────────────────────────────────────────────────────────────
$nuevosComercios = [
    [
        'registro'     => '000036',
        'business_name'=> 'TALLER',
        'owner_name'   => 'DITTLER CARLOS GUSTAVO',
        'address'      => 'SAN LORENZO',
        'phone'        => '',
        'fecha_inicio' => '2020-11-04',
        'rubro_code'   => '20901',
        'cuota_fija'   => 13000,
    ],
    [
        'registro'     => '000108',
        'business_name'=> 'REMIS',
        'owner_name'   => 'FOLMER PEDRO HORACIO',
        'address'      => 'EL PINGO',
        'phone'        => '3434710086',
        'fecha_inicio' => '2025-10-09',
        'rubro_code'   => '21101',
        'cuota_fija'   => 10000,
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

    // ── Parte 1: corregir comercios existentes ──
    $stmtSelect = $db->prepare("SELECT id, business_name, owner_name, address, phone FROM users WHERE legacy_registro = :reg AND role_id = 3");
    $actualizados = 0;
    $sinComercio = [];
    $camposTocados = [];

    foreach ($correcciones as $reg => $campos) {
        $stmtSelect->execute([':reg' => $reg]);
        $user = $stmtSelect->fetch();
        if (!$user) {
            $sinComercio[] = $reg;
            continue;
        }

        $sets = [];
        $params = [':id' => $user['id']];
        foreach ($campos as $campo => $valor) {
            $columna = $campo === 'business_name' ? 'business_name'
                : ($campo === 'owner_name' ? 'owner_name'
                : ($campo === 'address' ? 'address'
                : ($campo === 'phone' ? 'phone' : null)));
            if ($columna === null) {
                continue;
            }
            $sets[] = "{$columna} = :{$campo}";
            $params[":{$campo}"] = $valor;
            $camposTocados[$campo] = ($camposTocados[$campo] ?? 0) + 1;
        }
        if (empty($sets)) {
            continue;
        }

        if ($confirmar) {
            $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = :id";
            $db->prepare($sql)->execute($params);
        }
        $actualizados++;
    }

    echo "Comercios corregidos: {$actualizados}\n";
    foreach ($camposTocados as $campo => $n) {
        echo "  - {$campo}: {$n} cambio(s)\n";
    }
    if (!empty($sinComercio)) {
        echo "⚠ No se encontró comercio activo para estos registros (se omitieron): " . implode(', ', $sinComercio) . "\n";
    }

    // ── Parte 2: agregar los 2 comercios que nunca se cargaron ──
    echo "\n";
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE legacy_registro = :reg AND role_id = 3");
    $stmtInsert = $db->prepare("
        INSERT INTO users (
            client_code, business_name, cuit, address, phone, email, password_hash, base_rate, role_id,
            owner_name, rubro_code, activity_start_date, needs_data_review, data_review_reason, legacy_registro
        ) VALUES (
            :code, :name, :cuit, :addr, :phone, :email, :pass, :base_rate, 3,
            :owner, :rubro_code, :inicio, 1, :review_reason, :legacy
        )
    ");
    $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, 'system', 'Bienvenido', 'Su cuenta ha sido creada exitosamente en el Sistema de Control Tributario Municipal.')");

    $creados = 0;
    foreach ($nuevosComercios as $c) {
        $stmtCheck->execute([':reg' => $c['registro']]);
        if ($stmtCheck->fetch()) {
            echo "COM-{$c['registro']}: ya existe. Se omite.\n";
            continue;
        }

        $codeAlnum = 'COM' . $c['registro'];
        $cuitProvisorio = 'SC-' . substr($codeAlnum, -10);
        $email = 'sin-email.com-' . strtolower($c['registro']) . '@controltributario.local';
        $tempPassword = generarPasswordTemporal();

        echo "COM-{$c['registro']} ({$c['business_name']}): ";
        if ($confirmar) {
            $stmtInsert->execute([
                ':code'          => 'COM-' . $c['registro'],
                ':name'          => $c['business_name'],
                ':cuit'          => $cuitProvisorio,
                ':addr'          => $c['address'] !== '' ? $c['address'] : 'Sin domicilio registrado',
                ':phone'         => $c['phone'],
                ':email'         => $email,
                ':pass'          => password_hash($tempPassword, PASSWORD_DEFAULT),
                ':base_rate'     => $c['cuota_fija'],
                ':owner'         => $c['owner_name'],
                ':rubro_code'    => $c['rubro_code'],
                ':inicio'        => $c['fecha_inicio'],
                ':review_reason' => 'Comercio recién incorporado desde el padrón oficial actualizado (10/09/2026); no tenía CUIT registrado.',
                ':legacy'        => $c['registro'],
            ]);
            $newId = (int) $db->lastInsertId();
            $stmtNotif->execute([':uid' => $newId]);
            echo "creado. CUIT de acceso: {$cuitProvisorio} / Contraseña temporal: {$tempPassword}\n";
        } else {
            echo "se crearía con CUIT de acceso {$cuitProvisorio}\n";
        }
        $creados++;
    }

    if ($confirmar) {
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, details)
            VALUES ('users.bulk_correction_padron_oficial', 'user', :details)
        ");
        $auditStmt->execute([
            ':details' => json_encode(['corregidos' => $actualizados, 'nuevos' => $creados, 'campos' => $camposTocados]),
        ]);
        $db->commit();
    }

    echo "\nComercios nuevos agregados: {$creados}\n";

    echo "\n";
    if ($confirmar) {
        echo "=== CAMBIOS APLICADOS ===\n";
        echo "Anotá las contraseñas temporales de arriba si se crearon comercios nuevos.\n";
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
