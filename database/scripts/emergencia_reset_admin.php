<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== RESETEO DE EMERGENCIA: CUENTA DE ADMINISTRADOR (ID 1) ===\n\n";
echo "Restablece la contraseña de la ÚNICA cuenta de administrador\n";
echo "(ADMIN-001, rol super) porque quedó sin acceso. Genera una\n";
echo "contraseña nueva al azar y la muestra UNA sola vez acá abajo.\n\n";
echo "Borrá este archivo del servidor apenas hayas iniciado sesión con la\n";
echo "contraseña nueva — a diferencia de los demás scripts, este no pide\n";
echo "confirmación aparte porque es para una emergencia de acceso.\n\n";

function generarPasswordTemporal(int $length = 10): string
{
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $password;
}

try {
    $db = \App\Config\Database::getConnection();

    $stmt = $db->prepare("SELECT id, client_code, business_name, cuit FROM users WHERE id = 1 AND role_id IN (1, 2)");
    $stmt->execute();
    $admin = $stmt->fetch();

    if (!$admin) {
        echo "🚨 No se encontró la cuenta de administrador con ID 1.\n";
        exit;
    }

    $passwordNueva = generarPasswordTemporal();

    $stmtUpdate = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
    $stmtUpdate->execute([
        ':hash' => password_hash($passwordNueva, PASSWORD_DEFAULT),
        ':id'   => $admin['id'],
    ]);

    $auditStmt = $db->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details)
        VALUES (:uid, 'users.emergency_reset_admin', 'user', :eid, :details)
    ");
    $auditStmt->execute([
        ':uid'     => $admin['id'],
        ':eid'     => $admin['id'],
        ':details' => json_encode(['motivo' => 'Bloqueo de acceso reportado por el usuario']),
    ]);

    echo "Cuenta: {$admin['client_code']} — {$admin['business_name']}\n";
    echo "CUIT para iniciar sesión: {$admin['cuit']}\n";
    echo "Contraseña nueva: {$passwordNueva}\n\n";
    echo "=== LISTO ===\n";
    echo "Entrá con estos datos y, una vez adentro, te recomiendo cambiarla\n";
    echo "por una tuya en el ícono de la llave (Cambiar mi contraseña), abajo\n";
    echo "a la izquierda del panel.\n";
    echo "Borrá este archivo del servidor ahora que ya la tenés.\n";

} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
