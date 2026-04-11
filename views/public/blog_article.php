<?php
/**
 * Blog article template — uses shared public.css design system.
 */
$canonicalUrl = (string)($canonicalUrl ?? rtrim(APP_URL, '/') . '/');
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$logoUrl = rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.png';
$_nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? $cspNonce ?? '', ENT_QUOTES);
$sections = is_array($sections ?? null) ? $sections : [];
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
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
    <script type="application/ld+json" nonce="<?= $_nonce ?>">
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
                    'url' => rtrim(APP_URL, '/') . '/assets/icon.svg',
                ],
            ],
            'mainEntityOfPage' => $canonicalUrl,
            'image' => [$socialImageUrl],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script type="application/ld+json" nonce="<?= $_nonce ?>">
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
    <section class="blog-hero">
        <div class="mx-xs">
            <div class="crumbs"><a href="<?= APP_URL ?>/">Home</a><span>/</span><a href="<?= APP_URL ?>/blog">Blog</a><span>/</span><span><?= Helper::escape((string)($heading ?? APP_NAME)) ?></span></div>
            <div class="badge"><i class="fas fa-book-open"></i> Business Software Guide</div>
            <h1 class="hero-title-xl hero-title-gap-16"><?= Helper::escape((string)($heading ?? APP_NAME)) ?></h1>
            <p class="hero-copy-wide"><?= Helper::escape((string)($excerpt ?? '')) ?></p>
            <div class="article-meta">Published <?= Helper::escape((string)($publishedDate ?? date('Y-m-d'))) ?> · For Indian SMEs</div>
        </div>
    </section>
    <article class="article-body">
        <div class="mx-xs">
            <?php foreach ($sections as $section): ?>
            <section class="article-block rv">
                <h2><?= Helper::escape((string)($section['h'] ?? '')) ?></h2>
                <p><?= Helper::escape((string)($section['p'] ?? '')) ?></p>
            </section>
            <?php endforeach; ?>
            <section class="article-cta rv">
                <p>TSA Legacy is built for Indian businesses that need GST billing, inventory management, customer tracking, reports, and multi-user operations from one cloud platform.</p>
                <div class="cta-btns">
                    <a href="<?= APP_URL ?>/signup" class="btn-p">Start Free Trial</a>
                    <a href="<?= APP_URL ?>/pricing" class="btn-g">View Pricing</a>
                </div>
            </section>
        </div>
    </article>
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
