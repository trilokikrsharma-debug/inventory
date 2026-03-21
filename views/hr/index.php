<div class="row g-4 mt-2">
    <div class="col-md-3">
        <div class="card bg-dark text-white border-secondary h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">Total Employees</h6>
                <div class="d-flex align-items-center mb-0">
                    <i class="fas fa-users fa-2x text-primary me-3 opacity-75"></i>
                    <h2 class="mb-0 fw-bold"><?= count($employees) ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white border-secondary h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">On Leave Today</h6>
                <div class="d-flex align-items-center mb-0">
                    <i class="fas fa-bed fa-2x text-warning me-3 opacity-75"></i>
                    <h2 class="mb-0 fw-bold">0</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card bg-dark text-white shadow-sm border-secondary mt-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-id-badge text-primary me-2"></i> Employee Directory</h5>
        <button class="btn btn-sm btn-primary" onclick="alert('Demo: This action will be available soon.')"><i class="fas fa-plus"></i> Add Employee</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0 text-nowrap">
                <thead>
                    <tr>
                        <th class="ps-3 border-secondary text-muted">EMPLOYEE NAME</th>
                        <th class="border-secondary text-muted">DESIGNATION</th>
                        <th class="border-secondary text-muted">STATUS</th>
                        <th class="border-secondary text-muted">DATE JOINED</th>
                        <th class="pe-3 border-secondary text-end text-muted">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($employees)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-folder-open mb-2 fa-2x"></i><br>No employees found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($employees as $emp): ?>
                        <tr>
                            <td class="ps-3 fw-bold"><?= htmlspecialchars($emp['name']) ?></td>
                            <td><?= htmlspecialchars($emp['designation']) ?></td>
                            <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2"><?= htmlspecialchars($emp['status']) ?></span></td>
                            <td><?= date('d M Y', strtotime($emp['joined'])) ?></td>
                            <td class="pe-3 text-end">
                                <button class="btn btn-sm btn-outline-secondary" onclick="alert('Demo: Feature coming soon')"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
