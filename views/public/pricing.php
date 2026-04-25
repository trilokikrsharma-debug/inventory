<?php
require_once __DIR__ . '/_partials/brand.php';

$plans = is_array($plans ?? null) ? $plans : [];
$assets = tsa_brand_assets();
$pricingPageUrl = rtrim(APP_URL, '/') . '/pricing';
$pricingCtaHref = APP_URL . '/signup';
$pricingCtaText = 'Get Started';
if (Session::isLoggedIn() && !Session::isSuperAdmin()) {
    $pricingCtaHref = APP_URL . '/index.php?page=saas_billing&action=subscribe';
    $pricingCtaText = 'Upgrade Now';
}

$heroCards = [
    ['title' => 'Accessible starting point', 'text' => 'Pricing is designed for new shops and growing SMEs that want operational software without a heavy entry cost.'],
    ['title' => 'Upgrade only when needed', 'text' => 'Plan limits scale with users and product volume so growth drives the next tier.'],
    ['title' => 'Review before subscribing', 'text' => 'Use the free trial or instant demo access before moving into a paid plan.'],
    ['title' => 'Operationally clear', 'text' => 'Each plan is framed around workspace capacity, daily workflows, and business control.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php tsa_render_adsense_verification(); ?>
    <title>Pricing for GST Billing & Inventory Software | <?= APP_NAME ?></title>
    <meta name="description" content="See pricing for TSA Legacy GST billing and inventory management software. Simple monthly plans for Indian small businesses, retail teams and growing SMEs.">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="theme-color" content="#16385f">
    <meta property="og:title" content="Pricing for GST Billing & Inventory Software | <?= Helper::escape(APP_NAME) ?>">
    <meta property="og:description" content="Simple monthly pricing for Indian SMEs using TSA Legacy for GST billing, inventory management and daily business operations.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($pricingPageUrl, ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($assets['og'], ENT_QUOTES) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape(APP_NAME) ?>">
    <meta property="og:site_name" content="<?= Helper::escape(APP_NAME) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Pricing for GST Billing & Inventory Software | <?= Helper::escape(APP_NAME) ?>">
    <meta name="twitter:description" content="Simple monthly pricing for Indian SMEs using TSA Legacy for GST billing, inventory management and daily business operations.">
    <meta name="twitter:image" content="<?= htmlspecialchars($assets['og'], ENT_QUOTES) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($assets['favicon'], ENT_QUOTES) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($pricingPageUrl, ENT_QUOTES) ?>">
    <script type="application/ld+json">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'OfferCatalog',
            'name' => APP_NAME . ' Pricing',
            'url' => $pricingPageUrl,
            'itemListElement' => array_values(array_map(static function (array $plan): array {
                $price = isset($plan['price_monthly']) ? (float)$plan['price_monthly'] : 0.0;
                return [
                    '@type' => 'Offer',
                    'name' => (string)($plan['name'] ?? 'Plan'),
                    'priceCurrency' => 'INR',
                    'price' => number_format($price, 2, '.', ''),
                    'url' => rtrim(APP_URL, '/') . '/pricing',
                ];
            }, $plans)),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
    <link rel="stylesheet" href="<?= htmlspecialchars($assets['brand_css'], ENT_QUOTES) ?>">
</head>
<body class="tsa-public">
<?php tsa_render_public_nav([
    'active_href' => APP_URL . '/pricing',
    'links' => [
        ['href' => APP_URL . '/', 'label' => 'Home'],
        ['href' => APP_URL . '/pricing', 'label' => 'Pricing'],
        ['href' => APP_URL . '/blog', 'label' => 'Guides'],
    ],
    'secondary_label' => 'Instant Demo Access',
    'secondary_href' => APP_URL . '/demo',
    'primary_label' => $pricingCtaText,
    'primary_href' => $pricingCtaHref,
]); ?>

<main class="tsa-page" id="main-content">
    <div class="tsa-container">
        <?php tsa_render_page_hero([
            'eyebrow' => 'Pricing',
            'title' => 'Simple monthly pricing for <span class="tsa-serif">serious business operations</span>.',
            'lead' => 'Choose a plan that fits your current team and catalog, then upgrade only when your business actually needs more capacity.',
            'primary_href' => $pricingCtaHref,
            'primary_label' => $pricingCtaText,
            'secondary_href' => APP_URL . '/demo',
            'secondary_label' => 'Instant Demo Access',
            'note' => 'Start with the right operating tier now and expand as your workflow grows.',
            'side_cards' => $heroCards,
        ]); ?>

        <section class="tsa-trust-strip">
            <div class="tsa-container-sm tsa-container-flush">
                <div class="tsa-trust-surface">
                    <div class="tsa-trust-copy">
                        <div>
                            <h2>Review the product before you subscribe</h2>
                            <p>Instant demo access signs you into a sample workspace configured to show broader operational capabilities before you commit to a plan.</p>
                        </div>
                        <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-secondary">Instant Demo Access</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="tsa-section">
            <?php if (!empty($plans)): ?>
                <div class="tsa-pricing-grid">
                    <?php foreach ($plans as $plan): ?>
                        <?php
                        $effectivePrice = SaaSBillingHelper::effectivePlanPrice($plan);
                        $price = (float)($plan['price'] ?? 0);
                        $offer = isset($plan['offer_price']) ? (float)$plan['offer_price'] : null;
                        $features = SaaSBillingHelper::extractPlanFeatures($plan, 8, 3);
                        $limits = SaaSBillingHelper::planLimitsSummary($plan);
                        $isFeatured = !empty($plan['is_featured']);
                        $billing = strtolower((string)($plan['billing_type'] ?? 'monthly'));
                        $days = (int)($plan['duration_days'] ?? 30);
                        $monthlyEquivalent = $days >= 365 ? max(1, round($effectivePrice / 12)) : $effectivePrice;
                        $planCaption = $isFeatured ? 'Best value for most teams' : (($plan['slug'] ?? '') === 'starter' ? 'Lowest monthly entry' : 'For larger operations');
                        ?>
                        <article class="tsa-pricing-card<?= $isFeatured ? ' is-featured' : '' ?>">
                            <?php if ($isFeatured): ?><div class="tsa-pricing-badge">Recommended</div><?php endif; ?>
                            <div class="tsa-plan-name"><?= e($plan['name'] ?? 'Plan') ?></div>
                            <div class="tsa-plan-price">
                                <?php if ($offer !== null && $offer > 0 && $offer < $price): ?>
                                    <small><s>Rs <?= number_format($price, 0) ?></s></small><br>
                                <?php endif; ?>
                                Rs <?= number_format($effectivePrice, 0) ?><small>/<?= e($billing) ?></small>
                            </div>
                            <p class="tsa-plan-desc"><?= e($planCaption) ?></p>
                            <p class="tsa-plan-limit">
                                <?= !empty($plan['description']) ? e($plan['description']) : 'Built for reliable daily business operations.' ?><br>
                                <span class="tsa-small">Valid <?= e((string)$days) ?> days • approx Rs <?= e(number_format($monthlyEquivalent, 0)) ?>/month value</span>
                            </p>

                            <div class="tsa-grid-2">
                                <div class="tsa-card tsa-metric">
                                    <strong><?= e(number_format($limits['users'])) ?></strong>
                                    <span>Users Included</span>
                                </div>
                                <div class="tsa-card tsa-metric">
                                    <strong><?= e(number_format($limits['products'])) ?></strong>
                                    <span>Products Included</span>
                                </div>
                            </div>

                            <ul class="tsa-plan-features">
                                <?php foreach ($features['enabled'] as $label): ?>
                                    <li><i class="fas fa-check"></i><span><?= e($label) ?></span></li>
                                <?php endforeach; ?>
                                <?php foreach ($features['disabled'] as $label): ?>
                                    <li><i class="fas fa-xmark"></i><span><?= e($label) ?></span></li>
                                <?php endforeach; ?>
                            </ul>

                            <a href="<?= $pricingCtaHref ?>" class="tsa-btn <?= $isFeatured ? 'tsa-btn-primary' : 'tsa-btn-secondary' ?> tsa-btn-block"><?= e($pricingCtaText) ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="tsa-plan-trust-note">
                    <p><i class="fas fa-lock tsa-inline-accent-icon"></i>Secure payment processing. No hidden setup fees. Upgrade or cancel anytime.</p>
                </div>
            <?php else: ?>
                <div class="tsa-legal-card tsa-card-center">
                    <h1>No active pricing plans are available right now.</h1>
                    <p>Please contact support or check back once plan configuration is restored.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="tsa-section tsa-section-no-top" id="faq">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Common Questions</div>
                <h2>Frequently asked questions about pricing</h2>
                <p>Quick answers to help you choose the right plan for your business.</p>
            </div>
            <div class="tsa-faq-grid">
                <article class="tsa-card tsa-faq-card">
                    <h3>Can I switch plans later?</h3>
                    <p>Yes. You can upgrade or downgrade your plan at any time. Changes take effect from your next billing cycle.</p>
                </article>
                <article class="tsa-card tsa-faq-card">
                    <h3>Is there a free trial?</h3>
                    <p>Yes. Every new workspace starts with a free trial so you can explore the full system before subscribing.</p>
                </article>
                <article class="tsa-card tsa-faq-card">
                    <h3>What payment methods are accepted?</h3>
                    <p>We accept UPI, debit cards, credit cards, and net banking through Razorpay's secure payment processing.</p>
                </article>
                <article class="tsa-card tsa-faq-card">
                    <h3>Can I cancel anytime?</h3>
                    <p>Yes. There are no lock-in contracts. You can cancel your subscription anytime from your workspace settings.</p>
                </article>
            </div>
        </section>

    </div>
</main>

<?php tsa_render_public_footer(['show_guides' => true]); ?>
<script>
<?= tsa_brand_script() ?>
</script>
</body>
</html>
