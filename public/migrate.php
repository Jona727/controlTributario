<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== MIGRACIÓN DE BASE DE DATOS: AÑADIR TASA BASE A COMERCIOS ===\n\n";

try {
    $db = \App\Config\Database::getConnection();
    
    // 1. Verificar si la columna base_rate ya existe
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'base_rate'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "Añadiendo la columna 'base_rate' a la tabla 'users'...\n";
        $db->exec("ALTER TABLE users ADD COLUMN base_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER is_active");
        echo "¡Columna 'base_rate' añadida con éxito!\n\n";
        
        // 2. Poblar tasas base para los comercios de prueba
        echo "Poblando tasas base predeterminadas...\n";
        
        $updates = [
            '20-12345678-9' => 3500.00, // Panadería San José
            '20-23456789-0' => 5200.00, // Farmacia del Centro
            '20-34567890-1' => 2800.00, // Ferretería La Unión
            '20-45678901-2' => 1500.00, // Kiosco Don Pedro
            '20-56789012-3' => 2200.00, // Librería El Saber
        ];
        
        $stmtUpdate = $db->prepare("UPDATE users SET base_rate = :base_rate WHERE cuit = :cuit");
        foreach ($updates as $cuit => $rate) {
            $stmtUpdate->execute([
                ':base_rate' => $rate,
                ':cuit' => $cuit
            ]);
            echo " - Comercio CUIT $cuit actualizado con Tasa Base = $$rate\n";
        }
        echo "¡Población de tasas base completada!\n";
    } else {
        echo "La columna 'base_rate' ya existe en la tabla 'users'. No se realizaron cambios estructurales.\n";
    }
    
    echo "\n=== MIGRACIÓN FINALIZADA CON ÉXITO ===";
    
} catch (Exception $e) {
    echo "🚨 ERROR EN LA MIGRACIÓN: " . $e->getMessage() . "\n";
}
