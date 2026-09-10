<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CARGAR TARIFARIO Y CÓDIGOS DE RUBRO (Tasa de Higiene y Profilaxis) ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

// ─────────────────────────────────────────────────────────────
// 1. Tarifario transcripto de la Ordenanza (Título II, Art. 6°),
//    ya con las correcciones confirmadas por el usuario el 09/09/2026.
// ─────────────────────────────────────────────────────────────
$tarifario = [
    ['codigo' => '20105', 'rubro' => 'Elaboración de Sandwich, empanadas, pizzas y comidas en general', 'categoria' => 'Comercios al por mayor y menor', 'alicuota' => null, 'cuota_fija' => 15000],
    ['codigo' => '20108', 'rubro' => 'Carnicerías (comercialización de productos cárnicos)', 'categoria' => 'Comercios al por mayor y menor', 'alicuota' => null, 'cuota_fija' => 20000],
    ['codigo' => '20113', 'rubro' => 'Pollería', 'categoria' => 'Comercios al por mayor y menor', 'alicuota' => null, 'cuota_fija' => 15000],
    ['codigo' => '20607', 'rubro' => 'Venta (supermercados y autoservicios)', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 30000],
    ['codigo' => '20609', 'rubro' => 'Venta de artículos variados no comestibles (bazar, regalería, juguetería, mercería, papelería, cotillón)', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 15000],
    ['codigo' => '20610', 'rubro' => 'Polirrubro', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 20000],
    ['codigo' => '20611', 'rubro' => 'Verdulería y frutas frescas', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 10000],
    ['codigo' => '20612', 'rubro' => 'Panaderías, venta de pastas y confituras', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 15000],
    ['codigo' => '20613', 'rubro' => 'Kioscos', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 10000],
    ['codigo' => '20615', 'rubro' => 'Roperías y accesorios', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 13000],
    ['codigo' => '20616', 'rubro' => 'Venta de calzado y artículos de cuero', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 15000],
    ['codigo' => '20618', 'rubro' => 'Corralón de Materiales de construcción', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 35000],
    ['codigo' => '20619', 'rubro' => 'Ferreterías', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 25000],
    ['codigo' => '20630', 'rubro' => 'Venta por menor de bebidas al mostrador (BAR)', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 25000],
    ['codigo' => '20631', 'rubro' => 'Venta de muebles (Mueblería) - carpintería', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 13000],
    ['codigo' => '20634', 'rubro' => 'Venta de artículos de limpieza', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 8000],
    ['codigo' => '20635', 'rubro' => 'Emprendedores de producción artesanal', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 4000],
    ['codigo' => '20635.1', 'rubro' => 'Emprendedores gastronómicos', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 8000],
    ['codigo' => '20636', 'rubro' => 'Dietética', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 15000],
    ['codigo' => '20637', 'rubro' => 'Pañalera', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 10000],
    ['codigo' => '20638', 'rubro' => 'Productos farmacéuticos, perfumería y de herboristería', 'categoria' => 'Comercio al por menor', 'alicuota' => null, 'cuota_fija' => 30000],
    ['codigo' => '20639', 'rubro' => 'Venta de combustible para vehículos automotores y motocicletas (incluye lubricantes)', 'categoria' => 'Comercio al por menor', 'alicuota' => '6% (alícuota, no cuota fija)', 'cuota_fija' => null],
    ['codigo' => '20704', 'rubro' => 'Venta a domicilio de agua, soda y aguas saborizadas', 'categoria' => 'Comercio al por menor a domicilio', 'alicuota' => null, 'cuota_fija' => 8000],
    ['codigo' => '20806', 'rubro' => 'Peluquería', 'categoria' => 'Otras actividades de servicios', 'alicuota' => null, 'cuota_fija' => 7500],
    ['codigo' => '20901', 'rubro' => 'Taller de reparaciones de automóviles, maquinarias y motocicletas', 'categoria' => 'Servicios de reparación y mantenimiento', 'alicuota' => null, 'cuota_fija' => 13000],
    ['codigo' => '20902', 'rubro' => 'Taller de reparaciones de motocicletas', 'categoria' => 'Servicios de reparación y mantenimiento', 'alicuota' => null, 'cuota_fija' => 5000],
    ['codigo' => '20904', 'rubro' => 'Oficios (plomeros, gasistas, albañiles, electricistas, pintores)', 'categoria' => 'Servicios de reparación y mantenimiento', 'alicuota' => null, 'cuota_fija' => 3000],
    ['codigo' => '21001', 'rubro' => 'Carribares y similares', 'categoria' => 'Hoteles, restaurantes y espectáculos públicos', 'alicuota' => null, 'cuota_fija' => 8000],
    ['codigo' => '21101', 'rubro' => 'Servicio de Taxi, fletes y remises, mensajerías', 'categoria' => 'Transportes, encomiendas y almacenamiento', 'alicuota' => null, 'cuota_fija' => 10000],
    ['codigo' => '21302', 'rubro' => 'Agencias de juegos de azar', 'categoria' => 'Intermediación financiera, juegos de azar y seguros', 'alicuota' => null, 'cuota_fija' => 20000],
    ['codigo' => '21400', 'rubro' => 'Por alquiler de peloteros', 'categoria' => 'Actividades inmobiliarias y de alquiler', 'alicuota' => null, 'cuota_fija' => 6500],
    ['codigo' => '21401', 'rubro' => 'Alquiler de casas y departamentos por mes', 'categoria' => 'Actividades inmobiliarias y de alquiler', 'alicuota' => null, 'cuota_fija' => 8000],
    ['codigo' => '21402', 'rubro' => 'Alquiler de casas y departamentos por día - por casa', 'categoria' => 'Actividades inmobiliarias y de alquiler', 'alicuota' => null, 'cuota_fija' => 10000],
    ['codigo' => '21403', 'rubro' => 'Canchas de fútbol 5', 'categoria' => 'Actividades inmobiliarias y de alquiler', 'alicuota' => null, 'cuota_fija' => 15000],
    ['codigo' => '21802', 'rubro' => 'Lavadero de autos', 'categoria' => 'Otras actividades de servicios', 'alicuota' => null, 'cuota_fija' => 8000],
    ['codigo' => '21803', 'rubro' => 'Actividades no previstas', 'categoria' => 'Otras actividades de servicios', 'alicuota' => null, 'cuota_fija' => 8000],
    ['codigo' => '21804', 'rubro' => 'Cooperativas de aguas', 'categoria' => 'Otras actividades de servicios', 'alicuota' => null, 'cuota_fija' => 50000],
];

// ─────────────────────────────────────────────────────────────
// 2. Código de rubro por comercio (columna legacy_registro), tal como
//    lo confirmó el usuario comercio por comercio contra el padrón real
//    (mensaje del 09/09/2026), más los que ya estaban resueltos por
//    coincidencia directa de texto contra la ordenanza.
// ─────────────────────────────────────────────────────────────
$rubroPorComercio = [
    ['registro' => '000001', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000002', 'rubro_code' => '20704', 'cuota_fija' => 8000],
    ['registro' => '000003', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000004', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000005', 'rubro_code' => '20607', 'cuota_fija' => 30000],
    ['registro' => '000006', 'rubro_code' => '20615', 'cuota_fija' => 13000],
    ['registro' => '000007', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000008', 'rubro_code' => '20615', 'cuota_fija' => 13000],
    ['registro' => '000009', 'rubro_code' => '20108', 'cuota_fija' => 20000],
    ['registro' => '000010', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000011', 'rubro_code' => '20615', 'cuota_fija' => 13000],
    ['registro' => '000012', 'rubro_code' => '20607', 'cuota_fija' => 30000],
    ['registro' => '000013', 'rubro_code' => '20607', 'cuota_fija' => 30000],
    ['registro' => '000014', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000015', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000016', 'rubro_code' => '21302', 'cuota_fija' => 20000],
    ['registro' => '000017', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000018', 'rubro_code' => '20806', 'cuota_fija' => 7500],
    ['registro' => '000019', 'rubro_code' => '20105', 'cuota_fija' => 15000],
    ['registro' => '000021', 'rubro_code' => '20630', 'cuota_fija' => 25000],
    ['registro' => '000022', 'rubro_code' => '20612', 'cuota_fija' => 15000],
    ['registro' => '000023', 'rubro_code' => '20630', 'cuota_fija' => 25000],
    ['registro' => '000024', 'rubro_code' => '20105', 'cuota_fija' => 15000],
    ['registro' => '000025', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000026', 'rubro_code' => '20612', 'cuota_fija' => 15000],
    ['registro' => '000027', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000028', 'rubro_code' => '21101', 'cuota_fija' => 10000],
    ['registro' => '000029', 'rubro_code' => '21101', 'cuota_fija' => 10000],
    ['registro' => '000030', 'rubro_code' => '21101', 'cuota_fija' => 10000],
    ['registro' => '000031', 'rubro_code' => '21101', 'cuota_fija' => 10000],
    ['registro' => '000032', 'rubro_code' => '21802', 'cuota_fija' => 8000],
    ['registro' => '000033', 'rubro_code' => '20610', 'cuota_fija' => 20000],
    ['registro' => '000034', 'rubro_code' => '21401', 'cuota_fija' => 8000],
    ['registro' => '000035', 'rubro_code' => '20619', 'cuota_fija' => 25000],
    ['registro' => '000037', 'rubro_code' => '21804', 'cuota_fija' => 50000],
    ['registro' => '000038', 'rubro_code' => '20607', 'cuota_fija' => 30000],
    ['registro' => '000039', 'rubro_code' => '20619', 'cuota_fija' => 25000],
    ['registro' => '000040', 'rubro_code' => '21401', 'cuota_fija' => 8000],
    ['registro' => '000041', 'rubro_code' => '20637', 'cuota_fija' => 10000],
    ['registro' => '000043', 'rubro_code' => '21101', 'cuota_fija' => 10000],
    ['registro' => '000044', 'rubro_code' => '21403', 'cuota_fija' => 15000],
    ['registro' => '000045', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000046', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000048', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000049', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000050', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000051', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000052', 'rubro_code' => '20612', 'cuota_fija' => 15000],
    ['registro' => '000053', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000054', 'rubro_code' => '20806', 'cuota_fija' => 7500],
    ['registro' => '000055', 'rubro_code' => '20901', 'cuota_fija' => 13000],
    ['registro' => '000056', 'rubro_code' => '20902', 'cuota_fija' => 5000],
    ['registro' => '000057', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000058', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000060', 'rubro_code' => '20901', 'cuota_fija' => 13000],
    ['registro' => '000061', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000062', 'rubro_code' => '20616', 'cuota_fija' => 15000],
    ['registro' => '000064', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000065', 'rubro_code' => '20113', 'cuota_fija' => 15000],
    ['registro' => '000066', 'rubro_code' => '20113', 'cuota_fija' => 15000],
    ['registro' => '000067', 'rubro_code' => '20607', 'cuota_fija' => 30000],
    ['registro' => '000069', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000070', 'rubro_code' => '20901', 'cuota_fija' => 13000],
    ['registro' => '000071', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000073', 'rubro_code' => '21401', 'cuota_fija' => 8000],
    ['registro' => '000074', 'rubro_code' => '21101', 'cuota_fija' => 10000],
    ['registro' => '000075', 'rubro_code' => '21401', 'cuota_fija' => 8000],
    ['registro' => '000076', 'rubro_code' => '21101', 'cuota_fija' => 10000],
    ['registro' => '000077', 'rubro_code' => '20611', 'cuota_fija' => 10000],
    ['registro' => '000078', 'rubro_code' => '20630', 'cuota_fija' => 25000],
    ['registro' => '000079', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000080', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000081', 'rubro_code' => '21001', 'cuota_fija' => 8000],
    ['registro' => '000082', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000083', 'rubro_code' => '21401', 'cuota_fija' => 8000],
    ['registro' => '000084', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000085', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000086', 'rubro_code' => '21401', 'cuota_fija' => 8000],
    ['registro' => '000088', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000090', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000091', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000092', 'rubro_code' => '20806', 'cuota_fija' => 7500],
    ['registro' => '000093', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000094', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000095', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000096', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000097', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000098', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000099', 'rubro_code' => '20613', 'cuota_fija' => 10000],
    ['registro' => '000100', 'rubro_code' => '21803', 'cuota_fija' => 8000],
    ['registro' => '000101', 'rubro_code' => '20615', 'cuota_fija' => 13000],
    ['registro' => '000102', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000103', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000104', 'rubro_code' => '20635', 'cuota_fija' => 4000],
    ['registro' => '000106', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000107', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000109', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
    ['registro' => '000110', 'rubro_code' => '20611', 'cuota_fija' => 10000],
    ['registro' => '000111', 'rubro_code' => '20635.1', 'cuota_fija' => 8000],
];

// Comercios que el propio usuario marcó como pendientes de consultar
// al municipio (no se les toca base_rate, solo se marca needs_data_review
// para que aparezcan en el banner de revisión de la sección Comercios).
$pendientesMunicipio = [
    '000063' => 'Confirmar con el municipio qué tipo de tarifa corresponde (posible rubro 20639, combustible, por alícuota).',
    '000068' => 'Confirmar con el municipio qué tipo de tarifa corresponde en este caso.',
    '000087' => 'El rubro no existe en la ordenanza disponible (servicio de enfermería a domicilio) — consultar al municipio.',
    '000105' => 'El rubro no existe en la ordenanza disponible — consultar al municipio.',
];

try {
    $db = \App\Config\Database::getConnection();

    if ($confirmar) {
        $db->beginTransaction();
    }

    $stmtSelectTarifa = $db->prepare("SELECT id FROM tarifas WHERE codigo = :codigo");
    $stmtInsertTarifa = $db->prepare("
        INSERT INTO tarifas (codigo, rubro, categoria, alicuota, cuota_fija)
        VALUES (:codigo, :rubro, :categoria, :alicuota, :cuota_fija)
    ");
    $stmtUpdateTarifa = $db->prepare("
        UPDATE tarifas SET rubro = :rubro, categoria = :categoria, alicuota = :alicuota, cuota_fija = :cuota_fija
        WHERE codigo = :codigo
    ");

    $tarifasNuevas = 0;
    $tarifasActualizadas = 0;

    foreach ($tarifario as $t) {
        $stmtSelectTarifa->execute([':codigo' => $t['codigo']]);
        $existing = $stmtSelectTarifa->fetch();
        $params = [
            ':codigo'     => $t['codigo'],
            ':rubro'      => $t['rubro'],
            ':categoria'  => $t['categoria'],
            ':alicuota'   => $t['alicuota'],
            ':cuota_fija' => $t['cuota_fija'],
        ];
        if ($existing) {
            if ($confirmar) {
                $stmtUpdateTarifa->execute($params);
            }
            $tarifasActualizadas++;
        } else {
            if ($confirmar) {
                $stmtInsertTarifa->execute($params);
            }
            $tarifasNuevas++;
        }
    }

    echo "Tarifario: {$tarifasNuevas} códigos nuevos, {$tarifasActualizadas} ya existían (se actualizan los datos).\n\n";

    $stmtSelectUser = $db->prepare("SELECT id, business_name, base_rate, rubro_code FROM users WHERE legacy_registro = :reg AND role_id = 3");
    $stmtUpdateUser = $db->prepare("
        UPDATE users SET rubro_code = :rubro_code, base_rate = :base_rate, needs_data_review = 0, data_review_reason = NULL
        WHERE id = :id
    ");
    $stmtUpdatePendiente = $db->prepare("
        UPDATE users SET needs_data_review = 1, data_review_reason = :reason
        WHERE legacy_registro = :reg AND role_id = 3
    ");

    $comerciosActualizados = 0;
    $sinComercio = [];

    foreach ($rubroPorComercio as $r) {
        $stmtSelectUser->execute([':reg' => $r['registro']]);
        $user = $stmtSelectUser->fetch();
        if (!$user) {
            $sinComercio[] = $r['registro'];
            continue;
        }
        if ($confirmar) {
            $stmtUpdateUser->execute([
                ':rubro_code' => $r['rubro_code'],
                ':base_rate'  => $r['cuota_fija'],
                ':id'         => $user['id'],
            ]);
        }
        $comerciosActualizados++;
    }

    echo "Comercios con rubro y tasa base actualizados: {$comerciosActualizados}\n";
    if (!empty($sinComercio)) {
        echo "⚠ No se encontró comercio activo para estos registros (se omitieron): " . implode(', ', $sinComercio) . "\n";
    }

    $pendientesActualizados = 0;
    foreach ($pendientesMunicipio as $registro => $motivo) {
        if ($confirmar) {
            $stmtUpdatePendiente->execute([':reason' => $motivo, ':reg' => $registro]);
        }
        $pendientesActualizados++;
    }
    echo "Comercios marcados para consultar al municipio (needs_data_review): {$pendientesActualizados}\n";

    if ($confirmar) {
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, details)
            VALUES ('users.bulk_update_rubro_tarifas', 'user', :details)
        ");
        $auditStmt->execute([
            ':details' => json_encode([
                'tarifas_nuevas' => $tarifasNuevas,
                'tarifas_actualizadas' => $tarifasActualizadas,
                'comercios_actualizados' => $comerciosActualizados,
                'pendientes_municipio' => $pendientesActualizados,
            ]),
        ]);
    }

    if ($confirmar) {
        $db->commit();
    }

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
