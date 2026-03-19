<?php
/**
 * Lightweight asset minifier for production bundles.
 *
 * Usage:
 *   php cli/build_assets.php
 */

define('BASE_PATH', dirname(__DIR__));

/**
 * Basic CSS minification for our static stylesheet.
 */
function minifyCss(string $css): string {
    $css = preg_replace('!/\*.*?\*/!s', '', $css) ?? $css;
    $css = preg_replace('/\s+/', ' ', $css) ?? $css;
    $css = str_replace([' {', '{ ', ' }', '} ', '; ', ': ', ', '], ['{', '{', '}', '}', ';', ':', ','], $css);
    return trim($css);
}

/**
 * Basic JS minification preserving quoted strings.
 */
function minifyJs(string $js): string {
    // Remove block comments.
    $js = preg_replace('!/\*.*?\*/!s', '', $js) ?? $js;

    // Remove line comments outside URLs.
    $lines = preg_split('/\R/', $js) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, '//')) {
            continue;
        }
        $clean[] = $line;
    }

    $js = implode("\n", $clean);
    $js = preg_replace('/\s+/', ' ', $js) ?? $js;
    $js = str_replace([' ;', '; ', ' {', '{ ', ' }', '} ', ', '], [';', ';', '{', '{', '}', '}', ','], $js);
    return trim($js);
}

function build(string $source, string $target, callable $minifier): void {
    if (!is_file($source)) {
        throw new RuntimeException("Source file not found: {$source}");
    }

    $content = file_get_contents($source);
    if ($content === false) {
        throw new RuntimeException("Failed to read source file: {$source}");
    }

    $minified = $minifier($content);
    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0755, true);
    }
    file_put_contents($target, $minified);
}

try {
    build(
        BASE_PATH . '/assets/css/style.css',
        BASE_PATH . '/assets/css/style.min.css',
        'minifyCss'
    );
    build(
        BASE_PATH . '/assets/js/app.js',
        BASE_PATH . '/assets/js/app.min.js',
        'minifyJs'
    );
    echo "Assets built successfully.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Asset build failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
