#!/bin/bash
MYopts="-u invenbill_app -pInvenBillPass@2026 invenbill"

echo "=== Companies columns ==="
mysql $MYOPTS -e "SHOW COLUMNS FROM companies;" 2>&1 || mysql -u invenbill_app -pInvenBillPass@2026 invenbill -e "SHOW COLUMNS FROM companies;" 2>&1

echo ""
echo "=== company_settings columns ==="
mysql -u invenbill_app -pInvenBillPass@2026 invenbill -e "SHOW COLUMNS FROM company_settings;" 2>&1

echo ""
echo "=== All tables ==="
mysql -u invenbill_app -pInvenBillPass@2026 invenbill -e "SHOW TABLES;" 2>&1

echo ""
echo "=== is_demo column check ==="
mysql -u invenbill_app -pInvenBillPass@2026 invenbill -e "SELECT COUNT(*) as has_col FROM information_schema.columns WHERE table_schema='invenbill' AND table_name='companies' AND column_name='is_demo';" 2>&1

echo ""
echo "=== RateLimiter ==="
find /var/www/inventory -name "RateLimiter.php" 2>/dev/null
head -30 /var/www/inventory/core/RateLimiter.php 2>/dev/null || echo "Not found at core/RateLimiter.php"
