<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== FACTURAS PENDIENTES CON TASA BASE DESACTUALIZADA ===\n\n";
echo "Compara el subtotal de cada factura PENDIENTE o VENCIDA (sin pagar)\n";
echo "contra la Tasa Base actual del comercio (la que sale del Tarifario\n";
echo "ya corregido). Las facturas PAGADAS no se tocan ni se listan acá —\n";
echo "son historial real de lo que se cobró, no se corrigen.\n";
echo "Esto es solo un reporte de lectura, no modifica nada.\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("
        SELECT i.id, i.invoice_number, i.period, i.subtotal, i.surcharge, i.total_amount, i.status,
               u.client_code, u.business_name, u.base_rate, u.rubro_code
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        WHERE i.status IN ('pending', 'overdue')
        ORDER BY u.business_name ASC, i.period ASC
    ");
    $facturas = $stmt->fetchAll();

    $desactualizadas = [];
    $sinRubro = [];
    $ok = 0;

    foreach ($facturas as $f) {
        if ($f['rubro_code'] === null) {
            $sinRubro[] = $f;
            continue;
        }
        $baseRate = (float) $f['base_rate'];
        $subtotal = (float) $f['subtotal'];
        if ($baseRate <= 0) {
            continue;
        }
        if (abs($baseRate - $subtotal) > 0.01) {
            $desactualizadas[] = $f;
        } else {
            $ok++;
        }
    }

    echo "Facturas pendientes/vencidas revisadas: " . count($facturas) . "\n";
    echo "Ya coinciden con la tasa base actual: {$ok}\n";
    echo "Sin rubro asignado todavía (no se pueden comparar): " . count($sinRubro) . "\n";
    echo "Con tasa base desactualizada: " . count($desactualizadas) . "\n\n";

    if (!empty($desactualizadas)) {
        echo "--- DETALLE: facturas con subtotal desactualizado ---\n";
        foreach ($desactualizadas as $f) {
            echo "{$f['client_code']} ({$f['business_name']}) — {$f['invoice_number']} período {$f['period']}: "
                . "factura \${$f['subtotal']} vs. tasa base actual \${$f['base_rate']} [{$f['status']}]\n";
        }
        echo "\n";
    }

    if (!empty($sinRubro)) {
        echo "--- Comercios con facturas pendientes pero sin rubro asignado ---\n";
        $vistos = [];
        foreach ($sinRubro as $f) {
            if (isset($vistos[$f['client_code']])) {
                continue;
            }
            $vistos[$f['client_code']] = true;
            echo "{$f['client_code']} ({$f['business_name']})\n";
        }
        echo "\n";
    }

    echo "=== FIN DEL REPORTE (no se modificó nada) ===\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
