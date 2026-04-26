<!DOCTYPE html>
<html lang="en" data-theme="<?= ($currentUser['theme_mode'] ?? 'light') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TSA Legacy - GST billing, inventory and business operations software for Indian SMEs">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= Helper::escape($pageTitle ?? 'Dashboard') ?> | <?= Helper::escape(APP_NAME) ?></title>
    <?php
    $assetSuffix = '?v=' . rawurlencode((string)ASSET_VERSION) . '.' . @filemtime(ASSET_PATH . '/js/app.js');
    $cssAsset = '/assets/css/style.css';
    $appJsAsset = '/assets/js/app.js';
    if (defined('APP_ENV') && APP_ENV === 'production') {
        if (is_file(ASSET_PATH . '/css/style.min.css')) {
            $cssAsset = '/assets/css/style.min.css';
        }
        if (is_file(ASSET_PATH . '/js/app.min.js')) {
            $appJsAsset = '/assets/js/app.min.js';
        }
    }
    ?>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Critical Dark Mode CSS (inline to bypass CDN caching) -->
    <style id="darkModeInline">
    [data-theme="dark"] body, [data-bs-theme="dark"] body {
        background: #0f172a !important; color: #e2e8f0 !important;
    }
    [data-theme="dark"] .main-content, [data-bs-theme="dark"] .main-content {
        background: #0f172a !important;
    }
    [data-theme="dark"] .top-navbar, [data-bs-theme="dark"] .top-navbar {
        background: #1e293b !important; border-bottom: 1px solid rgba(255,255,255,0.06) !important;
    }
    [data-theme="dark"] .card, [data-bs-theme="dark"] .card {
        background: #1e293b !important; border-color: rgba(255,255,255,0.08) !important; color: #e2e8f0 !important;
    }
    [data-theme="dark"] .card-header, [data-bs-theme="dark"] .card-header {
        background: rgba(255,255,255,0.03) !important; border-bottom-color: rgba(255,255,255,0.08) !important; color: #f1f5f9 !important;
    }
    [data-theme="dark"] .card-body, [data-bs-theme="dark"] .card-body { color: #e2e8f0 !important; }
    [data-theme="dark"] .stat-card.stat-primary, [data-bs-theme="dark"] .stat-card.stat-primary {
        background: linear-gradient(135deg, rgba(78,115,223,0.28), rgba(78,115,223,0.14)) !important;
        border-color: rgba(78,115,223,0.3) !important;
    }
    [data-theme="dark"] .stat-card.stat-success, [data-bs-theme="dark"] .stat-card.stat-success {
        background: linear-gradient(135deg, rgba(28,200,138,0.28), rgba(28,200,138,0.14)) !important;
        border-color: rgba(28,200,138,0.3) !important;
    }
    [data-theme="dark"] .stat-card.stat-warning, [data-bs-theme="dark"] .stat-card.stat-warning {
        background: linear-gradient(135deg, rgba(246,194,62,0.28), rgba(246,194,62,0.14)) !important;
        border-color: rgba(246,194,62,0.3) !important;
    }
    [data-theme="dark"] .stat-card.stat-danger, [data-bs-theme="dark"] .stat-card.stat-danger {
        background: linear-gradient(135deg, rgba(231,74,59,0.28), rgba(231,74,59,0.14)) !important;
        border-color: rgba(231,74,59,0.3) !important;
    }
    [data-theme="dark"] .stat-card.stat-info, [data-bs-theme="dark"] .stat-card.stat-info {
        background: linear-gradient(135deg, rgba(54,185,204,0.28), rgba(54,185,204,0.14)) !important;
        border-color: rgba(54,185,204,0.3) !important;
    }
    [data-theme="dark"] .stat-card .stat-label, [data-bs-theme="dark"] .stat-card .stat-label {
        color: #94a3b8 !important; opacity: 1 !important;
    }
    [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3,
    [data-theme="dark"] h4, [data-theme="dark"] h5, [data-theme="dark"] h6,
    [data-theme="dark"] label, [data-theme="dark"] .text-dark,
    [data-bs-theme="dark"] h1, [data-bs-theme="dark"] h2, [data-bs-theme="dark"] h3,
    [data-bs-theme="dark"] h4, [data-bs-theme="dark"] h5, [data-bs-theme="dark"] h6,
    [data-bs-theme="dark"] label, [data-bs-theme="dark"] .text-dark {
        color: #f1f5f9 !important;
    }
    [data-theme="dark"] .table, [data-bs-theme="dark"] .table {
        color: #e2e8f0 !important; --bs-table-bg: transparent !important;
    }
    [data-theme="dark"] .table thead th, [data-bs-theme="dark"] .table thead th {
        background: rgba(255,255,255,0.04) !important; color: #94a3b8 !important;
        border-bottom: 1px solid rgba(255,255,255,0.08) !important;
    }
    [data-theme="dark"] .table td, [data-bs-theme="dark"] .table td {
        border-color: rgba(255,255,255,0.05) !important; color: #e2e8f0 !important;
    }
    [data-theme="dark"] .form-control, [data-theme="dark"] .form-select,
    [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select {
        background: rgba(255,255,255,0.06) !important; border-color: rgba(255,255,255,0.12) !important;
        color: #e2e8f0 !important;
    }
    [data-theme="dark"] .bg-white, [data-bs-theme="dark"] .bg-white { background: #1e293b !important; }
    [data-theme="dark"] .bg-light, [data-bs-theme="dark"] .bg-light { background: rgba(255,255,255,0.04) !important; }
    [data-theme="dark"] .modal-content, [data-bs-theme="dark"] .modal-content {
        background: #1e293b !important; border-color: rgba(255,255,255,0.1) !important; color: #e2e8f0 !important;
    }
    [data-theme="dark"] .dropdown-menu, [data-bs-theme="dark"] .dropdown-menu {
        background: #1e293b !important; border-color: rgba(255,255,255,0.1) !important;
    }
    [data-theme="dark"] .dropdown-item, [data-bs-theme="dark"] .dropdown-item { color: #e2e8f0 !important; }
    [data-theme="dark"] .dropdown-item:hover, [data-bs-theme="dark"] .dropdown-item:hover {
        background: rgba(255,255,255,0.06) !important;
    }
    [data-theme="dark"] .app-footer, [data-bs-theme="dark"] .app-footer {
        background: #1e293b !important; border-top: 1px solid rgba(255,255,255,0.06) !important; color: #64748b !important;
    }
    [data-theme="dark"] .text-muted, [data-bs-theme="dark"] .text-muted { color: #64748b !important; }
    </style>
    <!-- Font Awesome 6 -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" /></noscript>
    <!-- Custom CSS -->
    <link href="<?= APP_URL . $cssAsset . $assetSuffix ?>" rel="stylesheet">
    
    <!-- PWA Setup -->
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/favicon.svg">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/icon.svg">
    <link rel="mask-icon" href="<?= APP_URL ?>/assets/safari-pinned-tab.svg" color="#4f46e5">
</head>
<body>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border-custom"></div>
    </div>

    <!-- Flash Messages -->
    <?php
    $flashTypes = ['success', 'error', 'warning', 'info'];
    $hasFlash = false;
    foreach ($flashTypes as $ft) {
        if (Session::hasFlash($ft)) { $hasFlash = true; break; }
    }
    if ($hasFlash): ?>
    <div class="alert-container" id="flashContainer">
        <?php foreach ($flashTypes as $type): 
            $msg = Session::getFlash($type);
            if ($msg):
                $alertClass = $type === 'error' ? 'danger' : $type;
                $icon = ['success'=>'check-circle','error'=>'exclamation-circle','warning'=>'exclamation-triangle','info'=>'info-circle'][$type];
        ?>
        <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $icon ?> me-2"></i><?= Helper::escape($msg) ?>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <?php require VIEW_PATH . '/layouts/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <?php require VIEW_PATH . '/layouts/navbar.php'; ?>

        <!-- Content -->
        <div class="content-wrapper">
            <?php if (Tenant::isDemo()): ?>
            <div class="alert alert-primary border-0 shadow-sm mb-3" role="alert">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                    <div>
                        <strong><i class="fas fa-flask me-2"></i>Interactive Demo Workspace</strong>
                        <div class="small mt-1">
                            You can test products, billing, sales, purchases, inventory, and daily workflows here.
                            Demo data may reset. Billing, account, and settings changes stay restricted.
                        </div>
                    </div>
                    <a href="<?= APP_URL ?>/signup?from_demo=1" class="btn btn-sm btn-success">Create Your Free Workspace</a>
                </div>
            </div>
            <?php endif; ?>
            <?= $content ?>
        </div>

        <!-- Footer -->
        <?php require VIEW_PATH . '/layouts/footer.php'; ?>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <!-- Chart.js (only when needed) -->
    <?php if (isset($inlineScript) && strpos($inlineScript, 'Chart') !== false): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <?php endif; ?>
    <!-- SweetAlert2 (must load before app.js since both use defer — execution is in source order) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js" defer></script>
    <!-- App JS -->
    <script src="<?= APP_URL . $appJsAsset . $assetSuffix ?>" defer></script>

    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= APP_URL ?>/assets/js/<?= $script ?><?= $assetSuffix ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($inlineScript)): ?>
    <script nonce="<?= $cspNonce ?? '' ?>"><?= $inlineScript ?></script>
    <?php endif; ?>

    <?php
    // SweetAlert for conversion success (intercept flash)
    $flashSuccess = Session::getFlash('_swal_success');
    if ($flashSuccess): ?>
    <script nonce="<?= $cspNonce ?? '' ?>">
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Converted!', text: <?= json_encode($flashSuccess) ?>, icon: 'success', confirmButtonColor: '#198754', timer: 4000, timerProgressBar: true });
        }
    });
    </script>
    <?php endif; ?>

    <script nonce="<?= $cspNonce ?? '' ?>">
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= APP_URL ?>/sw.js')
                    .then(reg => console.log('PWA ServiceWorker registered'))
                    .catch(err => console.log('PWA ServiceWorker failed: ', err));
            });
        }
    </script>
</body>
</html>
