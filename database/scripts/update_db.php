<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== ACTUALIZACIÓN DE BASE DE DATOS ===\n\n";

try {
    $db = \App\Config\Database::getConnection();
    
    echo "1. Creando tabla 'notifications' (si no existe)...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('alert','reminder','info','system') NOT NULL DEFAULT 'info',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_notif_user (user_id),
            INDEX idx_notif_read (is_read)
        ) ENGINE=InnoDB;
    ");
    echo "OK.\n\n";

    echo "2. Creando tabla 'audit_log' (si no existe)...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) DEFAULT NULL,
            entity_id INT DEFAULT NULL,
            details JSON DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE,
            INDEX idx_audit_user (user_id),
            INDEX idx_audit_action (action),
            INDEX idx_audit_date (created_at)
        ) ENGINE=InnoDB;
    ");
    echo "OK.\n\n";

    echo "3. Creando tabla 'payments' (si no existe)...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            receipt_number VARCHAR(50) NOT NULL UNIQUE,
            payment_date DATETIME NOT NULL,
            amount_paid DECIMAL(12,2) NOT NULL,
            surcharge_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            registered_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
            CONSTRAINT fk_payments_admin FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE RESTRICT,
            INDEX idx_payments_date (payment_date)
        ) ENGINE=InnoDB;
    ");
    echo "OK.\n\n";

    echo "=== TODAS LAS TABLAS FUERON CREADAS / VERIFICADAS CON ÉXITO ===\n";
    echo "Ya puedes volver a la aplicación y crear comercios.\n";

} catch (Exception $e) {
    echo "🚨 ERROR EN LA ACTUALIZACIÓN: " . $e->getMessage() . "\n";
}
