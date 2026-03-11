<?php
/**
 * CSRF Injector
 * 
 * Auto-injects CSRF tokens into HTML output:
 * 1. Hidden fields in POST/PUT/DELETE/PATCH forms
 * 2. Meta tag in <head> for JavaScript usage
 * 
 * Extracted from index.php lines 323-352.
 */
class CsrfInjector {
    /**
     * Inject CSRF tokens into the output HTML.
     * 
     * @param  string $output  Raw HTML output from output buffer
     * @return string          HTML with CSRF tokens injected
     */
    public static function inject(string $output): string {
        // Skip JSON responses
        if (in_array('Content-Type: application/json', headers_list())) {
            return $output;
        }

        $tokenField = CSRF::field();
        $name = CSRF_TOKEN_NAME;

        // 1. Auto-inject CSRF hidden field into POST forms missing it
        $output = preg_replace_callback(
            '/(<form\b[^>]*method=["\']?(?:POST|PUT|DELETE|PATCH)["\']?[^>]*>)(.*?)(<\/form>)/is',
            function ($matches) use ($tokenField, $name) {
                // Skip if CSRF already present
                if (
                    strpos($matches[2], 'name="' . $name . '"') !== false
                    || strpos($matches[2], "name='" . $name . "'") !== false
                ) {
                    return $matches[0];
                }
                return $matches[1] . "\n    " . $tokenField . $matches[2] . $matches[3];
            },
            $output
        );

        // 2. Inject CSRF meta tag in <head> for JS usage
        if (strpos($output, '</head>') !== false) {
            $meta = '<meta name="csrf-token" content="' . CSRF::getToken() . '">';
            $output = str_replace('</head>', "    {$meta}\n</head>", $output);
        }

        return $output;
    }
}
