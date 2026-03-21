<?php
/**
 * Refund Policy — TSA Legacy Ventures
 * Last updated: March 2026
 */
$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Policy — TSA Legacy</title>
    <meta name="description" content="Refund and Cancellation Policy for TSA Legacy SaaS platform.">
    <link rel="canonical" href="https://tsalegacy.shop/index.php?page=refund">
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
        .highlight{background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:12px;padding:20px;margin:20px 0}
        .highlight p{margin-bottom:0}
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
    <h1>Refund & Cancellation Policy</h1>
    <p class="updated">Last updated: March 2026</p>

    <p>At TSA Legacy Ventures, we want you to be completely satisfied with our Service. This policy outlines our approach to refunds and cancellations.</p>

    <h2>1. Free Plan</h2>
    <p>The Free plan is available at no cost with no obligation. No refund applies as no payment is made.</p>

    <h2>2. Free Trial Period</h2>
    <p>All paid plans come with a <strong>14-day free trial</strong>. During the trial:</p>
    <ul>
        <li>No payment is required</li>
        <li>Full access to all plan features</li>
        <li>You can cancel anytime without any charge</li>
        <li>No automatic billing during the trial period</li>
    </ul>

    <div class="highlight">
        <p><strong>💡 Tip:</strong> We recommend trying the free trial before committing to a paid plan. This way you can evaluate all features risk-free.</p>
    </div>

    <h2>3. Subscription Cancellation</h2>
    <p>You may cancel your paid subscription at any time from your account settings.</p>
    <ul>
        <li>Your subscription will remain active until the end of the current billing period</li>
        <li>After the billing period ends, your account will be downgraded to the Free plan</li>
        <li>Your data will be retained and accessible on the Free plan (subject to Free plan limits)</li>
    </ul>

    <h2>4. Refund Eligibility</h2>
    <p><strong>Within 7 days of payment:</strong> If you are unsatisfied with the Service, you may request a full refund within 7 days of your payment date. The refund will be processed to the original payment method within 5-10 business days.</p>
    <p><strong>After 7 days:</strong> Refunds are generally not available after 7 days. However, we evaluate each request on a case-by-case basis. Contact us if you have special circumstances.</p>
    <p><strong>Annual Plans:</strong> For annual subscriptions, a pro-rata refund may be issued for the unused months if requested within 30 days of payment.</p>

    <h2>5. Non-Refundable Situations</h2>
    <p>Refunds will not be issued in these cases:</p>
    <ul>
        <li>Account termination due to Terms of Service violations</li>
        <li>Failure to use the Service during the subscription period</li>
        <li>Downgrade requests — the difference is not refundable</li>
        <li>Payments made more than 60 days ago</li>
    </ul>

    <h2>6. How to Request a Refund</h2>
    <p>To request a refund, please contact us with:</p>
    <ul>
        <li>Your registered email address</li>
        <li>Business name on your account</li>
        <li>Reason for the refund request</li>
        <li>Payment receipt or transaction ID (if available)</li>
    </ul>
    <p>Send your request to: <strong>hello@tsalegacy.shop</strong></p>
    <p>We will respond within 2 business days and process approved refunds within 5-10 business days.</p>

    <h2>7. Payment Processing</h2>
    <p>All payments are securely processed through <strong>Razorpay</strong>. Refunds are processed through the same payment method used for the original transaction.</p>

    <h2>8. Changes to This Policy</h2>
    <p>We reserve the right to modify this policy at any time. Changes will be communicated via email to registered users.</p>

    <h2>9. Contact Us</h2>
    <p>For any questions regarding refunds or cancellations:</p>
    <ul>
        <li><strong>Email:</strong> hello@tsalegacy.shop</li>
        <li><strong>Business:</strong> TSA Legacy Ventures (MSME / Udyam Registered)</li>
        <li><strong>Location:</strong> India</li>
    </ul>
</div>

<footer><p>© 2025–<?= date('Y') ?> TSA Legacy Ventures. All rights reserved.</p></footer>
</body>
</html>
