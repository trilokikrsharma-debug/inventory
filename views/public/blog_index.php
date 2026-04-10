<?php
/**
 * Blog index — uses shared public.css design system.
 */
$items = is_array($items ?? null) ? $items : [];
$canonicalUrl = (string)($canonicalUrl ?? rtrim(APP_URL, '/') . '/blog');
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$logoUrl = rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.png';
$_nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? $cspNonce ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
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
        <div class="mx-sm">
            <div class="crumbs"><a href="<?= APP_URL ?>/">Home</a><span>/</span><span>Blog</span></div>
            <div class="badge"><i class="fas fa-book-open"></i> Guides & Articles</div>
            <h1 style="font-size:clamp(2rem,5vw,3.2rem);font-weight:900;color:var(--w);line-height:1.08;margin-bottom:14px">Business software guides for Indian SMEs</h1>
            <p style="color:var(--mt);line-height:1.8;max-width:720px;font-size:1.02rem">Practical articles on GST billing software, inventory management, retail and wholesale workflows, invoice software, and small-business operations in India.</p>
        </div>
    </section>
    <section class="sec" style="padding-top:18px">
        <div class="mx-sm">
            <div class="grid-3">
                <?php foreach ($items as $index => $item): ?>
                <a href="<?= Helper::escape((string)$item['url']) ?>" class="card rv" style="transition-delay:<?= $index * 60 ?>ms;display:block">
                    <h3><?= Helper::escape((string)($item['heading'] ?? $item['title'] ?? '')) ?></h3>
                    <p><?= Helper::escape((string)$item['description']) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/_partials/footer.php'; ?>

<script nonce="<?= $_nonce ?>">
document.getElementById('hamburger').addEventListener('click',function(){document.getElementById('mobMenu').classList.toggle('open')});
function clM(){document.getElementById('mobMenu').classList.remove('open')}
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
