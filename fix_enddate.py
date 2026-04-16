import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()

# Fix $endDate -> $toDate in the GSTR buttons
with sftp.open('/var/www/tsalegacy/views/reports/tax_summary.php', 'r') as f:
    ts = f.read().decode('utf-8')

ts = ts.replace('$endDate', '$toDate')

with sftp.open('/var/www/tsalegacy/views/reports/tax_summary.php', 'w') as f:
    f.write(ts.encode('utf-8'))

print("Fixed $endDate -> $toDate in tax_summary.php")

# Validate syntax
stdin, stdout, stderr = ssh.exec_command("php -l /var/www/tsalegacy/views/reports/tax_summary.php")
print(stdout.read().decode('utf-8', errors='replace'))

sftp.close()
ssh.close()
