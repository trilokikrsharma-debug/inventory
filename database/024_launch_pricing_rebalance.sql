UPDATE saas_plans
SET
    name = 'Starter',
    slug = 'starter',
    description = 'Affordable daily billing for new businesses.',
    price = 299.00,
    offer_price = NULL,
    billing_type = 'monthly',
    billing_cycle = 'monthly',
    duration_days = 30,
    max_users = 3,
    max_products = 500,
    features = JSON_OBJECT(
        'audit_trail', true,
        'basic_reports', true,
        'customer_management', true,
        'export_pdf', true,
        'inventory', true,
        'invoicing', true,
        'payment_tracking', true
    ),
    is_featured = 0,
    sort_order = 1,
    status = 'active',
    is_active = 1
WHERE id = 1;

UPDATE saas_plans
SET
    name = 'Professional',
    slug = 'professional',
    description = 'Best value for growing teams that need more control.',
    price = 699.00,
    offer_price = NULL,
    billing_type = 'monthly',
    billing_cycle = 'monthly',
    duration_days = 30,
    max_users = 10,
    max_products = 5000,
    features = JSON_OBJECT(
        'advanced_reports', true,
        'audit_trail', true,
        'basic_reports', true,
        'customer_management', true,
        'export_pdf', true,
        'inventory', true,
        'invoicing', true,
        'multi_user', true,
        'payment_tracking', true,
        'quotations', true,
        'sale_returns', true
    ),
    is_featured = 1,
    sort_order = 2,
    status = 'active',
    is_active = 1
WHERE id = 2;

UPDATE saas_plans
SET
    name = 'Enterprise',
    slug = 'enterprise',
    description = 'High-volume operations with backups and integrations.',
    price = 1499.00,
    offer_price = NULL,
    billing_type = 'monthly',
    billing_cycle = 'monthly',
    duration_days = 30,
    max_users = 25,
    max_products = 20000,
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
    ),
    is_featured = 0,
    sort_order = 3,
    status = 'active',
    is_active = 1
WHERE id = 3;
