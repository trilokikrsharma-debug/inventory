<?php
/**
 * Two-Factor Authentication Controller
 * 
 * Handles 2FA setup (QR code + recovery codes) and
 * OTP verification during login flow.
 * 
 * Routes:
 *   ?page=twoFactor&action=setup         Show QR code for setup
 *   ?page=twoFactor&action=enable        POST: verify + enable 2FA
 *   ?page=twoFactor&action=disable       POST: disable 2FA
 *   ?page=twoFactor&action=verify        OTP verification during login
 *   ?page=twoFactor&action=verifyPost    POST: check OTP code
 *   ?page=twoFactor&action=recovery      Recovery code input during login
 *   ?page=twoFactor&action=recoveryPost  POST: verify recovery code
 */
class TwoFactorController extends Controller {

    protected $allowedActions = [
        'setup', 'enable', 'disable',
        'verify', 'verifyPost',
        'recovery', 'recoveryPost'
    ];

    /**
     * Show 2FA setup page with QR code.
     * Requires authenticated user.
     */
    public function setup() {
        $this->requireAuth();

        $user = Session::get('user');
        $userId = $user['id'];
        $email = $user['email'] ?? $user['username'];

        $isEnabled = TwoFactorService::isEnabled($userId);

        // Generate a new secret for setup
        $secret = TwoFactorService::generateSecret();
        Session::set('twofa_setup_secret', $secret);

        $qrUrl = TwoFactorService::getQrCodeUrl($secret, $email);
        $otpAuthUrl = TwoFactorService::getOtpAuthUrl($secret, $email);

        $this->view('twoFactor.setup', [
            'pageTitle' => 'Two-Factor Authentication',
            'secret' => $secret,
            'qrUrl' => $qrUrl,
            'otpAuthUrl' => $otpAuthUrl,
            'isEnabled' => $isEnabled,
        ]);
    }

    /**
     * POST: Enable 2FA after verifying the first OTP code.
     */
    public function enable() {
        $this->requireAuth();
        $this->requirePost();

        $user = Session::get('user');
        $userId = $user['id'];
        $code = trim($_POST['otp_code'] ?? '');
        $secret = Session::get('twofa_setup_secret');

        if (!$secret) {
            Session::setFlash('error', 'Setup session expired. Please try again.');
            $this->redirect('index.php?page=twoFactor&action=setup');
            return;
        }

        // Verify the code against the setup secret
        if (!TwoFactorService::verifyCode($secret, $code)) {
            Session::setFlash('error', 'Invalid verification code. Please try again.');
            $this->redirect('index.php?page=twoFactor&action=setup');
            return;
        }

        // Generate recovery codes
        $recovery = TwoFactorService::generateRecoveryCodes();

        // Enable 2FA
        TwoFactorService::enable($userId, $secret, $recovery['hashed']);

        // Send security alert
        try { EmailService::send2faAlert($userId, 'enabled'); } catch (\Exception $e) {}

        // Clear setup session
        Session::remove('twofa_setup_secret');

        // Show recovery codes (one-time display)
        $this->view('twoFactor.recoveryCodes', [
            'pageTitle' => '2FA Recovery Codes',
            'codes' => $recovery['plain'],
        ]);
    }

    /**
     * POST: Disable 2FA.
     */
    public function disable() {
        $this->requireAuth();
        $this->requirePost();

        $user = Session::get('user');
        $userId = $user['id'];
        $password = $_POST['password'] ?? '';

        // Require password confirmation to disable 2FA
        $db = Database::getInstance();
        $dbUser = $db->query("SELECT password FROM users WHERE id = ?", [$userId])->fetch(\PDO::FETCH_ASSOC);

        if (!$dbUser || !password_verify($password, $dbUser['password'])) {
            Session::setFlash('error', 'Incorrect password. 2FA was not disabled.');
            $this->redirect('index.php?page=twoFactor&action=setup');
            return;
        }

        TwoFactorService::disable($userId);

        try { EmailService::send2faAlert($userId, 'disabled'); } catch (\Exception $e) {}

        Session::setFlash('success', 'Two-factor authentication has been disabled.');
        $this->redirect('index.php?page=twoFactor&action=setup');
    }

    /**
     * Show OTP verification form during login.
     */
    public function verify() {
        // User must have completed username/password but not yet full auth
        if (!Session::get('twofa_pending_user_id')) {
            $this->redirect('index.php?page=login');
            return;
        }

        $this->view('twoFactor.verify', [
            'pageTitle' => 'Enter Verification Code',
        ]);
    }

    /**
     * POST: Verify OTP code during login.
     */
    public function verifyPost() {
        $userId = Session::get('twofa_pending_user_id');
        if (!$userId) {
            $this->redirect('?page=login');
            return;
        }

        $code = trim($_POST['otp_code'] ?? '');
        $secret = TwoFactorService::getSecret($userId);

        if (!$secret || !TwoFactorService::verifyCode($secret, $code)) {
            Session::setFlash('error', 'Invalid verification code. Please try again.');
            Logger::security('2FA verification failed', ['user_id' => $userId]);
            $this->redirect('index.php?page=twoFactor&action=verify');
            return;
        }

        // 2FA passed — complete the login
        $this->completeLogin($userId);
    }

    /**
     * Show recovery code input form.
     */
    public function recovery() {
        if (!Session::get('twofa_pending_user_id')) {
            $this->redirect('index.php?page=login');
            return;
        }

        $this->view('twoFactor.recovery', [
            'pageTitle' => 'Recovery Code',
        ]);
    }

    /**
     * POST: Verify recovery code during login.
     */
    public function recoveryPost() {
        $userId = Session::get('twofa_pending_user_id');
        if (!$userId) {
            $this->redirect('index.php?page=login');
            return;
        }

        $code = trim($_POST['recovery_code'] ?? '');

        if (!TwoFactorService::verifyRecoveryCode($userId, $code)) {
            Session::setFlash('error', 'Invalid recovery code.');
            Logger::security('2FA recovery code failed', ['user_id' => $userId]);
            $this->redirect('index.php?page=twoFactor&action=recovery');
            return;
        }

        Logger::security('2FA recovery code used', ['user_id' => $userId]);

        // Recovery code valid — complete login
        $this->completeLogin($userId);
    }

    // ─── Internal ────────────────────────────────────────

    /**
     * Complete the login after 2FA verification.
     */
    private function completeLogin(int $userId): void {
        $db = Database::getInstance();
        $user = $db->query(
            "SELECT u.*, c.name AS company_name, c.db_name AS company_db
             FROM users u
             LEFT JOIN companies c ON u.company_id = c.id
             WHERE u.id = ?",
            [$userId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            $this->redirect('index.php?page=login');
            return;
        }

        // Clear pending state
        Session::remove('twofa_pending_user_id');

        // Set full session
        session_regenerate_id(true);
        Session::set('user', $user);

        Logger::info('Login completed (2FA verified)', ['user_id' => $userId]);

        $this->redirect('index.php?page=dashboard');
    }

    private function requirePost(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
    }
}
