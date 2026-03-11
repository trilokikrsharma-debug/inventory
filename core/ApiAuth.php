<?php
/**
 * API Token Authentication — Bearer Token Strategy
 * 
 * Provides stateless API authentication for programmatic access.
 * Tokens are scoped to a tenant and can have granular permissions.
 * 
 * Token Format: inv_{base64(random_bytes(32))}
 * Storage:      SHA-256 hash stored in DB (never store raw tokens)
 * 
 * Usage:
 *   // Generate token for a tenant:
 *   $token = ApiAuth::generateToken($companyId, $userId, 'My Integration');
 *   
 *   // Validate incoming request:
 *   $tokenData = ApiAuth::validateRequest();
 *   if (!$tokenData) {
 *       http_response_code(401);
 *       exit(json_encode(['error' => 'Invalid API token']));
 *   }
 */
class ApiAuth {

    /**
     * Generate a new API token for a tenant
     * 
     * @param int    $companyId  Tenant ID
     * @param int    $userId     User who created the token
     * @param string $name       Human-readable name
     * @param array  $scopes     Permission scopes ['sales.read', 'products.write']
     * @return array ['token' => 'inv_...', 'id' => int]
     */
    public static function generateToken(int $companyId, int $userId, string $name, array $scopes = ['*']): array {
        $rawToken = 'inv_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $rawToken);
        
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO api_tokens (company_id, user_id, name, token_hash, scopes, last_used_at, created_at) 
             VALUES (?, ?, ?, ?, ?, NULL, NOW())",
            [$companyId, $userId, $name, $hash, json_encode($scopes)]
        );
        
        $id = $db->lastInsertId();
        
        Logger::audit('api_token_created', 'api_tokens', $id, [
            'name' => $name, 'scopes' => $scopes
        ]);
        
        return ['token' => $rawToken, 'id' => $id];
    }

    /**
     * Validate an API request using Bearer token from Authorization header
     * 
     * @return array|null Token data (company_id, user_id, scopes) or null
     */
    public static function validateRequest(): ?array {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (stripos($header, 'Bearer ') !== 0) {
            return null;
        }
        
        $rawToken = substr($header, 7);
        if (empty($rawToken) || strpos($rawToken, 'inv_') !== 0) {
            return null;
        }
        
        return self::validateToken($rawToken);
    }

    /**
     * Validate a raw token string
     */
    public static function validateToken(string $rawToken): ?array {
        $hash = hash('sha256', $rawToken);
        
        $db = Database::getInstance();
        $token = $db->query(
            "SELECT t.*, c.status AS company_status 
             FROM api_tokens t
             JOIN companies c ON t.company_id = c.id
             WHERE t.token_hash = ? AND t.is_active = 1 AND c.status = 'active'",
            [$hash]
        )->fetch();
        
        if (!$token) return null;
        
        // Check expiry
        if ($token['expires_at'] && strtotime($token['expires_at']) < time()) {
            return null;
        }
        
        // Update last used timestamp (non-blocking)
        try {
            $db->query("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?", [$token['id']]);
        } catch (\Exception $e) { /* non-critical */ }
        
        return [
            'token_id'   => $token['id'],
            'company_id' => $token['company_id'],
            'user_id'    => $token['user_id'],
            'name'       => $token['name'],
            'scopes'     => json_decode($token['scopes'], true) ?: ['*'],
        ];
    }

    /**
     * Check if token has a specific scope
     */
    public static function hasScope(array $tokenData, string $scope): bool {
        $scopes = $tokenData['scopes'] ?? [];
        return in_array('*', $scopes) || in_array($scope, $scopes);
    }

    /**
     * Revoke a token
     */
    public static function revokeToken(int $tokenId, int $companyId): bool {
        $db = Database::getInstance();
        $affected = $db->query(
            "UPDATE api_tokens SET is_active = 0 WHERE id = ? AND company_id = ?",
            [$tokenId, $companyId]
        )->rowCount();
        
        if ($affected > 0) {
            Logger::audit('api_token_revoked', 'api_tokens', $tokenId);
        }
        return $affected > 0;
    }
}
