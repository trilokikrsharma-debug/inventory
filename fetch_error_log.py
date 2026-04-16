import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

try:
    print("--- APACHE ERROR LOG ---")
    stdin, stdout, stderr = ssh.exec_command("tail -n 30 /var/log/apache2/error.log")
    print(stdout.read().decode('utf-8'))
except Exception as e:
    print(e)
    
ssh.close()
