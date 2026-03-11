<?php
/**
 * Asset Helper — Versioned & Optimized Static Asset Loading
 * 
 * Provides URL generation with cache-busting version strings
 * and helpers for preload hints.
 * 
 * Usage in views:
 *   <link rel="stylesheet" href="<?= Asset::css('style.css') ?>">
 *   <script src="<?= Asset::js('app.js') ?>"></script>
 *   <img src="<?= Asset::img('logo.png') ?>">
 */
class Asset {
    private static ?string $version = null;

    /**
     * Get the asset version string.
     */
    private static function version(): string {
        if (self::$version === null) {
            self::$version = defined('ASSET_VERSION') ? ASSET_VERSION : '1.0.0';
        }
        return self::$version;
    }

    /**
     * Generate a versioned CSS URL.
     */
    public static function css(string $path): string {
        return APP_URL . '/assets/css/' . ltrim($path, '/') . '?v=' . self::version();
    }

    /**
     * Generate a versioned JS URL.
     */
    public static function js(string $path): string {
        return APP_URL . '/assets/js/' . ltrim($path, '/') . '?v=' . self::version();
    }

    /**
     * Generate a versioned image URL.
     */
    public static function img(string $path): string {
        return APP_URL . '/assets/images/' . ltrim($path, '/') . '?v=' . self::version();
    }

    /**
     * Generate a versioned URL for any asset.
     */
    public static function url(string $path): string {
        return APP_URL . '/assets/' . ltrim($path, '/') . '?v=' . self::version();
    }

    /**
     * Generate a preload link tag for critical resources.
     */
    public static function preload(string $path, string $as = 'style'): string {
        $url = self::url($path);
        return '<link rel="preload" href="' . htmlspecialchars($url) . '" as="' . $as . '">';
    }

    /**
     * Generate cache-control headers for static assets.
     * Call from a static file serving script or .htaccess.
     */
    public static function cacheHeaders(int $maxAge = 31536000): void {
        header("Cache-Control: public, max-age={$maxAge}, immutable");
        header("Vary: Accept-Encoding");
    }
}
