import { NextRequest, NextResponse } from "next/server";
import bcrypt from "bcryptjs";
import pool from "@/lib/db";
import { createToken, setSessionCookie } from "@/lib/auth";

export async function POST(req: NextRequest) {
  const { email, password } = await req.json();

  if (!email || !password) {
    return NextResponse.json({ error: "Email and password are required." }, { status: 400 });
  }

  const result = await pool.query(
    "SELECT id, full_name, email, password_hash, role, is_active FROM app_users WHERE email = $1",
    [email]
  );

  const user = result.rows[0];
  if (!user) {
    return NextResponse.json({ error: "Invalid email or password." }, { status: 401 });
  }

  if (!user.is_active) {
    return NextResponse.json({ error: "Account is deactivated. Contact your admin." }, { status: 403 });
  }

  const valid = await bcrypt.compare(password, user.password_hash);
  if (!valid) {
    return NextResponse.json({ error: "Invalid email or password." }, { status: 401 });
  }

  const sessionUser = { id: user.id, fullName: user.full_name, email: user.email, role: user.role };
  const token = await createToken(sessionUser);
  await setSessionCookie(token);

  return NextResponse.json({ user: sessionUser });
}
