import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function POST(req: NextRequest) {
  const { batch_id, export_format, file_size, record_count, user_id } = await req.json();

  if (!batch_id || !export_format) {
    return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
  }

  try {
    // Log export to batch_exports
    const result = await pool.query(
      `INSERT INTO batch_exports (batch_id, export_format, exported_by, record_count, file_size)
       VALUES ($1, $2, $3, $4, $5)
       RETURNING id, created_at`,
      [batch_id, export_format, user_id || null, record_count || 0, file_size || 0]
    );

    return NextResponse.json({
      success: true,
      export_id: result.rows[0].id,
      exported_at: result.rows[0].created_at
    });
  } catch (err) {
    console.error("Batch export logging error:", err);
    return NextResponse.json({ error: "Failed to log export" }, { status: 500 });
  }
}

export async function GET(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const batch_id = searchParams.get("batch_id");

  try {
    let query = `
      SELECT
        be.id, be.batch_id, be.export_format, be.record_count,
        be.file_size, be.exported_by, u.full_name as exported_by_name,
        be.created_at
      FROM batch_exports be
      LEFT JOIN app_users u ON be.exported_by = u.id
    `;

    const params = [];

    if (batch_id) {
      query += ` WHERE be.batch_id = $1`;
      params.push(batch_id);
    }

    query += ` ORDER BY be.created_at DESC LIMIT 50`;

    const result = await pool.query(query, params);
    return NextResponse.json(result.rows);
  } catch (err) {
    console.error("Failed to fetch batch exports:", err);
    return NextResponse.json({ error: "Failed to fetch exports" }, { status: 500 });
  }
}
