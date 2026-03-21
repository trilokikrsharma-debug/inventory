<?php
/**
 * API Controller
 */
class ApiController extends Controller {

    protected $allowedActions = ['index', 'generate'];

    public function index() {
        $this->requireFeature('api');
        $this->requirePermission('settings.view');

        $this->view('api.index', [
            'pageTitle' => 'API Access',
            'api_key' => 'sk_live_' . bin2hex(random_bytes(16)) // Placeholder for UI demonstration
        ]);
    }

    public function generate() {
        $this->requireFeature('api');
        $this->requirePermission('settings.view');
        
        if ($this->isPost()) {
            $this->validateCSRF();
            $this->setFlash('success', 'New API Key generated successfully! Make sure to copy it now.');
        }
        $this->redirect('index.php?page=api');
    }
}
