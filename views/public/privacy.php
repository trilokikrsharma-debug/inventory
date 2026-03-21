<?php
/**
 * Privacy Policy — TSA Legacy Ventures
 * Last updated: March 2026
 */
$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — TSA Legacy</title>
    <meta name="description" content="Privacy Policy for TSA Legacy, an MSME-registered SaaS platform for Indian SMEs.">
    <link rel="canonical" href="https://tsalegacy.shop/index.php?page=privacy">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root{--p:#6366f1;--pl:#818cf8;--ac:#06b6d4;--d:#020617;--d2:#0f172a;--card:rgba(255,255,255,.04);--brd:rgba(255,255,255,.07);--tx:#e2e8f0;--mt:#94a3b8;--w:#fff}
        *{margin:0;padding:0;box-sizing:border-box}html{scroll-behavior:smooth}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--d);color:var(--tx);-webkit-font-smoothing:antialiased}
        a{color:var(--pl);text-decoration:none}a:hover{text-decoration:underline}
        nav{position:fixed;top:0;left:0;right:0;z-index:100;backdrop-filter:blur(20px);background:rgba(2,6,23,.9);border-bottom:1px solid var(--brd);height:64px;display:flex;align-items:center;padding:0 24px}
        .nav-i{max-width:1200px;margin:0 auto;width:100%;display:flex;align-items:center;justify-content:space-between}
        .logo{display:flex;align-items:center;gap:10px;font-weight:900;font-size:1.15rem;color:var(--w);text-decoration:none}
        .logo-ic{width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,var(--p),var(--ac));display:flex;align-items:center;justify-content:center;font-size:.8rem;color:#fff}
        .logo .hl{color:var(--pl)}
        .btn-g{padding:8px 18px;border-radius:8px;font-size:.8rem;font-weight:600;color:var(--tx);border:1px solid var(--brd);text-decoration:none;display:inline-flex;align-items:center;gap:6px}
        .content{max-width:800px;margin:0 auto;padding:100px 24px 60px}
        .content h1{font-size:2rem;font-weight:800;color:var(--w);margin-bottom:8px}
        .updated{font-size:.8rem;color:var(--mt);margin-bottom:32px}
        .content h2{font-size:1.2rem;font-weight:700;color:var(--w);margin:32px 0 12px;padding-top:16px;border-top:1px solid var(--brd)}
        .content p,.content li{font-size:.9rem;line-height:1.75;color:var(--tx);margin-bottom:12px}
        .content ul{padding-left:20px;margin-bottom:16px}
        .content li{margin-bottom:6px}
        .content strong{color:var(--w)}
        footer{border-top:1px solid var(--brd);padding:24px;text-align:center}
        footer p{color:var(--mt);font-size:.72rem}
    </style>
</head>
<body>
<nav><div class="nav-i">
    <a href="<?= APP_URL ?>/" class="logo"><div class="logo-ic"><i class="fas fa-bolt"></i></div>TSA<span class="hl">Legacy</span></a>
    <a href="<?= APP_URL ?>/" class="btn-g"><i class="fas fa-arrow-left"></i> Back to Home</a>
</div></nav>

<div class="content">
    <h1>Privacy Policy</h1>
    <p class="updated">Last updated: March 2026</p>

    <p>TSA Legacy Ventures ("we", "us", "our") operates the TSA Legacy platform (https://tsalegacy.shop). This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our Service.</p>

    <h2>1. Information We Collect</h2>
    <p><strong>Account Information:</strong> When you register, we collect your name, email address, phone number, business name, and business address.</p>
    <p><strong>Business Data:</strong> Information you enter into the platform including products, invoices, sales, purchases, customer details, supplier details, and financial records.</p>
    <p><strong>Usage Data:</strong> We automatically collect information about how you interact with our Service, including IP address, browser type, pages visited, and timestamps.</p>
    <p><strong>Payment Data:</strong> Payment processing is handled by Razorpay. We do not store your credit/debit card details. Please refer to Razorpay's privacy policy for their data handling practices.</p>

    <h2>2. How We Use Your Information</h2>
    <ul>
        <li>To provide, operate, and maintain the TSA Legacy platform</li>
        <li>To process your transactions and manage your subscriptions</li>
        <li>To send you service-related communications (account verification, billing, security alerts)</li>
        <li>To improve our Service and develop new features</li>
        <li>To comply with legal obligations and enforce our terms</li>
    </ul>

    <h2>3. Data Isolation & Multi-Tenancy</h2>
    <p>TSA Legacy operates as a multi-tenant SaaS platform. Each business account (tenant) has <strong>complete data isolation</strong>. Your business data is accessible only to users within your organization. No other tenant can access your data.</p>

    <h2>4. Data Storage & Security</h2>
    <p>Your data is stored on <strong>Google Cloud Platform</strong> servers located in India (asia-south1 region). We implement industry-standard security measures including:</p>
    <ul>
        <li>HTTPS/TLS encryption for all data in transit</li>
        <li>CSRF protection, rate limiting, and security headers</li>
        <li>Role-Based Access Control (RBAC) and optional Two-Factor Authentication (2FA)</li>
        <li>Regular automated backups</li>
        <li>Audit logging for all critical operations</li>
    </ul>

    <h2>5. Data Sharing</h2>
    <p>We <strong>do not sell</strong> your personal data to third parties. We may share data only with:</p>
    <ul>
        <li><strong>Razorpay:</strong> For processing payments</li>
        <li><strong>Google Cloud:</strong> For hosting infrastructure</li>
        <li><strong>Legal authorities:</strong> When required by law or to protect our rights</li>
    </ul>

    <h2>6. Your Rights</h2>
    <p>You have the right to:</p>
    <ul>
        <li>Access and download your business data at any time</li>
        <li>Correct or update your account information</li>
        <li>Request deletion of your account and associated data</li>
        <li>Opt out of non-essential communications</li>
    </ul>

    <h2>7. Data Retention</h2>
    <p>We retain your data for as long as your account is active. Upon account deletion, your data will be permanently removed within 30 days, unless retention is required by law (e.g., tax records under Indian law).</p>

    <h2>8. Cookies</h2>
    <p>We use essential session cookies to maintain your login state and ensure security. These cookies are strictly necessary for the functioning of the platform and cannot be disabled.</p>

    <h2>9. Children's Privacy</h2>
    <p>TSA Legacy is not intended for individuals under the age of 18. We do not knowingly collect personal information from children.</p>

    <h2>10. Changes to This Policy</h2>
    <p>We may update this Privacy Policy from time to time. We will notify registered users via email for significant changes.</p>

    <h2>11. Contact Us</h2>
    <p>If you have questions about this Privacy Policy:</p>
    <ul>
        <li><strong>Email:</strong> hello@tsalegacy.shop</li>
        <li><strong>Business:</strong> TSA Legacy Ventures (MSME / Udyam Registered)</li>
        <li><strong>Location:</strong> India</li>
    </ul>
</div>

<footer><p>© 2025–<?= date('Y') ?> TSA Legacy Ventures. All rights reserved.</p></footer>
</body>
</html>
