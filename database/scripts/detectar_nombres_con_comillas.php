<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== COMERCIOS CON POSIBLE DATO MAL IMPORTADO (comillas en el nombre) ===\n";
echo "(Solo lectura — no modifica nada)\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("
        SELECT id, client_code, business_name, owner_name, cuit
        FROM users
        WHERE role_id = 3
          AND (
              business_name LIKE '\"%' OR business_name LIKE '%\"'
              OR owner_name LIKE '\"%' OR owner_name LIKE '%\"'
          )
        ORDER BY client_code
    ");
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        echo "No se encontró ningún comercio con comillas en el nombre o el titular.\n";
        exit;
    }

    echo "Encontrados: " . count($rows) . "\n\n";
    foreach ($rows as $r) {
        echo "{$r['client_code']} (id {$r['id']}) — CUIT {$r['cuit']}\n";
        echo "  Razón Social: " . var_export($r['business_name'], true) . "\n";
        echo "  Titular:      " . var_export($r['owner_name'], true) . "\n\n";
    }

    echo "Para corregirlos: Admin > Comercios > buscar el código > Editar, y ajustar \"Razón Social\" y \"Titular\".\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
