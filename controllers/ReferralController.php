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

    public function __construct() {
        $this->requireSuperAdmin();
        $this->referralModel = new Referral();
    }

    public function index() {
        $status = trim((string)$this->get('status', ''));
        $referrals = $this->referralModel->listReferrals($status);
        $rules = $this->referralModel->listRewardRules();
        $activeRule = $this->referralModel->getActiveRewardRule();

        $this->view('platform.referrals', [
            'pageTitle' => 'Referrals',
            'status' => $status,
            'referrals' => $referrals,
            'rules' => $rules,
            'activeRule' => $activeRule,
        ]);
    }

    public function rewards() {
        $rewards = $this->referralModel->listRewards();

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

        $referralId = (int)$this->post('referral_id');
        $note = trim((string)$this->post('note', 'Manual approval'));
        $ok = $this->referralModel->approveReward($referralId, $note);

        if ($ok) {
            $this->logActivity('Referral reward approved', 'referrals', $referralId, $note);
            $this->setFlash('success', 'Referral reward approved successfully.');
        } else {
            $this->setFlash('error', 'Failed to approve referral reward.');
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

        $referralId = (int)$this->post('referral_id');
        $note = trim((string)$this->post('note', 'Manual rejection'));
        $ok = $this->referralModel->rejectReward($referralId, $note);

        if ($ok) {
            $this->logActivity('Referral reward rejected', 'referrals', $referralId, $note);
            $this->setFlash('success', 'Referral reward rejected.');
        } else {
            $this->setFlash('error', 'Failed to reject referral reward.');
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

        $id = (int)$this->post('id');
        $id = $id > 0 ? $id : null;
        $result = $this->referralModel->saveRewardRule($this->post(), $id);

        if ($result['success']) {
            $this->logActivity(
                $id ? 'Referral reward rule updated' : 'Referral reward rule created',
                'referrals',
                (int)$result['id']
            );
            $this->setFlash('success', 'Referral reward rule saved.');
        } else {
            $this->setFlash('error', $result['message'] ?? 'Failed to save referral rule.');
        }
        $this->redirect('index.php?page=referrals');
    }
}

