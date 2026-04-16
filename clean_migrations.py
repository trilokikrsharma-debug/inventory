import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def run_cmd(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if err.strip() and 'Warning' not in err and 'Deprecated' not in err:
        print("ERR: ", err)
    return out

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

print("--- 1. CREATING MIGRATION FILE ---")
migration_sql = """-- Migration: Add Dispatch & E-Way Bill Hub fields to Sales table
-- Implements Indian GST requirement for transport documentation on invoices > 50K

ALTER TABLE `sales`
ADD COLUMN `dispatch_vehicle` VARCHAR(50) DEFAULT NULL AFTER `shipping_cost`,
ADD COLUMN `dispatch_transporter` VARCHAR(100) DEFAULT NULL AFTER `dispatch_vehicle`,
ADD COLUMN `dispatch_lr_no` VARCHAR(50) DEFAULT NULL AFTER `dispatch_transporter`;
"""
sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/database/058_add_gst_dispatch_fields.sql', 'w') as f:
    f.write(migration_sql.encode('utf-8'))
print("Created database/058_add_gst_dispatch_fields.sql")

print("--- 2. UPDATING MAIN SCHEMA.SQL ---")
with sftp.open('/var/www/tsalegacy/database/schema.sql', 'r') as f:
    schema = f.read().decode('utf-8')

# We need to find the `CREATE TABLE \`sales\`` block and add our new fields inside it.
# Usually, fields are separated by commas. We'll search for `shipping_cost` inside schema.sql
# and append our fields right after it.
if '`shipping_cost`' in schema and 'dispatch_vehicle' not in schema:
    # Let's cleanly inject it.
    schema = schema.replace(
        "`shipping_cost` decimal(10,2) DEFAULT '0.00',",
        "`shipping_cost` decimal(10,2) DEFAULT '0.00',\n  `dispatch_vehicle` varchar(50) DEFAULT NULL,\n  `dispatch_transporter` varchar(100) DEFAULT NULL,\n  `dispatch_lr_no` varchar(50) DEFAULT NULL,"
    )
    # Check for cases without backticks or single quotes
    schema = schema.replace(
        "shipping_cost DECIMAL(10,2) DEFAULT 0.00,",
        "shipping_cost DECIMAL(10,2) DEFAULT 0.00,\n    dispatch_vehicle VARCHAR(50) DEFAULT NULL,\n    dispatch_transporter VARCHAR(100) DEFAULT NULL,\n    dispatch_lr_no VARCHAR(50) DEFAULT NULL,"
    )
    with sftp.open('/var/www/tsalegacy/database/schema.sql', 'w') as f:
        f.write(schema.encode('utf-8'))
    print("Injected dispatch fields into schema.sql `sales` table definition.")
else:
    print("Schema already updated or shipping_cost column structure differs. Please check manually.")

sftp.close()
ssh.close()
print("Migration files cleanup complete.")
