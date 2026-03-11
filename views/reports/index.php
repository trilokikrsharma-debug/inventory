<?php $pageTitle = 'Reports'; ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Reports</li></ol></nav></div>
<div class="row g-3">
    <?php
    $reports = [
        ['sales', 'chart-line', 'success', 'Sales Report', 'View all sales with date & customer filters'],
        ['purchases', 'cart-shopping', 'primary', 'Purchase Report', 'View all purchases with date & supplier filters'],
        ['stock', 'boxes-stacked', 'info', 'Stock Report', 'Current stock levels for all products'],
        ['profit', 'coins', 'warning', 'Profit & Loss', 'Revenue, cost, and profit analysis'],
        ['customer_dues', 'user-clock', 'danger', 'Customer Dues', 'Outstanding amounts from customers'],
        ['supplier_dues', 'truck-clock', 'secondary', 'Supplier Dues', 'Outstanding amounts to suppliers'],
    ];
    foreach ($reports as $r): ?>
    <div class="col-md-4">
        <a href="<?= APP_URL ?>/index.php?page=reports&action=<?= $r[0] ?>" class="text-decoration-none">
            <div class="card h-100 report-card-hover" style="cursor:pointer;">
                <div class="card-body text-center py-4">
                    <div class="mb-3"><span class="rounded-circle bg-<?= $r[2] ?> bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width:60px;height:60px;"><i class="fas fa-<?= $r[1] ?> fa-lg text-<?= $r[2] ?>"></i></span></div>
                    <h6><?= $r[3] ?></h6>
                    <small class="text-muted"><?= $r[4] ?></small>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
