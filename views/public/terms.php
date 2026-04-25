<?php
/**
 * Terms of Service — TSA Legacy Ventures
 * Last updated: March 2026
 */
require_once __DIR__ . '/_partials/brand.php';

$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES);
$assets = tsa_brand_assets();
$canonicalUrl = rtrim(APP_URL, '/') . '/terms';
$faviconUrl = $assets['favicon'];
$socialImageUrl = $assets['og'];

$heroCards = [
    ['title' => 'Self-serve SaaS', 'text' => 'Plans, upgrades, and operational access are designed for Indian SME workflows.'],
    ['title' => 'Controlled access', 'text' => 'Role-based permissions and tenant separation define how the service is used.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php tsa_render_adsense_verification(); ?>
    <title>Terms of Service — <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?></title>
    <meta name="description" content="Terms of Service for <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?> SaaS platform.">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="theme-color" content="#16385f">
    <meta property="og:title" content="Terms of Service — <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta property="og:description" content="Terms of Service for <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?> SaaS platform.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Terms of Service — <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta name="twitter:description" content="Terms of Service for <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?> SaaS platform.">
    <meta name="twitter:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
    <link rel="stylesheet" href="<?= htmlspecialchars($assets['brand_css'], ENT_QUOTES) ?>">
</head>
<body class="tsa-public">
<?php tsa_render_public_nav([
    'active_href' => APP_URL . '/terms',
    'links' => [
        ['href' => APP_URL . '/', 'label' => 'Home'],
        ['href' => APP_URL . '/pricing', 'label' => 'Pricing'],
        ['href' => APP_URL . '/terms', 'label' => 'Terms'],
    ],
    'secondary_label' => 'Back to Home',
    'secondary_href' => APP_URL . '/',
]); ?>

<main class="tsa-page tsa-legal-shell" id="main-content">
    <div class="tsa-container">
        <?php tsa_render_page_hero([
            'eyebrow' => 'Legal & Trust',
            'title' => 'Terms of service for <span class="tsa-serif">TSA Legacy</span>',
            'lead' => 'These terms describe the relationship between ' . Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) . ' and the businesses using the platform, including account responsibilities, billing and acceptable use.',
            'primary_href' => APP_URL . '/signup',
            'primary_label' => 'Start Free Trial',
            'secondary_href' => APP_URL . '/',
            'secondary_label' => 'Back to Home',
            'note' => 'Last updated: March 2026',
            'side_cards' => $heroCards,
        ]); ?>

        <article class="tsa-legal-card tsa-legal-prose">
            <h1>Terms of Service</h1>
            <p class="tsa-legal-meta">Last updated: March 2026</p>

            <p>These Terms of Service ("Terms") govern your use of the TSA Legacy platform ("Service") operated by <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?> ("Company", "we", "us"). By accessing or using the service, you agree to these Terms.</p>

    <h2>1. Acceptance of Terms</h2>
    <p>By creating an account or using the Service, you agree to these Terms and our <a href="<?= APP_URL ?>/privacy">Privacy Policy</a>. If you do not agree, please do not use the Service.</p>

    <h2>2. Description of Service</h2>
    <p>TSA Legacy is a cloud-based, self-serve SaaS product providing business management tools for Indian SMEs, including:</p>
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
    <p>We work to keep the Service available and reliable for day-to-day business use, but we do not guarantee uninterrupted access. Scheduled maintenance windows will be communicated in advance when reasonably possible.</p>
    <p>We are not liable for service interruptions caused by factors outside our control, including third-party service outages, natural disasters, or internet connectivity issues.</p>

    <h2>9. Limitation of Liability</h2>
    <p>To the maximum extent permitted by law, <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?> shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, or business opportunities.</p>
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
