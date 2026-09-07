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
        // Rate Limiting (3 intentos fallidos por 5 minutos)
        if (isset($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > time()) {
            $mins = ceil(($_SESSION['login_locked_until'] - time()) / 60);
            return $this->returnError($request, $response, "Demasiados intentos fallidos. Intente de nuevo en {$mins} minuto(s).");
        }

        $data = $request->getParsedBody();
        $cuit = trim($data['cuit'] ?? '');
        $pass = $data['password'] ?? '';
        $remember = !empty($data['remember_me']);

        if (empty($cuit) || empty($pass)) {
            return $this->returnError($request, $response, 'Ingrese CUIT y contraseña.');
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
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= 3) {
                $_SESSION['login_locked_until'] = time() + (5 * 60); // 5 min
                return $this->returnError($request, $response, 'Demasiados intentos fallidos. Intente de nuevo en 5 minutos.');
            }
            return $this->returnError($request, $response, 'Credenciales inválidas.');
        }

        // Si login exitoso, limpiar intentos
        unset($_SESSION['login_attempts']);
        unset($_SESSION['login_locked_until']);

        // Generar tokens
        $jwt          = new JwtService();
        $accessToken  = $jwt->generateAccessToken($user);
        $refreshToken = $jwt->generateRefreshToken((int) $user['id']);

        // Actualizar last_login
        $stmt = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
        $stmt->execute([':id' => $user['id']]);

        // Establecer cookies (con expiración modificada por remember_me)
        $expAccess = $remember ? time() + (30 * 24 * 3600) : time() + (int) $_ENV['JWT_EXPIRATION'];
        $expRefresh = $remember ? time() + (60 * 24 * 3600) : time() + (int) $_ENV['JWT_REFRESH_EXPIRATION'];

        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        $cookiePath = $basePath === '' ? '/' : $basePath;
        setcookie('access_token', $accessToken, [
            'expires'  => $expAccess,
            'path'     => $cookiePath,
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
        setcookie('refresh_token', $refreshToken, [
            'expires'  => $expRefresh,
            'path'     => $cookiePath,
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);

        // Redirigir según rol
        $redirect = in_array($user['role_name'], ['admin', 'super'])
            ? $basePath . '/admin/dashboard'
            : $basePath . '/user/dashboard';

        if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            $response->getBody()->write(json_encode(['success' => true, 'redirect' => $redirect]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

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

    private function returnError(Request $request, Response $response, string $message): Response
    {
        if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            $response->getBody()->write(json_encode(['success' => false, 'error' => $message]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        
        $_SESSION['login_error'] = $message;
        $basePath = $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public';
        return $response
            ->withHeader('Location', $basePath . '/login')
            ->withStatus(302);
    }
}
