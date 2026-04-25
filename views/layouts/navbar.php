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
        <div class="page-title-stack">
            <h1 class="page-title"><?= Helper::escape($pageTitle ?? 'Dashboard') ?></h1>
        </div>
    </div>

    <div class="navbar-right">

        <!-- Global Search (Ctrl+K) -->
        <button class="navbar-btn d-none d-md-flex align-items-center gap-2 px-3 search-trigger-btn" 
                id="globalSearchTrigger" 
                title="Global Search (Ctrl+K)"
                style="border:1px solid rgba(255,255,255,0.15);border-radius:8px;font-size:0.8rem;color:var(--text-muted);min-width:160px;justify-content:space-between;">
            <span><i class="fas fa-search me-2" style="font-size:0.75rem;"></i>Search anything...</span>
            <kbd style="background:rgba(255,255,255,0.1);border-radius:4px;padding:2px 6px;font-size:0.7rem;">Ctrl+K</kbd>
        </button>

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
                <div class="user-avatar"><?= Helper::escape($initials) ?></div>
                <div class="user-info d-none d-sm-block">
                    <div class="user-name"><?= Helper::escape($user['full_name'] ?? 'User') ?></div>
                    <div class="user-role"><?= Helper::escape($user['role'] ?? 'staff') ?></div>
                </div>
                <i class="fas fa-chevron-down ms-1 navbar-chevron"></i>
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

<!-- ====================================================== -->
<!-- GLOBAL SEARCH MODAL -->
<!-- ====================================================== -->
<div class="modal fade" id="globalSearchModal" tabindex="-1" aria-label="Global Search" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width:680px;">
        <div class="modal-content" style="border-radius:16px;border:1px solid rgba(255,255,255,0.1);background:var(--bg-card);box-shadow:0 25px 60px rgba(0,0,0,0.4);">
            <div class="modal-body p-0">
                <!-- Search Input -->
                <div class="d-flex align-items-center px-4 py-3 border-bottom" style="border-color:rgba(255,255,255,0.08)!important;">
                    <i class="fas fa-search me-3" style="color:var(--text-muted);font-size:1rem;"></i>
                    <input type="text" 
                           id="globalSearchInput" 
                           class="form-control border-0 shadow-none p-0 bg-transparent" 
                           placeholder="Search customers, products, sales, pages..."
                           style="font-size:1rem;color:var(--text-primary);"
                           autocomplete="off">
                    <kbd style="background:rgba(255,255,255,0.07);border-radius:6px;padding:3px 8px;font-size:0.75rem;color:var(--text-muted);white-space:nowrap;">ESC</kbd>
                </div>

                <!-- Search Results -->
                <div id="globalSearchResults" style="max-height:420px;overflow-y:auto;">
                    <!-- Quick Navigation Links -->
                    <div id="searchDefaultState" class="px-4 py-3">
                        <p class="text-muted mb-2" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Quick Navigation</p>
                        <div class="row g-2">
                            <?php
                            $quickLinks = [
                                ['page'=>'dashboard','icon'=>'fas fa-chart-line','label'=>'Dashboard','color'=>'#667eea'],
                                ['page'=>'sales','icon'=>'fas fa-receipt','label'=>'Sales','color'=>'#28a745'],
                                ['page'=>'purchases','icon'=>'fas fa-cart-shopping','label'=>'Purchases','color'=>'#fd7e14'],
                                ['page'=>'products','icon'=>'fas fa-box','label'=>'Products','color'=>'#6610f2'],
                                ['page'=>'customers','icon'=>'fas fa-users','label'=>'Customers','color'=>'#17a2b8'],
                                ['page'=>'reports','icon'=>'fas fa-chart-bar','label'=>'Reports','color'=>'#dc3545'],
                                ['page'=>'sales&action=create','icon'=>'fas fa-plus-circle','label'=>'New Sale','color'=>'#20c997'],
                                ['page'=>'purchases&action=create','icon'=>'fas fa-plus','label'=>'New Purchase','color'=>'#fd7e14'],
                            ];
                            foreach ($quickLinks as $link):
                            ?>
                            <div class="col-6">
                                <a href="<?= APP_URL ?>/index.php?page=<?= $link['page'] ?>" 
                                   class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none search-quick-link"
                                   data-bs-dismiss="modal"
                                   style="transition:background 0.15s;">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width:28px;height:28px;background:<?= $link['color'] ?>22;flex-shrink:0;">
                                        <i class="<?= $link['icon'] ?>" style="font-size:0.75rem;color:<?= $link['color'] ?>;"></i>
                                    </div>
                                    <span style="font-size:0.875rem;color:var(--text-primary);"><?= $link['label'] ?></span>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Dynamic Results Container -->
                    <div id="searchDynamicResults" class="px-2 py-2 d-none"></div>
                    <!-- Loading -->
                    <div id="searchLoading" class="text-center py-4 d-none">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2 text-muted">Searching...</span>
                    </div>
                    <!-- No results -->
                    <div id="searchNoResults" class="text-center py-4 d-none">
                        <i class="fas fa-search-minus" style="font-size:2rem;color:var(--text-muted);opacity:0.4;"></i>
                        <p class="text-muted mt-2 mb-0">No results found</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2 d-flex align-items-center gap-3 border-top" 
                     style="border-color:rgba(255,255,255,0.08)!important;font-size:0.75rem;color:var(--text-muted);">
                    <span><kbd style="background:rgba(255,255,255,0.07);border-radius:4px;padding:1px 5px;">↑↓</kbd> Navigate</span>
                    <span><kbd style="background:rgba(255,255,255,0.07);border-radius:4px;padding:1px 5px;">Enter</kbd> Open</span>
                    <span><kbd style="background:rgba(255,255,255,0.07);border-radius:4px;padding:1px 5px;">Esc</kbd> Close</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── Global Search Logic ──
(function() {
    var searchInput = document.getElementById('globalSearchInput');
    var defaultState = document.getElementById('searchDefaultState');
    var dynamicResults = document.getElementById('searchDynamicResults');
    var loadingEl = document.getElementById('searchLoading');
    var noResults = document.getElementById('searchNoResults');
    var searchDebounce = null;

    if (!searchInput) return;

    document.getElementById('globalSearchTrigger')?.addEventListener('click', function() {
        var m = new bootstrap.Modal(document.getElementById('globalSearchModal'));
        m.show();
        setTimeout(function() { searchInput.focus(); }, 300);
    });

    searchInput.addEventListener('input', function() {
        var q = this.value.trim();
        clearTimeout(searchDebounce);
        
        if (q.length < 2) {
            defaultState.classList.remove('d-none');
            dynamicResults.classList.add('d-none');
            loadingEl.classList.add('d-none');
            noResults.classList.add('d-none');
            return;
        }

        defaultState.classList.add('d-none');
        loadingEl.classList.remove('d-none');
        dynamicResults.classList.add('d-none');
        noResults.classList.add('d-none');

        searchDebounce = setTimeout(function() {
            doSearch(q);
        }, 280);
    });

    function doSearch(q) {
        var urls = [
            { url: '<?= APP_URL ?>/index.php?page=customers&action=search_api&q=' + encodeURIComponent(q), type: 'customer', icon: 'fas fa-user', color: '#17a2b8', base: '<?= APP_URL ?>/index.php?page=customers&action=view_customer&id=' },
            { url: '<?= APP_URL ?>/index.php?page=products&action=search_api&q=' + encodeURIComponent(q), type: 'product', icon: 'fas fa-box', color: '#6610f2', base: '<?= APP_URL ?>/index.php?page=products&action=view_product&id=' },
        ];

        // Simple client-side filter on quick links
        var allLinks = document.querySelectorAll('.search-quick-link');
        var matched = [];
        allLinks.forEach(function(link) {
            var label = link.querySelector('span')?.textContent?.toLowerCase() || '';
            if (label.includes(q.toLowerCase())) {
                matched.push({
                    type: 'page',
                    icon: link.querySelector('i')?.className || 'fas fa-link',
                    color: '#667eea',
                    label: link.querySelector('span')?.textContent,
                    url: link.href
                });
            }
        });

        loadingEl.classList.add('d-none');
        
        if (matched.length > 0) {
            renderResults(matched);
        } else {
            noResults.classList.remove('d-none');
        }
    }

    function renderResults(items) {
        if (items.length === 0) {
            noResults.classList.remove('d-none');
            dynamicResults.classList.add('d-none');
            return;
        }
        
        var html = '';
        items.forEach(function(item) {
            html += '<a href="' + (item.url || '#') + '" class="d-flex align-items-center gap-3 px-3 py-2 text-decoration-none search-result-item rounded mx-2 mb-1" data-bs-dismiss="modal" style="transition:background 0.12s;">' +
                '<div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:' + item.color + '22;flex-shrink:0;">' +
                '<i class="' + item.icon + '" style="font-size:0.8rem;color:' + item.color + ';"></i></div>' +
                '<div>' +
                '<div style="font-size:0.875rem;color:var(--text-primary);">' + (item.label || '') + '</div>' +
                '<div style="font-size:0.75rem;color:var(--text-muted);text-transform:capitalize;">' + (item.type || '') + '</div>' +
                '</div></a>';
        });
        
        dynamicResults.innerHTML = html;
        dynamicResults.classList.remove('d-none');
        noResults.classList.add('d-none');
    }
})();
</script>

