import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def run(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if err.strip():
        print("ERR:", err)
    return out

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/views/purchases/create.php', 'r') as f:
    c = f.read().decode('utf-8')

bad_line = "document.querySelector('select[name=\"supplier_id\"]').addEventListener('change', function(e) {"
good_line = "document.querySelector('select[name=\\'supplier_id\\']').addEventListener('change', function(e) {"

if bad_line in c:
    c = c.replace(bad_line, good_line)

with sftp.open('/var/www/tsalegacy/views/purchases/create.php', 'w') as f:
    f.write(c.encode('utf-8'))

sftp.close()
run(ssh, "php -l /var/www/tsalegacy/views/purchases/create.php")
ssh.close()
print("Fixed syntax for purchases create.")
