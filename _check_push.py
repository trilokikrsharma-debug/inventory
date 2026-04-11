import paramiko

HOST = '34.14.169.146'
USER = 'deploy'
PASS = 'Triloki'

def ssh_exec(client, cmd):
    actual = f"echo '{PASS}' | sudo -S bash -c \"{cmd}\""
    stdin, stdout, stderr = client.exec_command(actual, timeout=40)
    out = stdout.read().decode(errors='replace').strip()
    return out

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)

print("Checking repo status...")
status = ssh_exec(client, "cd /var/www/tsalegacy && git status")
print(status)
print("\nChecking push access...")
# dry run push
push_test = ssh_exec(client, "cd /var/www/tsalegacy && git push --dry-run origin main 2>&1")
print(push_test)

client.close()
