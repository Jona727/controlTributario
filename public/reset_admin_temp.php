<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== RESET TEMPORAL DE CONTRASEÑA (DEBUG_TEMP) ===\n\n";

$cuitObjetivo = '30-99999999-0';
$nuevaPassword = 'rzblWp34rrJsAx!';

try {
    $db = \App\Config\Database::getConnection();

    $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

    $stmt = $db->prepare('UPDATE users SET password_hash = :hash WHERE cuit = :cuit');
    $stmt->execute([':hash' => $hash, ':cuit' => $cuitObjetivo]);

    echo "Filas actualizadas: " . $stmt->rowCount() . "\n";
    echo "Contraseña temporal establecida para CUIT {$cuitObjetivo}.\n";
    echo "Ingrese con esa contraseña y cámbiela de inmediato.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
