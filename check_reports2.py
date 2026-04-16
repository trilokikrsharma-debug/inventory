import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
try:
    print("Controllers:")
    print([f for f in sftp.listdir('/var/www/tsalegacy/controllers') if 'Report' in f])
except Exception as e:
    print(e)
    
try:
    print("Views in views/reports:")
    print(sftp.listdir('/var/www/tsalegacy/views/reports'))
except Exception as e:
    print(e)

sftp.close()
ssh.close()
