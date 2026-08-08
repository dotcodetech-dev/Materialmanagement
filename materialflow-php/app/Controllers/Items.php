<?php

namespace App\Controllers;

use App\Libraries\StockService;
use App\Models\ItemModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class Items extends BaseController
{
    public function index()
    {
        $q     = $this->request->getGet('q');
        $stock = new StockService();

        return view('items/index', [
            'title'   => 'Items',
            'heading' => 'Items',
            'items'   => $stock->itemsWithBalance($q),
            'q'       => (string) ($q ?? ''),
            'units'   => ItemModel::UNITS,
            'canEdit' => $this->canEdit(),
        ]);
    }

    public function create()
    {
        return redirect()->to('/items');
    }

    public function store()
    {
        [$data, $error] = $this->validated();
        if ($error !== null) {
            return redirect()->to('/items')->withInput()->with('error', $error);
        }

        if ($data['barcode'] === '') {
            $data['barcode'] = (new StockService())->nextItemBarcode();
        }

        try {
            model(ItemModel::class)->insert($data);
        } catch (DatabaseException $e) {
            return redirect()->to('/items')->withInput()->with('error', $this->duplicateMessage($e));
        }

        return redirect()->to('/items')->with('notice', 'Item "' . $data['name'] . '" saved.');
    }

    public function edit(string $id)
    {
        $item = model(ItemModel::class)->findActive($id);
        if ($item === null) {
            return redirect()->to('/items')->with('error', 'Item not found.');
        }

        return view('items/form', [
            'title'   => 'Edit item',
            'heading' => 'Edit item',
            'item'    => $item,
            'units'   => ItemModel::UNITS,
        ]);
    }

    public function update(string $id)
    {
        $model = model(ItemModel::class);
        if ($model->findActive($id) === null) {
            return redirect()->to('/items')->with('error', 'Item not found.');
        }

        [$data, $error] = $this->validated();
        if ($error !== null || $data['barcode'] === '') {
            return redirect()->back()->withInput()->with('error', $error ?? 'Name, barcode, and unit are required.');
        }

        try {
            $model->update($id, $data);
        } catch (DatabaseException $e) {
            return redirect()->back()->withInput()->with('error', $this->duplicateMessage($e));
        }

        return redirect()->to('/items')->with('notice', 'Item updated.');
    }

    public function deactivate(string $id)
    {
        $model = model(ItemModel::class);
        if ($model->findActive($id) === null) {
            return redirect()->to('/items')->with('error', 'Item not found.');
        }

        $model->update($id, ['is_active' => 0]);

        return redirect()->to('/items')->with('notice', 'Item deleted.');
    }

    /**
     * @return array{0: array, 1: ?string}
     */
    private function validated(): array
    {
        $post = $this->request->getPost();

        $data = [
            'barcode'       => trim((string) ($post['barcode'] ?? '')),
            'sku'           => trim((string) ($post['sku'] ?? '')) ?: null,
            'name'          => trim((string) ($post['name'] ?? '')),
            'category'      => trim((string) ($post['category'] ?? '')) ?: 'General',
            'unit'          => trim((string) ($post['unit'] ?? '')),
            'reorder_level' => is_numeric($post['reorder_level'] ?? null) ? (float) $post['reorder_level'] : 0,
        ];

        if ($data['name'] === '' || $data['unit'] === '') {
            return [$data, 'Name, barcode, and unit are required.'];
        }
        if (! in_array($data['unit'], ItemModel::UNITS, true)) {
            return [$data, 'Unit must be one of: ' . implode(', ', ItemModel::UNITS)];
        }
        if ($data['reorder_level'] < 0) {
            return [$data, 'Reorder level cannot be negative.'];
        }
        // Validate the barcode is Code 128-safe when one is supplied (a blank
        // barcode is auto-generated as MF-##### by the caller). Item codes keep
        // the hyphen.
        if ($data['barcode'] !== '') {
            helper('barcode');
            $err = mf_is_code128_safe($data['barcode'], true);
            if ($err !== null) {
                return [$data, $err];
            }
        }

        return [$data, null];
    }

    private function duplicateMessage(DatabaseException $e): string
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Duplicate entry')) {
            if (str_contains($msg, 'barcode')) {
                return 'This barcode already exists.';
            }
            if (str_contains($msg, 'sku')) {
                return 'This SKU already exists.';
            }

            return 'Duplicate value.';
        }

        log_message('error', 'Item save failed: ' . $msg);

        return 'Could not save item.';
    }
}
