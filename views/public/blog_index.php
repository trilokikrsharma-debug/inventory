<?php
$items = is_array($items ?? null) ? $items : [];
$canonicalUrl = (string)($canonicalUrl ?? rtrim(APP_URL, '/') . '/blog');
$iconUrl = rtrim(APP_URL, '/') . '/assets/icon.svg';
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$logoUrl = rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.svg';
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
    <meta property="og:image" content="<?= Helper::escape($socialImageUrl) ?>">
    <meta property="og:image:alt" content="TSA Legacy">
    <meta property="og:site_name" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Business Software Guides for Indian SMEs | TSA Legacy">
    <meta name="twitter:description" content="Guides on GST billing software, inventory management, retail billing, kirana shop software, wholesale workflows and small-business operations in India.">
    <meta name="twitter:image" content="<?= Helper::escape($socialImageUrl) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Helper::escape($faviconUrl) ?>">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{--p:#6366f1;--ac:#06b6d4;--bg:#020617;--card:rgba(255,255,255,.05);--brd:rgba(255,255,255,.08);--tx:#e2e8f0;--mt:#94a3b8;--w:#fff}
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',system-ui,sans-serif;background:linear-gradient(180deg,#020617 0%,#091224 100%);color:var(--tx)}
        a{text-decoration:none;color:inherit}.wrap{max-width:1120px;margin:0 auto;padding:0 24px}.nav{padding:18px 0;border-bottom:1px solid var(--brd);background:rgba(2,6,23,.88)}
        .brand-lockup{height:34px;display:block}
        .hero{padding:40px 0 24px}.crumbs{display:flex;gap:8px;flex-wrap:wrap;color:var(--mt);font-size:.82rem;margin-bottom:18px}.crumbs a{color:#93c5fd}.eyebrow{display:inline-flex;padding:8px 14px;border-radius:999px;background:rgba(147,197,253,.1);border:1px solid rgba(147,197,253,.22);color:#93c5fd;font-size:.78rem;font-weight:700;margin-bottom:18px}
        h1{font-size:clamp(2rem,5vw,3.2rem);line-height:1.08;color:var(--w);margin-bottom:14px}.lead{color:var(--mt);line-height:1.8;max-width:760px}
        .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;padding:18px 0 56px}.card{display:block;padding:22px;border-radius:22px;background:var(--card);border:1px solid var(--brd)}
        .brand-lockup{height:30px;display:block}.card h2{font-size:1.05rem;color:var(--w);margin-bottom:10px;line-height:1.38}.card p{color:var(--mt);line-height:1.75;font-size:.94rem}
        @media(max-width:960px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<nav class="nav"><div class="wrap"><a href="<?= APP_URL ?>/"><img src="<?= Helper::escape($logoUrl) ?>" alt="TSA Legacy" class="brand-lockup"></a></div></nav>
<main class="wrap">
    <section class="hero">
        <div class="crumbs"><a href="<?= APP_URL ?>/">Home</a><span>/</span><span>Blog</span></div>
        <div class="eyebrow">Guides & Articles</div>
        <h1>Business software guides for Indian SMEs</h1>
        <p class="lead">Practical articles on GST billing software, inventory management, retail and wholesale workflows, invoice software, and small-business operations in India.</p>
    </section>
    <section class="grid">
        <?php foreach ($items as $item): ?>
        <a href="<?= Helper::escape((string)$item['url']) ?>" class="card">
            <h2><?= Helper::escape((string)$item['title']) ?></h2>
            <p><?= Helper::escape((string)$item['description']) ?></p>
        </a>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
