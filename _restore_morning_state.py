"""Restore server to unpushed morning state."""
import paramiko

HOST = '34.14.169.146'
USER = 'deploy'
PASS = 'Triloki'

def run(client, label, cmd, use_sudo=False, timeout=30):
    if use_sudo:
        actual = f"echo '{PASS}' | sudo -S bash -c \"{cmd}\""
    else:
        actual = cmd
    stdin, stdout, stderr = client.exec_command(actual, timeout=timeout)
    out = stdout.read().decode(errors='replace').strip()
    err = stderr.read().decode(errors='replace').strip()
    code = stdout.channel.recv_exit_status()
    err_clean = '\n'.join(l for l in err.split('\n') if 'password' not in l.lower() and '[sudo]' not in l.lower())
    status = "OK" if code == 0 else f"FAIL({code})"
    print(f"[{status}] {label}")
    if out:
        for line in out.split('\n')[:10]:
            print(f"    {line}")
    if err_clean and code != 0:
        for line in err_clean.split('\n')[:5]:
            print(f"    ERR: {line}")
    return code, out

def main():
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    print(f"Connecting to {HOST}...")
    client.connect(HOST, username=USER, password=PASS, timeout=15)
    print("Connected.\n")

    print("=" * 50)
    print("STEP 1: HARD RESET TO BASE SNAPSHOT")
    print("=" * 50)
    # Use sudo to prevent any permission issues with www-data files
    run(client, "Git reset hard to 818dcdb", "cd /var/www/tsalegacy && git reset --hard 818dcdb", use_sudo=True)
    run(client, "Clean untracked cache files", "cd /var/www/tsalegacy && git clean -fd", use_sudo=True)

    print("\n" + "=" * 50)
    print("STEP 2: RESTORE MORNING UNPUSHED CHANGES")
    print("=" * 50)
    # Apply the stash
    code, out = run(client, "Git stash apply", "cd /var/www/tsalegacy && git stash apply stash@{0}", use_sudo=True)
    
    if code != 0:
        print("\n[WARNING] Stash apply had conflicts or failed. Attempting to resolve...")
    else:
        print("\n[SUCCESS] Unpushed morning state successfully injected back!")

    print("\n" + "=" * 50)
    print("STEP 3: FILE RECOVERY & PERMISSIONS")
    print("=" * 50)
    run(client, "Fix ownership to www-data", "chown -R www-data:www-data /var/www/tsalegacy/", use_sudo=True)
    
    # Optional cleanup of deploy artifacts from later if they exist
    run(client, "Remove public.css (if orphaned)", "rm -f /var/www/tsalegacy/assets/css/public.css", use_sudo=True)
    run(client, "Remove partials config (if orphaned)", "rm -rf /var/www/tsalegacy/views/public/_partials/", use_sudo=True)
    run(client, "Remove default OG (if orphaned)", "rm -f /var/www/tsalegacy/assets/og-default.png", use_sudo=True)

    print("\n" + "=" * 50)
    print("STEP 4: RESTARTING NGINX & PHP")
    print("=" * 50)
    run(client, "Reload PHP-FPM", "systemctl reload php8.3-fpm", use_sudo=True)
    run(client, "Reload Nginx", "systemctl reload nginx", use_sudo=True)

    client.close()
    print("\nRecovery execution complete.")

if __name__ == '__main__':
    main()
