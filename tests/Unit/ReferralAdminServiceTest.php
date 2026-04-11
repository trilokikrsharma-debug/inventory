<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/models/Referral.php';
require_once dirname(__DIR__, 2) . '/services/ReferralAdminService.php';

if (!class_exists('ReferralAdminFakeModel')) {
    class ReferralAdminFakeModel extends Referral {
        public array $approveCalls = [];
        public array $rejectCalls = [];
        public array $saveCalls = [];
        public array $referrals = [];
        public array $rules = [];
        public ?array $activeRule = null;
        public array $rewards = [];
        public bool $approveResult = true;
        public bool $rejectResult = true;
        public array $saveResult = ['success' => true, 'id' => 8];

        public function __construct() {}

        public function listReferrals(string $status = ''): array {
            return $this->referrals;
        }

        public function listRewardRules(): array {
            return $this->rules;
        }

        public function getActiveRewardRule(): ?array {
            return $this->activeRule;
        }

        public function listRewards(): array {
            return $this->rewards;
        }

        public function approveReward(int $referralId, string $note = ''): bool {
            $this->approveCalls[] = ['id' => $referralId, 'note' => $note];
            return $this->approveResult;
        }

        public function rejectReward(int $referralId, string $note = ''): bool {
            $this->rejectCalls[] = ['id' => $referralId, 'note' => $note];
            return $this->rejectResult;
        }

        public function saveRewardRule(array $input, ?int $id = null): array {
            $this->saveCalls[] = ['input' => $input, 'id' => $id];
            return $this->saveResult;
        }
    }
}

class ReferralAdminServiceTest extends BaseTestCase {
    public function testApproveRewardUsesDefaultNoteAndSuccessMessage(): void {
        $model = new ReferralAdminFakeModel();
        $service = new ReferralAdminService($model);

        $result = $service->approveReward(5, '');

        $this->assertTrue($result['success']);
        $this->assertSame('Manual approval', $result['note']);
        $this->assertSame('Referral reward approved successfully.', $result['message']);
        $this->assertSame('Manual approval', $model->approveCalls[0]['note']);
    }

    public function testRejectRewardReturnsFailureMessage(): void {
        $model = new ReferralAdminFakeModel();
        $model->rejectResult = false;
        $service = new ReferralAdminService($model);

        $result = $service->rejectReward(7, 'Bad referral');

        $this->assertFalse($result['success']);
        $this->assertSame('Failed to reject referral reward.', $result['message']);
        $this->assertSame('Bad referral', $model->rejectCalls[0]['note']);
    }

    public function testSaveRuleReportsUpdateAction(): void {
        $model = new ReferralAdminFakeModel();
        $service = new ReferralAdminService($model);

        $result = $service->saveRule([
            'id' => '12',
            'name' => 'Updated Rule',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('updated', $result['action']);
        $this->assertSame(12, $model->saveCalls[0]['id']);
        $this->assertSame(8, $result['rule_id']);
    }
}
