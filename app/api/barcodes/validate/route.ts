import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function POST(req: NextRequest) {
  const { barcode } = await req.json();

  if (!barcode) {
    return NextResponse.json({ error: "Barcode required" }, { status: 400 });
  }

  try {
    // Find barcode in batch_barcodes table
    const result = await pool.query(
      `SELECT
        bb.id, bb.barcode_code, bb.item_id, i.name as item_name, i.unit,
        bb.unit_number, bb.status, bb.scanned_at, bb.scanned_by, bb.movement_id,
        bat.batch_reference,
        u.full_name as scanned_by_name
       FROM batch_barcodes bb
       JOIN items i ON bb.item_id = i.id
       JOIN barcode_batches bat ON bb.batch_id = bat.id
       LEFT JOIN app_users u ON bb.scanned_by = u.id
       WHERE bb.barcode_code = $1`,
      [barcode.trim()]
    );

    if (result.rows.length === 0) {
      return NextResponse.json(
        {
          valid: false,
          error: "BARCODE_NOT_FOUND",
          message: "Barcode not found in batch system",
        },
        { status: 404 }
      );
    }

    const barcodeRecord = result.rows[0];

    // Check if already scanned
    if (barcodeRecord.status === "SCANNED") {
      return NextResponse.json(
        {
          valid: false,
          error: "BARCODE_ALREADY_SCANNED",
          message: "This barcode was already scanned",
          details: {
            barcode_code: barcodeRecord.barcode_code,
            scanned_at: barcodeRecord.scanned_at,
            scanned_by: barcodeRecord.scanned_by_name,
            batch_reference: barcodeRecord.batch_reference,
          },
        },
        { status: 409 }
      );
    }

    // Valid unscanned barcode
    return NextResponse.json({
      valid: true,
      barcode_id: barcodeRecord.id,
      barcode_code: barcodeRecord.barcode_code,
      item_id: barcodeRecord.item_id,
      item_name: barcodeRecord.item_name,
      item_unit: barcodeRecord.unit,
      unit_number: barcodeRecord.unit_number,
      batch_reference: barcodeRecord.batch_reference,
      status: barcodeRecord.status,
    });
  } catch (err) {
    console.error("Barcode validation error:", err);
    return NextResponse.json({ error: "Failed to validate barcode" }, { status: 500 });
  }
}
