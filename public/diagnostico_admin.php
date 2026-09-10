<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO: CUENTAS DE ADMINISTRADOR ===\n";
echo "(Solo lectura, no modifica nada)\n\n";

function enmascarar(string $v, int $mostrarFinales = 3): string
{
    $len = strlen($v);
    if ($len <= $mostrarFinales) {
        return str_repeat('*', $len);
    }
    return str_repeat('*', $len - $mostrarFinales) . substr($v, -$mostrarFinales);
}

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->query("
        SELECT u.id, u.client_code, u.business_name, u.email, u.cuit, u.role_id, r.name AS role_name,
               u.is_active, u.updated_at, u.last_login
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.role_id IN (1, 2)
        ORDER BY u.id ASC
    ");
    $admins = $stmt->fetchAll();

    echo "--- Cuentas con rol admin/super (" . count($admins) . ") ---\n";
    echo "(CUIT y email parcialmente ocultos por seguridad — esta URL no pide login)\n\n";
    foreach ($admins as $a) {
        echo "ID {$a['id']} | {$a['client_code']} | {$a['business_name']} | rol: {$a['role_name']} | ";
        echo "email: " . enmascarar($a['email'], 4) . " | cuit login: " . enmascarar($a['cuit'], 3) . " | activo: {$a['is_active']} | ";
        echo "actualizado: {$a['updated_at']} | último login: " . ($a['last_login'] ?? 'nunca') . "\n";
    }

    echo "\n--- Por las dudas, buscando si algún usuario con 'admin' en el nombre quedó con role_id = 3 ---\n";
    $stmt2 = $db->query("
        SELECT id, client_code, business_name, email, cuit, role_id, updated_at
        FROM users
        WHERE role_id = 3 AND (LOWER(business_name) LIKE '%admin%' OR LOWER(email) LIKE '%admin%' OR LOWER(client_code) LIKE '%admin%')
    ");
    $sospechosos = $stmt2->fetchAll();
    if (empty($sospechosos)) {
        echo "Ninguno encontrado.\n";
    } else {
        foreach ($sospechosos as $s) {
            echo "ID {$s['id']} | {$s['client_code']} | {$s['business_name']} | email: " . enmascarar($s['email'], 4) . " | cuit: " . enmascarar($s['cuit'], 3) . " | role_id: {$s['role_id']} | actualizado: {$s['updated_at']}\n";
        }
    }

    echo "\n--- Últimas 10 acciones en el registro de auditoría ---\n";
    $stmt3 = $db->query("SELECT id, user_id, action, entity_type, entity_id, created_at FROM audit_log ORDER BY id DESC LIMIT 10");
    foreach ($stmt3->fetchAll() as $log) {
        echo "[{$log['created_at']}] user_id={$log['user_id']} action={$log['action']} entity={$log['entity_type']}#{$log['entity_id']}\n";
    }

    echo "\n=== FIN DEL DIAGNÓSTICO ===\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
