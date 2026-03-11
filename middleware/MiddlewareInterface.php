<?php
/**
 * Middleware Interface
 * 
 * Contract for all middleware classes in the pipeline.
 * Each middleware must implement handle(), which receives
 * the current Request and a callable to pass control to the next middleware.
 */
interface MiddlewareInterface {
    /**
     * Handle the request.
     * 
     * @param Request  $request  The current HTTP request
     * @param callable $next     Call this to pass control to the next middleware: $next($request)
     * @return void
     */
    public function handle(Request $request, callable $next): void;
}
