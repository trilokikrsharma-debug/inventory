<?php
require_once __DIR__ . '/_partials/brand.php';

$assets = tsa_brand_assets();
$homeUrl = rtrim(APP_URL, '/') . '/';
$faviconUrl = $assets['favicon'];
$socialImageUrl = $assets['og'];
$nonce = htmlspecialchars($GLOBALS['csp_nonce'] ?? ($cspNonce ?? ''), ENT_QUOTES);

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
    ],
    [
        'label' => 'Inventory screen',
        'title' => 'Catalog control with live stock signals',
        'text' => 'Products, SKU details, category structure, and low-stock visibility stay accessible in one operational list.',
        'theme' => 'inventory',
    ],
    [
        'label' => 'Reports screen',
        'title' => 'Reports organized around daily business review',
        'text' => 'Sales, purchases, stock, dues, and finance visibility are grouped into one reporting layer for owners and managers.',
        'theme' => 'reports',
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
            'text' => 'Yes. Instant demo access opens a sample workspace so you can review the product flow before creating your own account.',
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Billing & Inventory Management Software for Indian Businesses | TSA Legacy</title>
    <meta name="description" content="TSA Legacy brings GST billing, inventory, customers, suppliers, and reports into one cloud-based business system for Indian SMEs.">
    <meta property="og:title" content="GST Billing & Inventory Management Software for Indian Businesses | TSA Legacy">
    <meta property="og:description" content="Run billing, inventory, and daily business operations from one structured cloud system.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($homeUrl, ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <meta property="og:image:alt" content="TSA Legacy">
    <meta property="og:site_name" content="TSA Legacy">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="GST Billing & Inventory Management Software for Indian Businesses | TSA Legacy">
    <meta name="twitter:description" content="Run billing, inventory, and daily business operations from one structured cloud system.">
    <meta name="twitter:image" content="<?= htmlspecialchars($socialImageUrl, ENT_QUOTES) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($faviconUrl, ENT_QUOTES) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($assets['brand_css'], ENT_QUOTES) ?>">
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
                'name' => 'TSA Legacy Ventures',
                'url' => $homeUrl,
            ],
            'mainEntity' => $faqSchema,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <style>
        .tsa-home-hero{padding:56px 0 32px}
        .tsa-home-hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:40px;align-items:center}
        .tsa-home-hero-copy{position:relative;z-index:1}
        .tsa-home-hero-copy::before{
            content:"";position:absolute;left:-48px;top:-34px;width:280px;height:280px;border-radius:50%;
            background:radial-gradient(circle, rgba(22,56,95,.16), rgba(22,56,95,0) 72%);filter:blur(4px);z-index:-1;pointer-events:none
        }
        .tsa-home-hero h1{margin:18px 0 14px;font-size:clamp(2.7rem,6vw,5rem);line-height:.95;letter-spacing:-.05em;color:var(--tsa-ink)}
        .tsa-home-hero h1{animation:tsaHeroRise .7s ease both}
        .tsa-home-hero h1 .tsa-serif{display:block;font-family:"Instrument Serif",serif;font-weight:400;font-style:italic;color:var(--tsa-accent)}
        .tsa-home-hero p{max-width:700px;font-size:1.05rem;line-height:1.85;color:var(--tsa-muted)}
        .tsa-home-note{margin-top:14px;font-size:.84rem;color:var(--tsa-muted)}
        .tsa-home-trustline{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
        .tsa-home-trustline span{
            display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:999px;
            border:1px solid rgba(20,33,58,.08);background:rgba(255,255,255,.76);font-size:.78rem;font-weight:800;color:var(--tsa-ink)
        }
        .tsa-home-hero-actions{display:flex;flex-wrap:wrap;gap:14px;margin-top:22px}
        .tsa-home-cta-primary{position:relative;box-shadow:0 24px 56px rgba(16,38,63,.28), 0 0 0 1px rgba(255,255,255,.18) inset}
        .tsa-home-cta-primary::after{
            content:"";position:absolute;inset:auto 18px -10px 18px;height:18px;border-radius:999px;
            background:radial-gradient(circle, rgba(22,56,95,.34), rgba(22,56,95,0) 72%);filter:blur(10px);z-index:-1
        }
        .tsa-home-proof-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:22px}
        .tsa-home-proof-chip{
            padding:16px 18px;border-radius:20px;border:1px solid rgba(20,33,58,.08);background:rgba(255,255,255,.78);
            box-shadow:0 20px 44px rgba(20,33,58,.08)
        }
        .tsa-home-proof-chip strong{display:block;font-size:.94rem;color:var(--tsa-ink);margin-bottom:6px}
        .tsa-home-proof-chip span{display:block;font-size:.82rem;line-height:1.65;color:var(--tsa-muted)}
        .tsa-home-trustbar{padding:20px 0 0}
        .tsa-home-badges{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
        .tsa-home-badge{display:flex;align-items:center;gap:12px;padding:16px 18px;border-radius:18px;border:1px solid var(--tsa-line);background:rgba(255,255,255,.82);box-shadow:var(--tsa-shadow-soft)}
        .tsa-home-badge i{color:var(--tsa-primary)}
        .tsa-home-badge span{font-size:.86rem;font-weight:800;color:var(--tsa-ink)}
        .tsa-home-preview{
            display:flex;flex-direction:column;gap:24px;
            position:relative;padding:36px;border-radius:32px;max-width:580px;margin:0 auto;z-index:1;background:
                radial-gradient(circle at top right, rgba(22,56,95,.22), transparent 30%),
                radial-gradient(circle at bottom left, rgba(154,119,66,.18), transparent 24%),
                linear-gradient(160deg,#ffffff 0%, #f8f4ec 58%, #edf2f8 100%);
            border:1px solid rgba(20,33,58,.08);box-shadow:0 34px 90px rgba(20,33,58,.16)
        }
        .tsa-home-preview::before{
            content:"";position:absolute;inset:10px -8px auto auto;width:200px;height:200px;border-radius:999px;
            background:radial-gradient(circle, rgba(22,56,95,.22), transparent 72%);filter:blur(10px);pointer-events:none
        }
        .tsa-home-preview::after{
            content:"";position:absolute;left:12px;bottom:6px;width:180px;height:180px;border-radius:999px;
            background:radial-gradient(circle, rgba(154,119,66,.20), transparent 72%);filter:blur(14px);pointer-events:none
        }
        .tsa-home-window{
            order:2;overflow:hidden;border-radius:24px;border:1px solid rgba(20,33,58,.08);background:#fff;
            transform:perspective(2000px) rotateY(-4deg) rotateX(2deg) translateY(0);transform-origin:center;box-shadow:0 36px 88px rgba(20,33,58,.22);
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
        .tsa-home-main-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:14px}
        .tsa-home-panel{padding:18px;border-radius:20px;border:1px solid rgba(20,33,58,.08);background:#fff;box-shadow:var(--tsa-shadow-soft)}
        .tsa-home-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        .tsa-home-metric{padding:14px;border-radius:16px;background:#edf2f7;border:1px solid rgba(22,56,95,.10)}
        .tsa-home-metric strong{display:block;font-size:1.08rem;line-height:1;color:var(--tsa-ink);margin-bottom:6px}
        .tsa-home-metric span{display:block;font-size:.76rem;color:var(--tsa-muted)}
        .tsa-home-table{display:grid;gap:10px;margin-top:12px}
        .tsa-home-table-row{display:grid;grid-template-columns:1.2fr .9fr .7fr;gap:10px;padding:11px 12px;border-radius:14px;background:#fbfcfd;border:1px solid rgba(20,33,58,.06);font-size:.78rem;color:var(--tsa-muted-strong)}
        .tsa-home-table-row strong{color:var(--tsa-ink)}
        .tsa-home-chart{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));align-items:end;gap:10px;height:170px;padding-top:12px}
        .tsa-home-bar{display:flex;align-items:flex-end;justify-content:center;height:100%}
        .tsa-home-bar span{display:block;width:100%;border-radius:14px 14px 6px 6px;background:linear-gradient(180deg,#315882 0%, #16385f 100%);box-shadow:inset 0 -10px 18px rgba(255,255,255,.12)}
        .tsa-home-preview-card{
            position:static;padding:20px;border-radius:20px;border:1px solid rgba(20,33,58,.08);
            background:rgba(255,255,255,.9);backdrop-filter:blur(12px);box-shadow:0 14px 44px rgba(20,33,58,.10);
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
        .tsa-home-signal{padding:14px;border-radius:18px;border:1px solid rgba(20,33,58,.08);background:rgba(255,255,255,.82)}
        .tsa-home-signal strong{display:block;font-size:.95rem;color:var(--tsa-ink);margin-bottom:4px}
        .tsa-home-signal span{display:block;font-size:.8rem;line-height:1.6;color:var(--tsa-muted)}
        .tsa-home-overview-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:18px}
        .tsa-home-screen-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .tsa-home-screen-card{padding:18px}
        .tsa-home-screen-label{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:var(--tsa-primary-soft);font-size:.74rem;font-weight:800;color:var(--tsa-primary);margin-bottom:14px}
        .tsa-home-shot{
            padding:12px;border-radius:20px;background:linear-gradient(180deg,#fff 0%, #f8fafb 100%);border:1px solid rgba(20,33,58,.08);
            box-shadow:0 24px 52px rgba(20,33,58,.10);margin-bottom:14px
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
        .tsa-home-report-stack{display:grid;gap:10px}
        .tsa-home-report-tile{padding:14px;border-radius:14px;background:#fff;border:1px solid rgba(20,33,58,.08)}
        .tsa-home-report-tile strong{display:block;font-size:.8rem;color:var(--tsa-ink);margin-bottom:4px}
        .tsa-home-report-tile span{display:block;font-size:.72rem;line-height:1.55;color:var(--tsa-muted)}
        .tsa-home-growth-shell{
            position:relative;overflow:hidden;padding:34px;border-radius:32px;border:1px solid rgba(20,33,58,.08);
            background:
                radial-gradient(circle at top left, rgba(22,56,95,.14), transparent 28%),
                radial-gradient(circle at bottom right, rgba(154,119,66,.14), transparent 22%),
                linear-gradient(160deg, rgba(255,255,255,.92) 0%, rgba(247,242,234,.96) 100%);
            box-shadow:0 30px 72px rgba(20,33,58,.10)
        }
        .tsa-home-growth-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:26px}
        .tsa-home-growth-meta{display:flex;flex-wrap:wrap;gap:10px}
        .tsa-home-growth-pill{
            display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;background:rgba(255,255,255,.8);
            border:1px solid rgba(20,33,58,.08);font-size:.78rem;font-weight:800;color:var(--tsa-ink)
        }
        .tsa-home-growth-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:18px}
        .tsa-home-proof-panel{padding:26px}
        .tsa-home-proof-panel h3{margin:0 0 10px;font-size:1.08rem;color:var(--tsa-ink)}
        .tsa-home-proof-panel p{margin:0;color:var(--tsa-muted);font-size:.95rem;line-height:1.8}
        .tsa-home-proof-listing{display:grid;gap:14px;margin-top:18px}
        .tsa-home-proof-row{
            display:grid;grid-template-columns:44px minmax(0,1fr);gap:12px;align-items:start;padding:16px 0;border-top:1px solid rgba(20,33,58,.08)
        }
        .tsa-home-proof-row:first-child{border-top:none;padding-top:0}
        .tsa-home-proof-row .tsa-icon-chip{margin-bottom:0}
        .tsa-home-testimonial-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .tsa-home-testimonial-card{padding:22px}
        .tsa-home-testimonial-card blockquote{
            margin:0;color:var(--tsa-ink);font-size:.96rem;line-height:1.8;font-weight:600
        }
        .tsa-home-testimonial-card small{
            display:block;margin-top:14px;color:var(--tsa-muted);font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase
        }
        .tsa-home-snapshot-grid{display:grid;grid-template-columns:1.15fr .85fr .85fr;gap:18px}
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
        .tsa-home-use-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .tsa-home-use-card{padding:24px}
        .tsa-home-use-card h3{margin:0 0 10px;font-size:1.08rem;color:var(--tsa-ink)}
        .tsa-home-use-card p{margin:0;color:var(--tsa-muted);font-size:.94rem;line-height:1.8}
        .tsa-home-demo-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:22px;align-items:stretch}
        .tsa-home-demo-list{list-style:none;margin:16px 0 0;padding:0;display:grid;gap:10px}
        .tsa-home-demo-list li{display:flex;align-items:flex-start;gap:10px;color:var(--tsa-ink);font-size:.9rem}
        .tsa-home-demo-list i{color:var(--tsa-primary);margin-top:4px}
        .tsa-home-demo-preview{padding:18px}
        .tsa-home-demo-preview .tsa-home-shot{margin-bottom:0}
        .tsa-home-pricing-preview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
        .tsa-home-price-card{padding:24px}
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
        }
    </style>
</head>
<body class="tsa-public">
<?php tsa_render_public_nav([
    'active_href' => APP_URL . '/',
    'links' => [
        ['href' => APP_URL . '/', 'label' => 'Home'],
        ['href' => APP_URL . '/pricing', 'label' => 'Pricing'],
        ['href' => APP_URL . '/blog', 'label' => 'Guides'],
    ],
    'secondary_label' => 'Instant Demo Access',
    'secondary_href' => APP_URL . '/demo',
    'primary_label' => 'Start Free Trial',
    'primary_href' => APP_URL . '/signup',
]); ?>

<main class="tsa-page">
    <div class="tsa-container">
        <section class="tsa-home-hero">
            <div class="tsa-home-hero-grid">
                <div class="tsa-home-hero-copy">
                    <div class="tsa-eyebrow"><span class="dot"></span>Secure Business Operations Software</div>
                    <h1 style="font-size: clamp(2.8rem, 6vw, 4.5rem); letter-spacing: -0.04em;">Take back control of your business. <br><span class="tsa-serif" style="color: var(--tsa-primary); margin-top: 8px;">Run operations from one structured system.</span></h1>
                    <p style="font-size: 1.15rem; max-width: 650px; margin-top: 18px;">TSA Legacy brings your GST billing, inventory tracking, customer histories, and supplier pipelines into a single, reliable cloud workspace. Built exclusively for Indian SMEs to stop tool friction.</p>
                    <div class="tsa-home-hero-actions">
                        <a href="<?= APP_URL ?>/signup" class="tsa-btn tsa-btn-primary tsa-home-cta-primary">Start Free Trial</a>
                        <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-secondary">Instant Demo Access</a>
                    </div>
                    <div class="tsa-home-note">No credit card required. Setup is fast, and instant demo access opens a sample workspace before signup.</div>
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
                </div>

                <aside class="tsa-home-preview">
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
                    <div class="tsa-home-window">
                        <div class="tsa-home-window-top">
                            <div class="tsa-home-dots"><span></span><span></span><span></span></div>
                            <div class="tsa-home-window-label">Sample Dashboard</div>
                            <div class="tsa-home-pill"><i class="fas fa-shield-halved"></i> GST-ready</div>
                        </div>
                        <div class="tsa-home-window-body">
                            <div class="tsa-home-sidebar">
                                <div class="tsa-home-sidebar-brand"><i class="fas fa-chart-pie"></i></div>
                                <div class="tsa-home-sidebar-list">
                                    <div class="tsa-home-sidebar-item is-active"><i class="fas fa-grid-2"></i><span>Dashboard</span></div>
                                    <div class="tsa-home-sidebar-item"><i class="fas fa-file-invoice"></i><span>Sales</span></div>
                                    <div class="tsa-home-sidebar-item"><i class="fas fa-boxes-stacked"></i><span>Products</span></div>
                                    <div class="tsa-home-sidebar-item"><i class="fas fa-chart-line"></i><span>Reports</span></div>
                                </div>
                            </div>
                            <div class="tsa-home-main">
                                <div class="tsa-home-headbar">
                                    <div>
                                        <h3>Business dashboard</h3>
                                        <small>Daily billing, stock, dues, and reporting visibility</small>
                                    </div>
                                    <div class="tsa-home-userpill"><i class="fas fa-circle-check"></i> Workspace active</div>
                                </div>
                                <div class="tsa-home-metrics">
                                    <div class="tsa-home-metric"><strong>Rs 84,200</strong><span>Today's sales</span></div>
                                    <div class="tsa-home-metric"><strong>Rs 18,450</strong><span>Customer dues</span></div>
                                    <div class="tsa-home-metric"><strong>12 items</strong><span>Low-stock alerts</span></div>
                                </div>
                                <div class="tsa-home-main-grid">
                                    <div class="tsa-home-panel">
                                        <h3>Recent invoices</h3>
                                        <div class="tsa-home-table">
                                            <div class="tsa-home-table-row"><strong>INV-2031</strong><span>Om Traders</span><span>Paid</span></div>
                                            <div class="tsa-home-table-row"><strong>INV-2030</strong><span>Nila Stores</span><span>Due</span></div>
                                            <div class="tsa-home-table-row"><strong>INV-2029</strong><span>Patel Electricals</span><span>Paid</span></div>
                                        </div>
                                    </div>
                                    <div class="tsa-home-panel">
                                        <h3>Sales vs purchase</h3>
                                        <div class="tsa-home-chart">
                                            <div class="tsa-home-bar"><span style="height:58%"></span></div>
                                            <div class="tsa-home-bar"><span style="height:74%"></span></div>
                                            <div class="tsa-home-bar"><span style="height:67%"></span></div>
                                            <div class="tsa-home-bar"><span style="height:88%"></span></div>
                                            <div class="tsa-home-bar"><span style="height:80%"></span></div>
                                            <div class="tsa-home-bar"><span style="height:96%"></span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tsa-home-panel">
                                    <h3>Operational alerts</h3>
                                    <div class="tsa-home-table-mini">
                                        <div class="tsa-home-mini-row"><span>Low stock</span><strong>Engine Oil 1L</strong></div>
                                        <div class="tsa-home-mini-row"><span>Supplier due</span><strong>Rs 42,300</strong></div>
                                        <div class="tsa-home-mini-row"><span>Pending quotations</span><strong>4 open</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tsa-home-preview-signals">
                        <div class="tsa-home-signal"><strong>Counter-ready</strong><span>Fast workflows for daily billing desks.</span></div>
                        <div class="tsa-home-signal"><strong>Cloud-based</strong><span>Use across devices and business locations.</span></div>
                        <div class="tsa-home-signal"><strong>Operational</strong><span>Billing, stock, and records stay connected.</span></div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="tsa-home-trustbar">
            <div class="tsa-home-badges">
                <?php foreach ($trustBadges as $badge): ?>
                    <div class="tsa-home-badge">
                        <i class="fas <?= htmlspecialchars($badge['icon']) ?>"></i>
                        <span><?= htmlspecialchars($badge['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section">
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

        <section class="tsa-section" style="padding-top:0">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Inside The System</div>
                <h2>See how operations look inside the system</h2>
                <p>The interface is designed around practical working screens: billing flow, inventory control, and reporting visibility.</p>
            </div>
            <div class="tsa-home-screen-grid">
                <?php foreach ($productScreens as $screen): ?>
                    <article class="tsa-card tsa-home-screen-card">
                        <div class="tsa-home-screen-label"><i class="fas fa-desktop"></i><?= htmlspecialchars($screen['label']) ?></div>
                        <div class="tsa-home-shot">
                            <div class="tsa-home-shot-top">
                                <div>
                                    <div class="tsa-home-shot-title"><?= htmlspecialchars($screen['title']) ?></div>
                                    <div class="tsa-home-shot-sub">Realistic workspace view</div>
                                </div>
                                <div class="tsa-home-dots"><span></span><span></span><span></span></div>
                            </div>
                            <div class="tsa-home-shot-body">
                                <?php if ($screen['theme'] === 'billing'): ?>
                                    <div class="tsa-home-shot-billing">
                                        <div class="tsa-home-shot-panel">
                                            <h4>Sales list</h4>
                                            <div class="tsa-home-shot-band"><strong>Filters</strong><span>Invoice · Customer · Date</span></div>
                                            <div class="tsa-home-shot-table tsa-home-shot-billing-table">
                                                <div class="tsa-home-shot-table-head"><span>Invoice</span><span>Customer</span><span>Total</span><span>Status</span></div>
                                                <div class="tsa-home-shot-table-row"><strong>INV-2031</strong><span>Om Traders</span><span>Rs 12,450</span><span class="tsa-home-shot-status">Paid</span></div>
                                                <div class="tsa-home-shot-table-row"><strong>INV-2030</strong><span>Nila Stores</span><span>Rs 4,820</span><span class="tsa-home-shot-status is-due">Due</span></div>
                                                <div class="tsa-home-shot-table-row"><strong>INV-2029</strong><span>Patel Electricals</span><span>Rs 9,640</span><span class="tsa-home-shot-status">Paid</span></div>
                                            </div>
                                        </div>
                                        <div class="tsa-home-shot-panel">
                                            <h4>Payment summary</h4>
                                            <div class="tsa-home-shot-band"><strong>Collections</strong><span>Paid and due totals</span></div>
                                            <div class="tsa-home-shot-list">
                                                <span><strong>Total</strong><span>Rs 84,200</span></span>
                                                <span><strong>Paid</strong><span>Rs 46,000</span></span>
                                                <span><strong>Due</strong><span>Rs 38,200</span></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($screen['theme'] === 'inventory'): ?>
                                    <div class="tsa-home-shot-inventory">
                                        <div class="tsa-home-shot-band"><strong>Product list</strong><span>Search · Category · Stock status</span></div>
                                        <div class="tsa-home-shot-table tsa-home-shot-inventory-table">
                                            <div class="tsa-home-shot-table-head"><span>Product</span><span>SKU</span><span>Category</span><span>Stock</span></div>
                                            <div class="tsa-home-shot-table-row"><strong>Brake Pad Set</strong><span>BP-204</span><span>Brakes</span><span class="ok">128 pcs</span></div>
                                            <div class="tsa-home-shot-table-row"><strong>Engine Oil 1L</strong><span>EO-1L</span><span>Lubricants</span><span class="low">9 pcs</span></div>
                                            <div class="tsa-home-shot-table-row"><strong>Battery Cable</strong><span>BC-111</span><span>Electrical</span><span class="ok">64 pcs</span></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="tsa-home-shot-reports">
                                        <div class="tsa-home-report-chart">
                                            <strong>Reports dashboard</strong>
                                            <span class="tsa-home-shot-sub">Sales, profit, dues, and stock review</span>
                                            <div class="tsa-home-report-chart-bars">
                                                <span style="height:52%"></span>
                                                <span style="height:70%"></span>
                                                <span style="height:62%"></span>
                                                <span style="height:84%"></span>
                                                <span style="height:96%"></span>
                                            </div>
                                        </div>
                                        <div class="tsa-home-report-stack">
                                            <div class="tsa-home-report-tile"><strong>Sales Report</strong><span>View all sales with date and customer filters.</span></div>
                                            <div class="tsa-home-report-tile"><strong>Stock Report</strong><span>Current stock levels and exceptions by product.</span></div>
                                            <div class="tsa-home-report-tile"><strong>Customer Dues</strong><span>Outstanding amounts ready for follow-up.</span></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p><?= htmlspecialchars($screen['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section tsa-section-alt">
            <div class="tsa-home-growth-shell">
                <div class="tsa-home-growth-head">
                <div class="tsa-section-head" style="margin-bottom:0">
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
                        <p>The homepage now shows the product as a working business system: specific screens, live-looking data states, and use cases tied to how businesses actually run.</p>
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

        <section class="tsa-section" style="padding-top:0">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Working Feedback</div>
                <h2>Short signals of product trust</h2>
                <p>Small teams do not need exaggerated claims. They need to feel that the system is useful in billing, stock handling, and daily review.</p>
            </div>
            <div class="tsa-home-testimonial-grid">
                <?php foreach ($microTestimonials as $item): ?>
                    <article class="tsa-card tsa-home-testimonial-card">
                        <blockquote>“<?= htmlspecialchars($item['quote']) ?>”</blockquote>
                        <small>Early product feedback</small>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="tsa-section" style="padding-top:0">
            <div class="tsa-section-head">
                <div class="tsa-section-kicker">Example System Output</div>
                <h2>Outputs the team can actually use</h2>
                <p>A business system earns trust when its invoice records, stock views, and reports look ready for real work, not just for a homepage illustration.</p>
            </div>
            <div class="tsa-home-snapshot-grid">
                <article class="tsa-card tsa-home-snapshot-card">
                    <h3>Sample invoice</h3>
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
                    <h3>Sample report</h3>
                    <p>Organized for quick monthly review without losing operating detail.</p>
                    <div class="tsa-home-output-sheet">
                        <div class="tsa-home-output-header">
                            <strong>Sales Report</strong>
                            <span>Monthly view</span>
                        </div>
                        <div class="tsa-home-report-mini-bars">
                            <span style="height:46%"></span>
                            <span style="height:68%"></span>
                            <span style="height:72%"></span>
                            <span style="height:94%"></span>
                        </div>
                        <div class="tsa-home-report-mini-list">
                            <div><span>Total sales</span><strong>Rs 4.82L</strong></div>
                            <div><span>Open dues</span><strong>Rs 38,200</strong></div>
                        </div>
                    </div>
                </article>
                <article class="tsa-card tsa-home-snapshot-card">
                    <h3>Sample stock table</h3>
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
            <div style="padding: 40px; background: #fbfaf7; border: 1px solid rgba(20,33,58,.08); border-radius: 20px; box-shadow: var(--tsa-shadow-soft);">
                <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                    <h2 style="font-size: 2.2rem; color: var(--tsa-ink); margin-bottom: 24px; font-weight: 800; letter-spacing: -0.03em;">Built by TSA Legacy Ventures</h2>
                    <p style="font-size: 1.1rem; color: var(--tsa-ink); line-height: 1.8; margin-bottom: 16px; font-weight: 600;">
                        We didn't build this to be just another software product. We built it because real businesses were struggling to find a system that actually worked for them.
                    </p>
                    <p style="font-size: 1rem; color: var(--tsa-muted); line-height: 1.8; margin-bottom: 16px;">
                        Hi, I'm <strong>Triloki</strong>, founder of TSA Legacy Ventures. When looking at how Indian SMEs operate, I realized most teams were forced to buy three different tools—or rely on clunky, bloated legacy software—just to run their daily billing and inventory.
                    </p>
                    <p style="font-size: 1rem; color: var(--tsa-muted); line-height: 1.8;">
                        TSA Legacy was created to solve that specific problem. Our mission is direct: provide a <strong>simple, structured, reliable</strong> cloud workspace where growing businesses can take back control of their workflows without the friction. We're a real team, building reliable tools for real work.
                    </p>
                </div>
            </div>
        </section>

        <section class="tsa-section" style="padding-top: 0;">
            <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; align-items: center; border: 1px solid rgba(20,33,58,.08); border-radius: 20px; padding: 40px; background: #fff; box-shadow: var(--tsa-shadow-soft);">
                <div>
                    <h3 style="font-size: 1.8rem; font-weight: 800; color: var(--tsa-ink); margin-bottom: 12px; letter-spacing: -0.03em;">Support &amp; Onboarding</h3>
                    <p style="color: var(--tsa-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">Software is only as good as the team supporting it. We don't leave you alone after signup.</p>
                    <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 14px;">
                        <li style="display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 600; color: var(--tsa-ink);">
                            <i class="fas fa-check-circle" style="color: var(--tsa-primary); font-size: 1.1rem;"></i> Direct email support within 24 hours
                        </li>
                        <li style="display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 600; color: var(--tsa-ink);">
                            <i class="fas fa-check-circle" style="color: var(--tsa-primary); font-size: 1.1rem;"></i> Guided onboarding help &amp; data setup
                        </li>
                        <li style="display: flex; align-items: center; gap: 12px; font-size: 0.95rem; font-weight: 600; color: var(--tsa-ink);">
                            <i class="fas fa-check-circle" style="color: var(--tsa-primary); font-size: 1.1rem;"></i> Human-written documentation &amp; operational guides
                        </li>
                    </ul>
                </div>
                <div style="background: #f4f7fb; padding: 30px; border-radius: 16px; text-align: center; border: 1px solid rgba(22,56,95,.06);">
                    <p style="font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--tsa-muted); margin-bottom: 12px;">Speak directly with our team</p>
                    <a href="mailto:support@tsalegacy.com" style="display: inline-block; font-size: 1.35rem; font-weight: 800; color: var(--tsa-primary); text-decoration: none; padding: 12px 20px; background: #fff; border-radius: 12px; border: 1px solid rgba(20,33,58,.08); box-shadow: 0 10px 20px rgba(22,56,95,.06);">support@tsalegacy.com</a>
                </div>
            </div>
        </section>

        <section class="tsa-section">
            <div class="tsa-home-demo-grid">
                <div>
                    <div class="tsa-section-head" style="margin-bottom:22px">
                        <div class="tsa-section-kicker">Product Preview</div>
                        <h2>See how the product works before you start</h2>
                        <p>Instant demo access signs you into a sample workspace so you can review the product flow, navigation, and business structure before creating your own account.</p>
                    </div>
                    <ul class="tsa-home-demo-list">
                        <li><i class="fas fa-check"></i><span>Open a sample workspace immediately</span></li>
                        <li><i class="fas fa-check"></i><span>Review billing, inventory, and reporting flow</span></li>
                        <li><i class="fas fa-check"></i><span>Understand how teams work inside one system</span></li>
                    </ul>
                    <div class="tsa-hero-actions">
                        <a href="<?= APP_URL ?>/demo" class="tsa-btn tsa-btn-primary">Instant Demo Access</a>
                    </div>
                </div>
                <aside class="tsa-card tsa-home-demo-preview">
                    <div class="tsa-home-screen-label"><i class="fas fa-laptop-code"></i>Sample workspace preview</div>
                    <div class="tsa-home-shot">
                        <div class="tsa-home-shot-top">
                            <div>
                                <div class="tsa-home-shot-title">Demo workspace</div>
                                <div class="tsa-home-shot-sub">Sidebar, dashboard, and sample records</div>
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
                                    <h4>Sample data</h4>
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
                <div class="tsa-card" style="text-align:center">
                    <h3>Pricing details are available on the pricing page.</h3>
                    <p>Use the pricing page to review the latest plans and workspace limits.</p>
                </div>
            <?php endif; ?>
            <div class="tsa-hero-actions">
                <a href="<?= APP_URL ?>/pricing" class="tsa-btn tsa-btn-secondary">View Pricing</a>
            </div>
        </section>

        <section class="tsa-section">
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
        </section>

        <section class="tsa-section" style="padding-top:12px">
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
    </div>
</main>

<?php tsa_render_public_footer(['show_guides' => true]); ?>
<script nonce="<?= $nonce ?>">
<?= tsa_brand_script() ?>
</script>
</body>
</html>
