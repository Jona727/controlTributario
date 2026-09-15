<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== LIMPIAR COMILLAS EN RAZÓN SOCIAL ===\n";
echo "Solo corrige los casos donde la comilla envuelve TODO el nombre (ej: \"MISIGI\" -> MISIGI).\n";
echo "Los que combinan tipo de negocio + nombre entre comillas (ej: KIOSCO \"LA MILAGROSA\") quedan intactos.\n\n";

if (!$confirmar) {
    echo "*** MODO VISTA PREVIA (no se toca nada) ***\n";
    echo "Para aplicar de verdad, agregá &confirmar=SI al final de la URL.\n\n";
}

function limpiarSiEnvuelveTodo(string $nombre): ?string
{
    $trimmed = trim($nombre);
    if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"') && strlen($trimmed) > 1) {
        $limpio = trim(substr($trimmed, 1, -1));
        if ($limpio !== '' && $limpio !== $trimmed) {
            return $limpio;
        }
    }
    return null;
}

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("
        SELECT id, client_code, business_name
        FROM users
        WHERE role_id = 3
          AND (business_name LIKE '\"%' OR business_name LIKE '%\"')
        ORDER BY client_code
    ");
    $rows = $stmt->fetchAll();

    $aCorregir = [];
    $seOmiten  = [];

    foreach ($rows as $r) {
        $limpio = limpiarSiEnvuelveTodo($r['business_name']);
        if ($limpio !== null) {
            $aCorregir[] = ['id' => $r['id'], 'client_code' => $r['client_code'], 'antes' => $r['business_name'], 'despues' => $limpio];
        } else {
            $seOmiten[] = $r;
        }
    }

    echo "--- Se van a corregir (" . count($aCorregir) . ") ---\n";
    foreach ($aCorregir as $c) {
        echo "  {$c['client_code']}: " . var_export($c['antes'], true) . " -> " . var_export($c['despues'], true) . "\n";
    }

    echo "\n--- Se dejan intactas, comilla parcial (" . count($seOmiten) . ") ---\n";
    foreach ($seOmiten as $o) {
        echo "  {$o['client_code']}: " . var_export($o['business_name'], true) . "\n";
    }

    if ($confirmar) {
        $db->beginTransaction();
        $upd = $db->prepare("UPDATE users SET business_name = :nombre WHERE id = :id");
        foreach ($aCorregir as $c) {
            $upd->execute([':nombre' => $c['despues'], ':id' => $c['id']]);
        }
        $db->commit();
        echo "\n=== LISTO — se corrigieron " . count($aCorregir) . " comercios ===\n";
    } else {
        echo "\n=== ESTO FUE SOLO UNA VISTA PREVIA — NO SE TOCÓ NADA ===\n";
        echo "Si la lista de arriba es lo que esperabas corregir, volvé a abrir esta URL agregando &confirmar=SI\n";
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
