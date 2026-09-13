<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== CREAR TABLA account_status_requests ===\n";
echo "(Solo crea la tabla si no existe todavía. No borra ni modifica datos.)\n\n";

try {
    $db = \App\Config\Database::getConnection();

    $db->exec("
        CREATE TABLE IF NOT EXISTS account_status_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            status ENUM('pending','resolved') NOT NULL DEFAULT 'pending',
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME DEFAULT NULL,
            resolved_by INT DEFAULT NULL COMMENT 'Admin que respondió',
            response_message TEXT DEFAULT NULL,
            CONSTRAINT fk_asr_user FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_asr_admin FOREIGN KEY (resolved_by) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE,
            INDEX idx_asr_user (user_id),
            INDEX idx_asr_status (status)
        ) ENGINE=InnoDB
    ");

    echo "Listo. La tabla 'account_status_requests' existe.\n";
    echo "Por seguridad, borrá este archivo del servidor una vez confirmado el resultado.\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
