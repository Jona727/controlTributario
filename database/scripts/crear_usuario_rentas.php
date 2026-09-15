<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CREAR USUARIO ADMINISTRADOR \"rentas\" ===\n\n";

$clientCode = 'rentas';
$password   = '123456';
$businessName = 'Rentas Municipales';
$address    = 'Municipio de El Pingo';
$email      = 'rentas@elpingo.gob.ar';
$cuit       = 'SC-RENTAS-001';

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->prepare("SELECT id, client_code, role_id FROM users WHERE client_code = :cc OR email = :email OR cuit = :cuit");
    $stmt->execute([':cc' => $clientCode, ':email' => $email, ':cuit' => $cuit]);
    $existente = $stmt->fetch();

    if ($existente) {
        echo "Ya existe un usuario con ese código, email o CUIT (id {$existente['id']}, código {$existente['client_code']}, rol {$existente['role_id']}).\n";
        echo "No se creó nada. Si querés cambiarle la contraseña en vez de crear uno nuevo, avisame.\n";
        exit;
    }

    echo "Se va a crear:\n";
    echo "  Usuario (client_code): {$clientCode}\n";
    echo "  Contraseña: {$password}\n";
    echo "  Rol: admin (Administrador Municipal, no super)\n";
    echo "  Razón social: {$businessName}\n";
    echo "  Email: {$email}\n\n";

    if (!$confirmar) {
        echo "*** MODO VISTA PREVIA (no se creó nada) ***\n";
        echo "Para aplicar de verdad, agregá &confirmar=SI al final de la URL.\n";
        exit;
    }

    $stmtRole = $db->prepare("SELECT id FROM roles WHERE name = 'admin'");
    $stmtRole->execute();
    $roleId = (int) $stmtRole->fetchColumn();

    if (!$roleId) {
        echo "🚨 No se encontró el rol 'admin' en la tabla roles.\n";
        exit;
    }

    $stmtInsert = $db->prepare("
        INSERT INTO users (client_code, business_name, cuit, address, email, password_hash, role_id, is_active, base_rate)
        VALUES (:cc, :bn, :cuit, :addr, :email, :hash, :role, 1, 0.00)
    ");
    $stmtInsert->execute([
        ':cc'    => $clientCode,
        ':bn'    => $businessName,
        ':cuit'  => $cuit,
        ':addr'  => $address,
        ':email' => $email,
        ':hash'  => password_hash($password, PASSWORD_DEFAULT),
        ':role'  => $roleId,
    ]);

    echo "=== LISTO — usuario creado ===\n";
    echo "Ya puede iniciar sesión con Usuario: {$clientCode} / Contraseña: {$password}\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
