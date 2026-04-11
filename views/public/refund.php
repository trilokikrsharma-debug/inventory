<?php
/**
 * Refund Policy — TSA Legacy Ventures
 * Last updated: March 2026
 */
require_once __DIR__ . '/_partials/brand.php';

$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES);
$assets = tsa_brand_assets();
$canonicalUrl = rtrim(APP_URL, '/') . '/refund';
$faviconUrl = $assets['favicon'];
$socialImageUrl = $assets['og'];

$heroCards = [
    ['title' => 'Try before paying', 'text' => 'Paid plans begin with a 14-day free trial before subscription commitment.'],
    ['title' => 'Clear refund windows', 'text' => 'Refund handling follows the stated policy windows and review process below.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Policy — TSA Legacy</title>
    <meta name="description" content="Refund and Cancellation Policy for TSA Legacy SaaS platform.">
    <meta property="og:title" content="Refund Policy — TSA Legacy">
    <meta property="og:description" content="Refund and Cancellation Policy for TSA Legacy SaaS platform.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <meta property="og:image:alt" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Refund Policy — TSA Legacy">
    <meta name="twitter:description" content="Refund and Cancellation Policy for TSA Legacy SaaS platform.">
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
    'active_href' => APP_URL . '/refund',
    'links' => [
        ['href' => APP_URL . '/', 'label' => 'Home'],
        ['href' => APP_URL . '/pricing', 'label' => 'Pricing'],
        ['href' => APP_URL . '/refund', 'label' => 'Refund'],
    ],
    'secondary_label' => 'Back to Home',
    'secondary_href' => APP_URL . '/',
]); ?>

<main class="tsa-page tsa-legal-shell">
    <div class="tsa-container">
        <?php tsa_render_page_hero([
            'eyebrow' => 'Legal & Trust',
            'title' => 'Refund and cancellation policy for <span class="tsa-serif">TSA Legacy</span>',
            'lead' => 'This page explains the free-trial structure, cancellation model and the refund windows currently stated for paid subscriptions.',
            'primary_href' => APP_URL . '/pricing',
            'primary_label' => 'See Pricing',
            'secondary_href' => APP_URL . '/',
            'secondary_label' => 'Back to Home',
            'note' => 'Last updated: March 2026',
            'side_cards' => $heroCards,
        ]); ?>

        <article class="tsa-legal-card tsa-legal-prose">
            <h1>Refund &amp; Cancellation Policy</h1>
            <p class="tsa-legal-meta">Last updated: March 2026</p>

            <p>This policy explains how TSA Legacy handles free trials, subscription cancellation, and refund requests for paid plans.</p>

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

    <div class="tsa-callout">
        <p><strong>Tip:</strong> We recommend trying the free trial before committing to a paid plan so you can evaluate the workflows before subscription billing begins.</p>
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
    <p>Send your request to: <strong>triloki@tsalegacy.com</strong></p>
    <p>We will respond within 2 business days and process approved refunds within 5-10 business days.</p>

    <h2>7. Payment Processing</h2>
    <p>All payments are securely processed through <strong>Razorpay</strong>. Refunds are processed through the same payment method used for the original transaction.</p>

    <h2>8. Changes to This Policy</h2>
    <p>We reserve the right to modify this policy at any time. Changes will be communicated via email to registered users.</p>

    <h2>9. Contact Us</h2>
    <p>For any questions regarding refunds or cancellations:</p>
    <ul>
        <li><strong>Email:</strong> triloki@tsalegacy.com</li>
        <li><strong>Business:</strong> TSA Legacy Ventures (MSME / Udyam Registered)</li>
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
