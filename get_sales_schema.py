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

print("=== CHECKING SALES TABLE SCHEMA ===")
# Getting schema from mysql
db_cmd = """
source /var/www/tsalegacy/.env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "DESCRIBE sales;"
"""
run(ssh, db_cmd)

print("\n=== GETTING SALES VIEW CUSTOMER DROPDOWN ===")
run(ssh, "sed -n '20,25p' /var/www/tsalegacy/views/sales/create.php")

ssh.close()
