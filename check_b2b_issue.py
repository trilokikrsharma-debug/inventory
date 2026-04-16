import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

print("=== CHECKING JS IN SALES CREATE ===")
run_cmd = "sed -n '/let taxCalculationEnabled/,$p' /var/www/tsalegacy/views/sales/create.php | grep -n -A 15 'customer_id'"
stdin, stdout, stderr = ssh.exec_command(run_cmd)
out = stdout.read().decode('utf-8', errors='replace')
print(out)

# And what about the original GSTR reporting issue?
# The subagent said the buttons were NOT visible on the tax_summary.php!
# Let's check `views/reports/tax_summary.php`.
print("=== CHECKING GSTR BUTTON UI IN TAX SUMMARY ===")
run_cmd2 = "sed -n '5,20p' /var/www/tsalegacy/views/reports/tax_summary.php"
stdin, stdout, stderr = ssh.exec_command(run_cmd2)
out2 = stdout.read().decode('utf-8', errors='replace')
print(out2)

ssh.close()
