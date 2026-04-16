import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
try:
    with sftp.open('/var/www/tsalegacy/views/sales/index.php', 'r') as f:
        c = f.read().decode('utf-8')
        lines = c.split('\n')
        for i in range(len(lines)):
            if 'payment_status' in lines[i]:
                print(f"Line {i+1}: {lines[i].strip()}")
except Exception as e:
    print(e)
    
sftp.close()
ssh.close()
