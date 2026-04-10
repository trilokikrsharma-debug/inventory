<?php
/**
 * Pricing page — redesigned to align with homepage design system.
 * Removes Bootstrap dependency. Uses shared public.css.
 */
$homeUrl = rtrim(APP_URL, '/') . '/';
$faviconUrl = rtrim(APP_URL, '/') . '/assets/favicon.svg';
$logoUrl = rtrim(APP_URL, '/') . '/assets/logo-lockup.svg';
$socialImageUrl = rtrim(APP_URL, '/') . '/assets/og-default.png';
$_nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? $cspNonce ?? '', ENT_QUOTES);
$plans = is_array($plans ?? null) ? $plans : [];

// Context-aware CTA and back link
$pricingCtaHref = APP_URL . '/signup';
$pricingCtaText = 'Get Started';
$backLink = APP_URL . '/';
$backText = 'Back to Home';
$backIcon = 'fa-arrow-left';

if (class_exists('Session') && Session::isLoggedIn()) {
    if (!Session::isSuperAdmin()) {
        $pricingCtaHref = APP_URL . '/index.php?page=saas_billing&action=subscribe';
        $pricingCtaText = 'Upgrade Now';
    }
    $backLink = Session::isSuperAdmin() ? APP_URL . '/platform/dashboard' : APP_URL . '/dashboard';
    $backText = 'Back to Dashboard';
}
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Monthly Pricing for Indian SMEs | TSA Legacy</title>
    <meta name="description" content="Affordable monthly pricing for GST billing and inventory management software. Start small, upgrade when your team and catalog grow. No lock-in contracts.">
    <meta property="og:title" content="Simple Monthly Pricing for Indian SMEs | TSA Legacy">
    <meta property="og:description" content="Low-risk monthly plans for GST billing and inventory management. Try before you commit.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($homeUrl . 'pricing', ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <meta property="og:image:alt" content="TSA Legacy Pricing">
    <meta property="og:site_name" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Simple Monthly Pricing for Indian SMEs | TSA Legacy">
    <meta name="twitter:description" content="Low-risk monthly plans for GST billing and inventory management. Try before you commit.">
    <meta name="twitter:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($homeUrl . 'pricing', ENT_QUOTES) ?>">
    <script type="application/ld+json" nonce="<?= $_nonce ?>">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'OfferCatalog',
            'name' => 'TSA Legacy Pricing Plans',
            'description' => 'Monthly SaaS pricing plans for GST billing and inventory management software for Indian SMEs.',
            'url' => rtrim(APP_URL, '/') . '/pricing',
            'numberOfItems' => count($plans),
            'itemListElement' => array_map(static function (array $plan) use ($homeUrl): array {
                return [
                    '@type' => 'Offer',
                    'name' => (string)($plan['name'] ?? 'Plan'),
                    'price' => (string)SaaSBillingHelper::effectivePlanPrice($plan),
                    'priceCurrency' => 'INR',
                    'url' => rtrim(APP_URL, '/') . '/pricing',
                    'description' => (string)($plan['description'] ?? ''),
                ];
            }, $plans),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script nonce="<?= $_nonce ?>">document.documentElement.classList.remove('no-js');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/public.css">
</head>
<body>

<?php include __DIR__ . '/_partials/nav.php'; ?>

<!-- PRICING HERO -->
<section class="pricing-hero">
    <div class="hero-bg"></div>
    <div class="mx-sm" style="position:relative;z-index:1">
        <a href="<?= htmlspecialchars($backLink, ENT_QUOTES) ?>" class="back-link" style="display:inline-flex;align-items:center;margin-bottom:16px">
            <i class="fas <?= $backIcon ?>" style="margin-right:6px"></i><?= htmlspecialchars($backText, ENT_QUOTES) ?>
        </a>
        <div class="badge"><span class="dot"></span><i class="fas fa-rocket"></i> Launch Pricing For Early Customers</div>
        <h1 style="font-size:clamp(2rem,5vw,3.2rem);font-weight:900;color:var(--w);line-height:1.08;margin-bottom:14px">Simple monthly pricing.<br><span class="gt">Cheap enough to start now.</span></h1>
        <p style="font-size:1.02rem;color:var(--mt);max-width:660px;margin:0 auto 24px;line-height:1.72">Pick a low-risk plan, start billing immediately, and upgrade only when your team or catalog actually grows.</p>
    </div>
</section>

<!-- LAUNCH STRIP -->
<section class="sec" style="padding-top:0;padding-bottom:36px">
    <div class="mx-sm">
        <div class="launch-strip rv">
            <div class="launch-item">
                <strong><i class="fas fa-coins" style="margin-right:6px;color:var(--ac)"></i>Low entry pricing</strong>
                <p>Built for new shops and growing SMEs that want fast adoption, not heavy upfront software cost.</p>
            </div>
            <div class="launch-item">
                <strong><i class="fas fa-chart-line" style="margin-right:6px;color:var(--ac)"></i>Upgrade only when needed</strong>
                <p>Limits are based on users and products, so the jump feels tied to actual growth.</p>
            </div>
            <div class="launch-item">
                <strong><i class="fas fa-play-circle" style="margin-right:6px;color:var(--ac)"></i>Try before committing</strong>
                <p>Start with the demo or trial flow first and move to a paid plan only when operations stabilize.</p>
            </div>
        </div>
    </div>
</section>

<!-- DEMO CALLOUT -->
<section class="sec" style="padding-top:0;padding-bottom:36px">
    <div class="mx-sm">
        <div class="demo-callout rv">
            <div>
                <strong>Live demo runs on the enterprise showcase setup</strong>
                <p>Try bulk import, AI insights, API access, backup, and HR-ready workflows before you commit.</p>
            </div>
            <a href="<?= APP_URL ?>/demo" class="btn-p" style="min-width:180px;white-space:nowrap">
                <i class="fas fa-play-circle"></i>Open Demo
            </a>
        </div>
    </div>
</section>

<!-- PRICING CARDS -->
<section class="sec sec-alt" id="plans" style="padding-top:48px">
    <div class="mx-sm">
        <div class="sec-hd rv"><div class="sec-tag"><i class="fas fa-tags"></i> Plans</div><h2 class="sec-t">Choose the right plan for your business</h2><p class="sec-s">Plans are updated in real time. Start small and upgrade as your team and catalog grow.</p></div>
        <?php if (!empty($plans)): ?>
        <div class="grid-3">
            <?php foreach ($plans as $plan): ?>
                <?php
                    $effectivePrice = SaaSBillingHelper::effectivePlanPrice($plan);
                    $price = (float)($plan['price'] ?? 0);
                    $offer = isset($plan['offer_price']) ? (float)$plan['offer_price'] : null;
                    $features = SaaSBillingHelper::extractPlanFeatures($plan, 8, 3);
                    $limits = SaaSBillingHelper::planLimitsSummary($plan);
                    $isFeatured = !empty($plan['is_featured']);
                    $billing = strtolower(trim((string)($plan['billing_type'] ?? 'monthly')));
                    $days = (int)($plan['duration_days'] ?? 30);
                    $monthlyEquivalent = $days >= 365 ? max(1, round($effectivePrice / 12)) : $effectivePrice;
                ?>
                <div class="p-card <?= $isFeatured ? 'pop' : '' ?> rv">
                    <?php if ($isFeatured): ?><div class="pop-badge">⚡ MOST POPULAR</div><?php endif; ?>
                    <div class="p-name" <?= $isFeatured ? 'style="color:var(--pl)"' : '' ?>><?= htmlspecialchars($plan['name'] ?? 'Plan', ENT_QUOTES) ?></div>
                    <div class="p-price">
                        <?php if ($offer !== null && $offer > 0 && $offer < $price): ?>
                            <span style="font-size:.9rem;text-decoration:line-through;color:var(--mt)">₹<?= number_format($price) ?></span><br>
                        <?php endif; ?>
                        ₹<?= number_format($effectivePrice) ?><sub>/<?= htmlspecialchars($billing, ENT_QUOTES) ?></sub>
                    </div>
                    <p class="p-desc">
                        <?= htmlspecialchars(!empty($plan['description']) ? $plan['description'] : 'Built for reliable daily business operations.', ENT_QUOTES) ?>
                        <br><small style="color:var(--mt);text-transform:uppercase;font-size:.68rem;letter-spacing:.06em">Valid <?= $days ?> days · approx ₹<?= number_format($monthlyEquivalent, 0) ?>/month value</small>
                    </p>
                    <div class="limit-row">
                        <div class="limit-box"><strong><?= number_format($limits['users']) ?></strong><span>Users Included</span></div>
                        <div class="limit-box"><strong><?= number_format($limits['products']) ?></strong><span>Products Included</span></div>
                    </div>
                    <ul class="p-feat">
                        <?php foreach ($features['enabled'] as $label): ?>
                        <li><span class="ck"><i class="fas fa-check-circle"></i></span><?= htmlspecialchars($label, ENT_QUOTES) ?></li>
                        <?php endforeach; ?>
                        <?php foreach ($features['disabled'] as $label): ?>
                        <li><span class="cx"><i class="fas fa-times-circle"></i></span><span style="opacity:.5"><?= htmlspecialchars($label, ENT_QUOTES) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= $pricingCtaHref ?>" class="btn-plan <?= $isFeatured ? 'btn-p' : 'btn-g' ?>" style="width:100%;justify-content:center"><?= htmlspecialchars($pricingCtaText, ENT_QUOTES) ?></a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="mx-xs" style="text-align:center;padding:40px 0">
            <div class="card" style="border-color:rgba(245,158,11,.3)">
                <p style="color:var(--mt)"><i class="fas fa-info-circle" style="margin-right:6px;color:#f59e0b"></i>No active pricing plans are available right now. Please contact support.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- TRUST NOTE -->
<section class="sec" style="padding-top:36px;padding-bottom:36px">
    <div class="mx-xs" style="text-align:center">
        <p style="color:var(--mt);font-size:.9rem;line-height:1.7;margin-bottom:14px">
            <i class="fas fa-shield-alt" style="margin-right:4px;color:var(--ac)"></i>All plans include secure session handling, CSRF protection, and billing audit trails.
        </p>
        <a href="<?= APP_URL ?>/demo" class="btn-g" style="min-height:40px;font-size:.85rem"><i class="fas fa-play-circle"></i>Try Free Demo</a>
    </div>
</section>

<?php include __DIR__ . '/_partials/footer.php'; ?>

<script nonce="<?= $_nonce ?>">
document.getElementById('hamburger').addEventListener('click',function(){document.getElementById('mobMenu').classList.toggle('open')});
function clM(){document.getElementById('mobMenu').classList.remove('open')}
var revEls=document.querySelectorAll('.rv');
if('IntersectionObserver' in window){
var ob=new IntersectionObserver(function(e){e.forEach(function(el){if(el.isIntersecting){el.target.classList.add('vis');ob.unobserve(el.target)}})},{threshold:.08,rootMargin:'0px 0px -20px 0px'});
revEls.forEach(function(el){ob.observe(el)});
}
setTimeout(function(){revEls.forEach(function(el){el.classList.add('vis')})},2500);
window.addEventListener('scroll',function(){document.getElementById('mainNav').style.background=window.scrollY>50?'rgba(6,16,27,.96)':'rgba(6,16,27,.9)'});
</script>
</body>
</html>
