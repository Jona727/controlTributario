<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE CONEXIÓN A LA BASE DE DATOS ===\n\n";

try {
    echo "Cargando configuración de .env...\n";
    echo "DB_HOST: " . $_ENV['DB_HOST'] . "\n";
    echo "DB_PORT: " . $_ENV['DB_PORT'] . "\n";
    echo "DB_NAME: " . $_ENV['DB_NAME'] . "\n";
    echo "DB_USER: " . $_ENV['DB_USER'] . "\n\n";

    echo "Conectando con PDO...\n";
    $db = \App\Config\Database::getConnection();
    echo "¡Conexión PDO exitosa!\n\n";

    echo "Consultando tabla 'users'...\n";
    $stmt = $db->query("SELECT u.id, u.cuit, u.email, u.password_hash, u.is_active, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id");
    $users = $stmt->fetchAll();

    echo "Total de usuarios encontrados: " . count($users) . "\n\n";

    foreach ($users as $user) {
        echo "ID: " . $user['id'] . "\n";
        echo "CUIT: " . $user['cuit'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Rol: " . $user['role_name'] . "\n";
        echo "Activo: " . ($user['is_active'] ? 'SÍ' : 'NO') . "\n";
        
        $hash = $user['password_hash'];
        echo "Hash en DB: " . $hash . "\n";
        
        $verify = password_verify('123456', $hash);
        echo "Verificación de password '123456': " . ($verify ? "¡ÉXITO!" : "¡FALLO!") . "\n";
        echo "--------------------------------------------------\n";
    }

} catch (Exception $e) {
    echo "🚨 ERROR DETECTADO: " . $e->getMessage() . "\n";
}
