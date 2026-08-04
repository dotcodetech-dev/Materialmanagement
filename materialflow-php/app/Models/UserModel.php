<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'app_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['id', 'full_name', 'email', 'password_hash', 'role', 'is_active'];
    protected $beforeInsert     = ['assignId'];

    public const ROLES = ['ADMIN', 'STOREKEEPER', 'MANAGER', 'VIEWER'];

    protected function assignId(array $data): array
    {
        if (empty($data['data']['id'])) {
            helper('uuid');
            $data['data']['id'] = uuid_v4();
        }

        return $data;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', trim($email))->first();
    }
}
