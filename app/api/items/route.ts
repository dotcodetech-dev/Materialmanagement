import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function GET(req: NextRequest) {
  const q = req.nextUrl.searchParams.get("q");
  let query = `
    SELECT isb.item_id, isb.barcode, isb.sku, isb.name, isb.category, isb.unit,
           isb.reorder_level::float, isb.available_quantity::float
    FROM item_stock_balance isb
    JOIN items i ON i.id = isb.item_id
    WHERE i.is_active = true
  `;
  const params: string[] = [];
  if (q) {
    params.push(`%${q}%`);
    query += ` AND (isb.name ILIKE $1 OR isb.barcode ILIKE $1 OR isb.sku ILIKE $1 OR isb.category ILIKE $1)`;
  }
  query += ` ORDER BY isb.name`;
  const result = await pool.query(query, params);
  return NextResponse.json(result.rows);
}

export async function POST(req: NextRequest) {
  const { barcode, sku, name, category, unit, reorder_level } = await req.json();
  if (!barcode || !name || !unit) {
    return NextResponse.json({ error: "Name, barcode, and unit are required." }, { status: 400 });
  }
  try {
    const result = await pool.query(
      `INSERT INTO items (barcode, sku, name, category, unit, reorder_level)
       VALUES ($1, $2, $3, $4, $5, $6)
       RETURNING id, barcode, sku, name, category, unit, reorder_level::float`,
      [barcode.trim(), sku?.trim() || null, name.trim(), category?.trim() || "General", unit, Number(reorder_level) || 0]
    );
    return NextResponse.json(result.rows[0], { status: 201 });
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : "";
    if (msg.includes("unique") && msg.includes("barcode")) {
      return NextResponse.json({ error: "This barcode already exists." }, { status: 409 });
    }
    throw err;
  }
}
