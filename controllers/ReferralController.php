<?php
/**
 * Referral management (super admin)
 */
class ReferralController extends Controller {
    protected $allowedActions = [
        'index',
        'rewards',
        'approve_reward',
        'reject_reward',
        'save_rule',
    ];

    private Referral $referralModel;
    private ?ReferralAdminService $referralAdminService = null;

    public function __construct() {
        $this->requireSuperAdmin();
        $this->referralModel = new Referral();
    }

    public function index() {
        $status = trim((string)$this->get('status', ''));
        $viewData = $this->workflowService()->buildIndexViewData($status);

        $this->view('platform.referrals', [
            'pageTitle' => 'Referrals',
            'status' => $viewData['status'],
            'referrals' => $viewData['referrals'],
            'rules' => $viewData['rules'],
            'activeRule' => $viewData['activeRule'],
        ]);
    }

    public function rewards() {
        $rewards = $this->workflowService()->listRewards();

        $this->view('platform.referral-rewards', [
            'pageTitle' => 'Referral Rewards',
            'rewards' => $rewards,
        ]);
    }

    public function approve_reward() {
        if (!$this->isPost()) {
            $this->redirect('index.php?page=referrals');
            return;
        }
        $this->validateCSRF();
        if ($this->demoGuard()) {
            return;
        }

        $result = $this->workflowService()->approveReward(
            (int)$this->post('referral_id'),
            (string)$this->post('note', 'Manual approval')
        );

        if ($result['success']) {
            $this->logActivity('Referral reward approved', 'referrals', $result['id'], $result['note']);
            $this->setFlash('success', $result['message']);
        } else {
            $this->setFlash('error', $result['message']);
        }
        $this->redirect('index.php?page=referrals');
    }

    public function reject_reward() {
        if (!$this->isPost()) {
            $this->redirect('index.php?page=referrals');
            return;
        }
        $this->validateCSRF();
        if ($this->demoGuard()) {
            return;
        }

        $result = $this->workflowService()->rejectReward(
            (int)$this->post('referral_id'),
            (string)$this->post('note', 'Manual rejection')
        );

        if ($result['success']) {
            $this->logActivity('Referral reward rejected', 'referrals', $result['id'], $result['note']);
            $this->setFlash('success', $result['message']);
        } else {
            $this->setFlash('error', $result['message']);
        }
        $this->redirect('index.php?page=referrals');
    }

    public function save_rule() {
        if (!$this->isPost()) {
            $this->redirect('index.php?page=referrals');
            return;
        }
        $this->validateCSRF();
        if ($this->demoGuard()) {
            return;
        }

        $result = $this->workflowService()->saveRule($this->post());

        if ($result['success']) {
            $this->logActivity(
                $result['action'] === 'updated' ? 'Referral reward rule updated' : 'Referral reward rule created',
                'referrals',
                (int)$result['rule_id']
            );
            $this->setFlash('success', 'Referral reward rule saved.');
        } else {
            $this->setFlash('error', $result['message'] ?? 'Failed to save referral rule.');
        }
        $this->redirect('index.php?page=referrals');
    }

    private function workflowService(): ReferralAdminService {
        if ($this->referralAdminService === null) {
            $this->referralAdminService = new ReferralAdminService($this->referralModel);
        }

        return $this->referralAdminService;
    }
}
