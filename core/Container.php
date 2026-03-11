<?php
/**
 * Dependency Injection Container
 * 
 * Maps interfaces/class names to concrete instances or factories.
 * Supports singletons and transient instances.
 */
class Container {
    private static array $bindings = [];
    private static array $instances = [];

    /**
     * Bind a key to a factory function (Transient - new instance every time)
     */
    public static function bind(string $key, callable $factory): void {
        self::$bindings[$key] = $factory;
    }

    /**
     * Bind a key to a factory function (Singleton - same instance every time)
     */
    public static function singleton(string $key, callable $factory): void {
        self::$bindings[$key] = function() use ($key, $factory) {
            if (!isset(self::$instances[$key])) {
                self::$instances[$key] = $factory();
            }
            return self::$instances[$key];
        };
    }

    /**
     * Bind an existing instance to a key
     */
    public static function instance(string $key, $instance): void {
        self::$instances[$key] = $instance;
        self::$bindings[$key] = function() use ($key) {
            return self::$instances[$key];
        };
    }

    /**
     * Resolve an instance from the container
     */
    public static function make(string $key): mixed {
        if (!isset(self::$bindings[$key])) {
            throw new \Exception("No binding found in container for key: {$key}");
        }
        return (self::$bindings[$key])();
    }

    /**
     * Check if a binding exists
     */
    public static function has(string $key): bool {
        return isset(self::$bindings[$key]) || isset(self::$instances[$key]);
    }

    /**
     * Flush all instances (useful for testing)
     */
    public static function flush(): void {
        self::$instances = [];
    }
}
