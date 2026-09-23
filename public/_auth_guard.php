<?php

declare(strict_types=1);

/**
 * Exige estar logueado como admin o super en el panel (misma cookie de
 * sesión que usa la app) para poder ejecutar el script que lo incluye.
 * Se requiere al principio de cada script de un solo uso en public/.
 */

$token = $_COOKIE['access_token'] ?? null;

if (!$token) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "403 - Acceso denegado. Iniciá sesión como administrador en el panel (en la misma pestaña/navegador) y volvé a abrir este link.\n";
    exit;
}

try {
    $jwt = new \App\Services\JwtService();
    $decoded = $jwt->decodeToken($token);

    if (!$decoded || !in_array($decoded->role ?? '', ['admin', 'super'], true)) {
        throw new \RuntimeException('rol inválido');
    }

    $db = \App\Config\Database::getConnection();
    $stmt = $db->prepare("SELECT is_active FROM users WHERE id = :id");
    $stmt->execute([':id' => $decoded->sub]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['is_active'] === 0) {
        throw new \RuntimeException('usuario inactivo');
    }
} catch (\Throwable $e) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "403 - Acceso denegado. Iniciá sesión como administrador en el panel (en la misma pestaña/navegador) y volvé a abrir este link.\n";
    exit;
}
