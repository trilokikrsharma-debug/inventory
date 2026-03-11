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
        .pricing-header { text-align: center; margin-bottom: 3rem; }
        .pricing-header h1 { color: #fff; font-weight: 800; font-size: 2.5rem; margin-bottom: 0.5rem; }
        .pricing-header p { color: #858796; font-size: 1.1rem; }

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
            border-color: rgba(78,115,223,0.3);
        }
        .pricing-card.featured {
            border-color: rgba(78,115,223,0.5);
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
        .plan-price small { font-size: 1rem; font-weight: 400; color: #858796; }
        .plan-price .currency { font-size: 1.5rem; vertical-align: super; }
        .plan-desc { color: #858796; font-size: 0.85rem; margin-bottom: 1.5rem; }

        .feature-list { list-style: none; padding: 0; margin: 0 0 2rem; }
        .feature-list li {
            padding: 0.5rem 0;
            color: #b7b9cc;
            font-size: 0.88rem;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .feature-list li i {
            width: 20px;
            margin-right: 8px;
        }
        .feature-list li .fa-check { color: #1cc88a; }
        .feature-list li .fa-times { color: #e74a3b; opacity: 0.5; }

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
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
        }
        .btn-plan-outline:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
            border-color: rgba(78,115,223,0.5);
        }
        .back-link { color: #858796; text-decoration: none; transition: color 0.2s; }
        .back-link:hover { color: #4e73df; }
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
            <p>Simple, transparent pricing. Start free, upgrade as you grow.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Starter Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card">
                    <div class="plan-name">Starter</div>
                    <div class="plan-price"><span class="currency">₹</span>0<small>/month</small></div>
                    <p class="plan-desc">Perfect for new businesses getting started.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i>Up to 3 Users</li>
                        <li><i class="fas fa-check"></i>500 Products</li>
                        <li><i class="fas fa-check"></i>GST Invoicing</li>
                        <li><i class="fas fa-check"></i>Customer Management</li>
                        <li><i class="fas fa-check"></i>Basic Reports</li>
                        <li><i class="fas fa-check"></i>Payment Tracking</li>
                        <li><i class="fas fa-times"></i>Quotations</li>
                        <li><i class="fas fa-times"></i>AI Insights</li>
                        <li><i class="fas fa-times"></i>Data Backup</li>
                    </ul>
                    <a href="<?= APP_URL ?>/index.php?page=signup" class="btn btn-plan btn-plan-outline">
                        Get Started Free
                    </a>
                </div>
            </div>

            <!-- Growth Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card featured">
                    <div class="plan-name">Growth</div>
                    <div class="plan-price"><span class="currency">₹</span>499<small>/month</small></div>
                    <p class="plan-desc">For growing businesses that need more power.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i>Up to 10 Users</li>
                        <li><i class="fas fa-check"></i>5,000 Products</li>
                        <li><i class="fas fa-check"></i>GST Invoicing</li>
                        <li><i class="fas fa-check"></i>Customer Management</li>
                        <li><i class="fas fa-check"></i>Advanced Reports</li>
                        <li><i class="fas fa-check"></i>Payment Tracking</li>
                        <li><i class="fas fa-check"></i>Quotations</li>
                        <li><i class="fas fa-check"></i>Purchase Orders</li>
                        <li><i class="fas fa-times"></i>AI Insights</li>
                    </ul>
                    <a href="<?= APP_URL ?>/index.php?page=signup" class="btn btn-plan btn-plan-primary">
                        <i class="fas fa-rocket me-1"></i>Start Growth Plan
                    </a>
                </div>
            </div>

            <!-- Pro Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card">
                    <div class="plan-name">Pro</div>
                    <div class="plan-price"><span class="currency">₹</span>999<small>/month</small></div>
                    <p class="plan-desc">Full-featured for established businesses.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i>Unlimited Users</li>
                        <li><i class="fas fa-check"></i>Unlimited Products</li>
                        <li><i class="fas fa-check"></i>GST Invoicing</li>
                        <li><i class="fas fa-check"></i>Customer Management</li>
                        <li><i class="fas fa-check"></i>Advanced Reports</li>
                        <li><i class="fas fa-check"></i>Quotations + PO</li>
                        <li><i class="fas fa-check"></i>AI Business Insights</li>
                        <li><i class="fas fa-check"></i>Data Backup & Export</li>
                        <li><i class="fas fa-check"></i>Priority Support</li>
                    </ul>
                    <a href="<?= APP_URL ?>/index.php?page=signup" class="btn btn-plan btn-plan-outline">
                        Go Pro
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <p style="color:#858796;font-size:0.9rem;">
                <i class="fas fa-shield-alt me-1"></i>All plans include SSL encryption, daily backups, and CSRF protection.
            </p>
            <a href="<?= APP_URL ?>/index.php?page=demo_login" class="btn btn-outline-info btn-sm mt-2">
                <i class="fas fa-play-circle me-1"></i>Try Free Demo — No Signup Required
            </a>
        </div>
    </div>
</div>
</body>
</html>
