<?php
$pageTitle = 'GST / Tax Summary';
$summary = $report['summary'] ?? [];
$salesBreakdown = $report['sales_breakdown'] ?? [];
$purchaseBreakdown = $report['purchase_breakdown'] ?? [];
$gstEnabled = !empty($report['gst_enabled']);
?>
<div class="report-page-shell">
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">GST / Tax Summary</li></ol></nav>
    <div class="report-page-actions">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=reports&action=queue_export">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="report_type" value="tax_summary">
            <input type="hidden" name="from_date" value="<?= Helper::escape($fromDate) ?>">
            <input type="hidden" name="to_date" value="<?= Helper::escape($toDate) ?>">
            <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-file-arrow-down me-1"></i>Queue CSV</button>
        </form>
        <button type="button" class="btn btn-outline-primary btn-sm" data-print-target="reportTable"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>

<div class="card mb-3 report-filter-card"><div class="card-body py-2">
    <form class="report-filter-form" method="GET"><input type="hidden" name="page" value="reports"><input type="hidden" name="action" value="tax_summary">
        <div class="report-filter-field"><label class="form-label small mb-0">From</label><input type="date" name="from_date" class="form-control form-control-sm" value="<?= Helper::escape($fromDate) ?>"></div>
        <div class="report-filter-field"><label class="form-label small mb-0">To</label><input type="date" name="to_date" class="form-control form-control-sm" value="<?= Helper::escape($toDate) ?>"></div>
        <button class="btn btn-sm btn-primary report-filter-submit"><i class="fas fa-filter me-1"></i>Filter</button>
    </form>
</div></div>

<?php if (!$gstEnabled): ?>
<div class="alert alert-info"><strong>Non-GST mode active.</strong> New bills keep tax calculations off. This report still shows historical tax snapshots if older GST entries exist in the selected period.</div>
<?php endif; ?>

<div class="row g-3 mb-3 report-summary-grid">
    <div class="col-md-3"><div class="stat-card stat-success"><div class="stat-value"><?= Helper::formatCurrency($summary['sales_taxable'] ?? 0) ?></div><div class="stat-label">Sales Taxable Turnover</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-info"><div class="stat-value"><?= Helper::formatCurrency($summary['sales_non_gst'] ?? 0) ?></div><div class="stat-label">Non-GST / Zero-Tax Sales</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-primary"><div class="stat-value"><?= Helper::formatCurrency($summary['output_tax'] ?? 0) ?></div><div class="stat-label">Output GST</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-warning"><div class="stat-value"><?= Helper::formatCurrency($summary['net_tax_payable'] ?? 0) ?></div><div class="stat-label">Net Tax Payable</div></div></div>
</div>

<div class="card report-data-card mb-3" id="reportTable">
    <div class="card-header fw-bold">GST Summary</div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0 report-data-table">
        <tbody>
            <tr><th>Output CGST</th><td class="text-end"><?= Helper::formatCurrency($summary['output_cgst'] ?? 0) ?></td><th>Output SGST</th><td class="text-end"><?= Helper::formatCurrency($summary['output_sgst'] ?? 0) ?></td></tr>
            <tr><th>Output IGST</th><td class="text-end"><?= Helper::formatCurrency($summary['output_igst'] ?? 0) ?></td><th>Total Output Tax</th><td class="text-end"><?= Helper::formatCurrency($summary['output_tax'] ?? 0) ?></td></tr>
            <tr><th>Posted Return Taxable Adjustment</th><td class="text-end"><?= Helper::formatCurrency($summary['sales_return_taxable'] ?? 0) ?></td><th>Non-GST / Zero-Tax Sales</th><td class="text-end"><?= Helper::formatCurrency($summary['sales_non_gst'] ?? 0) ?></td></tr>
            <tr><th>Purchase Taxable Value</th><td class="text-end"><?= Helper::formatCurrency($summary['purchase_taxable'] ?? 0) ?></td><th>Input Tax</th><td class="text-end"><?= Helper::formatCurrency($summary['input_tax'] ?? 0) ?></td></tr>
            <tr><th>Purchase Return Taxable Adjustment</th><td class="text-end"><?= Helper::formatCurrency($summary['purchase_return_taxable'] ?? 0) ?></td><th></th><td></td></tr>
            <tr class="fw-bold"><th>Net Tax Payable</th><td class="text-end" colspan="3"><?= Helper::formatCurrency($summary['net_tax_payable'] ?? 0) ?></td></tr>
        </tbody>
    </table></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card report-data-card">
            <div class="card-header fw-bold">Sales Tax Breakdown</div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0 report-data-table">
                <thead><tr><th>GST %</th><th>Type</th><th class="text-end">Vouchers</th><th class="text-end">Taxable</th><th class="text-end">Tax</th></tr></thead>
                <tbody>
                <?php if (!empty($salesBreakdown)): foreach ($salesBreakdown as $row): ?>
                    <tr><td><?= number_format((float)$row['tax_rate'], 2) ?>%</td><td><?= Helper::escape(strtoupper((string)$row['gst_type'])) ?></td><td class="text-end"><?= (int)$row['voucher_count'] ?></td><td class="text-end"><?= Helper::formatCurrency($row['taxable_amount']) ?></td><td class="text-end"><?= Helper::formatCurrency($row['tax_amount']) ?></td></tr>
                <?php endforeach; else: ?><tr><td colspan="5" class="text-center py-3 text-muted">No sales tax data</td></tr><?php endif; ?>
                </tbody>
            </table></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card report-data-card">
            <div class="card-header fw-bold">Purchase Tax Breakdown</div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0 report-data-table">
                <thead><tr><th>GST %</th><th class="text-end">Vouchers</th><th class="text-end">Taxable</th><th class="text-end">Tax</th></tr></thead>
                <tbody>
                <?php if (!empty($purchaseBreakdown)): foreach ($purchaseBreakdown as $row): ?>
                    <tr><td><?= number_format((float)$row['tax_rate'], 2) ?>%</td><td class="text-end"><?= (int)$row['voucher_count'] ?></td><td class="text-end"><?= Helper::formatCurrency($row['taxable_amount']) ?></td><td class="text-end"><?= Helper::formatCurrency($row['tax_amount']) ?></td></tr>
                <?php endforeach; else: ?><tr><td colspan="4" class="text-center py-3 text-muted">No purchase tax data</td></tr><?php endif; ?>
                </tbody>
            </table></div></div>
        </div>
    </div>
</div>
</div>
