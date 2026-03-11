<?php $pageTitle = 'Dashboard'; ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-success animate-fade-in-up">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($salesAll['total_amount'] ?? 0) ?></div>
            <div class="stat-label">Total Sales</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-primary animate-fade-in-up" style="animation-delay:0.1s">
            <div class="stat-icon"><i class="fas fa-cart-shopping"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($purchaseAll['total_amount'] ?? 0) ?></div>
            <div class="stat-label">Total Purchases</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-warning animate-fade-in-up" style="animation-delay:0.2s">
            <div class="stat-icon"><i class="fas fa-sun"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($salesToday['total_amount'] ?? 0) ?></div>
            <div class="stat-label">Today's Sales</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-info animate-fade-in-up" style="animation-delay:0.3s">
            <div class="stat-icon"><i class="fas fa-boxes-stacked"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($stockValue['total_value'] ?? 0) ?></div>
            <div class="stat-label">Stock Value</div>
        </div>
    </div>
</div>

<!-- Second Row - More Stats -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-danger animate-fade-in-up" style="animation-delay:0.1s">
            <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($customerDues ?? 0) ?></div>
            <div class="stat-label">Customer Dues</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-warning animate-fade-in-up" style="animation-delay:0.15s">
            <div class="stat-icon"><i class="fas fa-truck-clock"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($supplierDues ?? 0) ?></div>
            <div class="stat-label">Supplier Dues</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-success animate-fade-in-up" style="animation-delay:0.2s">
            <div class="stat-icon"><i class="fas fa-calendar"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($salesMonth['total_amount'] ?? 0) ?></div>
            <div class="stat-label">This Month Sales</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-primary animate-fade-in-up" style="animation-delay:0.25s">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($purchaseMonth['total_amount'] ?? 0) ?></div>
            <div class="stat-label">This Month Purchase</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-chart-area me-2 text-primary"></i>Monthly Sales vs Purchase (<?= date('Y') ?>)</h6>
            </div>
            <div class="card-body">
                <canvas id="salesPurchaseChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-trophy me-2 text-warning"></i>Top Selling Products</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Qty Sold</th></tr></thead>
                        <tbody>
                        <?php if (!empty($topProducts)): foreach ($topProducts as $tp): ?>
                        <tr>
                            <td><?= Helper::escape($tp['name']) ?></td>
                            <td class="text-end fw-bold"><?= Helper::formatQty($tp['total_qty']) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">No sales data</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sales & Low Stock -->
<div class="row g-3">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-receipt me-2 text-success"></i>Recent Sales</h6>
                <a href="<?= APP_URL ?>/index.php?page=sales" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (!empty($recentSales)): foreach ($recentSales as $s): ?>
                        <tr>
                            <td><a href="<?= APP_URL ?>/index.php?page=sales&action=view_sale&id=<?= $s['id'] ?>"><?= Helper::escape($s['invoice_number']) ?></a></td>
                            <td><?= Helper::escape($s['customer_name']) ?></td>
                            <td><?= Helper::formatDate($s['sale_date']) ?></td>
                            <td class="text-end fw-bold"><?= Helper::formatCurrency($s['grand_total']) ?></td>
                            <td><?= Helper::paymentBadge($s['payment_status']) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No recent sales</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Low Stock Alert</h6>
                <a href="<?= APP_URL ?>/index.php?page=reports&action=stock" class="btn btn-sm btn-outline-danger">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Stock</th><th>Unit</th></tr></thead>
                        <tbody>
                        <?php if (!empty($lowStockProducts)): foreach ($lowStockProducts as $lp): ?>
                        <tr>
                            <td><?= Helper::escape($lp['name']) ?></td>
                            <td class="text-end"><span class="badge bg-danger"><?= Helper::formatQty($lp['current_stock']) ?></span></td>
                            <td><?= Helper::escape($lp['unit_name'] ?? 'pcs') ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="3" class="text-center text-muted py-3"><i class="fas fa-check-circle text-success me-1"></i>All stock levels OK</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AI Business Insights -->
<div class="row g-3 mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-brain me-2" style="color:#f6c23e;"></i>AI Business Insights</h6>
                <a href="<?= APP_URL ?>/index.php?page=insights" class="btn btn-sm btn-outline-info">View All</a>
            </div>
            <div class="card-body" id="insightsContainer">
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                    <span class="text-muted ms-2">Analyzing your data...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= $cspNonce ?>">
// Load insights asynchronously
document.addEventListener('DOMContentLoaded', function() {
    fetch('<?= APP_URL ?>/index.php?page=insights&action=get_insights', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('insightsContainer');
        if (!data.success || !data.insights || data.insights.length === 0) {
            container.innerHTML = '<p class="text-muted text-center mb-0"><i class="fas fa-check-circle text-success me-1"></i>Everything looks good! No urgent insights.</p>';
            return;
        }
        const top3 = data.insights.slice(0, 3);
        let html = '<div class="row g-2">';
        top3.forEach(i => {
            const badge = i.priority === 'high' ? '<span class="badge bg-danger ms-1" style="font-size:0.6rem;">URGENT</span>' : '';
            const arrow = i.action ? `<a href="${i.action}" class="text-muted" style="font-size:0.75rem;"><i class="fas fa-arrow-right"></i></a>` : '';
            html += `<div class="col-md-4"><div class="p-2 rounded" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05);">
                <div class="d-flex align-items-center mb-1"><span style="font-size:1.2rem;">${i.icon}</span><strong class="ms-2 text-${i.color}" style="font-size:0.85rem;">${i.title}</strong>${badge}</div>
                <p style="font-size:0.8rem;color:#b7b9cc;margin-bottom:0.25rem;">${i.message}</p>
                <div class="d-flex justify-content-between align-items-center"><span class="fw-bold text-${i.color}">${i.value}</span>${arrow}</div>
            </div></div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    })
    .catch(() => {
        document.getElementById('insightsContainer').innerHTML = '<p class="text-muted text-center mb-0">Unable to load insights.</p>';
    });
});
</script>

<?php $inlineScript = "
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesPurchaseChart');
    if (!ctx) return;

    const initChart = function () {
        if (typeof Chart === 'undefined') {
            setTimeout(initChart, 60);
            return;
        }

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        const textColor = isDark ? '#a8adc0' : '#858796';
        const currencySymbol = " . json_encode(Helper::normalizeCurrencySymbol($company['currency_symbol'] ?? '₹')) . ";

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                datasets: [{
                    label: 'Sales',
                    data: {$salesChartData},
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28,200,138,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1cc88a',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                },{
                    label: 'Purchase',
                    data: {$purchaseChartData},
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78,115,223,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4e73df',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { labels: { color: textColor, usePointStyle: true, padding: 20 } }
                },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor } },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            callback: function (value) {
                                return currencySymbol + ' ' + Number(value).toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    };

    initChart();
});
"; ?>

