<?php
/**
 * Top Navbar Component
 */
$user = Session::get('user');
$initials = '';
if (!empty($user['full_name'])) {
    $parts = explode(' ', $user['full_name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
?>
<header class="top-navbar" id="topNavbar">
    <div class="navbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title d-none d-md-block"><?= $pageTitle ?? 'Dashboard' ?></h1>
    </div>

    <div class="navbar-right">
        <!-- Theme Toggle -->
        <div class="theme-toggle me-2" title="Toggle Dark Mode">
            <input type="checkbox" id="themeSwitch" <?= ($user['theme_mode'] ?? 'light') === 'dark' ? 'checked' : '' ?>>
            <label class="theme-slider" for="themeSwitch"></label>
        </div>

        <!-- Notifications placeholder -->
        <button class="navbar-btn" title="Notifications">
            <i class="fas fa-bell"></i>
            <?php
            $lowCount = count((new ProductModel())->getLowStock(100));
            if ($lowCount > 0): ?>
            <span class="badge-dot"></span>
            <?php endif; ?>
        </button>

        <!-- Fullscreen -->
        <button class="navbar-btn d-none d-md-block" id="fullscreenBtn" title="Fullscreen">
            <i class="fas fa-expand"></i>
        </button>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar"><?= $initials ?></div>
                <div class="user-info d-none d-sm-block">
                    <div class="user-name"><?= Helper::escape($user['full_name'] ?? 'User') ?></div>
                    <div class="user-role"><?= Helper::escape($user['role'] ?? 'staff') ?></div>
                </div>
                <i class="fas fa-chevron-down ms-1" style="font-size:0.65rem; color: var(--text-muted);"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/index.php?page=profile">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/index.php?page=profile&action=password">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                </li>
                <?php if (Session::hasPermission('settings.manage')): ?>
                <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/index.php?page=settings">
                        <i class="fas fa-gear"></i> Settings
                    </a>
                </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?= APP_URL ?>/index.php?page=logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
