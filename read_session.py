import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

# Find Session.php
print("--- FINDING Session.php ---")
stdin, stdout, stderr = ssh.exec_command("find /var/www/tsalegacy -name 'Session.php' -type f")
print(stdout.read().decode('utf-8', errors='replace'))

# Read it
stdin, stdout, stderr = ssh.exec_command("grep -n 'function.*Permission\\|function.*Role\\|function.*Admin\\|function.*perm\\|function.*role\\|function.*login' /var/www/tsalegacy/core/Session.php 2>/dev/null || grep -rn 'function.*Permission\\|function.*Admin' /var/www/tsalegacy/core/ /var/www/tsalegacy/helpers/ 2>/dev/null")
print(stdout.read().decode('utf-8', errors='replace'))

# Read the PERMISSIONS constant from RbacMiddleware top section
sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/middleware/RbacMiddleware.php', 'r') as f:
    c = f.read().decode('utf-8')
    start = c.find('const PERMISSIONS')
    end = c.find('];', start) + 2
    print("="*60)
    print("PERMISSIONS MAP:")
    print("="*60)
    print(c[start:end])

sftp.close()
ssh.close()
