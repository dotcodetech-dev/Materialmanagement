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

            // ---- Batch-unit lifecycle state machine ----
            // Each batch label is ONE physical unit that moves through:
            //   UNSCANNED (generated) --inward--> SCANNED (in stock) --outward--> DISPATCHED (gone)
            // Inward is valid only from UNSCANNED; outward only from SCANNED.
            // This makes every unit receivable exactly once and dispatchable
            // exactly once, so the ledger records +1 then -1 per unit and a
            // single physical item can never be issued (or received) twice.
            if ($batchRow !== null) {
                $status = $batchRow['status'];
                if (! $isOut) {
                    // INWARD (receive into stock)
                    if ($status !== 'UNSCANNED') {
                        $db->transRollback();

                        return $this->batchConflict(
                            $batchRow,
                            $status === 'DISPATCHED'
                                ? 'This unit was already dispatched — it cannot be received again.'
                                : 'This unit was already received into stock.'
                        );
                    }
                } else {
                    // OUTWARD (dispatch from stock)
                    if ($status === 'UNSCANNED') {
                        $db->transRollback();

                        return $this->jsonError('This unit is not in stock yet. Scan it inward before dispatching.', 400);
                    }
                    if ($status === 'DISPATCHED') {
                        $db->transRollback();

                        return $this->batchConflict($batchRow, 'This unit was already dispatched.');
                    }
                }

                // Serialize concurrent scans against this item's balance.
                $db->query('SELECT id FROM items WHERE id = ? FOR UPDATE', [$batchRow['item_id']]);
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
                    log_message('warning', 'Scan attempt with invalid barcode: ' . $barcode . ' from user ' . $userId);

                    return $this->response->setStatusCode(404)->setJSON([
                        'error'   => 'BARCODE_NOT_FOUND',
                        'message' => 'Barcode not found',
                    ]);
                }
            }

            $itemId   = $batchRow['item_id'] ?? $itemRow['id'];
            $itemName = $batchRow['item_name'] ?? $itemRow['name'];
            $itemUnit = $batchRow['unit'] ?? $itemRow['unit'];

            // Outward stock guard. For batch units in SCANNED state this always
            // passes (the unit itself is +1 stock); it's the real guard for
            // plain item-barcode scans, which have no per-unit tracking.
            if ($isOut) {
                $available = $this->balanceInTxn($db, $itemId);
                if ($available < 1) {
                    $db->transRollback();

                    return $this->jsonError('Insufficient stock. Available: ' . ($available + 0), 400);
                }
            }

            if ($batchRow !== null) {
                $reference = 'Batch: ' . $batchRow['batch_reference'] . ', Unit: ' . $batchRow['unit_number'];
                $notes     = ($isOut ? 'Batch dispatch' : 'Batch receipt')
                    . ' - Unit ' . $batchRow['unit_number'] . '/' . $batchRow['batch_reference'];
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

            // Advance the unit's lifecycle state. The WHERE on the expected
            // from-status makes this a compare-and-set: if a concurrent scan
            // moved the unit first, affectedRows() is 0 and we roll back.
            if ($batchRow !== null) {
                $fromStatus = $isOut ? 'SCANNED' : 'UNSCANNED';
                $newStatus  = $isOut ? 'DISPATCHED' : 'SCANNED';

                $db->table('batch_barcodes')
                    ->where('id', $batchRow['id'])
                    ->where('status', $fromStatus)
                    ->update([
                        'status'      => $newStatus,
                        'scanned_at'  => date('Y-m-d H:i:s'),
                        'scanned_by'  => $userId,
                        'movement_id' => $movementId,
                    ]);

                if ($db->affectedRows() === 0) {
                    // Lost the race despite the lock (defensive).
                    $db->transRollback();

                    return $this->batchConflict(
                        $this->findBatchBarcode($barcode, false),
                        'This unit was just updated by another scan. Please rescan.'
                    );
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
            // MySQL 8 supports `FOR UPDATE OF bb` (lock only the batch_barcodes
            // row) but MariaDB doesn't understand `OF <alias>`. Use plain
            // FOR UPDATE — locks all joined rows, still correct.
            . ($forUpdate ? ' FOR UPDATE' : '');

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
        return $this->batchConflict($row, 'This barcode was already scanned');
    }

    /**
     * 409 for any batch-unit lifecycle conflict (already received / already
     * dispatched / lost race). Carries the friendly message plus who/when
     * details so the scan UI can tell the operator exactly what happened.
     */
    private function batchConflict(?array $row, string $message)
    {
        return $this->response->setStatusCode(409)->setJSON([
            'valid'   => false,
            'error'   => 'BARCODE_CONFLICT',
            'message' => $message,
            'details' => [
                'barcode_code'    => $row['barcode_code'] ?? null,
                'status'          => $row['status'] ?? null,
                'scanned_at'      => $row['scanned_at'] ?? null,
                'scanned_by'      => $row['scanned_by_name'] ?? 'Unknown user',
                'batch_reference' => $row['batch_reference'] ?? null,
            ],
        ]);
    }
}
