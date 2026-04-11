<?php
/**
 * Tenant Onboarding Controller
 *
 * Public API endpoint for creating a tenant company + owner user.
 */
class TenantOnboardingController extends Controller {
    protected $allowedActions = ['index', 'register'];
    private ?TenantOnboardingService $tenantOnboardingService = null;
    public function index() {
        $this->register();
    }

    public function register() {
        header('Content-Type: application/json');

        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::attempt('register_ip:' . $ip, 5, 3600)) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many registration attempts. Please try again in an hour.']);
            return;
        }
        if (!RateLimiter::attempt('register_global', 50, 3600)) {
            http_response_code(429);
            echo json_encode(['error' => 'Registration limit reached. Please try again later.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        try {
            $normalized = $this->service()->validateRegistrationInput($input);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }

        try {
            $this->service()->ensureAvailability($normalized);
        } catch (\RuntimeException $e) {
            http_response_code($e->getCode() === 409 ? 409 : 400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }

        try {
            $result = $this->service()->registerTenant($normalized);
            echo json_encode([
                'success' => true,
                'message' => 'Company registered successfully. You can now log in.',
                'tenant_id' => $result['tenant_id'],
                'subdomain' => $result['subdomain'],
                'username' => $result['username'],
            ]);
        } catch (\Throwable $e) {
            error_log('[Onboarding] Failed to register tenant: ' . $e->getMessage());

            $error = 'An internal error occurred during registration.';
            if ($e instanceof \RuntimeException && stripos($e->getMessage(), 'referral') !== false) {
                $error = $e->getMessage();
            } elseif (stripos($e->getMessage(), 'duplicate') !== false && stripos($e->getMessage(), 'email') !== false) {
                $error = 'This email is already registered.';
            } elseif (stripos($e->getMessage(), 'duplicate') !== false && stripos($e->getMessage(), 'subdomain') !== false) {
                $error = 'Subdomain is already taken.';
            }

            http_response_code(500);
            echo json_encode(['error' => $error]);
        }
    }

    private function service(): TenantOnboardingService {
        if ($this->tenantOnboardingService === null) {
            $this->tenantOnboardingService = new TenantOnboardingService();
        }

        return $this->tenantOnboardingService;
    }
}
