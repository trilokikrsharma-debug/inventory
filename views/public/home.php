<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KarsoBill – Smart Inventory & Billing SaaS for Indian Businesses</title>
    <meta name="description" content="KarsoBill is a multi-tenant SaaS platform for inventory management, GST billing, purchase/sales tracking, and business analytics. Built for Indian SMEs.">
    <meta name="keywords" content="inventory management, GST billing, SaaS, Indian SME, billing software, stock management">
    <meta property="og:title" content="KarsoBill – Smart Billing & Inventory SaaS">
    <meta property="og:description" content="Manage inventory, generate GST invoices, track sales & purchases — all in one platform.">
    <meta property="og:type" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #818cf8;
            --accent: #06b6d4;
            --accent2: #10b981;
            --dark: #0a0a1a;
            --dark2: #0f0f2d;
            --card: #13132b;
            --card2: #1a1a3e;
            --border: rgba(255,255,255,0.08);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --white: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ── NAV ── */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            backdrop-filter: blur(20px);
            background: rgba(10, 10, 26, 0.85);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 68px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .nav-logo {
            display: flex; align-items: center; gap: 0.6rem;
            font-size: 1.3rem; font-weight: 800; color: var(--white);
            text-decoration: none;
        }
        .nav-logo span { color: var(--primary-light); }
        .logo-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: white;
        }
        .nav-links { display: flex; align-items: center; gap: 2rem; }
        .nav-links a {
            color: var(--muted); text-decoration: none; font-size: 0.9rem;
            font-weight: 500; transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--white); }
        .nav-cta { display: flex; align-items: center; gap: 0.75rem; }
        .btn-ghost {
            padding: 0.5rem 1.2rem; border-radius: 8px; font-size: 0.85rem;
            font-weight: 600; color: var(--text); text-decoration: none;
            border: 1px solid var(--border); transition: all 0.2s;
        }
        .btn-ghost:hover { background: var(--card); color: var(--white); }
        .btn-primary-sm {
            padding: 0.5rem 1.4rem; border-radius: 8px; font-size: 0.85rem;
            font-weight: 600; color: white; text-decoration: none;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            transition: all 0.2s; box-shadow: 0 4px 15px rgba(79,70,229,0.35);
        }
        .btn-primary-sm:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(79,70,229,0.5); }

        /* ── HERO ── */
        .hero {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 100px 2rem 60px;
            position: relative; overflow: hidden;
        }
        .hero-bg {
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(79,70,229,0.25) 0%, transparent 70%),
                        radial-gradient(ellipse 40% 40% at 80% 60%, rgba(6,182,212,0.12) 0%, transparent 60%);
        }
        .hero-grid {
            position: absolute; inset: 0; opacity: 0.03;
            background-image: linear-gradient(var(--white) 1px, transparent 1px),
                              linear-gradient(90deg, var(--white) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .hero-content {
            max-width: 900px; text-align: center; position: relative; z-index: 1;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.4rem 1rem; border-radius: 50px;
            border: 1px solid rgba(79,70,229,0.4);
            background: rgba(79,70,229,0.1);
            font-size: 0.8rem; font-weight: 600; color: var(--primary-light);
            margin-bottom: 1.5rem;
        }
        .hero-badge .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--accent2); animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.4); }
        }
        .hero h1 {
            font-size: clamp(2.5rem, 7vw, 4.5rem);
            font-weight: 900; line-height: 1.1; letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
        }
        .hero h1 .gradient {
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero p {
            font-size: 1.15rem; color: var(--muted); max-width: 600px;
            margin: 0 auto 2.5rem; line-height: 1.7;
        }
        .hero-actions {
            display: flex; align-items: center; justify-content: center;
            gap: 1rem; flex-wrap: wrap;
        }
        .btn-hero-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.9rem 2rem; border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white; text-decoration: none; font-weight: 700; font-size: 1rem;
            box-shadow: 0 8px 30px rgba(79,70,229,0.4); transition: all 0.3s;
        }
        .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(79,70,229,0.6); }
        .btn-hero-demo {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.9rem 2rem; border-radius: 12px;
            border: 1px solid var(--border); color: var(--text);
            text-decoration: none; font-weight: 600; font-size: 1rem;
            background: rgba(255,255,255,0.04); transition: all 0.3s;
        }
        .btn-hero-demo:hover { background: rgba(255,255,255,0.08); color: var(--white); }
        .hero-stats {
            display: flex; justify-content: center; gap: 3rem; margin-top: 4rem;
            flex-wrap: wrap;
        }
        .stat { text-align: center; }
        .stat-num {
            font-size: 1.8rem; font-weight: 900; color: var(--white);
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-label { font-size: 0.8rem; color: var(--muted); font-weight: 500; }

        /* ── FEATURES ── */
        .section { padding: 100px 2rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section-label {
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-size: 0.75rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: var(--primary-light);
            margin-bottom: 1rem;
        }
        .section-title {
            font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 900;
            line-height: 1.2; letter-spacing: -0.02em; margin-bottom: 1rem;
        }
        .section-sub {
            font-size: 1.05rem; color: var(--muted); max-width: 550px;
            line-height: 1.7;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem; margin-top: 4rem;
        }
        .feature-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px; padding: 2rem;
            transition: all 0.3s;
            position: relative; overflow: hidden;
        }
        .feature-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            opacity: 0; transition: opacity 0.3s;
        }
        .feature-card:hover { transform: translateY(-4px); border-color: rgba(79,70,229,0.3); }
        .feature-card:hover::before { opacity: 1; }
        .feature-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: 1.2rem;
        }
        .feature-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.6rem; }
        .feature-card p { font-size: 0.9rem; color: var(--muted); line-height: 1.6; }

        /* ── HOW IT WORKS ── */
        .steps { max-width: 1100px; margin: 0 auto; }
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 2rem; margin-top: 4rem; position: relative;
        }
        .step {
            text-align: center; padding: 2rem 1.5rem;
            background: var(--card); border: 1px solid var(--border);
            border-radius: 20px; position: relative;
        }
        .step-num {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.9rem; margin: 0 auto 1.2rem;
        }
        .step h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; }
        .step p { font-size: 0.85rem; color: var(--muted); line-height: 1.6; }

        /* ── PRICING ── */
        .pricing-bg {
            background: radial-gradient(ellipse 70% 50% at 50% 50%, rgba(79,70,229,0.1) 0%, transparent 70%);
        }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem; margin-top: 4rem; max-width: 950px; margin-left: auto; margin-right: auto;
        }
        .pricing-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 24px; padding: 2.5rem;
            position: relative; transition: all 0.3s;
        }
        .pricing-card.popular {
            border-color: var(--primary);
            background: linear-gradient(180deg, rgba(79,70,229,0.15) 0%, var(--card) 100%);
            transform: scale(1.02);
        }
        .popular-badge {
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white; padding: 0.3rem 1.2rem; border-radius: 50px;
            font-size: 0.75rem; font-weight: 700; white-space: nowrap;
        }
        .plan-name { font-size: 0.85rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; }
        .plan-price {
            font-size: 3rem; font-weight: 900; color: var(--white);
            line-height: 1; margin: 1rem 0 0.25rem;
        }
        .plan-price sup { font-size: 1.5rem; vertical-align: top; margin-top: 0.5rem; }
        .plan-price sub { font-size: 1rem; font-weight: 400; color: var(--muted); }
        .plan-desc { font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem; }
        .plan-features { list-style: none; margin-bottom: 2rem; }
        .plan-features li {
            display: flex; align-items: center; gap: 0.6rem;
            font-size: 0.875rem; padding: 0.4rem 0; color: var(--text);
        }
        .plan-features li .check { color: var(--accent2); font-size: 0.8rem; }
        .btn-plan {
            display: block; text-align: center; padding: 0.8rem;
            border-radius: 10px; font-weight: 700; text-decoration: none;
            font-size: 0.9rem; transition: all 0.2s;
        }
        .btn-plan-outline {
            border: 1px solid var(--border); color: var(--text);
            background: rgba(255,255,255,0.04);
        }
        .btn-plan-outline:hover { background: rgba(255,255,255,0.08); color: var(--white); }
        .btn-plan-fill {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white; box-shadow: 0 4px 15px rgba(79,70,229,0.4);
        }
        .btn-plan-fill:hover { box-shadow: 0 6px 25px rgba(79,70,229,0.6); transform: translateY(-1px); }

        /* ── TESTIMONIALS ── */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem; margin-top: 4rem;
        }
        .testimonial-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 20px; padding: 2rem; position: relative;
        }
        .quote-icon {
            font-size: 2rem; color: var(--primary-light); opacity: 0.3;
            margin-bottom: 1rem; line-height: 1;
        }
        .testimonial-text {
            font-size: 0.9rem; color: var(--text); line-height: 1.7;
            margin-bottom: 1.5rem; font-style: italic;
        }
        .testimonial-author {
            display: flex; align-items: center; gap: 0.75rem;
        }
        .author-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem;
        }
        .author-name { font-weight: 600; font-size: 0.9rem; }
        .author-role { font-size: 0.75rem; color: var(--muted); }
        .stars { color: #f59e0b; font-size: 0.75rem; margin-bottom: 0.25rem; }

        /* ── CTA BANNER ── */
        .cta-banner {
            background: linear-gradient(135deg, rgba(79,70,229,0.2) 0%, rgba(6,182,212,0.15) 100%);
            border: 1px solid rgba(79,70,229,0.3);
            border-radius: 28px; padding: 4rem 3rem; text-align: center;
            position: relative; overflow: hidden;
            max-width: 900px; margin: 0 auto;
        }
        .cta-banner h2 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 900; margin-bottom: 1rem; }
        .cta-banner p { color: var(--muted); font-size: 1.05rem; margin-bottom: 2rem; }
        .cta-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

        /* ── FOOTER ── */
        footer {
            border-top: 1px solid var(--border);
            padding: 3rem 2rem 2rem;
        }
        .footer-inner {
            max-width: 1200px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 1rem;
        }
        .footer-logo { font-weight: 700; font-size: 1rem; }
        .footer-links { display: flex; gap: 1.5rem; }
        .footer-links a { color: var(--muted); text-decoration: none; font-size: 0.85rem; }
        .footer-links a:hover { color: var(--white); }
        .footer-copy { color: var(--muted); font-size: 0.8rem; }

        /* ── MOBILE RESPONSIVE ── */
        .hamburger { display: none; }
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hamburger { display: block; cursor: pointer; background: none; border: none; color: var(--white); font-size: 1.2rem; }
            .nav-mobile { display: none; }
            .nav-mobile.open {
                display: flex; flex-direction: column; gap: 0; position: fixed;
                top: 68px; left: 0; right: 0; background: rgba(10,10,26,0.98);
                backdrop-filter: blur(20px); border-bottom: 1px solid var(--border);
                padding: 1rem; z-index: 999;
            }
            .nav-mobile a {
                color: var(--text); text-decoration: none; padding: 0.8rem 0;
                font-weight: 500; border-bottom: 1px solid var(--border);
            }
            .hero h1 { font-size: 2.2rem; }
            .hero-stats { gap: 1.5rem; }
            .pricing-card.popular { transform: none; }
        }
    </style>
</head>
<body>

<!-- ══ NAVIGATION ══ -->
<nav>
    <a href="<?= APP_URL ?>/" class="nav-logo">
        <div class="logo-icon"><i class="fas fa-bolt"></i></div>
        Karso<span>Bill</span>
    </a>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#pricing">Pricing</a>
        <a href="<?= APP_URL ?>/index.php?page=demo_login">Demo</a>
    </div>
    <div class="nav-cta">
        <a href="<?= APP_URL ?>/index.php?page=login" class="btn-ghost">Sign In</a>
        <a href="<?= APP_URL ?>/index.php?page=signup" class="btn-primary-sm">
            Start Free Trial
        </a>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menu">
        <i class="fas fa-bars"></i>
    </button>
</nav>

<!-- Mobile Nav -->
<div class="nav-mobile" id="navMobile">
    <a href="#features" onclick="closeMobileNav()">Features</a>
    <a href="#how-it-works" onclick="closeMobileNav()">How It Works</a>
    <a href="#pricing" onclick="closeMobileNav()">Pricing</a>
    <a href="<?= APP_URL ?>/index.php?page=demo_login">Try Demo</a>
    <a href="<?= APP_URL ?>/index.php?page=login">Sign In</a>
    <a href="<?= APP_URL ?>/index.php?page=signup" style="color:var(--primary-light);font-weight:700;">Start Free →</a>
</div>

<!-- ══ HERO ══ -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-grid"></div>
    <div class="hero-content">
        <div class="hero-badge">
            <div class="dot"></div>
            Now Live — Built for Indian SMEs
        </div>
        <h1>
            Smart Billing &<br>
            <span class="gradient">Inventory SaaS</span><br>
            for Modern Businesses
        </h1>
        <p>
            Manage stock, generate GST invoices, track purchases & sales, 
            and monitor business health — all in one powerful platform. 
            Zero setup. Ready in minutes.
        </p>
        <div class="hero-actions">
            <a href="<?= APP_URL ?>/index.php?page=signup" class="btn-hero-primary">
                <i class="fas fa-rocket"></i>
                Start for Free — 14 Day Trial
            </a>
            <a href="<?= APP_URL ?>/index.php?page=demo_login" class="btn-hero-demo">
                <i class="fas fa-play-circle"></i>
                Live Demo
            </a>
        </div>
        <div class="hero-stats">
            <div class="stat">
                <div class="stat-num">14-Day</div>
                <div class="stat-label">Free Trial</div>
            </div>
            <div class="stat">
                <div class="stat-num">GST</div>
                <div class="stat-label">Ready Invoices</div>
            </div>
            <div class="stat">
                <div class="stat-num">Multi</div>
                <div class="stat-label">Tenant SaaS</div>
            </div>
            <div class="stat">
                <div class="stat-num">99.9%</div>
                <div class="stat-label">Uptime SLA</div>
            </div>
        </div>
    </div>
</section>

<!-- ══ FEATURES ══ -->
<section class="section" id="features">
    <div class="container">
        <div class="section-label"><i class="fas fa-star"></i> Core Features</div>
        <h2 class="section-title">Everything you need to run<br>your business efficiently</h2>
        <p class="section-sub">Purpose-built for Indian SMEs — from a single shop to a multi-branch enterprise.</p>

        <div class="features-grid">

            <div class="feature-card">
                <div class="feature-icon" style="background:rgba(79,70,229,0.15);color:#818cf8;">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h3>GST Billing & Invoicing</h3>
                <p>Generate professional GST-compliant invoices instantly. Customizable templates, automatic tax calculations, and digital delivery.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:rgba(6,182,212,0.15);color:#22d3ee;">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <h3>Inventory Management</h3>
                <p>Real-time stock tracking across categories, brands, and units. Low-stock alerts, barcode support, and product performance analytics.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:rgba(16,185,129,0.15);color:#34d399;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Sales & Purchase Tracking</h3>
                <p>End-to-end order management from purchase to sale. Track supplier & customer ledgers, payment history, and outstanding balances.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:rgba(245,158,11,0.15);color:#fbbf24;">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Customer & Supplier CRM</h3>
                <p>Maintain complete customer and supplier profiles with transaction history, credit limits, advance payments, and detailed ledgers.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:rgba(239,68,68,0.15);color:#f87171;">
                    <i class="fas fa-building"></i>
                </div>
                <h3>Multi-Tenant Architecture</h3>
                <p>True SaaS multi-tenancy — each business gets isolated data, custom branding, and independent settings. Perfect for resellers.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:rgba(168,85,247,0.15);color:#c084fc;">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h3>Enterprise Security</h3>
                <p>CSRF protection, RBAC, 2FA, session management, audit logs, and security headers — built for business-critical operations.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:rgba(79,70,229,0.15);color:#818cf8;">
                    <i class="fas fa-receipt"></i>
                </div>
                <h3>Quotations & Estimates</h3>
                <p>Create and send professional quotations. Convert to invoices with one click. Track acceptance rates and follow-ups.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:rgba(6,182,212,0.15);color:#22d3ee;">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3>Business Analytics</h3>
                <p>Revenue reports, purchase trends, stock valuation, profit & loss — real-time insights to make smarter decisions.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:rgba(16,185,129,0.15);color:#34d399;">
                    <i class="fas fa-tag"></i>
                </div>
                <h3>Subscription & Plans</h3>
                <p>Flexible SaaS billing with Razorpay integration. Promo codes, referral programs, and automatic plan management.</p>
            </div>

        </div>
    </div>
</section>

<!-- ══ HOW IT WORKS ══ -->
<section class="section" id="how-it-works" style="background:var(--dark2);">
    <div class="container">
        <div class="section-label"><i class="fas fa-map"></i> Getting Started</div>
        <h2 class="section-title">Up and running in minutes,<br>not weeks</h2>
        <p class="section-sub">No IT team needed. No complicated setup. Just sign up and start billing.</p>

        <div class="steps steps-grid">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Create Your Account</h3>
                <p>Sign up free in 30 seconds. Enter your shop name, email, and you're in. No credit card required.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Add Your Products</h3>
                <p>Import your inventory via CSV or add products manually with categories, brands, pricing, and tax rates.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Start Billing</h3>
                <p>Create GST invoices, process sales, manage purchases, and track payments from day one.</p>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <h3>Grow Your Business</h3>
                <p>Use insights, analytics, and reports to understand trends and make data-driven decisions.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ PRICING ══ -->
<section class="section pricing-bg" id="pricing">
    <div class="container">
        <div style="text-align:center; margin-bottom:0;">
            <div class="section-label" style="justify-content:center;"><i class="fas fa-tag"></i> Simple Pricing</div>
            <h2 class="section-title">Transparent pricing.<br>No surprises.</h2>
            <p class="section-sub" style="margin:0 auto;">Start free, upgrade when you're ready. All plans include GST billing and inventory management.</p>
        </div>

        <div class="pricing-grid">
            <!-- Starter -->
            <div class="pricing-card">
                <div class="plan-name">Starter</div>
                <div class="plan-price"><sup>₹</sup>0<sub>/mo</sub></div>
                <p class="plan-desc">Perfect for new businesses getting started</p>
                <ul class="plan-features">
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Up to 3 users</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> 500 products</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> GST Invoices</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Basic reports</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> 14-day trial</li>
                </ul>
                <a href="<?= APP_URL ?>/index.php?page=signup" class="btn-plan btn-plan-outline">Get Started Free</a>
            </div>

            <!-- Growth -->
            <div class="pricing-card popular">
                <div class="popular-badge">⚡ Most Popular</div>
                <div class="plan-name">Growth</div>
                <div class="plan-price"><sup>₹</sup>999<sub>/mo</sub></div>
                <p class="plan-desc">Ideal for growing businesses with multiple users</p>
                <ul class="plan-features">
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Up to 10 users</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Unlimited products</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Advanced reports</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Quotations</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Priority support</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Sale returns</li>
                </ul>
                <a href="<?= APP_URL ?>/index.php?page=signup" class="btn-plan btn-plan-fill">Start Free Trial</a>
            </div>

            <!-- Enterprise -->
            <div class="pricing-card">
                <div class="plan-name">Enterprise</div>
                <div class="plan-price"><sup>₹</sup>2499<sub>/mo</sub></div>
                <p class="plan-desc">For large businesses needing full control</p>
                <ul class="plan-features">
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Unlimited users</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Multi-branch</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Custom roles & RBAC</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> 2FA security</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> Dedicated support</li>
                    <li><span class="check"><i class="fas fa-check-circle"></i></span> API access</li>
                </ul>
                <a href="<?= APP_URL ?>/index.php?page=signup" class="btn-plan btn-plan-outline">Contact Sales</a>
            </div>
        </div>
    </div>
</section>

<!-- ══ TESTIMONIALS ══ -->
<section class="section" style="background:var(--dark2);">
    <div class="container">
        <div class="section-label"><i class="fas fa-heart"></i> What Businesses Say</div>
        <h2 class="section-title">Trusted by businesses<br>across India</h2>

        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="quote-icon">"</div>
                <div class="stars">★★★★★</div>
                <p class="testimonial-text">KarsoBill transformed how we manage our retail shop. Invoice generation that used to take 20 minutes now takes 30 seconds. Game changer!</p>
                <div class="testimonial-author">
                    <div class="author-avatar">RK</div>
                    <div>
                        <div class="author-name">Ramesh Kumar</div>
                        <div class="author-role">Retail Shop Owner, Jaipur</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote-icon">"</div>
                <div class="stars">★★★★★</div>
                <p class="testimonial-text">The multi-tenant feature is brilliant. We use it for 3 different businesses under one account. The GST billing is spot-on for India.</p>
                <div class="testimonial-author">
                    <div class="author-avatar">SP</div>
                    <div>
                        <div class="author-name">Sunita Patel</div>
                        <div class="author-role">Wholesale Distributor, Mumbai</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote-icon">"</div>
                <div class="stars">★★★★★</div>
                <p class="testimonial-text">Finally a SaaS that understands Indian business needs. Stock management, purchase tracking, supplier ledgers — everything just works.</p>
                <div class="testimonial-author">
                    <div class="author-avatar">AV</div>
                    <div>
                        <div class="author-name">Amit Verma</div>
                        <div class="author-role">Hardware Store, Delhi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ CTA ══ -->
<section class="section">
    <div class="container">
        <div class="cta-banner">
            <div class="hero-badge" style="display:inline-flex;margin-bottom:1.5rem;">
                <div class="dot"></div>
                No credit card required
            </div>
            <h2>Ready to transform<br>your business?</h2>
            <p>Join hundreds of Indian businesses using KarsoBill to manage inventory, billing, and growth.</p>
            <div class="cta-actions">
                <a href="<?= APP_URL ?>/index.php?page=signup" class="btn-hero-primary">
                    <i class="fas fa-rocket"></i>
                    Start Free for 14 Days
                </a>
                <a href="<?= APP_URL ?>/index.php?page=demo_login" class="btn-hero-demo">
                    <i class="fas fa-play"></i>
                    Try Live Demo
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ══ FOOTER ══ -->
<footer>
    <div class="footer-inner">
        <div>
            <div class="nav-logo" style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.4rem;">
                <div class="logo-icon" style="width:28px;height:28px;font-size:0.8rem;"><i class="fas fa-bolt"></i></div>
                <span style="font-weight:800;font-size:1rem;">Karso<span style="color:var(--primary-light);">Bill</span></span>
            </div>
            <div class="footer-copy">© 2026 TSA Legacy Ventures. All rights reserved.</div>
        </div>
        <div class="footer-links">
            <a href="<?= APP_URL ?>/index.php?page=pricing">Pricing</a>
            <a href="<?= APP_URL ?>/index.php?page=signup">Sign Up</a>
            <a href="<?= APP_URL ?>/index.php?page=login">Login</a>
            <a href="<?= APP_URL ?>/index.php?page=demo_login">Demo</a>
        </div>
    </div>
</footer>

<script>
// Mobile nav toggle
const hamburger = document.getElementById('hamburger');
const navMobile = document.getElementById('navMobile');
hamburger.addEventListener('click', () => navMobile.classList.toggle('open'));
function closeMobileNav() { navMobile.classList.remove('open'); }

// Scroll reveal animation
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.feature-card, .step, .pricing-card, .testimonial-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    observer.observe(el);
});
</script>
</body>
</html>
