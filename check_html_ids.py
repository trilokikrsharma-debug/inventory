import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/views/sales/create.php', 'r') as f:
    c = f.read().decode('utf-8')
    print("Checking HTML tags related to discount, shipping, transport, round_off:")
    for line in c.split('\n'):
        if 'discountInput' in line or 'shippingInput' in line or 'freightInput' in line or 'loadingInput' in line or 'roundOffInput' in line:
            print(line.strip())

sftp.close()
ssh.close()
