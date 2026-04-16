import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()

# =============================================
# FIX 1: Add export_gstr1 and tax_summary to RBAC PERMISSIONS map
# =============================================
print("--- FIX 1: Updating RbacMiddleware PERMISSIONS map ---")
with sftp.open('/var/www/tsalegacy/middleware/RbacMiddleware.php', 'r') as f:
    c = f.read().decode('utf-8')

# Add export_gstr1 to sales permissions
old_sales = """'sales' => [
            'index' => 'sales.view',
            'view_sale' => 'sales.view',
            'create' => 'sales.create',
            'edit' => 'sales.edit',
            'delete' => 'sales.delete',
        ],"""

new_sales = """'sales' => [
            'index' => 'sales.view',
            'view_sale' => 'sales.view',
            'create' => 'sales.create',
            'edit' => 'sales.edit',
            'delete' => 'sales.delete',
            'export_gstr1' => 'reports.view',
        ],"""

c = c.replace(old_sales, new_sales)

# Add tax_summary action to reports
old_reports = """'reports' => [
            'index' => 'reports.view',
            'sales' => 'reports.view',
            'purchases' => 'reports.view',
            'stock' => 'reports.view',
            'warehouse_transfers' => 'reports.view',
            'payroll_finance' => 'reports.view',
            'profit' => 'reports.view',
            'customer_dues' => 'reports.view',
            'supplier_dues' => 'reports.view',
        ],"""

new_reports = """'reports' => [
            'index' => 'reports.view',
            'sales' => 'reports.view',
            'purchases' => 'reports.view',
            'stock' => 'reports.view',
            'tax_summary' => 'reports.view',
            'warehouse_transfers' => 'reports.view',
            'payroll_finance' => 'reports.view',
            'profit' => 'reports.view',
            'customer_dues' => 'reports.view',
            'supplier_dues' => 'reports.view',
            'queue_export' => 'reports.view',
        ],"""

c = c.replace(old_reports, new_reports)

with sftp.open('/var/www/tsalegacy/middleware/RbacMiddleware.php', 'w') as f:
    f.write(c.encode('utf-8'))
print("RBAC PERMISSIONS map updated with export_gstr1 and tax_summary.")

# Validate PHP syntax
stdin, stdout, stderr = ssh.exec_command("php -l /var/www/tsalegacy/middleware/RbacMiddleware.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

sftp.close()
ssh.close()
