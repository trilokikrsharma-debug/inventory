<?php
/**
 * Middleware Pipeline
 * 
 * Executes middleware classes in sequence using the chain-of-responsibility pattern.
 * Each middleware can modify the request, perform side effects, 
 * or short-circuit the pipeline.
 * 
 * Usage:
 *   $pipeline = new Pipeline();
 *   $pipeline->pipe(new SecurityHeadersMiddleware());
 *   $pipeline->pipe(new SessionMiddleware());
 *   $pipeline->run($request);
 */
class Pipeline {
    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    /**
     * Add a middleware to the pipeline.
     */
    public function pipe(MiddlewareInterface $middleware): self {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Execute the pipeline against a Request.
     * 
     * Each middleware receives the request and a $next callable.
     * Calling $next($request) passes control to the next middleware.
     * Not calling $next() short-circuits the pipeline (e.g., auth failure).
     */
    public function run(Request $request): void {
        $runner = $this->buildRunner(0);
        $runner($request);
    }

    /**
     * Build a recursive runner function for the middleware chain.
     */
    private function buildRunner(int $index): callable {
        // Terminal: no more middleware to run
        if ($index >= count($this->middlewares)) {
            return function (Request $request): void {
                // End of pipeline — control returns to the caller
            };
        }

        $middleware = $this->middlewares[$index];
        $next = $this->buildRunner($index + 1);

        return function (Request $request) use ($middleware, $next): void {
            $middleware->handle($request, $next);
        };
    }
}
