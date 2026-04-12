import paramiko

def execute_ssh():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect('34.14.169.146', username='deploy', password='Triloki')
    
    commands = [
        "ls -la /var/www/",
        "ls -la /var/www/html/"
    ]
    
    for cmd in commands:
        print(f"Executing: {cmd}")
        stdin, stdout, stderr = ssh.exec_command(cmd)
        print("STDOUT:", stdout.read().decode())
        print("STDERR:", stderr.read().decode())
        
    ssh.close()

if __name__ == '__main__':
    execute_ssh()
