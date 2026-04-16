import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()

# 1. Check for Role/Permission related files
print("="*60)
print("1. ROLE/PERMISSION FILES")
print("="*60)
for d in ['controllers', 'models', 'middleware', 'services', 'helpers']:
    try:
        files = sftp.listdir(f'/var/www/tsalegacy/{d}')
        role_files = [f for f in files if any(k in f.lower() for k in ['role', 'perm', 'auth', 'acl', 'access', 'guard'])]
        if role_files:
            print(f"  {d}/: {role_files}")
    except: pass

# 2. Check database for roles table
print("\n" + "="*60)
print("2. DATABASE TABLES (role/permission related)")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "SHOW TABLES LIKE '%role%'; SHOW TABLES LIKE '%perm%'; SHOW TABLES LIKE '%user%';"
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 3. Check users table structure
print("="*60)
print("3. USERS TABLE STRUCTURE")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "DESCRIBE users;"
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 4. Check roles table structure if exists
print("="*60)
print("4. ROLES TABLE STRUCTURE (if exists)")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "DESCRIBE roles;" 2>/dev/null || echo "No roles table found"
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 5. Check permissions table
print("="*60)
print("5. PERMISSIONS TABLE (if exists)")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "DESCRIBE permissions;" 2>/dev/null || echo "No permissions table"
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "DESCRIBE role_permissions;" 2>/dev/null || echo "No role_permissions table"
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "DESCRIBE user_roles;" 2>/dev/null || echo "No user_roles table"
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 6. Check existing roles data
print("="*60)
print("6. EXISTING ROLES DATA")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "SELECT * FROM roles LIMIT 20;" 2>/dev/null || echo "No roles data"
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 7. Check existing users and their roles
print("="*60)
print("7. USERS WITH ROLES")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "SELECT id, name, email, role, role_id, company_id, is_super_admin FROM users LIMIT 20;" 2>/dev/null || echo "Checking alt columns..."
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "SELECT id, name, email, company_id FROM users LIMIT 10;" 2>/dev/null
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 8. Check how requirePermission works
print("="*60)
print("8. PERMISSION CHECK MECHANISM (grep)")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("grep -rn 'requirePermission\\|checkPermission\\|hasPermission\\|isAdmin\\|isSuperAdmin\\|role' /var/www/tsalegacy/controllers/BaseController.php 2>/dev/null || echo 'No BaseController'")
print(stdout.read().decode('utf-8', errors='replace'))

stdin, stdout, stderr = ssh.exec_command("grep -rn 'requirePermission\\|checkPermission\\|hasPermission\\|isAdmin\\|isSuperAdmin' /var/www/tsalegacy/middleware/ 2>/dev/null || echo 'No middleware dir'")
print(stdout.read().decode('utf-8', errors='replace'))

sftp.close()
ssh.close()
