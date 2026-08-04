<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\StockService;
use Throwable;

class Scan extends BaseController
{
    /**
     * Advisory pre-scan lookup (no side effects). The commit endpoint
     * re-validates inside its transaction, so this is for UI feedback only.
     */
    public function check()
    {
        $barcode = trim((string) ($this->request->getJSON(true)['barcode'] ?? ''));
        if ($barcode === '') {
            return $this->jsonError('Barcode required', 400);
        }

        $row = $this->findBatchBarcode($barcode, false);

        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'valid'   => false,
                'error'   => 'BARCODE_NOT_FOUND',
                'message' => 'Barcode not found in batch system',
            ]);
        }

        if ($row['status'] === 'SCANNED') {
            return $this->alreadyScanned($row);
        }

        return $this->response->setJSON([
            'valid'           => true,
            'barcode_id'      => $row['id'],
            'barcode_code'    => $row['barcode_code'],
            'item_id'         => $row['item_id'],
            'item_name'       => $row['item_name'],
            'item_unit'       => $row['unit'],
            'unit_number'     => (int) $row['unit_number'],
            'batch_reference' => $row['batch_reference'],
            'status'          => 'UNSCANNED',
        ]);
    }

    /**
     * One transactional scan: batch-barcode path (one-time-scan enforced) or
     * plain item-barcode fallback. Replaces the old validate → movement →
     * mark-scanned triple round-trip and closes its race conditions.
     */
    public function commit()
    {
        $body         = $this->request->getJSON(true) ?? [];
        $barcode      = trim((string) ($body['barcode'] ?? ''));
        $movementType = (string) ($body['movement_type'] ?? '');

        if ($barcode === '') {
            return $this->jsonError('Barcode required', 400);
        }
        if (! in_array($movementType, StockService::ALL_TYPES, true)) {
            return $this->jsonError('Invalid movement type.', 400);
        }

        $db      = db_connect();
        $stock   = new StockService($db);
        $userId  = session('user_id');
        $isOut   = in_array($movementType, StockService::OUTWARD_TYPES, true);

        $db->transBegin();

        try {
            $batchRow = $this->findBatchBarcode($barcode, true, $db);

            if ($batchRow !== null && $batchRow['status'] === 'SCANNED') {
                $db->transRollback();

                return $this->alreadyScanned($batchRow);
            }

            $itemRow = null;
            if ($batchRow === null) {
                // Not a batch barcode — fall back to the item's own barcode (case-insensitive, like the SPA).
                $itemRow = $db->query(
                    'SELECT id, name, unit FROM items WHERE LOWER(barcode) = LOWER(?) AND is_active = 1 LIMIT 1 FOR UPDATE',
                    [$barcode]
                )->getRowArray();

                if ($itemRow === null) {
                    $db->transRollback();

                    return $this->response->setStatusCode(404)->setJSON([
                        'error'   => 'BARCODE_NOT_FOUND',
                        'message' => 'Barcode not found: ' . $barcode,
                    ]);
                }
            } else {
                // Serialize concurrent scans against the same item's balance.
                $db->query('SELECT id FROM items WHERE id = ? FOR UPDATE', [$batchRow['item_id']]);
            }

            $itemId   = $batchRow['item_id'] ?? $itemRow['id'];
            $itemName = $batchRow['item_name'] ?? $itemRow['name'];
            $itemUnit = $batchRow['unit'] ?? $itemRow['unit'];

            if ($isOut) {
                $available = $this->balanceInTxn($db, $itemId);
                if ($available < 1) {
                    $db->transRollback();

                    return $this->jsonError('Insufficient stock. Available: ' . ($available + 0), 400);
                }
            }

            if ($batchRow !== null) {
                $reference = 'Batch: ' . $batchRow['batch_reference'] . ', Unit: ' . $batchRow['unit_number'];
                $notes     = 'Batch scan - Unit ' . $batchRow['unit_number'] . '/' . $batchRow['batch_reference'];
            } else {
                $reference = null;
                $notes     = 'Quick scan - ' . date('H:i:s');
            }

            $movementId = $stock->insertMovement([
                'item_id'          => $itemId,
                'movement_type'    => $movementType,
                'quantity'         => 1,
                'reference_number' => $reference,
                'notes'            => $notes,
                'recorded_by'      => $userId,
            ]);

            if ($batchRow !== null) {
                $db->table('batch_barcodes')
                    ->where('id', $batchRow['id'])
                    ->where('status', 'UNSCANNED')
                    ->update([
                        'status'      => 'SCANNED',
                        'scanned_at'  => date('Y-m-d H:i:s'),
                        'scanned_by'  => $userId,
                        'movement_id' => $movementId,
                    ]);

                if ($db->affectedRows() === 0) {
                    // Lost the race despite the lock (defensive): treat as already scanned.
                    $db->transRollback();

                    return $this->alreadyScanned($this->findBatchBarcode($barcode, false));
                }
            }

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Scan commit failed: ' . $e->getMessage());

            return $this->jsonError('Failed to record scan.', 500);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'success'         => true,
            'movement_id'     => $movementId,
            'item_name'       => $itemName,
            'item_unit'       => $itemUnit,
            'is_batch'        => $batchRow !== null,
            'unit_number'     => $batchRow !== null ? (int) $batchRow['unit_number'] : null,
            'batch_reference' => $batchRow['batch_reference'] ?? null,
        ]);
    }

    private function findBatchBarcode(string $barcode, bool $forUpdate, $db = null): ?array
    {
        $db ??= db_connect();

        $sql = 'SELECT bb.id, bb.barcode_code, bb.item_id, i.name AS item_name, i.unit,'
            . ' bb.unit_number, bb.status, bb.scanned_at, bb.scanned_by, bat.batch_reference,'
            . ' u.full_name AS scanned_by_name'
            . ' FROM batch_barcodes bb'
            . ' JOIN items i ON bb.item_id = i.id'
            . ' JOIN barcode_batches bat ON bb.batch_id = bat.id'
            . ' LEFT JOIN app_users u ON bb.scanned_by = u.id'
            . ' WHERE bb.barcode_code = ?'
            . ($forUpdate ? ' FOR UPDATE OF bb' : '');

        return $db->query($sql, [$barcode])->getRowArray() ?: null;
    }

    private function balanceInTxn($db, string $itemId): float
    {
        $row = $db->query(
            "SELECT COALESCE(SUM(CASE WHEN movement_type IN ('INWARD','RETURN_IN','ADJUSTMENT_IN') THEN quantity ELSE -quantity END), 0) AS bal"
            . ' FROM stock_movements WHERE item_id = ?',
            [$itemId]
        )->getRowArray();

        return (float) ($row['bal'] ?? 0);
    }

    private function alreadyScanned(?array $row)
    {
        return $this->response->setStatusCode(409)->setJSON([
            'valid'   => false,
            'error'   => 'BARCODE_ALREADY_SCANNED',
            'message' => 'This barcode was already scanned',
            'details' => [
                'barcode_code'    => $row['barcode_code'] ?? null,
                'scanned_at'      => $row['scanned_at'] ?? null,
                'scanned_by'      => $row['scanned_by_name'] ?? 'Unknown user',
                'batch_reference' => $row['batch_reference'] ?? null,
            ],
        ]);
    }
}
