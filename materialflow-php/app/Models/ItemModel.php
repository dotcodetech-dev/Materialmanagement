<?php

namespace App\Models;

use CodeIgniter\Model;

class ItemModel extends Model
{
    protected $table            = 'items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['id', 'barcode', 'sku', 'name', 'category', 'unit', 'reorder_level', 'is_active'];
    protected $beforeInsert     = ['assignId'];

    public const UNITS = ['Nos', 'Kg', 'Meters', 'Liters', 'Boxes', 'Pairs'];

    protected function assignId(array $data): array
    {
        if (empty($data['data']['id'])) {
            helper('uuid');
            $data['data']['id'] = uuid_v4();
        }

        return $data;
    }

    public function findActive(string $id): ?array
    {
        return $this->where(['id' => $id, 'is_active' => 1])->first();
    }
}
