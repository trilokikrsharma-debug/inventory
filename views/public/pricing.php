<?php
$plans = is_array($plans ?? null) ? $plans : [];
$pricingPageUrl = rtrim(APP_URL, '/') . '/pricing';
$iconUrl = rtrim(APP_URL, '/') . '/assets/icon.svg';
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.svg';

$pricingCtaHref = APP_URL . '/signup';
$pricingCtaText = 'Get Started';
if (Session::isLoggedIn() && !Session::isSuperAdmin()) {
    $pricingCtaHref = APP_URL . '/index.php?page=saas_billing&action=subscribe';
    $pricingCtaText = 'Upgrade Now';
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing for GST Billing & Inventory Software | <?= APP_NAME ?></title>
    <meta name="description" content="See pricing for TSA Legacy GST billing and inventory management software. Simple monthly plans for Indian small businesses, retail teams and growing SMEs.">
    <meta property="og:title" content="Pricing for GST Billing & Inventory Software | <?= Helper::escape(APP_NAME) ?>">
    <meta property="og:description" content="Simple monthly pricing for Indian SMEs using TSA Legacy for GST billing, inventory management and daily business operations.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($pricingPageUrl, ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape(APP_NAME) ?>">
    <meta property="og:site_name" content="<?= Helper::escape(APP_NAME) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Pricing for GST Billing & Inventory Software | <?= Helper::escape(APP_NAME) ?>">
    <meta name="twitter:description" content="Simple monthly pricing for Indian SMEs using TSA Legacy for GST billing, inventory management and daily business operations.">
    <meta name="twitter:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES) ?>">
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .pricing-wrapper {
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(82, 168, 255, 0.18), transparent 28%),
                radial-gradient(circle at 80% 20%, rgba(27, 200, 138, 0.12), transparent 20%),
                linear-gradient(145deg, #07111f 0%, #102038 42%, #172338 100%);
            padding: 3rem 1rem;
        }
        .pricing-shell {
            max-width: 1180px;
            margin: 0 auto;
        }
        .pricing-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .pricing-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #d8e7ff;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            margin-bottom: 1rem;
        }
        .pricing-header h1 { color: #fff; font-weight: 900; font-size: clamp(2.4rem, 5vw, 4rem); margin-bottom: 0.75rem; line-height: 1.05; }
        .pricing-header p { color: #c7d6f3; font-size: 1.05rem; max-width: 720px; margin: 0 auto; }
        .launch-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin: 1.75rem auto 2.75rem;
            max-width: 980px;
        }
        .launch-item {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 1rem;
            padding: 1rem 1.1rem;
            color: #dbe7fb;
            text-align: left;
        }
        .launch-item strong {
            display: block;
            color: #fff;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }
        .section-note {
            color: #8b91b6;
            font-size: 0.86rem;
        }
        .demo-callout {
            max-width: 980px;
            margin: 0 auto 2rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 1.2rem;
            padding: 1rem 1.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .demo-callout strong {
            display: block;
            color: #fff;
            margin-bottom: 0.2rem;
        }
        .demo-callout p {
            margin: 0;
            color: #c7d6f3;
            font-size: 0.9rem;
        }

        .pricing-card {
            background: rgba(10, 18, 34, 0.86);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1.5rem;
            padding: 2rem;
            height: 100%;
            transition: all 0.3s;
            backdrop-filter: blur(18px);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .pricing-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 24px 60px rgba(0,0,0,0.28);
            border-color: rgba(82,168,255,0.35);
        }
        .pricing-card.featured {
            border-color: rgba(82,168,255,0.6);
            background: linear-gradient(180deg, rgba(46, 93, 187, 0.34) 0%, rgba(11, 20, 38, 0.96) 100%);
            transform: translateY(-12px) scale(1.015);
            box-shadow: 0 26px 70px rgba(40, 92, 201, 0.22);
        }
        .pricing-card.featured::before {
            content: 'MOST POPULAR';
            position: absolute;
            top: 18px;
            right: -38px;
            background: linear-gradient(135deg, #58a9ff, #2563eb);
            color: #fff;
            padding: 5px 42px;
            font-size: 0.7rem;
            font-weight: 700;
            transform: rotate(45deg);
            letter-spacing: 1px;
        }
        .plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.38rem 0.75rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.08);
            color: #d7e6ff;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 1rem;
        }
        .plan-name { color: #b7d7ff; font-size: 0.92rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; }
        .plan-price { color: #fff; font-size: 3.1rem; font-weight: 900; margin: 0.65rem 0 0.25rem; line-height: 1; }
        .plan-price small { font-size: 1rem; font-weight: 500; color: #9db4dc; }
        .plan-caption { color: #7ee0b2; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.9rem; }
        .plan-desc { color: #bfd0ee; font-size: 0.9rem; margin-bottom: 1rem; min-height: 3rem; }
        .limit-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .limit-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1rem;
            padding: 0.85rem 0.9rem;
        }
        .limit-box strong {
            display: block;
            color: #fff;
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }
        .limit-box span {
            color: #9fb0cf;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .feature-list { list-style: none; padding: 0; margin: 0 0 1.6rem; flex: 1; }
        .feature-list li {
            padding: 0.45rem 0;
            color: #d2d6f1;
            font-size: 0.88rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .feature-list li i {
            width: 20px;
            margin-right: 8px;
        }
        .feature-list li .fa-check { color: #1cc88a; }
        .feature-list li .fa-times { color: #e74a3b; opacity: 0.6; }

        .btn-plan {
            width: 100%;
            padding: 0.9rem;
            border-radius: 0.85rem;
            font-weight: 700;
            transition: all 0.3s;
        }
        .btn-plan-primary {
            background: linear-gradient(135deg, #58a9ff, #2563eb);
            border: none;
            color: #fff;
        }
        .btn-plan-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37,99,235,0.4);
            color: #fff;
        }
        .btn-plan-outline {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
        }
        .btn-plan-outline:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border-color: rgba(78,115,223,0.5);
        }
        .back-link { color: #a7add2; text-decoration: none; transition: color 0.2s; }
        .back-link:hover { color: #4e73df; }
        @media (max-width: 991px) {
            .pricing-card.featured {
                transform: none;
            }
        }
        @media (max-width: 768px) {
            .launch-strip {
                grid-template-columns: 1fr;
            }
            .demo-callout {
                flex-direction: column;
                align-items: stretch;
            }
            .limit-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="pricing-wrapper">
    <div class="container pricing-shell">
        <div class="text-center mb-3">
            <a href="<?= APP_URL ?>/login" class="back-link">
                <i class="fas fa-arrow-left me-2"></i>Back to Login
            </a>
        </div>

        <div class="pricing-header">
            <div class="pricing-kicker"><i class="fas fa-rocket"></i>Launch Pricing For Early Customers</div>
            <h1>Simple monthly pricing.<br>Cheap enough to start now.</h1>
            <p>Pick a low-risk plan, start billing immediately, and upgrade only when your team or catalog actually grows.</p>
            <div class="section-note mt-3">
                <i class="fas fa-database me-1"></i>Plans on this page are loaded dynamically from <code>saas_plans</code>.
            </div>
        </div>

        <div class="launch-strip">
            <div class="launch-item">
                <strong>Low entry pricing</strong>
                Built for new shops and growing SMEs that want fast adoption, not heavy upfront software cost.
            </div>
            <div class="launch-item">
                <strong>Upgrade only when needed</strong>
                Limits are based on users and products, so the jump feels tied to actual growth.
            </div>
            <div class="launch-item">
                <strong>Try before committing</strong>
                Start with the demo or trial flow first and move to a paid plan only when operations stabilize.
            </div>
        </div>

        <div class="demo-callout">
            <div>
                <strong>Live demo runs on the enterprise showcase setup</strong>
                <p>Try bulk import, AI insights, API access, backup, and HR-ready workflows before you commit.</p>
            </div>
            <a href="<?= APP_URL ?>/demo" class="btn btn-plan-primary btn-plan" style="max-width:220px;">
                <i class="fas fa-play-circle me-1"></i>Open Demo
            </a>
        </div>

        <div class="row g-4 justify-content-center">
            <?php if (!empty($plans)): ?>
                <?php foreach ($plans as $plan): ?>
                    <?php
                        $effectivePrice = SaaSBillingHelper::effectivePlanPrice($plan);
                        $price = (float)($plan['price'] ?? 0);
                        $offer = isset($plan['offer_price']) ? (float)$plan['offer_price'] : null;
                        $features = SaaSBillingHelper::extractPlanFeatures($plan, 8, 3);
                        $limits = SaaSBillingHelper::planLimitsSummary($plan);
                        $isFeatured = !empty($plan['is_featured']);
                        $billing = strtoupper((string)($plan['billing_type'] ?? 'monthly'));
                        $days = (int)($plan['duration_days'] ?? 30);
                        $monthlyEquivalent = $days >= 365 ? max(1, round($effectivePrice / 12)) : $effectivePrice;
                        $planCaption = $isFeatured ? 'Best value for most teams' : (($plan['slug'] ?? '') === 'starter' ? 'Lowest monthly entry' : 'For larger operations');
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="pricing-card <?= $isFeatured ? 'featured' : '' ?>">
                            <div class="plan-badge">
                                <i class="fas <?= $isFeatured ? 'fa-star' : 'fa-layer-group' ?>"></i>
                                <?= $isFeatured ? 'Recommended' : 'Launch Plan' ?>
                            </div>
                            <div class="plan-name"><?= e($plan['name'] ?? 'Plan') ?></div>
                            <div class="plan-price">
                                <?php if ($offer !== null && $offer > 0 && $offer < $price): ?>
                                    <small><s>Rs <?= number_format($price, 2) ?></s></small><br>
                                <?php endif; ?>
                                Rs <?= number_format($effectivePrice, 2) ?>
                                <small>/<?= strtolower($billing) ?></small>
                            </div>
                            <div class="plan-caption"><?= e($planCaption) ?></div>
                            <p class="plan-desc">
                                <?= !empty($plan['description']) ? e($plan['description']) : 'Built for reliable daily business operations.' ?>
                                <br><small class="text-uppercase">Valid <?= $days ?> days • approx Rs <?= number_format($monthlyEquivalent, 0) ?>/month value</small>
                            </p>

                            <div class="limit-row">
                                <div class="limit-box">
                                    <strong><?= e(number_format($limits['users'])) ?></strong>
                                    <span>Users Included</span>
                                </div>
                                <div class="limit-box">
                                    <strong><?= e(number_format($limits['products'])) ?></strong>
                                    <span>Products Included</span>
                                </div>
                            </div>

                            <ul class="feature-list">
                                <?php foreach ($features['enabled'] as $label): ?>
                                    <li><i class="fas fa-check"></i><?= e($label) ?></li>
                                <?php endforeach; ?>
                                <?php foreach ($features['disabled'] as $label): ?>
                                    <li><i class="fas fa-times"></i><?= e($label) ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <a href="<?= $pricingCtaHref ?>" class="btn btn-plan <?= $isFeatured ? 'btn-plan-primary' : 'btn-plan-outline' ?>">
                                <?= e($pricingCtaText) ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 col-lg-8">
                    <div class="alert alert-warning text-center border-0 shadow-sm">
                        No active pricing plans are available right now. Please contact support.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5">
            <p style="color:#9fa4c7;font-size:0.9rem;">
                <i class="fas fa-shield-alt me-1"></i>All plans include secure session handling, CSRF protection, and billing audit trails.
            </p>
            <a href="<?= APP_URL ?>/demo" class="btn btn-outline-info btn-sm mt-2">
                <i class="fas fa-play-circle me-1"></i>Try Free Demo
            </a>
        </div>
    </div>
</div>
</body>
</html>
