<?php

namespace App\Controllers;

use App\Libraries\BatchService;

class Batches extends BaseController
{
    public function index()
    {
        return view('batches/index', [
            'title'   => 'Batch History',
            'heading' => 'Batch History',
            'batches' => (new BatchService())->statusList(),
            'canEdit' => in_array(session('role'), ['ADMIN', 'MANAGER', 'STOREKEEPER'], true),
        ]);
    }

    /**
     * Standalone A4 label sheet (63/page). Optional ?from=&to= unit range.
     * Logs PRINTED / PARTIAL_PRINT via a beacon once the page opens.
     */
    public function printLabels(string $batchId)
    {
        $service = new BatchService();
        $detail = $service->details($batchId);
        if ($detail === null) {
            return redirect()->to('/batches')->with('error', 'Batch not found.');
        }

        $batch    = $detail['batch'];
        $barcodes = $service->getBarcodes($batchId) ?? [];
        $max      = (int) $batch['quantity_generated'];

        $from = (int) ($this->request->getGet('from') ?? 1);
        $to   = (int) ($this->request->getGet('to') ?? $max);
        $from = max(1, min($from, $max));
        $to   = max($from, min($to, $max));

        $isRange = ($from > 1 || $to < $max);
        if ($isRange) {
            $barcodes = array_values(array_filter(
                $barcodes,
                static fn ($b) => $b['unit_number'] >= $from && $b['unit_number'] <= $to
            ));
        }

        return view('print/labels_batch', [
            'batch'    => $batch,
            'barcodes' => $barcodes,
            'title'    => $isRange
                ? $batch['batch_reference'] . ' (Units ' . $from . '-' . $to . ')'
                : $batch['batch_reference'],
            'action'   => $isRange ? 'PARTIAL_PRINT' : 'PRINTED',
        ]);
    }

    public function exportCsv(string $batchId)
    {
        $export = (new BatchService())->exportCsv($batchId, session('user_id'));
        if ($export === null) {
            return redirect()->to('/batches')->with('error', 'Batch not found.');
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $export['filename'] . '"')
            ->setBody("\xEF\xBB\xBF" . $export['content']);
    }
}
