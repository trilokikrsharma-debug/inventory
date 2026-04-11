<?php
/**
 * Demo Login Controller - One-click demo access.
 */
class DemoLoginController extends Controller {
    protected $allowedActions = ['index'];
    private ?DemoLoginService $demoLoginService = null;

    public function index() {
        try {
            $resolved = $this->service()->resolveDemoSession();
            $company = $resolved['company'];
            $user = $resolved['user'];
            $companyId = (int)$company['id'];

            session_regenerate_id(true);
            CSRF::rotateToken();
            Session::initFingerprint();
            Session::clearPermissionCache();
            Session::set('user', $user);
            Tenant::set($companyId, $company);
            Session::setFlash('info', "Welcome to Demo Mode. Explore freely, demo data may reset.");
            header("Location: " . APP_URL . "/index.php?page=dashboard");
            exit;
        } catch (\Throwable $e) {
            error_log('[DEMO_LOGIN] Error: ' . $e->getMessage());
            Session::setFlash('error', 'Demo mode is temporarily unavailable.');
            header("Location: " . APP_URL . "/index.php?page=login");
            exit;
        }
    }

    private function service(): DemoLoginService {
        if ($this->demoLoginService === null) {
            $this->demoLoginService = new DemoLoginService();
        }

        return $this->demoLoginService;
    }
}
