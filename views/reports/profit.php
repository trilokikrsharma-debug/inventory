<?php $pageTitle = 'Profit & Loss'; ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">Profit & Loss</li></ol></nav>
    <form method="POST" action="<?= APP_URL ?>/index.php?page=reports&action=queue_export">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
        <input type="hidden" name="report_type" value="profit">
        <input type="hidden" name="from_date" value="<?= Helper::escape($fromDate) ?>">
        <input type="hidden" name="to_date" value="<?= Helper::escape($toDate) ?>">
        <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-file-arrow-down me-1"></i>Queue CSV</button>
    </form>
</div>
<div class="card mb-3"><div class="card-body py-2">
    <form class="d-flex gap-2 align-items-end" method="GET"><input type="hidden" name="page" value="reports"><input type="hidden" name="action" value="profit">
        <div><label class="form-label small mb-0">From</label><input type="date" name="from_date" class="form-control form-control-sm" value="<?= Helper::escape($fromDate) ?>"></div>
        <div><label class="form-label small mb-0">To</label><input type="date" name="to_date" class="form-control form-control-sm" value="<?= Helper::escape($toDate) ?>"></div>
        <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
    </form>
</div></div>
<?php
$totalRevenue = $profitData['total_revenue'] ?? $profitData['total_sales'] ?? 0;
$totalCost = $profitData['total_cost'] ?? 0;
$grossProfit = $profitData['gross_profit'] ?? 0;
$profitPct = $totalRevenue > 0 ? ($grossProfit / $totalRevenue * 100) : 0;
?>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card stat-success"><div class="stat-value"><?= Helper::formatCurrency($totalRevenue) ?></div><div class="stat-label">Revenue</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-danger"><div class="stat-value"><?= Helper::formatCurrency($totalCost) ?></div><div class="stat-label">Cost of Goods</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-primary"><div class="stat-value"><?= Helper::formatCurrency($grossProfit) ?></div><div class="stat-label">Gross Profit</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-warning"><div class="stat-value"><?= number_format($profitPct, 1) ?>%</div><div class="stat-label">Profit Margin</div></div></div>
</div>
<div class="card">
    <div class="card-body">
        <h6 class="mb-3">Breakdown</h6>
        <table class="table">
            <tr><td>Total Sales Revenue</td><td class="text-end fw-bold text-success"><?= Helper::formatCurrency($totalRevenue) ?></td></tr>
            <tr><td>Cost of Goods Sold</td><td class="text-end fw-bold text-danger">- <?= Helper::formatCurrency($totalCost) ?></td></tr>
            <tr class="fs-5 fw-bold"><td>Gross Profit</td><td class="text-end text-primary"><?= Helper::formatCurrency($grossProfit) ?></td></tr>
        </table>
    </div>
</div>
