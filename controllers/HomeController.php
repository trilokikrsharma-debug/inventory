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
                $this->redirect('twoFactor/verify');
                return;
            }
            if (Session::isSuperAdmin()) {
                $this->redirect('platform/dashboard');
                return;
            }
            $this->redirect('dashboard');
            return;
        }

        $plans = [];
        try {
            $plans = (new SaaSPlan())->listForCheckout();
        } catch (\Throwable $e) { }

        $this->renderPartial('public.home', [
            'plans' => $plans
        ]);
    }
}
