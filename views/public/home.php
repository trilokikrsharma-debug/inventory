<?php
$homeUrl = rtrim(APP_URL, '/') . '/';
$iconUrl = rtrim(APP_URL, '/') . '/assets/icon.svg';
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$logoUrl = rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.svg';
$seoLandingLinks = [
    ['href' => APP_URL . '/gst-billing-software', 'title' => 'GST Billing Software', 'description' => 'Focused page for GST invoices, receipts, quotations and faster billing workflows.'],
    ['href' => APP_URL . '/inventory-management-software', 'title' => 'Inventory Management Software', 'description' => 'Focused page for stock tracking, purchases, product catalog control and low-stock visibility.'],
    ['href' => APP_URL . '/billing-software-for-small-business', 'title' => 'Billing Software for Small Business', 'description' => 'Focused page for owner-led teams that need billing, inventory and reporting in one place.'],
];
$faqSchema = [
    [
        '@type' => 'Question',
        'name' => 'What is TSA Legacy used for?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'TSA Legacy is used for GST billing, inventory management, customer and supplier tracking, reporting, and multi-user business operations for Indian SMEs.',
        ],
    ],
    [
        '@type' => 'Question',
        'name' => 'Is TSA Legacy good for small businesses in India?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'Yes. It is designed for Indian small businesses that need affordable monthly billing and inventory software with cloud access and self-serve onboarding.',
        ],
    ],
    [
        '@type' => 'Question',
        'name' => 'Does TSA Legacy include both billing and inventory management?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'Yes. The platform combines GST billing, inventory management, customer records, supplier workflows, reports, and operational controls in one SaaS product.',
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Billing & Inventory Management Software for Indian SMEs | TSA Legacy</title>
    <meta name="description" content="TSA Legacy is GST billing and inventory management software for Indian SMEs. Manage invoices, products, stock, customers, suppliers and reports from one cloud platform.">
    <meta property="og:title" content="GST Billing & Inventory Management Software for Indian SMEs | TSA Legacy">
    <meta property="og:description" content="Cloud-native GST billing, inventory management and business software for Indian small businesses.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($homeUrl, ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <meta property="og:image:alt" content="TSA Legacy">
    <meta property="og:site_name" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="GST Billing & Inventory Management Software for Indian SMEs | TSA Legacy">
    <meta name="twitter:description" content="Cloud-native GST billing, inventory management and business software for Indian small businesses.">
    <meta name="twitter:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES) ?>">
    <script type="application/ld+json" nonce="<?= htmlspecialchars($cspNonce ?? '', ENT_QUOTES) ?>">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'TSA Legacy',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => $homeUrl,
            'description' => 'Cloud-native GST billing, inventory management, CRM and analytics software for Indian SMEs.',
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'INR',
                'lowPrice' => '99',
                'highPrice' => '4999',
                'offerCount' => '4',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'TSA Legacy Ventures',
                'url' => $homeUrl,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script type="application/ld+json" nonce="<?= htmlspecialchars($cspNonce ?? '', ENT_QUOTES) ?>">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqSchema,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script nonce="<?= htmlspecialchars($cspNonce ?? '', ENT_QUOTES) ?>">document.documentElement.classList.remove('no-js');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root{--p:#2563eb;--pd:#1d4ed8;--pl:#93c5fd;--ac:#60a5fa;--ac2:#38bdf8;--d:#06101b;--d2:#0f1f32;--d3:#1b3148;--card:rgba(255,255,255,.055);--brd:rgba(148,163,184,.16);--tx:#e6edf7;--mt:#b7c4d6;--w:#fff}
        *{margin:0;padding:0;box-sizing:border-box}html{scroll-behavior:smooth}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--d);color:var(--tx);overflow-x:hidden;-webkit-font-smoothing:antialiased}
        a{text-decoration:none;color:inherit}
        .mx{max-width:1200px;margin:0 auto;padding:0 24px}
        .mx-sm{max-width:960px;margin:0 auto;padding:0 24px}
        .mx-xs{max-width:720px;margin:0 auto;padding:0 24px}

        /* NAV */
        nav{position:fixed;top:0;left:0;right:0;z-index:100;backdrop-filter:blur(20px);background:rgba(6,16,27,.9);border-bottom:1px solid var(--brd);height:64px;display:flex;align-items:center;padding:0 20px;transition:background .3s}
        .nav-i{max-width:1200px;margin:0 auto;width:100%;display:flex;align-items:center;justify-content:space-between}
        .logo{display:flex;align-items:center;color:var(--w)}
        .logo img{height:34px;display:block}
        .nav-l{display:flex;gap:32px;align-items:center}
        .nav-l a{color:var(--mt);font-size:.88rem;font-weight:600;transition:color .2s}
        .nav-l a:hover{color:var(--w)}
        .nav-c{display:flex;gap:10px;align-items:center}
        .btn-g,.btn-p{min-height:48px;padding:11px 18px;border-radius:12px;font-size:.92rem;font-weight:700;transition:all .22s;display:inline-flex;align-items:center;justify-content:center;gap:8px}
        .btn-g{color:var(--w);border:1px solid rgba(147,197,253,.28);background:rgba(15,31,50,.84);box-shadow:inset 0 0 0 1px rgba(255,255,255,.02)}
        .btn-g:hover{background:rgba(24,45,70,.94);border-color:rgba(147,197,253,.48);color:var(--w)}
        .btn-p{color:#fff;background:linear-gradient(135deg,var(--pd),var(--p));box-shadow:0 10px 24px rgba(37,99,235,.34);border:1px solid rgba(147,197,253,.24);cursor:pointer}
        .btn-p:hover{transform:translateY(-1px);box-shadow:0 14px 30px rgba(37,99,235,.42)}
        .btn-lg{padding:15px 24px;border-radius:14px;font-size:1rem}
        .hamburger{display:none;background:none;border:none;color:var(--w);font-size:1.2rem;cursor:pointer}
        .mob-menu{display:none}

        /* HERO */
        .hero{min-height:min(88svh,820px);display:flex;align-items:center;padding:84px 20px 34px;position:relative;overflow:hidden;text-align:center}
        .hero-bg{position:absolute;inset:0;background:radial-gradient(ellipse 80% 50% at 50% -20%,rgba(37,99,235,.24),transparent),radial-gradient(ellipse 50% 40% at 80% 50%,rgba(96,165,250,.14),transparent)}
        .hero-grid{position:absolute;inset:0;opacity:.03;background-image:linear-gradient(var(--w) 1px,transparent 1px),linear-gradient(90deg,var(--w) 1px,transparent 1px);background-size:48px 48px}
        .hero-c{max-width:880px;margin:0 auto;position:relative;z-index:1}
        .badge{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;border:1px solid rgba(147,197,253,.24);background:rgba(147,197,253,.08);font-size:.76rem;font-weight:700;color:var(--pl);margin-bottom:18px;letter-spacing:.03em}
        .badge .dot{width:6px;height:6px;border-radius:50%;background:var(--ac2);animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(1.5)}}
        .hero h1{font-size:clamp(2.15rem,6vw,4rem);font-weight:900;line-height:1.03;letter-spacing:-.035em;color:var(--w);margin-bottom:14px}
        .gt{background:linear-gradient(135deg,var(--pl),var(--ac));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero p{font-size:1.02rem;color:var(--mt);max-width:700px;margin:0 auto 26px;line-height:1.72}
        .hero-btns{display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;margin-bottom:26px}
        .hero-proof{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin:0 0 24px}
        .hero-proof span{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid var(--brd);font-size:.79rem;color:var(--tx);font-weight:600}
        .hero-proof i,.badge i,.sec-tag i{width:1em;text-align:center}
        .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;max-width:760px;margin:0 auto}
        .stats > div{padding:14px 10px;border-radius:18px;background:rgba(255,255,255,.04);border:1px solid var(--brd)}
        .stat-n{font-size:1.2rem;font-weight:900}
        .stat-l{font-size:.72rem;color:var(--mt);font-weight:600;margin-top:4px}

        /* TRUST BAR */
        .trust{border-top:1px solid var(--brd);border-bottom:1px solid var(--brd);padding:28px 24px;text-align:center}
        .trust p{font-size:.7rem;color:rgba(148,163,184,.6);text-transform:uppercase;letter-spacing:.15em;font-weight:600;margin-bottom:18px}
        .trust-logos{display:flex;justify-content:center;align-items:center;gap:40px;flex-wrap:wrap;opacity:.35}
        .trust-logos span{font-size:1.1rem;font-weight:900;color:var(--mt)}

        /* SECTIONS */
        .sec{padding:84px 20px}
        .sec-alt{background:linear-gradient(180deg,rgba(15,23,42,.5) 0%,var(--d) 100%)}
        .sec-hd{text-align:center;margin-bottom:42px}
        .sec-tag{display:inline-flex;align-items:center;gap:6px;font-size:.7rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--pl);margin-bottom:12px}
        .sec-t{font-size:clamp(1.75rem,4vw,2.5rem);font-weight:900;color:var(--w);line-height:1.16;letter-spacing:-.025em;margin-bottom:12px}
        .sec-s{font-size:.98rem;color:var(--mt);max-width:620px;margin:0 auto;line-height:1.74}

        /* CARDS GRID */
        .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
        .grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
        .grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:40px;align-items:center}
        .card{background:var(--card);border:1px solid var(--brd);border-radius:20px;padding:24px;transition:all .3s;position:relative;overflow:hidden}
        .card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--p),transparent);opacity:0;transition:opacity .3s}
        .card:hover{transform:translateY(-4px);border-color:rgba(99,102,241,.3);box-shadow:0 0 40px rgba(99,102,241,.08)}
        .card:hover::before{opacity:1}
        .card-ic{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.08rem;margin-bottom:16px;flex-shrink:0}
        .card h3{font-size:1.04rem;font-weight:800;color:var(--w);margin-bottom:10px;line-height:1.35}
        .card p{font-size:.92rem;color:var(--mt);line-height:1.72}

        /* STEPS */
        .step{text-align:center;padding:28px 20px}
        .step-n{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--ac));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem;color:#fff;margin:0 auto 14px}

        /* ABOUT */
        .founder-card{background:var(--card);border:1px solid var(--brd);border-radius:24px;padding:30px;position:relative;overflow:hidden}
        .founder-card .corner{position:absolute;top:0;right:0;width:100px;height:100px;background:linear-gradient(135deg,rgba(99,102,241,.15),transparent);border-radius:0 0 0 100%}
        .av{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,var(--p),var(--ac));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;font-weight:900;margin-bottom:16px}
        .tag{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:8px;font-size:.7rem;font-weight:700;margin-right:6px;margin-bottom:6px}
        .tag-g{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);color:#34d399}
        .tag-b{background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);color:var(--pl)}
        .tag-c{background:rgba(6,182,212,.1);border:1px solid rgba(6,182,212,.2);color:#22d3ee}

        /* TESTIMONIALS */
        .t-card{background:var(--card);border:1px solid var(--brd);border-radius:20px;padding:24px}
        .stars{color:#f59e0b;font-size:.7rem;margin-bottom:8px}
        .t-text{font-size:.875rem;color:var(--tx);line-height:1.7;margin-bottom:20px;font-style:italic}
        .t-auth{display:flex;align-items:center;gap:12px}
        .t-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--ac));display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:700}
        .t-name{font-weight:600;font-size:.85rem;color:var(--w)}
        .t-role{font-size:.7rem;color:var(--mt)}

        /* PRICING */
        .p-card{background:var(--card);border:1px solid var(--brd);border-radius:22px;padding:28px;transition:all .3s;position:relative}
        .p-card:hover{transform:translateY(-4px);box-shadow:0 0 40px rgba(99,102,241,.08)}
        .p-card.pop{border-color:rgba(96,165,250,.34);background:linear-gradient(180deg,rgba(37,99,235,.14) 0%,rgba(6,16,27,1) 100%)}
        .pop-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,var(--p),var(--ac));color:#fff;padding:6px 16px;border-radius:999px;font-size:.68rem;font-weight:800;white-space:nowrap}
        .p-name{font-size:.75rem;font-weight:700;color:var(--mt);text-transform:uppercase;letter-spacing:.08em}
        .p-price{font-size:2.8rem;font-weight:900;color:var(--w);line-height:1;margin:12px 0 4px}
        .p-price sub{font-size:.9rem;font-weight:400;color:var(--mt)}
        .p-desc{font-size:.88rem;color:var(--mt);margin-bottom:18px;line-height:1.7}
        .p-feat{list-style:none;margin-bottom:24px}
        .p-feat li{display:flex;align-items:center;gap:8px;font-size:.82rem;padding:5px 0;color:var(--tx)}
        .p-feat .ck{color:var(--ac2);font-size:.7rem}
        .btn-plan{display:block;text-align:center;padding:10px;border-radius:10px;font-weight:700;font-size:.85rem;transition:all .2s}
        .seo-links{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
        .seo-link{display:block;padding:22px;border-radius:22px;background:var(--card);border:1px solid var(--brd);transition:transform .2s,border-color .2s,box-shadow .2s}
        .seo-link:hover{transform:translateY(-3px);border-color:rgba(99,102,241,.32);box-shadow:0 18px 40px rgba(2,6,23,.24)}
        .seo-link h3{color:var(--w);font-size:1rem;margin-bottom:10px}
        .seo-link p{color:var(--mt);font-size:.92rem;line-height:1.72}
        .faq-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
        .faq-card{padding:22px;border-radius:22px;background:var(--card);border:1px solid var(--brd)}
        .faq-card h3{color:var(--w);font-size:1.02rem;margin-bottom:10px;line-height:1.42}
        .faq-card p{color:var(--mt);font-size:.94rem;line-height:1.78}

        /* CTA */
        .cta-box{background:var(--card);border:1px solid rgba(96,165,250,.18);border-radius:28px;padding:42px 28px;text-align:center;position:relative;overflow:hidden}
        .cta-box .bg{position:absolute;inset:0;background:linear-gradient(135deg,rgba(37,99,235,.1),rgba(96,165,250,.06))}
        .cta-form{display:flex;gap:10px;max-width:420px;margin:0 auto 12px}
        .cta-input{flex:1;padding:14px 16px;border-radius:12px;background:rgba(255,255,255,.06);border:1px solid var(--brd);color:var(--w);font-size:.92rem;font-family:inherit;outline:none}
        .cta-input:focus{border-color:rgba(96,165,250,.46)}

        /* FOOTER */
        footer{border-top:1px solid var(--brd);padding:42px 20px 28px}
        .ft-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:32px;margin-bottom:32px}
        .ft-title{font-weight:600;font-size:.85rem;color:var(--w);margin-bottom:12px}
        .ft-links{display:flex;flex-direction:column;gap:8px}
        .ft-links a{color:var(--mt);font-size:.78rem;transition:color .2s}
        .ft-links a:hover{color:var(--w)}
        .ft-bar{border-top:1px solid var(--brd);padding-top:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
        .ft-copy{color:rgba(148,163,184,.5);font-size:.72rem}

        /* REVEAL ANIMATION */
        .rv{opacity:0;transform:translateY(16px);transition:opacity .5s ease,transform .5s ease}
        .rv.vis{opacity:1;transform:translateY(0)}
        .no-js .rv,.rv-fallback .rv{opacity:1!important;transform:none!important}

        /* RESPONSIVE */
        @media(max-width:1024px){.grid-3{grid-template-columns:repeat(2,1fr)}.grid-4{grid-template-columns:repeat(2,1fr)}.ft-grid{grid-template-columns:repeat(2,1fr)}.seo-links{grid-template-columns:1fr}.faq-grid{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:768px){
            .nav-l,.nav-c{display:none}
            .hamburger{display:block}
            .mob-menu.open{display:flex;flex-direction:column;position:fixed;top:64px;left:0;right:0;background:rgba(2,6,23,.98);backdrop-filter:blur(20px);border-bottom:1px solid var(--brd);padding:16px 24px;z-index:99;gap:4px}
            .mob-menu a{padding:10px 0;color:var(--tx);font-weight:500;border-bottom:1px solid var(--brd);font-size:.9rem}
            .grid-3,.grid-4{grid-template-columns:1fr}
            .grid-2{grid-template-columns:1fr}
            .hero{min-height:auto;padding:82px 16px 26px}
            .logo img{height:30px}
            .badge{margin-bottom:14px}
            .hero h1{font-size:2rem}
            .hero p{font-size:.96rem;margin-bottom:20px}
            .hero-btns{margin-bottom:18px}
            .hero-proof{gap:8px;margin-bottom:18px}
            .hero-proof span{font-size:.74rem;padding:8px 10px}
            .stats{gap:10px}
            .cta-form{flex-direction:column}
            .ft-grid{grid-template-columns:1fr 1fr}
            .p-card.pop{transform:none}
        }
        @media(max-width:480px){
            nav{padding:0 14px}
            .hero{padding:78px 14px 22px}
            .logo img{height:28px}
            .hero h1{font-size:1.86rem}
            .hero-proof span:nth-child(n+3){display:none}
            .btn-g,.btn-p,.btn-lg{width:100%}
            .stats{grid-template-columns:repeat(2,minmax(0,1fr))}
            .sec{padding:64px 14px}
            .sec-hd{margin-bottom:32px}
            .sec-t{font-size:1.7rem}
            .ft-grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body>

<!-- NAV -->
<nav id="mainNav">
    <div class="nav-i">
        <a href="<?= APP_URL ?>/" class="logo"><img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES) ?>" alt="TSA Legacy"></a>
        <div class="nav-l">
            <a href="#features">Features</a>
            <a href="#pricing">Pricing</a>
            <a href="#about">About</a>
            <a href="#tech">Technology</a>
        </div>
        <div class="nav-c">
            <a href="<?= APP_URL ?>/login" class="btn-g">Sign In</a>
            <a href="<?= APP_URL ?>/signup" class="btn-p">Start Free Trial</a>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Menu"><i class="fas fa-bars"></i></button>
    </div>
</nav>
<div class="mob-menu" id="mobMenu">
    <a href="#features" onclick="clM()">Features</a>
    <a href="#pricing" onclick="clM()">Pricing</a>
    <a href="#about" onclick="clM()">About</a>
    <a href="#tech" onclick="clM()">Technology</a>
    <a href="<?= APP_URL ?>/login">Sign In</a>
    <a href="<?= APP_URL ?>/signup" style="color:var(--pl);font-weight:700">Start Free Trial →</a>
</div>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg"></div><div class="hero-grid"></div>
    <div class="hero-c">
        <div class="badge"><span class="dot"></span>MSME Registered • India</div>
        <h1>All-in-One<br><span class="gt">Business OS</span><br>for Indian SMEs</h1>
        <p>Automate billing, manage inventory, track customers & suppliers, and get real-time analytics from one cloud-native software product built for Indian SMEs.</p>
        <div class="hero-btns">
            <a href="<?= APP_URL ?>/signup" class="btn-p btn-lg"><i class="fas fa-rocket"></i> Start Free Trial</a>
            <a href="<?= APP_URL ?>/demo" class="btn-g btn-lg"><i class="fas fa-play-circle"></i> Try Live Demo</a>
        </div>
        <div class="hero-proof">
            <span><i class="fas fa-code"></i>API-ready integrations</span>
            <span><i class="fas fa-file-import"></i>Bulk import workflows</span>
            <span><i class="fas fa-id-badge"></i>HR-ready operations</span>
            <span><i class="fas fa-brain"></i>AI-led insight surfaces</span>
        </div>
        <div class="stats">
            <div><div class="stat-n gt">GST</div><div class="stat-l">Billing Ready</div></div>
            <div><div class="stat-n gt">Cloud</div><div class="stat-l">Access Anywhere</div></div>
            <div><div class="stat-n gt">Role</div><div class="stat-l">Multi User Control</div></div>
            <div><div class="stat-n gt">Demo</div><div class="stat-l">Try Before Signup</div></div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<div class="trust">
        <p>Designed for GST billing, inventory management and daily business workflows across India</p>
    <div class="trust-logos">
        <span>Retail</span><span>Wholesale</span><span>Distribution</span><span>Services</span><span>Trading</span>
    </div>
</div>

<!-- FEATURES -->
<section class="sec" id="features">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag"><i class="fas fa-star"></i> Product Suite</div><h2 class="sec-t">Everything to run your<br>billing and inventory efficiently</h2><p class="sec-s">Six powerful modules purpose-built for Indian SMEs that need GST billing software, inventory management and cleaner daily operations.</p></div>
        <div class="grid-3">
            <?php
            $features = [
                ['i'=>'fa-file-invoice-dollar','bg'=>'rgba(99,102,241,.12)','c'=>'#818cf8','t'=>'GST Billing & Invoicing','d'=>'Professional GST-compliant invoices. Auto CGST/SGST/IGST calculation, PDF generation, print support, and quotation management.'],
                ['i'=>'fa-boxes-stacked','bg'=>'rgba(6,182,212,.12)','c'=>'#22d3ee','t'=>'Inventory Management','d'=>'Real-time stock tracking with low-stock alerts, SKU management, category & brand organization, and product analytics.'],
                ['i'=>'fa-chart-line','bg'=>'rgba(16,185,129,.12)','c'=>'#34d399','t'=>'Sales & Purchase Tracking','d'=>'End-to-end order lifecycle. Supplier & customer ledgers, payment history, outstanding balances.'],
                ['i'=>'fa-users','bg'=>'rgba(245,158,11,.12)','c'=>'#fbbf24','t'=>'Customer & Supplier CRM','d'=>'Complete profiles with transaction history, dues tracking, payment management and balance recalculation.'],
                ['i'=>'fa-chart-pie','bg'=>'rgba(239,68,68,.12)','c'=>'#f87171','t'=>'Reports, AI Insights & Audit','d'=>'Revenue dashboards, P&L, stock valuation, smart insight summaries, and audit trails for stronger operational visibility.'],
                ['i'=>'fa-building','bg'=>'rgba(139,92,246,.12)','c'=>'#a78bfa','t'=>'Enterprise Controls & Integrations','d'=>'Bulk import, API access, backup workflows, HR tools, and tenant-safe controls for scale-ready operations.'],
            ];
            foreach($features as $i=>$f): ?>
            <div class="card rv" style="transition-delay:<?= $i*60 ?>ms">
                <div class="card-ic" style="background:<?= $f['bg'] ?>;color:<?= $f['c'] ?>"><i class="fas <?= $f['i'] ?>"></i></div>
                <h3><?= $f['t'] ?></h3><p><?= $f['d'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="sec sec-alt">
    <div class="mx-sm">
        <div class="sec-hd rv"><div class="sec-tag" style="color:var(--ac)"><i class="fas fa-map"></i> Getting Started</div><h2 class="sec-t">Up and running in 3 minutes</h2><p class="sec-s">No complex setup. No IT team. Just sign up and start billing.</p></div>
        <div class="grid-4">
            <?php $steps=[['1','Create Account','Sign up free in 30 seconds. Business name + email — done.'],['2','Load Your Data','Add items manually or import products, customers, and suppliers in bulk.'],['3','Run Operations','Create GST invoices, manage purchases, track dues, and coordinate your team.'],['4','Scale with Control','Use reports, API access, AI insights, and HR tools as operations mature.']];
            foreach($steps as $i=>$s): ?>
            <div class="card step rv" style="transition-delay:<?= $i*80 ?>ms">
                <div class="step-n"><?= $s[0] ?></div><h3><?= $s[1] ?></h3><p><?= $s[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ABOUT / FOUNDER -->
<section class="sec" id="about">
    <div class="mx-sm">
        <div class="grid-2">
            <div class="rv">
                <div class="sec-tag"><i class="fas fa-heart"></i> Our Story</div>
                <h2 class="sec-t" style="text-align:left">Building India's Next<br><span class="gt">Business OS</span></h2>
                <p style="color:var(--mt);line-height:1.7;margin-bottom:14px">TSA Legacy Ventures started with a simple observation — millions of Indian small businesses still manage billing and inventory on paper or expensive tools that do not fit local workflows.</p>
                <p style="color:var(--mt);line-height:1.7;margin-bottom:14px">This is a product-led technology startup, not a services business. We are building a multi-tenant SaaS platform for Indian SMEs with cloud-native architecture, self-serve onboarding, and low-cost monthly plans.</p>
                <p style="color:var(--mt);line-height:1.7;margin-bottom:20px">Our vision: <strong style="color:var(--w)">Empower 1 million Indian businesses</strong> with affordable, world-class software infrastructure.</p>
                <div><span class="tag tag-g"><i class="fas fa-check-circle"></i> MSME Registered</span><span class="tag tag-b"><i class="fas fa-check-circle"></i> Udyam Verified</span><span class="tag tag-c"><i class="fas fa-check-circle"></i> India-based Startup</span></div>
            </div>
            <div class="founder-card rv" style="transition-delay:150ms">
                <div class="corner"></div>
                <div style="position:relative;z-index:1">
                    <div class="av">TK</div>
                    <h3 style="color:var(--w);font-size:1.15rem;margin-bottom:2px">Triloki Kumar Sharma</h3>
                    <p style="color:var(--pl);font-size:.8rem;font-weight:600;margin-bottom:14px">Founder & Developer, TSA Legacy Ventures</p>
                    <blockquote style="color:var(--mt);font-size:.85rem;line-height:1.7;font-style:italic;border-left:2px solid rgba(99,102,241,.3);padding-left:14px;margin-bottom:18px">"I believe every Indian business — from a kirana shop to a growing enterprise — deserves world-class software. TSA Legacy is being built as a scalable product company from India, with cloud infrastructure and pricing designed for real adoption."</blockquote>
                    <div style="display:flex;gap:24px;font-size:.8rem"><div><strong style="color:var(--w)">2025</strong><br><span style="font-size:.65rem;color:var(--mt)">Founded</span></div><div><strong style="color:var(--w)">India</strong><br><span style="font-size:.65rem;color:var(--mt)">HQ</span></div><div><strong style="color:var(--w)">SaaS</strong><br><span style="font-size:.65rem;color:var(--mt)">Model</span></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TECH -->
<section class="sec sec-alt" id="tech">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag" style="color:var(--ac)"><i class="fas fa-shield-halved"></i> Technology</div><h2 class="sec-t">Enterprise-grade architecture.<br>Startup-friendly pricing.</h2><p class="sec-s">Built as a cloud-native technology startup on modern infrastructure designed to scale from early MVP traction to large multi-tenant usage.</p></div>
        <div class="grid-3">
            <?php $tech=[['fa-cloud','Google Cloud Native','Built on Google Cloud infrastructure to support MVP launch, production workloads, and future scaling.'],['fa-lock','Enterprise Security','CSRF, RBAC, 2FA, encrypted sessions, rate limiting, audit logging.'],['fa-database','Multi-Tenant Isolation','True per-tenant data isolation with zero data leakage.'],['fa-bolt','Self-Serve SaaS','Online signup, plan-based billing, and low-touch onboarding for independent business adoption.'],['fa-code-branch','Modern Stack','PHP 8.2, MySQL 8.0, Nginx, Redis — battle-tested technologies.'],['fa-expand','Scale to 1M+ Users','Horizontal scaling, stateless architecture for multi-region expansion.']];
            foreach($tech as $i=>$t): ?>
            <div class="card rv" style="transition-delay:<?= $i*60 ?>ms">
                <div class="card-ic" style="background:rgba(6,182,212,.1);color:#22d3ee"><i class="fas <?= $t[0] ?>"></i></div>
                <h3><?= $t[1] ?></h3><p style="font-size:.8rem"><?= $t[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="sec">
    <div class="mx-sm">
        <div class="sec-hd rv"><div class="sec-tag"><i class="fas fa-heart"></i> Common Use Cases</div><h2 class="sec-t">Built for daily business operations</h2></div>
        <div class="grid-3">
            <?php $test=[['Fast GST billing, product lookup, and due tracking for counter sales and repeat customers.','Retail Teams','Billing Counters','RT'],['Shared inventory visibility, supplier balance tracking, and cleaner purchase workflows for stock-heavy operations.','Wholesale Teams','Stock Operations','WT'],['Multi-user access, reports, and returns handling for businesses moving from paper or spreadsheets.','Growing SMEs','Owner-led Teams','SM']];
            foreach($test as $i=>$t): ?>
            <div class="t-card rv" style="transition-delay:<?= $i*80 ?>ms">
                <div class="stars"><i class="fas fa-check-circle"></i> Use Case</div>
                <p class="t-text"><?= htmlspecialchars($t[0]) ?></p>
                <div class="t-auth"><div class="t-av"><?= $t[3] ?></div><div><div class="t-name"><?= $t[1] ?></div><div class="t-role"><?= $t[2] ?></div></div></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SEO LANDING LINKS -->
<section class="sec sec-alt">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag" style="color:var(--ac)"><i class="fas fa-magnifying-glass"></i> Popular Searches</div><h2 class="sec-t">Explore the software by<br>business need</h2><p class="sec-s">These focused pages explain how TSA Legacy fits common search intent around GST billing, inventory management, and small business software.</p></div>
        <div class="seo-links">
            <?php foreach ($seoLandingLinks as $index => $landing): ?>
            <a href="<?= htmlspecialchars($landing['href']) ?>" class="seo-link rv" style="transition-delay:<?= $index * 80 ?>ms">
                <h3><?= htmlspecialchars($landing['title']) ?></h3>
                <p><?= htmlspecialchars($landing['description']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="sec sec-alt" id="pricing">
    <div class="mx-sm">
        <div class="sec-hd rv"><div class="sec-tag"><i class="fas fa-tag"></i> Simple Pricing</div><h2 class="sec-t">Launch pricing that stays easy.<br>Built for Indian businesses.</h2><p class="sec-s">Start small, keep costs low, and upgrade only when your team or catalog grows. All plans include GST billing.</p></div>
        <div class="grid-3">
            <?php
            $plans = $plans ?? [];
            if (!empty($plans)): 
                $delay = 0;
                foreach ($plans as $plan): 
                    $isFeatured = !empty($plan['is_featured']);
                    $price = SaaSBillingHelper::effectivePlanPrice($plan);
                    $billing = strtolower($plan['billing_type'] ?? 'monthly');
                    $features = SaaSBillingHelper::extractPlanFeatures($plan, 5, 0);
                    $featuresList = $features['enabled'];
                    $limits = SaaSBillingHelper::planLimitsSummary($plan);
            ?>
            <div class="p-card <?= $isFeatured ? 'pop' : '' ?> rv" style="transition-delay:<?= $delay ?>ms">
                <?php if ($isFeatured): ?><div class="pop-badge">⚡ MOST POPULAR</div><?php endif; ?>
                <div class="p-name" <?= $isFeatured ? 'style="color:var(--pl)"' : '' ?>><?= htmlspecialchars($plan['name']) ?></div>
                <div class="p-price">₹<?= number_format($price) ?><sub>/<?= $billing ?></sub></div>
                <p class="p-desc"><?= htmlspecialchars($plan['description'] ?? 'For growing businesses') ?></p>
                <p class="p-desc" style="margin-top:-4px"><?= htmlspecialchars($limits['users_label']) ?> • <?= htmlspecialchars($limits['products_label']) ?></p>
                <ul class="p-feat">
                    <?php foreach ($featuresList as $feat): ?>
                    <li><span class="ck"><i class="fas fa-check-circle"></i></span><?= htmlspecialchars($feat) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan <?= $isFeatured ? 'btn-p' : 'btn-g' ?>" style="width:100%;justify-content:center">Start Free Trial</a>
            </div>
            <?php 
                $delay += 80;
                endforeach; 
            else: 
            ?>
            <div class="p-card rv">
                <div class="p-name">Free</div>
                <div class="p-price">₹0<sub>/forever</sub></div>
                <p class="p-desc">Perfect to get started</p>
                <p class="p-desc" style="margin-top:-4px">1 user • 100 products</p>
                <ul class="p-feat"><li><span class="ck"><i class="fas fa-check-circle"></i></span>GST invoicing</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Inventory management</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Basic reports</li></ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan btn-g" style="width:100%;justify-content:center">Get Started Free</a>
            </div>
            <div class="p-card pop rv" style="transition-delay:80ms">
                <div class="pop-badge">⚡ MOST POPULAR</div>
                <div class="p-name" style="color:var(--pl)">Starter</div>
                <div class="p-price">₹299<sub>/month</sub></div>
                <p class="p-desc">Core billing stack for new businesses</p>
                <p class="p-desc" style="margin-top:-4px">3 users • 500 products</p>
                <ul class="p-feat"><li><span class="ck"><i class="fas fa-check-circle"></i></span>GST invoicing</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Inventory & payments</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Audit trail & PDF export</li></ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan btn-p" style="width:100%;justify-content:center">Start Free Trial</a>
            </div>
            <div class="p-card rv" style="transition-delay:160ms">
                <div class="p-name">Professional</div>
                <div class="p-price">₹699<sub>/month</sub></div>
                <p class="p-desc">More users, reports, quotations and returns</p>
                <p class="p-desc" style="margin-top:-4px">10 users • 5,000 products</p>
                <ul class="p-feat"><li><span class="ck"><i class="fas fa-check-circle"></i></span>Advanced reports</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Quotations & sale returns</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Multi-user operations</li></ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan btn-g" style="width:100%;justify-content:center">Start Free Trial</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="sec">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag" style="color:var(--ac)"><i class="fas fa-circle-question"></i> FAQ</div><h2 class="sec-t">Questions people ask before<br>choosing business software</h2><p class="sec-s">These answers help clarify whether TSA Legacy fits small-business billing, inventory and multi-user workflows.</p></div>
        <div class="faq-grid">
            <div class="faq-card rv">
                <h3>What is TSA Legacy used for?</h3>
                <p>TSA Legacy is used for GST billing, inventory management, customer and supplier tracking, reporting, and multi-user business operations for Indian SMEs.</p>
            </div>
            <div class="faq-card rv" style="transition-delay:80ms">
                <h3>Is TSA Legacy good for small businesses in India?</h3>
                <p>Yes. It is designed for Indian small businesses that need affordable monthly billing and inventory software with cloud access and self-serve onboarding.</p>
            </div>
            <div class="faq-card rv" style="transition-delay:160ms">
                <h3>Does TSA Legacy include both billing and inventory management?</h3>
                <p>Yes. The platform combines GST billing, inventory management, customer records, supplier workflows, reports, and operational controls in one SaaS product.</p>
            </div>
            <div class="faq-card rv" style="transition-delay:240ms">
                <h3>Can growing teams use it with multiple users?</h3>
                <p>Yes. Plans support multi-user access, role-based control, shared inventory visibility, and operational workflows for growing business teams.</p>
            </div>
        </div>
    </div>
</section>

<section class="sec sec-alt">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag" style="color:var(--ac)"><i class="fas fa-pen"></i> Guides</div><h2 class="sec-t">Helpful articles for buyers<br>and small-business teams</h2><p class="sec-s">These support pages build authority around billing, inventory and software selection topics people search before buying.</p></div>
        <div class="seo-links">
            <a href="<?= APP_URL ?>/blog/how-to-choose-gst-billing-software" class="seo-link rv">
                <h3>How to choose GST billing software</h3>
                <p>A practical buyer guide for businesses comparing billing tools in India.</p>
            </a>
            <a href="<?= APP_URL ?>/blog/inventory-management-tips-small-business-india" class="seo-link rv" style="transition-delay:80ms">
                <h3>Inventory management tips for small businesses</h3>
                <p>Operational advice for better stock visibility, purchase control and fewer manual errors.</p>
            </a>
            <a href="<?= APP_URL ?>/blog/billing-software-vs-accounting-software" class="seo-link rv" style="transition-delay:160ms">
                <h3>Billing software vs accounting software</h3>
                <p>Understand what small businesses should buy first and when deeper accounting tools matter.</p>
            </a>
        </div>
        <div class="seo-links" style="margin-top:20px">
            <a href="<?= APP_URL ?>/blog/best-billing-software-for-kirana-shop" class="seo-link rv">
                <h3>Best billing software for kirana shop</h3>
                <p>Focus on faster counters, dues tracking and practical daily workflows for local stores.</p>
            </a>
            <a href="<?= APP_URL ?>/blog/wholesale-billing-software-features" class="seo-link rv" style="transition-delay:80ms">
                <h3>Wholesale billing software features</h3>
                <p>See what matters for supplier balances, bulk products, returns and stock-heavy operations.</p>
            </a>
            <a href="<?= APP_URL ?>/blog/retail-billing-software-checklist" class="seo-link rv" style="transition-delay:160ms">
                <h3>Retail billing software checklist</h3>
                <p>A practical selection checklist for retail teams comparing billing tools.</p>
            </a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="sec">
    <div class="mx-xs">
        <div class="cta-box rv">
            <div class="bg"></div>
            <div style="position:relative;z-index:1">
                <div class="badge" style="margin-bottom:20px"><span class="dot"></span>No card required</div>
                <h2 style="font-size:clamp(1.6rem,4vw,2.4rem);font-weight:900;color:var(--w);margin-bottom:12px">Ready to transform<br>your business?</h2>
                <p style="color:var(--mt);margin-bottom:28px;font-size:.95rem">Launch fast with billing and inventory, then grow into bulk imports, AI insights, API access, backup, and HR workflows on the same platform.</p>
                <form class="cta-form" action="<?= APP_URL ?>/index.php" method="get" onsubmit="return hL(event)">
                    <input type="hidden" name="page" value="signup">
                    <input type="email" id="le" name="email" placeholder="Enter your email" required class="cta-input">
                    <button type="submit" class="btn-p">Get Started</button>
                </form>
                <p id="lm" style="color:var(--ac2);font-size:.75rem;font-weight:600;display:none">🎉 Welcome! Redirecting to signup...</p>
                <p style="color:rgba(148,163,184,.5);font-size:.7rem">Free 14-day trial • No credit card • Cancel anytime</p>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="mx">
        <div class="ft-grid">
            <div>
                <a href="<?= APP_URL ?>/" class="logo" style="margin-bottom:10px"><img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES) ?>" alt="TSA Legacy" style="height:32px"></a>
                <p style="color:var(--mt);font-size:.78rem;line-height:1.6;margin:10px 0">Cloud-native business management platform built for Indian SMEs.</p>
                <p style="color:var(--mt);font-size:.72rem"><i class="fas fa-map-marker-alt" style="margin-right:4px"></i> India-based Startup</p>
            </div>
            <div><div class="ft-title">Product</div><div class="ft-links"><a href="#features">Features</a><a href="#pricing">Pricing</a><a href="<?= APP_URL ?>/blog">Guides</a><a href="<?= APP_URL ?>/demo">Live Demo</a><a href="<?= APP_URL ?>/signup">Sign Up</a></div></div>
            <div><div class="ft-title">Company</div><div class="ft-links"><a href="#about">About Us</a><a href="<?= APP_URL ?>/privacy">Privacy Policy</a><a href="<?= APP_URL ?>/terms">Terms of Service</a><a href="<?= APP_URL ?>/refund">Refund Policy</a></div></div>
            <div><div class="ft-title">Contact</div><div class="ft-links"><a href="mailto:hello@tsalegacy.com"><i class="fas fa-envelope" style="margin-right:4px"></i>hello@tsalegacy.com</a><span style="color:var(--mt);font-size:.78rem"><i class="fas fa-building" style="margin-right:4px"></i>TSA Legacy Ventures</span><span style="color:var(--mt);font-size:.78rem"><i class="fas fa-certificate" style="margin-right:4px"></i>MSME / Udyam Registered</span><span style="color:var(--mt);font-size:.78rem"><i class="fas fa-flag" style="margin-right:4px"></i>Made with ❤️ in India</span></div></div>
        </div>
        <div class="ft-bar">
            <p class="ft-copy">© 2025–<?= date('Y') ?> TSA Legacy Ventures. All rights reserved.</p>
            <div style="display:flex;align-items:center;gap:8px"><span class="ft-copy">Powered by</span><span style="color:var(--mt);font-size:.75rem;font-weight:600"><i class="fab fa-google" style="margin-right:3px"></i>Google Cloud</span></div>
        </div>
    </div>
</footer>

<script nonce="<?= htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES) ?>">
document.getElementById('hamburger').addEventListener('click',function(){document.getElementById('mobMenu').classList.toggle('open')});
function clM(){document.getElementById('mobMenu').classList.remove('open')}
var revEls=document.querySelectorAll('.rv');
if('IntersectionObserver' in window){
var ob=new IntersectionObserver(function(e){e.forEach(function(el){if(el.isIntersecting){el.target.classList.add('vis');ob.unobserve(el.target)}})},{threshold:.08,rootMargin:'0px 0px -20px 0px'});
revEls.forEach(function(el){ob.observe(el)});
}
setTimeout(function(){revEls.forEach(function(el){el.classList.add('vis')})},2500);
function hL(e){e.preventDefault();var em=document.getElementById('le').value;if(em){document.getElementById('lm').style.display='block';setTimeout(function(){window.location.href='<?= APP_URL ?>/signup?email='+encodeURIComponent(em)},1200)}return false}
window.addEventListener('scroll',function(){document.getElementById('mainNav').style.background=window.scrollY>50?'rgba(6,16,27,.96)':'rgba(6,16,27,.9)'});
</script>
</body>
</html>
