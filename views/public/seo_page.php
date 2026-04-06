<?php
$pageTitle = (string)($title ?? APP_NAME);
$metaDescription = (string)($description ?? '');
$canonicalUrl = (string)($canonicalUrl ?? rtrim(APP_URL, '/') . '/');
$iconUrl = rtrim(APP_URL, '/') . '/assets/icon.svg';
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$logoUrl = rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.svg';
$headline = (string)($headline ?? APP_NAME);
$eyebrow = (string)($eyebrow ?? 'Business Software');
$intro = (string)($intro ?? '');
$benefits = is_array($benefits ?? null) ? $benefits : [];
$useCases = is_array($useCases ?? null) ? $useCases : [];
$faq = is_array($faq ?? null) ? $faq : [];
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
    <meta property="og:image" content="<?= Helper::escape($socialImageUrl) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape($pageTitle) ?>">
    <meta property="og:site_name" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= Helper::escape($pageTitle) ?>">
    <meta name="twitter:description" content="<?= Helper::escape($metaDescription) ?>">
    <meta name="twitter:image" content="<?= Helper::escape($socialImageUrl) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Helper::escape($faviconUrl) ?>">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root{--p:#2563eb;--ac:#60a5fa;--ok:#38bdf8;--bg:#06101b;--card:rgba(255,255,255,.055);--brd:rgba(148,163,184,.16);--tx:#e6edf7;--mt:#b7c4d6;--w:#fff}
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',system-ui,sans-serif;background:linear-gradient(180deg,#020617 0%,#081226 100%);color:var(--tx);-webkit-font-smoothing:antialiased}
        a{text-decoration:none;color:inherit}.wrap{max-width:1120px;margin:0 auto;padding:0 24px}
        .nav{padding:16px 0;position:sticky;top:0;background:rgba(6,16,27,.9);backdrop-filter:blur(18px);border-bottom:1px solid var(--brd)}
        .nav-in{display:flex;align-items:center;justify-content:space-between;gap:18px}.logo{display:flex;align-items:center;color:var(--w)}
        .logo img{height:30px;display:block}
        .nav-cta{display:flex;gap:10px;flex-wrap:wrap}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:46px;padding:11px 18px;border-radius:12px;font-weight:700;font-size:.92rem}
        .btn-pri{background:linear-gradient(135deg,var(--p),#1d4ed8);color:#fff;border:1px solid rgba(147,197,253,.22)}.btn-sec{border:1px solid rgba(147,197,253,.26);color:var(--tx);background:rgba(15,31,50,.84)}
        .hero{padding:48px 0 30px}.eyebrow{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;background:rgba(147,197,253,.1);border:1px solid rgba(147,197,253,.22);color:#93c5fd;font-size:.78rem;font-weight:700;margin-bottom:16px}
        h1{font-size:clamp(2rem,5vw,3.4rem);line-height:1.06;color:var(--w);max-width:900px;margin-bottom:16px}
        .lead{max-width:760px;font-size:1.05rem;line-height:1.8;color:var(--mt);margin-bottom:28px}
        .hero-grid,.grid-3,.faq-grid{display:grid;gap:20px}.hero-grid{grid-template-columns:1.3fr .9fr;align-items:start}.panel,.card,.faq{background:var(--card);border:1px solid var(--brd);border-radius:22px}
        .panel{padding:28px}.stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.stat{padding:16px;border-radius:16px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05)}
        .stat strong{display:block;color:var(--w);font-size:1rem}.stat span{font-size:.82rem;color:var(--mt)}
        .sec{padding:26px 0 12px}.sec h2{font-size:1.7rem;color:var(--w);margin-bottom:10px}.sec p.sec-intro{color:var(--mt);line-height:1.7;max-width:820px;margin-bottom:22px}
        .grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}.card{padding:22px}.card i{color:#93c5fd;font-size:1rem;margin-bottom:12px}.card h3{font-size:1.02rem;color:var(--w);margin-bottom:10px;line-height:1.38}.card p{color:var(--mt);line-height:1.72;font-size:.94rem}
        .checks{list-style:none;display:grid;gap:12px}.checks li{display:flex;gap:10px;line-height:1.7;color:var(--tx)}.checks i{color:var(--ok);margin-top:4px}
        .link-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:18px}.chip{padding:10px 14px;border:1px solid var(--brd);border-radius:999px;color:var(--tx);font-size:.85rem}
        .faq-grid{grid-template-columns:1fr 1fr}.faq{padding:20px}.faq h3{color:var(--w);font-size:1rem;margin-bottom:10px}.faq p{color:var(--mt);line-height:1.7;font-size:.92rem}
        .cta{padding:28px;margin:28px 0 56px;text-align:center}.cta h2{font-size:2rem;color:var(--w);margin-bottom:10px}.cta p{max-width:700px;margin:0 auto 20px;color:var(--mt);line-height:1.7}
        footer{padding:28px 0 40px;border-top:1px solid var(--brd)}footer p{color:var(--mt);font-size:.8rem}
        @media(max-width:900px){.hero-grid,.grid-3,.faq-grid{grid-template-columns:1fr}.stats{grid-template-columns:1fr 1fr}}
        @media(max-width:640px){.nav-in{flex-direction:column;align-items:flex-start}.nav-cta{width:100%}.btn{width:100%}.stats{grid-template-columns:1fr}.hero{padding:36px 0 24px}}
    </style>
</head>
<body>
<nav class="nav">
    <div class="wrap nav-in">
        <a href="<?= APP_URL ?>/" class="logo"><img src="<?= Helper::escape($logoUrl) ?>" alt="TSA Legacy"></a>
        <div class="nav-cta">
            <a href="<?= APP_URL ?>/pricing" class="btn btn-sec">View Pricing</a>
            <a href="<?= APP_URL ?>/signup" class="btn btn-pri">Start Free Trial</a>
        </div>
    </div>
</nav>
<main class="wrap">
    <section class="hero">
        <div class="hero-grid">
            <div>
                <div class="eyebrow"><i class="fas fa-chart-line"></i><?= Helper::escape($eyebrow) ?></div>
                <h1><?= Helper::escape($headline) ?></h1>
                <p class="lead"><?= Helper::escape($intro) ?></p>
                <div class="link-row">
                    <a href="<?= APP_URL ?>/signup" class="btn btn-pri">Start Free Trial</a>
                    <a href="<?= APP_URL ?>/demo" class="btn btn-sec">Try Live Demo</a>
                </div>
            </div>
            <aside class="panel">
                <div class="stats">
                    <div class="stat"><strong>GST Billing</strong><span>Invoices, receipts, returns and quotation workflows</span></div>
                    <div class="stat"><strong>Inventory Control</strong><span>Products, stock movement, alerts and valuation reports</span></div>
                    <div class="stat"><strong>Customer Tracking</strong><span>Ledger visibility, dues and payment history</span></div>
                    <div class="stat"><strong>Multi-User SaaS</strong><span>Cloud access for owners and growing teams</span></div>
                </div>
            </aside>
        </div>
    </section>
    <section class="sec">
        <h2>Why businesses choose TSA Legacy</h2>
        <p class="sec-intro">TSA Legacy is built for Indian SMEs that need billing, inventory, reporting, and customer workflows in one place instead of managing multiple disconnected tools.</p>
        <ul class="checks">
            <?php foreach ($benefits as $benefit): ?>
                <li><i class="fas fa-check-circle"></i><span><?= Helper::escape((string)$benefit) ?></span></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="sec">
        <h2>Built for everyday business operations</h2>
        <p class="sec-intro">These workflows are common across retail, wholesale, trading, and service-led teams that need faster billing with better operational control.</p>
        <div class="grid-3">
            <?php foreach ($useCases as $item): ?>
                <div class="card">
                    <i class="fas fa-layer-group"></i>
                    <h3><?= Helper::escape((string)$item) ?></h3>
                    <p>Use one platform for billing, inventory, reports, customer records, and daily operational visibility.</p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="sec">
        <h2>Frequently asked questions</h2>
        <p class="sec-intro">These questions target the commercial queries businesses usually search before choosing a billing or inventory platform.</p>
        <div class="faq-grid">
            <?php foreach ($faq as $item): ?>
                <div class="faq">
                    <h3><?= Helper::escape((string)($item['q'] ?? '')) ?></h3>
                    <p><?= Helper::escape((string)($item['a'] ?? '')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="sec">
        <h2>Related pages</h2>
        <div class="link-row">
            <a class="chip" href="<?= APP_URL ?>/gst-billing-software">GST Billing Software</a>
            <a class="chip" href="<?= APP_URL ?>/inventory-management-software">Inventory Management Software</a>
            <a class="chip" href="<?= APP_URL ?>/billing-software-for-small-business">Billing Software for Small Business</a>
            <a class="chip" href="<?= APP_URL ?>/pricing">Pricing</a>
        </div>
    </section>
    <section class="panel cta">
        <h2>Start with billing. Scale into full operations.</h2>
        <p>Launch with GST billing and inventory, then grow into reports, customer tracking, multi-user workflows, backups, and integrations on the same platform.</p>
        <div class="link-row" style="justify-content:center">
            <a href="<?= APP_URL ?>/signup" class="btn btn-pri">Start Free Trial</a>
            <a href="<?= APP_URL ?>/pricing" class="btn btn-sec">See Plans</a>
        </div>
    </section>
</main>
<footer>
    <div class="wrap">
        <p>© 2025–<?= date('Y') ?> TSA Legacy Ventures. Cloud-native business software for Indian SMEs.</p>
    </div>
</footer>
</body>
</html>
