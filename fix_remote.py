import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
# Print Javascript calc() code from views/sales/create.php
with sftp.open('/var/www/tsalegacy/views/sales/create.php', 'r') as f:
    c = f.read().decode('utf-8')
    if "function calc()" in c:
        start_idx = c.find('function calc()')
        js_part = c[start_idx:c.find('</script>', start_idx)]
        print("=== JS CALC IN SALES ===")
        # Check if the auto gst logic was inserted wrongly
        auto_gst_idx = c.find('document.querySelector(\\\'select[name=')
        print(c[auto_gst_idx-50:auto_gst_idx+500])

# Fix the Tax Summary Buttons:
# In the previous script, I looked for `<div class="card-header d-flex flex-wrap align-items-center justify-content-between">`
# But the subagent showed the header looks like:
# `<div class="report-page-actions">`
with sftp.open('/var/www/tsalegacy/views/reports/tax_summary.php', 'r') as f:
    ts = f.read().decode('utf-8')
    
    old_actions = '<div class="report-page-actions">'
    new_actions = """<div class="report-page-actions">
        <a href="<?= APP_URL ?>/index.php?page=sales&action=export_gstr1&type=b2b&month=<?= date('Y-m', strtotime($endDate)) ?>" class="btn btn-sm btn-success me-2"><i class="fas fa-file-excel me-1"></i> GSTR-1 (B2B)</a>
        <a href="<?= APP_URL ?>/index.php?page=sales&action=export_gstr1&type=b2c&month=<?= date('Y-m', strtotime($endDate)) ?>" class="btn btn-sm btn-info text-white me-2"><i class="fas fa-file-excel me-1"></i> GSTR-1 (B2C)</a>"""
    if 'GSTR-1' not in ts:
        ts = ts.replace(old_actions, new_actions)
        with sftp.open('/var/www/tsalegacy/views/reports/tax_summary.php', 'w') as f2:
            f2.write(ts.encode('utf-8'))
        print("\nSuccessfully injected GSTR-1 buttons into views/reports/tax_summary.php")
    else:
        print("\nGSTR-1 buttons already injected in tax_summary.php")

sftp.close()
ssh.close()
