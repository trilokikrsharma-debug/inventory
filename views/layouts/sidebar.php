<?php
/**
 * Sidebar Navigation Component
 * 
 * Renders the collapsible sidebar with all navigation links.
 * Active state is determined by the current page.
 */
$currentPage = $_GET['page'] ?? 'dashboard';
$currentAction = $_GET['action'] ?? 'index';
?>
<aside class="sidebar" id="sidebar">
    <!-- Brand / Logo -->
    <a href="<?= APP_URL ?>" class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-bolt"></i>
        </div>
        <span class="brand-text"><?= APP_NAME ?></span>
    </a>

    <?php if (Session::isSuperAdmin()): ?>
    <div style="padding:0.25rem 0.5rem;margin:0 0.75rem 0.25rem;text-align:center;">
        <span style="font-size:0.6rem;background:linear-gradient(135deg,#ffd700,#ff8c00);color:#000;padding:0.2rem 0.6rem;border-radius:4px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">
            <i class="fas fa-crown" style="font-size:0.55rem;"></i> Super Admin
        </span>
    </div>
    <?php endif; ?>

    <?php if (Session::get('_impersonating_from')): ?>
    <?php $tenantCompany = Tenant::company(); ?>
    <div style="margin:0.25rem 0.75rem 0.5rem;padding:0.5rem 0.6rem;background:linear-gradient(135deg,#ff4757,#ff6b81);border-radius:6px;text-align:center;">
        <div style="font-size:0.6rem;color:#fff;font-weight:600;margin-bottom:0.3rem;">
            <i class="fas fa-eye"></i> Impersonating Tenant
        </div>
        <div style="font-size:0.55rem;color:rgba(255,255,255,0.85);margin-bottom:0.35rem;">
            <?= htmlspecialchars($tenantCompany['name'] ?? 'Unknown', ENT_QUOTES) ?>
        </div>
        <a href="<?= APP_URL ?>/index.php?page=platform&action=stop_impersonation"
           style="display:inline-block;font-size:0.6rem;color:#ff4757;background:#fff;padding:0.2rem 0.6rem;border-radius:4px;font-weight:700;text-decoration:none;"
           id="btn-stop-impersonation">
            <i class="fas fa-arrow-left" style="font-size:0.5rem;"></i> Return to Admin
        </a>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- Main -->
        <div class="sidebar-section-title"><span>Main</span></div>
        <ul style="list-style:none; padding:0; margin:0;">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=dashboard">
                    <i class="fas fa-th-large nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- Platform Admin (Super Admins Only) -->
        <?php if (Session::isSuperAdmin()): ?>
        <div class="sidebar-section-title"><span>Platform</span></div>
        <ul style="list-style:none; padding:0; margin:0;">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'platform' && $currentAction === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=platform&action=dashboard">
                    <i class="fas fa-satellite-dish nav-icon text-warning"></i>
                    <span class="nav-text text-warning">Platform Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'platform' && $currentAction === 'tenants' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=platform&action=tenants">
                    <i class="fas fa-building nav-icon text-warning"></i>
                    <span class="nav-text text-warning">Tenants</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'platform' && $currentAction === 'subscriptions' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=platform&action=subscriptions">
                    <i class="fas fa-credit-card nav-icon text-warning"></i>
                    <span class="nav-text text-warning">Subscriptions</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'platform' && $currentAction === 'payments' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=platform&action=payments">
                    <i class="fas fa-money-check nav-icon text-warning"></i>
                    <span class="nav-text text-warning">Payments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'saas_plans' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=saas_plans">
                    <i class="fas fa-layer-group nav-icon text-warning"></i>
                    <span class="nav-text text-warning">SaaS Plans</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'promos' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=promos">
                    <i class="fas fa-tags nav-icon text-warning"></i>
                    <span class="nav-text text-warning">Promo Codes</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'referrals' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=referrals">
                    <i class="fas fa-user-plus nav-icon text-warning"></i>
                    <span class="nav-text text-warning">Referrals</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'platform' && $currentAction === 'revenue' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=platform&action=revenue">
                    <i class="fas fa-chart-line nav-icon text-warning"></i>
                    <span class="nav-text text-warning">Revenue</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'platform' && $currentAction === 'system' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=platform&action=system">
                    <i class="fas fa-server nav-icon text-warning"></i>
                    <span class="nav-text text-warning">System Health</span>
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <?php if (!Session::isSuperAdmin()): ?>
        <div class="sidebar-section-title"><span>Billing</span></div>
        <ul style="list-style:none; padding:0; margin:0;">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'saas_billing' && $currentAction === 'subscribe' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=saas_billing&action=subscribe">
                    <i class="fas fa-crown nav-icon"></i>
                    <span class="nav-text">Plans & Billing</span>
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <!-- Inventory -->
        <div class="sidebar-section-title"><span>Inventory</span></div>
        <ul style="list-style:none; padding:0; margin:0;">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'products' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=products">
                    <i class="fas fa-boxes-stacked nav-icon"></i>
                    <span class="nav-text">Products</span>
                    <?php
                    // SECURITY FIX (TENANT-1): Only show low-stock badge in tenant context.
                    // When Tenant::id() is null (super-admin), skip entirely to prevent
                    // counting products across ALL tenants (cross-tenant data leak).
                    // Also cached to avoid a DB query on every page load.
                    $lowStockCount = 0;
                    if (Tenant::id() !== null) {
                        $settings = (new SettingsModel())->getSettings();
                        $defaultThreshold = (int)($settings['low_stock_threshold'] ?? 10);
                        $cacheKey = 'c' . Tenant::id() . '_sidebar_lowstock';
                        $lowStockCount = Cache::remember($cacheKey, 300, function() use ($defaultThreshold) {
                            return (int)Database::getInstance()->query(
                                "SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND is_active = 1 AND current_stock <= COALESCE(low_stock_alert, ?) AND company_id = ?",
                                [$defaultThreshold, Tenant::id()]
                            )->fetchColumn();
                        });
                    }
                    if ($lowStockCount > 0): ?>
                    <span class="nav-badge bg-danger"><?= $lowStockCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=categories">
                    <i class="fas fa-tags nav-icon"></i>
                    <span class="nav-text">Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'brands' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=brands">
                    <i class="fas fa-award nav-icon"></i>
                    <span class="nav-text">Brands</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'units' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=units">
                    <i class="fas fa-ruler nav-icon"></i>
                    <span class="nav-text">Units</span>
                </a>
            </li>
        </ul>

        <!-- People -->
        <div class="sidebar-section-title"><span>People</span></div>
        <ul style="list-style:none; padding:0; margin:0;">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'customers' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=customers">
                    <i class="fas fa-user-group nav-icon"></i>
                    <span class="nav-text">Customers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'suppliers' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=suppliers">
                    <i class="fas fa-truck nav-icon"></i>
                    <span class="nav-text">Suppliers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'hr' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=hr">
                    <i class="fas fa-id-badge nav-icon"></i>
                    <span class="nav-text">HR Tools</span>
                </a>
            </li>
        </ul>

        <!-- Transactions -->
        <div class="sidebar-section-title"><span>Transactions</span></div>
        <ul style="list-style:none; padding:0; margin:0;">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'quotations' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=quotations">
                    <i class="fas fa-file-alt nav-icon"></i>
                    <span class="nav-text">Quotations</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'purchases' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=purchases">
                    <i class="fas fa-cart-shopping nav-icon"></i>
                    <span class="nav-text">Purchases</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'sales' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=sales">
                    <i class="fas fa-receipt nav-icon"></i>
                    <span class="nav-text">Sales</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'sale_returns' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=sale_returns">
                    <i class="fas fa-undo nav-icon"></i>
                    <span class="nav-text">Sale Returns</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'payments' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=payments">
                    <i class="fas fa-money-bill-transfer nav-icon"></i>
                    <span class="nav-text">Payments</span>
                </a>
            </li>
        </ul>

        <!-- Reports -->
        <div class="sidebar-section-title"><span>Reports</span></div>
        <ul style="list-style:none; padding:0; margin:0;">
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'reports') ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=reports">
                    <i class="fas fa-chart-pie nav-icon"></i>
                    <span class="nav-text">Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'insights' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=insights">
                    <i class="fas fa-brain nav-icon"></i>
                    <span class="nav-text">AI Insights</span>
                    <span class="nav-badge bg-info" style="font-size:0.6rem;">AI</span>
                </a>
            </li>
        </ul>

        <!-- Settings -->
        <?php if (Session::hasPermission('users.view') || Session::hasPermission('settings.manage') || Session::hasPermission('backup.manage') || Session::hasPermission('roles.manage')): ?>
        <div class="sidebar-section-title"><span>System</span></div>
        <ul style="list-style:none; padding:0; margin:0;">
            <?php if (Session::hasPermission('users.view')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=users">
                    <i class="fas fa-users-cog nav-icon"></i>
                    <span class="nav-text">Users</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (Session::hasPermission('roles.manage')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'roles' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=roles">
                    <i class="fas fa-user-shield nav-icon"></i>
                    <span class="nav-text">Roles & Permissions</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (Session::hasPermission('settings.manage')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'company' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=company">
                    <i class="fas fa-building nav-icon"></i>
                    <span class="nav-text">Company</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=settings">
                    <i class="fas fa-gear nav-icon"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'api' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=api">
                    <i class="fas fa-code nav-icon"></i>
                    <span class="nav-text">API Access</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (Session::hasPermission('backup.manage')): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'backup' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=backup">
                    <i class="fas fa-shield-halved nav-icon"></i>
                    <span class="nav-text">Backup & Restore</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

        <?php if (Tenant::isDemo()): ?>
        <div style="padding:0.75rem;margin:0.5rem;background:rgba(54,185,204,0.1);border:1px solid rgba(54,185,204,0.2);border-radius:0.5rem;text-align:center;">
            <small style="color:#36b9cc;"><i class="fas fa-flask me-1"></i>Demo Mode</small><br>
            <a href="<?= APP_URL ?>/index.php?page=signup" class="btn btn-sm btn-outline-success mt-1" style="font-size:0.7rem;">Sign Up Free</a>
        </div>
        <?php endif; ?>
    </nav>
</aside>

