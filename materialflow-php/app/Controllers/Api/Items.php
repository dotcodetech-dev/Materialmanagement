<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\StockService;

class Items extends BaseController
{
    public function nextBarcode()
    {
        return $this->response->setJSON([
            'barcode' => (new StockService())->nextItemBarcode(),
        ]);
    }
}
