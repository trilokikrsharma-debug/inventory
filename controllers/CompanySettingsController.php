<?php
/**
 * Company Settings Controller
 * 
 * Manages per-company settings (business name, GST, logo, etc.)
 * This extends the existing settings functionality with company context.
 */
class CompanySettingsController extends Controller {
    protected $allowedActions = ['index'];

    public function index() {
        $this->requirePermission('settings.manage');
        
        $company = Tenant::company();
        $settings = (new SettingsModel())->getSettings();

        if ($this->isPost()) {
            if ($this->demoGuard()) return;
            $this->validateCSRF();

            $data = [
                'company_name'    => $this->sanitize($this->post('company_name')),
                'company_email'   => $this->sanitize($this->post('company_email')),
                'company_phone'   => $this->sanitize($this->post('company_phone')),
                'company_address' => $this->sanitize($this->post('company_address')),
                'company_city'    => $this->sanitize($this->post('company_city')),
                'company_state'   => $this->sanitize($this->post('company_state')),
                'company_zip'     => $this->sanitize($this->post('company_zip')),
                'company_country' => $this->sanitize($this->post('company_country')),
                'company_website' => $this->sanitize($this->post('company_website')),
                'tax_number'      => $this->sanitize($this->post('tax_number')),
            ];

            if (!empty($_FILES['company_logo']['name'])) {
                $r = Helper::uploadFile($_FILES['company_logo'], 'logo', ALLOWED_IMAGE_TYPES);
                if ($r['success']) $data['company_logo'] = $r['filepath'];
            }

            (new SettingsModel())->updateSettings($data);

            // Also update company name in companies table
            if (!empty($data['company_name'])) {
                Database::getInstance()->query(
                    "UPDATE companies SET name = ? WHERE id = ?",
                    [$data['company_name'], Tenant::id()]
                );
            }

            $this->logActivity('Updated company settings', 'company', Tenant::id());
            $this->setFlash('success', 'Company settings updated.');
            $this->redirect('index.php?page=company');
        }

        $this->view('company.settings', [
            'pageTitle' => 'Company Settings',
            'company'   => $company,
            'settings'  => $settings,
        ]);
    }
}
