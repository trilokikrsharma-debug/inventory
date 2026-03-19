<?php $pageTitle = 'Return: ' . Helper::escape($return['return_number']); ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=sale_returns">Returns</a></li>
        <li class="breadcrumb-item active"><?= Helper::escape($return['return_number']) ?></li>
    </ol></nav>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=invoice&type=return&id=<?= $return['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-invoice me-1"></i>Credit Note</a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-6">
                        <span class="badge bg-warning text-dark fs-6"><?= Helper::escape($return['return_number']) ?></span>
                        <div class="mt-2"><strong>Against Invoice:</strong> <?= Helper::escape($return['invoice_number'] ?? 'N/A') ?></div>
                        <div><strong>Customer:</strong> <?= Helper::escape($return['customer_name'] ?? '') ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="text-muted">Return Date</div>
                        <div class="fw-bold"><?= Helper::formatDate($return['return_date']) ?></div>
                        <?php if (!empty($return['note'])): ?>
                        <div class="mt-2"><small class="text-muted">Reason: <?= Helper::escape($return['note']) ?></small></div>
                        <?php endif; ?>
                    </div>
                </div>
                <table class="table">
                    <thead><tr><th>#</th><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    <?php $i = 0; foreach ($return['items'] as $item): $i++; ?>
                    <tr>
                        <td><?= $i ?></td>
                        <td><?= Helper::escape($item['product_name']) ?></td>
                        <td class="text-center"><?= Helper::formatQty($item['quantity']) ?></td>
                        <td class="text-end"><?= Helper::formatCurrency($item['unit_price']) ?></td>
                        <td class="text-end fw-bold"><?= Helper::formatCurrency($item['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6>Summary</h6></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2 fs-5 fw-bold">
                    <span>Total Returned</span>
                    <span class="text-danger"><?= Helper::formatCurrency($return['total_amount']) ?></span>
                </div>
                <hr>
                <div class="alert alert-success py-2 small">
                    <i class="fas fa-check-circle me-1"></i>
                    Stock restored. Customer balance reduced by <?= Helper::formatCurrency($return['total_amount']) ?>.
                </div>
                <div class="mt-3">
                    <a href="<?= APP_URL ?>/index.php?page=sales&action=view_sale&id=<?= $return['sale_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-file-invoice me-1"></i>View Original Sale
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
