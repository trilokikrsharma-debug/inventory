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
    private string $page;
    private string $action;

    private function __construct(array $query, array $post, array $server, array $files, array $cookies) {
        $this->query   = $query;
        $this->post    = $post;
        $this->server  = $server;
        $this->files   = $files;
        $this->cookies = $cookies;
        $this->method  = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $this->page    = $query['page'] ?? 'dashboard';
        $this->action  = $query['action'] ?? 'index';
    }

    /**
     * Create a Request from the current PHP superglobals.
     */
    public static function capture(): self {
        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE);
    }

    /**
     * Create a Request from custom arrays (for testing).
     */
    public static function create(array $query = [], array $post = [], array $server = [], array $files = [], array $cookies = []): self {
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
}
