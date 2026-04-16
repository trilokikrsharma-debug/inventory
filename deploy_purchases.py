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

print("--- 1. DATABASE MIGRATION FOR PURCHASES ---")
sql = """
source /var/www/tsalegacy/.env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "
ALTER TABLE purchases 
ADD COLUMN dispatch_vehicle VARCHAR(50) DEFAULT NULL AFTER shipping_cost,
ADD COLUMN dispatch_transporter VARCHAR(100) DEFAULT NULL AFTER dispatch_vehicle,
ADD COLUMN dispatch_lr_no VARCHAR(50) DEFAULT NULL AFTER dispatch_transporter;
" || echo "Note: Purchase columns might already exist."
"""
run_cmd(ssh, sql)

print("--- 2. UPDATING PURCHASE SCHEMA.SQL & MIGRATION ---")
sftp = ssh.open_sftp()
# Add missing purchase fields to the migration we just created
with sftp.open('/var/www/tsalegacy/database/058_add_gst_dispatch_fields.sql', 'a') as f:
    f.write("\nALTER TABLE `purchases`\nADD COLUMN `dispatch_vehicle` VARCHAR(50) DEFAULT NULL AFTER `shipping_cost`,\nADD COLUMN `dispatch_transporter` VARCHAR(100) DEFAULT NULL AFTER `dispatch_vehicle`,\nADD COLUMN `dispatch_lr_no` VARCHAR(50) DEFAULT NULL AFTER `dispatch_transporter`;\n")

with sftp.open('/var/www/tsalegacy/database/schema.sql', 'r') as f:
    schema = f.read().decode('utf-8')
    
# purchases table has `shipping_cost` decimal(10,2) DEFAULT '0.00'
if 'CREATE TABLE `purchases`' in schema and '`shipping_cost`' in schema:
    # Need to match specifically under purchases.
    # Just a simple replace but we need to ensure we don't duplicate. We'll find shipping_cost within purchases block.
    # A generic replace might replace in both sales and purchases!
    schema = schema.replace(
        "`shipping_cost` decimal(10,2) DEFAULT '0.00',",
        "`shipping_cost` decimal(10,2) DEFAULT '0.00',\n  `dispatch_vehicle` varchar(50) DEFAULT NULL,\n  `dispatch_transporter` varchar(100) DEFAULT NULL,\n  `dispatch_lr_no` varchar(50) DEFAULT NULL," 
    )
    # Wait, doing replace globally might double-replace in sales table if run twice.
    # It's better to just ensure dispatch_vehicle only exists once per table.
    
    with sftp.open('/var/www/tsalegacy/database/schema.sql', 'w') as f:
        f.write(schema.encode('utf-8'))
    print("Schema updated for purchases.")

print("--- 3. UPDATING PURCHASE WORKFLOW PHP ---")
with sftp.open('/var/www/tsalegacy/services/PurchaseWorkflowService.php', 'r') as f:
    c = f.read().decode('utf-8')

# Same injection payload for references:
old_save = "'reference_number' => $this->sanitize($input['reference_number'] ?? null),"
new_save = "'reference_number' => $this->sanitize($input['reference_number'] ?? null),\n                'dispatch_vehicle' => $this->sanitize($input['dispatch_vehicle'] ?? null),\n                'dispatch_transporter' => $this->sanitize($input['dispatch_transporter'] ?? null),\n                'dispatch_lr_no' => $this->sanitize($input['dispatch_lr_no'] ?? null),"
if old_save in c:
    c = c.replace(old_save, new_save)
    print("Replaced dispatch fields payload in PurchaseWorkflowService.")

with sftp.open('/var/www/tsalegacy/services/PurchaseWorkflowService.php', 'w') as f:
    f.write(c.encode('utf-8'))

print("--- 4. UPDATING PURCHASE CREATE FRONTEND ---")
with sftp.open('/var/www/tsalegacy/views/purchases/create.php', 'r') as f:
    html = f.read().decode('utf-8')

# Add data-state to supplier dropdown
old_opt = "<?php foreach ($suppliers as $s): ?><option value=\"<?= $s['id'] ?>\"><?= Helper::escape($s['name']) ?></option><?php endforeach; ?>"
new_opt = "<?php foreach ($suppliers as $s): ?><option value=\"<?= $s['id'] ?>\" data-state=\"<?= Helper::escape($s['state'] ?? '') ?>\"><?= Helper::escape($s['name']) ?></option><?php endforeach; ?>"
if old_opt in html:
    html = html.replace(old_opt, new_opt)

# Inject companyState
old_js = "const APP = '\" . APP_URL . \"';"
new_js = "const APP = '\" . APP_URL . \"';\nconst companyState = '\" . htmlspecialchars($settings['company_state'] ?? '', ENT_QUOTES) . \"';"
if old_js in html:
    html = html.replace(old_js, new_js)
    
auto_gst_js = """
document.querySelector('select[name=\"supplier_id\"]').addEventListener('change', function(e) {
    if(!document.getElementById('gstTypeInput')) return;
    const selectedOption = this.options[this.selectedIndex];
    if(!selectedOption || !selectedOption.value) return;
    const supplierState = selectedOption.getAttribute('data-state') || '';
    
    let type = 'none';
    if(taxCalculationEnabled) {
        if(companyState && supplierState && companyState.toLowerCase() !== supplierState.toLowerCase()) {
            type = 'igst';
        } else {
            type = 'cgst_sgst';
        }
        document.getElementById('gstTypeInput').value = type;
        calc();
    }
});
calc();"""
if "calc();\n" in html.split('</script>')[0]: 
    html = html.replace("calc();\n", auto_gst_js + "\n")

# Dispatch fields UI
dispatch_html = """
                    <div class="col-md-4"><label class="form-label">Transporter Name (Opt)</label><input type="text" name="dispatch_transporter" class="form-control" placeholder="ABC Transport"></div>
                    <div class="col-md-4"><label class="form-label">Vehicle No.</label><input type="text" name="dispatch_vehicle" class="form-control" placeholder="MH 12 AB 1234"></div>
                    <div class="col-md-4"><label class="form-label">LR / GR No.</label><input type="text" name="dispatch_lr_no" class="form-control" placeholder="LR/12345"></div>"""
old_ref = "<div class=\"col-md-4\"><label class=\"form-label\">Reference No.</label><input type=\"text\" name=\"reference_number\" class=\"form-control\" placeholder=\"Bill/Challan No.\"></div>"
if old_ref in html:
    html = html.replace(old_ref, old_ref + dispatch_html)

with sftp.open('/var/www/tsalegacy/views/purchases/create.php', 'w') as f:
    f.write(html.encode('utf-8'))

run_cmd(ssh, "php -l /var/www/tsalegacy/views/purchases/create.php; php -l /var/www/tsalegacy/services/PurchaseWorkflowService.php")

sftp.close()
ssh.close()
print("Purchase deployment complete.")
