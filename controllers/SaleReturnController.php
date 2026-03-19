<?php
/**
 * Sale Return Controller
 */
class SaleReturnController extends Controller {

    protected $allowedActions = ['index', 'create', 'detail'];

    public function index() {
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
        $this->requirePermission('returns.create');

        // If sale_id is passed, fetch that specific sale
        $saleId    = (int)$this->get('sale_id', 0);
        $salesModel = new SalesModel();
        $returnModel = new SaleReturnModel();
        $sale       = $saleId ? $salesModel->getWithDetails($saleId) : null;
        if ($sale) {
            $summary = $returnModel->getSaleReturnSummary($saleId);
            $remainingAmount = max(0, (float)$sale['grand_total'] - (float)($summary['returned_amount'] ?? 0));
            if ($remainingAmount <= 0.009) {
                $this->setFlash('error', 'This invoice has already been fully returned.');
                $this->redirect('index.php?page=sales&action=view_sale&id=' . $saleId);
                return;
            }
        }

        if ($this->isPost()) {
            $this->validateCSRF();

            $saleId   = (int)$this->post('sale_id');
            $sale     = $salesModel->getWithDetails($saleId);
            if (!$sale) { $this->setFlash('error', 'Invalid sale.'); $this->redirect('index.php?page=sale_returns&action=create'); return; }
            $summary = $returnModel->getSaleReturnSummary($saleId);
            $remainingAmount = max(0, (float)$sale['grand_total'] - (float)($summary['returned_amount'] ?? 0));
            if ($remainingAmount <= 0.009) {
                $this->setFlash('error', 'This invoice has already been fully returned.');
                $this->redirect('index.php?page=sales&action=view_sale&id=' . $saleId);
                return;
            }

            $productIds = $this->post('product_id', []);
            $quantities = $this->post('quantity', []);
            $unitPrices = $this->post('unit_price', []);

            $items       = [];
            $totalAmount = 0;
            foreach ($productIds as $i => $pid) {
                $qty = (float)($quantities[$i] ?? 0);
                $up  = (float)($unitPrices[$i] ?? 0);

                if ($qty < 0 || $up < 0) {
                    error_log("Invalid input values in SaleReturn: qty=$qty, price=$up");
                    $this->setFlash('error', 'Invalid quantities or prices provided. Values must be positive.');
                    $this->redirect('index.php?page=sale_returns&action=create&sale_id=' . $saleId);
                    return;
                }
                if ($qty <= 0 || !$pid) continue;
                $total        = $qty * $up;
                $totalAmount += $total;
                $items[] = ['product_id' => (int)$pid, 'quantity' => $qty, 'unit_price' => $up, 'total' => $total];
            }

            if (empty($items)) {
                $this->setFlash('error', 'Please add at least one item to return.');
                $this->redirect('index.php?page=sale_returns&action=create&sale_id=' . $saleId);
                return;
            }

            $returnData = [
                'return_number' => $returnModel->getNextReturnNumber(),
                'sale_id'       => $saleId,
                // customer_id is NOT stored here - model fetches it from sales table
                'total_amount'  => $totalAmount,
                'return_date'   => $this->post('return_date', date('Y-m-d')),
                'note'          => $this->sanitize($this->post('reason')),
            ];

            try {
                $userId   = Session::get('user')['id'];
                $returnId = $returnModel->createReturn($returnData, $items, $userId);
                $this->logActivity('Created sale return: ' . $returnData['return_number'], 'sale_returns', $returnId, 'Against sale #' . $saleId . ', Amount: ' . $totalAmount);
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
}
