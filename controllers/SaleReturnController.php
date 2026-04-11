<?php
/**
 * Sale Return Controller
 */
class SaleReturnController extends Controller {

    protected $allowedActions = ['index', 'create', 'detail', 'cancel'];
    private ?SaleReturnWorkflowService $saleReturnWorkflowService = null;
    private ?SaleReturnLifecycleService $saleReturnLifecycleService = null;

    public function index() {
        $this->requireFeature('sale_returns');
        $this->requirePermission('returns.view');
        $search   = $this->get('search', '');
        $fromDate = $this->get('from_date', '');
        $toDate   = $this->get('to_date', '');
        $page     = max(1, (int)$this->get('pg', 1));
        $returns  = (new SaleReturnModel())->getAll($search, $fromDate, $toDate, $page);

        $this->view('sale_returns.index', [
            'pageTitle' => 'Sale Returns',
            'returns'   => $returns,
            'search'    => $search,
            'fromDate'  => $fromDate,
            'toDate'    => $toDate,
        ]);
    }

    public function create() {
        $this->requireFeature('sale_returns');
        $this->requirePermission('returns.create');

        // If sale_id is passed, fetch that specific sale
        $saleId    = (int)$this->get('sale_id', 0);
        $salesModel = new SalesModel();
        $returnModel = new SaleReturnModel();
        $sale       = $saleId ? $salesModel->getWithDetails($saleId) : null;
        if ($sale) {
            $summary = $returnModel->getSaleReturnSummary($saleId);
            $remainingAmount = max(0, (float)$sale['grand_total'] - (float)($summary['returned_amount'] ?? 0));
            try {
                $this->workflowService()->ensureSaleIsReturnable($sale, $remainingAmount);
            } catch (\InvalidArgumentException $e) {
                $this->setFlash('error', 'This invoice has already been fully returned.');
                $this->redirect('index.php?page=sales&action=view_sale&id=' . $saleId);
                return;
            }
        }

        if ($this->isPost()) {
            $this->validateCSRF();

            $saleId   = (int)$this->post('sale_id');
            $sale     = $salesModel->getWithDetails($saleId);
            $summary = $returnModel->getSaleReturnSummary($saleId);
            $remainingAmount = max(0, (float)$sale['grand_total'] - (float)($summary['returned_amount'] ?? 0));
            try {
                $this->workflowService()->ensureSaleIsReturnable($sale, $remainingAmount);
                $prepared = $this->workflowService()->buildCreatePayload(
                    $this->post(),
                    $sale,
                    $remainingAmount,
                    $returnModel->getNextReturnNumber()
                );
                $returnData = $prepared['return'];
                $items = $prepared['items'];
            } catch (\InvalidArgumentException $e) {
                $this->setFlash('error', $e->getMessage());
                $redirectTarget = $saleId > 0 ? 'index.php?page=sale_returns&action=create&sale_id=' . $saleId : 'index.php?page=sale_returns&action=create';
                if ($e->getMessage() === 'This invoice has already been fully returned.') {
                    $redirectTarget = 'index.php?page=sales&action=view_sale&id=' . $saleId;
                } elseif ($e->getMessage() === 'Invalid sale.') {
                    $redirectTarget = 'index.php?page=sale_returns&action=create';
                }
                $this->redirect($redirectTarget);
                return;
            }

            try {
                $userId   = Session::get('user')['id'];
                $returnId = $returnModel->createReturn($returnData, $items, $userId);
                $returnNumber = 'RET-' . str_pad((string)$returnId, 4, '0', STR_PAD_LEFT);
                $this->logActivity('Created sale return: ' . $returnNumber, 'sale_returns', $returnId, 'Against sale #' . $saleId . ', Amount: ' . $returnData['total_amount']);
                $this->setFlash('success', 'Sale return created. Stock restored and balances updated.');
                $this->redirect('index.php?page=sale_returns&action=detail&id=' . $returnId);
            } catch (Exception $e) {
                error_log($e->getMessage());
                $this->setFlash('error', $e->getMessage());
                $this->redirect('index.php?page=sale_returns&action=create&sale_id=' . $saleId);
            }
        }

        // Get recent sales for dropdown - show all sales with IN clause
        $recentSales = $returnModel->getRecentSalesForReturn();
        $this->view('sale_returns.create', [
            'pageTitle'   => 'New Sale Return',
            'sale'        => $sale,
            'recentSales' => $recentSales,
        ]);

    }

    public function detail() {
        $this->requirePermission('returns.view');
        $id     = (int)$this->get('id');
        $return = (new SaleReturnModel())->getWithDetails($id);
        $this->authorizeRecordAccess($return, 'index.php?page=sale_returns');
        $this->view('sale_returns.view', ['pageTitle' => 'Return Details', 'return' => $return]);
    }

    public function cancel() {
        $this->requireFeature('sale_returns');
        if (!$this->hasReturnCancelPermission()) {
            $this->setFlash('error', 'You do not have permission to perform this action.');
            $this->redirect('index.php?page=sale_returns');
            return;
        }
        if (!$this->isPost()) { $this->redirect('index.php?page=sale_returns'); }
        $this->validateCSRF();

        $id = (int)$this->post('id');
        try {
            $return = $this->lifecycleService()->cancelReturn($id, $this->post('cancel_reason', ''), (int)(Session::get('user')['id'] ?? 0));
            $this->logActivity('Cancelled sale return: ' . ($return['return_number'] ?? $id), 'sale_returns', $id, (string)($return['cancel_reason'] ?? ''));
            $this->setFlash('success', 'Sale return cancelled successfully. Stock and customer balances were recalculated.');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('index.php?page=sale_returns&action=detail&id=' . $id);
    }

    private function workflowService(): SaleReturnWorkflowService {
        if ($this->saleReturnWorkflowService === null) {
            $this->saleReturnWorkflowService = new SaleReturnWorkflowService();
        }

        return $this->saleReturnWorkflowService;
    }

    private function lifecycleService(): SaleReturnLifecycleService {
        if ($this->saleReturnLifecycleService === null) {
            $this->saleReturnLifecycleService = new SaleReturnLifecycleService();
        }

        return $this->saleReturnLifecycleService;
    }

    private function hasReturnCancelPermission(): bool {
        $this->requireAuth();
        return Session::hasPermission('returns.cancel') || Session::hasPermission('returns.create');
    }
}
