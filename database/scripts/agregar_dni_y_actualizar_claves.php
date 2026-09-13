<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== NUEVO LOGIN: USUARIO = CÓDIGO DE COMERCIO, CLAVE = DNI ===\n\n";
if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se escribe nada) ***\n";
    echo "Para aplicar los cambios de verdad, volvé a abrir esta misma URL agregando ?confirmar=SI al final.\n\n";
}

/**
 * Para una persona física (CUIT que empieza con 20/23/24/27), los 8 dígitos
 * del medio del CUIT son el DNI con un cero a la izquierda si hace falta.
 * Para una sociedad (30/33/34) esos dígitos son un número de inscripción,
 * no el DNI de nadie — ahí no se puede derivar nada, hay que pedírselo al
 * municipio y cargarlo a mano desde la ficha del comercio.
 */
function derivarDniDeCuit(string $cuit): ?string
{
    $limpio = preg_replace('/\D/', '', $cuit);
    if (strlen($limpio) !== 11) {
        return null;
    }
    $prefijo = substr($limpio, 0, 2);
    if (!in_array($prefijo, ['20', '23', '24', '27'], true)) {
        return null;
    }
    $dni = ltrim(substr($limpio, 2, 8), '0');
    return $dni !== '' ? $dni : null;
}

try {
    $db = \App\Config\Database::getConnection();

    // 1. Columna dni, si todavía no existe.
    $col = $db->query("SHOW COLUMNS FROM users LIKE 'dni'")->fetch();
    if (!$col) {
        if ($confirmar) {
            $db->exec("ALTER TABLE users ADD COLUMN dni VARCHAR(15) DEFAULT NULL COMMENT 'DNI del titular, usado como contraseña inicial' AFTER cuit");
            echo "Columna 'dni' creada en users.\n\n";
        } else {
            echo "Falta la columna 'dni' en users (se crearía con ?confirmar=SI).\n\n";
        }
    }

    if ($confirmar) {
        $db->beginTransaction();
    }

    // En vista previa sin confirmar todavía puede no existir la columna dni
    // (recién se crearía al confirmar), así que no se puede pedir en el SELECT.
    $selectDni = $col || $confirmar ? ', dni' : '';
    $stmt = $db->query("SELECT id, client_code, business_name, cuit{$selectDni} FROM users WHERE role_id = 3 ORDER BY client_code ASC");
    $comercios = $stmt->fetchAll();

    $stmtUpdate = ($col || $confirmar)
        ? $db->prepare("UPDATE users SET dni = :dni, password_hash = :pass WHERE id = :id")
        : null;

    $actualizados = 0;
    $yaSincronizados = 0;
    $pendientesMunicipio = [];

    foreach ($comercios as $c) {
        $derivado = derivarDniDeCuit($c['cuit']);

        if ($derivado === null) {
            $pendientesMunicipio[] = "{$c['client_code']} — {$c['business_name']} (CUIT: {$c['cuit']})";
            continue;
        }

        if ($c['dni'] === $derivado) {
            $yaSincronizados++;
            continue;
        }

        echo "{$c['client_code']} ({$c['business_name']}): DNI '" . ($c['dni'] ?? '—') . "' → '{$derivado}'";
        if ($confirmar && $stmtUpdate) {
            $stmtUpdate->execute([
                ':dni'  => $derivado,
                ':pass' => password_hash($derivado, PASSWORD_DEFAULT),
                ':id'   => $c['id'],
            ]);
            echo " (aplicado)\n";
        } else {
            echo " (se aplicaría)\n";
        }
        $actualizados++;
    }

    echo "\n--- Resumen ---\n";
    echo "Comercios con DNI derivable del CUIT (persona física): " . ($actualizados + $yaSincronizados) . "\n";
    echo "  - Actualizados ahora: {$actualizados}\n";
    echo "  - Ya estaban sincronizados: {$yaSincronizados}\n";
    echo "Comercios sin DNI derivable (sociedades u otros, requieren el dato del municipio): " . count($pendientesMunicipio) . "\n";

    if (!empty($pendientesMunicipio)) {
        echo "\n--- Pendientes de DNI (pedirle al municipio y cargar a mano en cada ficha) ---\n";
        foreach ($pendientesMunicipio as $p) {
            echo "{$p}\n";
        }
        echo "\nMientras tanto, podés asignarles una contraseña provisoria compartida desde el botón\n";
        echo "'Resetear Contraseñas' del panel de Comercios (solo afecta a los que no tienen DNI cargado).\n";
    }

    if ($confirmar) {
        $auditStmt = $db->prepare("
            INSERT INTO audit_log (action, entity_type, details)
            VALUES ('users.dni_login_migracion', 'user', :details)
        ");
        $auditStmt->execute([
            ':details' => json_encode([
                'actualizados'  => $actualizados,
                'sincronizados' => $yaSincronizados,
                'pendientes'    => count($pendientesMunicipio),
            ]),
        ]);
        $db->commit();
    }

    echo "\n";
    if ($confirmar) {
        echo "=== CAMBIOS APLICADOS ===\n";
        echo "Los comercios de arriba ya pueden entrar con su código de comercio y su DNI.\n";
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
