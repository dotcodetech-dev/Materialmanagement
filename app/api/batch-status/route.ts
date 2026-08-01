import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function GET(req: NextRequest) {
  try {
    const result = await pool.query(`
      SELECT
        bb.id,
        bb.batch_reference,
        bb.item_id,
        i.name as item_name,
        bb.quantity_total,
        bb.quantity_generated,
        bb.status,
        bb.status_detail,
        bb.total_printed,
        bb.last_printed_at,
        u_created.full_name as created_by_name,
        u_printed.full_name as last_printed_by_name,
        bb.created_at,
        COUNT(CASE WHEN bb2.status = 'SCANNED' THEN 1 END)::int as scanned_count,
        COUNT(bb2.id)::int as total_barcodes,
        (SELECT COUNT(*)::int FROM batch_history WHERE batch_id = bb.id) as total_actions,
        (SELECT COUNT(*)::int FROM batch_exports WHERE batch_id = bb.id) as total_exports
      FROM barcode_batches bb
      JOIN items i ON bb.item_id = i.id
      LEFT JOIN app_users u_created ON bb.created_by = u_created.id
      LEFT JOIN app_users u_printed ON bb.last_printed_by = u_printed.id
      LEFT JOIN batch_barcodes bb2 ON bb.id = bb2.batch_id
      GROUP BY bb.id, bb.batch_reference, bb.item_id, i.name, bb.quantity_total,
               bb.quantity_generated, bb.status, bb.status_detail, bb.total_printed,
               bb.last_printed_at, u_created.full_name, u_printed.full_name, bb.created_at
      ORDER BY bb.created_at DESC
    `);

    return NextResponse.json(result.rows);
  } catch (err) {
    console.error("Failed to fetch batch status:", err);
    return NextResponse.json({ error: "Failed to fetch batch status" }, { status: 500 });
  }
}
