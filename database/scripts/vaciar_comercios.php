<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== VACIAR COMERCIOS (para reimportar el padrón real) ===\n\n";
echo "Esto NUNCA toca cuentas de administrador (role_id 1 o 2), solo comercios (role_id 3)\n";
echo "y todo lo que depende de ellos: sus facturas, pagos, notificaciones y sesiones.\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("SELECT id, client_code, business_name, cuit, email FROM users WHERE role_id = 3 ORDER BY id");
    $comercios = $stmt->fetchAll();

    if (empty($comercios)) {
        echo "No hay comercios cargados. No hay nada para borrar.\n";
        exit;
    }

    echo "Se van a eliminar " . count($comercios) . " comercio(s):\n";
    foreach ($comercios as $c) {
        echo " - #{$c['id']} {$c['client_code']} | {$c['business_name']} | CUIT {$c['cuit']} | {$c['email']}\n";
    }
    echo "\n";

    $stmtF = $db->query("
        SELECT COUNT(*) AS total FROM invoices WHERE user_id IN (SELECT id FROM users WHERE role_id = 3)
    ");
    $totalFacturas = (int) $stmtF->fetch()['total'];
    echo "Facturas asociadas que también se eliminan: {$totalFacturas}\n";

    $stmtP = $db->query("
        SELECT COUNT(*) AS total FROM payments
        WHERE invoice_id IN (SELECT id FROM invoices WHERE user_id IN (SELECT id FROM users WHERE role_id = 3))
    ");
    $totalPagos = (int) $stmtP->fetch()['total'];
    echo "Pagos asociados que también se eliminan: {$totalPagos}\n\n";

    if (($_GET['confirmar'] ?? '') !== 'SI') {
        echo "=== NADA SE BORRÓ TODAVÍA (modo vista previa) ===\n";
        echo "Si esta lista es correcta, volvé a abrir esta URL agregando ?confirmar=SI al final\n";
        echo "para ejecutar el borrado de verdad. Por ejemplo:\n";
        echo "  " . ($_SERVER['REQUEST_URI'] ?? '') . (str_contains($_SERVER['REQUEST_URI'] ?? '', '?') ? '&' : '?') . "confirmar=SI\n";
        exit;
    }

    echo "Confirmado. Borrando...\n\n";

    $db->beginTransaction();

    $db->exec("
        DELETE FROM payments
        WHERE invoice_id IN (SELECT id FROM invoices WHERE user_id IN (SELECT id FROM users WHERE role_id = 3))
    ");
    echo "Pagos eliminados.\n";

    $db->exec("DELETE FROM invoices WHERE user_id IN (SELECT id FROM users WHERE role_id = 3)");
    echo "Facturas eliminadas (sus renglones/invoice_items se borran en cascada).\n";

    $db->exec("DELETE FROM users WHERE role_id = 3");
    echo "Comercios eliminados (sus notificaciones y sesiones se borran en cascada;\n";
    echo "el historial de auditoría se conserva, solo pierde la referencia al usuario).\n";

    $db->commit();

    echo "\n=== LISTO: EL SISTEMA QUEDÓ SIN COMERCIOS ===\n";
    echo "Ya podés importar el padrón real desde 'Importar CSV' en Comercios.\n";
    echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
