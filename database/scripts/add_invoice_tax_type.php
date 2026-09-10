<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== MIGRACIÓN: COLUMNA tax_type EN invoices ===\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("SHOW COLUMNS FROM invoices LIKE 'tax_type'");
    if ($stmt->fetch()) {
        echo "La columna 'tax_type' ya existe. No se hicieron cambios.\n";
    } else {
        echo "Añadiendo la columna 'tax_type' a la tabla 'invoices'...\n";
        $db->exec("
            ALTER TABLE invoices
            ADD COLUMN tax_type VARCHAR(50) DEFAULT NULL
                COMMENT 'De qué tributo es la boleta. Hoy solo existe uno (Tasa de Higiene y Profilaxis); queda preparado por si en el futuro se suma otro con su propia fórmula de mora'
                AFTER status
        ");
        echo "OK.\n";
    }

    echo "\n=== MIGRACIÓN FINALIZADA CON ÉXITO ===\n";
    echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";

} catch (Exception $e) {
    echo "🚨 ERROR EN LA MIGRACIÓN: " . $e->getMessage() . "\n";
}
