<?php

namespace App\Controllers;

use App\Libraries\StockService;

class Labels extends BaseController
{
    private const SIZES = [
        'small'  => ['w' => 145, 'h' => 95, 'bw' => 1.2, 'bh' => 30, 'fs' => 9],
        'medium' => ['w' => 200, 'h' => 120, 'bw' => 1.5, 'bh' => 40, 'fs' => 11],
        'large'  => ['w' => 280, 'h' => 160, 'bw' => 2, 'bh' => 55, 'fs' => 13],
    ];

    public function index()
    {
        return view('labels/index', [
            'title'   => 'Labels',
            'heading' => 'Print Labels',
            'items'   => (new StockService())->itemsWithBalance(),
        ]);
    }

    /**
     * Standalone print page. ?sel=itemId:qty,itemId:qty&size=small|medium|large
     */
    public function printView()
    {
        $size = $this->request->getGet('size');
        $size = array_key_exists($size ?? '', self::SIZES) ? $size : 'medium';

        $counts = [];
        foreach (explode(',', (string) $this->request->getGet('sel')) as $pair) {
            [$id, $qty] = array_pad(explode(':', $pair, 2), 2, '1');
            if ($id !== '') {
                $counts[$id] = max(1, min(100, (int) $qty));
            }
        }

        if ($counts === []) {
            return redirect()->to('/labels')->with('error', 'Select at least one item to print labels.');
        }

        $items = array_values(array_filter(
            (new StockService())->itemsWithBalance(),
            static fn ($i) => isset($counts[$i['item_id']])
        ));

        $labels = [];
        foreach ($items as $i) {
            $labels[] = [
                'barcode'  => $i['barcode'],
                'name'     => $i['name'],
                'category' => $i['category'],
                'unit'     => $i['unit'],
                'qty'      => $counts[$i['item_id']],
            ];
        }

        return view('print/labels_items', [
            'labels' => $labels,
            'sz'     => self::SIZES[$size],
            'total'  => array_sum(array_column($labels, 'qty')),
        ]);
    }
}
