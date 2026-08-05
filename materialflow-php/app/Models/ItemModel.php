<?php

namespace App\Models;

use App\Models\Traits\AutoUuid;
use CodeIgniter\Model;

class ItemModel extends Model
{
    use AutoUuid;

    protected $table            = 'items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['id', 'barcode', 'sku', 'name', 'category', 'unit', 'reorder_level', 'is_active'];
    protected $beforeInsert     = ['assignId'];

    public const UNITS = ['Nos', 'Kg', 'Meters', 'Liters', 'Boxes', 'Pairs'];

    public function findActive(string $id): ?array
    {
        return $this->where(['id' => $id, 'is_active' => 1])->first();
    }
}
