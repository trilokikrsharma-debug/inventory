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

print("=== CHECKING DATABASE SCHEMA ===")
# Getting schema from mysql (Assuming credentials are in .env)
# The application uses a central DB. Let's inspect customers and suppliers tables.
db_cmd = """
source /var/www/tsalegacy/.env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "DESCRIBE customers; DESCRIBE suppliers; DESCRIBE company_settings;"
"""
run(ssh, db_cmd)

print("\n=== CHECKING CUSTOMER MODEL (Validation & Fields) ===")
run(ssh, "cat /var/www/tsalegacy/models/CustomerModel.php | grep -iE 'gst|state'")

print("\n=== CHECKING CUSTOMER CREATION VIEW ===")
run(ssh, "cat /var/www/tsalegacy/views/customers/create.php | grep -iE 'gst|state'")

ssh.close()
