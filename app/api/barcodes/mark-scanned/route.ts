import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function POST(req: NextRequest) {
  const { barcode_id, movement_id, user_id } = await req.json();

  if (!barcode_id || !movement_id) {
    return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
  }

  try {
    // Update barcode status to SCANNED
    const result = await pool.query(
      `UPDATE batch_barcodes
       SET status = $1, scanned_at = NOW(), scanned_by = $2, movement_id = $3
       WHERE id = $4
       RETURNING barcode_code, status, scanned_at`,
      ["SCANNED", user_id, movement_id, barcode_id]
    );

    if (result.rows.length === 0) {
      return NextResponse.json({ error: "Barcode not found" }, { status: 404 });
    }

    const barcode = result.rows[0];

    return NextResponse.json({
      success: true,
      barcode_code: barcode.barcode_code,
      status: barcode.status,
      scanned_at: barcode.scanned_at,
    });
  } catch (err) {
    console.error("Mark scanned error:", err);
    return NextResponse.json({ error: "Failed to mark barcode as scanned" }, { status: 500 });
  }
}
