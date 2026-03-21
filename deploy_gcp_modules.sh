#!/bin/bash
# GCP Production & Stub Completion Script
set -e

# 1. Create DB Tables for API and HR
cat << 'EOF' > /tmp/upgrade_db.sql
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    designation VARCHAR(100),
    status ENUM('Active', 'On Leave', 'Terminated') DEFAULT 'Active',
    joined_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
EOF

mysql -u root inventory < /tmp/upgrade_db.sql

# 2. Write ApiController.php (CRUD + Auth + Endpoints)
cat << 'EOF' > /var/www/inventory/controllers/ApiController.php
<?php
class ApiController extends Controller {
    protected $allowedActions = ['index', 'generate', 'v1_products'];

    public function index() {
        $this->requireFeature('api');
        $this->requirePermission('settings.view');
        
        $db = Database::getInstance();
        $keys = $db->query("SELECT * FROM api_keys WHERE company_id = ?", [Tenant::id()])->fetchAll();

        $this->view('api.index', [
            'pageTitle' => 'API Access',
            'api_keys' => $keys
        ]);
    }

    public function generate() {
        $this->requireFeature('api');
        $this->requirePermission('settings.view');
        
        if ($this->isPost()) {
            $this->validateCSRF();
            $name = $_POST['name'] ?? 'Production Key';
            $key = 'sk_live_' . bin2hex(random_bytes(16));
            
            Database::getInstance()->query(
                "INSERT INTO api_keys (company_id, api_key, name) VALUES (?, ?, ?)",
                [Tenant::id(), $key, $name]
            );
            $this->setFlash('success', 'New API Key generated successfully! Make sure to copy it now: ' . $key);
        }
        $this->redirect('index.php?page=api');
    }

    // Actual API endpoint
    public function v1_products() {
        header('Content-Type: application/json');
        
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Missing or invalid Authorization header']);
            exit;
        }
        
        $apiKey = $matches[1];
        $db = Database::getInstance();
        $keyRecord = $db->query("SELECT company_id FROM api_keys WHERE api_key = ?", [$apiKey])->fetch();
        
        if (!$keyRecord) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid API Key']);
            exit;
        }
        
        $tenantId = $keyRecord['company_id'];
        $products = $db->query("SELECT id, name, sku, current_stock, sale_price FROM products WHERE company_id = ? AND is_active = 1", [$tenantId])->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $products
        ]);
        exit;
    }
}
EOF

# 3. Write HrController.php (CRUD for Employees)
cat << 'EOF' > /var/www/inventory/controllers/HrController.php
<?php
class HrController extends Controller {
    protected $allowedActions = ['index', 'add'];

    public function index() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.view');
        
        $db = Database::getInstance();
        $employees = $db->query("SELECT * FROM employees WHERE company_id = ? ORDER BY created_at DESC", [Tenant::id()])->fetchAll();

        $this->view('hr.index', [
            'pageTitle' => 'HR Tools',
            'employees' => $employees ?: []
        ]);
    }

    public function add() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.view');
        
        if ($this->isPost()) {
            $this->validateCSRF();
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO employees (company_id, name, designation, joined_date, status) VALUES (?, ?, ?, ?, ?)",
                [Tenant::id(), $_POST['name'], $_POST['designation'], $_POST['joined_date'], 'Active']
            );
            $this->setFlash('success', 'Employee added successfully!');
        }
        $this->redirect('index.php?page=hr');
    }
}
EOF

# 4. Update the Views slightly to match the new logic
cat << 'EOF' > /var/www/inventory/views/api/index.php
<div class="card bg-dark text-white shadow-sm border-secondary mt-3">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-code text-primary me-2"></i> API Access Keys</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Use API keys to authenticate your requests via <code>Authorization: Bearer sk_live_...</code></p>
        
        <?php foreach($api_keys as $k): ?>
        <div class="bg-darker p-3 rounded border border-secondary mb-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-white-50 small text-uppercase"><?= htmlspecialchars($k['name']) ?></span><br>
                <code class="fs-5 text-primary">Hidden for security (ID: <?= $k['id'] ?>)</code>
            </div>
            <span class="text-muted small">Generated: <?= $k['created_at'] ?></span>
        </div>
        <?php endforeach; ?>

        <form action="<?= APP_URL ?>/index.php?page=api&action=generate" method="POST" class="mt-4 border-top border-secondary pt-3">
            <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
            <div class="input-group mb-3" style="max-width: 400px;">
                <input type="text" name="name" class="form-control bg-dark text-white border-secondary" placeholder="Key Label (e.g. Mobile App)" required>
                <button type="submit" class="btn btn-primary">Generate New Key</button>
            </div>
        </form>
        
        <div class="mt-4">
            <h6>Example Request</h6>
            <pre class="bg-darker p-3 rounded border border-secondary text-success"><code>curl -H "Authorization: Bearer YOUR_API_KEY" <?= APP_URL ?>/index.php?page=api&action=v1_products</code></pre>
        </div>
    </div>
</div>
EOF

cat << 'EOF' > /var/www/inventory/views/hr/index.php
<div class="row g-4 mt-2">
    <div class="col-md-4">
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
</div>

<div class="card bg-dark text-white shadow-sm border-secondary mt-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-id-badge text-primary me-2"></i> Employee Directory</h5>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal"><i class="fas fa-plus"></i> Add Employee</button>
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
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($employees)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No employees found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($employees as $emp): ?>
                        <tr>
                            <td class="ps-3 fw-bold"><?= htmlspecialchars($emp['name']) ?></td>
                            <td><?= htmlspecialchars($emp['designation']) ?></td>
                            <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2"><?= htmlspecialchars($emp['status']) ?></span></td>
                            <td><?= date('d M Y', strtotime($emp['joined_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" data-bs-theme="dark">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white border-secondary">
      <form action="<?= APP_URL ?>/index.php?page=hr&action=add" method="POST">
      <div class="modal-header border-secondary">
        <h5 class="modal-title">Add Employee</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control bg-dark text-white border-secondary" required>
        </div>
        <div class="mb-3">
            <label>Designation</label>
            <input type="text" name="designation" class="form-control bg-dark text-white border-secondary" required>
        </div>
        <div class="mb-3">
            <label>Date Joined</label>
            <input type="date" name="joined_date" class="form-control bg-dark text-white border-secondary" required>
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="submit" class="btn btn-primary">Save Employee</button>
      </div>
      </form>
    </div>
  </div>
</div>
EOF

echo "API and HR modules successfully written to GCP environment and DB updated."
