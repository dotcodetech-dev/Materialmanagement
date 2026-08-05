<?php

namespace App\Controllers;

use App\Libraries\StockService;

class Reports extends BaseController
{
    public function index()
    {
        [$reportType, $filters, $rows, $items] = $this->buildReport();
        $stock = new StockService();

        $users = array_values(array_unique(array_filter(array_column($stock->movements([], 1000), 'recorded_by_name'))));
        sort($users);
        $categories = array_values(array_unique(array_column($stock->itemsWithBalance(), 'category')));
        sort($categories);

        return view('reports/index', [
            'title'      => 'Reports',
            'heading'    => 'Reports',
            'reportType' => $reportType,
            'filters'    => $filters,
            'rows'       => $rows,
            'items'      => $items,
            'users'      => $users,
            'categories' => $categories,
        ]);
    }

    public function printView()
    {
        [$reportType, $filters, $rows, $items] = $this->buildReport();

        return view('print/report', [
            'reportType' => $reportType,
            'filters'    => $filters,
            'rows'       => $rows,
            'items'      => $items,
            'brand'      => mf_settings(),
        ]);
    }

    public function exportCsv()
    {
        [$reportType, , $rows, $items] = $this->buildReport();

        $out = fopen('php://temp', 'r+');

        if ($reportType === 'stock') {
            fputcsv($out, ['Item', 'SKU', 'Barcode', 'Category', 'Unit', 'Available', 'Reorder Level', 'Status']);
            foreach ($items as $i) {
                fputcsv($out, [
                    $i['name'], $i['sku'] ?? '', $i['barcode'], $i['category'], $i['unit'],
                    $i['available_quantity'], $i['reorder_level'],
                    $i['available_quantity'] <= $i['reorder_level'] ? 'LOW' : 'OK',
                ]);
            }
        } else {
            fputcsv($out, ['Date', 'Type', 'Item', 'Category', 'Qty', 'Unit', 'Customer', 'Reference', 'Recorded by']);
            foreach ($rows as $m) {
                fputcsv($out, [
                    $m['occurred_at'], $m['movement_type'], $m['item_name'], $m['item_category'],
                    $m['quantity'], $m['item_unit'], $m['customer_name'] ?? '',
                    $m['reference_number'] ?? '', $m['recorded_by_name'] ?? '',
                ]);
            }
        }

        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        $filename = $reportType . '-report-' . date('Y-m-d') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody("\xEF\xBB\xBF" . $content);
    }

    /**
     * @return array{0: string, 1: array, 2: list<array>, 3: list<array>}
     */
    private function buildReport(): array
    {
        $get        = $this->request->getGet();
        $reportType = in_array($get['type'] ?? '', ['inward', 'outward', 'stock'], true) ? $get['type'] : 'inward';

        $filters = [
            'from'        => (string) ($get['from'] ?? ''),
            'to'          => (string) ($get['to'] ?? ''),
            'recorded_by' => (string) ($get['recorded_by'] ?? ''),
            'category'    => (string) ($get['category'] ?? ''),
        ];

        $stock = new StockService();
        $rows  = [];
        $items = [];

        if ($reportType === 'stock') {
            $items = $stock->itemsWithBalance();
            if ($filters['category'] !== '') {
                $items = array_values(array_filter($items, fn ($i) => $i['category'] === $filters['category']));
            }
        } else {
            $rows = $stock->movements(array_filter($filters) + ['type' => $reportType], 1000);
        }

        return [$reportType, $filters, $rows, $items];
    }
}
