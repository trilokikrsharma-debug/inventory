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
    const allowedInsightColors = new Set(['primary', 'success', 'warning', 'danger', 'info', 'secondary']);

    function clearElement(element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function getSafeColor(color) {
        return allowedInsightColors.has(color) ? color : 'info';
    }

    function isSafeInsightAction(action) {
        if (typeof action !== 'string') {
            return false;
        }

        const trimmed = action.trim();
        if (!trimmed || /^(javascript|data|vbscript):/i.test(trimmed)) {
            return false;
        }

        try {
            const url = new URL(trimmed, window.location.origin);
            return url.origin === window.location.origin && (url.protocol === 'http:' || url.protocol === 'https:');
        } catch (e) {
            return false;
        }
    }

    function renderEmptyState(container, message, iconClass, iconColorClass) {
        clearElement(container);

        const paragraph = document.createElement('p');
        paragraph.className = 'text-muted text-center mb-0';

        if (iconClass) {
            const icon = document.createElement('i');
            icon.className = iconClass + (iconColorClass ? ' ' + iconColorClass : '') + ' me-1';
            paragraph.appendChild(icon);
        }

        paragraph.appendChild(document.createTextNode(message));
        container.appendChild(paragraph);
    }

    function renderInsightCard(insight) {
        const col = document.createElement('div');
        col.className = 'col-md-4';

        const card = document.createElement('div');
        card.className = 'p-2 rounded';
        card.style.background = 'rgba(255,255,255,0.03)';
        card.style.border = '1px solid rgba(255,255,255,0.05)';

        const header = document.createElement('div');
        header.className = 'd-flex align-items-center mb-1';

        const icon = document.createElement('span');
        icon.style.fontSize = '1.2rem';
        icon.textContent = insight.icon || '';
        header.appendChild(icon);

        const title = document.createElement('strong');
        title.className = 'ms-2 text-' + getSafeColor(insight.color);
        title.style.fontSize = '0.85rem';
        title.textContent = insight.title || '';
        header.appendChild(title);

        if (insight.priority === 'high') {
            const badge = document.createElement('span');
            badge.className = 'badge bg-danger ms-1';
            badge.style.fontSize = '0.6rem';
            badge.textContent = 'URGENT';
            header.appendChild(badge);
        }

        const message = document.createElement('p');
        message.style.fontSize = '0.8rem';
        message.style.color = '#b7b9cc';
        message.style.marginBottom = '0.25rem';
        message.textContent = insight.message || '';

        const footer = document.createElement('div');
        footer.className = 'd-flex justify-content-between align-items-center';

        const value = document.createElement('span');
        value.className = 'fw-bold text-' + getSafeColor(insight.color);
        value.textContent = insight.value || '';
        footer.appendChild(value);

        if (isSafeInsightAction(insight.action)) {
            const link = document.createElement('a');
            link.className = 'text-muted';
            link.style.fontSize = '0.75rem';
            link.href = insight.action.trim();
            link.setAttribute('aria-label', 'View insight details');

            const arrow = document.createElement('i');
            arrow.className = 'fas fa-arrow-right';
            link.appendChild(arrow);

            footer.appendChild(link);
        }

        card.appendChild(header);
        card.appendChild(message);
        card.appendChild(footer);
        col.appendChild(card);
        return col;
    }

    let insightsLoaded = false;
    const loadInsights = function() {
        if (insightsLoaded) return;
        insightsLoaded = true;

        fetch('<?= APP_URL ?>/index.php?page=insights&action=get_insights', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('insightsContainer');
            if (!container) return;

            if (!data.success || !data.insights || data.insights.length === 0) {
                renderEmptyState(container, 'Everything looks good! No urgent insights.', 'fas fa-check-circle', 'text-success');
                return;
            }

            const top3 = data.insights.slice(0, 3);

            clearElement(container);
            const row = document.createElement('div');
            row.className = 'row g-2';
            top3.forEach((insight) => {
                row.appendChild(renderInsightCard(insight));
            });
            container.appendChild(row);
        })
        .catch(() => {
            const container = document.getElementById('insightsContainer');
            if (container) {
                renderEmptyState(container, 'Unable to load insights.', null, null);
            }
        });
    };

    const container = document.getElementById('insightsContainer');
    if (!container) return;

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    observer.disconnect();
                    loadInsights();
                }
            });
        }, { rootMargin: '120px 0px' });
        observer.observe(container);
    } else {
        loadInsights();
    }
});
</script>

<?php $inlineScript = "
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('salesPurchaseChart');
    if (!canvas) return;

    const renderChart = function () {
        if (canvas.dataset.chartRendered === '1') return;
        canvas.dataset.chartRendered = '1';

        const initChart = function () {
            if (typeof Chart === 'undefined') {
                setTimeout(initChart, 60);
                return;
            }

            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
            const textColor = isDark ? '#a8adc0' : '#858796';
            const currencySymbol = " . json_encode(Helper::normalizeCurrencySymbol($company['currency_symbol'] ?? '₹')) . ";

            new Chart(canvas, {
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

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(initChart, { timeout: 1000 });
        } else {
            setTimeout(initChart, 0);
        }
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    observer.disconnect();
                    renderChart();
                }
            });
        }, { rootMargin: '120px 0px' });
        observer.observe(canvas);
    } else {
        renderChart();
    }
});
"; ?>

