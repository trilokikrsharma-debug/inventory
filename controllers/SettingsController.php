<?php
class SettingsController extends Controller {

    protected $allowedActions = ['index'];

    public function index() {
        $this->requirePermission('settings.manage');
        $settings = (new SettingsModel())->getSettings();
        if ($this->isPost()) {
            $this->validateCSRF();

            $enableGst = $this->post('enable_gst', 0) ? 1 : 0;
            $enableTax = $this->post('enable_tax', 0) ? 1 : 0;

            // If non-GST, use the non-GST tax rate field if provided
            $taxRate = (float)$this->post('tax_rate', 18);
            if (!$enableGst && $this->post('tax_rate_nongst') !== null) {
                $taxRate = (float)$this->post('tax_rate_nongst', 0);
            }

            $data = [
                // Company info
                'company_name'    => $this->sanitize($this->post('company_name')),
                'company_email'   => $this->sanitize($this->post('company_email')),
                'company_phone'   => $this->sanitize($this->post('company_phone')),
                'company_address' => $this->sanitize($this->post('company_address')),
                'company_city'    => $this->sanitize($this->post('company_city')),
                'company_state'   => $this->sanitize($this->post('company_state')),
                'company_zip'     => $this->sanitize($this->post('company_zip')),
                'company_country' => $this->sanitize($this->post('company_country')),
                'company_website' => $this->sanitize($this->post('company_website')),

                // Tax & GST
                'tax_number'  => $this->sanitize($this->post('tax_number')),
                'enable_tax'  => $enableTax,
                'enable_gst'  => $enableGst,
                'tax_rate'    => $taxRate,

                // Currency
                'currency_symbol'     => $this->sanitize($this->post('currency_symbol')),
                'currency_code'       => $this->sanitize($this->post('currency_code')),
                'low_stock_threshold' => (int)$this->post('low_stock_threshold', 10),

                // Prefixes
                'invoice_prefix'  => $this->sanitize($this->post('invoice_prefix')),
                'purchase_prefix' => $this->sanitize($this->post('purchase_prefix')),
                'payment_prefix'  => $this->sanitize($this->post('payment_prefix')),
                'receipt_prefix'  => $this->sanitize($this->post('receipt_prefix')),

                // Invoice Customization
                'invoice_title'          => $this->sanitize($this->post('invoice_title', 'Tax Invoice')),
                'purchase_invoice_title' => $this->sanitize($this->post('purchase_invoice_title', 'Purchase Bill')),
                'invoice_footer_text'    => $this->sanitize($this->post('invoice_footer_text')),
                'invoice_terms'          => $this->sanitize($this->post('invoice_terms')),
                'invoice_bank_details'   => $this->sanitize($this->post('invoice_bank_details')),
                'invoice_signature_label'=> $this->sanitize($this->post('invoice_signature_label', 'Authorised Signatory')),
                'show_paid_due_on_invoice' => $this->post('show_paid_due_on_invoice', 0) ? 1 : 0,
                'show_unit_on_invoice' => $this->post('show_unit_on_invoice', 0) ? 1 : 0,
                'show_discount_on_invoice' => $this->post('show_discount_on_invoice', 0) ? 1 : 0,

                'theme_color' => $this->post('theme_color', '#4e73df'),
            ];

            if (!empty($_FILES['company_logo']['name'])) {
                $r = Helper::uploadFile($_FILES['company_logo'], 'logo', ALLOWED_IMAGE_TYPES);
                if ($r['success']) $data['company_logo'] = $r['filepath'];
            }

            (new SettingsModel())->updateSettings($data);
            // Keep sidebar/dashboard counters fresh after settings changes
            // (especially low_stock_threshold updates).
            $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
            Cache::delete($tenantPrefix . 'sidebar_lowstock');
            Cache::flushPrefix($tenantPrefix . 'dash_');

            // Log what changed at a high level (not every field)
            $changes = [];
            if (($settings['enable_gst'] ?? 1) != $enableGst) $changes[] = 'GST: ' . ($enableGst ? 'on' : 'off');
            if (($settings['enable_tax'] ?? 1) != $enableTax) $changes[] = 'Tax: ' . ($enableTax ? 'on' : 'off');
            if (($settings['tax_rate'] ?? 18) != $taxRate) $changes[] = 'Tax rate: ' . $taxRate . '%';
            if (($settings['invoice_prefix'] ?? '') !== ($data['invoice_prefix'] ?? '')) $changes[] = 'Invoice prefix changed';
            if (($settings['purchase_prefix'] ?? '') !== ($data['purchase_prefix'] ?? '')) $changes[] = 'Purchase prefix changed';
            $this->logActivity('Updated system settings', 'settings', null, !empty($changes) ? implode(', ', $changes) : null);

            $this->setFlash('success', 'Settings updated successfully.');
            $this->redirect('index.php?page=settings');
        }
        $this->view('settings.index', ['pageTitle' => 'Settings', 'settings' => $settings]);
    }
}
