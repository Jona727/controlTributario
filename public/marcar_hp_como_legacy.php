<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/_auth_guard.php';

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== MARCAR DEUDA HISTÓRICA (HP-...) COMO SIN VERIFICAR ===\n\n";
echo "El script que cargó la deuda histórica de Higiene y Profilaxis (facturas\n";
echo "'HP-...') nunca marcó esas filas como is_legacy — la migración que agregó\n";
echo "esa columna solo revisó created_by IS NULL una vez, cuando se creó la\n";
echo "columna. Si esas facturas se cargaron después, quedaron contándose como\n";
echo "deuda confiable en Facturación, Deuda e Indicadores y en el panel del\n";
echo "comercio, sin la protección de 'sin verificar' que tiene el resto de la\n";
echo "facturación migrada.\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("
        SELECT i.id, i.invoice_number, u.client_code, u.business_name, i.status, i.total_amount
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        WHERE i.invoice_number LIKE 'HP-%' AND i.is_legacy = 0
        ORDER BY i.invoice_number ASC
    ");
    $afectadas = $stmt->fetchAll();

    if (empty($afectadas)) {
        echo "No hay facturas 'HP-...' sin marcar — no hay nada para corregir.\n";
        exit;
    }

    echo "Se van a marcar como sin verificar " . count($afectadas) . " factura(s):\n\n";
    foreach ($afectadas as $f) {
        echo "  {$f['invoice_number']}  {$f['client_code']} — {$f['business_name']}  (" . $f['status'] . ", \$" . number_format((float)$f['total_amount'], 2, ',', '.') . ")\n";
    }
    echo "\nEsto no borra ni cambia montos — solo las excluye de los totales\n";
    echo "confiables hasta que Catastro y Rentas las concilie (mismo criterio que\n";
    echo "ya se usa para el resto de la facturación migrada).\n\n";

    if (!$confirmar) {
        echo "*** MODO VISTA PREVIA (no se cambió nada) ***\n";
        echo "Para aplicar de verdad, agregá &confirmar=SI al final de la URL.\n";
        exit;
    }

    $stmtUpdate = $db->prepare("UPDATE invoices SET is_legacy = 1 WHERE invoice_number LIKE 'HP-%' AND is_legacy = 0");
    $stmtUpdate->execute();

    echo "=== LISTO — " . $stmtUpdate->rowCount() . " factura(s) marcada(s) como sin verificar ===\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
