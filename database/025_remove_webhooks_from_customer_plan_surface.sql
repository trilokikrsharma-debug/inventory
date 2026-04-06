UPDATE saas_plans
SET
    features = JSON_OBJECT(
        'advanced_reports', true,
        'api', true,
        'audit_trail', true,
        'backup_restore', true,
        'basic_reports', true,
        'customer_management', true,
        'export_pdf', true,
        'inventory', true,
        'invoicing', true,
        'multi_user', true,
        'payment_tracking', true,
        'quotations', true,
        'sale_returns', true
    )
WHERE slug = 'enterprise';
