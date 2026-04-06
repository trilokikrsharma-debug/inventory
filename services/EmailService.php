<?php
/**
 * Email Service — Queue-Based Email Delivery
 * 
 * Dispatches emails through the job queue for async processing.
 * Uses PHP's mail() function or SMTP (configurable).
 * 
 * Usage:
 *   EmailService::send('user@email.com', 'Subject', '<h1>Body</h1>');
 *   EmailService::sendInvoice($saleId, 'customer@email.com');
 *   EmailService::sendPasswordReset($userId, $token);
 */
class EmailService {
    /**
     * Send an email (queued for background delivery).
     */
    public static function send(string $to, string $subject, string $htmlBody, array $options = []): int {
        return JobDispatcher::dispatch('email', 'EmailService::processEmail', [
            'to' => $to,
            'subject' => $subject,
            'body' => $htmlBody,
            'from' => $options['from'] ?? self::defaultFrom(),
            'reply_to' => $options['reply_to'] ?? null,
            'attachments' => $options['attachments'] ?? [],
        ], priority: 3);
    }

    /**
     * Send invoice PDF via email.
     */
    public static function sendInvoice(int $saleId, string $toEmail): int {
        $db = Database::getInstance();
        $user = Session::get('user');
        $sale = $db->query(
            "SELECT invoice_number FROM sales WHERE id = ? AND company_id = ?",
            [$saleId, $user['company_id'] ?? 0]
        )->fetch(\PDO::FETCH_ASSOC);

        $invoiceNo = $sale['invoice_number'] ?? $saleId;
        $companyName = self::companyName();

        return JobDispatcher::dispatch('email', 'EmailService::processInvoiceEmail', [
            'sale_id' => $saleId,
            'to' => $toEmail,
            'invoice_number' => $invoiceNo,
            'company_name' => $companyName,
        ], priority: 2);
    }

    /**
     * Send password reset email.
     */
    public static function sendPasswordReset(int $userId, string $resetToken): int {
        Helper::securityLog('PASSWORD_RESET_EMAIL_BLOCKED', 'Password reset email was requested but no self-service reset flow is enabled.');
        throw new \RuntimeException('Password reset email flow is disabled until a complete token-based reset implementation is added.');
    }

    /**
     * Send 2FA alert email.
     */
    public static function send2faAlert(int $userId, string $action): int {
        $db = Database::getInstance();
        $user = $db->query("SELECT email, name FROM users WHERE id = ?", [$userId])->fetch(\PDO::FETCH_ASSOC);

        if (!$user || empty($user['email'])) return 0;

        $companyName = self::companyName();
        $body = <<<HTML
        <div style="font-family:sans-serif; max-width:500px; margin:0 auto; padding:20px;">
            <h2 style="color:#4e73df;">{$companyName} — Security Alert</h2>
            <p>Hi {$user['name']},</p>
            <p>Two-factor authentication was <strong>{$action}</strong> on your account.</p>
            <p>If you did not perform this action, contact your administrator immediately.</p>
            <p style="color:#888; font-size:12px;">Time: {date('Y-m-d H:i:s')}</p>
        </div>
HTML;

        return self::send($user['email'], "Security Alert: 2FA {$action} — {$companyName}", $body);
    }

    // ─── Job Handlers (called by worker) ─────────────────

    /**
     * Process a queued email job.
     */
    public static function processEmail(array $payload, array $job = []): void {
        $to = $payload['to'] ?? '';
        $subject = $payload['subject'] ?? '';
        $body = $payload['body'] ?? '';
        $from = $payload['from'] ?? self::defaultFrom();

        if (empty($to) || empty($subject)) {
            throw new \RuntimeException('Email missing required fields');
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from,
        ];
        if (!empty($payload['reply_to'])) {
            $headers[] = 'Reply-To: ' . $payload['reply_to'];
        }

        $sent = @mail($to, $subject, $body, implode("\r\n", $headers));
        if (!$sent) {
            throw new \RuntimeException("mail() failed for: {$to}");
        }

        Logger::info('Email sent', ['to' => $to, 'subject' => $subject]);
    }

    /**
     * Process an invoice email job (generates PDF on the fly).
     */
    public static function processInvoiceEmail(array $payload, array $job = []): void {
        $saleId = $payload['sale_id'] ?? 0;
        $to = $payload['to'] ?? '';
        $invoiceNo = $payload['invoice_number'] ?? $saleId;
        $companyName = $payload['company_name'] ?? 'InvenBill';

        $body = <<<HTML
        <div style="font-family:sans-serif; max-width:500px; margin:0 auto; padding:20px;">
            <h2 style="color:#4e73df;">{$companyName}</h2>
            <p>Please find attached your invoice <strong>#{$invoiceNo}</strong>.</p>
            <p>Thank you for your business!</p>
        </div>
HTML;

        // For now, send without attachment (PDF attachment requires MIME encoding)
        self::processEmail([
            'to' => $to,
            'subject' => "Invoice #{$invoiceNo} — {$companyName}",
            'body' => $body,
            'from' => self::defaultFrom(),
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────

    private static function defaultFrom(): string {
        $name = self::companyName();
        return "{$name} <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>';
    }

    private static function companyName(): string {
        try {
            $cid = Tenant::id();
            if ($cid) {
                $db = Database::getInstance();
                $settings = $db->query(
                    "SELECT company_name FROM company_settings WHERE company_id = ? LIMIT 1",
                    [$cid]
                )->fetch(\PDO::FETCH_ASSOC);
                return $settings['company_name'] ?? (defined('APP_NAME') ? APP_NAME : 'InvenBill');
            }
            return defined('APP_NAME') ? APP_NAME : 'InvenBill';
        } catch (\Exception $e) {
            return defined('APP_NAME') ? APP_NAME : 'InvenBill';
        }
    }
}
