import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def run(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out.strip():
        print(out)
    if err.strip():
        print("ERR:", err)

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/views/sales/create.php', 'r') as f:
    c = f.read().decode('utf-8')

# The Javascript has unescaped double quotes breaking PHP string
# We need to find the added JS and replace "customer_id" with \\"customer_id\\"
# Even better, let's use single quotes inside the JS querySelector.
# But inside a PHP double quote string $inlineScript = "..."; we can use single quotes fine.
# 'select[name="customer_id"]' should be 'select[name=\\'customer_id\\']' for JS inside Double Quoted PHP String
bad_line = "document.querySelector('select[name=\"customer_id\"]').addEventListener('change', function(e) {"
good_line = "document.querySelector('select[name=\\'customer_id\\']').addEventListener('change', function(e) {"

if bad_line in c:
    c = c.replace(bad_line, good_line)
    
# Let's verify no other double quotes inside JS are breaking the PHP string.
# Our generated code block: 
# document.querySelector('select[name=\"customer_id\"]').addEventListener('change', function(e) {
#    if(!document.getElementById('gstTypeInput')) return;
# ...
# document.getElementById('gstTypeInput').value = type;

bad_line2 = "document.getElementById('gstTypeInput').value = type;"
# Wait, single quotes inside PHP double quote string do NOT need escaping.
# It was only the double quotes in 'select[name="customer_id"]'. Because the PHP string starts with "

with sftp.open('/var/www/tsalegacy/views/sales/create.php', 'w') as f:
    f.write(c.encode('utf-8'))

sftp.close()

run(ssh, "php -l /var/www/tsalegacy/views/sales/create.php")
ssh.close()
