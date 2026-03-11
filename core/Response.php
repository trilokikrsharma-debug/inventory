<?php
/**
 * Response — HTTP Response Abstraction
 * 
 * Provides a clean interface for sending HTTP responses
 * instead of calling header() and echo directly.
 */
class Response {
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';

    public function __construct(string $body = '', int $statusCode = 200, array $headers = []) {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function json(array $data, int $statusCode = 200): self {
        $response = new self(json_encode($data), $statusCode);
        $response->headers['Content-Type'] = 'application/json';
        return $response;
    }

    public static function redirect(string $url, int $statusCode = 302): never {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    public static function error(int $code, string $message = ''): self {
        return new self($message, $code);
    }

    public function status(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    public function body(string $content): self {
        $this->body = $content;
        return $this;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function send(): void {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
