<?php

namespace App\Controllers;

use App\Libraries\StockService;
use App\Models\CustomerModel;
use Throwable;

/**
 * Manual movement entry — the only path that links movements to customers
 * and supports quantities other than 1.
 */
class Movements extends BaseController
{
    public function create()
    {
        $stock = new StockService();

        return view('movements/form', [
            'title'     => 'Manual movement',
            'heading'   => 'Manual movement',
            'items'     => $stock->itemsWithBalance(),
            'customers' => model(CustomerModel::class)->activeCustomers(),
            'types'     => StockService::ALL_TYPES,
        ]);
    }

    public function store()
    {
        $post         = $this->request->getPost();
        $itemId       = (string) ($post['item_id'] ?? '');
        $customerId   = (string) ($post['customer_id'] ?? '');
        $movementType = (string) ($post['movement_type'] ?? '');
        $quantity     = is_numeric($post['quantity'] ?? null) ? (float) $post['quantity'] : 0;

        if ($itemId === '' || $movementType === '' || $quantity <= 0) {
            return redirect()->back()->withInput()->with('error', 'Item, movement type, and positive quantity are required.');
        }
        if (! in_array($movementType, StockService::ALL_TYPES, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid movement type.');
        }

        $db    = db_connect();
        $stock = new StockService($db);

        $db->transBegin();

        try {
            $item = $db->query('SELECT id, name FROM items WHERE id = ? AND is_active = 1 FOR UPDATE', [$itemId])->getRowArray();
            if ($item === null) {
                $db->transRollback();

                return redirect()->back()->withInput()->with('error', 'Item not found.');
            }

            if (in_array($movementType, StockService::OUTWARD_TYPES, true)) {
                $available = $stock->availableQuantity($itemId);
                if ($available < $quantity) {
                    $db->transRollback();

                    return redirect()->back()->withInput()->with('error', 'Insufficient stock. Available: ' . ($available + 0));
                }
            }

            $stock->insertMovement([
                'item_id'          => $itemId,
                'customer_id'      => $customerId !== '' ? $customerId : null,
                'movement_type'    => $movementType,
                'quantity'         => $quantity,
                'reference_number' => trim((string) ($post['reference_number'] ?? '')) ?: null,
                'notes'            => trim((string) ($post['notes'] ?? '')) ?: null,
                'recorded_by'      => session('user_id'),
            ]);

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Manual movement failed: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Could not record movement.');
        }

        return redirect()->to('/ledger')->with('notice', $movementType . ' of ' . ($quantity + 0) . ' recorded for ' . $item['name'] . '.');
    }
}
