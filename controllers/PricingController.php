<?php
/**
 * Pricing Controller — Public Pricing Page
 */
class PricingController extends Controller {
    protected $allowedActions = ['index'];

    public function index() {
        $plans = [];
        try {
            $plans = (new SaaSPlan())->listForCheckout();
        } catch (\Throwable $e) {
            Logger::error('Failed to load pricing plans', ['error' => $e->getMessage()]);
        }

        $this->renderPartial('public.pricing', [
            'pageTitle' => 'Pricing',
            'plans' => $plans,
        ]);
    }
}
