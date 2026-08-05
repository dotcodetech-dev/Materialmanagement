<?php

namespace App\Models\Traits;

trait AutoUuid
{
    protected function assignId(array $data): array
    {
        if (empty($data['data']['id'])) {
            helper('uuid');
            $data['data']['id'] = uuid_v4();
        }

        return $data;
    }
}
