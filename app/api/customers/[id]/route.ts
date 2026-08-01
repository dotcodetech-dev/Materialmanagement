import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function PUT(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const { name, phone, email, address } = await req.json();
  if (!name?.trim()) {
    return NextResponse.json({ error: "Customer name is required." }, { status: 400 });
  }
  const result = await pool.query(
    `UPDATE customers SET name=$1, phone=$2, email=$3, address=$4, updated_at=NOW()
     WHERE id=$5 AND is_active=true RETURNING id, name, phone, email, address`,
    [name.trim(), phone?.trim() || null, email?.trim() || null, address?.trim() || null, id]
  );
  if (!result.rows.length) return NextResponse.json({ error: "Customer not found." }, { status: 404 });
  return NextResponse.json(result.rows[0]);
}

export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const result = await pool.query(
    "UPDATE customers SET is_active=false, updated_at=NOW() WHERE id=$1 AND is_active=true RETURNING id",
    [id]
  );
  if (!result.rows.length) return NextResponse.json({ error: "Customer not found." }, { status: 404 });
  return NextResponse.json({ ok: true });
}
