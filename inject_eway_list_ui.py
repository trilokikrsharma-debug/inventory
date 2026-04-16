import paramiko, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('34.14.169.146', username='deploy', password='Triloki')

sftp = ssh.open_sftp()
# 1. Update Sales List View (Index)
try:
    with sftp.open('/var/www/tsalegacy/views/sales/index.php', 'r') as f:
        c = f.read().decode('utf-8')
        
        old_badge = """<td class="status-badge"><?= Helper::paymentBadge($s['payment_status']) ?></td>"""
        new_badge = """<td class="status-badge">
                        <?= Helper::paymentBadge($s['payment_status']) ?>
                        <?php if ($s['grand_total'] >= 50000 && empty($s['dispatch_lr_no'])): ?>
                            <br><span class="badge bg-warning text-dark mt-1" style="font-size:0.7em;" title="E-Way Bill Recommended for > 50K"><i class="fas fa-exclamation-circle"></i> E-Way Req</span>
                        <?php elseif (!empty($s['dispatch_lr_no'])): ?>
                            <br><span class="badge bg-secondary mt-1" style="font-size:0.7em;"><i class="fas fa-truck"></i> <?= Helper::escape($s['dispatch_lr_no']) ?></span>
                        <?php endif; ?>
                    </td>"""
        
        if 'E-Way Req' not in c and old_badge in c:
            c = c.replace(old_badge, new_badge)
            with sftp.open('/var/www/tsalegacy/views/sales/index.php', 'w') as f:
                f.write(c.encode('utf-8'))
            print("E-way alert added to Sales list.")
except Exception as e: print(e)

# 2. Update Purchases List View
try:
    with sftp.open('/var/www/tsalegacy/views/purchases/index.php', 'r') as f:
        c = f.read().decode('utf-8')
        old_badge = """<td class="status-badge"><?= Helper::paymentBadge($p['payment_status']) ?></td>"""
        new_badge = """<td class="status-badge">
                        <?= Helper::paymentBadge($p['payment_status']) ?>
                        <?php if ($p['grand_total'] >= 50000 && empty($p['dispatch_lr_no'])): ?>
                            <br><span class="badge bg-warning text-dark mt-1" style="font-size:0.7em;" title="E-Way Bill Tracking Missing"><i class="fas fa-exclamation-circle"></i> E-Way Req</span>
                        <?php elseif (!empty($p['dispatch_lr_no'])): ?>
                            <br><span class="badge bg-secondary mt-1" style="font-size:0.7em;"><i class="fas fa-truck"></i> <?= Helper::escape($p['dispatch_lr_no']) ?></span>
                        <?php endif; ?>
                    </td>"""
        
        if 'E-Way Req' not in c and old_badge in c:
            c = c.replace(old_badge, new_badge)
            with sftp.open('/var/www/tsalegacy/views/purchases/index.php', 'w') as f:
                f.write(c.encode('utf-8'))
            print("E-way alert added to Purchases list.")
except Exception as e: print(e)

sftp.close()
ssh.close()
