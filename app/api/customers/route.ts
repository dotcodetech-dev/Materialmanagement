import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";

export async function GET(req: NextRequest) {
  const q = req.nextUrl.searchParams.get("q");
  let query = "SELECT id, name, phone, email, address FROM customers WHERE is_active = true";
  const params: string[] = [];
  if (q) {
    params.push(`%${q}%`);
    query += ` AND (name ILIKE $1 OR phone ILIKE $1 OR email ILIKE $1)`;
  }
  query += " ORDER BY name";
  const result = await pool.query(query, params);
  return NextResponse.json(result.rows);
}

export async function POST(req: NextRequest) {
  const { name, phone, email, address } = await req.json();
  if (!name?.trim()) {
    return NextResponse.json({ error: "Customer name is required." }, { status: 400 });
  }
  const result = await pool.query(
    "INSERT INTO customers (name, phone, email, address) VALUES ($1, $2, $3, $4) RETURNING id, name, phone, email, address",
    [name.trim(), phone?.trim() || null, email?.trim() || null, address?.trim() || null]
  );
  return NextResponse.json(result.rows[0], { status: 201 });
}
