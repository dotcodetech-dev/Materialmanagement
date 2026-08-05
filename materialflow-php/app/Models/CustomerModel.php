<?php

namespace App\Models;

use App\Models\Traits\AutoUuid;
use CodeIgniter\Model;

class CustomerModel extends Model
{
    use AutoUuid;

    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['id', 'name', 'phone', 'email', 'address', 'is_active'];
    protected $beforeInsert     = ['assignId'];

    public function activeCustomers(): array
    {
        return $this->select('id, name, phone, email, address')
            ->where('is_active', 1)
            ->orderBy('name')
            ->findAll();
    }
}
