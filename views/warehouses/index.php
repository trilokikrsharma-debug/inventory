<?php
$warehouses = is_array($warehouses ?? null) ? $warehouses : [];
$activeWarehouses = is_array($activeWarehouses ?? null) ? $activeWarehouses : $warehouses;
$editingWarehouse = is_array($editingWarehouse ?? null) ? $editingWarehouse : null;
$recentTransfers = is_array($recentTransfers ?? null) ? $recentTransfers : [];
$isEditing = !empty($editingWarehouse);
$canTransferStock = !empty($canTransferStock);
?>

<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=products">Products</a></li>
                <li class="breadcrumb-item active">Warehouses</li>
            </ol>
        </nav>
        <h2 class="h4 mb-1">Warehouse Network</h2>
        <p class="text-muted mb-0">Manage stock locations and keep product inventory split by warehouse.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=products" class="btn btn-outline-secondary"><i class="fas fa-boxes-stacked me-1"></i>Products</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="fas fa-warehouse me-2"></i><?= $isEditing ? 'Edit Warehouse' : 'Add Warehouse' ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/index.php?page=warehouses&action=<?= $isEditing ? 'edit' : 'create' ?>">
                    <?= CSRF::field() ?>
                    <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int)$editingWarehouse['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Warehouse Name</label>
                        <input type="text" name="name" class="form-control" required value="<?= Helper::escape((string)($editingWarehouse['name'] ?? '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" maxlength="40" placeholder="e.g. MAIN" value="<?= Helper::escape((string)($editingWarehouse['code'] ?? '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g. Head Office - Ground Floor" value="<?= Helper::escape((string)($editingWarehouse['location'] ?? '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= Helper::escape((string)($editingWarehouse['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="warehouse_active" <?= !$isEditing || !empty($editingWarehouse['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="warehouse_active">Active warehouse</label>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="warehouse_default" <?= !empty($editingWarehouse['is_default']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="warehouse_default">Make default stock bucket</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas <?= $isEditing ? 'fa-save' : 'fa-plus' ?> me-1"></i><?= $isEditing ? 'Update Warehouse' : 'Create Warehouse' ?>
                        </button>
                        <?php if ($isEditing): ?>
                        <a href="<?= APP_URL ?>/index.php?page=warehouses" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-right-left me-2"></i>Stock Transfer</h6>
                <?php if (!$canTransferStock): ?><span class="badge bg-warning-subtle text-warning">Need 2 active warehouses</span><?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$canTransferStock): ?>
                <div class="text-muted small">Activate at least two warehouses before moving stock between locations.</div>
                <?php else: ?>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=warehouses&action=transfer" id="warehouseTransferForm">
                    <?= CSRF::field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">From Warehouse</label>
                            <select name="source_warehouse_id" class="form-select" id="transferSourceWarehouse" required>
                                <option value="">Select</option>
                                <?php foreach ($activeWarehouses as $warehouse): ?>
                                <option value="<?= (int)$warehouse['id'] ?>" <?= !empty($warehouse['is_default']) ? 'selected' : '' ?>>
                                    <?= Helper::escape($warehouse['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Warehouse</label>
                            <select name="destination_warehouse_id" class="form-select" required>
                                <option value="">Select</option>
                                <?php foreach ($activeWarehouses as $warehouse): ?>
                                <option value="<?= (int)$warehouse['id'] ?>">
                                    <?= Helper::escape($warehouse['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transfer Date</label>
                            <input type="date" name="transfer_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="Optional memo or challan">
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Transfer Items</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addTransferItemBtn">
                                <i class="fas fa-plus me-1"></i>Add Item
                            </button>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:58%">Product</th>
                                        <th style="width:22%">Available</th>
                                        <th style="width:16%">Qty</th>
                                        <th class="text-end"> </th>
                                    </tr>
                                </thead>
                                <tbody id="transferItemsBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Reason for movement, handover note, branch refill, etc."></textarea>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 mt-3">
                        <i class="fas fa-right-left me-1"></i>Create Transfer Request
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-network-wired me-2"></i>Configured Warehouses</h6>
                <span class="badge text-bg-light"><?= count($warehouses) ?> total</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($warehouses)): ?>
                <div class="p-4 text-center text-muted">No warehouses configured yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Warehouse</th>
                                <th>Status</th>
                                <th>Products</th>
                                <th class="text-end">Stock Units</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($warehouses as $warehouse): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($warehouse['name']) ?></div>
                                    <div class="small text-muted">
                                        <?= !empty($warehouse['code']) ? Helper::escape($warehouse['code']) : 'No code' ?>
                                        <?php if (!empty($warehouse['location'])): ?> • <?= Helper::escape($warehouse['location']) ?><?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($warehouse['is_default'])): ?><span class="badge bg-primary-subtle text-primary me-1">Default</span><?php endif; ?>
                                    <span class="badge <?= !empty($warehouse['is_active']) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                                        <?= !empty($warehouse['is_active']) ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= (int)($warehouse['assigned_products'] ?? 0) ?></td>
                                <td class="text-end fw-semibold"><?= Helper::formatQty($warehouse['stock_units'] ?? 0) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <?php if (empty($warehouse['is_default'])): ?>
                                        <form method="POST" action="<?= APP_URL ?>/index.php?page=warehouses&action=set_default" class="d-inline">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$warehouse['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Set Default">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <a href="<?= APP_URL ?>/index.php?page=warehouses&edit_id=<?= (int)$warehouse['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (empty($warehouse['is_default'])): ?>
                                        <form method="POST" action="<?= APP_URL ?>/index.php?page=warehouses&action=delete" class="d-inline" data-confirm="Delete this warehouse?">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$warehouse['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0"><i class="fas fa-clock-rotate-left me-2"></i>Recent Transfers</h6>
                    <small class="text-muted">Latest warehouse-to-warehouse movement audit trail.</small>
                </div>
                <span class="badge text-bg-light"><?= count($recentTransfers) ?> shown</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentTransfers)): ?>
                <div class="p-4 text-center text-muted">No stock transfers recorded yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Transfer</th>
                                <th>Route</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Items</th>
                                <th class="text-end">Quantity</th>
                                <th>Audit</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransfers as $transfer): ?>
                            <?php $transferStatus = (string)($transfer['status'] ?? 'approved'); ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($transfer['transfer_number']) ?></div>
                                    <?php if (!empty($transfer['note'])): ?><div class="small text-muted"><?= Helper::escape($transfer['note']) ?></div><?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($transfer['source_warehouse_name']) ?></div>
                                    <div class="small text-muted"><i class="fas fa-arrow-right me-1"></i><?= Helper::escape($transfer['destination_warehouse_name']) ?></div>
                                </td>
                                <td><?= Helper::formatDate($transfer['transfer_date']) ?></td>
                                <td>
                                    <span class="badge <?= $transferStatus === 'approved' ? 'bg-success-subtle text-success' : ($transferStatus === 'rejected' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') ?>">
                                        <?= Helper::escape(ucfirst($transferStatus)) ?>
                                    </span>
                                </td>
                                <td><?= (int)($transfer['item_count'] ?? 0) ?></td>
                                <td class="text-end fw-semibold"><?= Helper::formatQty($transfer['total_quantity'] ?? 0) ?></td>
                                <td>
                                    <div><?= Helper::escape($transfer['created_by_name'] ?? 'System') ?></div>
                                    <div class="small text-muted">
                                        <?= $transferStatus === 'approved'
                                            ? 'Approved by ' . Helper::escape($transfer['approved_by_name'] ?? 'System')
                                            : ($transferStatus === 'rejected'
                                                ? 'Rejected by ' . Helper::escape($transfer['rejected_by_name'] ?? 'System')
                                                : 'Awaiting approval') ?>
                                    </div>
                                    <?php if ($transferStatus === 'rejected' && !empty($transfer['rejection_reason'])): ?>
                                    <div class="small text-danger"><?= Helper::escape($transfer['rejection_reason']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($transferStatus === 'pending'): ?>
                                    <div class="d-inline-flex gap-1">
                                    <form method="POST" action="<?= APP_URL ?>/index.php?page=warehouses&action=approve_transfer" class="d-inline">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$transfer['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-check me-1"></i>Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= APP_URL ?>/index.php?page=warehouses&action=reject_transfer" class="d-inline">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$transfer['id'] ?>">
                                        <input type="hidden" name="rejection_reason" value="Rejected transfer request">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </form>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted small"><?= $transferStatus === 'approved' ? 'Completed' : 'Closed' ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canTransferStock): ?>
<?php $inlineScript = <<<'JS'
const transferAppUrl = __APP_URL__;
const transferItemsBody = document.getElementById('transferItemsBody');
const transferSourceWarehouse = document.getElementById('transferSourceWarehouse');
const addTransferItemBtn = document.getElementById('addTransferItemBtn');

function transferAddItemRow() {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="text" class="form-control form-control-sm transfer-product-search" placeholder="Search product by name / SKU">
            <input type="hidden" name="product_id[]" class="transfer-product-id">
        </td>
        <td><span class="badge bg-secondary-subtle text-secondary transfer-available">0</span></td>
        <td><input type="number" name="quantity[]" class="form-control form-control-sm transfer-qty" step="0.001" min="0.001" value="1"></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button></td>
    `;

    row.querySelector('button').addEventListener('click', () => row.remove());
    const input = row.querySelector('.transfer-product-search');
    let timeoutId;
    input.addEventListener('input', function () {
        clearTimeout(timeoutId);
        const sourceWarehouseId = transferSourceWarehouse.value;
        if (this.value.length < 2 || !sourceWarehouseId) {
            removeTransferDropdown(row);
            return;
        }
        timeoutId = setTimeout(() => {
            fetch(transferAppUrl + '/index.php?page=warehouses&action=search_products&warehouse_id=' + encodeURIComponent(sourceWarehouseId) + '&term=' + encodeURIComponent(this.value))
                .then((response) => response.json())
                .then((products) => showTransferDropdown(products, row, input));
        }, 250);
    });

    transferItemsBody.appendChild(row);
}

function removeTransferDropdown(row) {
    const dropdown = row.querySelector('.transfer-dropdown');
    if (dropdown) {
        dropdown.remove();
    }
}

function showTransferDropdown(products, row, input) {
    removeTransferDropdown(row);
    if (!Array.isArray(products) || products.length === 0) {
        return;
    }

    const dropdown = document.createElement('div');
    dropdown.className = 'transfer-dropdown';
    dropdown.style.cssText = 'position:absolute;z-index:9999;background:var(--card-bg,#fff);border:1px solid var(--border-color,#dee2e6);border-radius:8px;max-height:220px;overflow-y:auto;width:100%;box-shadow:0 10px 24px rgba(15,23,42,0.12);';

    products.forEach((product) => {
        const option = document.createElement('button');
        option.type = 'button';
        option.className = 'dropdown-item text-start';
        option.innerHTML = '<div class="fw-semibold">' + product.name + '</div><div class="small text-muted">Available: ' + product.available_stock + ' ' + (product.unit_name || '') + '</div>';
        option.addEventListener('click', () => {
            row.querySelector('.transfer-product-id').value = product.id;
            row.querySelector('.transfer-product-search').value = product.name_raw || product.name;
            row.querySelector('.transfer-available').textContent = product.available_stock + ' ' + (product.unit_name || '');
            removeTransferDropdown(row);
        });
        dropdown.appendChild(option);
    });

    input.parentElement.style.position = 'relative';
    input.parentElement.appendChild(dropdown);

    document.addEventListener('click', function handler(event) {
        if (!input.parentElement.contains(event.target)) {
            removeTransferDropdown(row);
            document.removeEventListener('click', handler);
        }
    });
}

if (addTransferItemBtn) {
    addTransferItemBtn.addEventListener('click', transferAddItemRow);
    transferAddItemRow();
}

if (transferSourceWarehouse) {
    transferSourceWarehouse.addEventListener('change', function () {
        transferItemsBody.querySelectorAll('tr').forEach((row) => {
            row.querySelector('.transfer-product-id').value = '';
            row.querySelector('.transfer-product-search').value = '';
            row.querySelector('.transfer-available').textContent = '0';
            removeTransferDropdown(row);
        });
    });
}
JS;
$inlineScript = str_replace('__APP_URL__', json_encode(APP_URL), $inlineScript);
?>
<?php endif; ?>
