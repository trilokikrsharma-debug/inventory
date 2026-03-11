<?php
/**
 * Helper Functions
 * 
 * Collection of useful utility functions used throughout the application.
 */
class Helper {
    /**
     * Decode accidentally double/triple HTML-encoded text from legacy records.
     */
    public static function decodeHtmlEntities($value, int $maxDepth = 10): string {
        $text = (string)($value ?? '');
        for ($i = 0; $i < $maxDepth; $i++) {
            $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $text) {
                break;
            }
            $text = $decoded;
        }
        return $text;
    }

    /**
     * Format currency amount
     */
    public static function formatCurrency($amount, $symbol = null) {
        if ($symbol === null) {
            $settings = (new SettingsModel())->getSettings();
            $symbol = $settings['currency_symbol'] ?? '₹';
        }
        $symbol = self::normalizeCurrencySymbol($symbol);
        return $symbol . ' ' . number_format((float)$amount, 2);
    }

    /**
     * Normalize common mojibake currency symbols from legacy/encoding issues.
     */
    public static function normalizeCurrencySymbol($symbol): string {
        $value = self::decodeHtmlEntities((string)($symbol ?? ''));
        $value = trim($value);

        if ($value === '' || $value === 'â‚¹' || $value === '&#8377;') {
            return '₹';
        }

        if (str_contains($value, 'â‚¹')) {
            return str_replace('â‚¹', '₹', $value);
        }

        return $value;
    }

    /**
     * Use ASCII-safe currency labels for PDF output to avoid '?' glyphs.
     */
    public static function pdfCurrencySymbol($symbol = null): string {
        if ($symbol === null) {
            $settings = (new SettingsModel())->getSettings();
            $symbol = $settings['currency_symbol'] ?? '₹';
        }

        $normalized = self::normalizeCurrencySymbol($symbol);
        if ($normalized === '₹') {
            return 'Rs.';
        }

        if (preg_match('/[^\x20-\x7E]/', $normalized)) {
            return 'INR';
        }

        return $normalized;
    }

    /**
     * Currency formatter dedicated to PDFs (ASCII-safe fallback symbol).
     */
    public static function formatCurrencyPdf($amount, $symbol = null): string {
        $safeSymbol = self::pdfCurrencySymbol($symbol);
        return $safeSymbol . ' ' . number_format((float)$amount, 2);
    }

    /**
     * Format date
     */
    public static function formatDate($date, $format = null) {
        if (empty($date)) return '';
        if ($format === null) {
            $settings = (new SettingsModel())->getSettings();
            $format = $settings['date_format'] ?? 'd-m-Y';
        }
        return date($format, strtotime($date));
    }

    /**
     * Format number
     */
    public static function formatNumber($number, $decimals = 2) {
        return number_format((float)$number, $decimals);
    }

    /**
     * Format quantity (remove trailing zeros)
     */
    public static function formatQty($qty) {
        $qty = (float)$qty;
        return ($qty == (int)$qty) ? (int)$qty : rtrim(rtrim(number_format($qty, 3), '0'), '.');
    }

    /**
     * Generate unique invoice/document number
     */
    public static function generateNumber($prefix, $nextNumber) {
        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Sanitize output for XSS protection
     */
    public static function escape($value) {
        $normalized = self::decodeHtmlEntities($value);
        return htmlspecialchars($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Convert stored payment method values into a readable label.
     */
    public static function paymentMethodLabel($method): string {
        return match (strtolower(trim((string)$method))) {
            'cash'   => 'Cash',
            'bank'   => 'Bank',
            'cheque' => 'Cheque',
            'online' => 'UPI / Online',
            'other'  => 'Other',
            default  => 'Cash',
        };
    }

    /**
     * Time-ago format
     */
    public static function timeAgo($datetime) {
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        return 'Just now';
    }

    /**
     * Upload file (Tenant-isolated, cryptographically safe)
     */
    public static function uploadFile($file, $directory = 'general', $allowedTypes = []) {
        if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error or no file sent.'];
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            self::securityLog('UPLOAD_REJECTED', 'Size exceeds limit: ' . $file['size']);
            return ['success' => false, 'message' => 'File size exceeds limit.'];
        }

        // 1. Strict MIME validation via finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);
        
        if (!empty($allowedTypes) && !in_array($realMime, $allowedTypes, true)) {
            self::securityLog('UPLOAD_REJECTED', 'Invalid MIME found: ' . $realMime);
            return ['success' => false, 'message' => 'Invalid file type. Found: ' . $realMime];
        }

        // 2. Strict Name & Extension Check (Smart block for executable patterns)
        $safeName = basename($file['name']); // Enforce basename sanitization natively
        
        $blockedExts = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phar', 'cgi', 'pl', 'exe', 'sh', 'bat', 'js', 'html', 'htm', 'asp', 'aspx', 'jsp'];
        
        // Smart multi-dot filename detection: block '.php.*' patterns but allow normal dots (e.g., 'version1.2.jpg')
        $dangerousPattern = '/\.(' . implode('|', $blockedExts) . ')(?:\.|$)/i';
        if (preg_match($dangerousPattern, $safeName)) {
            self::securityLog('UPLOAD_REJECTED', 'Illegal executable extension blocked: ' . $safeName);
            return ['success' => false, 'message' => 'Filenames containing executable extensions are not allowed.'];
        }

        $originalExt = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));

        // 3. Random cryptographic filename
        $extension = $originalExt ? '.' . $originalExt : '';
        $filename = bin2hex(random_bytes(16)) . '_' . time() . $extension;

        // 4. Tenant-based folder isolation + directory traversal prevention
        $tenantId = Tenant::id();
        $tenantPrefix = $tenantId ? 'tenant_' . (int)$tenantId . '/' : 'system/';
        $directory = preg_replace('/[^a-zA-Z0-9_-]/', '', $directory); // Prevent traversal
        
        $targetDir = UPLOAD_PATH . '/' . $tenantPrefix . $directory;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
            file_put_contents($targetDir . '/index.php', '<?php exit; ?>');
        }

        $targetPath = $targetDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // SECURITY: Reprocess images via GD to strip EXIF/embedded payloads
            if (in_array($realMime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                try {
                    $img = null;
                    switch ($realMime) {
                        case 'image/jpeg': $img = @imagecreatefromjpeg($targetPath); break;
                        case 'image/png':  $img = @imagecreatefrompng($targetPath); break;
                        case 'image/gif':  $img = @imagecreatefromgif($targetPath); break;
                        case 'image/webp': $img = @imagecreatefromwebp($targetPath); break;
                    }
                    if ($img) {
                        switch ($realMime) {
                            case 'image/jpeg': imagejpeg($img, $targetPath, 90); break;
                            case 'image/png':
                                imagealphablending($img, false);
                                imagesavealpha($img, true);
                                imagepng($img, $targetPath, 8);
                                break;
                            case 'image/gif':  imagegif($img, $targetPath); break;
                            case 'image/webp': imagewebp($img, $targetPath, 85); break;
                        }
                        imagedestroy($img);
                    }
                } catch (\Exception $e) {
                    // Reprocessing failed â€” keep original file (still safe due to MIME check)
                    error_log('[UPLOAD] Image reprocessing failed: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => 'uploads/' . $tenantPrefix . $directory . '/' . $filename
            ];
        }

        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }

    /**
     * Generate pagination HTML
     */
    public static function pagination($currentPage, $totalPages, $baseUrl) {
        if ($totalPages <= 1) return '';
        
        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-0">';
        
        // Previous
        $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
        $html .= '<li class="page-item ' . $prevDisabled . '"><a class="page-link" href="' . $baseUrl . '&pg=' . ($currentPage - 1) . '">&laquo;</a></li>';
        
        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        if ($start > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&pg=1">1</a></li>';
            if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        
        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '&pg=' . $i . '">' . $i . '</a></li>';
        }
        
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&pg=' . $totalPages . '">' . $totalPages . '</a></li>';
        }
        
        // Next
        $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
        $html .= '<li class="page-item ' . $nextDisabled . '"><a class="page-link" href="' . $baseUrl . '&pg=' . ($currentPage + 1) . '">&raquo;</a></li>';
        
        $html .= '</ul></nav>';
        
        return $html;
    }

    /**
     * Get payment status badge HTML
     */
    public static function paymentBadge($status) {
        $badges = [
            'paid'    => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Paid</span>',
            'partial' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Partial</span>',
            'unpaid'  => '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Unpaid</span>',
        ];
        return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }

    /**
     * Get status badge HTML
     */
    public static function statusBadge($status) {
        $badges = [
            'completed' => '<span class="badge bg-success">Completed</span>',
            'received'  => '<span class="badge bg-success">Received</span>',
            'pending'   => '<span class="badge bg-warning text-dark">Pending</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        ];
        return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }

    /**
     * Format file size to human-readable format
     */
    public static function formatFileSize($bytes) {
        if ($bytes == 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Minimal Security Logging
     */
    public static function securityLog($action, $details) {
        $logFile = (defined('LOG_PATH') ? LOG_PATH : __DIR__ . '/../logs') . '/security.log';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $date = date('Y-m-d H:i:s');
        $message = "[$date] IP: $ip | ACTION: $action | DETAILS: $details" . PHP_EOL;
        @file_put_contents($logFile, $message, FILE_APPEND);
    }

    /**
     * Convert number to words (for receipts/invoices)
     * Supports up to Crores (Indian numbering) with decimal (paise)
     */
    public static function numberToWords($number) {
        $number = round((float)$number, 2);
        if ($number == 0) return 'Zero';

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $convert = function($num) use ($ones, $tens, &$convert) {
            if ($num < 20) return $ones[$num];
            if ($num < 100) return $tens[(int)($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
            if ($num < 1000) return $ones[(int)($num / 100)] . ' Hundred' . ($num % 100 ? ' ' . $convert($num % 100) : '');
            if ($num < 100000) return $convert((int)($num / 1000)) . ' Thousand' . ($num % 1000 ? ' ' . $convert($num % 1000) : '');
            if ($num < 10000000) return $convert((int)($num / 100000)) . ' Lakh' . ($num % 100000 ? ' ' . $convert($num % 100000) : '');
            return $convert((int)($num / 10000000)) . ' Crore' . ($num % 10000000 ? ' ' . $convert($num % 10000000) : '');
        };

        $whole = (int)$number;
        $decimal = round(($number - $whole) * 100);

        $result = $convert($whole) . ' Rupees';
        if ($decimal > 0) {
            $result .= ' and ' . $convert($decimal) . ' Paise';
        }
        return $result . ' Only';
    }
}

// =========================================================
// Global Helper Functions (outside class for brevity in views)
// =========================================================

/**
 * Escape output for safe HTML rendering. Prevents XSS.
 * Usage in views: <?= e($value) ?>
 *
 * @param mixed $value  The value to escape (null-safe)
 * @return string
 */
function e($value) {
    return Helper::escape($value);
}

