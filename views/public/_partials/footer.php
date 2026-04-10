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
                <a href="<?= APP_URL ?>/" class="logo" style="margin-bottom:10px"><img src="<?= htmlspecialchars($_logoUrl, ENT_QUOTES) ?>" alt="TSA Legacy" style="height:32px"></a>
                <p style="color:var(--mt);font-size:.78rem;line-height:1.6;margin:10px 0">Cloud-native business management platform built for Indian SMEs.</p>
                <p style="color:var(--mt);font-size:.72rem"><i class="fas fa-map-marker-alt" style="margin-right:4px"></i>India-based Startup</p>
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
                    <a href="mailto:triloki@tsalegacy.com"><i class="fas fa-envelope" style="margin-right:4px"></i>triloki@tsalegacy.com</a>
                    <span style="color:var(--mt);font-size:.78rem"><i class="fas fa-building" style="margin-right:4px"></i>TSA Legacy Ventures</span>
                    <span style="color:var(--mt);font-size:.78rem"><i class="fas fa-certificate" style="margin-right:4px"></i>MSME / Udyam Registered</span>
                    <span style="color:var(--mt);font-size:.78rem"><i class="fas fa-flag" style="margin-right:4px"></i>Made with ❤️ in India</span>
                </div>
            </div>
        </div>
        <div class="ft-bar">
            <p class="ft-copy">© 2025–<?= date('Y') ?> TSA Legacy Ventures. All rights reserved.</p>
            <div style="display:flex;align-items:center;gap:8px"><span class="ft-copy">Powered by</span><span style="color:var(--mt);font-size:.75rem;font-weight:600"><i class="fab fa-google" style="margin-right:3px"></i>Google Cloud</span></div>
        </div>
    </div>
</footer>
