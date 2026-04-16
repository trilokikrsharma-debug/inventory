import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def run_cmd(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out.strip():
        print(out)
    if err.strip() and 'Warning' not in err and 'Deprecated' not in err:
        print("ERR: ", err)
    return out

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

print("--- 1. DATABASE MIGRATION FOR DISPATCH FIELDS ---")
sql = """
source /var/www/tsalegacy/.env
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST $DB_NAME -e "
ALTER TABLE sales 
ADD COLUMN dispatch_vehicle VARCHAR(50) DEFAULT NULL AFTER shipping_cost,
ADD COLUMN dispatch_transporter VARCHAR(100) DEFAULT NULL AFTER dispatch_vehicle,
ADD COLUMN dispatch_lr_no VARCHAR(50) DEFAULT NULL AFTER dispatch_transporter;
" || echo "Note: Columns might already exist."
"""
run_cmd(ssh, sql)

print("\n--- 2. UPDATING SALES WORKFLOW PHP (to save dispatch fields) ---")
sftp = ssh.open_sftp()
with sftp.open('/var/www/tsalegacy/services/SalesWorkflowService.php', 'r') as f:
    c = f.read().decode('utf-8')

# Search for the payload array creation and inject dispatch fields
old_save = "'reference_number' => $this->sanitize($input['reference_number'] ?? null),"
new_save = "'reference_number' => $this->sanitize($input['reference_number'] ?? null),\n                'dispatch_vehicle' => $this->sanitize($input['dispatch_vehicle'] ?? null),\n                'dispatch_transporter' => $this->sanitize($input['dispatch_transporter'] ?? null),\n                'dispatch_lr_no' => $this->sanitize($input['dispatch_lr_no'] ?? null),"
if old_save in c:
    c = c.replace(old_save, new_save)
    print("Replaced dispatch fields payload.")
else:
    print("Warning: old_save not found. Please review code.")

with sftp.open('/var/www/tsalegacy/services/SalesWorkflowService.php', 'w') as f:
    f.write(c.encode('utf-8'))

print("\n--- 3. UPDATING SALES CREATE FRONTEND (UI & Auto-GST Engine) ---")
with sftp.open('/var/www/tsalegacy/views/sales/create.php', 'r') as f:
    html = f.read().decode('utf-8')

# Add data-state to customer dropdown
old_opt = "<?php foreach ($customers as $c): ?><option value=\"<?= $c['id'] ?>\"><?= Helper::escape($c['name']) ?></option><?php endforeach; ?>"
new_opt = "<?php foreach ($customers as $c): ?><option value=\"<?= $c['id'] ?>\" data-state=\"<?= Helper::escape($c['state'] ?? '') ?>\"><?= Helper::escape($c['name']) ?></option><?php endforeach; ?>"
if old_opt in html:
    html = html.replace(old_opt, new_opt)
    
# Inject company_state into JS environment
# Let's add it where currentTaxStatus is set near the end of the file.
old_js = "const APP = '\" . APP_URL . \"';"
new_js = "const APP = '\" . APP_URL . \"';\nconst companyState = '\" . htmlspecialchars($settings['company_state'] ?? '', ENT_QUOTES) . \"';"
if old_js in html:
    html = html.replace(old_js, new_js)

# Inject Auto GST swap logic into JS
# When customer changes, compare their state to companyState and auto-select gstTypeInput.
auto_gst_js = """
// Auto IGST/CGST smart logic
document.querySelector('select[name="customer_id"]').addEventListener('change', function(e) {
    if(!document.getElementById('gstTypeInput')) return;
    const selectedOption = this.options[this.selectedIndex];
    if(!selectedOption || !selectedOption.value) return;
    const customerState = selectedOption.getAttribute('data-state') || '';
    
    let type = 'none';
    if(taxCalculationEnabled) {
        if(companyState && customerState && companyState.toLowerCase() !== customerState.toLowerCase()) {
            type = 'igst';
        } else {
            type = 'cgst_sgst';
        }
        document.getElementById('gstTypeInput').value = type;
        calc();
    }
});
calc();"""
if "calc();\n" in html.split('</script>')[0]: # Just ensuring calc() is there to replace.
    html = html.replace("calc();\n", auto_gst_js + "\n")
    
# Let's add Dispatch Details Input to the UI
# Find reference_number and append Dispatch Section below it
dispatch_html = """
                    <div class="col-md-4 col-xl-4"><label class="form-label">E-Way Bill / Transporter (Opt)</label><input type="text" name="dispatch_transporter" class="form-control" placeholder="ABC Transport"></div>
                    <div class="col-md-4 col-xl-4"><label class="form-label">Vehicle No.</label><input type="text" name="dispatch_vehicle" class="form-control" placeholder="MH 12 AB 1234"></div>
                    <div class="col-md-4 col-xl-4"><label class="form-label">LR / GR No.</label><input type="text" name="dispatch_lr_no" class="form-control" placeholder="LR/12345"></div>"""
old_ref = "<div class=\"col-md-6 col-xl-4\"><label class=\"form-label\">Reference</label><input type=\"text\" name=\"reference_number\" class=\"form-control\"></div>"
if old_ref in html:
    html = html.replace(old_ref, old_ref + dispatch_html)

with sftp.open('/var/www/tsalegacy/views/sales/create.php', 'w') as f:
    f.write(html.encode('utf-8'))

run_cmd(ssh, "php -l /var/www/tsalegacy/views/sales/create.php; php -l /var/www/tsalegacy/services/SalesWorkflowService.php")
sftp.close()
ssh.close()
print("\nDeployment complete.")
