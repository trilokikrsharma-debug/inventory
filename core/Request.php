<?php
/**
 * Request — Immutable HTTP Request Abstraction
 * 
 * Wraps PHP superglobals ($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE)
 * into a clean, testable object. Eliminates direct superglobal access
 * throughout the application.
 * 
 * Usage:
 *   $request = Request::capture();
 *   $page  = $request->query('page', 'dashboard');
 *   $name  = $request->input('name');
 *   $file  = $request->file('avatar');
 *   $ip    = $request->ip();
 *   $ajax  = $request->isAjax();
 */
class Request {
    private array $query;      // $_GET
    private array $post;       // $_POST
    private array $server;     // $_SERVER
    private array $files;      // $_FILES
    private array $cookies;    // $_COOKIE
    private string $method;
    private string $path;
    private string $page;
    private string $action;
    private bool $hasExplicitPageQuery;

    private function __construct(array $query, array $post, array $server, array $files, array $cookies) {
        $this->query   = $query;
        $this->post    = $post;
        $this->server  = $server;
        $this->files   = $files;
        $this->cookies = $cookies;
        $this->method  = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $this->path    = self::normalizeRequestPath($server);
        $this->hasExplicitPageQuery = isset($query['page']) && trim((string)$query['page']) !== '';

        [$page, $action] = self::resolveRoute($query, $this->path);
        $this->page = $page;
        $this->action = $action;
    }

    /**
     * Create a Request from the current PHP superglobals.
     */
    public static function capture(): self {
        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE);
    }

    /**
     * Create a Request from custom arrays (for testing).
     *
     * Backward compatibility:
     * - Preferred signature: create(query, post, server, files, cookies)
     * - Legacy signature:    create(server, query, post)
     */
    public static function create(array $first = [], array $second = [], array $third = [], array $files = [], array $cookies = []): self {
        $looksLikeServer = static function (array $candidate): bool {
            return isset($candidate['REQUEST_METHOD'])
                || isset($candidate['REQUEST_URI'])
                || isset($candidate['HTTP_HOST'])
                || isset($candidate['REMOTE_ADDR']);
        };

        if ($looksLikeServer($first)) {
            $server = $first;
            $query = $second;
            $post = $third;
        } else {
            $query = $first;
            $post = $second;
            $server = $third;
        }

        $server = array_merge(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '127.0.0.1'], $server);
        return new self($query, $post, $server, $files, $cookies);
    }

    // ── Accessors ──

    public function page(): string {
        return $this->page;
    }

    public function action(): string {
        return $this->action;
    }

    public function path(): string {
        return $this->path;
    }

    public function isApiPath(): bool {
        return str_starts_with($this->path, '/api/');
    }

    public function hasExplicitPageQuery(): bool {
        return $this->hasExplicitPageQuery;
    }

    public function method(): string {
        return $this->method;
    }

    public function isPost(): bool {
        return $this->method === 'POST';
    }

    public function isGet(): bool {
        return $this->method === 'GET';
    }

    public function isAjax(): bool {
        return !empty($this->server['HTTP_X_REQUESTED_WITH'])
            && strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function ip(): string {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function contentLength(): int {
        return (int)($this->server['CONTENT_LENGTH'] ?? 0);
    }

    public function referer(): string {
        return $this->server['HTTP_REFERER'] ?? '';
    }

    // ── Data Access ──

    /**
     * Get a query string parameter ($_GET).
     */
    public function query(string $key, mixed $default = null): mixed {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     */
    public function queryAll(): array {
        return $this->query;
    }

    /**
     * Get sanitized query params safe for view rendering.
     */
    public function safeQuery(): array {
        return array_map(
            fn($v) => is_string($v) ? htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $v,
            $this->query
        );
    }

    /**
     * Get a POST parameter.
     */
    public function input(string $key, mixed $default = null): mixed {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get all POST data.
     */
    public function all(): array {
        return $this->post;
    }

    /**
     * Get a specific file from the request.
     */
    public function file(string $key): ?array {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if the request has a file.
     */
    public function hasFile(string $key): bool {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get a server variable.
     */
    public function server(string $key, mixed $default = null): mixed {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get a cookie value.
     */
    public function cookie(string $key, mixed $default = null): mixed {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get a header value.
     */
    public function header(string $name, mixed $default = null): mixed {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? $default;
    }

    /**
     * Check if a POST key exists and is non-empty.
     */
    public function has(string $key): bool {
        return isset($this->post[$key]) && $this->post[$key] !== '';
    }

    /**
     * Get multiple POST values at once.
     */
    public function only(array $keys): array {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->post[$key] ?? null;
        }
        return $result;
    }

    private static function resolveRoute(array $query, string $path): array {
        if (isset($query['page']) && trim((string)$query['page']) !== '') {
            $page = trim((string)$query['page']);
            $action = trim((string)($query['action'] ?? 'index'));
            return [$page, $action !== '' ? $action : 'index'];
        }

        $rewrittenPath = self::extractRewrittenPathFromQuery($query);
        if ($rewrittenPath !== null) {
            return self::parsePathSegments($rewrittenPath);
        }

        return self::parsePathSegments($path);
    }

    private static function normalizeRequestPath(array $server): string {
        $rawPath = (string)(parse_url($server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
        $rawPath = str_replace('\\', '/', $rawPath);

        $scriptName = str_replace('\\', '/', (string)($server['SCRIPT_NAME'] ?? '/index.php'));
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        if ($scriptDir === '/' || $scriptDir === '.') {
            $scriptDir = '';
        }

        if ($scriptDir !== '') {
            if ($rawPath === $scriptDir) {
                $rawPath = '/';
            } elseif (str_starts_with($rawPath, $scriptDir . '/')) {
                $rawPath = substr($rawPath, strlen($scriptDir));
            }
        }

        if (str_starts_with($rawPath, '/index.php')) {
            $rawPath = substr($rawPath, strlen('/index.php'));
        }

        $normalized = '/' . ltrim($rawPath, '/');
        return $normalized === '//' || $normalized === '' ? '/' : $normalized;
    }

    private static function extractRewrittenPathFromQuery(array $query): ?string {
        foreach (array_keys($query) as $key) {
            if (is_string($key) && str_starts_with($key, '/')) {
                return $key;
            }
        }

        return null;
    }

    private static function parsePathSegments(string $path): array {
        $trimmed = trim($path, '/');
        if ($trimmed === '') {
            return ['', 'index'];
        }

        $segments = array_values(array_filter(explode('/', $trimmed), static fn($segment) => $segment !== ''));
        if (empty($segments)) {
            return ['', 'index'];
        }

        if (strtolower($segments[0]) === 'index.php') {
            array_shift($segments);
        }

        if (empty($segments)) {
            return ['', 'index'];
        }

        if (!self::isRecognizedPrettyRoute($segments[0])) {
            return ['', 'index'];
        }

        return [
            (string)$segments[0],
            isset($segments[1]) && trim((string)$segments[1]) !== ''
                ? (string)$segments[1]
                : 'index',
        ];
    }

    private static function isRecognizedPrettyRoute(string $segment): bool {
        static $knownRoutes = [
            'api',
            'backup',
            'brands',
            'categories',
            'company',
            'customers',
            'dashboard',
            'demo',
            'demo-login',
            'demo_login',
            'health',
            'home',
            'insights',
            'invoice',
            'login',
            'logout',
            'payments',
            'platform',
            'pricing',
            'promos',
            'products',
            'profile',
            'purchases',
            'quotations',
            'referrals',
            'reports',
            'roles',
            'sale_returns',
            'sales',
            'saas_billing',
            'saas_dashboard',
            'saas_plans',
            'settings',
            'signup',
            'suppliers',
            'twoFactor',
            'twofactor',
            'units',
            'users',
        ];

        return in_array($segment, $knownRoutes, true);
    }
}
