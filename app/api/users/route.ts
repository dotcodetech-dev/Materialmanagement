import { NextRequest, NextResponse } from "next/server";
import bcrypt from "bcryptjs";
import pool from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAdmin() {
  const session = await getSession();
  if (!session || session.role !== "ADMIN") return null;
  return session;
}

export async function GET() {
  if (!(await requireAdmin())) {
    return NextResponse.json({ error: "Admin access required." }, { status: 403 });
  }
  const result = await pool.query(
    "SELECT id, full_name, email, role, is_active, created_at FROM app_users ORDER BY created_at DESC"
  );
  return NextResponse.json(result.rows);
}

export async function POST(req: NextRequest) {
  if (!(await requireAdmin())) {
    return NextResponse.json({ error: "Admin access required." }, { status: 403 });
  }
  const { full_name, email, password, role } = await req.json();
  if (!full_name?.trim() || !email?.trim() || !password) {
    return NextResponse.json({ error: "Name, email, and password are required." }, { status: 400 });
  }
  const validRoles = ["ADMIN", "STOREKEEPER", "MANAGER", "VIEWER"];
  if (role && !validRoles.includes(role)) {
    return NextResponse.json({ error: `Role must be one of: ${validRoles.join(", ")}` }, { status: 400 });
  }
  try {
    const hash = await bcrypt.hash(password, 12);
    const result = await pool.query(
      "INSERT INTO app_users (full_name, email, password_hash, role) VALUES ($1, $2, $3, $4) RETURNING id, full_name, email, role, is_active, created_at",
      [full_name.trim(), email.trim().toLowerCase(), hash, role || "STOREKEEPER"]
    );
    return NextResponse.json(result.rows[0], { status: 201 });
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : "";
    if (msg.includes("unique") && msg.includes("email")) {
      return NextResponse.json({ error: "This email is already registered." }, { status: 409 });
    }
    throw err;
  }
}
