<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\BatchService;
use RuntimeException;

class Batches extends BaseController
{
    public function generate()
    {
        $body = $this->request->getJSON(true) ?? [];

        try {
            $result = (new BatchService())->generate(
                (string) ($body['item_id'] ?? ''),
                trim((string) ($body['batch_reference'] ?? '')),
                (int) ($body['quantity'] ?? 0),
                $body['barcode_prefix'] ?? null,
                session('user_id')
            );
        } catch (RuntimeException $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() >= 400 ? $e->getCode() : 500);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'batch_id'             => $result['batch_id'],
            'batch_reference'      => $result['batch_reference'],
            'item_name'            => $result['item_name'],
            'total_generated'      => count(array_slice($result['barcodes'], 0, 10)),
            'barcodes'             => array_slice($result['barcodes'], 0, 10),
            'total_generated_full' => $result['total_generated'],
        ]);
    }

    public function details(string $batchId)
    {
        $detail = (new BatchService())->details($batchId);
        if ($detail === null) {
            return $this->jsonError('Batch not found', 404);
        }

        return $this->response->setJSON($detail);
    }

    /**
     * QA gate — the operator scans a printed sample; we confirm it belongs to
     * the batch and (on first match) stamp the batch verified.
     */
    public function verify(string $batchId)
    {
        $body    = $this->request->getJSON(true) ?? [];
        $scanned = trim((string) ($body['scanned'] ?? ''));

        if ($scanned === '') {
            return $this->jsonError('Scanned value required', 400);
        }

        $result = (new BatchService())->verify($batchId, $scanned, session('user_id'));

        if (! $result['matched']) {
            return $this->response->setStatusCode(404)->setJSON([
                'matched' => false,
                'message' => 'That code does not belong to this batch.',
            ]);
        }

        return $this->response->setJSON([
            'matched'      => true,
            'unit_number'  => $result['unit_number'],
            'barcode_code' => $result['barcode_code'],
            'verified'     => true,
            'message'      => 'Verified — Unit ' . $result['unit_number'] . ' matches.',
        ]);
    }

    public function recordPrint(string $batchId)
    {
        $body   = $this->request->getJSON(true) ?? [];
        $action = (string) ($body['action'] ?? 'PRINTED');
        $qty    = max(0, (int) ($body['printed_quantity'] ?? 0));

        try {
            $ok = (new BatchService())->recordPrint($batchId, $action, $qty, session('user_id'));
        } catch (RuntimeException $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() >= 400 ? $e->getCode() : 500);
        }

        if (! $ok) {
            return $this->jsonError('Batch not found', 404);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Action logged successfully']);
    }
}
