import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
try:
    with sftp.open('/var/www/tsalegacy/views/reports/tax_summary.php', 'r') as f:
        c = f.read().decode('utf-8')
        
        # We want to add GSTR-1 export buttons in the header section, near the print/date filter
        old_buttons = '<div class="card-header d-flex flex-wrap align-items-center justify-content-between">'
        new_buttons = """<div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <div class="mb-2 mb-md-0">
                <a href="<?= APP_URL ?>/index.php?page=sales&action=export_gstr1&type=b2b&month=<?= date('Y-m', strtotime($endDate)) ?>" class="btn btn-sm btn-success me-2"><i class="fas fa-file-excel me-1"></i> GSTR-1 (B2B)</a>
                <a href="<?= APP_URL ?>/index.php?page=sales&action=export_gstr1&type=b2c&month=<?= date('Y-m', strtotime($endDate)) ?>" class="btn btn-sm btn-info text-white"><i class="fas fa-file-excel me-1"></i> GSTR-1 (B2C)</a>
            </div>"""
        
        if 'export_gstr1' not in c:
            c = c.replace(old_buttons, new_buttons)
            with sftp.open('/var/www/tsalegacy/views/reports/tax_summary.php', 'w') as f:
                f.write(c.encode('utf-8'))
            print("GSTR-1 buttons added to Tax Summary report.")
        else:
            print("GSTR-1 buttons already exist.")
            
except Exception as e:
    print(e)
    
sftp.close()
ssh.close()
