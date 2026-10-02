<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/_auth_guard.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== MIGRACIÓN: Estado de deuda por comercio + facturación migrada sin verificar ===\n\n";

try {
    $db = \App\Config\Database::getConnection();

    // 1. users.payment_status
    $col = $db->query("SHOW COLUMNS FROM users LIKE 'payment_status'")->fetch();
    if ($col) {
        echo "- users.payment_status ya existe, no se toca.\n";
    } else {
        $db->exec("
            ALTER TABLE users
            ADD COLUMN payment_status ENUM('con_deuda','al_dia') NOT NULL DEFAULT 'con_deuda'
            COMMENT 'Clasificación manual: con_deuda (arrastra deuda del sistema anterior, sin conciliar) o al_dia (factura normal desde el sistema nuevo)'
            AFTER data_review_reason
        ");
        echo "✓ Agregada users.payment_status (default 'con_deuda' para todos los existentes).\n";
    }

    // 2. invoices.is_legacy
    $col = $db->query("SHOW COLUMNS FROM invoices LIKE 'is_legacy'")->fetch();
    if ($col) {
        echo "- invoices.is_legacy ya existe, no se toca.\n";
    } else {
        $db->exec("
            ALTER TABLE invoices
            ADD COLUMN is_legacy TINYINT(1) NOT NULL DEFAULT 0
            COMMENT 'Factura importada del sistema anterior, sin conciliar — no se cuenta en los totales de Facturación ni Deuda e Indicadores hasta que se verifique'
            AFTER tax_type
        ");
        echo "✓ Agregada invoices.is_legacy.\n";

        // Backfill: las facturas creadas por scripts de importación nunca
        // setearon created_by (todo lo que crea la app sí lo hace), así que
        // created_by IS NULL es la señal confiable de "carga masiva vieja".
        $stmt = $db->query("UPDATE invoices SET is_legacy = 1 WHERE created_by IS NULL");
        $afectadas = $stmt->rowCount();
        echo "✓ Marcadas como legacy (sin verificar) {$afectadas} facturas con created_by NULL.\n";
    }

    echo "\n=== LISTO ===\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
