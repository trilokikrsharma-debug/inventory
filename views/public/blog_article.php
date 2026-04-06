<?php
$canonicalUrl = (string)($canonicalUrl ?? rtrim(APP_URL, '/') . '/');
$iconUrl = rtrim(APP_URL, '/') . '/assets/icon.svg';
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$logoUrl = rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.svg';
$sections = is_array($sections ?? null) ? $sections : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::escape((string)($title ?? APP_NAME)) ?></title>
    <meta name="description" content="<?= Helper::escape((string)($description ?? '')) ?>">
    <meta property="og:title" content="<?= Helper::escape((string)($title ?? APP_NAME)) ?>">
    <meta property="og:description" content="<?= Helper::escape((string)($description ?? '')) ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= Helper::escape($canonicalUrl) ?>">
    <meta property="og:image" content="<?= Helper::escape($socialImageUrl) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape((string)($title ?? APP_NAME)) ?>">
    <meta property="og:site_name" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= Helper::escape((string)($title ?? APP_NAME)) ?>">
    <meta name="twitter:description" content="<?= Helper::escape((string)($description ?? '')) ?>">
    <meta name="twitter:image" content="<?= Helper::escape($socialImageUrl) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Helper::escape($faviconUrl) ?>">
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
                'name' => 'TSA Legacy Ventures',
                'url' => rtrim(APP_URL, '/') . '/',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $iconUrl,
                ],
            ],
            'mainEntityOfPage' => $canonicalUrl,
            'image' => [$socialImageUrl],
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{--p:#6366f1;--ac:#06b6d4;--bg:#020617;--card:rgba(255,255,255,.05);--brd:rgba(255,255,255,.08);--tx:#e2e8f0;--mt:#94a3b8;--w:#fff}
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',system-ui,sans-serif;background:linear-gradient(180deg,#06101b 0%,#0f1f32 100%);color:var(--tx);-webkit-font-smoothing:antialiased}
        a{text-decoration:none;color:inherit}.wrap{max-width:900px;margin:0 auto;padding:0 24px}.nav{padding:18px 0;border-bottom:1px solid var(--brd);background:rgba(6,16,27,.9);backdrop-filter:blur(18px)}
        .nav img{height:30px;display:block}.hero{padding:40px 0 24px}.eyebrow{display:inline-flex;padding:8px 14px;border-radius:999px;background:rgba(147,197,253,.1);border:1px solid rgba(147,197,253,.22);color:#93c5fd;font-size:.78rem;font-weight:700;margin-bottom:18px}
        h1{font-size:clamp(2rem,5vw,3.2rem);line-height:1.08;color:var(--w);margin-bottom:16px}.lead{font-size:1.05rem;line-height:1.8;color:var(--mt)}
        .meta{margin-top:16px;color:var(--mt);font-size:.85rem}.crumbs{display:flex;gap:8px;flex-wrap:wrap;color:var(--mt);font-size:.82rem;margin-bottom:18px}.crumbs a{color:#c4b5fd}.article{padding:18px 0 56px}.block{padding:24px 0;border-top:1px solid var(--brd)}.block h2{font-size:1.35rem;color:var(--w);margin-bottom:10px}.block p{color:var(--tx);line-height:1.85}
        .cta{margin-top:18px;padding:24px;border-radius:22px;background:var(--card);border:1px solid var(--brd)}.cta p{color:var(--mt);line-height:1.7;margin-bottom:14px}.btns{display:flex;gap:12px;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 18px;border-radius:10px;font-weight:700;font-size:.92rem}.btn-pri{background:linear-gradient(135deg,var(--p),#4f46e5);color:#fff}.btn-sec{border:1px solid var(--brd);color:var(--tx)}
        footer{padding:26px 0 40px;border-top:1px solid var(--brd)}footer p{color:var(--mt);font-size:.82rem}
    </style>
</head>
<body>
<nav class="nav">
    <div class="wrap"><a href="<?= APP_URL ?>/"><img src="<?= Helper::escape($logoUrl) ?>" alt="TSA Legacy"></a></div>
</nav>
<main class="wrap">
    <section class="hero">
        <div class="crumbs"><a href="<?= APP_URL ?>/">Home</a><span>/</span><a href="<?= APP_URL ?>/blog">Blog</a><span>/</span><span><?= Helper::escape((string)($heading ?? APP_NAME)) ?></span></div>
        <div class="eyebrow">Business Software Guide</div>
        <h1><?= Helper::escape((string)($heading ?? APP_NAME)) ?></h1>
        <p class="lead"><?= Helper::escape((string)($excerpt ?? '')) ?></p>
        <div class="meta">Published <?= Helper::escape((string)($publishedDate ?? date('Y-m-d'))) ?> • For Indian SMEs</div>
    </section>
    <article class="article">
        <?php foreach ($sections as $section): ?>
        <section class="block">
            <h2><?= Helper::escape((string)($section['h'] ?? '')) ?></h2>
            <p><?= Helper::escape((string)($section['p'] ?? '')) ?></p>
        </section>
        <?php endforeach; ?>
        <section class="cta">
            <p>TSA Legacy is built for Indian businesses that need GST billing, inventory management, customer tracking, reports, and multi-user operations from one cloud platform.</p>
            <div class="btns">
                <a href="<?= APP_URL ?>/signup" class="btn btn-pri">Start Free Trial</a>
                <a href="<?= APP_URL ?>/pricing" class="btn btn-sec">View Pricing</a>
            </div>
        </section>
    </article>
</main>
<footer>
    <div class="wrap"><p>© 2025–<?= date('Y') ?> TSA Legacy Ventures</p></div>
</footer>
</body>
</html>
