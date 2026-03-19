<?php
$plans = is_array($plans ?? null) ? $plans : [];

$pricingCtaHref = APP_URL . '/index.php?page=signup';
$pricingCtaText = 'Get Started';
if (Session::isLoggedIn() && !Session::isSuperAdmin()) {
    $pricingCtaHref = APP_URL . '/index.php?page=saas_billing&action=subscribe';
    $pricingCtaText = 'Upgrade Now';
}

$featureLabels = [
    'inventory' => 'Inventory Management',
    'invoicing' => 'GST Invoicing',
    'api' => 'API Access',
    'api_access' => 'API Access',
    'crm' => 'CRM',
    'hr' => 'HR Tools',
    'multi_user' => 'Multi User',
    'quotations' => 'Quotations',
    'purchase_orders' => 'Purchase Orders',
    'advanced_reports' => 'Advanced Reports',
    'backup_restore' => 'Backup & Restore',
    'backup' => 'Backup & Restore',
    'webhooks' => 'Webhooks',
    'bulk_import' => 'Bulk Import',
    'ai_insights' => 'AI Insights',
    'custom_fields' => 'Custom Fields',
    'audit_trail' => 'Audit Trail',
    'export_pdf' => 'PDF Export',
];

$formatFeatureLabel = static function (string $key) use ($featureLabels): string {
    $normalized = strtolower(trim($key));
    $normalized = str_replace([' ', '-'], '_', $normalized);
    $normalized = preg_replace('/[^a-z0-9_]/', '', $normalized) ?: '';
    if ($normalized === '') {
        return 'Feature';
    }
    if (isset($featureLabels[$normalized])) {
        return $featureLabels[$normalized];
    }
    return ucwords(str_replace('_', ' ', $normalized));
};

$extractFeatures = static function (array $plan) use ($formatFeatureLabel): array {
    $raw = $plan['features'] ?? null;
    $decoded = null;
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
    } elseif (is_array($raw)) {
        $decoded = $raw;
    }

    $enabled = [];
    $disabled = [];

    if (is_array($decoded)) {
        $isAssoc = array_keys($decoded) !== range(0, count($decoded) - 1);
        if ($isAssoc) {
            foreach ($decoded as $k => $v) {
                $label = $formatFeatureLabel((string)$k);
                if ((bool)$v) {
                    $enabled[] = $label;
                } else {
                    $disabled[] = $label;
                }
            }
        } else {
            foreach ($decoded as $k) {
                $enabled[] = $formatFeatureLabel((string)$k);
            }
        }
    }

    if (empty($enabled)) {
        $enabled = ['Inventory Management', 'GST Invoicing', 'Reports'];
    }

    $enabled = array_values(array_unique($enabled));
    $disabled = array_values(array_unique($disabled));

    return [
        'enabled' => array_slice($enabled, 0, 8),
        'disabled' => array_slice($disabled, 0, 3),
    ];
};
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing | <?= APP_NAME ?></title>
    <meta name="description" content="Simple, transparent pricing for <?= APP_NAME ?>. Start free, scale as you grow.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .pricing-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 40%, #24243e 100%);
            padding: 3rem 1rem;
        }
        .pricing-header { text-align: center; margin-bottom: 2.5rem; }
        .pricing-header h1 { color: #fff; font-weight: 800; font-size: 2.5rem; margin-bottom: 0.5rem; }
        .pricing-header p { color: #c7cae1; font-size: 1rem; }

        .pricing-card {
            background: rgba(30, 30, 60, 0.9);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1.2rem;
            padding: 2rem;
            height: 100%;
            transition: all 0.3s;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }
        .pricing-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border-color: rgba(78,115,223,0.35);
        }
        .pricing-card.featured {
            border-color: rgba(78,115,223,0.55);
            background: rgba(40, 40, 80, 0.95);
        }
        .pricing-card.featured::before {
            content: 'POPULAR';
            position: absolute;
            top: 18px;
            right: -32px;
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: #fff;
            padding: 4px 40px;
            font-size: 0.7rem;
            font-weight: 700;
            transform: rotate(45deg);
            letter-spacing: 1px;
        }
        .plan-name { color: #b7b9cc; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; }
        .plan-price { color: #fff; font-size: 3rem; font-weight: 800; margin: 0.5rem 0; }
        .plan-price small { font-size: 1rem; font-weight: 400; color: #9fa4c7; }
        .plan-desc { color: #aeb3d6; font-size: 0.85rem; margin-bottom: 1rem; min-height: 2.6rem; }

        .feature-list { list-style: none; padding: 0; margin: 0 0 2rem; }
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
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-plan-primary {
            background: linear-gradient(135deg, #4e73df, #224abe);
            border: none;
            color: #fff;
        }
        .btn-plan-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(78,115,223,0.4);
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
        .db-note { color: #8b91b6; font-size: 0.86rem; }
    </style>
</head>
<body>
<div class="pricing-wrapper">
    <div class="container">
        <div class="text-center mb-3">
            <a href="<?= APP_URL ?>/index.php?page=login" class="back-link">
                <i class="fas fa-arrow-left me-2"></i>Back to Login
            </a>
        </div>

        <div class="pricing-header">
            <h1><i class="fas fa-bolt me-2" style="color:#f6c23e;"></i><?= APP_NAME ?></h1>
            <p>Live pricing from your billing engine. Start small and scale safely.</p>
            <div class="db-note">
                <i class="fas fa-database me-1"></i>Plans on this page are loaded dynamically from <code>saas_plans</code>.
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <?php if (!empty($plans)): ?>
                <?php foreach ($plans as $plan): ?>
                    <?php
                        $effectivePrice = SaaSBillingHelper::effectivePlanPrice($plan);
                        $price = (float)($plan['price'] ?? 0);
                        $offer = isset($plan['offer_price']) ? (float)$plan['offer_price'] : null;
                        $features = $extractFeatures($plan);
                        $isFeatured = !empty($plan['is_featured']);
                        $billing = strtoupper((string)($plan['billing_type'] ?? 'monthly'));
                        $days = (int)($plan['duration_days'] ?? 30);
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="pricing-card <?= $isFeatured ? 'featured' : '' ?>">
                            <div class="plan-name"><?= e($plan['name'] ?? 'Plan') ?></div>
                            <div class="plan-price">
                                <?php if ($offer !== null && $offer > 0 && $offer < $price): ?>
                                    <small><s>Rs <?= number_format($price, 2) ?></s></small><br>
                                <?php endif; ?>
                                Rs <?= number_format($effectivePrice, 2) ?>
                                <small>/<?= strtolower($billing) ?></small>
                            </div>
                            <p class="plan-desc">
                                <?= !empty($plan['description']) ? e($plan['description']) : 'Built for reliable daily business operations.' ?>
                                <br><small class="text-uppercase">Valid <?= $days ?> days</small>
                            </p>

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
            <a href="<?= APP_URL ?>/index.php?page=demo_login" class="btn btn-outline-info btn-sm mt-2">
                <i class="fas fa-play-circle me-1"></i>Try Free Demo
            </a>
        </div>
    </div>
</div>
</body>
</html>
