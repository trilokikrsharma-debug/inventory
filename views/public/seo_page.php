<?php
require_once __DIR__ . '/_partials/brand.php';

$assets = tsa_brand_assets();
$pageTitle = (string)($title ?? APP_NAME);
$metaDescription = (string)($description ?? '');
$canonicalUrl = (string)($canonicalUrl ?? rtrim(APP_URL, '/') . '/');
$headline = (string)($headline ?? APP_NAME);
$eyebrow = (string)($eyebrow ?? 'Business Software');
$intro = (string)($intro ?? '');
$benefits = is_array($benefits ?? null) ? $benefits : [];
$useCases = is_array($useCases ?? null) ? $useCases : [];
$faq = is_array($faq ?? null) ? $faq : [];

$heroCards = [
    ['title' => 'GST-ready workflows', 'text' => 'Billing, quotations, returns, and dues tracking are designed for Indian business operations.'],
    ['title' => 'Operational visibility', 'text' => 'Products, customers, suppliers, and reports stay connected inside one cloud workspace.'],
    ['title' => 'Role-aware access', 'text' => 'Owners, managers, and staff can work inside the same platform with clearer control.'],
    ['title' => 'Sample workspace access', 'text' => 'Use instant demo access to review the workflow before creating your own account.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::escape($pageTitle) ?></title>
    <meta name="description" content="<?= Helper::escape($metaDescription) ?>">
    <meta property="og:title" content="<?= Helper::escape($pageTitle) ?>">
    <meta property="og:description" content="<?= Helper::escape($metaDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= Helper::escape($canonicalUrl) ?>">
    <meta property="og:image" content="<?= Helper::escape($assets['og']) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape($pageTitle) ?>">
    <meta property="og:site_name" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= Helper::escape($pageTitle) ?>">
    <meta name="twitter:description" content="<?= Helper::escape($metaDescription) ?>">
    <meta name="twitter:image" content="<?= Helper::escape($assets['og']) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Helper::escape($assets['favicon']) ?>">
    <link rel="canonical" href="<?= Helper::escape($canonicalUrl) ?>">
    <script type="application/ld+json">
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
    <link rel="stylesheet" href="<?= Helper::escape($assets['brand_css']) ?>">
</head>
<body class="tsa-public">
<?php tsa_render_public_nav([
    'active_href' => APP_URL . '/pricing',
    'links' => [
        ['href' => APP_URL . '/', 'label' => 'Home'],
        ['href' => APP_URL . '/pricing', 'label' => 'Pricing'],
        ['href' => APP_URL . '/blog', 'label' => 'Guides'],
    ],
    'secondary_label' => 'Instant Demo Access',
    'secondary_href' => APP_URL . '/demo',
    'primary_label' => 'Start Free Trial',
    'primary_href' => APP_URL . '/signup',
]); ?>

<main class="tsa-page">
    <div class="tsa-container">
        <?php tsa_render_page_hero([
            'eyebrow' => $eyebrow,
            'title' => Helper::escape($headline),
            'lead' => $intro,
            'primary_href' => APP_URL . '/signup',
            'primary_label' => 'Start Free Trial',
            'secondary_href' => APP_URL . '/demo',
            'secondary_label' => 'Instant Demo Access',
            'note' => 'Built for Indian businesses that want billing, inventory, and operational control in one platform.',
            'side_cards' => $heroCards,
        ]); ?>

        <section class="tsa-section" style="padding-top:32px">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Why TSA Legacy</div>
                <h2>Built for everyday business operations, not disconnected admin work.</h2>
                <p>TSA Legacy helps Indian SMEs bring billing, inventory, reporting, and customer workflows into one workspace so daily operations stay easier to manage.</p>
            </div>
            <div class="tsa-grid-2">
                <div class="tsa-card">
                    <div class="tsa-icon-chip"><i class="fas fa-circle-check"></i></div>
                    <h3>Core benefits</h3>
                    <ul class="tsa-list">
                        <?php foreach ($benefits as $benefit): ?>
                            <li><i class="fas fa-check"></i><span><?= Helper::escape((string)$benefit) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="tsa-card">
                    <div class="tsa-icon-chip"><i class="fas fa-building"></i></div>
                    <h3>Business fit</h3>
                    <p>Suitable for retail, wholesale, trading, and service-led teams that need faster billing, cleaner records, and better owner visibility without ERP-level complexity.</p>
                    <div class="tsa-chip-row" style="margin-top:16px">
                        <a class="tsa-chip-link" href="<?= APP_URL ?>/pricing">View Pricing</a>
                        <a class="tsa-chip-link" href="<?= APP_URL ?>/blog">Read Guides</a>
                        <a class="tsa-chip-link" href="<?= APP_URL ?>/demo">Instant Demo Access</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="tsa-section tsa-section-alt">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Operational Use Cases</div>
                <h2>Use one platform across the workflows your team runs every day.</h2>
                <p>These common business scenarios reflect the kinds of teams that benefit most from a structured billing and inventory system.</p>
            </div>
            <div class="tsa-grid-3">
                <?php foreach ($useCases as $item): ?>
                    <article class="tsa-card">
                        <div class="tsa-icon-chip"><i class="fas fa-layer-group"></i></div>
                        <h3><?= Helper::escape((string)$item) ?></h3>
                        <p>Keep billing, stock, customer records, supplier activity, and reporting aligned in one cloud-based operating workflow.</p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if (!empty($faq)): ?>
            <section class="tsa-section">
                <div class="tsa-section-head">
                    <div class="tsa-section-kicker">Frequently Asked Questions</div>
                    <h2>Commercial questions buyers usually ask before they choose.</h2>
                    <p>These answers focus on how the product fits operational needs, pricing evaluation, and day-to-day business use.</p>
                </div>
                <div class="tsa-faq-grid">
                    <?php foreach ($faq as $item): ?>
                        <article class="tsa-card tsa-faq-card">
                            <h3><?= Helper::escape((string)($item['q'] ?? '')) ?></h3>
                            <p><?= Helper::escape((string)($item['a'] ?? '')) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="tsa-section" style="padding-top:20px">
            <div class="tsa-cta-box">
                <div class="tsa-eyebrow"><span class="dot"></span>TSA Legacy</div>
                <h2>Start with billing. Build stronger operational control over time.</h2>
                <p>Begin with GST billing and inventory management, then grow into customer records, reporting, multi-user workflows, and broader business operations on the same platform.</p>
                <div class="tsa-hero-actions">
                    <a href="<?= APP_URL ?>/signup" class="tsa-btn tsa-btn-primary">Start Free Trial</a>
                    <a href="<?= APP_URL ?>/pricing" class="tsa-btn tsa-btn-secondary">See Pricing</a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php tsa_render_public_footer(['show_guides' => true]); ?>
<script>
<?= tsa_brand_script() ?>
</script>
</body>
</html>
