<?php
class SettingsWorkflowService {
    public function buildPayload(array $input, array $uploadedPaths = []): array {
        $enableGst = !empty($input['enable_gst']) ? 1 : 0;
        $enableTax = !empty($input['enable_tax']) ? 1 : 0;

        // Indian business context: GST ON = Tax ON, GST OFF = Tax OFF
        // There's no practical "tax without GST" in Indian billing
        if ($enableGst) {
            $enableTax = 1; // GST always implies tax
        }
        if (!$enableGst) {
            $enableTax = 0; // No GST = no tax for Indian businesses
        }

        $taxRate = (float)($input['tax_rate'] ?? 18);
        if (!$enableGst && array_key_exists('tax_rate_nongst', $input)) {
            $taxRate = (float)($input['tax_rate_nongst'] ?? 0);
        }
        if (!$enableTax) {
            $taxRate = 0.0;
        }

        $taxNumber = $enableGst
            ? $this->sanitize($input['tax_number'] ?? null)
            : $this->sanitize($input['tax_number_nongst'] ?? ($input['tax_number'] ?? null));

        $payload = [
            'company_name' => $this->sanitize($input['company_name'] ?? null),
            'company_email' => $this->sanitize($input['company_email'] ?? null),
            'company_phone' => $this->sanitize($input['company_phone'] ?? null),
            'company_address' => $this->sanitize($input['company_address'] ?? null),
            'company_city' => $this->sanitize($input['company_city'] ?? null),
            'company_state' => $this->sanitize($input['company_state'] ?? null),
            'company_zip' => $this->sanitize($input['company_zip'] ?? null),
            'company_country' => $this->sanitize($input['company_country'] ?? null),
            'company_website' => $this->sanitize($input['company_website'] ?? null),
            'tax_number' => $taxNumber,
            'enable_tax' => $enableTax,
            'enable_gst' => $enableGst,
            'tax_rate' => $taxRate,
            'currency_symbol' => $this->sanitize($input['currency_symbol'] ?? null),
            'currency_code' => $this->sanitize($input['currency_code'] ?? null),
            'date_format' => $this->sanitize($input['date_format'] ?? 'd-m-Y'),
            'timezone' => $this->sanitize($input['timezone'] ?? 'Asia/Kolkata'),
            'low_stock_threshold' => (int)($input['low_stock_threshold'] ?? 10),
            'invoice_prefix' => $this->sanitize($input['invoice_prefix'] ?? null),
            'purchase_prefix' => $this->sanitize($input['purchase_prefix'] ?? null),
            'payment_prefix' => $this->sanitize($input['payment_prefix'] ?? null),
            'receipt_prefix' => $this->sanitize($input['receipt_prefix'] ?? null),
            'invoice_title' => $this->resolveInvoiceTitle($input['invoice_title'] ?? '', $enableGst, $enableTax),
            'invoice_subtitle' => $this->sanitize($input['invoice_subtitle'] ?? null),
            'purchase_invoice_title' => $this->sanitize($input['purchase_invoice_title'] ?? 'Purchase Bill'),
            'invoice_footer_text' => $this->sanitize($input['invoice_footer_text'] ?? null),
            'invoice_terms' => $this->sanitize($input['invoice_terms'] ?? null),
            'invoice_bank_details' => $this->sanitize($input['invoice_bank_details'] ?? null),
            'invoice_signature_label' => $this->sanitize($input['invoice_signature_label'] ?? 'Authorised Signatory'),
            'invoice_notes_label' => $this->sanitize($input['invoice_notes_label'] ?? 'Notes'),
            'invoice_show_logo' => !empty($input['invoice_show_logo']) ? 1 : 0,
            'invoice_show_signature' => !empty($input['invoice_show_signature']) ? 1 : 0,
            'invoice_show_seal' => !empty($input['invoice_show_seal']) ? 1 : 0,
            'invoice_show_payment_status' => !empty($input['invoice_show_payment_status']) ? 1 : 0,
            'show_paid_due_on_invoice' => !empty($input['show_paid_due_on_invoice']) ? 1 : 0,
            'show_unit_on_invoice' => !empty($input['show_unit_on_invoice']) ? 1 : 0,
            'show_discount_on_invoice' => !empty($input['show_discount_on_invoice']) ? 1 : 0,
            'show_hsn_on_invoice' => !empty($input['show_hsn_on_invoice']) ? 1 : 0,
            'auto_round_off_rupee' => !empty($input['auto_round_off_rupee']) ? 1 : 0,
            'theme_color' => (string)($input['theme_color'] ?? '#4e73df'),
        ];

        return array_merge($payload, $uploadedPaths);
    }

    public function flushSettingCaches(): void {
        $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
        Cache::delete($tenantPrefix . 'sidebar_lowstock');
        Cache::flushPrefix($tenantPrefix . 'dash_');
        Cache::flushPrefix($tenantPrefix . 'report_');
        Cache::flushPrefix($tenantPrefix . 'products_');
    }

    public function summarizeChanges(array $existingSettings, array $payload): ?string {
        $changes = [];
        if (($existingSettings['enable_gst'] ?? 1) != ($payload['enable_gst'] ?? 1)) {
            $changes[] = 'GST: ' . (!empty($payload['enable_gst']) ? 'on' : 'off');
        }
        if (($existingSettings['enable_tax'] ?? 1) != ($payload['enable_tax'] ?? 1)) {
            $changes[] = 'Tax: ' . (!empty($payload['enable_tax']) ? 'on' : 'off');
        }
        if (($existingSettings['tax_rate'] ?? 18) != ($payload['tax_rate'] ?? 18)) {
            $changes[] = 'Tax rate: ' . ($payload['tax_rate'] ?? 0) . '%';
        }
        if (($existingSettings['invoice_prefix'] ?? '') !== ($payload['invoice_prefix'] ?? '')) {
            $changes[] = 'Invoice prefix changed';
        }
        if (($existingSettings['purchase_prefix'] ?? '') !== ($payload['purchase_prefix'] ?? '')) {
            $changes[] = 'Purchase prefix changed';
        }

        return !empty($changes) ? implode(', ', $changes) : null;
    }


    /**
     * Auto-resolve invoice title based on GST/Tax mode.
     * If user provides a custom title, use it. If it's a standard title or empty, auto-set.
     */
    private function resolveInvoiceTitle(string $userTitle, int $enableGst, int $enableTax): string {
        $title = $this->sanitize($userTitle);
        $standardTitles = ['', 'Tax Invoice', 'Invoice', 'Bill of Supply'];
        
        // If user has a custom title (not one of the standard ones), keep it
        if ($title !== '' && !in_array($title, $standardTitles, true)) {
            return $title;
        }
        
        // Auto-set based on mode
        if ($enableGst && $enableTax) {
            return 'Tax Invoice';
        }
        // GST OFF = No tax in India, plain invoice
        if (!$enableGst) {
            return 'Invoice';
        }
        return 'Invoice'; // No tax = plain invoice
    }

    private function sanitize($value): string {
        if ($value === null || is_array($value)) {
            return '';
        }

        $clean = Helper::decodeHtmlEntities((string)$value);
        $clean = strip_tags($clean);
        return trim($clean);
    }
}
