<?php
/**
 * HomeController – Public Landing Page
 * Serves the marketing homepage at root URL for unauthenticated visitors.
 */
class HomeController extends Controller {
    protected $allowedActions = ['index'];

    public function index() {
        // Already logged-in users → redirect to appropriate dashboard
        if (Session::isLoggedIn()) {
            if (Session::isTwoFactorPending()) {
                $this->redirect('index.php?page=twoFactor&action=verify');
                return;
            }
            if (Session::isSuperAdmin() && Tenant::id() === null) {
                $this->redirect('index.php?page=platform&action=dashboard');
                return;
            }
            $this->redirect('index.php?page=dashboard');
            return;
        }

        $this->renderPartial('public.home', []);
    }
}
