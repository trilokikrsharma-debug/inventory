<?php
require_once __DIR__ . '/_partials/brand.php';

$items = is_array($items ?? null) ? $items : [];
$assets = tsa_brand_assets();
$canonicalUrl = (string)($canonicalUrl ?? rtrim(APP_URL, '/') . '/blog');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Software Guides for Indian SMEs | TSA Legacy</title>
    <meta name="description" content="Guides on GST billing software, inventory management, retail billing, kirana shop software, wholesale workflows and small-business operations in India.">
    <meta property="og:title" content="Business Software Guides for Indian SMEs | TSA Legacy">
    <meta property="og:description" content="Guides on GST billing software, inventory management, retail billing, kirana shop software, wholesale workflows and small-business operations in India.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= Helper::escape($canonicalUrl) ?>">
    <meta property="og:image" content="<?= Helper::escape($assets['og']) ?>">
    <meta property="og:image:alt" content="TSA Legacy">
    <meta property="og:site_name" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Business Software Guides for Indian SMEs | TSA Legacy">
    <meta name="twitter:description" content="Guides on GST billing software, inventory management, retail billing, kirana shop software, wholesale workflows and small-business operations in India.">
    <meta name="twitter:image" content="<?= Helper::escape($assets['og']) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Helper::escape($assets['favicon']) ?>">
    <link rel="canonical" href="<?= Helper::escape($canonicalUrl) ?>">
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
                    'item' => $canonicalUrl,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
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
    'secondary_label' => 'Instant Demo Access',
    'secondary_href' => APP_URL . '/demo',
]); ?>

<main class="tsa-page">
    <div class="tsa-container">
        <section class="tsa-page-hero">
            <div class="tsa-article-shell">
                <div class="tsa-breadcrumbs">
                    <a href="<?= APP_URL ?>/">Home</a>
                    <span>/</span>
                    <span>Blog</span>
                </div>
                <div class="tsa-eyebrow"><span class="dot"></span>Guides &amp; Articles</div>
                <h1>Business software guides for Indian SMEs</h1>
                <p>Practical articles on GST billing software, inventory management, retail and wholesale workflows, invoice software, and small-business operations in India.</p>
            </div>
        </section>

        <section class="tsa-section" style="padding-top:24px">
            <div class="tsa-blog-grid">
                <?php foreach ($items as $item): ?>
                    <a href="<?= Helper::escape((string)$item['url']) ?>" class="tsa-card tsa-blog-card">
                        <div class="tsa-icon-chip"><i class="fas fa-arrow-up-right-from-square"></i></div>
                        <h2><?= Helper::escape((string)$item['title']) ?></h2>
                        <p><?= Helper::escape((string)$item['description']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<?php tsa_render_public_footer(['show_guides' => false]); ?>
<script>
<?= tsa_brand_script() ?>
</script>
</body>
</html>
