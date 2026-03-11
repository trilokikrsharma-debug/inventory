<?php $pageTitle = ($type === 'receipt' ? 'New Receipt' : 'New Payment'); ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=payments">Payments</a></li><li class="breadcrumb-item active"><?= $type === 'receipt' ? 'Receipt' : 'Payment' ?></li></ol></nav></div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h6><i class="fas fa-<?= $type==='receipt'?'hand-holding-usd':'paper-plane' ?> me-2"></i><?= $type==='receipt' ? 'Receive Payment from Customer' : 'Make Payment to Supplier' ?></h6></div>
            <div class="card-body">
                <form method="POST"><?= CSRF::field() ?>
                    <input type="hidden" name="type" value="<?= $type ?>">
                    <?php if ($type === 'receipt'): ?>
                    <div class="mb-3"><label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select" required><option value="">Select</option>
                            <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= ($_GET['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= Helper::escape($c['name']) ?> (Due: <?= Helper::formatCurrency($c['current_balance']) ?>)</option><?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($_GET['sale_id'])): ?><input type="hidden" name="sale_id" value="<?= (int)$_GET['sale_id'] ?>"><?php endif; ?>
                    <?php else: ?>
                    <div class="mb-3"><label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required><option value="">Select</option>
                            <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>" <?= ($_GET['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= Helper::escape($s['name']) ?> (Due: <?= Helper::formatCurrency($s['current_balance']) ?>)</option><?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($_GET['purchase_id'])): ?><input type="hidden" name="purchase_id" value="<?= (int)$_GET['purchase_id'] ?>"><?php endif; ?>
                    <?php endif; ?>
                    <div class="mb-3"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" name="amount" class="form-control" step="0.01" required value="<?= !empty($_GET['amount']) ? (float)$_GET['amount'] : '' ?>"></div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Method</label><select name="payment_method" class="form-select"><option value="cash">Cash</option><option value="bank">Bank Transfer</option><option value="online">UPI / Online</option><option value="cheque">Cheque</option><option value="other">Other</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Reference No.</label><input type="text" name="reference_number" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Bank Name</label><input type="text" name="bank_name" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Note</label><textarea name="note" class="form-control" rows="2"></textarea></div>
                    <button type="submit" class="btn btn-<?= $type==='receipt'?'success':'primary' ?> w-100"><i class="fas fa-save me-2"></i>Save <?= ucfirst($type) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
