import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function POST(req: NextRequest) {
  const { batch_id, action, printed_quantity, user_id, action_details } = await req.json();

  if (!batch_id || !action) {
    return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
  }

  try {
    // Log action to batch_history
    await pool.query(
      `INSERT INTO batch_history (batch_id, action, printed_quantity, printed_by, action_details)
       VALUES ($1, $2, $3, $4, $5)`,
      [batch_id, action, printed_quantity || null, user_id || null, action_details ? JSON.stringify(action_details) : null]
    );

    // Update barcode_batches with status if it's a print action
    if (action === "PRINTED" || action === "PARTIAL_PRINT") {
      await pool.query(
        `UPDATE barcode_batches
         SET total_printed = COALESCE(total_printed, 0) + $1,
             last_printed_at = NOW(),
             last_printed_by = $2,
             status_detail = CASE
               WHEN (COALESCE(total_printed, 0) + $1) >= quantity_total THEN 'FULLY_PRINTED'
               ELSE 'PARTIALLY_PRINTED'
             END
         WHERE id = $3`,
        [printed_quantity || 0, user_id || null, batch_id]
      );
    }

    return NextResponse.json({ success: true, message: "Action logged successfully" });
  } catch (err) {
    console.error("Batch history logging error:", err);
    return NextResponse.json({ error: "Failed to log batch action" }, { status: 500 });
  }
}

export async function GET(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const batch_id = searchParams.get("batch_id");

  try {
    let query = `
      SELECT
        bh.id, bh.batch_id, bh.action, bh.printed_quantity,
        bh.printed_by, u.full_name as printed_by_name,
        bh.action_details, bh.created_at
      FROM batch_history bh
      LEFT JOIN app_users u ON bh.printed_by = u.id
    `;

    const params = [];

    if (batch_id) {
      query += ` WHERE bh.batch_id = $1`;
      params.push(batch_id);
    }

    query += ` ORDER BY bh.created_at DESC LIMIT 100`;

    const result = await pool.query(query, params);
    return NextResponse.json(result.rows);
  } catch (err) {
    console.error("Failed to fetch batch history:", err);
    return NextResponse.json({ error: "Failed to fetch batch history" }, { status: 500 });
  }
}
