<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Config\Database;

class JwtService
{
    private string $secret;
    private string $issuer;
    private int    $expiration;
    private int    $refreshExpiration;

    public function __construct()
    {
        $this->secret            = $_ENV['JWT_SECRET'];
        $this->issuer            = $_ENV['JWT_ISSUER'];
        $this->expiration        = (int) $_ENV['JWT_EXPIRATION'];
        $this->refreshExpiration = (int) $_ENV['JWT_REFRESH_EXPIRATION'];
    }

    /**
     * Genera un access token JWT.
     */
    public function generateAccessToken(array $user): string
    {
        $payload = [
            'iss'  => $this->issuer,
            'iat'  => time(),
            'exp'  => time() + $this->expiration,
            'sub'  => $user['id'],
            'role' => $user['role_name'],
            'name' => $user['business_name'],
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Genera y almacena un refresh token.
     */
    public function generateRefreshToken(int $userId): string
    {
        $token = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->refreshExpiration);

        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO jwt_tokens (user_id, refresh_token, expires_at) VALUES (:uid, :token, :exp)'
        );
        $stmt->execute([
            ':uid'   => $userId,
            ':token' => $token,
            ':exp'   => $expiresAt,
        ]);

        return $token;
    }

    /**
     * Decodifica y valida un access token.
     */
    public function decodeToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Valida un refresh token y retorna el user_id o null.
     */
    public function validateRefreshToken(string $token): ?int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT user_id FROM jwt_tokens WHERE refresh_token = :token AND is_revoked = 0 AND expires_at > NOW()'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();

        return $row ? (int) $row['user_id'] : null;
    }

    /**
     * Revoca todos los refresh tokens de un usuario.
     */
    public function revokeAllTokens(int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE jwt_tokens SET is_revoked = 1 WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
    }
}
