<?php
/**
 * SEO landing page template — uses shared public.css design system.
 */
$pageTitle = (string)($title ?? APP_NAME);
$metaDescription = (string)($description ?? '');
$canonicalUrl = (string)($canonicalUrl ?? rtrim(APP_URL, '/') . '/');
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$logoUrl = rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.png';
$_nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? $cspNonce ?? '', ENT_QUOTES);
$headline = (string)($headline ?? APP_NAME);
$eyebrow = (string)($eyebrow ?? 'Business Software');
$intro = (string)($intro ?? '');
$benefits = is_array($benefits ?? null) ? $benefits : [];
$useCases = is_array($useCases ?? null) ? $useCases : [];
$faq = is_array($faq ?? null) ? $faq : [];
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::escape($pageTitle) ?></title>
    <meta name="description" content="<?= Helper::escape($metaDescription) ?>">
    <meta property="og:title" content="<?= Helper::escape($pageTitle) ?>">
    <meta property="og:description" content="<?= Helper::escape($metaDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= Helper::escape($canonicalUrl) ?>">
    <meta property="og:image" content="<?= Helper::escape($socialImageUrl) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape($pageTitle) ?>">
    <meta property="og:site_name" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= Helper::escape($pageTitle) ?>">
    <meta name="twitter:description" content="<?= Helper::escape($metaDescription) ?>">
    <meta name="twitter:image" content="<?= Helper::escape($socialImageUrl) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Helper::escape($faviconUrl) ?>">
    <link rel="canonical" href="<?= Helper::escape($canonicalUrl) ?>">
    <script type="application/ld+json" nonce="<?= $_nonce ?>">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static function (array $item): array {
                return [
                    '@type' => 'Question',
                    'name' => (string)($item['q'] ?? ''),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => (string)($item['a'] ?? ''),
                    ],
                ];
            }, $faq),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script nonce="<?= $_nonce ?>">document.documentElement.classList.remove('no-js');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/public.css">
</head>
<body>

<?php include __DIR__ . '/_partials/nav.php'; ?>

<main>
    <!-- HERO -->
    <section class="seo-hero">
        <div class="mx">
            <div class="seo-hero-grid">
                <div>
                    <div class="badge"><i class="fas fa-chart-line"></i><?= Helper::escape($eyebrow) ?></div>
                    <h1 class="hero-title-xl hero-title-gap-16"><?= Helper::escape($headline) ?></h1>
                    <p class="hero-copy-wide"><?= Helper::escape($intro) ?></p>
                    <div class="cta-btns">
                        <a href="<?= APP_URL ?>/signup" class="btn-p"><i class="fas fa-rocket"></i>Start Free Trial</a>
                        <a href="<?= APP_URL ?>/demo" class="btn-g"><i class="fas fa-play-circle"></i>Try Live Demo</a>
                    </div>
                </div>
                <aside class="seo-panel">
                    <div class="seo-stats">
                        <div class="seo-stat"><strong>GST Billing</strong><span>Invoices, receipts, returns and quotation workflows</span></div>
                        <div class="seo-stat"><strong>Inventory Control</strong><span>Products, stock movement, alerts and valuation reports</span></div>
                        <div class="seo-stat"><strong>Customer Tracking</strong><span>Ledger visibility, dues and payment history</span></div>
                        <div class="seo-stat"><strong>Multi-User SaaS</strong><span>Cloud access for owners and growing teams</span></div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <!-- BENEFITS -->
    <section class="sec">
        <div class="mx-sm">
            <div class="sec-hd rv"><h2 class="sec-t">Why businesses choose TSA Legacy</h2><p class="sec-s">TSA Legacy is built for Indian SMEs that need billing, inventory, reporting, and customer workflows in one place instead of managing multiple disconnected tools.</p></div>
            <ul class="checks">
                <?php foreach ($benefits as $benefit): ?>
                    <li class="rv"><i class="fas fa-check-circle"></i><span><?= Helper::escape((string)$benefit) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- USE CASES -->
    <section class="sec sec-alt" id="use-cases">
        <div class="mx-sm">
            <div class="sec-hd rv"><h2 class="sec-t">Built for everyday business operations</h2><p class="sec-s">These workflows are common across retail, wholesale, trading, and service-led teams that need faster billing with better operational control.</p></div>
            <div class="grid-3">
                <?php foreach ($useCases as $index => $item): ?>
                    <?php
                        $ucTitle = is_array($item) ? (string)($item['t'] ?? '') : (string)$item;
                        $ucDesc = is_array($item) ? (string)($item['d'] ?? 'Use one platform for billing, inventory, reports, customer records, and daily operational visibility.') : 'Use one platform for billing, inventory, reports, customer records, and daily operational visibility.';
                        $useCaseDelay = [0 => '', 1 => 'delay-80', 2 => 'delay-160', 3 => 'delay-240', 4 => 'delay-320', 5 => 'delay-400'][$index] ?? 'delay-400';
                    ?>
                    <div class="card rv delay-inline <?= $useCaseDelay ?>">
                        <div class="card-ic card-ic-accent"><i class="fas fa-layer-group"></i></div>
                        <h3><?= Helper::escape($ucTitle) ?></h3>
                        <p><?= Helper::escape($ucDesc) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="sec">
        <div class="mx-sm">
            <div class="sec-hd rv"><h2 class="sec-t">Frequently asked questions</h2><p class="sec-s">These questions target the commercial queries businesses usually search before choosing a billing or inventory platform.</p></div>
            <div class="faq-grid">
                <?php foreach ($faq as $index => $item): ?>
                    <?php $faqDelay = [0 => '', 1 => 'delay-60', 2 => 'delay-120', 3 => 'delay-180', 4 => 'delay-240', 5 => 'delay-300', 6 => 'delay-360'][$index] ?? 'delay-400'; ?>
                    <div class="faq-card rv delay-inline <?= $faqDelay ?>">
                        <h3><?= Helper::escape((string)($item['q'] ?? '')) ?></h3>
                        <p><?= Helper::escape((string)($item['a'] ?? '')) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- RELATED PAGES -->
    <section class="sec sec-alt">
        <div class="mx-sm">
            <div class="sec-hd rv"><h2 class="sec-t">Related pages</h2></div>
            <div class="chip-row chip-row-center">
                <a class="chip" href="<?= APP_URL ?>/gst-billing-software">GST Billing Software</a>
                <a class="chip" href="<?= APP_URL ?>/inventory-management-software">Inventory Management Software</a>
                <a class="chip" href="<?= APP_URL ?>/billing-software-for-small-business">Billing Software for Small Business</a>
                <a class="chip" href="<?= APP_URL ?>/pricing">Pricing</a>
                <a class="chip" href="<?= APP_URL ?>/blog">Guides</a>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="sec">
        <div class="mx-sm">
            <div class="cta-box rv">
                <div class="bg"></div>
                <div class="copy-stack">
                    <h2 class="hero-title-compact">Start with billing. Scale into full operations.</h2>
                    <p class="center-copy-700">Launch with GST billing and inventory, then grow into reports, customer tracking, multi-user workflows, backups, and integrations on the same platform.</p>
                    <div class="cta-btns cta-btns-center">
                        <a href="<?= APP_URL ?>/signup" class="btn-p btn-lg">Start Free Trial</a>
                        <a href="<?= APP_URL ?>/pricing" class="btn-g btn-lg">See Plans</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/_partials/footer.php'; ?>

<script nonce="<?= $_nonce ?>">
document.getElementById('hamburger').addEventListener('click',function(){document.getElementById('mobMenu').classList.toggle('open')});
document.querySelectorAll('#mobMenu a').forEach(function(el){el.addEventListener('click',function(){document.getElementById('mobMenu').classList.remove('open')})});
var revEls=document.querySelectorAll('.rv');
if('IntersectionObserver' in window){
var ob=new IntersectionObserver(function(e){e.forEach(function(el){if(el.isIntersecting){el.target.classList.add('vis');ob.unobserve(el.target)}})},{threshold:.08,rootMargin:'0px 0px -20px 0px'});
revEls.forEach(function(el){ob.observe(el)});
}
setTimeout(function(){revEls.forEach(function(el){el.classList.add('vis')})},2500);
window.addEventListener('scroll',function(){document.getElementById('mainNav').style.background=window.scrollY>50?'rgba(6,16,27,.96)':'rgba(6,16,27,.9)'});
</script>
</body>
</html>
