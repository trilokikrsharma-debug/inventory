<?php
/**
 * Base Controller Class
 * 
 * All controllers extend this class for common functionality
 * including view rendering, redirects, and JSON responses.
 */
class Controller {

    /**
     * Whitelist of public actions callable via the router.
     * Each child controller MUST define this array.
     * If missing, only 'index' is allowed (safe default).
     *
     * @var array
     */
    protected $allowedActions = ['index'];

    /**
     * Get the list of actions that the router may dispatch to.
     * Called by the front controller (index.php) before invoking an action.
     *
     * @return array
     */
    public function getAllowedActions() {
        return $this->allowedActions;
    }

    /**
     * Render a view with layout
     */
    protected function view($viewPath, $data = []) {
        // Extract data to make variables accessible in views
        // SECURITY: EXTR_SKIP prevents $data keys from overwriting existing variables ($this, $cspNonce, etc.)
        extract($data, EXTR_SKIP);
        
        // Get company settings for all views
        $settingsModel = new SettingsModel();
        $company = $settingsModel->getSettings();
        
        // Get current user
        $currentUser = Session::get('user');
        
        // CSRF Token
        $csrfToken = CSRF::generateToken();
        
        // CSP Nonce (generated in index.php, needed for inline scripts in views)
        $cspNonce = $GLOBALS['csp_nonce'] ?? '';
        
        // Start output buffering for content
        ob_start();
        
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $viewPath) . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "<div class='alert alert-danger'>View not found: {$viewPath}</div>";
        }
        
        $content = ob_get_clean();
        
        // Check if it's an AJAX request
        if ($this->isAjax()) {
            echo $content;
            return;
        }
        
        // Include the layout
        require VIEW_PATH . '/layouts/main.php';
    }

    /**
     * Render a view without layout (for login, print, etc.)
     */
    protected function renderPartial($viewPath, $viewData = []) {
        extract($viewData, EXTR_SKIP);
        $settingsModel = new SettingsModel();
        $company = $settingsModel->getSettings();
        $csrfToken = CSRF::generateToken();
        $cspNonce = $GLOBALS['csp_nonce'] ?? '';
        
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $viewPath) . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "View not found: {$viewPath}";
        }
    }

    /**
     * Get sanitized $_GET filters safe for view rendering.
     * Prevents reflected XSS when filter values are echoed in views.
     */
    protected function safeFilters(): array {
        return array_map(
            fn($v) => is_string($v) ? htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $v,
            $_GET
        );
    }

    /**
     * Redirect to URL
     */
    protected function redirect($url) {
        $target = $this->normalizeInternalRedirectTarget($url, 'index.php?page=dashboard');
        header("Location: " . APP_URL . '/' . ltrim($target, '/'));
        exit;
    }

    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request is POST
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Get POST data with optional sanitization
     */
    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return isset($_POST[$key]) ? (is_array($_POST[$key]) ? $_POST[$key] : trim($_POST[$key])) : $default;
    }

    /**
     * Get GET data
     */
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
    }

    /**
     * Set flash message
     */
    protected function setFlash($type, $message) {
        Session::setFlash($type, $message);
    }

    /**
     * Require authentication
     */
    protected function requireAuth() {
        if (!Session::isLoggedIn()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Please login to continue.'], 401);
            }
            Session::setFlash('error', 'Please login to continue.');
            $this->redirect('index.php?page=login');
        }
    }

    /**
     * Require admin role
     */
    protected function requireAdmin() {
        $this->requireAuth();
        if (Session::get('user')['role'] !== 'admin') {
            Session::setFlash('error', 'Access denied. Admin privileges required.');
            $this->redirect('index.php?page=dashboard');
        }
    }

    /**
     * Authorize access to a specific record.
     *
     * - Admin users: always allowed.
     * - Non-admin users: allowed only if they created the record (created_by matches).
     * - Records without created_by (customers, suppliers): admin-only for edit,
     *   but view is allowed for all authenticated users.
     *
     * On failure, sets a generic flash error (does NOT reveal whether the record exists)
     * and redirects to the given URL.
     *
     * @param array|null $record      The loaded record array (must contain 'created_by' if applicable)
     * @param string     $redirectUrl URL to redirect to on failure
     * @param bool       $allowView   If true, allows non-admin users to view shared resources
     *                                (customers, suppliers) even without created_by match
     * @return bool True if authorized (never returns false — redirects instead)
     */
    protected function authorizeRecordAccess($record, $redirectUrl, $allowView = false) {
        // Record doesn't exist — use generic message to prevent enumeration
        if (!$record) {
            $this->setFlash('error', 'Record not found.');
            $this->redirect($redirectUrl);
            // redirect() calls exit, but return for clarity
            return false;
        }

        // Admins always have full access
        if (Session::isAdmin()) {
            return true;
        }

        // Shared resources without created_by (e.g., customers, suppliers)
        // allow view access but block modification
        if (!isset($record['created_by'])) {
            if ($allowView) {
                return true;
            }
            $this->setFlash('error', 'Record not found.');
            $this->redirect($redirectUrl);
            return false;
        }

        // Non-admin: check ownership via created_by
        $currentUserId = (int)(Session::get('user')['id'] ?? 0);
        if ((int)$record['created_by'] === $currentUserId) {
            return true;
        }

        // Unauthorized — generic message, no record existence leak
        $this->setFlash('error', 'Record not found.');
        $this->redirect($redirectUrl);
        return false;
    }

    /**
     * Check if current user owns a record (or is admin).
     * Lightweight boolean check — does NOT redirect.
     *
     * @param array $record The record array with 'created_by' field
     * @return bool
     */
    protected function isRecordOwner($record) {
        if (Session::isAdmin()) return true;
        if (!isset($record['created_by'])) return false;
        return (int)$record['created_by'] === (int)(Session::get('user')['id'] ?? 0);
    }

    /**
     * Validate CSRF token
     */
    protected function validateCSRF() {
        $token = $this->post(CSRF_TOKEN_NAME);
        if ($token === null || $token === '') {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        if (!CSRF::validateToken($token)) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
            $this->redirect($this->normalizeInternalRedirectTarget($referer, 'index.php?page=dashboard'));
        }
    }

    /**
     * Sanitize input
     */
    protected function sanitize($value) {
        if ($value === null || is_array($value)) {
            return '';
        }
        $clean = Helper::decodeHtmlEntities((string)$value);
        $clean = strip_tags($clean);
        return trim($clean);
    }

    /**
     * Normalize payment methods to DB-supported enum values.
     * Maps legacy/new UI values like "upi" into "online".
     */
    protected function normalizePaymentMethod($method, $default = 'cash') {
        $raw = strtolower(trim((string)$method));
        if ($raw === 'upi') {
            return 'online';
        }
        $allowed = ['cash', 'bank', 'cheque', 'online', 'other'];
        return in_array($raw, $allowed, true) ? $raw : $default;
    }

    /**
     * Log activity (fault-tolerant — never crashes main flow)
     *
     * @param string      $action      Action description
     * @param string|null $module      Module name (e.g. 'sales', 'users')
     * @param int|null    $referenceId Entity ID
     * @param string|null $details     Extra details or change summary
     */
    protected function logActivity($action, $module = null, $referenceId = null, $details = null) {
        try {
            $user = Session::get('user');
            $db = Database::getInstance();
            $cid = Tenant::id() ?? ($user['company_id'] ?? null);

            $db->query(
                "INSERT INTO activity_log (company_id, user_id, action, module, reference_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $cid,
                    $user['id'] ?? null,
                    $action,
                    $module,
                    $referenceId,
                    is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]
            );

            // ── Auto-Cleanup Strategy ──
            // 2% chance to trigger garbage collection for old logs to prevent SaaS table bloat.
            if ($cid && mt_rand(1, 50) === 1) {
                // Keep last 90 days. Done asynchronously from the user's perspective generally (though in-band here).
                $db->query("DELETE FROM activity_log WHERE company_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)", [$cid]);
            }
        } catch (\Exception $e) {
            // Logging must NEVER break the main flow
            error_log('[AuditLog] Failed to log activity: ' . $e->getMessage() . ' | action=' . $action);
        }
    }

    /**
     * Guard method for demo mode — prevents write operations.
     * Call at the start of any mutation action for fine-grained control.
     * Returns true if blocked (caller should return early).
     * 
     * @return bool True if demo and action was blocked
     */
    protected function demoGuard() {
        if (Tenant::isDemo()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Demo mode: This action is disabled. Sign up for a free account!'], 403);
            }
            $this->setFlash('warning', 'Demo mode: Changes are not saved. Sign up for a free account!');
            $this->redirect('index.php?page=dashboard');
            return true;
        }
        return false;
    }

    // =========================================================
    // RBAC Permission Guard
    // =========================================================

    /**
     * Require a specific permission to proceed.
     *
     * @param string $permission  Permission name (e.g. 'sales.create')
     * @return bool  Always true if execution continues (stops on failure)
     */
    protected function requirePermission($permission) {
        $this->requireAuth();

        if (Session::hasPermission($permission)) {
            return true;
        }
        
        if ($this->isAjax()) {
            $this->json(['success' => false, 'message' => 'You do not have permission to perform this action.'], 403);
        }

        Session::setFlash('error', 'You do not have permission to perform this action.');
        $this->redirect('index.php?page=dashboard');
    }

    /**
     * Require a specific SaaS plan feature to proceed.
     *
     * @param string $feature  Feature name (e.g. 'advanced_reports')
     */
    protected function requireFeature($feature) {
        $this->requireAuth();
        if (Session::isSuperAdmin()) {
            return;
        }
        if (Tenant::id() !== null) {
            if (!Tenant::canUse($feature)) {
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'message' => 'This feature is not available on your current plan.'], 403);
                }
                Session::setFlash('error', 'This feature is not available on your current plan. Please upgrade to access.');
                $this->redirect('index.php?page=pricing');
            }
        }
    }

    /**
     * Require super-admin privilege to proceed.
     * Use for platform-level operations: full DB backup, restore,
     * system settings, global admin tools.
     *
     * SECURITY: Reads ONLY from server-side session. Never from POST/GET.
     * Fails safe (default deny).
     *
     * @return bool  Always true if execution continues (stops on failure)
     */
    protected function requireSuperAdmin() {
        $this->requireAuth();

        if (Session::isSuperAdmin()) {
            return true;
        }

        if ($this->isAjax()) {
            $this->json(['success' => false, 'message' => 'This action requires super admin privileges.'], 403);
        }

        Session::setFlash('error', 'Access denied. Super admin privileges required.');
        $this->redirect('index.php?page=dashboard');
    }

    /**
     * Check permission without redirect (returns bool).
     *
     * @param string $permission  Permission name
     * @return bool
     */
    protected function canDo($permission) {
        return Session::hasPermission($permission);
    }

    /**
     * Normalize redirect targets to internal locations only.
     */
    private function normalizeInternalRedirectTarget(string $url, string $fallback = 'index.php?page=dashboard'): string {
        $target = trim($url);
        if ($target === '') {
            return $fallback;
        }

        $target = str_replace(["\r", "\n", "\0"], '', $target);
        $base = defined('APP_URL') ? rtrim((string)APP_URL, '/') : '';
        if ($base !== '' && str_starts_with($target, $base)) {
            $target = ltrim(substr($target, strlen($base)), '/');
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $target) || str_starts_with($target, '//')) {
            return $fallback;
        }

        $target = ltrim($target, '/');
        return $target !== '' ? $target : $fallback;
    }
}
