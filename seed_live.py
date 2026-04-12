import paramiko
import sys

def execute_ssh():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect('34.14.169.146', username='deploy', password='Triloki')
    except Exception as e:
        sys.exit(1)
        
    commands = [
        "cd /var/www/tsalegacy && mysql -u tsauser -pTrisha@1944 tsalegacy < database/demo_seeder.sql"
    ]
    
    for cmd in commands:
        print(f"Executing: {cmd}")
        stdin, stdout, stderr = ssh.exec_command(cmd)
        print("STDOUT:", stdout.read().decode())
        print("STDERR:", stderr.read().decode())
        
    ssh.close()

if __name__ == '__main__':
    execute_ssh()
