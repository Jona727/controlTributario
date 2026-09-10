<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== ¿HASTA QUÉ PERÍODO ESTÁ FACTURADO? ===\n";
echo "(Solo lectura, no modifica nada)\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("SELECT MAX(period) as ultimo FROM invoices");
    $ultimo = $stmt->fetch()['ultimo'];
    echo "Último período con facturas cargadas: " . ($ultimo ?? '(ninguno)') . "\n\n";

    echo "--- Últimos 8 períodos, con cantidad de facturas cada uno ---\n";
    $stmt2 = $db->query("
        SELECT period, COUNT(*) as cantidad, SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as pagadas
        FROM invoices
        GROUP BY period
        ORDER BY period DESC
        LIMIT 8
    ");
    foreach ($stmt2->fetchAll() as $p) {
        echo "{$p['period']}: {$p['cantidad']} factura(s) ({$p['pagadas']} pagadas)\n";
    }

    if ($ultimo) {
        echo "\n--- Comercios activos que NO tienen factura del último período ({$ultimo}) ---\n";
        $stmt3 = $db->prepare("
            SELECT u.client_code, u.business_name
            FROM users u
            WHERE u.role_id = 3 AND u.is_active = 1 AND u.base_rate > 0
              AND NOT EXISTS (
                  SELECT 1 FROM invoices i WHERE i.user_id = u.id AND i.period = :period AND i.status != 'cancelled'
              )
            ORDER BY u.business_name ASC
        ");
        $stmt3->execute([':period' => $ultimo]);
        $faltantes = $stmt3->fetchAll();
        if (empty($faltantes)) {
            echo "Ninguno — todos los comercios activos con tasa base tienen factura de ese período.\n";
        } else {
            foreach ($faltantes as $f) {
                echo "{$f['client_code']} — {$f['business_name']}\n";
            }
            echo "\nTotal: " . count($faltantes) . " comercio(s) sin factura del último período.\n";
        }
    }

    echo "\n=== FIN DEL REPORTE ===\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
