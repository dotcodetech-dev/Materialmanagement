import { NextRequest, NextResponse } from "next/server";
import bcrypt from "bcryptjs";
import pool from "@/lib/db";

export async function POST(req: NextRequest) {
  const { fullName, email, password, role } = await req.json();

  if (!fullName || !email || !password) {
    return NextResponse.json({ error: "fullName, email, and password are required." }, { status: 400 });
  }

  const existing = await pool.query("SELECT count(*)::int AS count FROM app_users");
  if (existing.rows[0].count > 0) {
    return NextResponse.json({ error: "Users already exist. Use the app to manage users." }, { status: 409 });
  }

  const passwordHash = await bcrypt.hash(password, 12);
  const result = await pool.query(
    "INSERT INTO app_users (full_name, email, password_hash, role) VALUES ($1, $2, $3, $4) RETURNING id, full_name, email, role",
    [fullName, email, passwordHash, role || "ADMIN"]
  );

  return NextResponse.json({ user: result.rows[0] }, { status: 201 });
}
