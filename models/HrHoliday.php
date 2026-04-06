<?php
/**
 * HR holiday calendar.
 */
class HrHoliday extends Model {
    protected $table = 'hr_holidays';

    public function findByDate(string $date): ?array {
        $row = $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
               AND holiday_date = ?
             ORDER BY id ASC
             LIMIT 1",
            [Tenant::require(), $date]
        )->fetch();

        return $row ?: null;
    }

    public function upcoming(int $limit = 12): array {
        return $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
               AND holiday_date >= CURDATE()
             ORDER BY holiday_date ASC, id ASC
             LIMIT {$limit}",
            [Tenant::require()]
        )->fetchAll();
    }

    public function listByYear(int $year): array {
        return $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
               AND YEAR(holiday_date) = ?
             ORDER BY holiday_date ASC, id ASC",
            [Tenant::require(), $year]
        )->fetchAll();
    }
}
