<!DOCTYPE html>
<html lang="en" data-theme="<?= ($currentUser['theme_mode'] ?? 'light') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="InvenBill Pro - Professional Inventory & Billing Management System">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= Helper::escape($pageTitle ?? 'Dashboard') ?> | <?= Helper::escape(APP_NAME) ?></title>
    <?php
    $assetSuffix = '?v=' . rawurlencode((string)ASSET_VERSION);
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
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Custom CSS -->
    <link href="<?= APP_URL . $cssAsset . $assetSuffix ?>" rel="stylesheet">
    
    <!-- PWA Setup -->
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <meta name="theme-color" content="#4e73df">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/icon.svg">
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
