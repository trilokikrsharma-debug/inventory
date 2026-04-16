import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

# Check apache error log for the 500 error
print("--- APACHE ERROR LOG ---")
stdin, stdout, stderr = ssh.exec_command("tail -n 40 /var/log/apache2/error.log 2>/dev/null || tail -n 40 /var/log/httpd/error_log 2>/dev/null")
print(stdout.read().decode('utf-8', errors='replace'))

# Check the tax_summary.php view for the $endDate variable
sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/views/reports/tax_summary.php', 'r') as f:
    ts = f.read().decode('utf-8')
    lines = ts.split('\n')
    for i, line in enumerate(lines):
        if 'endDate' in line or 'fromDate' in line or 'toDate' in line or '$end' in line or '$from' in line or '$to' in line:
            print(f"Line {i+1}: {line.strip()}")

sftp.close()
ssh.close()
