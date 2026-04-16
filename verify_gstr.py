import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()

# 1. Validate SalesController syntax
print("--- 1. PHP Syntax Check ---")
stdin, stdout, stderr = ssh.exec_command("php -l /var/www/tsalegacy/controllers/SalesController.php")
out = stdout.read().decode('utf-8', errors='replace')
err = stderr.read().decode('utf-8', errors='replace')
print(out)
if 'error' in err.lower():
    print("ERR:", err)

# 2. Check if export_gstr1 method exists and is well-formed
print("--- 2. export_gstr1 method check ---")
with sftp.open('/var/www/tsalegacy/controllers/SalesController.php', 'r') as f:
    c = f.read().decode('utf-8')
    
if 'export_gstr1' in c:
    start = c.find('function export_gstr1')
    end = c.find('public function', start + 20)
    if end == -1:
        end = c.rfind('}')
    snippet = c[start:end]
    print(f"Found export_gstr1 method ({len(snippet)} chars)")
    print(snippet[:200] + "...")
else:
    print("export_gstr1 NOT found in SalesController!")

# 3. Check allowedActions
print("\n--- 3. allowedActions ---")
for line in c.split('\n'):
    if 'allowedActions' in line:
        print(line.strip())
        break

# 4. Check the ReportController for tax_summary
print("\n--- 4. ReportController tax_summary ---")
with sftp.open('/var/www/tsalegacy/controllers/ReportController.php', 'r') as f:
    rc = f.read().decode('utf-8')
    
if 'tax_summary' in rc:
    start = rc.find('function tax_summary')
    end = rc.find('public function', start + 20)
    if end == -1:
        end = rc.rfind('}')
    print(rc[start:start+500])
else:
    print("tax_summary NOT found!")

# 5. Verify tax_summary view has GSTR buttons
print("\n--- 5. GSTR buttons in tax_summary.php ---")
with sftp.open('/var/www/tsalegacy/views/reports/tax_summary.php', 'r') as f:
    ts = f.read().decode('utf-8')

if 'GSTR-1' in ts:
    for line in ts.split('\n'):
        if 'GSTR' in line:
            print(line.strip())
else:
    print("GSTR buttons NOT found in tax_summary view!")

sftp.close()
ssh.close()
