<?php
/**
 * Two-Factor Authentication Service
 * 
 * TOTP-based 2FA compatible with Google Authenticator, Authy, etc.
 * Pure PHP implementation — no external dependencies required.
 * 
 * Uses RFC 6238 (TOTP) and RFC 4226 (HOTP) algorithms.
 * 
 * Usage:
 *   $secret = TwoFactorService::generateSecret();
 *   $qrUrl  = TwoFactorService::getQrCodeUrl($secret, 'user@email.com');
 *   $valid  = TwoFactorService::verifyCode($secret, '123456');
 */
class TwoFactorService {
    private const CODE_LENGTH = 6;
    private const TIME_STEP = 30;       // seconds
    private const WINDOW = 1;           // ±1 time step tolerance
    private const SECRET_LENGTH = 20;   // bytes (160-bit)
    private const RECOVERY_CODE_COUNT = 8;

    // Base32 alphabet (RFC 4648)
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    // ─── Secret Generation ───────────────────────────────

    /**
     * Generate a random TOTP secret (base32 encoded).
     */
    public static function generateSecret(): string {
        $bytes = random_bytes(self::SECRET_LENGTH);
        return self::base32Encode($bytes);
    }

    /**
     * Generate recovery backup codes.
     * 
     * @return array{plain: string[], hashed: string[]}
     */
    public static function generateRecoveryCodes(): array {
        $plain = [];
        $hashed = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4))); // 8-char hex codes
            $code = substr($code, 0, 4) . '-' . substr($code, 4, 4); // XXXX-XXXX
            $plain[] = $code;
            $hashed[] = password_hash($code, PASSWORD_BCRYPT, ['cost' => 10]);
        }

        return ['plain' => $plain, 'hashed' => $hashed];
    }

    // ─── Code Verification ───────────────────────────────

    /**
     * Verify a TOTP code against a secret.
     * Allows ±1 time step to handle clock drift.
     */
    public static function verifyCode(string $secret, string $code): bool {
        $code = preg_replace('/\s+/', '', $code);
        if (strlen($code) !== self::CODE_LENGTH || !ctype_digit($code)) {
            return false;
        }

        $secretBytes = self::base32Decode($secret);
        $timeSlice = intdiv(time(), self::TIME_STEP);

        // Check current and adjacent time windows
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            $computed = self::generateHotp($secretBytes, $timeSlice + $i);
            if (hash_equals($computed, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify a recovery code and mark it as used.
     * 
     * @param int    $userId  User ID
     * @param string $code    Recovery code (e.g., "A1B2-C3D4")
     * @return bool
     */
    public static function verifyRecoveryCode(int $userId, string $code): bool {
        $code = strtoupper(trim($code));

        $db = Database::getInstance();
        $user = $db->query(
            "SELECT twofa_recovery_codes FROM users WHERE id = ?",
            [$userId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$user || empty($user['twofa_recovery_codes'])) {
            return false;
        }

        $hashedCodes = json_decode($user['twofa_recovery_codes'], true);
        if (!is_array($hashedCodes)) return false;

        foreach ($hashedCodes as $index => $hashed) {
            if (password_verify($code, $hashed)) {
                // Remove used code
                array_splice($hashedCodes, $index, 1);
                $db->query(
                    "UPDATE users SET twofa_recovery_codes = ? WHERE id = ?",
                    [json_encode(array_values($hashedCodes)), $userId]
                );
                return true;
            }
        }

        return false;
    }

    // ─── QR Code URL ─────────────────────────────────────

    /**
     * Generate an otpauth:// URI for QR code generation.
     */
    public static function getOtpAuthUrl(string $secret, string $email, string $issuer = 'InvenBill Pro'): string {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
            self::CODE_LENGTH,
            self::TIME_STEP
        );
    }

    /**
     * Generate a QR code image URL via Google Charts API.
     * For production, consider generating QR locally with a library.
     */
    public static function getQrCodeUrl(string $secret, string $email, string $issuer = 'InvenBill Pro'): string {
        $otpUrl = self::getOtpAuthUrl($secret, $email, $issuer);
        return 'https://chart.googleapis.com/chart?cht=qr&chs=250x250&chl=' . urlencode($otpUrl);
    }

    // ─── Database Operations ─────────────────────────────

    /**
     * Enable 2FA for a user.
     */
    public static function enable(int $userId, string $secret, array $hashedRecoveryCodes): bool {
        $db = Database::getInstance();
        $db->query(
            "UPDATE users SET twofa_secret = ?, twofa_enabled = 1, twofa_recovery_codes = ? WHERE id = ?",
            [$secret, json_encode($hashedRecoveryCodes), $userId]
        );

        Logger::security('2FA enabled', ['user_id' => $userId]);
        AuditService::logUpdate('users', $userId, ['twofa_enabled' => 0], ['twofa_enabled' => 1]);

        return true;
    }

    /**
     * Disable 2FA for a user.
     */
    public static function disable(int $userId): bool {
        $db = Database::getInstance();
        $db->query(
            "UPDATE users SET twofa_secret = NULL, twofa_enabled = 0, twofa_recovery_codes = NULL WHERE id = ?",
            [$userId]
        );

        Logger::security('2FA disabled', ['user_id' => $userId]);
        return true;
    }

    /**
     * Check if user has 2FA enabled.
     */
    public static function isEnabled(int $userId): bool {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT twofa_enabled FROM users WHERE id = ?",
            [$userId]
        )->fetch(\PDO::FETCH_ASSOC);

        return $result && (bool)$result['twofa_enabled'];
    }

    /**
     * Get user's 2FA secret.
     */
    public static function getSecret(int $userId): ?string {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT twofa_secret FROM users WHERE id = ?",
            [$userId]
        )->fetch(\PDO::FETCH_ASSOC);

        return $result['twofa_secret'] ?? null;
    }

    // ─── TOTP Algorithm (RFC 6238) ───────────────────────

    /**
     * Generate HOTP code per RFC 4226.
     */
    private static function generateHotp(string $secretBytes, int $counter): string {
        // Pack counter as 8-byte big-endian
        $counterBytes = pack('J', $counter);

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $counterBytes, $secretBytes, true);

        // Dynamic truncation
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::CODE_LENGTH);

        return str_pad((string)$code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    // ─── Base32 Encoding/Decoding ────────────────────────

    private static function base32Encode(string $data): string {
        $encoded = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $buffer = ($buffer << 8) | ord($data[$i]);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $encoded .= self::BASE32_CHARS[($buffer >> $bitsLeft) & 0x1F];
            }
        }

        if ($bitsLeft > 0) {
            $encoded .= self::BASE32_CHARS[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $encoded;
    }

    private static function base32Decode(string $data): string {
        $data = strtoupper(rtrim($data, '='));
        $decoded = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $val = strpos(self::BASE32_CHARS, $data[$i]);
            if ($val === false) continue;

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $decoded .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $decoded;
    }
}
