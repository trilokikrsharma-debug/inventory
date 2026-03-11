<?php $pageTitle = 'Units'; ?>
<div class="page-header">
    <div><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Units</li></ol></nav></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-1"></i>Add Unit</button>
</div>
<div class="card">
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Name</th><th>Short Name</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (!empty($units)): $i=0; foreach ($units as $u): $i++; ?>
        <tr>
            <td><?= $i ?></td><td class="fw-bold"><?= Helper::escape($u['name']) ?></td><td><code><?= Helper::escape($u['short_name']) ?></code></td>
            <td><span class="badge bg-primary"><?= $u['product_count'] ?></span></td>
            <td><?= $u['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
            <td><div class="action-btns">
                <button class="btn btn-sm btn-outline-primary btn-icon edit-btn" data-id="<?= $u['id'] ?>" data-name="<?= Helper::escape($u['name']) ?>" data-short="<?= Helper::escape($u['short_name']) ?>" data-active="<?= $u['is_active'] ?>"><i class="fas fa-edit"></i></button>
                <?php if (Session::hasPermission('catalog.manage')): ?>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=units&action=delete" class="d-inline" data-confirm="Delete this unit?">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon"><i class="fas fa-trash"></i></button>
                </form>
                <?php endif; ?>
            </div></td>
        </tr>
        <?php endforeach; else: ?><tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-balance-scale fa-2x mb-2 opacity-25 d-block"></i>No units found.</td></tr><?php endif; ?>
        </tbody>
    </table></div></div>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="<?= APP_URL ?>/index.php?page=units&action=create"><?= CSRF::field() ?>
        <div class="modal-header"><h5 class="modal-title">Add Unit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required placeholder="e.g. Kilogram" autocomplete="off"></div>
            <div class="mb-3"><label class="form-label">Short Name <span class="text-danger">*</span></label><input type="text" name="short_name" class="form-control" required placeholder="e.g. kg"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
</div></div></div>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" id="editForm"><?= CSRF::field() ?>
        <div class="modal-header"><h5 class="modal-title">Edit Unit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" id="editName" class="form-control" autocomplete="off" required></div>
            <div class="mb-3"><label class="form-label">Short Name</label><input type="text" name="short_name" id="editShort" class="form-control" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
</div></div></div>
<?php $inlineScript = "document.querySelectorAll('.edit-btn').forEach(btn=>{btn.addEventListener('click',function(){document.getElementById('editName').value=this.dataset.name;document.getElementById('editShort').value=this.dataset.short;document.getElementById('editForm').action='" . APP_URL . "/index.php?page=units&action=edit&id='+this.dataset.id;new bootstrap.Modal(document.getElementById('editModal')).show();});});"; ?>
