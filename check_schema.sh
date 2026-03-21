#!/bin/bash
# Get full log without truncation issues
cd /var/www/inventory

echo "=== Companies columns ==="
mysql -u root invenbill -e "SHOW COLUMNS FROM companies;" 2>&1

echo ""
echo "=== company_settings columns ==="
mysql -u root invenbill -e "SHOW COLUMNS FROM company_settings;" 2>&1

echo ""
echo "=== All tables ==="
mysql -u root invenbill -e "SHOW TABLES;" 2>&1

echo ""
echo "=== Check is_demo column ==="
mysql -u root invenbill -e "SELECT COUNT(*) as has_is_demo FROM information_schema.columns WHERE table_schema='invenbill' AND table_name='companies' AND column_name='is_demo';" 2>&1

echo ""
echo "=== RateLimiter file ==="
find /var/www/inventory -name "*RateLimiter*" -o -name "*rate_limit*" 2>/dev/null
