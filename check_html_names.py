import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/views/sales/create.php', 'r') as f:
    c = f.read().decode('utf-8')
    print("Finding the Discount and Transport HTML inputs:")
    # We will search for name="discount" or name="shipping_cost"
    for line in c.split('\n'):
        if 'name="discount"' in line or 'name="shipping_cost"' in line or 'name="round_off"' in line:
            print(line.strip())

sftp.close()
ssh.close()
