import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/views/sales/create.php', 'r') as f:
    c = f.read().decode('utf-8')
    start = c.find('function calc()')
    end = c.find('</script>', start)
    js = c[start:end]
    print("JS Lines 1-33:")
    for idx, line in enumerate(js.split('\n')[:33]):
        print(f"{idx+1}: {line}")

sftp.close()
ssh.close()
