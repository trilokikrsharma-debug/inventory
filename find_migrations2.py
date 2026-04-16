import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def run(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    if out.strip():
        print(out)

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

print("=== FINDING DATABASE SCHEMA FILES ===")
# Exclude uploads directly in the find command
run(ssh, "find /var/www/tsalegacy -path '/var/www/tsalegacy/uploads' -prune -o -type f -name '*.sql' -print")

ssh.close()
