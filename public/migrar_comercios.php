<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== MIGRACIÓN: CAMPOS PARA IMPORTAR EL PADRÓN LEGADO DE COMERCIOS ===\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $columns = [
        'owner_name'           => "ALTER TABLE users ADD COLUMN owner_name VARCHAR(255) DEFAULT NULL COMMENT 'Titular (persona física), distinto de la Razón Social' AFTER base_rate",
        'activity_category'    => "ALTER TABLE users ADD COLUMN activity_category VARCHAR(150) DEFAULT NULL COMMENT 'Rubro de actividad comercial' AFTER owner_name",
        'activity_start_date'  => "ALTER TABLE users ADD COLUMN activity_start_date DATE DEFAULT NULL COMMENT 'Fecha de inicio de la actividad comercial' AFTER activity_category",
        'needs_data_review'    => "ALTER TABLE users ADD COLUMN needs_data_review TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'El comercio debe validar/corregir sus datos' AFTER activity_start_date",
        'data_review_reason'   => "ALTER TABLE users ADD COLUMN data_review_reason VARCHAR(255) DEFAULT NULL COMMENT 'Motivo de la marca de revisión' AFTER needs_data_review",
        'legacy_registro'      => "ALTER TABLE users ADD COLUMN legacy_registro VARCHAR(20) DEFAULT NULL COMMENT 'ID en el sistema anterior' AFTER data_review_reason",
    ];

    foreach ($columns as $name => $sql) {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE " . $db->quote($name));
        if ($stmt->fetch()) {
            echo "La columna '{$name}' ya existe. Se omite.\n";
            continue;
        }
        echo "Añadiendo la columna '{$name}'...\n";
        $db->exec($sql);
        echo "OK.\n";
    }

    $stmt = $db->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_needs_review'");
    if (!$stmt->fetch()) {
        echo "\nAñadiendo índice sobre needs_data_review...\n";
        $db->exec("ALTER TABLE users ADD INDEX idx_users_needs_review (needs_data_review)");
        echo "OK.\n";
    } else {
        echo "\nEl índice idx_users_needs_review ya existe. Se omite.\n";
    }

    echo "\n=== MIGRACIÓN FINALIZADA CON ÉXITO ===\n";
    echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";

} catch (Exception $e) {
    echo "🚨 ERROR EN LA MIGRACIÓN: " . $e->getMessage() . "\n";
}
