<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/**
 * Batch barcode generation, status feed, details, and the print-status
 * state machine. All multi-statement flows are transactional.
 */
class BatchService
{
    public const MAX_QUANTITY = 10000;
    private const CHUNK       = 500;

    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
        helper('uuid');
    }

    /**
     * Generate a batch and all its barcodes atomically.
     *
     * @return array{batch_id: string, batch_reference: string, item_name: string,
     *               total_generated: int, barcodes: list<array{barcode: string, unit_number: int}>}
     *
     * @throws RuntimeException with a user-facing message on validation/conflict failures.
     */
    public function generate(string $itemId, string $reference, int $quantity, ?string $prefix, ?string $userId): array
    {
        if ($itemId === '' || $reference === '' || $quantity === 0) {
            throw new RuntimeException('Missing required fields', 400);
        }
        if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
            throw new RuntimeException('Quantity must be between 1 and ' . self::MAX_QUANTITY, 400);
        }

        $item = $this->db->table('items')->select('id, name, barcode')->where('id', $itemId)->get()->getRowArray();
        if ($item === null) {
            throw new RuntimeException('Item not found', 404);
        }

        // Default prefix includes the batch reference, so codes from different
        // batches of the same item can never collide.
        $prefix = trim((string) $prefix);
        if ($prefix === '') {
            $prefix = $item['barcode'] . '-' . strtoupper(preg_replace('/\s+/', '-', $reference)) . '-';
        }
        if (strlen($prefix) + 6 > 64) {
            throw new RuntimeException('Barcode prefix too long (max ' . (64 - 6) . ' characters).', 400);
        }

        $batchId = uuid_v4();

        $this->db->transBegin();

        try {
            $exists = $this->db->table('barcode_batches')->select('id')->where('batch_reference', $reference)->get()->getRowArray();
            if ($exists !== null) {
                $this->db->transRollback();

                throw new RuntimeException('Batch reference already exists', 409);
            }

            $this->db->table('barcode_batches')->insert([
                'id'              => $batchId,
                'item_id'         => $itemId,
                'batch_reference' => $reference,
                'quantity_total'  => $quantity,
                'barcode_prefix'  => $prefix,
                'status'          => 'PENDING',
                'created_by'      => $userId,
            ]);

            $barcodes = [];
            $rows     = [];
            for ($i = 1; $i <= $quantity; $i++) {
                $code       = $prefix . str_pad((string) $i, 6, '0', STR_PAD_LEFT);
                $barcodes[] = ['barcode' => $code, 'unit_number' => $i];
                $rows[]     = [
                    'id'           => uuid_v4(),
                    'batch_id'     => $batchId,
                    'barcode_code' => $code,
                    'item_id'      => $itemId,
                    'unit_number'  => $i,
                    'status'       => 'UNSCANNED',
                ];
            }

            foreach (array_chunk($rows, self::CHUNK) as $chunk) {
                $this->db->table('batch_barcodes')->insertBatch($chunk);
            }

            $this->db->table('barcode_batches')->where('id', $batchId)->update([
                'quantity_generated' => $quantity,
                'status'             => 'COMPLETED',
                'status_detail'      => 'CREATED',
            ]);

            $this->db->table('batch_history')->insert([
                'id'               => uuid_v4(),
                'batch_id'         => $batchId,
                'action'           => 'GENERATED',
                'printed_quantity' => null,
                'printed_by'       => $userId,
                'action_details'   => json_encode(['quantity' => $quantity, 'prefix' => $prefix]),
            ]);

            $this->db->transCommit();
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->db->transRollback();

            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                throw new RuntimeException(
                    str_contains($e->getMessage(), 'batch_reference')
                        ? 'Batch reference already exists'
                        : 'Barcode prefix collides with an existing batch. Choose another prefix.',
                    409
                );
            }

            log_message('error', 'Batch generation failed: ' . $e->getMessage());

            throw new RuntimeException('Failed to generate batch', 500);
        }

        return [
            'batch_id'        => $batchId,
            'batch_reference' => $reference,
            'item_name'       => $item['name'],
            'total_generated' => $quantity,
            'barcodes'        => $barcodes,
        ];
    }

    /**
     * The batch dashboard feed (mirrors the old /api/batch-status).
     */
    public function statusList(): array
    {
        return $this->db->query(
            'SELECT bb.id, bb.batch_reference, bb.item_id, i.name AS item_name,'
            . ' bb.quantity_total, bb.quantity_generated, bb.status, bb.status_detail,'
            . ' bb.total_printed, bb.last_printed_at,'
            . ' u_created.full_name AS created_by_name,'
            . ' u_printed.full_name AS last_printed_by_name,'
            . ' bb.created_at,'
            . " COUNT(CASE WHEN bb2.status = 'SCANNED' THEN 1 END) AS scanned_count,"
            . ' COUNT(bb2.id) AS total_barcodes,'
            . ' (SELECT COUNT(*) FROM batch_history WHERE batch_id = bb.id) AS total_actions,'
            . ' (SELECT COUNT(*) FROM batch_exports WHERE batch_id = bb.id) AS total_exports'
            . ' FROM barcode_batches bb'
            . ' JOIN items i ON bb.item_id = i.id'
            . ' LEFT JOIN app_users u_created ON bb.created_by = u_created.id'
            . ' LEFT JOIN app_users u_printed ON bb.last_printed_by = u_printed.id'
            . ' LEFT JOIN batch_barcodes bb2 ON bb.id = bb2.batch_id'
            . ' GROUP BY bb.id'
            . ' ORDER BY bb.created_at DESC'
        )->getResultArray();
    }

    /**
     * Get barcodes for a batch (for print pages, not API).
     * Returns up to 10,000 rows at a time; print pages can handle it.
     *
     * @return list<array{barcode: string, unit_number: int}>|null
     */
    public function getBarcodes(string $batchId): ?array
    {
        $batch = $this->db->table('barcode_batches')->select('id')->where('id', $batchId)->get()->getRowArray();
        if ($batch === null) {
            return null;
        }

        $barcodes = $this->db->table('batch_barcodes')
            ->select('barcode_code AS barcode, unit_number')
            ->where('batch_id', $batchId)
            ->orderBy('unit_number', 'ASC')
            ->get()->getResultArray();
        foreach ($barcodes as &$b) {
            $b['unit_number'] = (int) $b['unit_number'];
        }

        return $barcodes;
    }

    /**
     * Full batch detail: header, print history, scan stats, export history.
     * Barcodes are not included (too large for 1000+ units; use getBarcodes() for print pages).
     */
    public function details(string $batchId): ?array
    {
        $batch = $this->db->query(
            'SELECT bb.id, bb.batch_reference, bb.item_id, i.name AS item_name,'
            . ' bb.quantity_total, bb.quantity_generated, bb.status_detail,'
            . ' bb.total_printed, bb.last_printed_at, u.full_name AS last_printed_by, bb.created_at'
            . ' FROM barcode_batches bb'
            . ' JOIN items i ON bb.item_id = i.id'
            . ' LEFT JOIN app_users u ON bb.last_printed_by = u.id'
            . ' WHERE bb.id = ?',
            [$batchId]
        )->getRowArray();

        if ($batch === null) {
            return null;
        }

        $printHistory = $this->db->query(
            'SELECT bh.action, bh.printed_quantity, bh.printed_by, u.full_name AS printed_by_name, bh.created_at'
            . ' FROM batch_history bh LEFT JOIN app_users u ON bh.printed_by = u.id'
            . " WHERE bh.batch_id = ? AND bh.action IN ('PRINTED','PARTIAL_PRINT')"
            . ' ORDER BY bh.created_at DESC LIMIT 20',
            [$batchId]
        )->getResultArray();

        $stats = $this->db->query(
            'SELECT COUNT(*) AS total,'
            . " COUNT(CASE WHEN status = 'SCANNED' THEN 1 END) AS scanned,"
            . " COUNT(CASE WHEN status = 'UNSCANNED' THEN 1 END) AS unscanned"
            . ' FROM batch_barcodes WHERE batch_id = ?',
            [$batchId]
        )->getRowArray();

        $exports = $this->db->query(
            'SELECT be.export_format, be.record_count, be.created_at, u.full_name AS exported_by_name'
            . ' FROM batch_exports be LEFT JOIN app_users u ON be.exported_by = u.id'
            . ' WHERE be.batch_id = ? ORDER BY be.created_at DESC LIMIT 10',
            [$batchId]
        )->getResultArray();

        return [
            'batch'          => $batch,
            'print_history'  => $printHistory,
            'scan_stats'     => array_map('intval', $stats),
            'export_history' => $exports,
        ];
    }

    /**
     * Log a PRINTED / PARTIAL_PRINT action and advance the cumulative print
     * counter. The new total is computed in PHP first — MySQL evaluates SET
     * clauses left-to-right, so a self-referencing CASE would double-count.
     */
    public function recordPrint(string $batchId, string $action, int $printedQuantity, ?string $userId): bool
    {
        if (! in_array($action, ['PRINTED', 'PARTIAL_PRINT'], true)) {
            throw new RuntimeException('Invalid action', 400);
        }

        $this->db->transBegin();

        try {
            $batch = $this->db->query(
                'SELECT total_printed, quantity_total FROM barcode_batches WHERE id = ? FOR UPDATE',
                [$batchId]
            )->getRowArray();

            if ($batch === null) {
                $this->db->transRollback();

                return false;
            }

            $newTotal = (int) $batch['total_printed'] + $printedQuantity;

            $this->db->table('batch_history')->insert([
                'id'               => uuid_v4(),
                'batch_id'         => $batchId,
                'action'           => $action,
                'printed_quantity' => $printedQuantity,
                'printed_by'       => $userId,
            ]);

            $this->db->table('barcode_batches')->where('id', $batchId)->update([
                'total_printed'   => $newTotal,
                'last_printed_at' => date('Y-m-d H:i:s'),
                'last_printed_by' => $userId,
                'status_detail'   => $newTotal >= (int) $batch['quantity_total'] ? 'FULLY_PRINTED' : 'PARTIALLY_PRINTED',
            ]);

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'recordPrint failed: ' . $e->getMessage());

            throw new RuntimeException('Failed to log batch action', 500);
        }

        return true;
    }

    /**
     * CSV export rows (single JOIN, no N+1) + audit log entry.
     *
     * @return array{filename: string, content: string}|null
     */
    public function exportCsv(string $batchId, ?string $userId): ?array
    {
        $detail = $this->details($batchId);
        if ($detail === null) {
            return null;
        }
        $batch = $detail['batch'];

        $rows = $this->db->query(
            'SELECT bb.unit_number, bb.barcode_code, bb.status, bb.scanned_at, u.full_name AS scanned_by_name'
            . ' FROM batch_barcodes bb LEFT JOIN app_users u ON bb.scanned_by = u.id'
            . ' WHERE bb.batch_id = ? ORDER BY bb.unit_number ASC',
            [$batchId]
        )->getResultArray();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Batch Export - ' . $batch['batch_reference']]);
        fputcsv($out, ['Item: ' . $batch['item_name']]);
        fputcsv($out, ['Generated: ' . $batch['quantity_generated'] . ' / ' . $batch['quantity_total']]);
        fputcsv($out, ['Export Date: ' . date('Y-m-d H:i:s') . ' UTC']);
        fputcsv($out, []);
        fputcsv($out, ['Unit Number', 'Barcode', 'Status', 'Scanned At', 'Scanned By']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['unit_number'],
                $r['barcode_code'],
                $r['status'],
                $r['scanned_at'] ?? '—',
                $r['scanned_by_name'] ?? '—',
            ]);
        }
        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        $this->db->table('batch_exports')->insert([
            'id'            => uuid_v4(),
            'batch_id'      => $batchId,
            'export_format' => 'CSV',
            'exported_by'   => $userId,
            'record_count'  => count($rows),
            'file_size'     => strlen($content),
        ]);

        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $batch['batch_reference']) . '.csv';

        return ['filename' => $filename, 'content' => $content];
    }
}
