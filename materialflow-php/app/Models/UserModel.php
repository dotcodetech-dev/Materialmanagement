<?php

namespace App\Models;

use App\Models\Traits\AutoUuid;
use CodeIgniter\Model;

class UserModel extends Model
{
    use AutoUuid;

    protected $table            = 'app_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = ['id', 'full_name', 'email', 'password_hash', 'role', 'is_active'];
    protected $beforeInsert     = ['assignId'];

    public const ROLES = ['ADMIN', 'STOREKEEPER', 'MANAGER', 'VIEWER'];

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', trim($email))->first();
    }
}
