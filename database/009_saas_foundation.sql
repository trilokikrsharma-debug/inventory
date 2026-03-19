-- 1. Create SaaS Plans Table
CREATE TABLE saas_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL, -- Starter, Professional, Enterprise
    razorpay_plan_id VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    billing_cycle VARCHAR(20) DEFAULT 'monthly',
    max_users INT DEFAULT 1,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Plans
INSERT INTO saas_plans (name, price, max_users, features) VALUES
('Starter', 999.00, 2, '{"inventory": true, "invoicing": true, "api": false}'),
('Professional', 2499.00, 5, '{"inventory": true, "invoicing": true, "api": true, "crm": true}'),
('Enterprise', 4999.00, 999, '{"inventory": true, "invoicing": true, "api": true, "crm": true, "hr": true}');

-- 2. Enhance Companies Table (Replacing tenants)
ALTER TABLE companies
ADD COLUMN subdomain VARCHAR(100) UNIQUE AFTER name,
ADD COLUMN saas_plan_id INT DEFAULT 1 AFTER subdomain,
ADD COLUMN subscription_status VARCHAR(20) DEFAULT 'trial' AFTER saas_plan_id,
ADD COLUMN trial_ends_at TIMESTAMP NULL AFTER subscription_status,
ADD FOREIGN KEY (saas_plan_id) REFERENCES saas_plans(id);

-- 3. Create Tenant Subscriptions Table (Razorpay Tracking)
CREATE TABLE tenant_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    razorpay_subscription_id VARCHAR(100) NOT NULL,
    razorpay_customer_id VARCHAR(100),
    plan_id INT NOT NULL,
    status VARCHAR(50) NOT NULL, -- created, active, authenticated, halted, cancelled
    current_start TIMESTAMP NULL,
    current_end TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (plan_id) REFERENCES saas_plans(id)
);

-- 4. Create Billing History Table
CREATE TABLE tenant_billing_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    razorpay_payment_id VARCHAR(100),
    razorpay_invoice_id VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'INR',
    status VARCHAR(50),
    billing_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdf_url VARCHAR(255),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
