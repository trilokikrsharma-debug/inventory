<?php
/**
 * Pricing Controller — Public Pricing Page
 */
class PricingController extends Controller {
    protected $allowedActions = ['index'];

    public function index() {
        $this->renderPartial('public.pricing', ['pageTitle' => 'Pricing']);
    }
}
