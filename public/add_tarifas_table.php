<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== MIGRACIÓN: TABLA DE TARIFAS Y CÓDIGO DE RUBRO ===\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("SHOW TABLES LIKE 'tarifas'");
    if ($stmt->fetch()) {
        echo "La tabla 'tarifas' ya existe. Se omite.\n";
    } else {
        echo "Creando la tabla 'tarifas'...\n";
        $db->exec("
            CREATE TABLE tarifas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(20) NOT NULL UNIQUE COMMENT 'Código de rubro de la ordenanza',
                rubro VARCHAR(255) NOT NULL,
                categoria VARCHAR(150) DEFAULT NULL,
                alicuota VARCHAR(50) DEFAULT NULL COMMENT 'Si se cobra por alícuota en vez de cuota fija (ej: combustible, 6%)',
                cuota_fija DECIMAL(12,2) DEFAULT NULL COMMENT 'Cuota fija bimestral en pesos, NULL si se cobra por alícuota',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        echo "OK.\n";
    }

    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'rubro_code'");
    if ($stmt->fetch()) {
        echo "La columna 'rubro_code' ya existe en users. Se omite.\n";
    } else {
        echo "Añadiendo la columna 'rubro_code' a users...\n";
        $db->exec("ALTER TABLE users ADD COLUMN rubro_code VARCHAR(20) DEFAULT NULL COMMENT 'Código de rubro del Código Tributario Municipal (tabla tarifas)' AFTER legacy_registro");
        echo "OK.\n";
    }

    echo "\n=== MIGRACIÓN FINALIZADA CON ÉXITO ===\n";
    echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";

} catch (Exception $e) {
    echo "🚨 ERROR EN LA MIGRACIÓN: " . $e->getMessage() . "\n";
}
