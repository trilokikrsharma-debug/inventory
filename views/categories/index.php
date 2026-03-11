<?php $pageTitle = 'Categories'; ?>
<div class="page-header">
    <div><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Categories</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-1"></i>Add Category</button>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Description</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (!empty($categories)): $i=0; foreach ($categories as $c): $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td class="fw-bold"><?= Helper::escape($c['name']) ?></td>
                    <td class="text-muted"><?= Helper::escape($c['description'] ?? '-') ?></td>
                    <td><span class="badge bg-primary"><?= $c['product_count'] ?></span></td>
                    <td><?= $c['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-sm btn-outline-primary btn-icon edit-btn" data-id="<?= $c['id'] ?>" data-name="<?= Helper::escape($c['name']) ?>" data-description="<?= Helper::escape($c['description'] ?? '') ?>" data-active="<?= $c['is_active'] ?>" title="Edit"><i class="fas fa-edit"></i></button>
                            <?php if (Session::hasPermission('catalog.manage')): ?>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=categories&action=delete" class="d-inline" data-confirm="Delete this category?">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-layer-group fa-2x mb-2 opacity-25 d-block"></i>No categories found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="<?= APP_URL ?>/index.php?page=categories&action=create">
        <?= CSRF::field() ?>
        <div class="modal-header"><h5 class="modal-title">Add Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required autocomplete="off"></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            <div class="mb-3"><label class="form-label">Status</label><select name="is_active" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button></div>
    </form>
</div></div></div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" id="editForm">
        <?= CSRF::field() ?>
        <div class="modal-header"><h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" id="editName" class="form-control" required autocomplete="off"></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="editDescription" class="form-control" rows="2"></textarea></div>
            <div class="mb-3"><label class="form-label">Status</label><select name="is_active" id="editActive" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button></div>
    </form>
</div></div></div>

<?php $inlineScript = "
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editName').value = this.dataset.name;
        document.getElementById('editDescription').value = this.dataset.description;
        document.getElementById('editActive').value = this.dataset.active;
        document.getElementById('editForm').action = '" . APP_URL . "/index.php?page=categories&action=edit&id=' + this.dataset.id;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});"; ?>
