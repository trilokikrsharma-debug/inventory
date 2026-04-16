import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
try:
    with sftp.open('/var/www/tsalegacy/views/sales/index.php', 'r') as f:
        c = f.read().decode('utf-8')
        
        # We find the table cell where Status is shown.
        # `<span class="badge <?= Helper::paymentStatusClass($s['payment_status']) ?>"><?= ucfirst($s['payment_status']) ?></span>`
        # We will add underneath it an E-way badge if total > 50k
        old_status = """<span class="badge <?= Helper::paymentStatusClass($s['payment_status']) ?>"><?= ucfirst($s['payment_status']) ?></span>"""
        new_status = """<span class="badge <?= Helper::paymentStatusClass($s['payment_status']) ?>"><?= ucfirst($s['payment_status']) ?></span>
                            <?php if ($s['grand_total'] >= 50000 && empty($s['dispatch_lr_no'])): ?>
                            <br><span class="badge bg-warning text-dark mt-1" title="Invoice Value Exceeds ₹50K">E-Way Bill Req.</span>
                            <?php elseif (!empty($s['dispatch_lr_no'])): ?>
                            <br><span class="badge bg-success mt-1"><i class="fas fa-truck text-white" title="Transiting: <?= Helper::escape($s['dispatch_lr_no']) ?>"></i></span>
                            <?php endif; ?>"""
        
        if 'E-Way Bill Req.' not in c:
            if old_status in c:
                c = c.replace(old_status, new_status)
                with sftp.open('/var/www/tsalegacy/views/sales/index.php', 'w') as f:
                    f.write(c.encode('utf-8'))
                print("E-way bill visual alert added to Sales list.")
            else:
                print("Could not find old_status pattern.")
        else:
            print("E-way bill visual alert already exists.")
            
except Exception as e:
    print(e)
    
sftp.close()
ssh.close()
