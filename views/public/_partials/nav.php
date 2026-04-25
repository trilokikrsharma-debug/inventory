<?php
/**
 * Shared public navigation partial.
 * Used by: pricing.php, seo_page.php, blog_index.php, blog_article.php
 * Homepage uses its own inline nav with local #anchors.
 */
$_logoUrl = $logoUrl ?? rtrim(APP_URL, '/') . '/assets/logo-lockup-compact.svg';
?>
<nav id="mainNav">
    <div class="nav-i">
        <a href="<?= APP_URL ?>/" class="logo"><img src="<?= htmlspecialchars($_logoUrl, ENT_QUOTES) ?>" alt="<?= htmlspecialchars(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME, ENT_QUOTES) ?>"></a>
        <div class="nav-l">
            <a href="<?= APP_URL ?>/#features">Features</a>
            <a href="<?= APP_URL ?>/pricing">Pricing</a>
            <a href="<?= APP_URL ?>/#about">About</a>
            <a href="<?= APP_URL ?>/blog">Guides</a>
        </div>
        <div class="nav-c">
            <a href="<?= APP_URL ?>/login" class="btn-g">Sign In</a>
            <a href="<?= APP_URL ?>/signup" class="btn-p">Start Free Trial</a>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Menu"><i class="fas fa-bars"></i></button>
    </div>
</nav>
<div class="mob-menu" id="mobMenu">
    <a href="<?= APP_URL ?>/#features">Features</a>
    <a href="<?= APP_URL ?>/pricing">Pricing</a>
    <a href="<?= APP_URL ?>/#about">About</a>
    <a href="<?= APP_URL ?>/blog">Guides</a>
    <a href="<?= APP_URL ?>/login">Sign In</a>
    <a href="<?= APP_URL ?>/signup" class="mob-cta-link">Start Free Trial →</a>
</div>
