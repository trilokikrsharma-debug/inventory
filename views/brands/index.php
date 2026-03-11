<?php $pageTitle = 'Brands'; ?>
<div class="page-header">
    <div><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Brands</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-1"></i>Add Brand</button>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive"><table class="table table-striped mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!empty($brands)): $i=0; foreach ($brands as $b): $i++; ?>
            <tr>
                <td><?= $i ?></td><td class="fw-bold"><?= Helper::escape($b['name']) ?></td>
                <td><span class="badge bg-primary"><?= $b['product_count'] ?></span></td>
                <td><?= $b['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                <td><div class="action-btns">
                    <button class="btn btn-sm btn-outline-primary btn-icon edit-btn" data-id="<?= $b['id'] ?>" data-name="<?= Helper::escape($b['name']) ?>" data-description="<?= Helper::escape($b['description'] ?? '') ?>" data-active="<?= $b['is_active'] ?>"><i class="fas fa-edit"></i></button>
                    <?php if (Session::hasPermission('catalog.manage')): ?>
                    <form method="POST" action="<?= APP_URL ?>/index.php?page=brands&action=delete" class="d-inline" data-confirm="Delete this brand?">
                        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div></td>
            </tr>
            <?php endforeach; else: ?><tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-tags fa-2x mb-2 opacity-25 d-block"></i>No brands found.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="<?= APP_URL ?>/index.php?page=brands&action=create"><?= CSRF::field() ?>
        <div class="modal-header"><h5 class="modal-title">Add Brand</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" autocomplete="organization" required></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
</div></div></div>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" id="editForm"><?= CSRF::field() ?>
        <div class="modal-header"><h5 class="modal-title">Edit Brand</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" id="editName" autocomplete="organization" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="editDescription" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
</div></div></div>
<?php $inlineScript = "document.querySelectorAll('.edit-btn').forEach(btn=>{btn.addEventListener('click',function(){document.getElementById('editName').value=this.dataset.name;document.getElementById('editDescription').value=this.dataset.description;document.getElementById('editForm').action='" . APP_URL . "/index.php?page=brands&action=edit&id='+this.dataset.id;new bootstrap.Modal(document.getElementById('editModal')).show();});});"; ?>
