<?php
/**
 * Payment Controller
 *
 * Handles payment entries (to suppliers) and receipts (from customers).
 */
class PaymentController extends Controller {

    protected $allowedActions = ['index', 'create', 'view_payment', 'delete'];
    private ?PaymentWorkflowService $paymentWorkflowService = null;

    public function index() {
        $this->requirePermission('payments.view');
        $type = $this->get('type', '');
        $payments = (new PaymentModel())->getAllPaginated(
            $type,
            $this->get('search', ''),
            $this->get('from_date', ''),
            $this->get('to_date', ''),
            max(1, (int)$this->get('pg', 1))
        );

        $this->view('payments.index', [
            'pageTitle' => 'Payments & Receipts',
            'payments'  => $payments,
            'type'      => $type,
            'filters'   => $this->safeFilters(),
        ]);
    }

    public function create() {
        $this->requirePermission('payments.create');

        if ($this->isPost()) {
            $this->validateCSRF();
            $type = $this->post('type');

            try {
                $result = $this->workflowService()->createPayment($this->post(), (int)(Session::get('user')['id'] ?? 0));
                $this->logActivity('Created ' . $result['type'] . ': ' . $result['payment_number'], 'payments', $result['id']);
                $this->setFlash('success', ucfirst($result['type']) . ' recorded successfully.');
                $this->redirect('index.php?page=payments');
            } catch (Exception $e) {
                error_log($e->getMessage());
                $this->setFlash('error', 'An unexpected error occurred. Please try again.');
                $this->redirect('index.php?page=payments&action=create&type=' . $type);
            }
        }

        $type = $this->get('type', 'receipt');
        $customers = (new CustomerModel())->allActive();
        $suppliers = (new SupplierModel())->allActive();

        $this->view('payments.create', [
            'pageTitle' => $type === 'receipt' ? 'New Receipt' : 'New Payment',
            'type'      => $type,
            'customers' => $customers,
            'suppliers' => $suppliers,
        ]);
    }

    public function view_payment() {
        $this->requirePermission('payments.view');
        $id = (int)$this->get('id');
        $payment = (new PaymentModel())->getWithDetails($id);
        $this->authorizeRecordAccess($payment, 'index.php?page=payments');

        $this->view('payments.view', ['pageTitle' => 'Payment Details', 'payment' => $payment]);
    }

    public function delete() {
        $this->requirePermission('payments.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=payments'); }
        $this->validateCSRF();

        $paymentId = (int)$this->post('id');
        try {
            $payment = (new PaymentModel())->getWithDetails($paymentId);
            (new PaymentModel())->deletePayment($paymentId);
            $this->logActivity('Deleted payment: ' . ($payment['payment_number'] ?? $paymentId), 'payments', $paymentId, 'Amount: ' . ($payment['amount'] ?? 0));
            $this->setFlash('success', 'Payment deleted. Sale/Purchase status and balances recalculated.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            $this->setFlash('error', 'An unexpected error occurred. Please try again.');
        }
        $this->redirect('index.php?page=payments');
    }

    private function workflowService(): PaymentWorkflowService {
        if ($this->paymentWorkflowService === null) {
            $this->paymentWorkflowService = new PaymentWorkflowService();
        }

        return $this->paymentWorkflowService;
    }
}
