<?php

namespace App\Controllers;

use App\Libraries\StockService;

class Dashboard extends BaseController
{
    public function index()
    {
        $stats = (new StockService())->dashboard();

        return view('dashboard/index', [
            'title'   => 'Dashboard',
            'heading' => 'Welcome, ' . (session('full_name') ?? 'User'),
        ] + $stats);
    }
}
