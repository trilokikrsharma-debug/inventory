<?php
if (!function_exists('tsa_brand_assets')) {
    function tsa_brand_assets(): array
    {
        $base = rtrim(APP_URL, '/');
        $brandCssPath = BASE_PATH . '/assets/css/public-brand.css';
        $brandCssVersion = defined('ASSET_VERSION') ? (string)ASSET_VERSION : '1';
        $brandCssMtime = @filemtime($brandCssPath);
        if ($brandCssMtime !== false) {
            $brandCssVersion .= '.' . $brandCssMtime;
        }
        return [
        'logo_png'     => APP_URL . '/assets/img/logo.png',
        'icon_png'     => APP_URL . '/assets/img/app-icon.png',
            'favicon' => $base . '/assets/favicon.svg',
            'icon' => $base . '/assets/icon.svg',
            'logo_light' => $base . '/assets/logo-lockup.svg',
            'logo_dark' => $base . '/assets/logo-lockup.svg',
            'logo_compact' => $base . '/assets/logo-lockup-compact.svg',
            'logo_mark' => $base . '/assets/logo-mark-refined.svg',
            'og' => $base . '/assets/og-default.svg',
            'brand_css' => $base . '/assets/css/public-brand.css?v=' . rawurlencode($brandCssVersion),
        ];
    }
}

if (!function_exists('tsa_h')) {
    function tsa_h(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES);
    }
}


if (!function_exists('tsa_render_adsense_verification')) {
    function tsa_render_adsense_verification(): void
    {
        ?>
        <meta name="google-adsense-account" content="ca-pub-9384564101816113">
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9384564101816113"
            crossorigin="anonymous"></script>
        <?php
    }
}

if (!function_exists('tsa_render_public_nav')) {
    function tsa_render_public_nav(array $options = []): void
    {
        $assets = tsa_brand_assets();
        $links = $options['links'] ?? [
            ['href' => APP_URL . '/', 'label' => 'Home'],
            ['href' => APP_URL . '/pricing', 'label' => 'Pricing'],
            ['href' => APP_URL . '/blog', 'label' => 'Guides'],
        ];
        $primaryHref = $options['primary_href'] ?? APP_URL . '/signup';
        $primaryLabel = $options['primary_label'] ?? 'Create Workspace';
        $secondaryHref = $options['secondary_href'] ?? APP_URL . '/demo';
        $secondaryLabel = $options['secondary_label'] ?? 'Instant Demo Access';
        $activeHref = $options['active_href'] ?? '';
        $navId = $options['nav_id'] ?? 'tsaPublicNav';
        $menuId = $options['menu_id'] ?? 'tsaPublicMenu';
        $mainId = $options['main_id'] ?? 'main-content';
        ?>
        <a href="#<?= tsa_h($mainId) ?>" class="tsa-skip-link">Skip to main content</a>
        <div class="tsa-nav-wrap">
            <nav class="tsa-nav" id="<?= tsa_h($navId) ?>">
                <div class="tsa-nav-inner">
                    <a href="<?= tsa_h(APP_URL . '/') ?>" class="tsa-logo tsa-logo-compact">
                        <img src="<?= tsa_h($assets['logo_compact']) ?>" alt="<?= tsa_h(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
                    </a>
                    <div class="tsa-nav-links">
                        <?php foreach ($links as $link): ?>
                            <a href="<?= tsa_h((string)$link['href']) ?>" class="<?= ((string)$link['href'] === (string)$activeHref) ? 'is-active' : '' ?>"><?= tsa_h((string)$link['label']) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <div class="tsa-nav-cta">
                        <a href="<?= tsa_h($secondaryHref) ?>" class="tsa-btn tsa-btn-ghost"><?= tsa_h($secondaryLabel) ?></a>
                        <a href="<?= tsa_h($primaryHref) ?>" class="tsa-btn tsa-btn-primary tsa-btn-trial"><?= tsa_h($primaryLabel) ?></a>
                    </div>
                    <button
                        class="tsa-hamburger"
                        type="button"
                        data-menu-toggle="<?= tsa_h($menuId) ?>"
                        aria-label="Open menu"
                        aria-controls="<?= tsa_h($menuId) ?>"
                        aria-expanded="false"
                    >
                        <span class="tsa-hamburger-box" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>
                </div>
            </nav>
            <div class="tsa-mobile-menu" id="<?= tsa_h($menuId) ?>" hidden aria-hidden="true">
                <?php foreach ($links as $link): ?>
                    <a href="<?= tsa_h((string)$link['href']) ?>" data-menu-close="<?= tsa_h($menuId) ?>"><?= tsa_h((string)$link['label']) ?></a>
                <?php endforeach; ?>
                <a href="<?= tsa_h($secondaryHref) ?>" class="tsa-mobile-menu-secondary" data-menu-close="<?= tsa_h($menuId) ?>"><?= tsa_h($secondaryLabel) ?></a>
                <a href="<?= tsa_h($primaryHref) ?>" class="tsa-mobile-menu-primary" data-menu-close="<?= tsa_h($menuId) ?>"><?= tsa_h($primaryLabel) ?></a>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('tsa_render_public_footer')) {
    function tsa_render_public_footer(array $options = []): void
    {
        $assets = tsa_brand_assets();
        $showGuides = $options['show_guides'] ?? true;
        ?>
        <footer class="tsa-footer">
            <div class="tsa-footer-shell">
                <div class="tsa-footer-grid">
                    <div>
                        <a href="<?= tsa_h(APP_URL . '/') ?>" class="tsa-logo tsa-footer-brand"><img src="<?= tsa_h($assets['logo_light']) ?>" alt="<?= tsa_h(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>"></a>
                        <p>Cloud-based GST billing, inventory and business operations software designed for Indian SMEs that want cleaner workflows and stronger control.</p>
                    </div>
                    <div>
                        <div class="tsa-footer-title">Product</div>
                        <div class="tsa-footer-links">
                            <a href="<?= tsa_h(APP_URL . '/') ?>">Home</a>
                            <a href="<?= tsa_h(APP_URL . '/pricing') ?>">Pricing</a>
                            <a href="<?= tsa_h(APP_URL . '/demo') ?>">Instant Demo Access</a>
                            <a href="<?= tsa_h(APP_URL . '/signup') ?>">Create Workspace</a>
                        </div>
                    </div>
                    <div>
                        <div class="tsa-footer-title">Company</div>
                        <div class="tsa-footer-links">
                            <a href="<?= tsa_h(APP_URL . '/#about') ?>">About Us</a>
                            <?php if ($showGuides): ?><a href="<?= tsa_h(APP_URL . '/blog') ?>">Guides</a><?php endif; ?>
                            <a href="<?= tsa_h(APP_URL . '/privacy') ?>">Privacy Policy</a>
                            <a href="<?= tsa_h(APP_URL . '/terms') ?>">Terms of Service</a>
                            <a href="<?= tsa_h(APP_URL . '/refund') ?>">Refund Policy</a>
                        </div>
                    </div>
                    <div>
                        <div class="tsa-footer-title">Trust Markers</div>
                        <div class="tsa-footer-links">
                            <span>Role-based access</span>
                            <span>Audit-ready workflows</span>
                            <span>Cloud-hosted delivery</span>
                            <a href="mailto:triloki@tsalegacy.com">triloki@tsalegacy.com</a>
                            <a href="https://linkedin.com/company/tsalegacy-ventures" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                            <a href="https://docs.tsalegacy.com" target="_blank" rel="noopener noreferrer">System Docs</a>
                        </div>
                    </div>
                </div>
                <div class="tsa-footer-bar">
                    <div class="tsa-footer-copy">© 2025–<?= date('Y') ?> <?= tsa_h(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>. All rights reserved.</div>
                    <div class="tsa-footer-copy">Built in India for retailers, wholesalers and growing SMEs. Simple, structured, reliable.</div>
                </div>
            </div>
        </footer>
        <?php
    }
}

if (!function_exists('tsa_render_trust_strip')) {
    function tsa_render_trust_strip(array $items, string $heading, string $copy, ?string $ctaHref = null, ?string $ctaLabel = null): void
    {
        ?>
        <section class="tsa-trust-strip">
            <div class="tsa-container">
                <div class="tsa-trust-surface">
                    <div class="tsa-trust-copy">
                        <div>
                            <h2><?= tsa_h($heading) ?></h2>
                            <p><?= tsa_h($copy) ?></p>
                        </div>
                        <?php if ($ctaHref && $ctaLabel): ?>
                            <a href="<?= tsa_h($ctaHref) ?>" class="tsa-btn tsa-btn-secondary"><?= tsa_h($ctaLabel) ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="tsa-trust-grid">
                        <?php foreach ($items as $item): ?>
                            <div class="tsa-card tsa-trust-item">
                                <div class="tsa-icon-chip"><i class="fas <?= tsa_h((string)($item['icon'] ?? 'fa-circle-check')) ?>"></i></div>
                                <strong><?= tsa_h((string)($item['title'] ?? '')) ?></strong>
                                <span><?= tsa_h((string)($item['text'] ?? '')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('tsa_render_page_hero')) {
    function tsa_render_page_hero(array $options = []): void
    {
        $eyebrow = $options['eyebrow'] ?? 'TSA Legacy';
        $title = $options['title'] ?? '';
        $lead = $options['lead'] ?? '';
        $primaryHref = $options['primary_href'] ?? APP_URL . '/signup';
        $primaryLabel = $options['primary_label'] ?? 'Create Workspace';
        $secondaryHref = $options['secondary_href'] ?? APP_URL . '/demo';
        $secondaryLabel = $options['secondary_label'] ?? 'Instant Demo Access';
        $note = $options['note'] ?? 'No credit card required. Setup in minutes.';
        $sideCards = is_array($options['side_cards'] ?? null) ? $options['side_cards'] : [];
        ?>
        <section class="tsa-page-hero">
            <div class="tsa-page-hero-grid">
                <div>
                    <div class="tsa-eyebrow"><span class="dot"></span><?= tsa_h($eyebrow) ?></div>
                    <h1><?= $title ?></h1>
                    <p><?= tsa_h($lead) ?></p>
                    <div class="tsa-hero-actions">
                        <a href="<?= tsa_h($primaryHref) ?>" class="tsa-btn tsa-btn-primary"><?= tsa_h($primaryLabel) ?></a>
                        <a href="<?= tsa_h($secondaryHref) ?>" class="tsa-btn tsa-btn-secondary"><?= tsa_h($secondaryLabel) ?></a>
                    </div>
                    <div class="tsa-hero-note"><?= tsa_h($note) ?></div>
                </div>
                <?php if ($sideCards): ?>
                    <aside class="tsa-hero-side">
                        <div class="tsa-grid-2">
                            <?php foreach ($sideCards as $card): ?>
                                <div class="tsa-mini-hero-card">
                                    <strong><?= tsa_h((string)($card['title'] ?? '')) ?></strong>
                                    <span><?= tsa_h((string)($card['text'] ?? '')) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('tsa_brand_script')) {
    function tsa_brand_script(string $navId = 'tsaPublicNav'): string
    {
        $navIdJs = json_encode($navId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return <<<JS
function tsaSetMenuState(el, button, open){
    if(!el){return;}
    el.classList.toggle('open', open);
    el.hidden = !open;
    el.setAttribute('aria-hidden', open ? 'false' : 'true');
    if(button){
        button.classList.toggle('is-open', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }
}
function tsaFindMenuButton(id){
    return document.querySelector('[aria-controls="' + id + '"]');
}
function tsaToggleMenu(id, button){
    var el=document.getElementById(id);
    if(!el){return;}
    tsaSetMenuState(el, button || tsaFindMenuButton(id), !el.classList.contains('open'));
}
function tsaCloseMenu(id){
    var el=document.getElementById(id);
    tsaSetMenuState(el, tsaFindMenuButton(id), false);
}
window.addEventListener('scroll',function(){
    var nav=document.getElementById($navIdJs);
    if(!nav){return;}
    nav.style.background=window.scrollY>20?'rgba(255,253,248,.96)':'rgba(255,253,248,.84)';
});
document.addEventListener('click',function(e){
    var toggle=e.target.closest('[data-menu-toggle]');
    if(toggle){
        tsaToggleMenu(toggle.getAttribute('data-menu-toggle'), toggle);
        return;
    }
    var closeLink=e.target.closest('[data-menu-close]');
    if(closeLink){
        tsaCloseMenu(closeLink.getAttribute('data-menu-close'));
        return;
    }
    var m=document.getElementById('tsaPublicMenu');
    if(m&&m.classList.contains('open')){
        var n=document.getElementById('tsaPublicNav');
        if(n&&!n.contains(e.target)&&!m.contains(e.target)){
            tsaCloseMenu('tsaPublicMenu');
        }
    }
});
document.addEventListener('keydown',function(e){
    if(e.key === 'Escape'){
        tsaCloseMenu('tsaPublicMenu');
    }
});
JS;
    }
}
