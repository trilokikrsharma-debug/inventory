import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/core/Session.php', 'r') as f:
    c = f.read().decode('utf-8')
    
    # Print lines around hasPermission, isSuperAdmin, loadPermissions
    lines = c.split('\n')
    for i, line in enumerate(lines):
        if any(k in line for k in ['function hasPermission', 'function isSuperAdmin', 'function loadPermissions', 'function clearPermissionCache', 'function isAdmin', 'function permissionCacheKey']):
            start = max(0, i-1)
            end = min(len(lines), i+30)
            print(f"\n{'='*60}")
            print(f"Line {i+1}: {line.strip()}")
            print('='*60)
            for j in range(start, end):
                print(f"{j+1}: {lines[j]}")

sftp.close()
ssh.close()
