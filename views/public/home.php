<?php
require_once __DIR__ . '/_partials/brand.php';

$assets = tsa_brand_assets();
$homeUrl = rtrim(APP_URL, '/') . '/';
$faviconUrl = $assets['favicon'];
$socialImageUrl = $assets['og'];
$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? ($cspNonce ?? ''), ENT_QUOTES);
$designVariant = 4;
$variantSectionClass = [
    1 => 'tsa-home-layout-slate',
    2 => 'tsa-home-layout-editorial',
    3 => 'tsa-home-layout-midnight',
    4 => 'tsa-home-layout-corporate',
    5 => 'tsa-home-layout-signature',
][$designVariant];

$trustBadges = [
    ['icon' => 'fa-certificate', 'label' => 'Udyam Registered'],
    ['icon' => 'fa-file-invoice', 'label' => 'GST-ready workflows'],
    ['icon' => 'fa-cloud-arrow-up', 'label' => 'Cloud-based system'],
    ['icon' => 'fa-user-shield', 'label' => 'Role-based access'],
];

$heroHighlights = [
    ['value' => 'Live operations', 'text' => 'Billing, stock, and reporting stay in one connected workspace.'],
    ['value' => 'Team-ready control', 'text' => 'Owners and staff work inside role-based flows built for daily use.'],
];

$heroStats = [
    ['value' => 'One workspace', 'text' => 'Billing, inventory, customers, and reports stay connected.'],
    ['value' => 'Role-based access', 'text' => 'Separate owner, manager, and staff flows without confusion.'],
    ['value' => 'Cloud-ready', 'text' => 'Work from desktop, laptop, or phone without local installs.'],
];

$overviewCards = [
    ['icon' => 'fa-file-invoice-dollar', 'title' => 'Billing', 'text' => 'Create GST invoices, quotations, and payment-linked records from one operational workflow.'],
    ['icon' => 'fa-boxes-stacked', 'title' => 'Inventory', 'text' => 'Track products, stock movement, low-stock visibility, and category structure inside one workspace.'],
    ['icon' => 'fa-users', 'title' => 'Customers', 'text' => 'Keep billing history, dues, and repeat-customer records connected to day-to-day activity.'],
    ['icon' => 'fa-truck-field', 'title' => 'Suppliers', 'text' => 'Manage supplier records, purchase entries, and payable visibility without scattered follow-up.'],
    ['icon' => 'fa-chart-column', 'title' => 'Reports', 'text' => 'Review revenue, balances, stock, and operational summaries with cleaner owner visibility.'],
];

$productScreens = [
    [
        'label' => 'Billing screen',
        'title' => 'Invoices, dues, and recent activity in one billing view',
        'text' => 'The billing workflow keeps invoice activity, customer follow-up, and payment status visible without switching between tools.',
        'theme' => 'billing',
        'image' => '/assets/home-screen-sales.svg',
    ],
    [
        'label' => 'Inventory screen',
        'title' => 'Catalog control with live stock signals',
        'text' => 'Products, SKU details, category structure, and low-stock visibility stay accessible in one operational list.',
        'theme' => 'inventory',
        'image' => '/assets/home-screen-inventory.svg',
    ],
    [
        'label' => 'Reports screen',
        'title' => 'Reports organized around daily business review',
        'text' => 'Sales, purchases, stock, dues, and finance visibility are grouped into one reporting layer for owners and managers.',
        'theme' => 'reports',
        'image' => '/assets/home-screen-reports.svg',
    ],
];

$steps = [
    ['step' => '01', 'title' => 'Create workspace', 'text' => 'Set up your business account and open your first cloud workspace in a few minutes.'],
    ['step' => '02', 'title' => 'Add products and customers', 'text' => 'Bring your catalog, customer records, and daily operating data into one place.'],
    ['step' => '03', 'title' => 'Start billing and tracking', 'text' => 'Run invoices, monitor stock, and follow payments from the same system.'],
];

$featureGroups = [
    [
        'hero' => true,
        'icon' => 'fa-file-invoice-dollar',
        'kicker' => 'Core Workflow',
        'title' => 'Billing & Sales Control',
        'text' => 'Create GST-ready invoices, track payments, manage customer dues, and handle daily sales — all from one billing view without switching tools.',
        'usecase' => 'Track daily billing and cash flow at the counter.',
        'bullets' => [
            'GST invoices, quotations, and sale returns',
            'Payment tracking with paid / due status per customer',
            'Print-ready PDF invoices for every transaction',
            'Daily, weekly, and monthly sales summaries',
        ],
    ],
    [
        'hero' => true,
        'icon' => 'fa-boxes-stacked',
        'kicker' => 'Core Workflow',
        'title' => 'Inventory & Stock Management',
        'text' => 'Know exact stock levels before selling, get low-stock alerts, and track product movement between purchases and sales — so nothing runs out unnoticed.',
        'usecase' => 'Know exact stock levels before selling.',
        'bullets' => [
            'Real-time stock count per product and category',
            'Low-stock alerts before items run out',
            'Purchase-to-stock pipeline tracking',
            'Product catalog with SKU, price, and tax setup',
        ],
    ],
    [
        'hero' => false,
        'icon' => 'fa-users',
        'title' => 'Customer & Supplier Tracking',
        'text' => 'See full billing history and outstanding amounts for every customer. Track supplier purchases and payable balances in one place.',
        'usecase' => 'See dues from customers and payments to suppliers.',
        'bullets' => [
            'Customer-wise invoice and payment history',
            'Outstanding dues and follow-up visibility',
            'Supplier purchase entries and payable tracking',
        ],
    ],
    [
        'hero' => false,
        'icon' => 'fa-chart-column',
        'title' => 'Reports & Business Insights',
        'text' => 'Review sales performance, stock levels, customer dues, and purchase activity from one reporting dashboard built for daily business review.',
        'usecase' => 'Review today\'s numbers before closing the day.',
        'bullets' => [
            'Sales, purchase, and profit reports',
            'Customer and supplier outstanding summaries',
            'Stock valuation and movement reports',
        ],
    ],
    [
        'hero' => false,
        'icon' => 'fa-users-gear',
        'title' => 'Multi-User & Role Access',
        'text' => 'Give your billing staff, store manager, and accountant separate logins with role-based permissions — owners see everything, staff sees what they need.',
        'usecase' => 'Staff bills at the counter. Owner reviews from anywhere.',
        'bullets' => [
            'Owner, manager, and staff role controls',
            'Activity logs for audit and accountability',
            'Secure sessions with device-aware access',
        ],
    ],
    [
        'hero' => false,
        'icon' => 'fa-cloud',
        'title' => 'Cloud Workspace',
        'text' => 'Access your billing, inventory, and reports from any device — counter desktop, laptop at home, or phone on the go. No local installation needed.',
        'usecase' => 'Run your business from counter, office, or remotely.',
        'bullets' => [
            'Access from any browser on any device',
            'No software installation or local backups needed',
            'Data stays secure and always accessible',
        ],
    ],
];

$useCases = [
    ['icon' => 'fa-store', 'title' => 'Retail shop', 'text' => 'Fast billing, repeat customers, product lookup, and owner visibility for day-to-day counter operations.'],
    ['icon' => 'fa-truck-ramp-box', 'title' => 'Distributor', 'text' => 'Handle larger catalogs, supplier coordination, purchase flow, and stock movement with better control.'],
    ['icon' => 'fa-screwdriver-wrench', 'title' => 'Service business', 'text' => 'Track customer records, billing activity, and operational follow-up from one workspace.'],
];

$growthSignals = [
    ['icon' => 'fa-compass-drafting', 'title' => 'Designed for Indian SMEs', 'text' => 'Created around the billing, stock, and follow-up patterns that smaller Indian businesses run every day.'],
    ['icon' => 'fa-briefcase', 'title' => 'Used in real business workflows', 'text' => 'The product presentation mirrors active billing desks, inventory handling, and owner review routines.'],
    ['icon' => 'fa-calendar-check', 'title' => 'Built for daily operations', 'text' => 'It is structured for repeated use throughout the day, not for occasional back-office reporting only.'],
];

$trustExamples = [
    ['title' => 'Retail shops managing daily billing', 'text' => 'Used where teams need faster invoice entry, payment status tracking, and repeat-customer visibility through the day.'],
    ['title' => 'Distributors tracking stock and dues', 'text' => 'Used for larger catalogs, low-stock follow-up, supplier coordination, and customer outstanding balances.'],
    ['title' => 'Service businesses keeping records organized', 'text' => 'Used where customer history, billing activity, and follow-up need to stay in one clean operating system.'],
];

$microTestimonials = [
    ['quote' => 'Helps keep invoices and stock in one place so daily follow-up is easier.'],
    ['quote' => 'The billing and dues view is clearer than managing everything in separate files.'],
    ['quote' => 'Useful when the owner wants one system for sales, stock, and reporting.'],
];

$securityCards = [
    ['icon' => 'fa-user-lock', 'title' => 'Role-based access', 'text' => 'Give owners, managers, and staff the access they need without exposing everything to everyone.'],
    ['icon' => 'fa-clock-rotate-left', 'title' => 'Audit-friendly history', 'text' => 'Operational records stay easier to review when workflows run inside one system.'],
    ['icon' => 'fa-server', 'title' => 'Cloud deployment', 'text' => 'Use the platform from connected locations instead of relying on one local machine or disconnected files.'],
];

$assuranceCards = [
    ['value' => 'HTTPS', 'title' => 'Encrypted sessions', 'text' => 'Traffic is served over TLS with security headers and secure cookies on the live site.'],
    ['value' => 'RBAC', 'title' => 'Scoped user access', 'text' => 'Teams can work with restricted permissions instead of shared credentials.'],
    ['value' => 'Audit', 'title' => 'Reviewable activity', 'text' => 'Critical business operations stay easier to trace and validate.'],
    ['value' => 'Cloud', 'title' => 'Device-flexible delivery', 'text' => 'Counter teams and owners can access the same operating system from different devices.'],
];

$faqItems = [
    [
        'question' => 'What is TSA Legacy used for?',
        'answer' => 'TSA Legacy helps Indian businesses manage GST billing, inventory, customer records, supplier workflows, and operational reporting in one cloud system.',
    ],
    [
        'question' => 'Who is TSA Legacy designed for?',
        'answer' => 'It is designed for Indian retail, distribution, trading, and service-led businesses that want clearer control over daily operations.',
    ],
    [
        'question' => 'Does TSA Legacy provide instant demo access?',
        'answer' => 'Yes. Instant demo access opens a guided product workspace so you can review the product flow before creating your own account.',
    ],
    [
        'question' => 'Can teams use TSA Legacy across devices?',
        'answer' => 'Yes. The product is browser-based, so owners and staff can access the same workspace from desktop, laptop, or mobile devices.',
    ],
];

$faqSchema = [
    [
        '@type' => 'Question',
        'name' => 'What is TSA Legacy used for?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'TSA Legacy helps Indian businesses manage GST billing, inventory, customer records, supplier workflows, and operational reporting in one cloud system.',
        ],
    ],
    [
        '@type' => 'Question',
        'name' => 'Who is TSA Legacy designed for?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'It is designed for Indian retail, distribution, trading, and service-led businesses that want clearer control over daily operations.',
        ],
    ],
    [
        '@type' => 'Question',
        'name' => 'Does TSA Legacy provide instant demo access?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => 'Yes. Instant demo access opens a guided product workspace so you can review the product flow before creating your own account.',
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-adsense-account" content="ca-pub-9384564101816113">
    <title>GST Billing & Inventory Management Software for Indian Businesses | <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?></title>
    <meta name="description" content="TSA Legacy brings GST billing, inventory, customers, suppliers, and reports into one cloud-based business system for Indian SMEs.">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="theme-color" content="#16385f">
    <meta name="color-scheme" content="light">
    <meta property="og:title" content="GST Billing & Inventory Management Software for Indian Businesses | <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta property="og:description" content="Run billing, inventory, and daily business operations from one structured cloud system.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($homeUrl, ENT_QUOTES) ?>">
    <meta property="og:locale" content="en_IN">
    <meta property="og:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <meta property="og:image:alt" content="<?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta property="og:site_name" content="<?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="GST Billing & Inventory Management Software for Indian Businesses | <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>">
    <meta name="twitter:description" content="Run billing, inventory, and daily business operations from one structured cloud system.">
    <meta name="twitter:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($assets['brand_css'], ENT_QUOTES) ?>">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9384564101816113"
        crossorigin="anonymous"></script>

    <script type="application/ld+json" nonce="<?= $nonce ?>">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'TSA Legacy',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => $homeUrl,
            'description' => 'Cloud-based GST billing, inventory management, customer tracking and operational reporting software for Indian businesses.',
            'publisher' => [
                '@type' => 'Organization',
                'name' => defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME,
                'url' => $homeUrl,
            ],
            'mainEntity' => $faqSchema,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <script type="application/ld+json" nonce="<?= $nonce ?>">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    'name' => defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME,
                    'url' => $homeUrl,
                    'logo' => $assets['logo_dark'],
                    'email' => 'triloki@tsalegacy.com',
                    'sameAs' => ['https://linkedin.com/company/tsalegacy-ventures'],
                ],
                [
                    '@type' => 'WebSite',
                    'name' => defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME,
                    'url' => $homeUrl,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <style>
        .tsa-home-hero{padding:12px 0 18px}
        .tsa-home-hero-stage{
            position:relative;overflow:hidden;padding:22px;border-radius:34px;border:1px solid rgba(20,33,58,.08);
            background:
                radial-gradient(circle at top left, rgba(22,56,95,.12), transparent 26%),
                radial-gradient(circle at bottom right, rgba(160,122,67,.10), transparent 24%),
                linear-gradient(160deg, rgba(255,255,255,.96) 0%, rgba(248,245,238,.98) 48%, rgba(237,243,249,.96) 100%);
            box-shadow:0 32px 78px rgba(20,33,58,.10);
        }
        .tsa-home-hero-stage::before{
            content:"";position:absolute;inset:0;pointer-events:none;opacity:.55;
            background-image:
                linear-gradient(rgba(20,33,58,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(20,33,58,.03) 1px, transparent 1px);
            background-size:24px 24px;
            mask-image:linear-gradient(180deg, rgba(0,0,0,.75), transparent 88%);
        }
        .tsa-home-hero-grid{display:grid;grid-template-columns:1fr;gap:28px;align-items:center}
        .tsa-home-hero-copy{position:relative;z-index:1;padding:4px 2px}
        .tsa-home-hero-copy::before{
            content:"";position:absolute;left:-36px;top:-26px;width:220px;height:220px;border-radius:50%;
            background:radial-gradient(circle, rgba(22,56,95,.18), rgba(22,56,95,0) 72%);filter:blur(6px);z-index:-1;pointer-events:none
        }
        .tsa-home-hero h1{margin:16px 0 12px;font-size:clamp(2.3rem,10vw,5rem);line-height:.94;letter-spacing:-.055em;color:var(--tsa-ink)}
        .tsa-home-hero h1{animation:tsaHeroRise .7s ease both}
        .tsa-home-hero-accent{
            display:block;margin-top:8px;font-family:"Instrument Serif",serif;font-weight:400;font-style:italic;color:var(--tsa-primary)
        }
        .tsa-home-hero p{max-width:700px;font-size:1rem;line-height:1.8;color:var(--tsa-muted)}
        .tsa-home-lead{font-size:1.06rem!important;max-width:650px;margin-top:14px}
        .tsa-home-note{margin-top:14px;font-size:.84rem;color:var(--tsa-muted)}
        .tsa-home-trustline{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
        .tsa-home-trustline span{
            display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:999px;
            border:1px solid rgba(20,33,58,.08);background:rgba(255,255,255,.82);font-size:.78rem;font-weight:800;color:var(--tsa-ink)
        }
        .tsa-home-trustline i{color:var(--tsa-accent-strong)}
        .tsa-home-hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:22px}
        .tsa-home-cta-primary{
            position:relative;
            background:linear-gradient(135deg,var(--tsa-primary),var(--tsa-primary-strong));
            border-color:#102948;
            color:#fff;
            box-shadow:0 24px 56px rgba(16,38,63,.32), 0 0 0 1px rgba(255,255,255,.18) inset
        }
        .tsa-home-cta-primary::after{
            content:"";position:absolute;inset:auto 18px -10px 18px;height:18px;border-radius:999px;
            background:radial-gradient(circle, rgba(16,38,63,.32), rgba(16,38,63,0) 72%);filter:blur(10px);z-index:-1
        }
        .tsa-home-proof-list{display:grid;grid-template-columns:1fr;gap:12px;margin-top:22px}
        .tsa-home-proof-chip{
            padding:16px 18px;border-radius:20px;border:1px solid rgba(20,33,58,.08);background:rgba(255,255,255,.82);
            box-shadow:0 20px 44px rgba(20,33,58,.08)
        }
        .tsa-home-proof-chip strong{display:block;font-size:.94rem;color:var(--tsa-ink);margin-bottom:6px}
        .tsa-home-proof-chip span{display:block;font-size:.82rem;line-height:1.65;color:var(--tsa-muted)}
        .tsa-home-trustbar{padding:20px 0 0}
        .tsa-home-badge-shell{
            padding:16px;border-radius:28px;border:1px solid rgba(20,33,58,.08);background:rgba(255,255,255,.68);
            backdrop-filter:blur(10px);box-shadow:var(--tsa-shadow-soft);
        }
        .tsa-home-badges{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
        .tsa-home-badge{display:flex;align-items:center;gap:12px;padding:16px 18px;border-radius:18px;border:1px solid var(--tsa-line);background:rgba(255,255,255,.84);box-shadow:var(--tsa-shadow-soft)}
        .tsa-home-badge i{
            display:inline-flex;align-items:center;justify-content:center;min-width:40px;height:40px;border-radius:14px;
            background:rgba(22,56,95,.10);color:var(--tsa-primary)
        }
        .tsa-home-badge span{font-size:.86rem;font-weight:800;color:var(--tsa-ink)}
        .tsa-home-preview{
            display:flex;flex-direction:column;gap:24px;
            position:relative;padding:24px;border-radius:28px;max-width:580px;margin:0 auto;z-index:1;background:
                radial-gradient(circle at top right, rgba(94,139,190,.16), transparent 28%),
                radial-gradient(circle at bottom left, rgba(118,162,210,.14), transparent 24%),
                linear-gradient(180deg,#11233a 0%, #17314f 100%);
            border:1px solid rgba(19,38,61,.16);box-shadow:0 34px 90px rgba(20,33,58,.20)
        }
        .tsa-home-preview::before{
            content:"";position:absolute;inset:10px -8px auto auto;width:200px;height:200px;border-radius:999px;
            background:radial-gradient(circle, rgba(135,178,227,.20), transparent 72%);filter:blur(10px);pointer-events:none
        }
        .tsa-home-preview::after{
            content:"";position:absolute;left:12px;bottom:6px;width:180px;height:180px;border-radius:999px;
            background:radial-gradient(circle, rgba(255,255,255,.08), transparent 72%);filter:blur(14px);pointer-events:none
        }
        .tsa-home-window{
            order:2;overflow:hidden;border-radius:24px;border:1px solid rgba(20,33,58,.08);background:#fff;
            transform:perspective(2000px) rotateY(-4deg) rotateX(2deg) translateY(0);transform-origin:center;box-shadow:0 36px 88px rgba(20,33,58,.22);
            animation:tsaFloatPanel 6.8s ease-in-out infinite
        }
        .tsa-home-product-visual{
            order:2;display:block;width:100%;height:auto;border-radius:24px;border:1px solid rgba(20,33,58,.10);
            background:#f7fbff;box-shadow:0 36px 88px rgba(20,33,58,.22);
            transform:perspective(2000px) rotateY(-4deg) rotateX(2deg) translateY(0);transform-origin:center;
            animation:tsaFloatPanel 6.8s ease-in-out infinite
        }
        .tsa-home-window-top{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid rgba(20,33,58,.08);background:#fffdf8}
        .tsa-home-dots{display:flex;gap:7px}
        .tsa-home-dots span{width:10px;height:10px;border-radius:50%;background:#d7dde5}
        .tsa-home-dots span:nth-child(1){background:#fca5a5}
        .tsa-home-dots span:nth-child(2){background:#fcd34d}
        .tsa-home-dots span:nth-child(3){background:#86efac}
        .tsa-home-window-label{font-size:.76rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--tsa-muted)}
        .tsa-home-pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:var(--tsa-primary-soft);font-size:.77rem;font-weight:800;color:var(--tsa-primary)}
        .tsa-home-window-body{display:grid;grid-template-columns:88px minmax(0,1fr);gap:16px;padding:18px;background:linear-gradient(180deg,#fff 0%, #fbfaf7 100%)}
        .tsa-home-sidebar{padding:14px 12px;border-radius:18px;background:#13233c;color:#d9e5f5}
        .tsa-home-sidebar-brand{display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,.08);margin-bottom:16px}
        .tsa-home-sidebar-list{display:grid;gap:10px}
        .tsa-home-sidebar-item{display:flex;align-items:center;gap:10px;padding:10px 10px;border-radius:12px;font-size:.74rem;font-weight:800;color:#d9e5f5}
        .tsa-home-sidebar-item.is-active{background:rgba(255,255,255,.10)}
        .tsa-home-main{display:grid;gap:14px}
        .tsa-home-headbar{display:flex;align-items:center;justify-content:space-between;gap:12px}
        .tsa-home-headbar h3{margin:0;font-size:1rem;color:var(--tsa-ink)}
        .tsa-home-headbar small{display:block;font-size:.78rem;color:var(--tsa-muted)}
        .tsa-home-userpill{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:999px;background:#f5f8fb;border:1px solid rgba(20,33,58,.08);font-size:.76rem;font-weight:800;color:var(--tsa-ink)}
        .tsa-home-main-grid{display:grid;grid-template-columns:1fr;gap:14px}
        .tsa-home-panel{padding:18px;border-radius:20px;border:1px solid rgba(20,33,58,.08);background:#fff;box-shadow:var(--tsa-shadow-soft)}
        .tsa-home-metrics{display:grid;grid-template-columns:1fr;gap:10px}
        .tsa-home-metric{padding:14px;border-radius:16px;background:#edf2f7;border:1px solid rgba(22,56,95,.10)}
        .tsa-home-metric strong{display:block;font-size:1.08rem;line-height:1;color:var(--tsa-ink);margin-bottom:6px}
        .tsa-home-metric span{display:block;font-size:.76rem;color:var(--tsa-muted)}
        .tsa-home-table{display:grid;gap:10px;margin-top:12px}
        .tsa-home-table-row{display:grid;grid-template-columns:1fr;gap:4px;padding:11px 12px;border-radius:14px;background:#fbfcfd;border:1px solid rgba(20,33,58,.06);font-size:.78rem;color:var(--tsa-muted-strong)}
        .tsa-home-table-row strong{color:var(--tsa-ink)}
        .tsa-home-chart{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));align-items:end;gap:10px;height:170px;padding-top:12px}
        .tsa-home-bar{display:flex;align-items:flex-end;justify-content:center;height:100%}
        .tsa-home-bar span{display:block;width:100%;border-radius:14px 14px 6px 6px;background:linear-gradient(180deg,#315882 0%, #16385f 100%);box-shadow:inset 0 -10px 18px rgba(255,255,255,.12)}
        .tsa-home-chart .tsa-home-bar:nth-child(1) span{height:58%}
        .tsa-home-chart .tsa-home-bar:nth-child(2) span{height:74%}
        .tsa-home-chart .tsa-home-bar:nth-child(3) span{height:67%}
        .tsa-home-chart .tsa-home-bar:nth-child(4) span{height:88%}
        .tsa-home-chart .tsa-home-bar:nth-child(5) span{height:80%}
        .tsa-home-chart .tsa-home-bar:nth-child(6) span{height:96%}
        .tsa-home-preview-card{
            position:static;padding:20px;border-radius:20px;border:1px solid rgba(20,33,58,.08);
            background:rgba(255,255,255,.94);backdrop-filter:blur(12px);box-shadow:0 14px 44px rgba(20,33,58,.10);
        }
        .tsa-home-preview-card strong{display:block;font-size:.9rem;color:var(--tsa-ink);margin-bottom:6px}
        .tsa-home-preview-card span{display:block;font-size:.8rem;line-height:1.55;color:var(--tsa-muted)}
        .tsa-home-preview-card.is-billing{order:1;max-width:none}
        .tsa-home-preview-card.is-stock{order:3;max-width:none}
        .tsa-home-preview-card .metric{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;margin-top:12px;border-radius:999px;background:var(--tsa-primary-soft);font-size:.76rem;font-weight:800;color:var(--tsa-primary);white-space:nowrap}
        .tsa-home-table-mini{display:grid;gap:10px;margin-top:12px}
        .tsa-home-mini-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid rgba(20,33,58,.08);font-size:.8rem;color:var(--tsa-muted-strong)}
        .tsa-home-mini-row:last-child{border-bottom:none}
        .tsa-home-mini-row strong{color:var(--tsa-ink)}
        .tsa-home-preview-signals{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:16px}
        .tsa-home-signal{padding:14px;border-radius:18px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.10)}
        .tsa-home-signal strong{display:block;font-size:.95rem;color:#edf4fb;margin-bottom:4px}
        .tsa-home-signal span{display:block;font-size:.8rem;line-height:1.6;color:#d4e2f0}
        .tsa-home-overview-grid{display:grid;grid-template-columns:1fr;gap:18px}
        .tsa-home-screen-grid{display:grid;grid-template-columns:1fr;gap:18px}
        .tsa-home-screen-card{padding:18px}
        .tsa-home-screen-label{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:var(--tsa-primary-soft);font-size:.74rem;font-weight:800;color:var(--tsa-primary);margin-bottom:14px}
        .tsa-home-shot{
            padding:12px;border-radius:20px;background:linear-gradient(180deg,#fff 0%, #f8fafb 100%);border:1px solid rgba(20,33,58,.08);
            box-shadow:0 24px 52px rgba(20,33,58,.10);margin-bottom:14px
        }
        .tsa-home-shot-image{
            display:block;width:100%;height:auto;border-radius:16px;border:1px solid rgba(20,33,58,.08);
            background:#eef4fb;box-shadow:0 18px 42px rgba(20,33,58,.08);margin-bottom:14px
        }
        .tsa-home-shot-top{display:flex;align-items:center;justify-content:space-between;gap:10px;padding-bottom:10px;border-bottom:1px solid rgba(20,33,58,.08);margin-bottom:12px}
        .tsa-home-shot-title{font-size:.8rem;font-weight:800;color:var(--tsa-ink)}
        .tsa-home-shot-sub{font-size:.72rem;color:var(--tsa-muted)}
        .tsa-home-shot-body{display:grid;gap:10px}
        .tsa-home-shot-billing{display:grid;grid-template-columns:1fr;gap:12px}
        .tsa-home-shot-panel{padding:12px;border-radius:14px;background:#fff;border:1px solid rgba(20,33,58,.08)}
        .tsa-home-shot-panel h4{margin:0 0 10px;font-size:.8rem;color:var(--tsa-ink)}
        .tsa-home-shot-band{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:14px;background:#edf2f7;border:1px solid rgba(22,56,95,.08);font-size:.73rem;color:var(--tsa-muted-strong)}
        .tsa-home-shot-band strong{color:var(--tsa-ink)}
        .tsa-home-shot-table{display:grid;gap:6px}
        .tsa-home-shot-table-head,.tsa-home-shot-table-row{
            display:grid;gap:8px;padding:9px 10px;border-radius:12px;font-size:.72rem
        }
        .tsa-home-shot-table-head{
            background:#f4f7fb;border:1px solid rgba(20,33,58,.06);font-weight:800;color:var(--tsa-ink)
        }
        .tsa-home-shot-table-row{
            background:#fff;border:1px solid rgba(20,33,58,.08);color:var(--tsa-muted-strong)
        }
        .tsa-home-shot-table-row strong{color:var(--tsa-ink)}
        .tsa-home-shot-billing-table .tsa-home-shot-table-head,
        .tsa-home-shot-billing-table .tsa-home-shot-table-row{grid-template-columns:1.2fr 1fr 1fr .8fr}
        .tsa-home-shot-inventory-table .tsa-home-shot-table-head,
        .tsa-home-shot-inventory-table .tsa-home-shot-table-row{grid-template-columns:1.2fr .8fr .8fr .7fr}
        .tsa-home-shot-status{
            display:inline-flex;align-items:center;justify-content:center;padding:4px 8px;border-radius:999px;
            background:var(--tsa-primary-soft);font-size:.67rem;font-weight:800;color:var(--tsa-primary)
        }
        .tsa-home-shot-status.is-due{background:#f4ead8;color:#8a6731}
        .tsa-home-shot-list{display:grid;gap:8px}
        .tsa-home-shot-list span{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:.74rem;color:var(--tsa-muted-strong)}
        .tsa-home-shot-list strong{color:var(--tsa-ink)}
        .tsa-home-shot-inventory{display:grid;gap:8px}
        .tsa-home-stock-row{display:grid;grid-template-columns:1.1fr .6fr .6fr;gap:8px;padding:10px 12px;border-radius:12px;background:#fff;border:1px solid rgba(20,33,58,.08);font-size:.74rem;color:var(--tsa-muted-strong)}
        .tsa-home-stock-row strong{color:var(--tsa-ink)}
        .tsa-home-stock-row .ok{color:#16385f;font-weight:800}
        .tsa-home-stock-row .low{color:#9a7742;font-weight:800}
        .tsa-home-shot-reports{display:grid;grid-template-columns:1.1fr .9fr;gap:10px}
        .tsa-home-report-chart{padding:14px;border-radius:14px;background:#fff;border:1px solid rgba(20,33,58,.08)}
        .tsa-home-report-chart-bars{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));align-items:end;gap:8px;height:120px;margin-top:14px}
        .tsa-home-report-chart-bars span{display:block;border-radius:12px 12px 5px 5px;background:linear-gradient(180deg,#6f8eb0 0%, #16385f 100%)}
        .tsa-home-report-chart-bars span:nth-child(1){height:52%}
        .tsa-home-report-chart-bars span:nth-child(2){height:70%}
        .tsa-home-report-chart-bars span:nth-child(3){height:62%}
        .tsa-home-report-chart-bars span:nth-child(4){height:84%}
        .tsa-home-report-chart-bars span:nth-child(5){height:96%}
        .tsa-home-report-stack{display:grid;gap:10px}
        .tsa-home-report-tile{padding:14px;border-radius:14px;background:#fff;border:1px solid rgba(20,33,58,.08)}
        .tsa-home-report-tile strong{display:block;font-size:.8rem;color:var(--tsa-ink);margin-bottom:4px}
        .tsa-home-report-tile span{display:block;font-size:.72rem;line-height:1.55;color:var(--tsa-muted)}
        .tsa-home-growth-shell{
            position:relative;overflow:hidden;padding:34px;border-radius:32px;border:1px solid rgba(20,33,58,.08);
            background:
                radial-gradient(circle at top left, rgba(98,144,195,.16), transparent 28%),
                radial-gradient(circle at bottom right, rgba(255,255,255,.08), transparent 22%),
                linear-gradient(160deg, #11233a 0%, #193452 100%);
            box-shadow:0 30px 72px rgba(20,33,58,.18)
        }
        .tsa-home-growth-shell .tsa-section-kicker,
        .tsa-home-growth-shell h2,
        .tsa-home-growth-shell p,
        .tsa-home-growth-pill{color:#edf4fb}
        .tsa-home-growth-shell .tsa-section-kicker{color:#a9c8eb}
        .tsa-home-growth-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:26px}
        .tsa-home-growth-heading{margin-bottom:0}
        .tsa-home-growth-meta{display:flex;flex-wrap:wrap;gap:10px}
        .tsa-home-growth-pill{
            display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;background:rgba(255,255,255,.08);
            border:1px solid rgba(255,255,255,.10);font-size:.78rem;font-weight:800;color:#edf4fb
        }
        .tsa-home-growth-grid{display:grid;grid-template-columns:1fr;gap:18px}
        .tsa-home-proof-panel{padding:26px;background:rgba(255,255,255,.92)}
        .tsa-home-proof-panel h3{margin:0 0 10px;font-size:1.08rem;color:var(--tsa-ink)}
        .tsa-home-proof-panel p{margin:0;color:var(--tsa-muted);font-size:.95rem;line-height:1.8}
        .tsa-home-proof-listing{display:grid;gap:14px;margin-top:18px}
        .tsa-home-proof-row{
            display:grid;grid-template-columns:44px minmax(0,1fr);gap:12px;align-items:start;padding:16px 0;border-top:1px solid rgba(20,33,58,.08)
        }
        .tsa-home-proof-row:first-child{border-top:none;padding-top:0}
        .tsa-home-proof-row .tsa-icon-chip{margin-bottom:0}
        .tsa-home-testimonial-grid{display:grid;grid-template-columns:1fr;gap:18px}
        .tsa-home-testimonial-card{padding:22px}
        .tsa-home-testimonial-card blockquote{
            margin:0;color:var(--tsa-ink);font-size:.96rem;line-height:1.8;font-weight:600
        }
        .tsa-home-testimonial-card small{
            display:block;margin-top:14px;color:var(--tsa-muted);font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase
        }
        .tsa-home-snapshot-grid{display:grid;grid-template-columns:1fr;gap:18px}
        .tsa-home-snapshot-card{padding:22px}
        .tsa-home-snapshot-card h3{margin:0 0 8px;font-size:1.02rem;color:var(--tsa-ink)}
        .tsa-home-snapshot-card p{margin:0 0 14px;color:var(--tsa-muted);font-size:.9rem;line-height:1.75}
        .tsa-home-output-sheet{
            padding:14px;border-radius:18px;background:linear-gradient(180deg,#fff 0%, #f8fafc 100%);border:1px solid rgba(20,33,58,.08);box-shadow:0 18px 40px rgba(20,33,58,.08)
        }
        .tsa-home-output-header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid rgba(20,33,58,.08)}
        .tsa-home-output-header strong{font-size:.8rem;color:var(--tsa-ink)}
        .tsa-home-output-header span{font-size:.72rem;color:var(--tsa-muted)}
        .tsa-home-invoice-lines{display:grid;gap:8px}
        .tsa-home-invoice-line,.tsa-home-stock-mini-row{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:.75rem;color:var(--tsa-muted-strong)}
        .tsa-home-invoice-total{display:flex;align-items:center;justify-content:space-between;gap:10px;padding-top:10px;margin-top:10px;border-top:1px solid rgba(20,33,58,.08);font-weight:800;color:var(--tsa-ink)}
        .tsa-home-report-mini-bars{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));align-items:end;gap:8px;height:110px;margin:14px 0 10px}
        .tsa-home-report-mini-bars span{display:block;border-radius:12px 12px 5px 5px;background:linear-gradient(180deg,#94abc5 0%, #16385f 100%)}
        .tsa-home-report-mini-bars span:nth-child(1){height:46%}
        .tsa-home-report-mini-bars span:nth-child(2){height:68%}
        .tsa-home-report-mini-bars span:nth-child(3){height:72%}
        .tsa-home-report-mini-bars span:nth-child(4){height:94%}
        .tsa-home-report-mini-list{display:grid;gap:8px}
        .tsa-home-report-mini-list div{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:.75rem;color:var(--tsa-muted-strong)}
        .tsa-home-report-mini-list strong,.tsa-home-stock-mini-row strong{color:var(--tsa-ink)}
        @keyframes tsaHeroRise{
            from{opacity:0;transform:translateY(10px)}
            to{opacity:1;transform:translateY(0)}
        }
        @keyframes tsaFloatPanel{
            0%,100%{transform:perspective(2000px) rotateY(-6deg) rotateX(3deg) translateY(0)}
            50%{transform:perspective(2000px) rotateY(-4deg) rotateX(2deg) translateY(-4px)}
        }
        .tsa-home-step{padding:26px;border-radius:24px;border:1px solid var(--tsa-line);background:linear-gradient(180deg,#fff 0%, #f7f2ea 100%);box-shadow:var(--tsa-shadow-soft)}
        .tsa-home-step-index{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:16px;background:rgba(22,56,95,.10);color:var(--tsa-primary);font-size:.9rem;font-weight:800;letter-spacing:.08em;margin-bottom:16px}
        .tsa-home-step h3{margin:0 0 10px;font-size:1.05rem;color:var(--tsa-ink)}
        .tsa-home-step p{margin:0;color:var(--tsa-muted);font-size:.94rem;line-height:1.8}
        .tsa-home-use-grid{display:grid;grid-template-columns:1fr;gap:18px}
        .tsa-home-use-card{padding:24px}
        .tsa-home-use-card h3{margin:0 0 10px;font-size:1.08rem;color:var(--tsa-ink)}
        .tsa-home-use-card p{margin:0;color:var(--tsa-muted);font-size:.94rem;line-height:1.8}
        .tsa-home-demo-grid{display:grid;grid-template-columns:1fr;gap:22px;align-items:stretch}
        .tsa-home-demo-list{list-style:none;margin:16px 0 0;padding:0;display:grid;gap:10px}
        .tsa-home-demo-list li{display:flex;align-items:flex-start;gap:10px;color:var(--tsa-ink);font-size:.9rem}
        .tsa-home-demo-list i{color:var(--tsa-primary);margin-top:4px}
        .tsa-home-demo-preview{padding:18px}
        .tsa-home-demo-preview .tsa-home-shot{margin-bottom:0}
        .tsa-home-pricing-preview{display:grid;grid-template-columns:1fr;gap:18px}
        .tsa-home-price-card{padding:24px}
        .tsa-home-price-card:nth-child(2){
            background:linear-gradient(180deg,#15324f 0%, #102948 100%);
            border-color:rgba(19,38,61,.30);
            box-shadow:0 26px 52px rgba(16,38,63,.20);
        }
        .tsa-home-price-card:nth-child(2) h3,
        .tsa-home-price-card:nth-child(2) .tsa-home-price-value,
        .tsa-home-price-card:nth-child(2) .tsa-home-price-value small,
        .tsa-home-price-card:nth-child(2) p,
        .tsa-home-price-card:nth-child(2) li{color:#edf4fb}
        .tsa-home-price-card:nth-child(2) .tsa-home-price-list i{color:#a9c8eb}
        .tsa-home-price-card:nth-child(2)::before{
            content:"Most Popular";display:inline-flex;align-items:center;justify-content:center;
            width:max-content;padding:7px 12px;margin-bottom:14px;border-radius:999px;
            background:rgba(255,255,255,.12);color:#fff;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;
        }
        .tsa-home-price-card h3{margin:0 0 10px;font-size:1.05rem;color:var(--tsa-ink)}
        .tsa-home-price-card p{margin:0 0 14px;color:var(--tsa-muted);font-size:.93rem;line-height:1.75}
        .tsa-home-price-value{display:block;font-size:2.2rem;line-height:1;color:var(--tsa-ink);font-weight:800;letter-spacing:-.05em;margin-bottom:8px}
        .tsa-home-price-value small{font-size:.9rem;color:var(--tsa-muted);font-weight:700}
        .tsa-home-price-list{list-style:none;margin:0;padding:0;display:grid;gap:10px}
        .tsa-home-price-list li{display:flex;align-items:flex-start;gap:10px;font-size:.88rem;color:var(--tsa-ink)}
        .tsa-home-price-list i{color:var(--tsa-primary);margin-top:4px}
        /* Feature groups — hero + secondary */
        .tsa-feat-hero-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px}
        .tsa-feat-hero-card{
            position:relative;overflow:hidden;padding:32px;border-radius:28px;
            border:1px solid rgba(20,33,58,.10);box-shadow:0 24px 56px rgba(20,33,58,.10);
            background:linear-gradient(168deg,#ffffff 0%,#f8f5ee 56%,#edf2f8 100%);
            transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease
        }
        .tsa-feat-hero-card::before{
            content:"";position:absolute;top:-40px;right:-40px;width:220px;height:220px;border-radius:50%;
            background:radial-gradient(circle,rgba(22,56,95,.10),transparent 70%);pointer-events:none
        }
        .tsa-feat-hero-card:hover{transform:translateY(-4px);border-color:rgba(22,56,95,.18);box-shadow:0 28px 64px rgba(20,33,58,.14)}
        .tsa-feat-hero-card .tsa-icon-chip{width:52px;height:52px;border-radius:18px;font-size:1.15rem;margin-bottom:18px;background:rgba(22,56,95,.12)}
        .tsa-feat-hero-card h3{font-size:1.35rem;letter-spacing:-.03em;margin:0 0 12px;color:var(--tsa-ink)}
        .tsa-feat-hero-card>p{color:var(--tsa-muted);font-size:.96rem;line-height:1.8;margin:0 0 18px}
        .tsa-feat-usecase{
            display:inline-flex;align-items:center;gap:8px;padding:9px 14px;border-radius:999px;
            background:var(--tsa-primary-soft);font-size:.78rem;font-weight:800;color:var(--tsa-primary);margin-bottom:18px
        }
        .tsa-feat-bullets{list-style:none;padding:0;margin:0;display:grid;gap:10px}
        .tsa-feat-bullets li{display:flex;align-items:flex-start;gap:10px;font-size:.9rem;color:var(--tsa-ink);font-weight:500}
        .tsa-feat-bullets i{color:var(--tsa-primary);margin-top:3px;font-size:.85em}
        .tsa-feat-sec-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;margin-top:22px}
        .tsa-feat-sec-card{
            padding:26px;border-radius:24px;
            border:1px solid var(--tsa-line);background:rgba(255,255,255,.82);box-shadow:var(--tsa-shadow-soft);
            transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease
        }
        .tsa-feat-sec-card::after{
            content:"";position:absolute;inset:0 auto auto 0;width:100%;height:1px;
            background:linear-gradient(90deg,rgba(255,255,255,.78),rgba(255,255,255,0));pointer-events:none
        }
        .tsa-feat-sec-card:hover{transform:translateY(-4px);border-color:rgba(22,56,95,.18);box-shadow:0 26px 54px rgba(20,33,58,.12)}
        .tsa-feat-sec-card .tsa-icon-chip{margin-bottom:14px}
        .tsa-feat-sec-card h3{font-size:1.05rem;letter-spacing:-.02em;margin:0 0 8px;color:var(--tsa-ink)}
        .tsa-feat-sec-card>p{color:var(--tsa-muted);font-size:.9rem;line-height:1.75;margin:0 0 14px}
        .tsa-feat-sec-usecase{font-size:.8rem;color:var(--tsa-accent);font-weight:700;font-style:italic;margin:0 0 14px;display:flex;align-items:center;gap:6px}
        .tsa-feat-sec-usecase i{font-size:.72rem}
        .tsa-feat-sec-bullets{list-style:none;padding:0;margin:0;display:grid;gap:8px}
        .tsa-feat-sec-bullets li{display:flex;align-items:flex-start;gap:8px;font-size:.84rem;color:var(--tsa-muted-strong)}
        .tsa-feat-sec-bullets i{color:var(--tsa-primary);margin-top:3px;font-size:.7em}
        .tsa-home-story-copy{max-width:840px;margin:0 auto;text-align:center}
        .tsa-home-support-note{margin-top:14px}
        .tsa-home-assurance-head{margin-top:32px}
        .tsa-home-assurance-head h2{font-size:clamp(1.8rem,3vw,2.4rem)}
        .tsa-home-faq-section{padding-top:0}
        .tsa-home-cta-section{padding-top:12px}
        .tsa-home-demo-head{margin-bottom:22px}
        .tsa-home-empty-pricing{text-align:center}
        .tsa-home-tight-top{padding-top:0}
        .tsa-home-layout-editorial .tsa-section-head,
        .tsa-home-layout-editorial .tsa-home-story-copy,
        .tsa-home-layout-editorial .tsa-home-demo-grid > div{max-width:920px}
        .tsa-home-layout-editorial .tsa-section-head h2,
        .tsa-home-layout-editorial .tsa-home-story-shell h2,
        .tsa-home-layout-editorial .tsa-home-support-shell h2{
            font-family:"Instrument Serif",serif;font-weight:400;letter-spacing:-.035em;
        }
        .tsa-home-layout-midnight .tsa-card,
        .tsa-home-layout-midnight .tsa-home-story-shell,
        .tsa-home-layout-midnight .tsa-home-support-shell,
        .tsa-home-layout-midnight .tsa-home-badge-shell,
        .tsa-home-layout-midnight .tsa-cta-box{border-radius:28px}
        .tsa-home-layout-corporate .tsa-section-head{max-width:680px}
        .tsa-home-layout-corporate .tsa-card,
        .tsa-home-layout-corporate .tsa-home-badge-shell,
        .tsa-home-layout-corporate .tsa-home-story-shell,
        .tsa-home-layout-corporate .tsa-home-support-shell{border-radius:20px}
        .tsa-home-layout-signature .tsa-section-head h2,
        .tsa-home-layout-signature .tsa-home-story-shell h2,
        .tsa-home-layout-signature .tsa-home-support-shell h2{letter-spacing:-.05em}
        .tsa-hero-centered{
            position:relative;padding:38px 22px 24px;border-radius:36px;border:1px solid rgba(20,33,58,.08);
            background:radial-gradient(circle at top center, rgba(22,56,95,.10), transparent 30%), linear-gradient(180deg, rgba(255,255,255,.97) 0%, rgba(244,242,236,.98) 100%);
            box-shadow:0 28px 70px rgba(20,33,58,.08);text-align:center;
        }
        .tsa-hero-centered .tsa-eyebrow{margin:0 auto}
        .tsa-hero-centered h1{margin:18px auto 14px;max-width:920px;font-size:clamp(2.5rem,8vw,5.6rem);line-height:.92;letter-spacing:-.06em}
        .tsa-hero-centered p{max-width:760px;margin:0 auto;color:var(--tsa-muted);font-size:1.02rem;line-height:1.85}
        .tsa-hero-centered .tsa-hero-actions{justify-content:center;margin-top:24px}
        .tsa-hero-centered-note{margin-top:14px;font-size:.84rem;color:var(--tsa-muted)}
        .tsa-hero-centered-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:26px}
        .tsa-hero-centered-stat{padding:18px;border-radius:20px;border:1px solid rgba(20,33,58,.08);background:rgba(255,255,255,.82);box-shadow:var(--tsa-shadow-soft)}
        .tsa-hero-centered-stat strong{display:block;font-size:1.15rem;color:var(--tsa-ink);margin-bottom:6px}
        .tsa-hero-centered-stat span{display:block;font-size:.84rem;color:var(--tsa-muted)}
        .tsa-hero-editorial{display:grid;gap:24px;padding:24px;border-radius:38px;border:1px solid rgba(20,33,58,.08);background:linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(248,243,236,.98) 100%);box-shadow:0 28px 72px rgba(20,33,58,.09)}
        .tsa-hero-editorial-copy h1{margin:18px 0 14px;font-size:clamp(2.4rem,8vw,5rem);line-height:.95;letter-spacing:-.055em;font-family:"Instrument Serif",serif;font-weight:400}
        .tsa-hero-editorial-copy p{max-width:680px;color:var(--tsa-muted);font-size:1rem;line-height:1.9}
        .tsa-hero-editorial-rail{display:grid;gap:14px}
        .tsa-hero-editorial-card{padding:18px;border-radius:22px;background:#fff;border:1px solid rgba(20,33,58,.08);box-shadow:var(--tsa-shadow-soft)}
        .tsa-hero-editorial-card strong{display:block;font-size:1rem;color:var(--tsa-ink);margin-bottom:6px}
        .tsa-hero-editorial-card span{display:block;font-size:.84rem;color:var(--tsa-muted);line-height:1.7}
        .tsa-hero-midnight{position:relative;overflow:hidden;padding:26px;border-radius:38px;border:1px solid rgba(95,143,214,.18);background:radial-gradient(circle at top left, rgba(93,143,214,.22), transparent 28%), linear-gradient(180deg, #0d1725 0%, #13243a 100%);box-shadow:0 34px 80px rgba(10,18,29,.32);color:#edf4ff}
        .tsa-hero-midnight-grid{display:grid;gap:22px}
        .tsa-hero-midnight .tsa-eyebrow{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.10);color:#d9e7f7}
        .tsa-hero-midnight h1{margin:18px 0 12px;font-size:clamp(2.5rem,9vw,5.2rem);line-height:.92;letter-spacing:-.06em;color:#fff}
        .tsa-hero-midnight p{max-width:680px;color:#b7c9df;font-size:1rem;line-height:1.85}
        .tsa-hero-midnight .tsa-hero-actions{margin-top:22px}
        .tsa-hero-midnight .tsa-btn-secondary{background:rgba(255,255,255,.08);color:#fff;border-color:rgba(255,255,255,.18)}
        .tsa-hero-midnight-rail{display:grid;gap:12px}
        .tsa-hero-midnight-tile{padding:16px 18px;border-radius:20px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.10)}
        .tsa-hero-midnight-tile strong{display:block;font-size:1rem;color:#fff;margin-bottom:6px}
        .tsa-hero-midnight-tile span{display:block;font-size:.84rem;color:#c5d5e8;line-height:1.7}
        .tsa-hero-corporate{display:grid;gap:20px;padding:26px;border-radius:28px;border:1px solid rgba(20,33,58,.08);background:#fff;box-shadow:var(--tsa-shadow-soft)}
        .tsa-hero-corporate-head{max-width:760px}
        .tsa-hero-corporate h1{margin:16px 0 12px;font-size:clamp(2.4rem,7vw,4.9rem);line-height:.94;letter-spacing:-.055em}
        .tsa-hero-corporate p{color:var(--tsa-muted);font-size:1rem;line-height:1.82}
        .tsa-hero-corporate-grid{display:grid;gap:16px}
        .tsa-hero-corporate-box{padding:18px;border-radius:18px;border:1px solid rgba(20,33,58,.08);background:linear-gradient(180deg,#fff 0%, #f6f9fc 100%)}
        .tsa-hero-corporate-box strong{display:block;font-size:.98rem;margin-bottom:6px;color:var(--tsa-ink)}
        .tsa-hero-corporate-box span{display:block;font-size:.84rem;line-height:1.7;color:var(--tsa-muted)}
        .tsa-hero-signature{display:grid;gap:22px;padding:22px;border-radius:38px;border:1px solid rgba(94,57,39,.10);background:linear-gradient(180deg, rgba(255,251,246,.98) 0%, rgba(245,235,224,.98) 100%);box-shadow:0 30px 72px rgba(94,57,39,.10)}
        .tsa-hero-signature-copy h1{margin:18px 0 12px;font-size:clamp(2.4rem,9vw,5rem);line-height:.93;letter-spacing:-.058em}
        .tsa-hero-signature-copy p{color:var(--tsa-muted);font-size:1rem;line-height:1.88}
        .tsa-hero-signature-aside{display:grid;gap:14px}
        .tsa-hero-signature-card{padding:18px;border-radius:22px;border:1px solid rgba(94,57,39,.10);background:#fff;box-shadow:var(--tsa-shadow-soft)}
        .tsa-hero-signature-card strong{display:block;font-size:1rem;color:var(--tsa-ink);margin-bottom:6px}
        .tsa-hero-signature-card span{display:block;font-size:.84rem;color:var(--tsa-muted);line-height:1.72}

        body.tsa-variant-2{
            --tsa-bg:#f3efe8;
            --tsa-bg-soft:#faf6f0;
            --tsa-ink:#251f1a;
            --tsa-muted:#71675e;
            --tsa-muted-strong:#5e554d;
            --tsa-primary:#3a4b66;
            --tsa-primary-strong:#243247;
            --tsa-accent:#af7b3d;
            --tsa-accent-strong:#8b5f2a;
            --tsa-primary-soft:#e8edf5;
        }
        body.tsa-variant-2 .tsa-home-hero-stage,
        body.tsa-variant-2 .tsa-home-growth-shell,
        body.tsa-variant-2 .tsa-cta-box{
            border-radius:40px;
        }
        body.tsa-variant-2 .tsa-home-hero h1,
        body.tsa-variant-2 .tsa-section-head h2,
        body.tsa-variant-2 .tsa-home-story-shell h2,
        body.tsa-variant-2 .tsa-home-support-shell h2{
            font-family:"Instrument Serif",serif;
            font-weight:400;
            letter-spacing:-.035em;
        }
        body.tsa-variant-2 .tsa-home-hero-stage{
            background:linear-gradient(180deg, rgba(255,252,247,.98) 0%, rgba(247,240,231,.98) 100%);
        }
        body.tsa-variant-2 .tsa-home-preview{
            background:linear-gradient(180deg,#5a4636 0%, #312720 100%);
            border-color:rgba(73,56,44,.36);
        }

        body.tsa-variant-3{
            --tsa-bg:#0d1522;
            --tsa-bg-soft:#121b2a;
            --tsa-surface:#121b2a;
            --tsa-surface-strong:#182334;
            --tsa-ink:#edf4ff;
            --tsa-muted:#a7b6cb;
            --tsa-muted-strong:#c5d0df;
            --tsa-line:rgba(167,182,203,.18);
            --tsa-line-strong:rgba(167,182,203,.28);
            --tsa-primary:#5d8fd6;
            --tsa-primary-strong:#2c5e9f;
            --tsa-accent:#7fb3ff;
            --tsa-accent-strong:#5d8fd6;
            --tsa-primary-soft:rgba(93,143,214,.18);
        }
        body.tsa-variant-3{
            background:
                radial-gradient(circle at top left, rgba(93,143,214,.16), transparent 26%),
                radial-gradient(circle at top right, rgba(127,179,255,.12), transparent 24%),
                linear-gradient(180deg, #0b121c 0%, #101928 52%, #0d1522 100%);
        }
        body.tsa-variant-3 .tsa-nav,
        body.tsa-variant-3 .tsa-mobile-menu,
        body.tsa-variant-3 .tsa-home-hero-stage,
        body.tsa-variant-3 .tsa-card,
        body.tsa-variant-3 .tsa-panel,
        body.tsa-variant-3 .tsa-cta-box,
        body.tsa-variant-3 .tsa-home-badge-shell,
        body.tsa-variant-3 .tsa-home-story-shell,
        body.tsa-variant-3 .tsa-home-support-shell,
        body.tsa-variant-3 .tsa-footer-shell{
            background:rgba(18,27,42,.86);
            border-color:rgba(167,182,203,.14);
            color:var(--tsa-ink);
        }
        body.tsa-variant-3 .tsa-home-preview{background:linear-gradient(180deg,#0b1420 0%, #13243b 100%)}
        body.tsa-variant-3 .tsa-home-window,
        body.tsa-variant-3 .tsa-home-preview-card,
        body.tsa-variant-3 .tsa-home-shot,
        body.tsa-variant-3 .tsa-home-output-sheet,
        body.tsa-variant-3 .tsa-home-shot-panel,
        body.tsa-variant-3 .tsa-home-report-chart,
        body.tsa-variant-3 .tsa-home-report-tile,
        body.tsa-variant-3 .tsa-home-panel,
        body.tsa-variant-3 .tsa-home-price-card{
            background:#162234;
            border-color:rgba(167,182,203,.12);
        }
        body.tsa-variant-3 .tsa-home-shot-title,
        body.tsa-variant-3 .tsa-card h3,
        body.tsa-variant-3 .tsa-home-price-value,
        body.tsa-variant-3 .tsa-home-output-header strong,
        body.tsa-variant-3 .tsa-home-invoice-total,
        body.tsa-variant-3 .tsa-home-bar strong,
        body.tsa-variant-3 .tsa-home-metric strong,
        body.tsa-variant-3 .tsa-home-signal strong{color:#edf4ff}
        body.tsa-variant-3 .tsa-card p,
        body.tsa-variant-3 .tsa-home-shot-sub,
        body.tsa-variant-3 .tsa-home-output-header span,
        body.tsa-variant-3 .tsa-home-mini-row,
        body.tsa-variant-3 .tsa-home-report-mini-list div,
        body.tsa-variant-3 .tsa-home-price-card p{color:#a7b6cb}
        body.tsa-variant-3 .tsa-home-growth-shell,
        body.tsa-variant-3 .tsa-home-proof-panel{background:#132033}

        body.tsa-variant-4{
            --tsa-bg:#eef4fb;
            --tsa-bg-soft:#f7fbff;
            --tsa-ink:#10263c;
            --tsa-muted:#587089;
            --tsa-muted-strong:#415b74;
            --tsa-primary:#0f4c81;
            --tsa-primary-strong:#0a3557;
            --tsa-accent:#1d74c7;
            --tsa-accent-strong:#0f4c81;
            --tsa-primary-soft:#e3effa;
            --tsa-shadow:0 18px 42px -18px rgba(15,76,129,.16), 0 8px 20px -14px rgba(0,0,0,.06);
            --tsa-shadow-soft:0 12px 28px -18px rgba(15,76,129,.14), 0 4px 12px -10px rgba(0,0,0,.05);
        }
        body.tsa-variant-4 .tsa-home-hero-stage,
        body.tsa-variant-4 .tsa-home-badge-shell,
        body.tsa-variant-4 .tsa-home-growth-shell,
        body.tsa-variant-4 .tsa-home-story-shell,
        body.tsa-variant-4 .tsa-home-support-shell,
        body.tsa-variant-4 .tsa-cta-box,
        body.tsa-variant-4 .tsa-footer-shell,
        body.tsa-variant-4 .tsa-nav{
            border-radius:22px;
        }
        body.tsa-variant-4 .tsa-home-hero-stage{
            background:linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(235,244,252,.98) 100%);
        }
        body.tsa-variant-4 .tsa-home-preview{
            background:linear-gradient(180deg,#f7fbff 0%, #dcecfb 100%);
            border-color:rgba(15,76,129,.12);
        }
        body.tsa-variant-4 .tsa-home-signal{
            background:#fff;border-color:rgba(15,76,129,.12)
        }
        body.tsa-variant-4 .tsa-home-signal strong{color:var(--tsa-primary-strong)}
        body.tsa-variant-4 .tsa-home-signal span{color:var(--tsa-muted)}

        body.tsa-variant-5{
            --tsa-bg:#f4eee7;
            --tsa-bg-soft:#fbf7f1;
            --tsa-ink:#2d221a;
            --tsa-muted:#776456;
            --tsa-muted-strong:#5e4d41;
            --tsa-primary:#5e3927;
            --tsa-primary-strong:#3e2418;
            --tsa-accent:#be8450;
            --tsa-accent-strong:#9f6938;
            --tsa-primary-soft:#efe2d5;
        }
        body.tsa-variant-5{
            background:
                radial-gradient(circle at top left, rgba(190,132,80,.12), transparent 26%),
                radial-gradient(circle at top right, rgba(94,57,39,.10), transparent 24%),
                linear-gradient(180deg, #fcfaf7 0%, #f4eee7 56%, #ede2d6 100%);
        }
        body.tsa-variant-5 .tsa-home-hero-stage{
            background:linear-gradient(160deg, rgba(255,250,245,.98) 0%, rgba(245,235,224,.98) 100%);
        }
        body.tsa-variant-5 .tsa-home-preview,
        body.tsa-variant-5 .tsa-home-growth-shell{
            background:linear-gradient(180deg,#3e2418 0%, #5e3927 100%);
            border-color:rgba(94,57,39,.26);
        }
        body.tsa-variant-5 .tsa-home-growth-shell .tsa-section-kicker,
        body.tsa-variant-5 .tsa-home-growth-shell h2,
        body.tsa-variant-5 .tsa-home-growth-shell p,
        body.tsa-variant-5 .tsa-home-growth-pill,
        body.tsa-variant-5 .tsa-home-signal strong,
        body.tsa-variant-5 .tsa-home-signal span{color:#fff5ea}
        body.tsa-variant-5 .tsa-home-growth-pill{border-color:rgba(255,245,234,.14);background:rgba(255,245,234,.08)}
        @media (max-width:1240px){
            .tsa-home-use-grid,.tsa-home-screen-grid,.tsa-home-snapshot-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .tsa-home-shot-billing{grid-template-columns:1fr}
            .tsa-feat-sec-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        }
        @media (max-width:1100px){
            .tsa-home-hero-grid,.tsa-home-demo-grid,.tsa-home-window-body,.tsa-home-main-grid,.tsa-home-growth-grid,.tsa-home-shot-reports{grid-template-columns:1fr}
            .tsa-home-badges,.tsa-home-overview-grid,.tsa-home-pricing-preview,.tsa-home-preview-signals,.tsa-home-proof-list,.tsa-home-testimonial-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .tsa-feat-hero-grid{grid-template-columns:1fr}
        }
        @media (max-width:820px){
            .tsa-home-use-grid,.tsa-home-screen-grid,.tsa-home-snapshot-grid{grid-template-columns:1fr}
            .tsa-home-badges,.tsa-home-overview-grid,.tsa-home-pricing-preview,.tsa-home-preview-signals,.tsa-home-metrics,.tsa-home-proof-list,.tsa-home-testimonial-grid{grid-template-columns:1fr}
            .tsa-home-window{transform:none}
            .tsa-feat-sec-grid{grid-template-columns:1fr}
            .tsa-home-shot-billing-table .tsa-home-shot-table-head,
            .tsa-home-shot-billing-table .tsa-home-shot-table-row,
            .tsa-home-shot-inventory-table .tsa-home-shot-table-head,
            .tsa-home-shot-inventory-table .tsa-home-shot-table-row{grid-template-columns:1fr 1fr}
            .tsa-home-shot-table-head span:nth-child(n+3),
            .tsa-home-shot-table-row span:nth-child(n+3){display:none}
        }
        @media (max-width:560px){
            .tsa-home-hero{padding-top:10px}
            .tsa-home-hero-grid{gap:22px}
            .tsa-home-hero-stage{padding:16px;border-radius:26px}
            .tsa-home-preview{padding:18px;border-radius:24px}
            .tsa-home-window-top,.tsa-home-window-body{padding-left:14px;padding-right:14px}
            .tsa-home-sidebar{padding:12px 10px}
            .tsa-home-headbar{align-items:flex-start;flex-direction:column}
            .tsa-home-hero-actions .tsa-btn{width:100%}
            .tsa-home-growth-shell{padding:22px}
            .tsa-home-proof-panel,.tsa-home-use-card,.tsa-home-price-card,.tsa-home-snapshot-card,.tsa-home-step,.tsa-feat-hero-card,.tsa-feat-sec-card{padding:20px}
            .tsa-home-trustline span{width:100%;justify-content:flex-start}
            .tsa-home-badges{grid-template-columns:1fr}
        }
        @media (min-width:821px){
            .tsa-home-hero{padding:44px 0 26px}
            .tsa-home-hero-grid{grid-template-columns:1.02fr .98fr;gap:38px}
            .tsa-home-hero-stage{padding:28px 28px 30px}
            .tsa-home-proof-list{grid-template-columns:repeat(2,minmax(0,1fr))}
            .tsa-home-overview-grid{grid-template-columns:repeat(5,minmax(0,1fr))}
            .tsa-home-screen-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
            .tsa-home-snapshot-grid{grid-template-columns:1.15fr .85fr .85fr}
            .tsa-home-use-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
            .tsa-home-pricing-preview{grid-template-columns:repeat(3,minmax(0,1fr))}
            .tsa-home-demo-grid{grid-template-columns:minmax(0,1fr) 320px}
            .tsa-home-main-grid{grid-template-columns:1.05fr .95fr}
            .tsa-home-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}
            .tsa-home-table-row{grid-template-columns:1.2fr .9fr .7fr;gap:10px}
            .tsa-home-growth-grid{grid-template-columns:1.1fr .9fr}
            .tsa-home-testimonial-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
            .tsa-home-badges{grid-template-columns:repeat(4,minmax(0,1fr))}
        }
    </style>
</head>
<body class="tsa-public tsa-variant-<?= $designVariant ?>">
<?php tsa_render_public_nav([
    'active_href' => APP_URL . '/',
    'links' => [
        ['href' => APP_URL . '/#features', 'label' => 'Features'],
        ['href' => APP_URL . '/#security', 'label' => 'Security'],
        ['href' => APP_URL . '/pricing', 'label' => 'Pricing'],
        ['href' => APP_URL . '/blog', 'label' => 'Guides'],
    ],
    'secondary_label' => 'Instant Demo Access',
    'secondary_href' => APP_URL . '/demo',
    'primary_label' => 'Start Free Trial',
    'primary_href' => APP_URL . '/signup',
]); ?>

<main class="tsa-page" id="main-content">
    <div class="tsa-container">
        <section class="tsa-home-hero <?= $variantSectionClass ?>">
            <?php if ($designVariant === 1): ?>
            <div class="tsa-home-hero-stage">
                <div class="tsa-home-hero-grid">
                    <div class="tsa-home-hero-copy">
                        <div class="tsa-eyebrow"><span class="dot"></span>Secure Business Operations Software</div>
                        <h1>Take back control of your business.<br><span class="tsa-serif tsa-home-hero-accent">Run operations from one structured system.</span></h1>
                        <p class="tsa-home-lead">TSA Legacy brings GST billing, inventory tracking, customer histories, supplier pipelines, and reporting into one reliable cloud workspace for Indian SMEs that need clarity, speed, and control.</p>
                        <div class="tsa-home-hero-actions">
                            <a href="<?= APP_URL ?>/signup" class="tsa-btn tsa-btn-primary tsa-btn-trial tsa-home-cta-primary">Start Free Trial</a>
                            <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-secondary">Instant Demo Access</a>
                        </div>
                        <div class="tsa-home-note">No credit card required. Setup is fast, and instant demo access opens a guided product workspace before signup.</div>
                        <div class="tsa-home-trustline">
                            <span><i class="fas fa-briefcase"></i> Built for real business workflows</span>
                            <span><i class="fas fa-calendar-day"></i> Designed for Indian SMEs</span>
                            <span><i class="fas fa-check-double"></i> Simple, structured, reliable</span>
                        </div>
                        <div class="tsa-home-proof-list">
                            <?php foreach ($heroHighlights as $item): ?>
                                <div class="tsa-home-proof-chip">
                                    <strong><?= htmlspecialchars($item['value']) ?></strong>
                                    <span><?= htmlspecialchars($item['text']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="tsa-home-statbar" aria-label="Operational highlights">
                            <?php foreach ($heroStats as $item): ?>
                                <div class="tsa-home-stat">
                                    <strong><?= htmlspecialchars($item['value']) ?></strong>
                                    <span><?= htmlspecialchars($item['text']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <aside class="tsa-home-preview">
            <?php elseif ($designVariant === 2): ?>
            <div class="tsa-hero-editorial">
                <div class="tsa-hero-editorial-copy">
                    <div class="tsa-eyebrow"><span class="dot"></span>Structured Operating System</div>
                    <h1>Operational clarity for businesses that have outgrown scattered tools.</h1>
                    <p>TSA Legacy brings billing, stock, customer records, dues, and reporting into one system that feels organized from the first screen.</p>
                    <div class="tsa-hero-actions">
                        <a href="<?= APP_URL ?>/signup" class="tsa-btn tsa-btn-primary tsa-btn-trial">Start Free Trial</a>
                        <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-secondary">Instant Demo Access</a>
                    </div>
                </div>
                <div class="tsa-hero-editorial-rail">
                    <?php foreach ($heroHighlights as $item): ?>
                        <div class="tsa-hero-editorial-card">
                            <strong><?= htmlspecialchars($item['value']) ?></strong>
                            <span><?= htmlspecialchars($item['text']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="tsa-home-statbar" aria-label="Operational highlights">
                    <?php foreach ($heroStats as $item): ?>
                        <div class="tsa-home-stat">
                            <strong><?= htmlspecialchars($item['value']) ?></strong>
                            <span><?= htmlspecialchars($item['text']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <aside class="tsa-home-preview">
            <?php elseif ($designVariant === 3): ?>
            <div class="tsa-hero-midnight">
                <div class="tsa-hero-midnight-grid">
                    <div>
                        <div class="tsa-eyebrow"><span class="dot"></span>Cloud Business Command</div>
                        <h1>Run billing, stock, and team activity from one serious workspace.</h1>
                        <p>TSA Legacy is built for owners who need control, visibility, and cleaner execution across daily operations.</p>
                        <div class="tsa-hero-actions">
                            <a href="<?= APP_URL ?>/signup" class="tsa-btn tsa-btn-primary tsa-btn-trial">Start Free Trial</a>
                            <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-secondary">Instant Demo Access</a>
                        </div>
                    </div>
                    <div class="tsa-hero-midnight-rail">
                        <?php foreach ($heroStats as $item): ?>
                            <div class="tsa-hero-midnight-tile">
                                <strong><?= htmlspecialchars($item['value']) ?></strong>
                                <span><?= htmlspecialchars($item['text']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <aside class="tsa-home-preview">
            <?php elseif ($designVariant === 4): ?>
            <div class="tsa-hero-corporate">
                <div class="tsa-hero-corporate-head">
                    <div class="tsa-eyebrow"><span class="dot"></span>Enterprise-ready Workflow Software</div>
                    <h1>One operating layer for billing, inventory, records, and reporting.</h1>
                    <p>TSA Legacy is designed for businesses that want a cleaner operating system, predictable team workflows, and stronger visibility across day-to-day activity.</p>
                    <div class="tsa-hero-actions">
                        <a href="<?= APP_URL ?>/signup" class="tsa-btn tsa-btn-primary tsa-btn-trial">Start Free Trial</a>
                        <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-secondary">Instant Demo Access</a>
                    </div>
                </div>
                <div class="tsa-hero-corporate-grid">
                    <?php foreach ($heroHighlights as $item): ?>
                        <div class="tsa-hero-corporate-box">
                            <strong><?= htmlspecialchars($item['value']) ?></strong>
                            <span><?= htmlspecialchars($item['text']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <aside class="tsa-home-preview">
            <?php else: ?>
            <div class="tsa-hero-signature">
                <div class="tsa-hero-signature-copy">
                    <div class="tsa-eyebrow"><span class="dot"></span>Built for Real Business Rhythm</div>
                    <h1>When operations feel calm, businesses move faster.</h1>
                    <p>TSA Legacy gives billing desks, stock teams, and business owners one structured environment instead of a pile of disconnected tools.</p>
                    <div class="tsa-hero-actions">
                        <a href="<?= APP_URL ?>/signup" class="tsa-btn tsa-btn-primary tsa-btn-trial">Start Free Trial</a>
                        <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-secondary">Instant Demo Access</a>
                    </div>
                </div>
                <div class="tsa-hero-signature-aside">
                    <?php foreach ($heroStats as $item): ?>
                        <div class="tsa-hero-signature-card">
                            <strong><?= htmlspecialchars($item['value']) ?></strong>
                            <span><?= htmlspecialchars($item['text']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <aside class="tsa-home-preview">
            <?php endif; ?>
                    <div class="tsa-home-preview-card is-billing">
                        <strong>Billing system</strong>
                        <span>Invoice status, collections, and GST-ready records sit inside the same workflow.</span>
                        <div class="metric"><i class="fas fa-bolt"></i> 7 payments logged today</div>
                    </div>
                    <div class="tsa-home-preview-card is-stock">
                        <strong>Inventory control</strong>
                        <span>Low-stock signals and product movement are visible before they become owner problems.</span>
                        <div class="metric"><i class="fas fa-boxes-stacked"></i> 12 alerts active</div>
                    </div>
                    <img class="tsa-home-product-visual" src="<?= htmlspecialchars(APP_URL . '/assets/home-product-dashboard.svg', ENT_QUOTES) ?>" alt="TSA Legacy dashboard showing sales, purchases, dues, stock value, recent sales, and low stock alerts" loading="eager" fetchpriority="high">
                    <div class="tsa-home-preview-signals">
                        <div class="tsa-home-signal"><strong>Counter-ready</strong><span>Fast workflows for daily billing desks.</span></div>
                        <div class="tsa-home-signal"><strong>Cloud-based</strong><span>Use across devices and business locations.</span></div>
                        <div class="tsa-home-signal"><strong>Operational</strong><span>Billing, stock, and records stay connected.</span></div>
                    </div>
                </aside>
            <?php if ($designVariant === 1): ?>
                </div>
            <?php endif; ?>
            </div>
        </section>

        <section class="tsa-home-trustbar">
            <div class="tsa-home-badge-shell">
            <div class="tsa-home-badges">
                <?php foreach ($trustBadges as $badge): ?>
                    <div class="tsa-home-badge">
                        <i class="fas <?= htmlspecialchars($badge['icon']) ?>"></i>
                        <span><?= htmlspecialchars($badge['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            </div>
        </section>

        <section class="tsa-section" id="features">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Product Overview</div>
                <h2>How your daily operations fit into one system</h2>
                <p>Each part of the workflow stays connected, so teams can bill, track stock, manage records, and review activity without switching between disconnected tools.</p>
            </div>
            <div class="tsa-home-overview-grid">
                <?php foreach ($overviewCards as $card): ?>
                    <article class="tsa-card">
                        <div class="tsa-icon-chip"><i class="fas <?= htmlspecialchars($card['icon']) ?>"></i></div>
                        <h3><?= htmlspecialchars($card['title']) ?></h3>
                        <p><?= htmlspecialchars($card['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section tsa-home-tight-top">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Inside The System</div>
                <h2>See how operations look inside the system</h2>
                <p>The interface is designed around practical working screens: billing flow, inventory control, and reporting visibility.</p>
            </div>
            <div class="tsa-home-screen-grid">
                <?php foreach ($productScreens as $screen): ?>
                    <article class="tsa-card tsa-home-screen-card">
                        <div class="tsa-home-screen-label"><i class="fas fa-desktop"></i><?= htmlspecialchars($screen['label']) ?></div>
                        <img class="tsa-home-shot-image" src="<?= htmlspecialchars(APP_URL . $screen['image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($screen['title'], ENT_QUOTES) ?>" loading="lazy">
                        <p><?= htmlspecialchars($screen['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section tsa-section-alt">
            <div class="tsa-home-growth-shell">
                <div class="tsa-home-growth-head">
                <div class="tsa-section-head tsa-home-growth-heading">
                        <div class="tsa-section-kicker">Used in real business environments</div>
                        <h2>Built to look credible because it is built for real work</h2>
                        <p>TSA Legacy is built for businesses that need a dependable operating system with practical billing, inventory, and reporting workflows they can use every day.</p>
                    </div>
                    <div class="tsa-home-growth-meta">
                        <div class="tsa-home-growth-pill"><i class="fas fa-circle-check"></i> Designed for Indian SMEs</div>
                        <div class="tsa-home-growth-pill"><i class="fas fa-briefcase"></i> Used in real business workflows</div>
                        <div class="tsa-home-growth-pill"><i class="fas fa-gears"></i> Built for daily operations</div>
                    </div>
                </div>
                <div class="tsa-home-growth-grid">
                    <div class="tsa-card tsa-home-proof-panel">
                        <h3>Operational trust signals</h3>
                        <p>The product experience is presented as a working business system: dashboard metrics, transaction screens, stock states, and use cases tied to how businesses actually run.</p>
                        <div class="tsa-home-proof-listing">
                            <?php foreach ($growthSignals as $item): ?>
                                <div class="tsa-home-proof-row">
                                    <div class="tsa-icon-chip"><i class="fas <?= htmlspecialchars($item['icon']) ?>"></i></div>
                                    <div>
                                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                                        <p><?= htmlspecialchars($item['text']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="tsa-home-use-grid">
                        <?php foreach ($trustExamples as $index => $item): ?>
                            <article class="tsa-card tsa-home-use-card">
                                <div class="tsa-icon-chip"><i class="fas <?= htmlspecialchars($useCases[$index]['icon']) ?>"></i></div>
                                <h3><?= htmlspecialchars($item['title']) ?></h3>
                                <p><?= htmlspecialchars($item['text']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="tsa-section tsa-home-tight-top">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Working Feedback</div>
                <h2>Short signals of product trust</h2>
                <p>Small teams do not need exaggerated claims. They need to feel that the system is useful in billing, stock handling, and daily review.</p>
            </div>
            <div class="tsa-home-testimonial-grid">
                <?php foreach ($microTestimonials as $item): ?>
                    <article class="tsa-card tsa-home-testimonial-card">
                        <blockquote>“<?= htmlspecialchars($item['quote']) ?>”</blockquote>
                        <small>Product workflow signal</small>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section tsa-home-tight-top">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Example System Output</div>
                <h2>Outputs the team can actually use</h2>
                <p>A business system earns trust when its invoice records, stock views, and reports look ready for real work, not just for a homepage illustration.</p>
            </div>
            <div class="tsa-home-snapshot-grid">
                <article class="tsa-card tsa-home-snapshot-card">
                    <h3>Invoice output</h3>
                    <p>Structured around invoice number, party name, line items, and balance summary.</p>
                    <div class="tsa-home-output-sheet">
                        <div class="tsa-home-output-header">
                            <strong>Invoice INV-2031</strong>
                            <span>Om Traders · 08 Apr 2026</span>
                        </div>
                        <div class="tsa-home-invoice-lines">
                            <div class="tsa-home-invoice-line"><span>Brake Pad Set x 4</span><strong>Rs 8,400</strong></div>
                            <div class="tsa-home-invoice-line"><span>Engine Oil 1L x 6</span><strong>Rs 3,600</strong></div>
                            <div class="tsa-home-invoice-line"><span>GST</span><strong>Rs 450</strong></div>
                        </div>
                        <div class="tsa-home-invoice-total"><span>Grand total</span><strong>Rs 12,450</strong></div>
                    </div>
                </article>
                <article class="tsa-card tsa-home-snapshot-card">
                    <h3>Monthly report</h3>
                    <p>Organized for quick monthly review without losing operating detail.</p>
                    <div class="tsa-home-output-sheet">
                        <div class="tsa-home-output-header">
                            <strong>Sales Report</strong>
                            <span>Monthly view</span>
                        </div>
                        <div class="tsa-home-report-mini-bars">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="tsa-home-report-mini-list">
                            <div><span>Total sales</span><strong>Rs 4.82L</strong></div>
                            <div><span>Open dues</span><strong>Rs 38,200</strong></div>
                        </div>
                    </div>
                </article>
                <article class="tsa-card tsa-home-snapshot-card">
                    <h3>Stock status table</h3>
                    <p>Clear product status for day-to-day stock review and follow-up.</p>
                    <div class="tsa-home-output-sheet">
                        <div class="tsa-home-output-header">
                            <strong>Stock Report</strong>
                            <span>Current levels</span>
                        </div>
                        <div class="tsa-home-report-mini-list">
                            <div class="tsa-home-stock-mini-row"><span>Brake Pad Set</span><strong>128 pcs</strong></div>
                            <div class="tsa-home-stock-mini-row"><span>Engine Oil 1L</span><strong>9 pcs</strong></div>
                            <div class="tsa-home-stock-mini-row"><span>Battery Cable</span><strong>64 pcs</strong></div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="tsa-section tsa-section-alt">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">How It Works</div>
                <h2>Get from setup to live operations in three clear steps</h2>
                <p>The first workflow should feel simple: create the workspace, load the business data, and start running billing and tracking from one place.</p>
            </div>
            <div class="tsa-grid-3">
                <?php foreach ($steps as $step): ?>
                    <article class="tsa-home-step">
                        <div class="tsa-home-step-index"><?= htmlspecialchars($step['step']) ?></div>
                        <h3><?= htmlspecialchars($step['title']) ?></h3>
                        <p><?= htmlspecialchars($step['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">What You Can Do</div>
                <h2>Every tool your business runs on, inside one system</h2>
                <p>Built around the daily workflows Indian SMEs actually use — billing customers, managing stock, tracking dues, and reviewing performance — not abstract feature labels.</p>
            </div>

            <div class="tsa-feat-hero-grid">
                <?php foreach ($featureGroups as $fg): ?>
                    <?php if (!empty($fg['hero'])): ?>
                        <article class="tsa-feat-hero-card">
                            <div class="tsa-icon-chip"><i class="fas <?= htmlspecialchars($fg['icon']) ?>"></i></div>
                            <h3><?= htmlspecialchars($fg['title']) ?></h3>
                            <p><?= htmlspecialchars($fg['text']) ?></p>
                            <div class="tsa-feat-usecase"><i class="fas fa-bolt"></i><?= htmlspecialchars($fg['usecase']) ?></div>
                            <ul class="tsa-feat-bullets">
                                <?php foreach ($fg['bullets'] as $bullet): ?>
                                    <li><i class="fas fa-check"></i><span><?= htmlspecialchars($bullet) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="tsa-feat-sec-grid">
                <?php foreach ($featureGroups as $fg): ?>
                    <?php if (empty($fg['hero'])): ?>
                        <article class="tsa-feat-sec-card">
                            <div class="tsa-icon-chip"><i class="fas <?= htmlspecialchars($fg['icon']) ?>"></i></div>
                            <h3><?= htmlspecialchars($fg['title']) ?></h3>
                            <p><?= htmlspecialchars($fg['text']) ?></p>
                            <div class="tsa-feat-sec-usecase"><i class="fas fa-quote-left"></i><?= htmlspecialchars($fg['usecase']) ?></div>
                            <ul class="tsa-feat-sec-bullets">
                                <?php foreach ($fg['bullets'] as $bullet): ?>
                                    <li><i class="fas fa-check"></i><span><?= htmlspecialchars($bullet) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section" id="about">
            <div class="tsa-home-story-shell">
                <div class="tsa-home-story-copy">
                    <h2>Built by <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?></h2>
                    <p><strong>We did not build this to be another generic software product.</strong> We built it because real businesses were still stitching together billing, inventory, and reporting across disconnected tools.</p>
                    <p>Hi, I&apos;m <strong>Triloki</strong>, founder of <?= Helper::escape(defined('APP_COMPANY_NAME') ? APP_COMPANY_NAME : APP_NAME) ?>. While reviewing how Indian SMEs actually operate, I kept seeing the same problem: teams had to combine spreadsheets, billing software, and manual follow-up just to manage ordinary daily work.</p>
                    <p>TSA Legacy was created to solve that exact gap. The mission is direct: provide a <strong>simple, structured, reliable</strong> cloud workspace where growing businesses can run core operations with less friction and better control.</p>
                </div>
            </div>
        </section>

        <section class="tsa-section tsa-home-tight-top">
            <div class="tsa-home-support-shell">
                <div>
                    <h2>Support &amp; onboarding that feels accountable</h2>
                    <p>Software is only as good as the team behind it. TSA Legacy is positioned as an operational partner, not just a signup form.</p>
                    <ul class="tsa-home-support-list">
                        <li><i class="fas fa-check-circle"></i><span>Direct email support for setup, migration, and operational questions</span></li>
                        <li><i class="fas fa-check-circle"></i><span>Guided onboarding help for catalog, customer, and workflow setup</span></li>
                        <li><i class="fas fa-check-circle"></i><span>Human-written documentation and product guides for teams</span></li>
                    </ul>
                </div>
                <div class="tsa-home-support-card">
                    <p>Speak directly with our team</p>
                    <a href="mailto:support@tsalegacy.com">support@tsalegacy.com</a>
                    <div class="tsa-home-note tsa-home-support-note">Practical product help for trial, onboarding, and rollout questions.</div>
                </div>
            </div>
        </section>

        <section class="tsa-section" id="security">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Trust & Security</div>
                <h2>Clear safeguards for teams that need dependable operational control</h2>
                <p>The trust layer should be understandable: controlled access, reviewable records, and cloud-based deployment that supports business continuity.</p>
            </div>
            <div class="tsa-grid-3">
                <?php foreach ($securityCards as $card): ?>
                    <article class="tsa-card">
                        <div class="tsa-icon-chip"><i class="fas <?= htmlspecialchars($card['icon']) ?>"></i></div>
                        <h3><?= htmlspecialchars($card['title']) ?></h3>
                        <p><?= htmlspecialchars($card['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="tsa-section-head tsa-home-assurance-head">
                <div class="tsa-section-kicker">Operational Assurance</div>
                <h2>What the live trust posture already shows</h2>
                <p>The deployed site already exposes HTTPS, security headers, secure cookies, and an application-level permission model. This section makes those signals easier for buyers to read.</p>
            </div>
            <div class="tsa-home-assurance-grid">
                <?php foreach ($assuranceCards as $item): ?>
                    <article class="tsa-card tsa-assurance-item">
                        <strong><?= htmlspecialchars($item['value']) ?></strong>
                        <span><?= htmlspecialchars($item['title']) ?></span>
                        <small><?= htmlspecialchars($item['text']) ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section tsa-home-faq-section" id="faq">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Common Questions</div>
                <h2>Answers buyers usually need before they start</h2>
                <p>Strong landing pages reduce hesitation. These answers reinforce fit, access, and day-to-day product use without forcing visitors into a separate support step.</p>
            </div>
            <div class="tsa-home-faq-grid">
                <?php foreach ($faqItems as $item): ?>
                    <article class="tsa-card tsa-home-faq-card">
                        <h3><?= htmlspecialchars($item['question']) ?></h3>
                        <p><?= htmlspecialchars($item['answer']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section tsa-home-cta-section">
            <div class="tsa-cta-box">
                <div class="tsa-eyebrow"><span class="dot"></span>TSA Legacy</div>
                <h2>Start your business operations on one system</h2>
                <p>Move billing, inventory, records, and reporting into one structured workspace built for practical day-to-day business use.</p>
                <div class="tsa-hero-actions">
                    <a href="<?= APP_URL ?>/signup" class="tsa-btn tsa-btn-primary">Start Free Trial</a>
                    <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-secondary">Instant Demo Access</a>
                </div>
            </div>
        </section>

        <section class="tsa-section">
            <div class="tsa-home-demo-grid">
                <div>
                    <div class="tsa-section-head tsa-home-demo-head">
                        <div class="tsa-section-kicker">Product Preview</div>
                        <h2>See how the product works before you start</h2>
                        <p>Instant demo access opens a guided product workspace so you can review the product flow, navigation, and business structure before creating your own account.</p>
                    </div>
                    <ul class="tsa-home-demo-list">
                        <li><i class="fas fa-check"></i><span>Open a guided product workspace immediately</span></li>
                        <li><i class="fas fa-check"></i><span>Review billing, inventory, and reporting flow</span></li>
                        <li><i class="fas fa-check"></i><span>Understand how teams work inside one system</span></li>
                    </ul>
                    <div class="tsa-hero-actions">
                        <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-primary">Instant Demo Access</a>
                    </div>
                </div>
                <aside class="tsa-card tsa-home-demo-preview">
                    <div class="tsa-home-screen-label"><i class="fas fa-laptop-code"></i>Product workspace preview</div>
                    <div class="tsa-home-shot">
                        <div class="tsa-home-shot-top">
                            <div>
                                <div class="tsa-home-shot-title">Demo workspace</div>
                                <div class="tsa-home-shot-sub">Sidebar, dashboard, and working records</div>
                            </div>
                            <div class="tsa-home-dots"><span></span><span></span><span></span></div>
                        </div>
                        <div class="tsa-home-shot-body">
                            <div class="tsa-home-shot-billing">
                                <div class="tsa-home-shot-panel">
                                    <h4>Navigation</h4>
                                    <div class="tsa-home-shot-list">
                                        <span><strong>Dashboard</strong><span>Overview</span></span>
                                        <span><strong>Sales</strong><span>Billing flow</span></span>
                                        <span><strong>Reports</strong><span>Review layer</span></span>
                                    </div>
                                </div>
                                <div class="tsa-home-shot-panel">
                                    <h4>Workspace data</h4>
                                    <div class="tsa-home-shot-list">
                                        <span><strong>Invoices</strong><span>Available</span></span>
                                        <span><strong>Products</strong><span>Catalog loaded</span></span>
                                        <span><strong>Reports</strong><span>Ready to review</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="tsa-section tsa-section-alt">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Pricing Preview</div>
                <h2>Choose the right starting tier, then grow from there</h2>
                <p>Pricing should feel clear and operationally sensible. The preview below gives buyers a quick read before they move into full plan detail.</p>
            </div>
            <?php if (!empty($plans ?? [])): ?>
                <div class="tsa-home-pricing-preview">
                    <?php foreach (array_slice($plans, 0, 3) as $plan): ?>
                        <?php
                        $effectivePrice = SaaSBillingHelper::effectivePlanPrice($plan);
                        $limits = SaaSBillingHelper::planLimitsSummary($plan);
                        $billing = strtolower((string)($plan['billing_type'] ?? 'monthly'));
                        $featuresPreview = array_slice(SaaSBillingHelper::extractPlanFeatures($plan, 4, 0)['enabled'], 0, 4);
                        ?>
                        <article class="tsa-card tsa-home-price-card">
                            <h3><?= e($plan['name'] ?? 'Plan') ?></h3>
                            <span class="tsa-home-price-value">Rs <?= number_format($effectivePrice, 0) ?><small>/<?= e($billing) ?></small></span>
                            <p><?= !empty($plan['description']) ? e($plan['description']) : 'Business operations plan with structured workspace access.' ?></p>
                            <ul class="tsa-home-price-list">
                                <li><i class="fas fa-check"></i><span><?= e($limits['users_label']) ?></span></li>
                                <li><i class="fas fa-check"></i><span><?= e($limits['products_label']) ?></span></li>
                                <?php foreach ($featuresPreview as $item): ?>
                                    <li><i class="fas fa-check"></i><span><?= e($item) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="tsa-card tsa-home-empty-pricing">
                    <h3>Pricing details are available on the pricing page.</h3>
                    <p>Use the pricing page to review the latest plans and workspace limits.</p>
                </div>
            <?php endif; ?>
            <div class="tsa-hero-actions">
                <a href="<?= APP_URL ?>/pricing" class="tsa-btn tsa-btn-secondary">View Pricing</a>
            </div>
        </section>

    </div>
</main>

<?php tsa_render_public_footer(['show_guides' => true]); ?>
<script nonce="<?= $nonce ?>">
<?= tsa_brand_script() ?>
</script>
</body>
</html>
