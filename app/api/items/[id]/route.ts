import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function PUT(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const { barcode, sku, name, category, unit, reorder_level } = await req.json();
  if (!barcode || !name || !unit) {
    return NextResponse.json({ error: "Name, barcode, and unit are required." }, { status: 400 });
  }
  try {
    const result = await pool.query(
      `UPDATE items SET barcode=$1, sku=$2, name=$3, category=$4, unit=$5, reorder_level=$6, updated_at=NOW()
       WHERE id=$7 AND is_active=true RETURNING *`,
      [barcode.trim(), sku?.trim() || null, name.trim(), category?.trim() || "General", unit, Number(reorder_level) || 0, id]
    );
    if (!result.rows.length) return NextResponse.json({ error: "Item not found." }, { status: 404 });
    return NextResponse.json(result.rows[0]);
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : "";
    if (msg.includes("unique") && msg.includes("barcode")) {
      return NextResponse.json({ error: "This barcode already exists." }, { status: 409 });
    }
    throw err;
  }
}

export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const result = await pool.query(
    "UPDATE items SET is_active=false, updated_at=NOW() WHERE id=$1 AND is_active=true RETURNING id",
    [id]
  );
  if (!result.rows.length) return NextResponse.json({ error: "Item not found." }, { status: 404 });
  return NextResponse.json({ ok: true });
}
