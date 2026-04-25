<?php
/**
 * Sidebar Navigation Component
 * 
 * Renders the collapsible sidebar with all navigation links.
 * Active state is determined by the current page.
 */
$currentPage = $_GET['page'] ?? 'dashboard';
$currentAction = $_GET['action'] ?? 'index';
$isSuperAdmin = Session::isSuperAdmin();
$hasReportsFeature = $isSuperAdmin || Tenant::canUse('basic_reports') || Tenant::canUse('advanced_reports');
$hasQuotationsFeature = $isSuperAdmin || Tenant::canUse('quotations');
$hasSaleReturnsFeature = $isSuperAdmin || Tenant::canUse('sale_returns');
$hasHrFeature = $isSuperAdmin || Tenant::canUse('hr');
$hasWarehouseFeature = $isSuperAdmin || (Tenant::id() !== null && Tenant::canUse('multi_warehouse'));
$hasApiFeature = $isSuperAdmin || (Tenant::id() !== null && Tenant::canUse('api'));
$hasBackupFeature = $isSuperAdmin || Tenant::canUse('backup_restore');
$hasInsightsFeature = Tenant::id() !== null && ($isSuperAdmin || Tenant::canUse('ai_insights'));
?>
<aside class="sidebar" id="sidebar">
    <!-- Brand / Logo -->
    <a href="<?= APP_URL ?>" class="sidebar-brand">
        <div class="brand-icon">
            <img src="<?= APP_URL ?>/assets/icon.svg" alt="<?= Helper::escape(APP_NAME) ?>" class="brand-mark">
        </div>
        <span class="brand-text"><?= APP_NAME ?></span>
    </a>

    <?php if ($isSuperAdmin): ?>
    <div class="sidebar-super-admin-banner">
        <span class="sidebar-super-admin-badge">
            <i class="fas fa-crown"></i> Super Admin
        </span>
    </div>
    <?php endif; ?>

    <?php if (Session::get('_impersonating_from')): ?>
    <?php $tenantCompany = Tenant::company(); ?>
    <div class="sidebar-impersonation-box">
        <div class="sidebar-impersonation-title">
            <i class="fas fa-eye"></i> Impersonating Tenant
        </div>
        <div class="sidebar-impersonation-name">
            <?= htmlspecialchars($tenantCompany['name'] ?? 'Unknown', ENT_QUOTES) ?>
        </div>
        <a href="<?= APP_URL ?>/index.php?page=platform&action=stop_impersonation"
           class="sidebar-impersonation-link"
           id="btn-stop-impersonation">
            <i class="fas fa-arrow-left"></i> Return to Admin
        </a>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- Main -->
        <div class="sidebar-section-title"><span>Main</span></div>
        <ul class="sidebar-list">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=dashboard">
                    <i class="fas fa-th-large nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- Platform Admin (Super Admins Only) -->
        <?php if ($isSuperAdmin): ?>
        <div class="sidebar-section-title"><span>Platform</span></div>
        <ul class="sidebar-list">
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

        <?php if (!$isSuperAdmin): ?>
        <div class="sidebar-section-title"><span>Billing</span></div>
        <ul class="sidebar-list">
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
        <ul class="sidebar-list">
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
            <?php if ($hasWarehouseFeature): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'warehouses' ? 'active' : '' ?>" href="<?= $isSuperAdmin ? APP_URL . '/index.php?page=platform&action=tenants' : APP_URL . '/index.php?page=warehouses' ?>">
                    <i class="fas fa-warehouse nav-icon"></i>
                    <span class="nav-text">Warehouses</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- People -->
        <div class="sidebar-section-title"><span>People</span></div>
        <ul class="sidebar-list">
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
            <?php if ($hasHrFeature): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'hr' ? 'active' : '' ?>" href="<?= $isSuperAdmin ? APP_URL . '/index.php?page=platform&action=tenants' : APP_URL . '/index.php?page=hr' ?>">
                    <i class="fas fa-id-badge nav-icon"></i>
                    <span class="nav-text">HR Tools</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Transactions -->
        <div class="sidebar-section-title"><span>Transactions</span></div>
        <ul class="sidebar-list">
            <?php if ($hasQuotationsFeature): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'quotations' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=quotations">
                    <i class="fas fa-file-alt nav-icon"></i>
                    <span class="nav-text">Quotations</span>
                </a>
            </li>
            <?php endif; ?>
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
            <?php if ($hasSaleReturnsFeature): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'sale_returns' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=sale_returns">
                    <i class="fas fa-undo nav-icon"></i>
                    <span class="nav-text">Sale Returns</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'payments' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=payments">
                    <i class="fas fa-money-bill-transfer nav-icon"></i>
                    <span class="nav-text">Payments</span>
                </a>
            </li>
        </ul>

        <!-- Reports -->
        <?php if ($hasReportsFeature): ?>
        <div class="sidebar-section-title"><span>Reports</span></div>
        <ul class="sidebar-list">
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'reports') ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=reports">
                    <i class="fas fa-chart-pie nav-icon"></i>
                    <span class="nav-text">Reports</span>
                </a>
            </li>
            <?php if ($hasInsightsFeature): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'insights' ? 'active' : '' ?>" href="<?= APP_URL ?>/index.php?page=insights">
                    <i class="fas fa-brain nav-icon"></i>
                    <span class="nav-text">Automated Insights</span>
                    <span class="nav-badge nav-badge-xs bg-info">NEW</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

        <!-- Settings -->
        <?php if (Session::hasPermission('users.view') || Session::hasPermission('settings.manage') || Session::hasPermission('backup.manage') || Session::hasPermission('roles.manage')): ?>
        <div class="sidebar-section-title"><span>System</span></div>
        <ul class="sidebar-list">
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
            <?php if ($hasApiFeature): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'api' ? 'active' : '' ?>" href="<?= $isSuperAdmin ? APP_URL . '/index.php?page=platform&action=tenants' : APP_URL . '/index.php?page=api' ?>">
                    <i class="fas fa-code nav-icon"></i>
                    <span class="nav-text">API Access</span>
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (Session::hasPermission('backup.manage') && $hasBackupFeature): ?>
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
        <div class="sidebar-demo-box">
            <small class="sidebar-demo-note"><i class="fas fa-flask me-1"></i>Demo Mode</small><br>
            <small class="sidebar-demo-note d-block mt-1">Try products, billing workflows, and day-to-day operations. Billing, account, and settings changes are restricted.</small>
            <a href="<?= APP_URL ?>/signup?from_demo=1" class="btn btn-sm btn-outline-success mt-1 sidebar-demo-cta">Sign Up Free</a>
        </div>
        <?php endif; ?>
    </nav>
</aside>
