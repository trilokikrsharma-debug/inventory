import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

# 1. Check if export_gstr1 is in RBAC map
print("="*60)
print("1. IS export_gstr1 IN RBAC MAP?")
stdin, stdout, stderr = ssh.exec_command("grep -n 'export_gstr1\\|tax_summary' /var/www/tsalegacy/middleware/RbacMiddleware.php")
out = stdout.read().decode('utf-8', errors='replace')
print(out if out.strip() else "NOT FOUND - Missing from RBAC!")

# 2. Check role views
print("="*60)
print("2. ROLE VIEWS")
sftp = ssh.open_sftp()
try:
    views = sftp.listdir('/var/www/tsalegacy/views/roles')
    print(views)
except: print("No views/roles directory")

# 3. Check users table for current users and their role_ids
print("="*60)
print("3. USERS & ROLE ASSIGNMENTS")
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "
SELECT u.id, u.full_name, u.email, u.role, u.role_id, u.is_super_admin, u.company_id, r.display_name as role_display 
FROM users u 
LEFT JOIN roles r ON u.role_id = r.id 
WHERE u.deleted_at IS NULL 
ORDER BY u.id;
"
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 4. Check permission data for each role
print("="*60)
print("4. PERMISSION COUNTS PER ROLE")
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "
SELECT r.id, r.name, r.display_name, COUNT(rp.permission_id) as perm_count
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id
ORDER BY r.id;
"
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 5. All permissions in DB
print("="*60)
print("5. ALL PERMISSIONS RECORDS")
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "SELECT * FROM permissions ORDER BY module, id;"
""")
print(stdout.read().decode('utf-8', errors='replace'))

sftp.close()
ssh.close()
