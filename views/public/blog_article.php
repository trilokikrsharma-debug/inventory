<?php
require_once __DIR__ . '/_partials/brand.php';

$assets = tsa_brand_assets();
$canonicalUrl = (string)($canonicalUrl ?? rtrim(APP_URL, '/') . '/');
$sections = is_array($sections ?? null) ? $sections : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php tsa_render_adsense_verification(); ?>
    <title><?= Helper::escape((string)($title ?? APP_NAME)) ?></title>
    <meta name="description" content="<?= Helper::escape((string)($description ?? '')) ?>">
    <meta property="og:title" content="<?= Helper::escape((string)($title ?? APP_NAME)) ?>">
    <meta property="og:description" content="<?= Helper::escape((string)($description ?? '')) ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= Helper::escape($canonicalUrl) ?>">
    <meta property="og:image" content="<?= Helper::escape($assets['og']) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape((string)($title ?? APP_NAME)) ?>">
    <meta property="og:site_name" content="<?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= Helper::escape((string)($title ?? APP_NAME)) ?>">
    <meta name="twitter:description" content="<?= Helper::escape((string)($description ?? '')) ?>">
    <meta name="twitter:image" content="<?= Helper::escape($assets['og']) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Helper::escape($assets['favicon']) ?>">
    <link rel="canonical" href="<?= Helper::escape($canonicalUrl) ?>">
    <script type="application/ld+json">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => (string)($heading ?? APP_NAME),
            'description' => (string)($description ?? ''),
            'datePublished' => (string)($publishedDate ?? date('Y-m-d')),
            'dateModified' => (string)($modifiedDate ?? date('Y-m-d')),
            'author' => [
                '@type' => 'Person',
                'name' => 'Triloki Kumar Sharma',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME,
                'url' => rtrim(APP_URL, '/') . '/',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $assets['icon'],
                ],
            ],
            'mainEntityOfPage' => $canonicalUrl,
            'image' => [$assets['og']],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script type="application/ld+json">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => rtrim(APP_URL, '/') . '/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Blog',
                    'item' => rtrim(APP_URL, '/') . '/blog',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => (string)($heading ?? APP_NAME),
                    'item' => $canonicalUrl,
                ],
            ],
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
    'active_href' => APP_URL . '/blog',
    'links' => [
        ['href' => APP_URL . '/', 'label' => 'Home'],
        ['href' => APP_URL . '/pricing', 'label' => 'Pricing'],
        ['href' => APP_URL . '/blog', 'label' => 'Guides'],
    ],
    'secondary_label' => 'View Pricing',
    'secondary_href' => APP_URL . '/pricing',
]); ?>

<main class="tsa-page">
    <div class="tsa-container">
        <section class="tsa-page-hero">
            <div class="tsa-article-shell">
                <div class="tsa-breadcrumbs">
                    <a href="<?= APP_URL ?>/">Home</a>
                    <span>/</span>
                    <a href="<?= APP_URL ?>/blog">Blog</a>
                    <span>/</span>
                    <span><?= Helper::escape((string)($heading ?? APP_NAME)) ?></span>
                </div>
                <div class="tsa-eyebrow"><span class="dot"></span>Business Software Guide</div>
                <h1><?= Helper::escape((string)($heading ?? APP_NAME)) ?></h1>
                <p><?= Helper::escape((string)($excerpt ?? '')) ?></p>
                <div class="tsa-article-meta">Published <?= Helper::escape((string)($publishedDate ?? date('Y-m-d'))) ?> • For Indian SMEs</div>
            </div>
        </section>

        <article class="tsa-article-shell tsa-legal-card">
            <?php foreach ($sections as $section): ?>
                <section class="tsa-article-block">
                    <h2><?= Helper::escape((string)($section['h'] ?? '')) ?></h2>
                    <p><?= Helper::escape((string)($section['p'] ?? '')) ?></p>
                </section>
            <?php endforeach; ?>
            <section class="tsa-cta-box tsa-article-cta">
                <div class="tsa-eyebrow"><span class="dot"></span>TSA Legacy</div>
                <h2>Billing, inventory, and daily operations in one cloud workflow.</h2>
                <p>TSA Legacy is built for Indian businesses that need GST billing, inventory management, customer tracking, reports, and multi-user operations from one platform.</p>
                <div class="tsa-hero-actions">
                    <a href="<?= APP_URL ?>/signup" class="tsa-btn tsa-btn-primary">Start Free Trial</a>
                    <a href="<?= APP_URL ?>/pricing" class="tsa-btn tsa-btn-secondary">View Pricing</a>
                </div>
            </section>
        </article>
    </div>
</main>

<?php tsa_render_public_footer(['show_guides' => false]); ?>
<script>
<?= tsa_brand_script() ?>
</script>
</body>
</html>
