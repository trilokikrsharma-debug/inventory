<?php
/**
 * Terms of Service — TSA Legacy Ventures
 * Last updated: March 2026
 */
$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service — TSA Legacy</title>
    <meta name="description" content="Terms of Service for TSA Legacy SaaS platform.">
    <link rel="canonical" href="https://tsalegacy.shop/index.php?page=terms">
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
    <h1>Terms of Service</h1>
    <p class="updated">Last updated: March 2026</p>

    <p>These Terms of Service ("Terms") govern your use of the TSA Legacy platform ("Service") operated by TSA Legacy Ventures ("Company", "we", "us"), an MSME-registered startup in India. By accessing or using our Service, you agree to be bound by these Terms.</p>

    <h2>1. Acceptance of Terms</h2>
    <p>By creating an account or using the Service, you agree to these Terms and our <a href="<?= APP_URL ?>/index.php?page=privacy">Privacy Policy</a>. If you do not agree, please do not use the Service.</p>

    <h2>2. Description of Service</h2>
    <p>TSA Legacy is a cloud-based SaaS platform providing business management tools for Indian SMEs, including:</p>
    <ul>
        <li>GST-compliant billing and invoicing</li>
        <li>Inventory and stock management</li>
        <li>Customer and supplier relationship management</li>
        <li>Financial reports and analytics</li>
        <li>Multi-user access with role-based permissions</li>
    </ul>

    <h2>3. Account Registration</h2>
    <p>You must provide accurate and complete information during registration. You are responsible for maintaining the security of your account credentials. You must be at least 18 years old to create an account.</p>
    <p>Each account represents a separate business entity (tenant). You are solely responsible for all activities that occur under your account.</p>

    <h2>4. Subscription Plans & Billing</h2>
    <p><strong>Free Plan:</strong> Available indefinitely with limited features and usage quotas.</p>
    <p><strong>Paid Plans:</strong> Billed monthly or annually via Razorpay. Prices are listed in Indian Rupees (₹) and are inclusive of applicable GST.</p>
    <p><strong>Upgrades & Downgrades:</strong> You may change your plan at any time. Changes take effect at the start of the next billing cycle.</p>
    <p><strong>Failed Payments:</strong> If a payment fails, we may restrict access to paid features until payment is resolved.</p>

    <h2>5. Acceptable Use</h2>
    <p>You agree not to:</p>
    <ul>
        <li>Use the Service for any unlawful purpose</li>
        <li>Attempt to gain unauthorized access to other users' data</li>
        <li>Upload malicious code, viruses, or harmful content</li>
        <li>Share your account credentials with unauthorized parties</li>
        <li>Use the Service to send spam or unsolicited communications</li>
        <li>Reverse engineer, decompile, or attempt to extract source code</li>
    </ul>

    <h2>6. Data Ownership</h2>
    <p>You retain full ownership of all business data you enter into TSA Legacy. We do not claim any intellectual property rights over your data.</p>
    <p>You grant us a limited license to process and store your data solely for the purpose of providing the Service.</p>

    <h2>7. Data Isolation</h2>
    <p>TSA Legacy operates on a multi-tenant architecture with strict per-tenant data isolation. Your business data is completely separated from other tenants and is only accessible to authorized users within your organization.</p>

    <h2>8. Service Availability</h2>
    <p>We strive to maintain 99.9% uptime but do not guarantee uninterrupted service. Scheduled maintenance windows will be communicated in advance when possible.</p>
    <p>We are not liable for service interruptions caused by factors outside our control, including third-party service outages, natural disasters, or internet connectivity issues.</p>

    <h2>9. Limitation of Liability</h2>
    <p>To the maximum extent permitted by law, TSA Legacy Ventures shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, or business opportunities.</p>
    <p>Our total liability shall not exceed the amount you paid for the Service in the 12 months preceding the event giving rise to the claim.</p>

    <h2>10. Termination</h2>
    <p>You may cancel your account at any time. Upon cancellation:</p>
    <ul>
        <li>Your data will be retained for 30 days for recovery purposes</li>
        <li>After 30 days, all data will be permanently deleted</li>
        <li>No refund will be issued for the current billing period</li>
    </ul>
    <p>We may suspend or terminate your account if you violate these Terms, with or without notice.</p>

    <h2>11. Modifications to Terms</h2>
    <p>We reserve the right to modify these Terms at any time. Significant changes will be notified via email to registered users. Continued use of the Service after changes constitutes acceptance of the updated Terms.</p>

    <h2>12. Governing Law</h2>
    <p>These Terms are governed by the laws of India. Any disputes arising from these Terms shall be subject to the exclusive jurisdiction of the courts in India.</p>

    <h2>13. Contact Us</h2>
    <p>For questions about these Terms:</p>
    <ul>
        <li><strong>Email:</strong> hello@tsalegacy.shop</li>
        <li><strong>Business:</strong> TSA Legacy Ventures (MSME / Udyam Registered)</li>
        <li><strong>Location:</strong> India</li>
    </ul>
</div>

<footer><p>© 2025–<?= date('Y') ?> TSA Legacy Ventures. All rights reserved.</p></footer>
</body>
</html>
