<?php $pageTitle = 'Reports'; ?>
<style>
    .reports-grid .report-tile {
        border: 1px solid var(--border-color);
        background:
            linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,255,0.96));
    }
    .reports-grid .report-tile h6 {
        color: var(--text-primary);
        font-size: 0.98rem;
        margin-bottom: 0.45rem;
    }
    .reports-grid .report-tile small {
        display: block;
        color: var(--text-muted);
        line-height: 1.55;
    }
    .reports-grid .report-tile-icon {
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.04);
    }
    [data-theme="dark"] .reports-grid .report-tile {
        background:
            linear-gradient(180deg, rgba(24,30,43,0.96), rgba(19,24,35,0.98));
        border-color: var(--border-color);
        box-shadow: 0 18px 34px rgba(0, 0, 0, 0.28);
    }
    [data-theme="dark"] .reports-grid .report-tile h6 {
        color: var(--text-primary);
    }
    [data-theme="dark"] .reports-grid .report-tile small {
        color: var(--text-secondary) !important;
    }
    [data-theme="dark"] .reports-grid .report-tile-icon {
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
    }
    [data-theme="dark"] .reports-grid .bg-dark.bg-opacity-10 {
        background-color: rgba(255,255,255,0.08) !important;
    }
    [data-theme="dark"] .reports-grid .text-dark {
        color: #dbe7ff !important;
    }
    @media (max-width: 767.98px) {
        .reports-grid .report-tile .card-body {
            text-align: left !important;
            padding: 1rem !important;
        }
        .reports-grid .report-tile-icon {
            width: 52px;
            height: 52px;
            margin-bottom: 0.85rem;
        }
    }
</style>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Reports</li></ol></nav></div>
<div class="row g-3 reports-grid">
    <?php
    $reports = [
        ['sales', 'chart-line', 'success', 'Sales Report', 'View all sales with date & customer filters'],
        ['purchases', 'cart-shopping', 'primary', 'Purchase Report', 'View all purchases with date & supplier filters'],
        ['tax_summary', 'file-invoice-dollar', 'success', 'GST / Tax Summary', 'Output GST, input GST, non-GST turnover, and net tax payable'],
        ['stock', 'boxes-stacked', 'info', 'Stock Report', !empty($hasWarehouseFeature) ? 'Current stock levels with warehouse filters' : 'Current stock levels for all products'],
        ['warehouse_transfers', 'right-left', 'primary', 'Warehouse Transfers', !empty($hasWarehouseFeature) ? 'Movement requests, approvals, and quantity flow by warehouse' : 'Requires multi-warehouse feature'],
        ['payroll_finance', 'wallet', 'dark', 'Payroll Finance', 'Approved payroll runs, payouts, and finance journal trace'],
        ['profit', 'coins', 'warning', 'Profit & Loss', 'Revenue, cost, and profit analysis'],
        ['customer_dues', 'user-clock', 'danger', 'Customer Dues', 'Outstanding amounts from customers'],
        ['supplier_dues', 'truck-clock', 'secondary', 'Supplier Dues', 'Outstanding amounts to suppliers'],
    ];
    foreach ($reports as $r): ?>
    <div class="col-md-6 col-xl-4">
        <a href="<?= APP_URL ?>/index.php?page=reports&action=<?= $r[0] ?>" class="text-decoration-none report-tile-link">
            <div class="card h-100 report-card-hover report-tile">
                <div class="card-body text-center py-4">
                    <div class="mb-3"><span class="rounded-circle bg-<?= $r[2] ?> bg-opacity-10 d-inline-flex align-items-center justify-content-center report-tile-icon"><i class="fas fa-<?= $r[1] ?> fa-lg text-<?= $r[2] ?>"></i></span></div>
                    <h6><?= $r[3] ?></h6>
                    <small class="text-muted"><?= $r[4] ?></small>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
