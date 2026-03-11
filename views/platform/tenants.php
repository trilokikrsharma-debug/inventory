<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-building text-primary me-2"></i> Tenant Management</h1>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom-0">
        <h6 class="m-0 font-weight-bold text-primary">All Registered Companies</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Tenant / Subdomain</th>
                        <th>Owner</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tenants as $t): ?>
                    <tr>
                        <td class="ps-4 text-muted">#<?php echo $t['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($t['name'] ?? ''); ?></strong><br>
                            <small class="text-muted"><code><?php echo htmlspecialchars($t['subdomain'] ?? ''); ?>.<?php echo $_SERVER['HTTP_HOST'] ?? 'localhost'; ?></code></small>
                        </td>
                        <td><a href="mailto:<?php echo htmlspecialchars($t['owner_email'] ?? ''); ?>"><?php echo htmlspecialchars($t['owner_email'] ?? ''); ?></a></td>
                        <td><span class="badge bg-dark"><?php echo htmlspecialchars($t['plan_name'] ?? 'Free'); ?></span></td>
                        <td>
                            <?php if($t['subscription_status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif($t['subscription_status'] === 'trial'): ?>
                                <span class="badge bg-warning text-dark">Trial</span>
                            <?php elseif($t['subscription_status'] === 'suspended'): ?>
                                <span class="badge bg-danger">Suspended</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($t['subscription_status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                        <td class="text-end pe-4">
                            <form action="<?php echo APP_URL; ?>/index.php?page=platform&action=impersonate_tenant" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Login as Owner">
                                    <i class="fas fa-user-secret"></i>
                                </button>
                            </form>
                            
                            <?php if($t['subscription_status'] === 'suspended'): ?>
                                <form action="<?php echo APP_URL; ?>/index.php?page=platform&action=reactivate_tenant" method="POST" class="d-inline" onsubmit="return confirm('Reactivate this tenant?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Reactivate">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form action="<?php echo APP_URL; ?>/index.php?page=platform&action=suspend_tenant" method="POST" class="d-inline" onsubmit="return confirm('Suspend tenant? They will be locked out immediately.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Suspend">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form action="<?php echo APP_URL; ?>/index.php?page=platform&action=delete_tenant" method="POST" class="d-inline" onsubmit="return confirm('EXTREME DANGER: Fully wipe tenant and all data? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hard Wipe">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($tenants)): ?>
            <div class="p-4 text-center text-muted">No tenants registered yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
