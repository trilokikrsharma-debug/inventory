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

print("=== CHECKING resolveSaleGstType() ===")
run(ssh, "sed -n '/private function resolveSaleGstType/,/^    }/p' /var/www/tsalegacy/services/SalesWorkflowService.php")

print("\n=== CHECKING IF FRONTEND HAS STATE DATA ===")
run(ssh, "sed -n '110,130p' /var/www/tsalegacy/views/sales/create.php")

print("\n=== CHECKING COMPONENT JAVASCRIPT FOR AUTO CALC ===")
run(ssh, "cat /var/www/tsalegacy/views/sales/create.php | grep -iE 'gstType|company_state|state'")

ssh.close()
