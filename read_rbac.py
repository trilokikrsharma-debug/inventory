import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()

# 1. Read RbacMiddleware.php (the core enforcement)
print("="*60)
print("1. RbacMiddleware.php")
print("="*60)
with sftp.open('/var/www/tsalegacy/middleware/RbacMiddleware.php', 'r') as f:
    print(f.read().decode('utf-8'))

# 2. Read RolePermissionService.php
print("\n" + "="*60)
print("2. RolePermissionService.php")
print("="*60)
with sftp.open('/var/www/tsalegacy/services/RolePermissionService.php', 'r') as f:
    print(f.read().decode('utf-8'))

sftp.close()
ssh.close()
