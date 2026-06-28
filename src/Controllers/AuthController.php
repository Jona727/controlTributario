<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use App\Services\JwtService;

class AuthController
{
    /**
     * Muestra la página de login.
     */
    public function showLogin(Request $request, Response $response): Response
    {
        ob_start();
        require __DIR__ . '/../../public/views/login.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Procesa el login.
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $cuit = trim($data['cuit'] ?? '');
        $pass = $data['password'] ?? '';

        if (empty($cuit) || empty($pass)) {
            return $this->redirectWithError($response, 'Ingrese CUIT y contraseña.');
        }

        // Limpiar guiones del CUIT de entrada para comparación robusta
        $cuitClean = str_replace('-', '', $cuit);

        $db   = Database::getConnection();
        $stmt = $db->prepare('
            SELECT u.*, r.name AS role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE REPLACE(u.cuit, \'-\', \'\') = :cuit_clean AND u.is_active = 1
        ');
        $stmt->execute([':cuit_clean' => $cuitClean]);
        $user = $stmt->fetch();

        error_log("DEBUG LOGIN: Recibido CUIT='$cuit' (Limpio='$cuitClean')");
        if ($user) {
            $verify = password_verify($pass, $user['password_hash']);
            error_log("DEBUG LOGIN: Usuario encontrado ID={$user['id']}, CUIT={$user['cuit']}. Verificacion: " . ($verify ? "OK" : "FALLO"));
        } else {
            error_log("DEBUG LOGIN: Usuario no encontrado en DB para CUIT limpio '$cuitClean'");
        }

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            return $this->redirectWithError($response, 'Credenciales inválidas.');
        }

        // Generar tokens
        $jwt          = new JwtService();
        $accessToken  = $jwt->generateAccessToken($user);
        $refreshToken = $jwt->generateRefreshToken((int) $user['id']);

        // Actualizar last_login
        $stmt = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
        $stmt->execute([':id' => $user['id']]);

        // Establecer cookies
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        $cookiePath = $basePath === '' ? '/' : $basePath;
        setcookie('access_token', $accessToken, [
            'expires'  => time() + (int) $_ENV['JWT_EXPIRATION'],
            'path'     => $cookiePath,
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
        setcookie('refresh_token', $refreshToken, [
            'expires'  => time() + (int) $_ENV['JWT_REFRESH_EXPIRATION'],
            'path'     => $cookiePath,
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);

        // Redirigir según rol
        $redirect = in_array($user['role_name'], ['admin', 'super'])
            ? $basePath . '/admin/dashboard'
            : $basePath . '/user/dashboard';

        return $response
            ->withHeader('Location', $redirect)
            ->withStatus(302);
    }

    /**
     * Cierra la sesión.
     */
    public function logout(Request $request, Response $response): Response
    {
        $cookies = $request->getCookieParams();
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        $cookiePath = $basePath === '' ? '/' : $basePath;

        // Revocar refresh tokens
        if (isset($cookies['access_token'])) {
            $jwt     = new JwtService();
            $decoded = $jwt->decodeToken($cookies['access_token']);
            if ($decoded) {
                $jwt->revokeAllTokens((int) $decoded->sub);
            }
        }

        // Limpiar cookies
        setcookie('access_token', '', ['expires' => time() - 3600, 'path' => $cookiePath]);
        setcookie('refresh_token', '', ['expires' => time() - 3600, 'path' => $cookiePath]);

        return $response
            ->withHeader('Location', $basePath . '/login')
            ->withStatus(302);
    }

    private function redirectWithError(Response $response, string $message): Response
    {
        $_SESSION['login_error'] = $message;
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response
            ->withHeader('Location', $basePath . '/login')
            ->withStatus(302);
    }
}
