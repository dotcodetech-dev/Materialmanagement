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
