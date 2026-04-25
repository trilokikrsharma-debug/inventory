<?php
/**
 * Shared public footer partial.
 * Used by: home.php, pricing.php, seo_page.php, blog_index.php, blog_article.php
 * Contact email: triloki@tsalegacy.com
 */
$_logoUrl = $logoUrl ?? rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
?>
<footer>
    <div class="mx">
        <div class="ft-grid">
            <div>
                <a href="<?= APP_URL ?>/" class="logo ft-logo"><img src="<?= htmlspecialchars($_logoUrl, ENT_QUOTES) ?>" alt="<?= htmlspecialchars(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME, ENT_QUOTES) ?>"></a>
                <p class="ft-intro">Cloud-native business management platform built for Indian SMEs.</p>
                <p class="ft-meta icon-gap-4"><i class="fas fa-map-marker-alt"></i>India-based Startup</p>
            </div>
            <div>
                <div class="ft-title">Product</div>
                <div class="ft-links">
                    <a href="<?= APP_URL ?>/#features">Features</a>
                    <a href="<?= APP_URL ?>/pricing">Pricing</a>
                    <a href="<?= APP_URL ?>/blog">Guides</a>
                    <a href="<?= APP_URL ?>/demo">Live Demo</a>
                    <a href="<?= APP_URL ?>/signup">Sign Up</a>
                </div>
            </div>
            <div>
                <div class="ft-title">Company</div>
                <div class="ft-links">
                    <a href="<?= APP_URL ?>/#about">About Us</a>
                    <a href="<?= APP_URL ?>/privacy">Privacy Policy</a>
                    <a href="<?= APP_URL ?>/terms">Terms of Service</a>
                    <a href="<?= APP_URL ?>/refund">Refund Policy</a>
                </div>
            </div>
            <div>
                <div class="ft-title">Contact</div>
                <div class="ft-links">
                    <a href="mailto:triloki@tsalegacy.com" class="icon-gap-4"><i class="fas fa-envelope"></i>triloki@tsalegacy.com</a>
                    <span class="ft-meta ft-contact-item icon-gap-4"><i class="fas fa-building"></i><?= htmlspecialchars(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME, ENT_QUOTES) ?></span>
                    <span class="ft-meta ft-contact-item icon-gap-4"><i class="fas fa-certificate"></i>MSME / Udyam Registered</span>
                    <span class="ft-meta ft-contact-item icon-gap-4"><i class="fas fa-flag"></i>Made with ❤️ in India</span>
                </div>
            </div>
        </div>
        <div class="ft-bar">
            <p class="ft-copy">© 2025–<?= date('Y') ?> <?= htmlspecialchars(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME, ENT_QUOTES) ?>. All rights reserved.</p>
            <div class="ft-inline"><span class="ft-copy">Powered by</span><span class="ft-cloud"><i class="fab fa-google"></i>Google Cloud</span></div>
        </div>
    </div>
</footer>
