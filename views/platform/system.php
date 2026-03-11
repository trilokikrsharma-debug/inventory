<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-terminal text-dark me-2"></i> System Diagnostics</h1>
</div>

<div class="row">
    <!-- Health Overview -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-memory me-2"></i> Global Health Vector</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr><td class="text-muted fw-bold">PHP Version</td><td class="text-end"><?php echo htmlspecialchars($sysHealth['php']); ?></td></tr>
                    <tr><td class="text-muted fw-bold">OPCache Core</td><td class="text-end"><span class="badge bg-<?php echo $sysHealth['opcache'] === 'Enabled' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($sysHealth['opcache']); ?></span></td></tr>
                    <tr><td class="text-muted fw-bold">RAM Allocation Limit</td><td class="text-end"><?php echo ini_get('memory_limit'); ?></td></tr>
                    <tr><td class="text-muted fw-bold">Current Process RAM</td><td class="text-end"><?php echo htmlspecialchars($sysHealth['mem']); ?></td></tr>
                    <tr><td class="text-muted fw-bold">Redis Connection</td><td class="text-end"><span class="badge bg-<?php echo $sysHealth['redis'] === 'Connected' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($sysHealth['redis']); ?></span></td></tr>
                    <tr><td class="text-muted fw-bold">Disk Free Volume</td><td class="text-end"><?php echo htmlspecialchars($sysHealth['disk']); ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Background Workers -->
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-cogs me-2"></i> Asynchronous Jobs Pipeline</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                   Displays metrics directly from `jobs` table being processed by `cli/worker.php`.
                </p>
                <div class="row text-center mb-4">
                    <div class="col-4 border-end">
                        <div class="display-6 fw-bold text-secondary"><?php echo number_format($queueStats['pending'] ?? 0); ?></div>
                        <div class="small fw-bold text-uppercase text-muted mt-1">Pending</div>
                    </div>
                    <div class="col-4 border-end">
                        <div class="display-6 fw-bold text-primary"><?php echo number_format($queueStats['processing'] ?? 0); ?></div>
                        <div class="small fw-bold text-uppercase text-muted mt-1">Processing</div>
                    </div>
                    <div class="col-4">
                        <div class="display-6 fw-bold text-danger"><?php echo number_format($queueStats['failed'] ?? 0); ?></div>
                        <div class="small fw-bold text-uppercase text-muted mt-1">Failed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 bg-dark text-white">
    <div class="card-header bg-black py-3 border-bottom-0">
        <h6 class="m-0 font-weight-bold text-warning font-monospace"><i class="fas fa-bug me-2"></i> /logs/error.log (TAIL 50)</h6>
    </div>
    <div class="card-body p-0">
        <div class="p-3 font-monospace small" style="max-height: 400px; overflow-y: auto; background:#111; color:#0f0;">
            <?php if(empty($errorLogs)): ?>
                No errors trapped.
            <?php else: ?>
                <?php foreach($errorLogs as $line): ?>
                    <!-- XSS Guard against dirty log streams -->
                    <div><?php echo htmlspecialchars($line); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
