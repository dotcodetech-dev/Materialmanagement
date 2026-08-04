<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Ledger and stock queries shared by the dashboard, items page, scan flow,
 * ledger, and reports. Stock is always derived from movements — never stored.
 */
class StockService
{
    public const INWARD_TYPES  = ['INWARD', 'RETURN_IN', 'ADJUSTMENT_IN'];
    public const OUTWARD_TYPES = ['OUTWARD', 'RETURN_OUT', 'ADJUSTMENT_OUT'];
    public const ALL_TYPES     = ['INWARD', 'OUTWARD', 'RETURN_IN', 'RETURN_OUT', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT'];

    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Active items with their ledger-derived balance, optionally filtered.
     *
     * @return list<array>
     */
    public function itemsWithBalance(?string $q = null): array
    {
        $builder = $this->db->table('item_stock_balance isb')
            ->select('isb.item_id, isb.barcode, isb.sku, isb.name, isb.category, isb.unit, isb.reorder_level, isb.available_quantity')
            ->join('items i', 'i.id = isb.item_id')
            ->where('i.is_active', 1)
            ->orderBy('isb.name');

        if ($q !== null && trim($q) !== '') {
            $like = trim($q);
            $builder->groupStart()
                ->like('isb.name', $like)
                ->orLike('isb.barcode', $like)
                ->orLike('isb.sku', $like)
                ->orLike('isb.category', $like)
                ->groupEnd();
        }

        return array_map([self::class, 'castItemRow'], $builder->get()->getResultArray());
    }

    public function availableQuantity(string $itemId): float
    {
        $row = $this->db->table('item_stock_balance')
            ->select('available_quantity')
            ->where('item_id', $itemId)
            ->get()->getRowArray();

        return (float) ($row['available_quantity'] ?? 0);
    }

    /**
     * Movement history with item/customer/user names.
     *
     * @param array{type?: string, from?: string, to?: string, recorded_by?: string, category?: string} $filters
     *
     * @return list<array>
     */
    public function movements(array $filters = [], int $limit = 200): array
    {
        $builder = $this->db->table('stock_movements sm')
            ->select('sm.id, sm.item_id, sm.customer_id, sm.movement_type, sm.quantity, sm.reference_number, sm.notes, sm.occurred_at,'
                . ' i.name AS item_name, i.barcode AS item_barcode, i.unit AS item_unit, i.category AS item_category,'
                . ' c.name AS customer_name, u.full_name AS recorded_by_name')
            ->join('items i', 'i.id = sm.item_id')
            ->join('customers c', 'c.id = sm.customer_id', 'left')
            ->join('app_users u', 'u.id = sm.recorded_by', 'left')
            ->orderBy('sm.occurred_at', 'DESC')
            ->limit(max(1, min($limit, 5000)));

        if (($filters['type'] ?? '') === 'inward') {
            $builder->whereIn('sm.movement_type', self::INWARD_TYPES);
        } elseif (($filters['type'] ?? '') === 'outward') {
            $builder->whereIn('sm.movement_type', self::OUTWARD_TYPES);
        }
        if (! empty($filters['from'])) {
            $builder->where('sm.occurred_at >=', $filters['from'] . ' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $builder->where('sm.occurred_at <', date('Y-m-d', strtotime($filters['to'] . ' +1 day')) . ' 00:00:00');
        }
        if (! empty($filters['recorded_by'])) {
            $builder->where('u.full_name', $filters['recorded_by']);
        }
        if (! empty($filters['category'])) {
            $builder->where('i.category', $filters['category']);
        }

        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['quantity'] = (float) $row['quantity'];
        }

        return $rows;
    }

    /**
     * Insert a movement inside an existing transaction (or standalone).
     * Returns the new movement id. Caller must have validated inputs.
     */
    public function insertMovement(array $data): string
    {
        helper('uuid');
        $id = uuid_v4();

        $this->db->table('stock_movements')->insert([
            'id'               => $id,
            'item_id'          => $data['item_id'],
            'customer_id'      => $data['customer_id'] ?? null,
            'movement_type'    => $data['movement_type'],
            'quantity'         => $data['quantity'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'recorded_by'      => $data['recorded_by'] ?? null,
        ]);

        return $id;
    }

    /**
     * Dashboard stats: item count, total units, low-stock list, today's movement count, recent activity.
     */
    public function dashboard(): array
    {
        $items = $this->itemsWithBalance();

        $low        = array_values(array_filter($items, static fn ($i) => $i['available_quantity'] <= $i['reorder_level']));
        $stockTotal = array_sum(array_column($items, 'available_quantity'));

        $today = (int) ($this->db->table('stock_movements')
            ->selectCount('id')
            ->where('occurred_at >=', date('Y-m-d') . ' 00:00:00')
            ->get()->getRowArray()['id'] ?? 0);

        return [
            'items'      => $items,
            'low'        => $low,
            'stockTotal' => $stockTotal,
            'todayCount' => $today,
            'recent'     => $this->movements([], 6),
        ];
    }

    /**
     * Next free MF-##### item barcode, computed over ALL items (active or not).
     */
    public function nextItemBarcode(): string
    {
        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING(barcode, 4) AS UNSIGNED)) AS maxnum FROM items WHERE barcode REGEXP '^MF-[0-9]+$'"
        )->getRowArray();

        $num = (int) ($row['maxnum'] ?? 0) + 1;

        return 'MF-' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    public static function isInward(string $type): bool
    {
        return in_array($type, self::INWARD_TYPES, true);
    }

    private static function castItemRow(array $row): array
    {
        $row['reorder_level']      = (float) $row['reorder_level'];
        $row['available_quantity'] = (float) $row['available_quantity'];

        return $row;
    }
}
