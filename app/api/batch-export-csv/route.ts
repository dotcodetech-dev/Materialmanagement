import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function GET(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const batch_id = searchParams.get("batch_id");
  const user_id = searchParams.get("user_id");

  if (!batch_id) {
    return NextResponse.json({ error: "Missing batch_id" }, { status: 400 });
  }

  try {
    // Fetch batch details
    const batchResult = await pool.query(
      `SELECT bb.id, bb.batch_reference, bb.item_id, i.name as item_name,
              bb.quantity_total, bb.quantity_generated, bb.barcode_prefix
       FROM barcode_batches bb
       JOIN items i ON bb.item_id = i.id
       WHERE bb.id = $1`,
      [batch_id]
    );

    if (batchResult.rows.length === 0) {
      return NextResponse.json({ error: "Batch not found" }, { status: 404 });
    }

    const batch = batchResult.rows[0];

    // Fetch all barcodes in the batch
    const barcodesResult = await pool.query(
      `SELECT barcode_code, unit_number, status, scanned_at, scanned_by
       FROM batch_barcodes
       WHERE batch_id = $1
       ORDER BY unit_number ASC`,
      [batch_id]
    );

    // Get scanned_by names
    const barcodeWithNames = await Promise.all(
      barcodesResult.rows.map(async (b: any) => {
        let scanned_by_name = "—";
        if (b.scanned_by) {
          const userResult = await pool.query("SELECT full_name FROM app_users WHERE id = $1", [b.scanned_by]);
          if (userResult.rows.length > 0) {
            scanned_by_name = userResult.rows[0].full_name;
          }
        }
        return {
          ...b,
          scanned_by_name
        };
      })
    );

    // Generate CSV content
    const csvHeaders = ["Unit Number", "Barcode", "Status", "Scanned At", "Scanned By"];
    const csvRows = barcodeWithNames.map(b => [
      b.unit_number,
      b.barcode_code,
      b.status,
      b.scanned_at ? new Date(b.scanned_at).toLocaleString() : "—",
      b.scanned_by_name
    ]);

    const csvContent = [
      `Batch Export - ${batch.batch_reference}`,
      `Item: ${batch.item_name}`,
      `Generated: ${batch.quantity_generated} / ${batch.quantity_total}`,
      `Export Date: ${new Date().toLocaleString()}`,
      "",
      csvHeaders.join(","),
      ...csvRows.map(row => row.map(cell => `"${cell}"`).join(","))
    ].join("\n");

    // Log export to database
    if (user_id) {
      await pool.query(
        `INSERT INTO batch_exports (batch_id, export_format, exported_by, record_count, file_size)
         VALUES ($1, $2, $3, $4, $5)`,
        [batch_id, "CSV", user_id, barcodeWithNames.length, csvContent.length]
      );
    }

    return new NextResponse(csvContent, {
      status: 200,
      headers: {
        "Content-Type": "text/csv; charset=utf-8",
        "Content-Disposition": `attachment; filename="${batch.batch_reference}.csv"`
      }
    });
  } catch (err) {
    console.error("CSV export error:", err);
    return NextResponse.json({ error: "Failed to export CSV" }, { status: 500 });
  }
}
