import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def run_cmd(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if err.strip() and 'Warning' not in err and 'Deprecated' not in err:
        print("ERR: ", err)
    return out

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

print("--- 1. CHECKING EXISTING REPORT VIEWS ---")
run_cmd(ssh, "ls -la /var/www/tsalegacy/views/reports/")

print("--- 2. CHECKING REPORT CONTROLLER ---")
run_cmd(ssh, "grep 'function' /var/www/tsalegacy/controllers/ReportController.php")

ssh.close()
