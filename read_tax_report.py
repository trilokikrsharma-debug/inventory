import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
try:
    with sftp.open('/var/www/tsalegacy/controllers/ReportController.php', 'r') as f:
        c = f.read().decode('utf-8')
        # Let's find tax_summary method
        start = c.find('function tax_summary')
        if start != -1:
            end = c.find('public function', start + 1)
            if end == -1: end = len(c)
            print("--- tax_summary function ---")
            print(c[start:min(end, start+1000)])
except Exception as e:
    print(e)
    
try:
    with sftp.open('/var/www/tsalegacy/views/reports/tax_summary.php', 'r') as f:
        c = f.read().decode('utf-8')
        print("--- views/reports/tax_summary.php ---")
        lines = c.split('\n')
        for i in range(1, min(50, len(lines))):
            print(lines[i])
except Exception as e:
    print(e)

sftp.close()
ssh.close()
