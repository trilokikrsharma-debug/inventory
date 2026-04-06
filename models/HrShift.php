<?php
/**
 * HR shift master.
 */
class HrShift extends Model {
    protected $table = 'hr_shifts';

    public function allOrdered(): array {
        return $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
             ORDER BY is_default DESC, shift_name ASC, id ASC",
            [Tenant::require()]
        )->fetchAll();
    }

    public function defaultShiftId(): ?int {
        $row = $this->db->query(
            "SELECT id
             FROM {$this->table}
             WHERE company_id = ?
               AND is_default = 1
             ORDER BY id ASC
             LIMIT 1",
            [Tenant::require()]
        )->fetch();

        return $row ? (int)$row['id'] : null;
    }

    public function setDefault(int $id): void {
        $this->db->beginTransaction();
        try {
            $this->db->query(
                "UPDATE {$this->table}
                 SET is_default = 0, updated_at = NOW()
                 WHERE company_id = ?",
                [Tenant::require()]
            );
            $this->db->query(
                "UPDATE {$this->table}
                 SET is_default = 1, updated_at = NOW()
                 WHERE company_id = ?
                   AND id = ?",
                [Tenant::require(), $id]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
