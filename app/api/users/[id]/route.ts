import { NextRequest, NextResponse } from "next/server";
import bcrypt from "bcryptjs";
import pool from "@/lib/db";
import { getSession } from "@/lib/auth";

async function requireAdmin() {
  const session = await getSession();
  if (!session || session.role !== "ADMIN") return null;
  return session;
}

export async function PUT(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  if (!(await requireAdmin())) {
    return NextResponse.json({ error: "Admin access required." }, { status: 403 });
  }
  const { id } = await params;
  const { full_name, email, role, is_active, password } = await req.json();

  if (password) {
    const hash = await bcrypt.hash(password, 12);
    await pool.query("UPDATE app_users SET password_hash=$1 WHERE id=$2", [hash, id]);
  }

  const result = await pool.query(
    `UPDATE app_users SET full_name=COALESCE($1, full_name), email=COALESCE($2, email),
     role=COALESCE($3, role), is_active=COALESCE($4, is_active)
     WHERE id=$5 RETURNING id, full_name, email, role, is_active, created_at`,
    [full_name?.trim() || null, email?.trim()?.toLowerCase() || null, role || null, is_active, id]
  );
  if (!result.rows.length) return NextResponse.json({ error: "User not found." }, { status: 404 });
  return NextResponse.json(result.rows[0]);
}

export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const admin = await requireAdmin();
  if (!admin) return NextResponse.json({ error: "Admin access required." }, { status: 403 });
  const { id } = await params;
  if (id === admin.id) {
    return NextResponse.json({ error: "You cannot deactivate your own account." }, { status: 400 });
  }
  const result = await pool.query(
    "UPDATE app_users SET is_active=false WHERE id=$1 RETURNING id", [id]
  );
  if (!result.rows.length) return NextResponse.json({ error: "User not found." }, { status: 404 });
  return NextResponse.json({ ok: true });
}
