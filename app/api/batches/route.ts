import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function POST(req: NextRequest) {
  const { item_id, batch_reference, quantity, barcode_prefix } = await req.json();

  if (!item_id || !batch_reference || !quantity) {
    return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
  }

  if (quantity <= 0 || quantity > 10000) {
    return NextResponse.json({ error: "Quantity must be between 1 and 10000" }, { status: 400 });
  }

  try {
    // Check if item exists
    const itemResult = await pool.query("SELECT id, name, barcode FROM items WHERE id = $1", [item_id]);
    if (itemResult.rows.length === 0) {
      return NextResponse.json({ error: "Item not found" }, { status: 404 });
    }

    const item = itemResult.rows[0];

    // Check if batch reference already exists
    const batchExist = await pool.query(
      "SELECT id FROM barcode_batches WHERE batch_reference = $1",
      [batch_reference]
    );
    if (batchExist.rows.length > 0) {
      return NextResponse.json({ error: "Batch reference already exists" }, { status: 409 });
    }

    // Generate barcode prefix
    const prefix = barcode_prefix || `MF-${item.name.toUpperCase().replace(/\s+/g, "-")}-`;

    // Create batch record
    const batchResult = await pool.query(
      "INSERT INTO barcode_batches (item_id, batch_reference, quantity_total, barcode_prefix, status) VALUES ($1, $2, $3, $4, $5) RETURNING id",
      [item_id, batch_reference, quantity, prefix, "PENDING"]
    );

    const batch_id = batchResult.rows[0].id;

    // Generate individual barcodes
    const barcodes = [];
    const values = [];
    let valueIndex = 1;

    for (let i = 1; i <= quantity; i++) {
      const barcode_code = `${prefix}${String(i).padStart(6, "0")}`;
      barcodes.push({
        barcode: barcode_code,
        unit_number: i,
      });

      values.push(`($${valueIndex}, $${valueIndex + 1}, $${valueIndex + 2}, $${valueIndex + 3}, $${valueIndex + 4})`);
      valueIndex += 5;
    }

    // Bulk insert barcodes
    if (barcodes.length > 0) {
      const placeholders = barcodes
        .map((_, i) => {
          const idx = i * 5 + 1;
          return `($${idx}, $${idx + 1}, $${idx + 2}, $${idx + 3}, $${idx + 4})`;
        })
        .join(",");

      const flatValues = barcodes.flatMap((b, i) => [
        batch_id,
        b.barcode,
        item_id,
        b.unit_number,
        "UNSCANNED",
      ]);

      try {
        await pool.query(
          `INSERT INTO batch_barcodes (batch_id, barcode_code, item_id, unit_number, status) VALUES ${placeholders}`,
          flatValues
        );
      } catch (insertErr) {
        console.error("Barcode insertion error:", insertErr);
        throw insertErr;
      }

      // Update batch status
      await pool.query("UPDATE barcode_batches SET quantity_generated = $1, status = $2 WHERE id = $3", [
        quantity,
        "COMPLETED",
        batch_id,
      ]);
    }

    return NextResponse.json(
      {
        batch_id,
        batch_reference,
        item_name: item.name,
        total_generated: quantity,
        barcodes: barcodes.slice(0, 10), // Return first 10 for preview
        total_generated_full: quantity,
      },
      { status: 201 }
    );
  } catch (err) {
    const errorMsg = err instanceof Error ? err.message : String(err);
    console.error("Batch generation error:", errorMsg);
    return NextResponse.json({ error: "Failed to generate batch", details: errorMsg }, { status: 500 });
  }
}

// GET all batches
export async function GET(req: NextRequest) {
  try {
    const result = await pool.query(
      `SELECT
        bb.id, bb.batch_reference, bb.item_id, i.name as item_name,
        bb.quantity_total, bb.quantity_generated, bb.status, bb.created_at,
        COUNT(CASE WHEN bb2.status = 'SCANNED' THEN 1 END)::int as scanned_count
       FROM barcode_batches bb
       JOIN items i ON bb.item_id = i.id
       LEFT JOIN batch_barcodes bb2 ON bb.id = bb2.batch_id
       GROUP BY bb.id, bb.batch_reference, bb.item_id, i.name, bb.quantity_total, bb.quantity_generated, bb.status, bb.created_at
       ORDER BY bb.created_at DESC`
    );

    return NextResponse.json(result.rows);
  } catch (err) {
    console.error("Failed to fetch batches:", err);
    return NextResponse.json({ error: "Failed to fetch batches" }, { status: 500 });
  }
}
