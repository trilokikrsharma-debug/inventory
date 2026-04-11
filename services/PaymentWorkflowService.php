<?php
class PaymentWorkflowService {
    private PaymentModel $paymentModel;
    private SettingsModel $settingsModel;

    public function __construct(?PaymentModel $paymentModel = null, ?SettingsModel $settingsModel = null) {
        $this->paymentModel = $paymentModel ?: new PaymentModel();
        $this->settingsModel = $settingsModel ?: new SettingsModel();
    }

    public function createPayment(array $input, int $userId): array {
        $type = $this->normalizeType($input['type'] ?? '');
        $prefix = $type === 'receipt' ? 'receipt' : 'payment';
        $paymentNumber = $this->settingsModel->getNextNumber($prefix);

        $payload = [
            'payment_number' => $paymentNumber,
            'type' => $type,
            'customer_id' => $type === 'receipt' ? (int)($input['customer_id'] ?? 0) : null,
            'supplier_id' => $type === 'payment' ? (int)($input['supplier_id'] ?? 0) : null,
            'sale_id' => $this->nullableInt($input['sale_id'] ?? null),
            'purchase_id' => $this->nullableInt($input['purchase_id'] ?? null),
            'amount' => max(0.0, round((float)($input['amount'] ?? 0), 2)),
            'payment_method' => $this->normalizePaymentMethod($input['payment_method'] ?? 'cash'),
            'payment_date' => trim((string)($input['payment_date'] ?? '')),
            'reference_number' => $this->sanitize($input['reference_number'] ?? null),
            'bank_name' => $this->sanitize($input['bank_name'] ?? null),
            'note' => $this->sanitize($input['note'] ?? null),
        ];

        $paymentId = $this->paymentModel->createPayment($payload, $userId);

        return [
            'id' => (int)$paymentId,
            'payment_number' => $paymentNumber,
            'type' => $type,
            'payload' => $payload,
        ];
    }

    private function sanitize($value): string {
        if ($value === null || is_array($value)) {
            return '';
        }

        $clean = Helper::decodeHtmlEntities((string)$value);
        $clean = strip_tags($clean);
        return trim($clean);
    }

    private function normalizePaymentMethod($method, string $default = 'cash'): string {
        $raw = strtolower(trim((string)$method));
        if ($raw === 'upi') {
            return 'online';
        }

        $allowed = ['cash', 'bank', 'cheque', 'online', 'other'];
        return in_array($raw, $allowed, true) ? $raw : $default;
    }

    private function normalizeType($type): string {
        return strtolower(trim((string)$type)) === 'payment' ? 'payment' : 'receipt';
    }

    private function nullableInt($value): ?int {
        $normalized = (int)$value;
        return $normalized > 0 ? $normalized : null;
    }
}
