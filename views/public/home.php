<?php
$homeUrl = rtrim(APP_URL, '/') . '/';
$iconUrl = rtrim(APP_URL, '/') . '/assets/icon.svg';
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$logoUrl = rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.png';
$_nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? $cspNonce ?? '', ENT_QUOTES);
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
    <script type="application/ld+json" nonce="<?= $_nonce ?>">
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
    <script type="application/ld+json" nonce="<?= $_nonce ?>">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqSchema,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script nonce="<?= $_nonce ?>">document.documentElement.classList.remove('no-js');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/public.css">
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
    <a href="#features">Features</a>
    <a href="#pricing">Pricing</a>
    <a href="#about">About</a>
    <a href="#tech">Technology</a>
    <a href="<?= APP_URL ?>/login">Sign In</a>
    <a href="<?= APP_URL ?>/signup" class="mob-cta-link">Start Free Trial →</a>
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
            <span><i class="fas fa-brain"></i>Automated operational insight surfaces</span>
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
                ['i'=>'fa-file-invoice-dollar','delay'=>'delay-0','iconClass'=>'card-ic-billing','t'=>'GST Billing & Invoicing','d'=>'Professional GST-compliant invoices. Auto CGST/SGST/IGST calculation, PDF generation, print support, and quotation management.'],
                ['i'=>'fa-boxes-stacked','delay'=>'delay-60','iconClass'=>'card-ic-inventory','t'=>'Inventory Management','d'=>'Real-time stock tracking with low-stock alerts, SKU management, category & brand organization, and product analytics.'],
                ['i'=>'fa-chart-line','delay'=>'delay-120','iconClass'=>'card-ic-sales','t'=>'Sales & Purchase Tracking','d'=>'End-to-end order lifecycle. Supplier & customer ledgers, payment history, outstanding balances.'],
                ['i'=>'fa-users','delay'=>'delay-180','iconClass'=>'card-ic-crm','t'=>'Customer & Supplier CRM','d'=>'Complete profiles with transaction history, dues tracking, payment management and balance recalculation.'],
                ['i'=>'fa-chart-pie','delay'=>'delay-240','iconClass'=>'card-ic-reports','t'=>'Reports, Automated Insights & Audit','d'=>'Revenue dashboards, P&L, stock valuation, rule-based insight summaries, and audit trails for stronger operational visibility.'],
                ['i'=>'fa-building','delay'=>'delay-300','iconClass'=>'card-ic-enterprise','t'=>'Enterprise Controls & Integrations','d'=>'Bulk import, API access, backup workflows, HR tools, and tenant-safe controls for scale-ready operations.'],
            ];
            foreach($features as $f): ?>
            <div class="card rv delay-inline <?= $f['delay'] ?>">
                <div class="card-ic <?= $f['iconClass'] ?>"><i class="fas <?= $f['i'] ?>"></i></div>
                <h3><?= $f['t'] ?></h3><p><?= $f['d'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="sec sec-alt">
    <div class="mx-sm">
        <div class="sec-hd rv"><div class="sec-tag sec-tag-accent"><i class="fas fa-map"></i> Getting Started</div><h2 class="sec-t">Up and running in 3 minutes</h2><p class="sec-s">No complex setup. No IT team. Just sign up and start billing.</p></div>
        <div class="grid-4">
            <?php $steps=[['1','Create Account','Sign up free in 30 seconds. Business name + email — done.'],['2','Load Your Data','Add items manually or import products, customers, and suppliers in bulk.'],['3','Run Operations','Create GST invoices, manage purchases, track dues, and coordinate your team.'],['4','Scale with Control','Use reports, API access, automated insights, and HR tools as operations mature.']];
            foreach($steps as $i=>$s): ?>
            <?php $stepDelay = [0 => '', 1 => 'delay-80', 2 => 'delay-160', 3 => 'delay-240'][$i] ?? ''; ?>
            <div class="card step rv delay-inline <?= $stepDelay ?>">
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
                <h2 class="sec-t copy-left">Building India's Next<br><span class="gt">Business OS</span></h2>
                <p class="body-copy">TSA Legacy Ventures started with a simple observation — millions of Indian small businesses still manage billing and inventory on paper or expensive tools that do not fit local workflows.</p>
                <p class="body-copy">This is a product-led technology startup, not a services business. We are building a multi-tenant SaaS platform for Indian SMEs with cloud-native architecture, self-serve onboarding, and low-cost monthly plans.</p>
                <p class="body-copy-last">Our vision: <strong class="emphasis-strong">Empower 1 million Indian businesses</strong> with affordable, world-class software infrastructure.</p>
                <div><span class="tag tag-g"><i class="fas fa-check-circle"></i> MSME Registered</span><span class="tag tag-b"><i class="fas fa-check-circle"></i> Udyam Verified</span><span class="tag tag-c"><i class="fas fa-check-circle"></i> India-based Startup</span></div>
            </div>
            <div class="founder-card rv delay-inline delay-150">
                <div class="corner"></div>
                <div class="founder-copy">
                    <div class="av">TK</div>
                    <h3 class="founder-name">Triloki Kumar Sharma</h3>
                    <p class="founder-role">Founder & Developer, TSA Legacy Ventures</p>
                    <blockquote class="founder-quote">"I believe every Indian business — from a kirana shop to a growing enterprise — deserves world-class software. TSA Legacy is being built as a scalable product company from India, with cloud infrastructure and pricing designed for real adoption."</blockquote>
                    <div class="founder-stats"><div><strong>2025</strong><br><span>Founded</span></div><div><strong>India</strong><br><span>HQ</span></div><div><strong>SaaS</strong><br><span>Model</span></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TECH -->
<section class="sec sec-alt" id="tech">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag sec-tag-accent"><i class="fas fa-shield-halved"></i> Technology</div><h2 class="sec-t">Enterprise-grade architecture.<br>Startup-friendly pricing.</h2><p class="sec-s">Built as a cloud-native technology startup on modern infrastructure designed to scale from early MVP traction to large multi-tenant usage.</p></div>
        <div class="grid-3">
            <?php $tech=[['fa-cloud','Google Cloud Native','Built on Google Cloud infrastructure to support MVP launch, production workloads, and future scaling.'],['fa-lock','Enterprise Security','CSRF, RBAC, 2FA, encrypted sessions, rate limiting, audit logging.'],['fa-database','Multi-Tenant Isolation','True per-tenant data isolation with zero data leakage.'],['fa-bolt','Self-Serve SaaS','Online signup, plan-based billing, and low-touch onboarding for independent business adoption.'],['fa-code-branch','Modern Stack','PHP 8.2, MySQL 8.0, Nginx, Redis — battle-tested technologies.'],['fa-expand','Scale to 1M+ Users','Horizontal scaling, stateless architecture for multi-region expansion.']];
            foreach($tech as $i=>$t): ?>
            <?php $techDelay = [0 => '', 1 => 'delay-60', 2 => 'delay-120', 3 => 'delay-180', 4 => 'delay-240', 5 => 'delay-300'][$i] ?? ''; ?>
            <div class="card rv delay-inline <?= $techDelay ?>">
                <div class="card-ic card-ic-tech"><i class="fas <?= $t[0] ?>"></i></div>
                <h3><?= $t[1] ?></h3><p class="tech-copy-sm"><?= $t[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="sec" id="use-cases">
    <div class="mx-sm">
        <div class="sec-hd rv"><div class="sec-tag"><i class="fas fa-heart"></i> Common Use Cases</div><h2 class="sec-t">Built for daily business operations</h2></div>
        <div class="grid-3">
            <?php $test=[['Fast GST billing, product lookup, and due tracking for counter sales and repeat customers.','Retail Teams','Billing Counters','RT'],['Shared inventory visibility, supplier balance tracking, and cleaner purchase workflows for stock-heavy operations.','Wholesale Teams','Stock Operations','WT'],['Multi-user access, reports, and returns handling for businesses moving from paper or spreadsheets.','Growing SMEs','Owner-led Teams','SM']];
            foreach($test as $i=>$t): ?>
            <?php $testDelay = [0 => '', 1 => 'delay-80', 2 => 'delay-160'][$i] ?? ''; ?>
            <div class="t-card rv delay-inline <?= $testDelay ?>">
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
        <div class="sec-hd rv"><div class="sec-tag sec-tag-accent"><i class="fas fa-magnifying-glass"></i> Popular Searches</div><h2 class="sec-t">Explore the software by<br>business need</h2><p class="sec-s">These focused pages explain how TSA Legacy fits common search intent around GST billing, inventory management, and small business software.</p></div>
        <div class="seo-links">
            <?php foreach ($seoLandingLinks as $index => $landing): ?>
            <?php $seoDelay = [0 => '', 1 => 'delay-80', 2 => 'delay-160', 3 => 'delay-240', 4 => 'delay-320', 5 => 'delay-400'][$index] ?? 'delay-400'; ?>
            <a href="<?= htmlspecialchars($landing['href']) ?>" class="seo-link rv delay-inline <?= $seoDelay ?>">
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
                    $pricingDelayClass = [0 => '', 80 => 'delay-80', 160 => 'delay-160', 240 => 'delay-240', 320 => 'delay-320', 400 => 'delay-400'][$delay] ?? 'delay-400';
            ?>
            <div class="p-card <?= $isFeatured ? 'pop' : '' ?> rv delay-inline <?= $pricingDelayClass ?>">
                <?php if ($isFeatured): ?><div class="pop-badge">⚡ MOST POPULAR</div><?php endif; ?>
                <div class="p-name<?= $isFeatured ? ' p-name-featured' : '' ?>"><?= htmlspecialchars($plan['name']) ?></div>
                <div class="p-price">₹<?= number_format($price) ?><sub>/<?= $billing ?></sub></div>
                <p class="p-desc"><?= htmlspecialchars($plan['description'] ?? 'For growing businesses') ?></p>
                <p class="p-desc mt-neg-4"><?= htmlspecialchars($limits['users_label']) ?> • <?= htmlspecialchars($limits['products_label']) ?></p>
                <ul class="p-feat">
                    <?php foreach ($featuresList as $feat): ?>
                    <li><span class="ck"><i class="fas fa-check-circle"></i></span><?= htmlspecialchars($feat) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan <?= $isFeatured ? 'btn-p' : 'btn-g' ?> w-full-center">Start Free Trial</a>
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
                <p class="p-desc mt-neg-4">1 user • 100 products</p>
                <ul class="p-feat"><li><span class="ck"><i class="fas fa-check-circle"></i></span>GST invoicing</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Inventory management</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Basic reports</li></ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan btn-g w-full-center">Get Started Free</a>
            </div>
            <div class="p-card pop rv delay-inline delay-80">
                <div class="pop-badge">⚡ MOST POPULAR</div>
                <div class="p-name mob-cta-link">Starter</div>
                <div class="p-price">₹299<sub>/month</sub></div>
                <p class="p-desc">Core billing stack for new businesses</p>
                <p class="p-desc mt-neg-4">3 users • 500 products</p>
                <ul class="p-feat"><li><span class="ck"><i class="fas fa-check-circle"></i></span>GST invoicing</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Inventory & payments</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Audit trail & PDF export</li></ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan btn-p w-full-center">Start Free Trial</a>
            </div>
            <div class="p-card rv delay-inline delay-160">
                <div class="p-name">Professional</div>
                <div class="p-price">₹699<sub>/month</sub></div>
                <p class="p-desc">More users, reports, quotations and returns</p>
                <p class="p-desc mt-neg-4">10 users • 5,000 products</p>
                <ul class="p-feat"><li><span class="ck"><i class="fas fa-check-circle"></i></span>Advanced reports</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Quotations & sale returns</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Multi-user operations</li></ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan btn-g w-full-center">Start Free Trial</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="sec">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag sec-tag-accent"><i class="fas fa-circle-question"></i> FAQ</div><h2 class="sec-t">Questions people ask before<br>choosing business software</h2><p class="sec-s">These answers help clarify whether TSA Legacy fits small-business billing, inventory and multi-user workflows.</p></div>
        <div class="faq-grid">
            <div class="faq-card rv">
                <h3>What is TSA Legacy used for?</h3>
                <p>TSA Legacy is used for GST billing, inventory management, customer and supplier tracking, reporting, and multi-user business operations for Indian SMEs.</p>
            </div>
            <div class="faq-card rv delay-inline delay-80">
                <h3>Is TSA Legacy good for small businesses in India?</h3>
                <p>Yes. It is designed for Indian small businesses that need affordable monthly billing and inventory software with cloud access and self-serve onboarding.</p>
            </div>
            <div class="faq-card rv delay-inline delay-160">
                <h3>Does TSA Legacy include both billing and inventory management?</h3>
                <p>Yes. The platform combines GST billing, inventory management, customer records, supplier workflows, reports, and operational controls in one SaaS product.</p>
            </div>
            <div class="faq-card rv delay-inline delay-240">
                <h3>Can growing teams use it with multiple users?</h3>
                <p>Yes. Plans support multi-user access, role-based control, shared inventory visibility, and operational workflows for growing business teams.</p>
            </div>
        </div>
    </div>
</section>

<section class="sec sec-alt">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag sec-tag-accent"><i class="fas fa-pen"></i> Guides</div><h2 class="sec-t">Helpful articles for buyers<br>and small-business teams</h2><p class="sec-s">These support pages build authority around billing, inventory and software selection topics people search before buying.</p></div>
        <div class="seo-links">
            <a href="<?= APP_URL ?>/blog/how-to-choose-gst-billing-software" class="seo-link rv">
                <h3>How to choose GST billing software</h3>
                <p>A practical buyer guide for businesses comparing billing tools in India.</p>
            </a>
            <a href="<?= APP_URL ?>/blog/inventory-management-tips-small-business-india" class="seo-link rv delay-inline delay-80">
                <h3>Inventory management tips for small businesses</h3>
                <p>Operational advice for better stock visibility, purchase control and fewer manual errors.</p>
            </a>
            <a href="<?= APP_URL ?>/blog/billing-software-vs-accounting-software" class="seo-link rv delay-inline delay-160">
                <h3>Billing software vs accounting software</h3>
                <p>Understand what small businesses should buy first and when deeper accounting tools matter.</p>
            </a>
        </div>
        <div class="seo-links mt-20">
            <a href="<?= APP_URL ?>/blog/best-billing-software-for-kirana-shop" class="seo-link rv">
                <h3>Best billing software for kirana shop</h3>
                <p>Focus on faster counters, dues tracking and practical daily workflows for local stores.</p>
            </a>
            <a href="<?= APP_URL ?>/blog/wholesale-billing-software-features" class="seo-link rv delay-inline delay-80">
                <h3>Wholesale billing software features</h3>
                <p>See what matters for supplier balances, bulk products, returns and stock-heavy operations.</p>
            </a>
            <a href="<?= APP_URL ?>/blog/retail-billing-software-checklist" class="seo-link rv delay-inline delay-160">
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
            <div class="copy-stack">
                <div class="badge cta-badge-gap"><span class="dot"></span>No card required</div>
                <h2 class="cta-title">Ready to transform<br>your business?</h2>
                <p class="cta-copy">Launch fast with billing and inventory, then grow into bulk imports, automated insights, API access, backup, and HR workflows on the same platform.</p>
                <form class="cta-form" id="landingCtaForm" action="<?= APP_URL ?>/index.php" method="get">
                    <input type="hidden" name="page" value="signup">
                    <input type="email" id="le" name="email" placeholder="Enter your email" required class="cta-input">
                    <button type="submit" class="btn-p">Get Started</button>
                </form>
                <p id="lm" class="cta-success">🎉 Welcome! Redirecting to signup...</p>
                <p class="cta-legal">Free 14-day trial • No credit card • Cancel anytime</p>
            </div>
        </div>
    </div>
</section>

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
document.getElementById('landingCtaForm').addEventListener('submit',function(e){e.preventDefault();var em=document.getElementById('le').value;if(em){document.getElementById('lm').style.display='block';setTimeout(function(){window.location.href='<?= APP_URL ?>/signup?email='+encodeURIComponent(em)},1200)}});
window.addEventListener('scroll',function(){document.getElementById('mainNav').style.background=window.scrollY>50?'rgba(6,16,27,.96)':'rgba(6,16,27,.9)'});
</script>
</body>
</html>
