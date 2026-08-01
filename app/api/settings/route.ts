import { NextRequest, NextResponse } from "next/server";
import pool from "@/lib/db";
import { getSession } from "@/lib/auth";

const VALID_KEYS = ["company_name", "tagline", "logo_icon", "address", "phone", "email"];

async function ensureTable() {
  await pool.query(`CREATE TABLE IF NOT EXISTS app_settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT now()
  )`);
}

export async function GET() {
  await ensureTable();
  const result = await pool.query("SELECT key, value FROM app_settings");
  const settings: Record<string, string> = {};
  result.rows.forEach((r: { key: string; value: string }) => { settings[r.key] = r.value; });
  return NextResponse.json(settings);
}

export async function PUT(req: NextRequest) {
  const session = await getSession();
  if (!session || session.role !== "ADMIN") {
    return NextResponse.json({ error: "Admin access required." }, { status: 403 });
  }
  await ensureTable();
  const body = await req.json();
  for (const [key, value] of Object.entries(body)) {
    if (!VALID_KEYS.includes(key)) continue;
    await pool.query(
      `INSERT INTO app_settings (key, value, updated_at) VALUES ($1, $2, now())
       ON CONFLICT (key) DO UPDATE SET value = $2, updated_at = now()`,
      [key, String(value)]
    );
  }
  return NextResponse.json({ ok: true });
}
