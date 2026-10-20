<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/_auth_guard.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== AUDITORÍA: COMERCIOS QUE PODRÍAN SER DEL MISMO TITULAR ===\n";
echo "(Solo lectura, no modifica nada — Fase 2 del plan de normalización de titulares)\n\n";

try {
    $db = \App\Config\Database::getConnection();

    // --- 1. Mismo DNI en más de un comercio ---
    echo "--- 1. Comercios que YA comparten el mismo DNI ---\n";
    echo "(Esto está bien si es a propósito — mismo titular, distinto comercio.\n";
    echo " Revisar si el nombre del titular también coincide.)\n\n";

    $stmt = $db->query("
        SELECT dni,
               GROUP_CONCAT(
                   CONCAT(client_code, ' — ', business_name,
                          ' — titular: ', COALESCE(owner_name, '(sin cargar)'),
                          IF(is_active = 1, '', ' [INACTIVO]'))
                   SEPARATOR '\n    '
               ) AS comercios,
               COUNT(*) AS cantidad
        FROM users
        WHERE role_id = 3 AND dni IS NOT NULL AND dni != ''
        GROUP BY dni
        HAVING COUNT(*) > 1
        ORDER BY cantidad DESC
    ");
    $mismoDni = $stmt->fetchAll();

    if (empty($mismoDni)) {
        echo "Ninguno. Ningún DNI está repetido en más de un comercio todavía.\n";
    } else {
        foreach ($mismoDni as $grupo) {
            echo "DNI {$grupo['dni']} aparece en {$grupo['cantidad']} comercios:\n    {$grupo['comercios']}\n\n";
        }
    }

    // --- 2. Mismo nombre de titular (normalizado), con DNI distinto o faltante ---
    echo "\n--- 2. Comercios con el mismo titular (nombre), pero DNI inconsistente ---\n";
    echo "(Mismo nombre normalizado -- sin mayúsculas ni espacios de más --\n";
    echo " pero con DNI distinto, o cargado en uno y no en el otro. Candidatos\n";
    echo " a corregir para que terminen compartiendo el mismo DNI.)\n\n";

    $stmt2 = $db->query("
        SELECT
            UPPER(TRIM(REGEXP_REPLACE(owner_name, '[[:space:]]+', ' '))) AS titular_normalizado,
            GROUP_CONCAT(
                CONCAT(client_code, ' — ', business_name,
                       ' — DNI: ', COALESCE(dni, 'SIN DNI'),
                       IF(is_active = 1, '', ' [INACTIVO]'))
                SEPARATOR '\n    '
            ) AS comercios,
            COUNT(*) AS cantidad,
            COUNT(DISTINCT COALESCE(dni, '')) AS dnis_distintos
        FROM users
        WHERE role_id = 3 AND owner_name IS NOT NULL AND TRIM(owner_name) != ''
        GROUP BY titular_normalizado
        HAVING COUNT(*) > 1
        ORDER BY cantidad DESC
    ");
    $mismoTitular = $stmt2->fetchAll();

    $inconsistentes = array_filter($mismoTitular, fn($g) => (int) $g['dnis_distintos'] > 1);

    if (empty($inconsistentes)) {
        echo "Ninguno. Todos los titulares con más de un comercio ya tienen el mismo DNI en todos (o ninguno tiene DNI aún).\n";
    } else {
        foreach ($inconsistentes as $grupo) {
            echo "Titular \"{$grupo['titular_normalizado']}\" — {$grupo['cantidad']} comercios, DNI inconsistente:\n    {$grupo['comercios']}\n\n";
        }
    }

    // --- 3. Comercios activos sin DNI cargado ---
    echo "\n--- 3. Comercios activos SIN DNI cargado todavía ---\n";
    echo "(No son necesariamente duplicados, pero mientras no tengan DNI no se\n";
    echo " puede verificar si comparten titular con otro comercio.)\n\n";

    $stmt3 = $db->query("
        SELECT client_code, business_name, COALESCE(owner_name, '(sin cargar)') AS owner_name
        FROM users
        WHERE role_id = 3 AND is_active = 1 AND (dni IS NULL OR dni = '')
        ORDER BY owner_name ASC, client_code ASC
    ");
    $sinDni = $stmt3->fetchAll();

    if (empty($sinDni)) {
        echo "Ninguno. Todos los comercios activos tienen DNI cargado.\n";
    } else {
        foreach ($sinDni as $c) {
            echo "{$c['client_code']} — {$c['business_name']} — titular: {$c['owner_name']}\n";
        }
        echo "\nTotal: " . count($sinDni) . " comercio(s) activo(s) sin DNI.\n";
    }

    // --- 4. Listado alfabético completo de titulares, para revisión visual ---
    // (Sirve para detectar a ojo variantes que la normalización automática no
    // agarra: "Pérez, Juan" vs "Juan Pérez", apodos, errores de tipeo, etc.
    // Esta normalización solo cubre mayúsculas/espacios, no acentos ni orden.)
    echo "\n--- 4. Listado completo de titulares (orden alfabético, para revisar a ojo) ---\n";
    echo "(Los casos 1 y 2 de arriba son automáticos. Este listado es para que\n";
    echo " revisés manualmente variantes de escritura que el sistema no puede\n";
    echo " detectar solo, por ejemplo nombre y apellido invertidos.)\n\n";

    $stmt4 = $db->query("
        SELECT client_code, business_name, COALESCE(owner_name, '(sin cargar)') AS owner_name, COALESCE(dni, 'SIN DNI') AS dni
        FROM users
        WHERE role_id = 3
        ORDER BY owner_name ASC, client_code ASC
    ");
    foreach ($stmt4->fetchAll() as $c) {
        echo "{$c['owner_name']}  |  DNI: {$c['dni']}  |  {$c['client_code']} — {$c['business_name']}\n";
    }

    echo "\n=== FIN DE LA AUDITORÍA ===\n";

} catch (\Throwable $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
