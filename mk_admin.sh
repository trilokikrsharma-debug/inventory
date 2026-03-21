#!/bin/bash
# Generate hash
HASH=$(php8.2 -r "echo password_hash('admin123', PASSWORD_BCRYPT);")
echo "Hash: $HASH"

# Get superadmin role id or create it
mysql -u root invenbill -e "INSERT IGNORE INTO roles (name, display_name, is_super_admin) VALUES ('superadmin', 'Super Admin', 1);"
ROLE_ID=$(mysql -u root invenbill -sN -e "SELECT id FROM roles WHERE is_super_admin=1 LIMIT 1;")
echo "Role ID: $ROLE_ID"

# Delete old failed superadmin if any
mysql -u root invenbill -e "DELETE FROM users WHERE username='superadmin' OR email='superadmin@tsalegacy.shop';" 2>/dev/null

# Insert fresh superadmin
mysql -u root invenbill -e "INSERT INTO users (username, email, password, is_super_admin, role_id, is_active, created_at) VALUES ('superadmin', 'superadmin@tsalegacy.shop', '$HASH', 1, $ROLE_ID, 1, NOW());"
echo "Insert done: $?"

# Verify
mysql -u root invenbill -e "SELECT id, username, email, is_super_admin, is_active FROM users WHERE is_super_admin=1;"
