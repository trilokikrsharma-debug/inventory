<?php
/**
 * API Controller
 */
class ApiController extends Controller {

    protected $allowedActions = ['index', 'generate', 'revoke', 'products', 'customers', 'summary'];

    public function index() {
        $companyId = $this->requireTenantApiManagementAccess();
        $tokens = Database::getInstance()->query(
            "SELECT id, name, scopes, is_active, expires_at, last_used_at, created_at
             FROM api_tokens
             WHERE company_id = ?
             ORDER BY id DESC",
            [$companyId]
        )->fetchAll();

        $newToken = (string)Session::get('new_api_token', '');
        Session::remove('new_api_token');

        $this->view('api.index', [
            'pageTitle' => 'API Access',
            'tokens' => $tokens,
            'newToken' => $newToken,
            'availableScopes' => ApiAuth::AVAILABLE_SCOPES,
        ]);
    }

    public function generate() {
        $companyId = $this->requireTenantApiManagementAccess();
        
        if (!$this->isPost()) {
            $this->redirect('index.php?page=api');
            return;
        }

        $this->validateCSRF();

        $currentUser = Session::get('user') ?? [];
        $userId = (int)($currentUser['id'] ?? 0);
        $name = trim((string)$this->post('name', ''));
        if ($name === '') {
            $name = 'Default Integration';
        }
        $name = mb_substr($name, 0, 100);

        $fullAccess = (string)$this->post('full_access', '0') === '1';
        $scopes = $fullAccess ? ['*'] : ApiAuth::normalizeScopes((array)$this->post('scopes', []));
        if (!$fullAccess && $scopes === ['*']) {
            $this->setFlash('error', 'Select at least one scope or keep full access enabled.');
            $this->redirect('index.php?page=api');
            return;
        }
        $expiresAt = $this->resolveExpiryTimestamp($this->post('expiry_days', ''));

        try {
            $token = ApiAuth::generateToken($companyId, $userId, $name, $scopes, $expiresAt);
            Session::set('new_api_token', (string)($token['token'] ?? ''));
            $this->setFlash('success', 'API token generated successfully. Copy it now, it will not be shown again.');
        } catch (\Throwable $e) {
            Logger::error('Failed to generate API token', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->setFlash('error', 'Failed to generate API token. Please try again.');
        }

        $this->redirect('index.php?page=api');
    }

    public function revoke() {
        $companyId = $this->requireTenantApiManagementAccess();

        if (!$this->isPost()) {
            $this->redirect('index.php?page=api');
            return;
        }

        $this->validateCSRF();

        $tokenId = (int)$this->post('token_id', 0);
        if ($tokenId <= 0) {
            $this->setFlash('error', 'Invalid API token.');
            $this->redirect('index.php?page=api');
            return;
        }

        if (ApiAuth::revokeToken($tokenId, $companyId)) {
            $this->setFlash('success', 'API token revoked successfully.');
        } else {
            $this->setFlash('error', 'Unable to revoke API token.');
        }

        $this->redirect('index.php?page=api');
    }

    public function products() {
        $token = $this->authenticateApiRequest('catalog.read');
        $page = max(1, (int)$this->get('page', 1));
        $limit = max(1, min(100, (int)$this->get('limit', 25)));
        $search = trim((string)$this->get('search', ''));
        $categoryId = trim((string)$this->get('category_id', ''));

        $result = (new ProductModel())->getAllWithRelations($search, $categoryId, $page, $limit);

        $this->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => [
                'page' => (int)$result['page'],
                'per_page' => (int)$result['perPage'],
                'total' => (int)$result['total'],
                'total_pages' => (int)$result['totalPages'],
                'company_id' => (int)$token['company_id'],
            ],
        ]);
    }

    public function customers() {
        $token = $this->authenticateApiRequest('catalog.read');
        $page = max(1, (int)$this->get('page', 1));
        $limit = max(1, min(100, (int)$this->get('limit', 25)));
        $search = trim((string)$this->get('search', ''));

        $result = (new CustomerModel())->getAllPaginated($search, $page, $limit);

        $this->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => [
                'page' => (int)$result['page'],
                'per_page' => (int)$result['perPage'],
                'total' => (int)$result['total'],
                'total_pages' => (int)$result['totalPages'],
                'company_id' => (int)$token['company_id'],
            ],
        ]);
    }

    public function summary() {
        $token = $this->authenticateApiRequest('reports.read');
        $companyId = (int)$token['company_id'];
        $db = Database::getInstance();

        $salesToday = (float)$db->query(
            "SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE company_id = ? AND deleted_at IS NULL AND sale_date = CURDATE()",
            [$companyId]
        )->fetchColumn();
        $salesMonth = (float)$db->query(
            "SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE company_id = ? AND deleted_at IS NULL AND sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
            [$companyId]
        )->fetchColumn();
        $outstandingReceivables = (float)$db->query(
            "SELECT COALESCE(SUM(current_balance), 0) FROM customers WHERE company_id = ? AND deleted_at IS NULL AND current_balance > 0",
            [$companyId]
        )->fetchColumn();
        $lowStockCount = (int)$db->query(
            "SELECT COUNT(*) FROM products WHERE company_id = ? AND deleted_at IS NULL AND is_active = 1 AND current_stock <= COALESCE(low_stock_alert, 10)",
            [$companyId]
        )->fetchColumn();

        $this->json([
            'success' => true,
            'data' => [
                'sales_today' => SaaSBillingHelper::money($salesToday),
                'sales_month' => SaaSBillingHelper::money($salesMonth),
                'outstanding_receivables' => SaaSBillingHelper::money($outstandingReceivables),
                'low_stock_count' => $lowStockCount,
            ],
            'meta' => [
                'company_id' => $companyId,
                'generated_at' => date(DATE_ATOM),
            ],
        ]);
    }

    private function authenticateApiRequest(string $requiredScope): array {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        $token = ApiAuth::validateRequest();
        if (!$token) {
            $this->json(['success' => false, 'message' => 'Invalid API token.'], 401);
        }

        $company = Database::getInstance()->query(
            "SELECT * FROM companies WHERE id = ? AND status = 'active' LIMIT 1",
            [(int)$token['company_id']]
        )->fetch();
        if (!$company) {
            $this->json(['success' => false, 'message' => 'Tenant not found or inactive.'], 403);
        }

        Tenant::set((int)$token['company_id'], $company);

        if (!Tenant::canUse('api')) {
            $this->json(['success' => false, 'message' => 'API access is not available on this plan.'], 403);
        }

        if (!ApiAuth::hasScope($token, '*') && !ApiAuth::hasScope($token, $requiredScope)) {
            $this->json(['success' => false, 'message' => 'Insufficient API scope.'], 403);
        }

        return $token;
    }

    private function requireTenantApiManagementAccess(): int {
        $this->requirePermission('settings.manage');

        if (Session::isSuperAdmin() || Tenant::id() === null) {
            Session::setFlash('error', 'API token management is available only inside a tenant account.');
            $this->redirect('index.php?page=platform');
        }

        $this->requireFeature('api');

        return Tenant::require();
    }

    private function resolveExpiryTimestamp($input): ?string {
        $value = trim((string)$input);
        if ($value === '' || $value === 'never') {
            return null;
        }

        $days = (int)$value;
        $allowed = [1, 7, 30, 90, 365];
        if (!in_array($days, $allowed, true)) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
    }
}
