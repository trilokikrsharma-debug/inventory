<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TSA Legacy — All-in-One Business OS for Indian SMEs | Billing, Inventory & Analytics</title>
    <meta name="description" content="TSA Legacy is a cloud-native SaaS platform for Indian SMEs. Manage invoices, inventory, customers, suppliers & analytics — all in one place. MSME registered startup.">
    <meta name="keywords" content="business OS, GST billing, inventory management, SaaS India, SME software, startup, multi-tenant">
    <meta property="og:title" content="TSA Legacy — All-in-One Business OS for Indian SMEs">
    <meta property="og:description" content="Cloud-native billing, inventory & business management platform built for Indian small businesses.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://tsalegacy.shop">
    <link rel="canonical" href="https://tsalegacy.shop">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root{--p:#6366f1;--pd:#4f46e5;--pl:#818cf8;--ac:#06b6d4;--ac2:#10b981;--d:#020617;--d2:#0f172a;--d3:#1e293b;--card:rgba(255,255,255,.04);--brd:rgba(255,255,255,.07);--tx:#e2e8f0;--mt:#94a3b8;--w:#fff}
        *{margin:0;padding:0;box-sizing:border-box}html{scroll-behavior:smooth}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--d);color:var(--tx);overflow-x:hidden;-webkit-font-smoothing:antialiased}
        a{text-decoration:none;color:inherit}
        .mx{max-width:1200px;margin:0 auto;padding:0 24px}
        .mx-sm{max-width:960px;margin:0 auto;padding:0 24px}
        .mx-xs{max-width:720px;margin:0 auto;padding:0 24px}

        /* NAV */
        nav{position:fixed;top:0;left:0;right:0;z-index:100;backdrop-filter:blur(20px);background:rgba(2,6,23,.85);border-bottom:1px solid var(--brd);height:64px;display:flex;align-items:center;padding:0 24px;transition:background .3s}
        .nav-i{max-width:1200px;margin:0 auto;width:100%;display:flex;align-items:center;justify-content:space-between}
        .logo{display:flex;align-items:center;gap:10px;font-weight:900;font-size:1.15rem;color:var(--w)}
        .logo-ic{width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,var(--p),var(--ac));display:flex;align-items:center;justify-content:center;font-size:.8rem;color:#fff}
        .logo .hl{color:var(--pl)}
        .nav-l{display:flex;gap:32px;align-items:center}
        .nav-l a{color:var(--mt);font-size:.875rem;font-weight:500;transition:color .2s}
        .nav-l a:hover{color:var(--w)}
        .nav-c{display:flex;gap:10px;align-items:center}
        .btn-g{padding:8px 18px;border-radius:8px;font-size:.8rem;font-weight:600;color:var(--tx);border:1px solid var(--brd);transition:all .2s;display:inline-flex;align-items:center;gap:6px}
        .btn-g:hover{background:rgba(255,255,255,.05);color:var(--w)}
        .btn-p{padding:8px 20px;border-radius:8px;font-size:.8rem;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--pd),var(--p));box-shadow:0 4px 16px rgba(99,102,241,.3);transition:all .2s;display:inline-flex;align-items:center;gap:6px;border:none;cursor:pointer}
        .btn-p:hover{transform:translateY(-1px);box-shadow:0 6px 24px rgba(99,102,241,.45)}
        .btn-lg{padding:14px 32px;border-radius:12px;font-size:1rem}
        .hamburger{display:none;background:none;border:none;color:var(--w);font-size:1.2rem;cursor:pointer}
        .mob-menu{display:none}

        /* HERO */
        .hero{min-height:100vh;display:flex;align-items:center;padding:100px 24px 60px;position:relative;overflow:hidden;text-align:center}
        .hero-bg{position:absolute;inset:0;background:radial-gradient(ellipse 80% 50% at 50% -20%,rgba(99,102,241,.28),transparent),radial-gradient(ellipse 50% 40% at 80% 50%,rgba(6,182,212,.12),transparent)}
        .hero-grid{position:absolute;inset:0;opacity:.03;background-image:linear-gradient(var(--w) 1px,transparent 1px),linear-gradient(90deg,var(--w) 1px,transparent 1px);background-size:48px 48px}
        .hero-c{max-width:880px;margin:0 auto;position:relative;z-index:1}
        .badge{display:inline-flex;align-items:center;gap:8px;padding:6px 16px;border-radius:50px;border:1px solid rgba(99,102,241,.35);background:rgba(99,102,241,.08);font-size:.75rem;font-weight:700;color:var(--pl);margin-bottom:28px;letter-spacing:.03em}
        .badge .dot{width:6px;height:6px;border-radius:50%;background:var(--ac2);animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(1.5)}}
        .hero h1{font-size:clamp(2.4rem,7vw,4.2rem);font-weight:900;line-height:1.08;letter-spacing:-.03em;color:var(--w);margin-bottom:20px}
        .gt{background:linear-gradient(135deg,var(--pl),var(--ac));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero p{font-size:1.1rem;color:var(--mt);max-width:600px;margin:0 auto 36px;line-height:1.7}
        .hero-btns{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;margin-bottom:48px}
        .stats{display:flex;justify-content:center;gap:48px;flex-wrap:wrap}
        .stat-n{font-size:1.6rem;font-weight:900}
        .stat-l{font-size:.7rem;color:var(--mt);font-weight:500;margin-top:2px}

        /* TRUST BAR */
        .trust{border-top:1px solid var(--brd);border-bottom:1px solid var(--brd);padding:28px 24px;text-align:center}
        .trust p{font-size:.7rem;color:rgba(148,163,184,.6);text-transform:uppercase;letter-spacing:.15em;font-weight:600;margin-bottom:18px}
        .trust-logos{display:flex;justify-content:center;align-items:center;gap:40px;flex-wrap:wrap;opacity:.35}
        .trust-logos span{font-size:1.1rem;font-weight:900;color:var(--mt)}

        /* SECTIONS */
        .sec{padding:100px 24px}
        .sec-alt{background:linear-gradient(180deg,rgba(15,23,42,.5) 0%,var(--d) 100%)}
        .sec-hd{text-align:center;margin-bottom:56px}
        .sec-tag{display:inline-flex;align-items:center;gap:6px;font-size:.7rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--pl);margin-bottom:12px}
        .sec-t{font-size:clamp(1.8rem,4vw,2.6rem);font-weight:900;color:var(--w);line-height:1.2;letter-spacing:-.02em;margin-bottom:12px}
        .sec-s{font-size:.95rem;color:var(--mt);max-width:520px;margin:0 auto;line-height:1.7}

        /* CARDS GRID */
        .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
        .grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
        .grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:40px;align-items:center}
        .card{background:var(--card);border:1px solid var(--brd);border-radius:20px;padding:28px;transition:all .3s;position:relative;overflow:hidden}
        .card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--p),transparent);opacity:0;transition:opacity .3s}
        .card:hover{transform:translateY(-4px);border-color:rgba(99,102,241,.3);box-shadow:0 0 40px rgba(99,102,241,.08)}
        .card:hover::before{opacity:1}
        .card-ic{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:16px}
        .card h3{font-size:1rem;font-weight:700;color:var(--w);margin-bottom:8px}
        .card p{font-size:.85rem;color:var(--mt);line-height:1.6}

        /* STEPS */
        .step{text-align:center;padding:28px 20px}
        .step-n{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--ac));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem;color:#fff;margin:0 auto 14px}

        /* ABOUT */
        .founder-card{background:var(--card);border:1px solid var(--brd);border-radius:24px;padding:36px;position:relative;overflow:hidden}
        .founder-card .corner{position:absolute;top:0;right:0;width:100px;height:100px;background:linear-gradient(135deg,rgba(99,102,241,.15),transparent);border-radius:0 0 0 100%}
        .av{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,var(--p),var(--ac));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;font-weight:900;margin-bottom:16px}
        .tag{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:8px;font-size:.7rem;font-weight:700;margin-right:6px;margin-bottom:6px}
        .tag-g{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);color:#34d399}
        .tag-b{background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);color:var(--pl)}
        .tag-c{background:rgba(6,182,212,.1);border:1px solid rgba(6,182,212,.2);color:#22d3ee}

        /* TESTIMONIALS */
        .t-card{background:var(--card);border:1px solid var(--brd);border-radius:20px;padding:28px}
        .stars{color:#f59e0b;font-size:.7rem;margin-bottom:8px}
        .t-text{font-size:.875rem;color:var(--tx);line-height:1.7;margin-bottom:20px;font-style:italic}
        .t-auth{display:flex;align-items:center;gap:12px}
        .t-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--ac));display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:700}
        .t-name{font-weight:600;font-size:.85rem;color:var(--w)}
        .t-role{font-size:.7rem;color:var(--mt)}

        /* PRICING */
        .p-card{background:var(--card);border:1px solid var(--brd);border-radius:22px;padding:32px;transition:all .3s;position:relative}
        .p-card:hover{transform:translateY(-4px);box-shadow:0 0 40px rgba(99,102,241,.08)}
        .p-card.pop{border-color:rgba(99,102,241,.4);background:linear-gradient(180deg,rgba(99,102,241,.1) 0%,rgba(2,6,23,1) 100%)}
        .pop-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,var(--p),var(--ac));color:#fff;padding:4px 16px;border-radius:50px;font-size:.65rem;font-weight:700;white-space:nowrap}
        .p-name{font-size:.75rem;font-weight:700;color:var(--mt);text-transform:uppercase;letter-spacing:.08em}
        .p-price{font-size:2.8rem;font-weight:900;color:var(--w);line-height:1;margin:12px 0 4px}
        .p-price sub{font-size:.9rem;font-weight:400;color:var(--mt)}
        .p-desc{font-size:.8rem;color:var(--mt);margin-bottom:20px}
        .p-feat{list-style:none;margin-bottom:24px}
        .p-feat li{display:flex;align-items:center;gap:8px;font-size:.82rem;padding:5px 0;color:var(--tx)}
        .p-feat .ck{color:var(--ac2);font-size:.7rem}
        .btn-plan{display:block;text-align:center;padding:10px;border-radius:10px;font-weight:700;font-size:.85rem;transition:all .2s}

        /* CTA */
        .cta-box{background:var(--card);border:1px solid rgba(99,102,241,.2);border-radius:28px;padding:56px 40px;text-align:center;position:relative;overflow:hidden}
        .cta-box .bg{position:absolute;inset:0;background:linear-gradient(135deg,rgba(99,102,241,.08),rgba(6,182,212,.05))}
        .cta-form{display:flex;gap:10px;max-width:420px;margin:0 auto 12px}
        .cta-input{flex:1;padding:12px 16px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid var(--brd);color:var(--w);font-size:.85rem;font-family:inherit;outline:none}
        .cta-input:focus{border-color:rgba(99,102,241,.4)}

        /* FOOTER */
        footer{border-top:1px solid var(--brd);padding:48px 24px 32px}
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
        @media(max-width:1024px){.grid-3{grid-template-columns:repeat(2,1fr)}.grid-4{grid-template-columns:repeat(2,1fr)}.ft-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:768px){
            .nav-l,.nav-c{display:none}
            .hamburger{display:block}
            .mob-menu.open{display:flex;flex-direction:column;position:fixed;top:64px;left:0;right:0;background:rgba(2,6,23,.98);backdrop-filter:blur(20px);border-bottom:1px solid var(--brd);padding:16px 24px;z-index:99;gap:4px}
            .mob-menu a{padding:10px 0;color:var(--tx);font-weight:500;border-bottom:1px solid var(--brd);font-size:.9rem}
            .grid-3,.grid-4{grid-template-columns:1fr}
            .grid-2{grid-template-columns:1fr}
            .stats{gap:24px}
            .hero h1{font-size:2rem}
            .cta-form{flex-direction:column}
            .ft-grid{grid-template-columns:1fr 1fr}
            .p-card.pop{transform:none}
        }
    </style>
</head>
<body>

<!-- NAV -->
<nav id="mainNav">
    <div class="nav-i">
        <a href="<?= APP_URL ?>/" class="logo"><div class="logo-ic"><i class="fas fa-bolt"></i></div>TSA<span class="hl">Legacy</span></a>
        <div class="nav-l">
            <a href="#features">Features</a>
            <a href="#pricing">Pricing</a>
            <a href="#about">About</a>
            <a href="#tech">Technology</a>
        </div>
        <div class="nav-c">
            <a href="<?= APP_URL ?>/index.php?page=login" class="btn-g">Sign In</a>
            <a href="<?= APP_URL ?>/index.php?page=signup" class="btn-p">Start Free Trial</a>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Menu"><i class="fas fa-bars"></i></button>
    </div>
</nav>
<div class="mob-menu" id="mobMenu">
    <a href="#features" onclick="clM()">Features</a>
    <a href="#pricing" onclick="clM()">Pricing</a>
    <a href="#about" onclick="clM()">About</a>
    <a href="#tech" onclick="clM()">Technology</a>
    <a href="<?= APP_URL ?>/index.php?page=login">Sign In</a>
    <a href="<?= APP_URL ?>/index.php?page=signup" style="color:var(--pl);font-weight:700">Start Free Trial →</a>
</div>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg"></div><div class="hero-grid"></div>
    <div class="hero-c">
        <div class="badge"><span class="dot"></span>MSME Registered Startup (Udyam Verified) • India</div>
        <h1>All-in-One<br><span class="gt">Business OS</span><br>for Indian SMEs</h1>
        <p>Automate billing, manage inventory, track customers & suppliers, and get real-time analytics — everything your business needs in one powerful cloud platform.</p>
        <div class="hero-btns">
            <a href="<?= APP_URL ?>/index.php?page=signup" class="btn-p btn-lg"><i class="fas fa-rocket"></i> Start Free Trial — No Card Required</a>
            <a href="<?= APP_URL ?>/index.php?page=demo_login" class="btn-g btn-lg"><i class="fas fa-play-circle"></i> Try Live Demo</a>
        </div>
        <div class="stats">
            <div><div class="stat-n gt">500+</div><div class="stat-l">Active Users</div></div>
            <div><div class="stat-n gt">₹2Cr+</div><div class="stat-l">Invoices Generated</div></div>
            <div><div class="stat-n gt">99.9%</div><div class="stat-l">Uptime SLA</div></div>
            <div><div class="stat-n gt">50+</div><div class="stat-l">Cities Served</div></div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<div class="trust">
    <p>Trusted by growing businesses across India</p>
    <div class="trust-logos">
        <span>ShopEasy</span><span>TradeMart</span><span>BillDesk<span style="color:var(--pl)">Pro</span></span><span>StockKart</span><span>RetailHub</span>
    </div>
</div>

<!-- FEATURES -->
<section class="sec" id="features">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag"><i class="fas fa-star"></i> Product Suite</div><h2 class="sec-t">Everything to run your<br>business efficiently</h2><p class="sec-s">Six powerful modules purpose-built for Indian SMEs — from kirana shops to growing enterprises.</p></div>
        <div class="grid-3">
            <?php
            $features = [
                ['i'=>'fa-file-invoice-dollar','bg'=>'rgba(99,102,241,.12)','c'=>'#818cf8','t'=>'GST Billing & Invoicing','d'=>'Professional GST-compliant invoices. Auto CGST/SGST/IGST calculation, PDF generation, print support, and quotation management.'],
                ['i'=>'fa-boxes-stacked','bg'=>'rgba(6,182,212,.12)','c'=>'#22d3ee','t'=>'Inventory Management','d'=>'Real-time stock tracking with low-stock alerts, SKU management, category & brand organization, and product analytics.'],
                ['i'=>'fa-chart-line','bg'=>'rgba(16,185,129,.12)','c'=>'#34d399','t'=>'Sales & Purchase Tracking','d'=>'End-to-end order lifecycle. Supplier & customer ledgers, payment history, outstanding balances.'],
                ['i'=>'fa-users','bg'=>'rgba(245,158,11,.12)','c'=>'#fbbf24','t'=>'Customer & Supplier CRM','d'=>'Complete profiles with transaction history, dues tracking, payment management and balance recalculation.'],
                ['i'=>'fa-chart-pie','bg'=>'rgba(239,68,68,.12)','c'=>'#f87171','t'=>'Reports & Analytics','d'=>'Revenue dashboards, P&L, stock valuation, purchase trends — real-time insights for smarter decisions.'],
                ['i'=>'fa-building','bg'=>'rgba(139,92,246,.12)','c'=>'#a78bfa','t'=>'Multi-Tenant SaaS Engine','d'=>'True data isolation, custom branding, subscription management. Built for scale from day one.'],
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
            <?php $steps=[['1','Create Account','Sign up free in 30 seconds. Business name + email — done.'],['2','Add Products','Add products with categories, brands, pricing & GST tax rates.'],['3','Start Billing','Create GST invoices, process sales, manage purchases.'],['4','Grow & Scale','Analytics, reports & insights for data-driven growth.']];
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
                <p style="color:var(--mt);line-height:1.7;margin-bottom:14px">TSA Legacy Ventures started with a simple observation — millions of Indian small businesses manage billing and inventory on paper or expensive software not built for them.</p>
                <p style="color:var(--mt);line-height:1.7;margin-bottom:14px">As a solo founder, I built this from the ground up — cloud-native architecture with deep understanding of Indian business needs. Every feature is designed for simplicity, speed, and scale.</p>
                <p style="color:var(--mt);line-height:1.7;margin-bottom:20px">Our vision: <strong style="color:var(--w)">Empower 1 million Indian businesses</strong> with affordable, world-class tools.</p>
                <div><span class="tag tag-g"><i class="fas fa-check-circle"></i> MSME Registered</span><span class="tag tag-b"><i class="fas fa-check-circle"></i> Udyam Verified</span><span class="tag tag-c"><i class="fas fa-check-circle"></i> India-based Startup</span></div>
            </div>
            <div class="founder-card rv" style="transition-delay:150ms">
                <div class="corner"></div>
                <div style="position:relative;z-index:1">
                    <div class="av">TK</div>
                    <h3 style="color:var(--w);font-size:1.15rem;margin-bottom:2px">Triloki Kumar Sharma</h3>
                    <p style="color:var(--pl);font-size:.8rem;font-weight:600;margin-bottom:14px">Founder & Developer, TSA Legacy Ventures</p>
                    <blockquote style="color:var(--mt);font-size:.85rem;line-height:1.7;font-style:italic;border-left:2px solid rgba(99,102,241,.3);padding-left:14px;margin-bottom:18px">"I believe every Indian business — from a kirana shop to a growing enterprise — deserves world-class software. That's why we built TSA Legacy: affordable, powerful, and truly made for India."</blockquote>
                    <div style="display:flex;gap:24px;font-size:.8rem"><div><strong style="color:var(--w)">2025</strong><br><span style="font-size:.65rem;color:var(--mt)">Founded</span></div><div><strong style="color:var(--w)">India</strong><br><span style="font-size:.65rem;color:var(--mt)">HQ</span></div><div><strong style="color:var(--w)">SaaS</strong><br><span style="font-size:.65rem;color:var(--mt)">Model</span></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TECH -->
<section class="sec sec-alt" id="tech">
    <div class="mx">
        <div class="sec-hd rv"><div class="sec-tag" style="color:var(--ac)"><i class="fas fa-shield-halved"></i> Technology</div><h2 class="sec-t">Enterprise-grade architecture.<br>Startup-friendly pricing.</h2><p class="sec-s">Built on modern cloud infrastructure designed to scale from 10 to 1,000,000+ users.</p></div>
        <div class="grid-3">
            <?php $tech=[['fa-cloud','Google Cloud Native','Deployed on Google Cloud with auto-scaling infrastructure and 99.9% uptime.'],['fa-lock','Enterprise Security','CSRF, RBAC, 2FA, encrypted sessions, rate limiting, audit logging.'],['fa-database','Multi-Tenant Isolation','True per-tenant data isolation with zero data leakage.'],['fa-bolt','High Performance','Redis caching, OPcache, CDN-ready assets. Sub-200ms responses.'],['fa-code-branch','Modern Stack','PHP 8.2, MySQL 8.0, Nginx, Redis — battle-tested technologies.'],['fa-expand','Scale to 1M+ Users','Horizontal scaling, stateless architecture for multi-region expansion.']];
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
        <div class="sec-hd rv"><div class="sec-tag"><i class="fas fa-heart"></i> Customer Stories</div><h2 class="sec-t">Loved by businesses across India</h2></div>
        <div class="grid-3">
            <?php $test=[['TSA Legacy transformed our billing. 20-minute manual invoicing became 30-second automated GST bills. Game changer!','Ramesh Kumar','Retail Owner, Jaipur','RK'],['Multi-tenant feature is brilliant — 3 businesses under one platform. Inventory tracking saves us hours weekly.','Sunita Patel','Wholesale, Mumbai','SP'],['Finally a SaaS for Indian businesses. GST, supplier ledgers, CRM — everything works. Unbeatable price.','Amit Verma','Hardware Store, Delhi','AV']];
            foreach($test as $i=>$t): ?>
            <div class="t-card rv" style="transition-delay:<?= $i*80 ?>ms">
                <div class="stars">★★★★★</div>
                <p class="t-text">"<?= $t[0] ?>"</p>
                <div class="t-auth"><div class="t-av"><?= $t[3] ?></div><div><div class="t-name"><?= $t[1] ?></div><div class="t-role"><?= $t[2] ?></div></div></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="sec sec-alt" id="pricing">
    <div class="mx-sm">
        <div class="sec-hd rv"><div class="sec-tag"><i class="fas fa-tag"></i> Simple Pricing</div><h2 class="sec-t">Transparent pricing.<br>Built for Indian businesses.</h2><p class="sec-s">Start free, upgrade when ready. All plans include GST billing.</p></div>
        <div class="grid-3">
            <?php
            $plans = $plans ?? [];
            if (!empty($plans)): 
                $delay = 0;
                $featureLabels = ['inventory' => 'Inventory', 'invoicing' => 'GST Invoicing', 'api' => 'API Access', 'crm' => 'CRM Tools', 'hr' => 'HR Tools', 'multi_user' => 'Multi User', 'backup' => 'Backup & Restore', 'backup_restore' => 'Backup & Restore', 'advanced_reports' => 'Advanced Reports'];
                foreach ($plans as $plan): 
                    $isFeatured = !empty($plan['is_featured']);
                    $price = isset($plan['offer_price']) && $plan['offer_price'] > 0 && $plan['offer_price'] < $plan['price'] ? $plan['offer_price'] : $plan['price'];
                    $billing = strtolower($plan['billing_type'] ?? 'monthly');
                    
                    // Extract up to 5 enabled features
                    $rawF = $plan['features'] ?? '';
                    $decoded = is_string($rawF) ? json_decode($rawF, true) : (is_array($rawF) ? $rawF : []);
                    $featuresList = [];
                    if (is_array($decoded)) {
                        $isAssoc = array_keys($decoded) !== range(0, count($decoded) - 1);
                        foreach ($decoded as $k => $v) {
                            if ($isAssoc && !$v) continue;
                            $key = $isAssoc ? $k : $v;
                            $norm = strtolower(preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', $key)));
                            $featuresList[] = $featureLabels[$norm] ?? ucwords(str_replace('_', ' ', $norm));
                        }
                    }
                    if (empty($featuresList)) $featuresList = ['Inventory Management', 'GST Invoicing', 'Basic Reports'];
                    $featuresList = array_slice(array_unique($featuresList), 0, 5);
            ?>
            <div class="p-card <?= $isFeatured ? 'pop' : '' ?> rv" style="transition-delay:<?= $delay ?>ms">
                <?php if ($isFeatured): ?><div class="pop-badge">⚡ MOST POPULAR</div><?php endif; ?>
                <div class="p-name" <?= $isFeatured ? 'style="color:var(--pl)"' : '' ?>><?= htmlspecialchars($plan['name']) ?></div>
                <div class="p-price">₹<?= number_format($price) ?><sub>/<?= $billing ?></sub></div>
                <p class="p-desc"><?= htmlspecialchars($plan['description'] ?? 'For growing businesses') ?></p>
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
                <ul class="p-feat"><li><span class="ck"><i class="fas fa-check-circle"></i></span>Up to 2 users</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>100 products</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>GST invoicing</li></ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan btn-g" style="width:100%;justify-content:center">Get Started Free</a>
            </div>
            <div class="p-card pop rv" style="transition-delay:80ms">
                <div class="pop-badge">⚡ MOST POPULAR</div>
                <div class="p-name" style="color:var(--pl)">Starter</div>
                <div class="p-price">₹99<sub>/month</sub></div>
                <p class="p-desc">For growing businesses</p>
                <ul class="p-feat"><li><span class="ck"><i class="fas fa-check-circle"></i></span>Unlimited products</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Advanced reports</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Quotations & returns</li></ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan btn-p" style="width:100%;justify-content:center">Start Free Trial</a>
            </div>
            <div class="p-card rv" style="transition-delay:160ms">
                <div class="p-name">Pro</div>
                <div class="p-price">₹299<sub>/month</sub></div>
                <p class="p-desc">Full power for enterprises</p>
                <ul class="p-feat"><li><span class="ck"><i class="fas fa-check-circle"></i></span>Multi-business</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>2FA security</li><li><span class="ck"><i class="fas fa-check-circle"></i></span>Backup & restore</li></ul>
                <a href="<?= APP_URL ?>/signup" class="btn-plan btn-g" style="width:100%;justify-content:center">Start Free Trial</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="sec">
    <div class="mx-xs">
        <div class="cta-box rv">
            <div class="bg"></div>
            <div style="position:relative;z-index:1">
                <div class="badge" style="margin-bottom:20px"><span class="dot"></span>No credit card required</div>
                <h2 style="font-size:clamp(1.6rem,4vw,2.4rem);font-weight:900;color:var(--w);margin-bottom:12px">Ready to transform<br>your business?</h2>
                <p style="color:var(--mt);margin-bottom:28px;font-size:.95rem">Join hundreds of Indian businesses using TSA Legacy to automate billing, manage inventory, and grow.</p>
                <form class="cta-form" onsubmit="return hL(event)">
                    <input type="email" id="le" placeholder="Enter your email" required class="cta-input">
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
                <a href="<?= APP_URL ?>/" class="logo" style="margin-bottom:10px"><div class="logo-ic" style="width:28px;height:28px;font-size:.7rem"><i class="fas fa-bolt"></i></div>TSA<span class="hl">Legacy</span></a>
                <p style="color:var(--mt);font-size:.78rem;line-height:1.6;margin:10px 0">Cloud-native business management platform built for Indian SMEs.</p>
                <p style="color:var(--mt);font-size:.72rem"><i class="fas fa-map-marker-alt" style="margin-right:4px"></i> India-based Startup</p>
            </div>
            <div><div class="ft-title">Product</div><div class="ft-links"><a href="#features">Features</a><a href="#pricing">Pricing</a><a href="<?= APP_URL ?>/index.php?page=demo_login">Live Demo</a><a href="<?= APP_URL ?>/index.php?page=signup">Sign Up</a></div></div>
            <div><div class="ft-title">Company</div><div class="ft-links"><a href="#about">About Us</a><a href="<?= APP_URL ?>/index.php?page=privacy">Privacy Policy</a><a href="<?= APP_URL ?>/index.php?page=terms">Terms of Service</a><a href="<?= APP_URL ?>/index.php?page=refund">Refund Policy</a></div></div>
            <div><div class="ft-title">Contact</div><div class="ft-links"><a href="mailto:hello@tsalegacy.shop"><i class="fas fa-envelope" style="margin-right:4px"></i>hello@tsalegacy.shop</a><span style="color:var(--mt);font-size:.78rem"><i class="fas fa-building" style="margin-right:4px"></i>TSA Legacy Ventures</span><span style="color:var(--mt);font-size:.78rem"><i class="fas fa-certificate" style="margin-right:4px"></i>MSME / Udyam Registered</span><span style="color:var(--mt);font-size:.78rem"><i class="fas fa-flag" style="margin-right:4px"></i>Made with ❤️ in India</span></div></div>
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
function hL(e){e.preventDefault();var em=document.getElementById('le').value;if(em){document.getElementById('lm').style.display='block';setTimeout(function(){window.location.href='<?= APP_URL ?>/index.php?page=signup&email='+encodeURIComponent(em)},1200)}return false}
window.addEventListener('scroll',function(){document.getElementById('mainNav').style.background=window.scrollY>50?'rgba(2,6,23,.95)':'rgba(2,6,23,.85)'});
</script>
</body>
</html>
