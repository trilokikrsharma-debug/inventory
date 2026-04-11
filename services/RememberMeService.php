<?php
/**
 * Persistent "remember me" login using rotating selector/validator tokens.
 */
class RememberMeService {
    private const COOKIE_NAME = 'tsa_remember';
    private const DEFAULT_LIFETIME = 2592000; // 30 days

    private static bool $schemaChecked = false;

    public static function shouldRememberFromRequest(): bool {
        $value = $_POST['remember_me'] ?? null;
        if (is_array($value)) {
            return false;
        }

        return in_array(strtolower(trim((string)$value)), ['1', 'true', 'on', 'yes'], true);
    }

    public static function issueForUser(array $user): void {
        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        try {
            self::ensureSchema();
            $db = Database::getInstance();

            $selector = bin2hex(random_bytes(9));
            $validator = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $validator);
            $expiresAt = date('Y-m-d H:i:s', time() + self::lifetime());

            $db->query(
                "INSERT INTO auth_remember_tokens
                 (selector, user_id, token_hash, expires_at, user_agent, ip_address, created_at, last_used_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $selector,
                    $userId,
                    $tokenHash,
                    $expiresAt,
                    self::trimValue($_SERVER['HTTP_USER_AGENT'] ?? '', 255),
                    self::trimValue($_SERVER['REMOTE_ADDR'] ?? '', 45),
                ]
            );

            Session::set('remember_token_selector', $selector);
            self::setCookie($selector, $validator, strtotime($expiresAt));
        } catch (\Throwable $e) {
            error_log('[REMEMBER_ME] Failed to issue token: ' . $e->getMessage());
        }
    }

    public static function resumeIfPossible(): bool {
        if (Session::isLoggedIn()) {
            return true;
        }

        $cookie = self::readCookie();
        if ($cookie === null) {
            return false;
        }

        try {
            self::ensureSchema();
            $db = Database::getInstance();
            $row = $db->query(
                "SELECT rt.id, rt.selector, rt.user_id, rt.token_hash, rt.expires_at,
                        u.*,
                        c.id AS company_id_check,
                        c.name AS company_name_check,
                        c.status AS company_status_check,
                        c.is_demo AS company_is_demo,
                        c.plan AS company_plan,
                        c.saas_plan_id AS company_saas_plan_id,
                        c.subscription_status AS company_subscription_status,
                        c.trial_ends_at AS company_trial_ends_at,
                        c.max_users AS company_max_users,
                        c.max_products AS company_max_products
                 FROM auth_remember_tokens rt
                 INNER JOIN users u ON u.id = rt.user_id
                 LEFT JOIN companies c ON c.id = u.company_id
                 WHERE rt.selector = ?
                 LIMIT 1",
                [$cookie['selector']]
            )->fetch();

            if (!$row) {
                self::forgetClientCookie();
                return false;
            }

            $tokenId = (int)($row['id'] ?? 0);
            $expiresAt = strtotime((string)($row['expires_at'] ?? ''));
            $expectedHash = (string)($row['token_hash'] ?? '');
            $actualHash = hash('sha256', $cookie['validator']);

            if ($tokenId <= 0 || $expectedHash === '' || !hash_equals($expectedHash, $actualHash)) {
                self::deleteTokenById($tokenId);
                self::forgetClientCookie();
                return false;
            }

            if ($expiresAt !== false && $expiresAt < time()) {
                self::deleteTokenById($tokenId);
                self::forgetClientCookie();
                return false;
            }

            if (self::hasSuspiciousClientChange($row)) {
                Logger::security('Remember-me token rejected due to client mismatch', [
                    'user_id' => (int)($row['user_id'] ?? 0),
                    'selector' => (string)($row['selector'] ?? ''),
                ]);
                self::deleteTokenById($tokenId);
                self::forgetClientCookie();
                return false;
            }

            $user = self::buildSessionUser($row);
            if ($user === null) {
                self::deleteTokenById($tokenId);
                self::forgetClientCookie();
                return false;
            }

            $company = self::buildCompanyContext($row);
            $isSuperAdmin = !empty($user['is_super_admin']);
            if (!$isSuperAdmin && (!$company || strtolower(trim((string)($company['status'] ?? ''))) !== 'active')) {
                self::deleteTokenById($tokenId);
                self::forgetClientCookie();
                return false;
            }

            $newValidator = bin2hex(random_bytes(32));
            $newHash = hash('sha256', $newValidator);
            $newExpiryTs = time() + self::lifetime();
            $newExpiry = date('Y-m-d H:i:s', $newExpiryTs);

            $db->query(
                "UPDATE auth_remember_tokens
                 SET token_hash = ?, expires_at = ?, last_used_at = NOW(), user_agent = ?, ip_address = ?
                 WHERE id = ?",
                [
                    $newHash,
                    $newExpiry,
                    self::trimValue($_SERVER['HTTP_USER_AGENT'] ?? '', 255),
                    self::trimValue($_SERVER['REMOTE_ADDR'] ?? '', 45),
                    $tokenId,
                ]
            );

            session_regenerate_id(true);
            CSRF::rotateToken();
            Session::clearPermissionCache();
            Session::set('user', $user);
            Session::set('remember_token_selector', $cookie['selector']);
            Session::initFingerprint();

            if ($isSuperAdmin) {
                Tenant::reset();
            } elseif (!empty($user['company_id']) && $company) {
                Tenant::set((int)$user['company_id'], $company);
            }

            self::setCookie($cookie['selector'], $newValidator, $newExpiryTs);
            return true;
        } catch (\Throwable $e) {
            error_log('[REMEMBER_ME] Failed to resume session: ' . $e->getMessage());
            self::forgetClientCookie();
            return false;
        }
    }

    public static function revokeCurrentToken(): void {
        $selector = trim((string)Session::get('remember_token_selector', ''));
        if ($selector !== '') {
            self::deleteBySelector($selector);
            Session::remove('remember_token_selector');
        }

        self::forgetClientCookie();
    }

    public static function revokeAllForUser(int $userId): void {
        if ($userId <= 0) {
            return;
        }

        try {
            self::ensureSchema();
            Database::getInstance()->query(
                'DELETE FROM auth_remember_tokens WHERE user_id = ?',
                [$userId]
            );
        } catch (\Throwable $e) {
            error_log('[REMEMBER_ME] Failed to delete tokens for user: ' . $e->getMessage());
        }

        $sessionUserId = (int)(Session::get('user')['id'] ?? 0);
        if ($sessionUserId === $userId) {
            Session::remove('remember_token_selector');
            self::forgetClientCookie();
        }
    }

    private static function ensureSchema(): void {
        if (self::$schemaChecked) {
            return;
        }

        try {
            Database::getInstance()->query(
                "SELECT 1 FROM auth_remember_tokens LIMIT 1"
            )->fetchColumn();
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Missing auth_remember_tokens table. Run php cli/migrate.php before using remember-me tokens.',
                0,
                $e
            );
        }

        self::$schemaChecked = true;

        try {
            Database::getInstance()->query(
                "DELETE FROM auth_remember_tokens
                 WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
        } catch (\Throwable $e) {
            error_log('[REMEMBER_ME] Failed to prune old tokens: ' . $e->getMessage());
        }
    }

    private static function setCookie(string $selector, string $validator, int $expiresTs): void {
        $cookieValue = $selector . ':' . $validator;
        setcookie(self::COOKIE_NAME, $cookieValue, self::cookieOptions($expiresTs));
        $_COOKIE[self::COOKIE_NAME] = $cookieValue;
    }

    private static function forgetClientCookie(): void {
        setcookie(self::COOKIE_NAME, '', self::cookieOptions(time() - 3600));
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    private static function readCookie(): ?array {
        $raw = trim((string)($_COOKIE[self::COOKIE_NAME] ?? ''));
        if ($raw === '' || !str_contains($raw, ':')) {
            return null;
        }

        [$selector, $validator] = explode(':', $raw, 2);
        $selector = trim($selector);
        $validator = trim($validator);

        if (!preg_match('/^[a-f0-9]{18}$/', $selector)) {
            return null;
        }
        if (!preg_match('/^[a-f0-9]{64}$/', $validator)) {
            return null;
        }

        return ['selector' => $selector, 'validator' => $validator];
    }

    private static function cookieOptions(int $expiresTs): array {
        $cookieDomain = '';
        $requestHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if (defined('TENANT_BASE_DOMAIN') && TENANT_BASE_DOMAIN !== '') {
            $baseDomain = TENANT_BASE_DOMAIN;
            if ($baseDomain !== 'localhost' && !filter_var($baseDomain, FILTER_VALIDATE_IP)) {
                $normalizedBase = ltrim(strtolower((string)$baseDomain), '.');
                if (
                    $requestHost !== '' &&
                    $requestHost !== $normalizedBase &&
                    !str_ends_with($requestHost, '.' . $normalizedBase) &&
                    $requestHost !== 'localhost' &&
                    !filter_var($requestHost, FILTER_VALIDATE_IP)
                ) {
                    $cookieDomain = $requestHost;
                } else {
                    $cookieDomain = '.' . $normalizedBase;
                }
            }
        }

        $isSecure = (defined('APP_ENV') && APP_ENV === 'production')
            || ((!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'));

        return [
            'expires' => $expiresTs,
            'path' => '/',
            'domain' => $cookieDomain,
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    private static function lifetime(): int {
        $env = getenv('REMEMBER_ME_LIFETIME');
        $ttl = $env !== false ? (int)$env : self::DEFAULT_LIFETIME;
        return max(86400, $ttl);
    }

    private static function deleteBySelector(string $selector): void {
        if ($selector === '') {
            return;
        }

        try {
            self::ensureSchema();
            Database::getInstance()->query(
                'DELETE FROM auth_remember_tokens WHERE selector = ?',
                [$selector]
            );
        } catch (\Throwable $e) {
            error_log('[REMEMBER_ME] Failed to delete token by selector: ' . $e->getMessage());
        }
    }

    private static function deleteTokenById(int $id): void {
        if ($id <= 0) {
            return;
        }

        try {
            self::ensureSchema();
            Database::getInstance()->query(
                'DELETE FROM auth_remember_tokens WHERE id = ?',
                [$id]
            );
        } catch (\Throwable $e) {
            error_log('[REMEMBER_ME] Failed to delete token by id: ' . $e->getMessage());
        }
    }

    private static function buildSessionUser(array $row): ?array {
        $userId = (int)($row['user_id'] ?? $row['id'] ?? 0);
        if ($userId <= 0 || empty($row['username']) || empty($row['email'])) {
            return null;
        }

        if (isset($row['deleted_at']) && $row['deleted_at'] !== null) {
            return null;
        }

        if ((int)($row['is_active'] ?? 0) !== 1) {
            return null;
        }

        unset(
            $row['token_hash'],
            $row['selector'],
            $row['expires_at'],
            $row['user_agent'],
            $row['ip_address'],
            $row['company_id_check'],
            $row['company_name_check'],
            $row['company_status_check'],
            $row['company_is_demo'],
            $row['company_plan'],
            $row['company_saas_plan_id'],
            $row['company_subscription_status'],
            $row['company_trial_ends_at'],
            $row['company_max_users'],
            $row['company_max_products']
        );

        $row['id'] = $userId;
        $row['is_super_admin'] = !empty($row['is_super_admin']);
        $row['twofa_pending'] = false;
        $row['twofa_verified'] = true;

        unset($row['password'], $row['twofa_secret'], $row['twofa_recovery_codes']);
        return $row;
    }

    private static function buildCompanyContext(array $row): ?array {
        $companyId = (int)($row['company_id'] ?? 0);
        if ($companyId <= 0) {
            return null;
        }

        return [
            'id' => $companyId,
            'name' => $row['company_name_check'] ?? null,
            'status' => $row['company_status_check'] ?? null,
            'is_demo' => $row['company_is_demo'] ?? 0,
            'plan' => $row['company_plan'] ?? null,
            'saas_plan_id' => $row['company_saas_plan_id'] ?? null,
            'subscription_status' => $row['company_subscription_status'] ?? null,
            'trial_ends_at' => $row['company_trial_ends_at'] ?? null,
            'max_users' => $row['company_max_users'] ?? null,
            'max_products' => $row['company_max_products'] ?? null,
        ];
    }

    private static function trimValue(string $value, int $limit): string {
        return mb_substr(trim($value), 0, $limit);
    }

    private static function hasSuspiciousClientChange(array $row): bool {
        $storedUa = self::trimValue((string)($row['user_agent'] ?? ''), 255);
        $currentUa = self::trimValue((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 255);
        if ($storedUa !== '' && $currentUa !== '' && self::browserFingerprint($storedUa) !== self::browserFingerprint($currentUa)) {
            return true;
        }

        $storedIp = self::trimValue((string)($row['ip_address'] ?? ''), 45);
        $currentIp = self::trimValue((string)($_SERVER['REMOTE_ADDR'] ?? ''), 45);
        if ($storedIp !== '' && $currentIp !== '' && self::ipNetworkFingerprint($storedIp) !== self::ipNetworkFingerprint($currentIp)) {
            return true;
        }

        return false;
    }

    private static function browserFingerprint(string $userAgent): string {
        $ua = strtolower($userAgent);
        foreach (['edg/', 'chrome/', 'firefox/', 'safari/', 'opr/', 'opera/', 'trident/', 'msie'] as $marker) {
            if (str_contains($ua, $marker)) {
                return $marker;
            }
        }

        return substr(hash('sha256', $ua), 0, 16);
    }

    private static function ipNetworkFingerprint(string $ip): string {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return implode('.', array_slice($parts, 0, 3));
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4));
        }

        return $ip;
    }
}
