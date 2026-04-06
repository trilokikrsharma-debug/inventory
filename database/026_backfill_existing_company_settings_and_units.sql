-- ============================================================================
-- 026: Backfill existing tenant settings + starter units
--
-- PURPOSE:
--   - Populate new company_settings fields for existing tenants without
--     overwriting explicit business choices
--   - Seed common inventory units for all existing tenants without duplicates
-- ============================================================================

-- Ensure every company has a settings row.
INSERT INTO company_settings (
    company_id,
    company_name,
    company_email,
    company_phone,
    company_address,
    company_city,
    company_state,
    company_country,
    currency_symbol,
    currency_code,
    date_format,
    timezone,
    invoice_title,
    invoice_signature_label,
    invoice_notes_label,
    invoice_show_logo,
    invoice_show_payment_status,
    show_paid_due_on_invoice,
    show_unit_on_invoice,
    show_discount_on_invoice,
    show_hsn_on_invoice,
    auto_round_off_rupee
)
SELECT
    c.id,
    c.name,
    '',
    '',
    '',
    '',
    '',
    'India',
    '₹',
    'INR',
    'd-m-Y',
    'Asia/Kolkata',
    'Invoice',
    'Authorised Signatory',
    'Notes',
    1,
    1,
    0,
    1,
    0,
    0,
    0
FROM companies c
LEFT JOIN company_settings cs ON cs.company_id = c.id
WHERE cs.id IS NULL;

-- Fill newly-added / nullable fields only when empty.
UPDATE company_settings
SET
    date_format = COALESCE(NULLIF(date_format, ''), 'd-m-Y'),
    timezone = COALESCE(NULLIF(timezone, ''), 'Asia/Kolkata'),
    invoice_title = COALESCE(NULLIF(invoice_title, ''), IF(IFNULL(enable_gst, 0) = 1, 'Tax Invoice', 'Invoice')),
    invoice_signature_label = COALESCE(NULLIF(invoice_signature_label, ''), 'Authorised Signatory'),
    invoice_notes_label = COALESCE(NULLIF(invoice_notes_label, ''), 'Notes'),
    invoice_show_logo = COALESCE(invoice_show_logo, 1),
    invoice_show_payment_status = COALESCE(invoice_show_payment_status, 1),
    show_paid_due_on_invoice = COALESCE(show_paid_due_on_invoice, 0),
    show_unit_on_invoice = COALESCE(show_unit_on_invoice, 1),
    show_discount_on_invoice = COALESCE(show_discount_on_invoice, 0),
    show_hsn_on_invoice = COALESCE(show_hsn_on_invoice, 0),
    auto_round_off_rupee = COALESCE(auto_round_off_rupee, 0);

-- Common starter units for all tenants.
CREATE TEMPORARY TABLE tmp_default_units (
    name VARCHAR(100) NOT NULL,
    short_name VARCHAR(20) NOT NULL
);

INSERT INTO tmp_default_units (name, short_name) VALUES
    ('Pieces', 'pcs'),
    ('Kilograms', 'kg'),
    ('Grams', 'g'),
    ('Meters', 'mtr'),
    ('Centimeters', 'cm'),
    ('Millimeters', 'mm'),
    ('Liters', 'ltr'),
    ('Milliliters', 'ml'),
    ('Boxes', 'box'),
    ('Packets', 'pkt'),
    ('Packs', 'pac'),
    ('Bags', 'bag'),
    ('Bottles', 'btl'),
    ('Cartons', 'ctn'),
    ('Dozens', 'doz'),
    ('Pairs', 'pair'),
    ('Sets', 'set'),
    ('Rolls', 'roll'),
    ('Sheets', 'sheet'),
    ('Units', 'unit');

INSERT INTO units (company_id, name, short_name, is_active)
SELECT
    c.id,
    u.name,
    u.short_name,
    1
FROM companies c
CROSS JOIN tmp_default_units u
LEFT JOIN units existing
    ON existing.company_id = c.id
   AND (
        LOWER(TRIM(existing.short_name)) = LOWER(TRIM(u.short_name))
        OR LOWER(TRIM(existing.name)) = LOWER(TRIM(u.name))
   )
WHERE existing.id IS NULL;

DROP TEMPORARY TABLE IF EXISTS tmp_default_units;
