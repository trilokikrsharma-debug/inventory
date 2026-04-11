<?php
class ReferralAdminService {
    private Referral $referralModel;

    public function __construct(?Referral $referralModel = null) {
        $this->referralModel = $referralModel ?: new Referral();
    }

    public function buildIndexViewData(string $status): array {
        return [
            'status' => $status,
            'referrals' => $this->referralModel->listReferrals($status),
            'rules' => $this->referralModel->listRewardRules(),
            'activeRule' => $this->referralModel->getActiveRewardRule(),
        ];
    }

    public function listRewards(): array {
        return $this->referralModel->listRewards();
    }

    public function approveReward(int $referralId, ?string $note = null): array {
        $normalizedNote = $this->normalizeNote($note, 'Manual approval');
        $success = $this->referralModel->approveReward($referralId, $normalizedNote);

        return [
            'success' => $success,
            'id' => $referralId,
            'note' => $normalizedNote,
            'message' => $success ? 'Referral reward approved successfully.' : 'Failed to approve referral reward.',
        ];
    }

    public function rejectReward(int $referralId, ?string $note = null): array {
        $normalizedNote = $this->normalizeNote($note, 'Manual rejection');
        $success = $this->referralModel->rejectReward($referralId, $normalizedNote);

        return [
            'success' => $success,
            'id' => $referralId,
            'note' => $normalizedNote,
            'message' => $success ? 'Referral reward rejected.' : 'Failed to reject referral reward.',
        ];
    }

    public function saveRule(array $input): array {
        $id = max(0, (int)($input['id'] ?? 0));
        $result = $this->referralModel->saveRewardRule($input, $id > 0 ? $id : null);
        $result['rule_id'] = (int)($result['id'] ?? $id);
        $result['action'] = $id > 0 ? 'updated' : 'created';
        return $result;
    }

    private function normalizeNote(?string $note, string $default): string {
        $normalized = trim((string)$note);
        return $normalized !== '' ? $normalized : $default;
    }
}
