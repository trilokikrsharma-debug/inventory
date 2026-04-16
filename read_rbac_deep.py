import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()

# 1. Read PERMISSIONS constant from RbacMiddleware
print("="*60)
print("1. PERMISSIONS MAP (from RbacMiddleware)")
print("="*60)
with sftp.open('/var/www/tsalegacy/middleware/RbacMiddleware.php', 'r') as f:
    c = f.read().decode('utf-8')
    # Extract the PERMISSIONS constant
    start = c.find('const PERMISSIONS')
    end = c.find('];', start) + 2
    print(c[start:end])

# 2. Read all permissions from DB
print("\n" + "="*60)
print("2. ALL PERMISSIONS IN DB")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "SELECT id, name, display_name, module FROM permissions ORDER BY module, id;"
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 3. Role-Permission mappings
print("="*60)
print("3. ROLE-PERMISSION MAPPINGS")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("""
cd /var/www/tsalegacy && source .env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "
SELECT r.name as role_name, GROUP_CONCAT(p.name ORDER BY p.module, p.name SEPARATOR ', ') as permissions
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
LEFT JOIN permissions p ON rp.permission_id = p.id
GROUP BY r.id, r.name
ORDER BY r.id;
"
""")
print(stdout.read().decode('utf-8', errors='replace'))

# 4. Read RoleController
print("="*60)
print("4. RoleController.php")
print("="*60)
with sftp.open('/var/www/tsalegacy/controllers/RoleController.php', 'r') as f:
    print(f.read().decode('utf-8'))

# 5. Read Session helper for role/permission methods
print("="*60)
print("5. Session class (role methods)")
print("="*60)
stdin, stdout, stderr = ssh.exec_command("grep -n 'function.*Permission\\|function.*Role\\|function.*Admin\\|function.*perm\\|function.*role' /var/www/tsalegacy/helpers/Session.php")
print(stdout.read().decode('utf-8', errors='replace'))

# 6. Read those methods from Session.php
with sftp.open('/var/www/tsalegacy/helpers/Session.php', 'r') as f:
    session_code = f.read().decode('utf-8')
    # Find hasPermission and isSuperAdmin
    for method in ['hasPermission', 'isSuperAdmin', 'loadPermissions', 'permissions']:
        idx = session_code.find(f'function {method}')
        if idx != -1:
            end_idx = session_code.find('\n    }', idx) + 6
            print(f"\n--- Session::{method} ---")
            print(session_code[idx:end_idx])

sftp.close()
ssh.close()
