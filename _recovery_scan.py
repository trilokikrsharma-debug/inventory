"""Server Forensic Recovery Scan."""
import paramiko

HOST = '34.14.169.146'
USER = 'deploy'
PASS = 'Triloki'

def ssh_exec(client, cmd):
    actual = f"echo '{PASS}' | sudo -S bash -c \"{cmd}\""
    stdin, stdout, stderr = client.exec_command(actual, timeout=40)
    out = stdout.read().decode(errors='replace').strip()
    return out

def main():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"Connecting to {HOST} for recovery scan...")
    client.connect(HOST, username=USER, password=PASS, timeout=15)
    print("Connected. Running forensic scans...\n")

    print("=== 1. CHECKING GIT STASH ===")
    stash = ssh_exec(client, "cd /var/www/tsalegacy && git stash list")
    print(stash if stash else "(No stash found)")

    print("\n=== 2. CHECKING REFLOG (Last 10 actions) ===")
    reflog = ssh_exec(client, "cd /var/www/tsalegacy && git reflog -n 10")
    print(reflog if reflog else "(No reflog found)")

    print("\n=== 3. LOOKING FOR DANGLING BLOBS (Uncommitted staged files) ===")
    ssh_exec(client, "cd /var/www/tsalegacy && git fsck --lost-found")
    blobs = ssh_exec(client, "cd /var/www/tsalegacy && find .git/lost-found/other/ -type f 2>/dev/null | wc -l")
    print(f"Found {blobs} dangling blobs that can be recovered.")

    print("\n=== 4. CHECKING FOR EDITOR TEMPORARY/SWAP FILES ===")
    swaps = ssh_exec(client, "find /var/www/tsalegacy/views/public -type f -name '*.swp' -o -name '*~' 2>/dev/null")
    print(swaps if swaps else "(No .swp or ~ files found)")

    print("\n=== 5. CHECKING REPO STATUS ===")
    status = ssh_exec(client, "cd /var/www/tsalegacy && git status")
    print(status)

    client.close()
    print("\nScan complete. We will analyze this data to restore the morning state.")

if __name__ == '__main__':
    main()
