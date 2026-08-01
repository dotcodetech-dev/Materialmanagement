import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function GET(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const batch_id = searchParams.get("batch_id");

  if (!batch_id) {
    return NextResponse.json({ error: "Missing batch_id" }, { status: 400 });
  }

  try {
    // Fetch batch details
    const batchResult = await pool.query(
      `SELECT bb.id, bb.batch_reference, bb.item_id, i.name as item_name,
              bb.quantity_total, bb.quantity_generated, bb.status_detail,
              bb.total_printed, bb.last_printed_at, u.full_name as last_printed_by,
              bb.created_at
       FROM barcode_batches bb
       JOIN items i ON bb.item_id = i.id
       LEFT JOIN app_users u ON bb.last_printed_by = u.id
       WHERE bb.id = $1`,
      [batch_id]
    );

    if (batchResult.rows.length === 0) {
      return NextResponse.json({ error: "Batch not found" }, { status: 404 });
    }

    const batch = batchResult.rows[0];

    // Fetch all barcodes for this batch
    const barcodesResult = await pool.query(
      `SELECT barcode_code, unit_number FROM batch_barcodes
       WHERE batch_id = $1 ORDER BY unit_number ASC`,
      [batch_id]
    );

    const barcodes = barcodesResult.rows.map(row => ({
      barcode: row.barcode_code,
      unit_number: row.unit_number
    }));

    // Fetch print history
    const historyResult = await pool.query(
      `SELECT bh.action, bh.printed_quantity, bh.printed_by, u.full_name as printed_by_name,
              bh.created_at
       FROM batch_history bh
       LEFT JOIN app_users u ON bh.printed_by = u.id
       WHERE bh.batch_id = $1 AND bh.action IN ('PRINTED', 'PARTIAL_PRINT')
       ORDER BY bh.created_at DESC
       LIMIT 20`,
      [batch_id]
    );

    // Fetch barcode scan statistics
    const scanStatsResult = await pool.query(
      `SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'SCANNED' THEN 1 END) as scanned,
        COUNT(CASE WHEN status = 'UNSCANNED' THEN 1 END) as unscanned
       FROM batch_barcodes
       WHERE batch_id = $1`,
      [batch_id]
    );

    const scanStats = scanStatsResult.rows[0];

    // Fetch export history
    const exportsResult = await pool.query(
      `SELECT be.export_format, be.record_count, be.created_at, u.full_name as exported_by_name
       FROM batch_exports be
       LEFT JOIN app_users u ON be.exported_by = u.id
       WHERE be.batch_id = $1
       ORDER BY be.created_at DESC
       LIMIT 10`,
      [batch_id]
    );

    return NextResponse.json({
      batch,
      barcodes,
      print_history: historyResult.rows,
      scan_stats: scanStats,
      export_history: exportsResult.rows
    });
  } catch (err) {
    console.error("Failed to fetch batch details:", err);
    return NextResponse.json({ error: "Failed to fetch batch details" }, { status: 500 });
  }
}
