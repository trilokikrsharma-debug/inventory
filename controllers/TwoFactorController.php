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
    private TwoFactorWorkflowService $workflowService;

    protected $allowedActions = [
        'setup', 'enable', 'disable',
        'verify', 'verifyPost',
        'recovery', 'recoveryPost'
    ];

    public function __construct() {
        $this->workflowService = new TwoFactorWorkflowService();
    }

    /**
     * Show 2FA setup page with QR code.
     * Requires authenticated user.
     */
    public function setup() {
        $this->requireAuth();

        $payload = $this->workflowService->buildSetupPayload(Session::get('user') ?? []);
        Session::set('twofa_setup_secret', $payload['secret']);
        CSRF::getToken();

        $this->view('twoFactor.setup', [
            'pageTitle' => 'Two-Factor Authentication',
            'secret' => $payload['secret'],
            'email' => $payload['email'],
            'otpAuthUrl' => $payload['otpAuthUrl'],
            'isEnabled' => $payload['isEnabled'],
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
        if (!Session::isTwoFactorPending()) {
            $this->redirect('index.php?page=login');
            return;
        }

        CSRF::getToken();
        $this->view('twoFactor.verify', [
            'pageTitle' => 'Enter Verification Code',
        ]);
    }

    /**
     * POST: Verify OTP code during login.
     */
    public function verifyPost() {
        $userId = (int)Session::get('twofa_pending_user_id');
        if ($userId <= 0 || !Session::isTwoFactorPending()) {
            $this->redirect('index.php?page=login');
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
        if (!Session::isTwoFactorPending()) {
            $this->redirect('index.php?page=login');
            return;
        }

        CSRF::getToken();
        $this->view('twoFactor.recovery', [
            'pageTitle' => 'Recovery Code',
        ]);
    }

    /**
     * POST: Verify recovery code during login.
     */
    public function recoveryPost() {
        $userId = (int)Session::get('twofa_pending_user_id');
        if ($userId <= 0 || !Session::isTwoFactorPending()) {
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
        $pendingUserId = (int)Session::get('twofa_pending_user_id');
        if ($pendingUserId <= 0 || $pendingUserId !== $userId) {
            $this->redirect('index.php?page=login');
            return;
        }

        $context = $this->workflowService->loadLoginContext($userId);
        if (!$context) {
            Session::remove('twofa_pending_user_id');
            Session::remove('twofa_pending_is_super_admin');
            Session::remove('twofa_pending_company_id');
            Session::remove('twofa_pending_company');
            Session::remove('user');
            Session::clearPermissionCache();
            Tenant::reset();
            Session::setFlash('error', 'Your login session expired. Please sign in again.');
            $this->redirect('index.php?page=login');
            return;
        }

        $rememberMe = (bool)Session::get('twofa_pending_remember_me', false);
        $this->finalizeLogin($context['user'], $context['company_id'], $context['company'], $context['is_super_admin'], $rememberMe);
    }

    private function requirePost(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
    }

    /**
     * Final login session rebuild used after 2FA succeeds.
     */
    private function finalizeLogin(array $user, int $companyId, ?array $company, bool $isSuperAdmin, bool $rememberMe = false): void {
        $sessionUser = $this->workflowService->sanitizeSessionUser($user, $isSuperAdmin);
        $sessionUser['twofa_pending'] = false;
        $sessionUser['twofa_verified'] = true;

        session_regenerate_id(true);
        CSRF::rotateToken();
        Session::initFingerprint();
        Session::clearPermissionCache();

        Session::remove('twofa_pending_user_id');
        Session::remove('twofa_pending_is_super_admin');
        Session::remove('twofa_pending_company_id');
        Session::remove('twofa_pending_company');
        Session::remove('twofa_pending_remember_me');

        Session::set('user', $sessionUser);
        if ($rememberMe) {
            RememberMeService::issueForUser($sessionUser);
        } else {
            RememberMeService::revokeCurrentToken();
        }

        if ($isSuperAdmin) {
            Tenant::reset();
        } elseif ($companyId > 0 && $company) {
            Tenant::set($companyId, $company);
        } else {
            Tenant::reset();
        }

        $this->logActivity('Login', 'auth', $sessionUser['id'],
            $isSuperAdmin ? 'Platform super-admin logged in after 2FA' : 'Tenant user logged in after 2FA');

        if ($isSuperAdmin) {
            $this->redirect('index.php?page=platform&action=dashboard');
        }

        $this->redirect('index.php?page=dashboard');
    }

}
