<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/_auth_guard.php';

header('Content-Type: text/plain; charset=utf-8');

$confirmar = ($_GET['confirmar'] ?? '') === 'SI';

echo "=== CREAR COMERCIO DE PRUEBA (para grabar el video) ===\n\n";

$clientCode   = 'DEMO-001';
$password     = 'demo1234';
$businessName = 'Comercio de Prueba (Demo)';
$address      = 'Dirección de prueba 123';
$email        = 'demo@elpingo.gob.ar';
$cuit         = '20-00000000-0';
$baseRate     = 3500.00;

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->prepare("SELECT id, client_code, role_id FROM users WHERE client_code = :cc OR email = :email OR cuit = :cuit");
    $stmt->execute([':cc' => $clientCode, ':email' => $email, ':cuit' => $cuit]);
    $existente = $stmt->fetch();

    if ($existente) {
        echo "Ya existe un usuario con ese código, email o CUIT (id {$existente['id']}, código {$existente['client_code']}, rol {$existente['role_id']}).\n";
        echo "No se creó nada. Si querés resetearle la contraseña en vez de crear uno nuevo, avisame.\n";
        exit;
    }

    echo "Se va a crear:\n";
    echo "  Usuario (client_code): {$clientCode}\n";
    echo "  Contraseña: {$password}\n";
    echo "  Rol: user (Comercio)\n";
    echo "  Razón social: {$businessName}\n";
    echo "  Email: {$email}\n";
    echo "  Estado de deuda inicial: con_deuda (lo podés cambiar a 'Al día' desde Comercios > Editar en cualquier momento)\n";
    echo "  Sin facturas cargadas — generalas en vivo desde el panel admin (Generar Lote Mensual) si las querés mostrar en el video.\n\n";

    if (!$confirmar) {
        echo "*** MODO VISTA PREVIA (no se creó nada) ***\n";
        echo "Para aplicar de verdad, agregá &confirmar=SI al final de la URL.\n";
        exit;
    }

    $stmtRole = $db->prepare("SELECT id FROM roles WHERE name = 'user'");
    $stmtRole->execute();
    $roleId = (int) $stmtRole->fetchColumn();

    if (!$roleId) {
        echo "🚨 No se encontró el rol 'user' en la tabla roles.\n";
        exit;
    }

    $stmtInsert = $db->prepare("
        INSERT INTO users (client_code, business_name, cuit, address, email, password_hash, role_id, is_active, base_rate, payment_status)
        VALUES (:cc, :bn, :cuit, :addr, :email, :hash, :role, 1, :rate, 'con_deuda')
    ");
    $stmtInsert->execute([
        ':cc'    => $clientCode,
        ':bn'    => $businessName,
        ':cuit'  => $cuit,
        ':addr'  => $address,
        ':email' => $email,
        ':hash'  => password_hash($password, PASSWORD_DEFAULT),
        ':role'  => $roleId,
        ':rate'  => $baseRate,
    ]);

    echo "=== LISTO — comercio de prueba creado ===\n";
    echo "Ya se puede iniciar sesión con Usuario: {$clientCode} / Contraseña: {$password}\n";
    echo "Cuando termines de grabar, podés desactivarlo o borrarlo desde Comercios en el panel admin.\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
