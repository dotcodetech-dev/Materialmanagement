<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['id', 'name', 'phone', 'email', 'address', 'is_active'];
    protected $beforeInsert     = ['assignId'];

    protected function assignId(array $data): array
    {
        if (empty($data['data']['id'])) {
            helper('uuid');
            $data['data']['id'] = uuid_v4();
        }

        return $data;
    }

    public function activeCustomers(): array
    {
        return $this->select('id, name, phone, email, address')
            ->where('is_active', 1)
            ->orderBy('name')
            ->findAll();
    }
}
