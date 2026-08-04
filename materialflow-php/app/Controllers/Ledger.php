<?php

namespace App\Controllers;

use App\Libraries\StockService;

class Ledger extends BaseController
{
    public function index()
    {
        return view('ledger/index', [
            'title'     => 'Stock Ledger',
            'heading'   => 'Stock Ledger',
            'movements' => (new StockService())->movements([], 500),
        ]);
    }

    public function exportCsv()
    {
        $movements = (new StockService())->movements([], 5000);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Date', 'Type', 'Item', 'Barcode', 'Qty', 'Unit', 'Customer', 'Reference', 'Notes', 'Recorded by']);
        foreach ($movements as $m) {
            fputcsv($out, [
                $m['occurred_at'],
                $m['movement_type'],
                $m['item_name'],
                $m['item_barcode'],
                $m['quantity'],
                $m['item_unit'],
                $m['customer_name'] ?? '',
                $m['reference_number'] ?? '',
                $m['notes'] ?? '',
                $m['recorded_by_name'] ?? '',
            ]);
        }
        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="stock-movements.csv"')
            ->setBody("\xEF\xBB\xBF" . $content);
    }
}
