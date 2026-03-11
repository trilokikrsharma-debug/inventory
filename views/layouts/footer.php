<footer class="app-footer">
    <span>&copy; <?= date('Y') ?> <?= Helper::escape($company['company_name'] ?? APP_NAME) ?>. All rights reserved.</span>
    <span class="d-none d-md-inline"><?= APP_NAME ?> v<?= APP_VERSION ?></span>
</footer>
