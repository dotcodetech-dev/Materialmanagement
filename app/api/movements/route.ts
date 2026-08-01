import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";
import { getSession } from "@/lib/auth";

const OUTWARD_TYPES = ["OUTWARD", "RETURN_OUT", "ADJUSTMENT_OUT"];

export async function GET(req: NextRequest) {
  const sp = req.nextUrl.searchParams;
  const limit = Number(sp.get("limit")) || 200;
  const type = sp.get("type");
  const from = sp.get("from");
  const to = sp.get("to");
  const recordedBy = sp.get("recorded_by");
  const category = sp.get("category");

  const conditions: string[] = [];
  const params: (string | number)[] = [];

  if (type === "inward") conditions.push(`sm.movement_type IN ('INWARD','RETURN_IN','ADJUSTMENT_IN')`);
  else if (type === "outward") conditions.push(`sm.movement_type IN ('OUTWARD','RETURN_OUT','ADJUSTMENT_OUT')`);

  if (from) { params.push(from); conditions.push(`sm.occurred_at >= $${params.length}::date`); }
  if (to) { params.push(to); conditions.push(`sm.occurred_at < ($${params.length}::date + interval '1 day')`); }
  if (recordedBy) { params.push(recordedBy); conditions.push(`u.full_name = $${params.length}`); }
  if (category) { params.push(category); conditions.push(`i.category = $${params.length}`); }

  params.push(limit);
  const where = conditions.length ? `WHERE ${conditions.join(" AND ")}` : "";

  const result = await pool.query(
    `SELECT sm.id, sm.item_id, sm.customer_id, sm.movement_type,
            sm.quantity::float, sm.reference_number, sm.notes, sm.occurred_at,
            i.name AS item_name, i.barcode AS item_barcode, i.unit AS item_unit,
            i.category AS item_category,
            c.name AS customer_name,
            u.full_name AS recorded_by_name
     FROM stock_movements sm
     JOIN items i ON i.id = sm.item_id
     LEFT JOIN customers c ON c.id = sm.customer_id
     LEFT JOIN app_users u ON u.id = sm.recorded_by
     ${where}
     ORDER BY sm.occurred_at DESC
     LIMIT $${params.length}`,
    params
  );
  return NextResponse.json(result.rows);
}

export async function POST(req: NextRequest) {
  const session = await getSession();
  const { item_id, customer_id, movement_type, quantity, reference_number, notes } = await req.json();

  if (!item_id || !movement_type || !quantity || quantity <= 0) {
    return NextResponse.json({ error: "Item, movement type, and positive quantity are required." }, { status: 400 });
  }

  if (OUTWARD_TYPES.includes(movement_type)) {
    const stock = await pool.query(
      "SELECT available_quantity::float FROM item_stock_balance WHERE item_id = $1", [item_id]
    );
    const avail = stock.rows[0]?.available_quantity ?? 0;
    if (avail < quantity) {
      return NextResponse.json({ error: `Insufficient stock. Available: ${avail}` }, { status: 400 });
    }
  }

  const result = await pool.query(
    `INSERT INTO stock_movements (item_id, customer_id, movement_type, quantity, reference_number, notes, recorded_by)
     VALUES ($1, $2, $3, $4, $5, $6, $7)
     RETURNING id, movement_type, quantity::float, occurred_at`,
    [item_id, customer_id || null, movement_type, quantity, reference_number?.trim() || null, notes?.trim() || null, session?.id || null]
  );
  return NextResponse.json(result.rows[0], { status: 201 });
}
