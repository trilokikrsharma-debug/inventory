<?php
class SettingsController extends Controller {

    protected $allowedActions = ['index'];
    private ?SettingsWorkflowService $settingsWorkflowService = null;

    public function index() {
        $this->requirePermission('settings.manage');
        $settings = (new SettingsModel())->getSettings();
        $activeTab = (string)$this->get('tab', 'company');
        if ($this->isPost()) {
            $this->validateCSRF();

            $validation = Validator::make($_POST, [
                'company_name' => 'nullable|string|max:150',
                'company_email' => 'nullable|email|max:255',
                'company_phone' => 'nullable|string|max:30',
                'company_address' => 'nullable|string|max:500',
                'company_city' => 'nullable|string|max:100',
                'company_state' => 'nullable|string|max:100',
                'company_zip' => 'nullable|string|max:20',
                'company_country' => 'nullable|string|max:100',
                'company_website' => 'nullable|string|max:255',
                'tax_number' => 'nullable|string|max:100',
                'tax_number_nongst' => 'nullable|string|max:100',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'tax_rate_nongst' => 'nullable|numeric|min:0|max:100',
                'currency_symbol' => 'nullable|string|max:10',
                'currency_code' => 'nullable|string|max:10',
                'date_format' => 'nullable|string|max:20',
                'timezone' => 'nullable|string|max:50',
                'low_stock_threshold' => 'nullable|integer|min:0|max:100000',
                'invoice_prefix' => 'nullable|string|max:20',
                'purchase_prefix' => 'nullable|string|max:20',
                'payment_prefix' => 'nullable|string|max:20',
                'receipt_prefix' => 'nullable|string|max:20',
                'invoice_title' => 'nullable|string|max:100',
                'invoice_subtitle' => 'nullable|string|max:100',
                'purchase_invoice_title' => 'nullable|string|max:100',
                'invoice_footer_text' => 'nullable|string|max:255',
                'invoice_terms' => 'nullable|string|max:1000',
                'invoice_bank_details' => 'nullable|string|max:1000',
                'invoice_signature_label' => 'nullable|string|max:100',
                'invoice_notes_label' => 'nullable|string|max:100',
                'auto_round_off_rupee' => 'nullable|boolean',
                'show_hsn_on_invoice' => 'nullable|boolean',
                'invoice_show_logo' => 'nullable|boolean',
                'invoice_show_signature' => 'nullable|boolean',
                'invoice_show_seal' => 'nullable|boolean',
                'invoice_show_payment_status' => 'nullable|boolean',
            ]);
            if ($validation->fails()) {
                $this->setFlash('error', $validation->firstError());
                $this->redirect('index.php?page=settings&tab=' . urlencode($activeTab));
                return;
            }

            $uploadedPaths = [];

            if (!empty($_FILES['company_logo']['name'])) {
                $r = Helper::uploadFile($_FILES['company_logo'], 'logo', ALLOWED_IMAGE_TYPES);
                if ($r['success']) $uploadedPaths['company_logo'] = $r['filepath'];
            }
            if (!empty($_FILES['invoice_signature_image']['name'])) {
                $r = Helper::uploadFile($_FILES['invoice_signature_image'], 'signature', ALLOWED_IMAGE_TYPES);
                if ($r['success']) $uploadedPaths['invoice_signature_image'] = $r['filepath'];
            }
            if (!empty($_FILES['invoice_seal_image']['name'])) {
                $r = Helper::uploadFile($_FILES['invoice_seal_image'], 'seal', ALLOWED_IMAGE_TYPES);
                if ($r['success']) $uploadedPaths['invoice_seal_image'] = $r['filepath'];
            }

            $data = $this->workflowService()->buildPayload($this->post(), $uploadedPaths);
            (new SettingsModel())->updateSettings($data);
            $this->workflowService()->flushSettingCaches();
            $this->logActivity('Updated system settings', 'settings', null, $this->workflowService()->summarizeChanges($settings, $data));

            $this->setFlash('success', 'Settings updated successfully.');
            $this->redirect('index.php?page=settings&tab=' . urlencode($activeTab));
        }
        $this->view('settings.index', ['pageTitle' => 'Settings', 'settings' => $settings]);
    }

    private function workflowService(): SettingsWorkflowService {
        if ($this->settingsWorkflowService === null) {
            $this->settingsWorkflowService = new SettingsWorkflowService();
        }

        return $this->settingsWorkflowService;
    }
}
