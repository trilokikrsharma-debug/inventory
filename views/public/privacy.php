<?php
/**
 * Privacy Policy — TSA Legacy Ventures
 * Last updated: March 2026
 */
require_once __DIR__ . '/_partials/brand.php';

$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES);
$assets = tsa_brand_assets();
$canonicalUrl = rtrim(APP_URL, '/') . '/privacy';
$faviconUrl = $assets['favicon'];
$socialImageUrl = $assets['og'];

$heroCards = [
    ['title' => 'Tenant isolation', 'text' => 'Business data stays scoped to each account with tenant-aware controls.'],
    ['title' => 'Operational safeguards', 'text' => 'Sessions, CSRF protection, rate limiting, and audit support are built into the platform.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php tsa_render_adsense_verification(); ?>
    <title>Privacy Policy — <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?></title>
    <meta name="description" content="Privacy Policy for <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>, an MSME-registered SaaS platform for Indian SMEs.">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="theme-color" content="#16385f">
    <meta property="og:title" content="Privacy Policy — <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta property="og:description" content="Privacy Policy for <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>, an MSME-registered SaaS platform for Indian SMEs.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Privacy Policy — <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta name="twitter:description" content="Privacy Policy for <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>, an MSME-registered SaaS platform for Indian SMEs.">
    <meta name="twitter:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($assets['brand_css'], ENT_QUOTES) ?>">
</head>
<body class="tsa-public">
<?php tsa_render_public_nav([
    'active_href' => APP_URL . '/privacy',
    'links' => [
        ['href' => APP_URL . '/', 'label' => 'Home'],
        ['href' => APP_URL . '/pricing', 'label' => 'Pricing'],
        ['href' => APP_URL . '/privacy', 'label' => 'Privacy'],
    ],
    'secondary_label' => 'Back to Home',
    'secondary_href' => APP_URL . '/',
]); ?>

<main class="tsa-page tsa-legal-shell" id="main-content">
    <div class="tsa-container">
        <?php tsa_render_page_hero([
            'eyebrow' => 'Legal & Trust',
            'title' => 'Privacy policy for <span class="tsa-serif">TSA Legacy</span>',
            'lead' => 'This page explains what information we collect, how we use it, and how the platform approaches tenant isolation, hosting and operational security.',
            'primary_href' => APP_URL . '/signup',
            'primary_label' => 'Start Free Trial',
            'secondary_href' => APP_URL . '/',
            'secondary_label' => 'Back to Home',
            'note' => 'Last updated: March 2026',
            'side_cards' => $heroCards,
        ]); ?>

        <article class="tsa-legal-card tsa-legal-prose">
            <h1>Privacy Policy</h1>
            <p class="tsa-legal-meta">Last updated: March 2026</p>

            <p><?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?> ("we", "us", "our") operates the TSA Legacy platform. This policy explains what information we collect, how we use it, and how we protect it when you use the service.</p>

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
        <li><strong>Email:</strong> triloki@tsalegacy.com</li>
        <li><strong>Business:</strong> <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?> (MSME / Udyam Registered)</li>
        <li><strong>Location:</strong> India</li>
    </ul>
        </article>
    </div>
</main>

<?php tsa_render_public_footer(['show_guides' => true]); ?>
<script nonce="<?= $nonce ?>">
<?= tsa_brand_script() ?>
</script>
</body>
</html>
