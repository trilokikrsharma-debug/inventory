<?php
if (!function_exists('tsa_brand_assets')) {
    function tsa_brand_assets(): array
    {
        $base = rtrim(APP_URL, '/');
        return [
            'favicon' => $base . '/assets/favicon.svg',
            'icon' => $base . '/assets/icon.svg',
            'logo_light' => $base . '/assets/logo-lockup-light.svg',
            'logo_dark' => $base . '/assets/logo-lockup.svg',
            'og' => $base . '/assets/og-default.svg',
            'brand_css' => $base . '/assets/css/public-brand.css?v=' . rawurlencode((string)ASSET_VERSION),
        ];
    }
}

if (!function_exists('tsa_h')) {
    function tsa_h(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES);
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
        ?>
        <div class="tsa-nav-wrap">
            <nav class="tsa-nav" id="<?= tsa_h($navId) ?>">
                <div class="tsa-nav-inner">
                    <a href="<?= tsa_h(APP_URL . '/') ?>" class="tsa-logo">
                        <img src="<?= tsa_h($assets['logo_light']) ?>" alt="TSA Legacy">
                    </a>
                    <div class="tsa-nav-links">
                        <?php foreach ($links as $link): ?>
                            <a href="<?= tsa_h((string)$link['href']) ?>" class="<?= ((string)$link['href'] === (string)$activeHref) ? 'is-active' : '' ?>"><?= tsa_h((string)$link['label']) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <div class="tsa-nav-cta">
                        <a href="<?= tsa_h($secondaryHref) ?>" class="tsa-btn tsa-btn-ghost"><?= tsa_h($secondaryLabel) ?></a>
                        <a href="<?= tsa_h($primaryHref) ?>" class="tsa-btn tsa-btn-primary"><?= tsa_h($primaryLabel) ?></a>
                    </div>
                    <button class="tsa-hamburger" type="button" aria-label="Menu" onclick="tsaToggleMenu('<?= tsa_h($menuId) ?>')">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </nav>
            <div class="tsa-mobile-menu" id="<?= tsa_h($menuId) ?>">
                <?php foreach ($links as $link): ?>
                    <a href="<?= tsa_h((string)$link['href']) ?>" onclick="tsaCloseMenu('<?= tsa_h($menuId) ?>')"><?= tsa_h((string)$link['label']) ?></a>
                <?php endforeach; ?>
                <a href="<?= tsa_h($secondaryHref) ?>" onclick="tsaCloseMenu('<?= tsa_h($menuId) ?>')"><?= tsa_h($secondaryLabel) ?></a>
                <a href="<?= tsa_h($primaryHref) ?>" onclick="tsaCloseMenu('<?= tsa_h($menuId) ?>')"><?= tsa_h($primaryLabel) ?></a>
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
                        <a href="<?= tsa_h(APP_URL . '/') ?>" class="tsa-logo" style="margin-bottom:14px"><img src="<?= tsa_h($assets['logo_light']) ?>" alt="TSA Legacy"></a>
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
                    <div class="tsa-footer-copy">© 2025–<?= date('Y') ?> TSA Legacy Ventures. All rights reserved.</div>
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
function tsaToggleMenu(id){var el=document.getElementById(id);if(el){el.classList.toggle('open');}}
function tsaCloseMenu(id){var el=document.getElementById(id);if(el){el.classList.remove('open');}}
window.addEventListener('scroll',function(){var nav=document.getElementById($navIdJs);if(!nav){return;}nav.style.background=window.scrollY>20?'rgba(255,253,248,.94)':'rgba(255,253,248,.84)';});
document.addEventListener('click',function(e){var m=document.getElementById('tsaPublicMenu');if(m&&m.classList.contains('open')){var n=document.getElementById('tsaPublicNav');if(n&&!n.contains(e.target)&&!m.contains(e.target)){m.classList.remove('open');}}});
JS;
    }
}
