<?php
/**
 * HR payroll statutory policy.
 */
class HrPayrollPolicy extends Model {
    protected $table = 'hr_payroll_policies';
    protected $softDelete = false;

    public function current(): array {
        $row = $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
             ORDER BY id DESC
             LIMIT 1",
            [Tenant::require()]
        )->fetch();

        return $row ?: [
            'enable_pf' => 0,
            'pf_rate' => 12.00,
            'pf_salary_cap' => 15000.00,
            'enable_esi' => 0,
            'esi_rate' => 0.75,
            'esi_salary_threshold' => 21000.00,
            'enable_tds' => 0,
            'tds_rate' => 10.00,
            'tds_annual_threshold' => 700000.00,
        ];
    }

    public function savePolicy(array $data): void {
        $existing = $this->db->query(
            "SELECT id
             FROM {$this->table}
             WHERE company_id = ?
             ORDER BY id DESC
             LIMIT 1",
            [Tenant::require()]
        )->fetch();

        $payload = [
            'enable_pf' => !empty($data['enable_pf']) ? 1 : 0,
            'pf_rate' => round((float)($data['pf_rate'] ?? 0), 2),
            'pf_salary_cap' => $data['pf_salary_cap'] !== null ? round((float)$data['pf_salary_cap'], 2) : null,
            'enable_esi' => !empty($data['enable_esi']) ? 1 : 0,
            'esi_rate' => round((float)($data['esi_rate'] ?? 0), 2),
            'esi_salary_threshold' => $data['esi_salary_threshold'] !== null ? round((float)$data['esi_salary_threshold'], 2) : null,
            'enable_tds' => !empty($data['enable_tds']) ? 1 : 0,
            'tds_rate' => round((float)($data['tds_rate'] ?? 0), 2),
            'tds_annual_threshold' => $data['tds_annual_threshold'] !== null ? round((float)$data['tds_annual_threshold'], 2) : null,
        ];

        if ($existing) {
            $this->update((int)$existing['id'], $payload);
            return;
        }

        $this->create($payload);
    }
}
