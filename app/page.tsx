"use client";

import { FormEvent, useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import JsBarcode from "jsbarcode";

type SessionUser = { id: string; fullName: string; email: string; role: string };
type Item = { item_id: string; barcode: string; sku: string | null; name: string; category: string; unit: string; reorder_level: number; available_quantity: number };
type Customer = { id: string; name: string; phone: string | null; email: string | null; address: string | null };
type Movement = { id: string; item_id: string; customer_id: string | null; movement_type: string; quantity: number; reference_number: string | null; notes: string | null; occurred_at: string; item_name: string; item_barcode: string; item_unit: string; item_category?: string; customer_name: string | null; recorded_by_name: string | null };
type AppUser = { id: string; full_name: string; email: string; role: string; is_active: boolean; created_at: string };
type BrandSettings = { company_name: string; tagline: string; logo_icon: string; address: string; phone: string; email: string };
type BatchStatus = { id: string; batch_reference: string; item_name: string; quantity_total: number; quantity_generated: number; status: string; status_detail: string; total_printed: number; scanned_count: number; total_barcodes: number; total_actions: number; total_exports: number; last_printed_at: string | null; last_printed_by_name: string | null; created_at: string };

async function api(path: string, opts?: RequestInit) {
  const res = await fetch(`/api${path}`, { ...opts, headers: { "Content-Type": "application/json" } });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || "Request failed");
  return data;
}

export default function Home() {
  const router = useRouter();
  const scanRef = useRef<HTMLInputElement>(null);
  const [user, setUser] = useState<SessionUser | null>(null);
  const [tab, setTab] = useState("dashboard");
  const [items, setItems] = useState<Item[]>([]);
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [movements, setMovements] = useState<Movement[]>([]);
  const [appUsers, setAppUsers] = useState<AppUser[]>([]);
  const [notice, setNotice] = useState("");
  const [barcode, setBarcode] = useState("");
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [editItem, setEditItem] = useState<Item | null>(null);
  const [editCustomer, setEditCustomer] = useState<Customer | null>(null);
  const [editUser, setEditUser] = useState<AppUser | null>(null);
  const [labelItem, setLabelItem] = useState<Item | null>(null);
  const [labelQty, setLabelQty] = useState(1);
  const [labelSize, setLabelSize] = useState<"small" | "medium" | "large">("medium");
  const [selectedLabels, setSelectedLabels] = useState<Record<string, number>>({});
  const [reportTab, setReportTab] = useState<"inward" | "outward" | "stock">("inward");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [filterUser, setFilterUser] = useState("");
  const [filterCategory, setFilterCategory] = useState("");
  const [reportData, setReportData] = useState<Movement[]>([]);
  const [reportLoading, setReportLoading] = useState(false);
  const [settingsTab, setSettingsTab] = useState<"profile" | "users">("profile");
  const [brand, setBrand] = useState<BrandSettings>({ company_name: "MaterialFlow", tagline: "BARCODE INVENTORY", logo_icon: "▦", address: "", phone: "", email: "" });
  const [drawerOpen, setDrawerOpen] = useState(false);

  // Barcode scanning state
  type RecentScan = { id: string; name: string; barcode: string; timestamp: Date; saved: boolean; movementType: string };
  const [scanInput, setScanInput] = useState("");
  const [recentScans, setRecentScans] = useState<RecentScan[]>([]);
  const [scanMovementType, setScanMovementType] = useState<"INWARD" | "OUTWARD">("INWARD");
  const [isScanning, setIsScanning] = useState(false);

  // Batch generation state
  const [showBatchDialog, setShowBatchDialog] = useState(false);
  const [batchItem, setBatchItem] = useState<Item | null>(null);
  const [batchReference, setBatchReference] = useState("");
  const [batchQuantity, setBatchQuantity] = useState(100);
  const [batchPrefix, setBatchPrefix] = useState("");
  const [batchGenerating, setBatchGenerating] = useState(false);
  const [batchResult, setBatchResult] = useState<{ batch_reference: string; item_name: string; total_generated: number; barcodes: Array<{ barcode: string; unit_number: number }> } | null>(null);

  // Batch history state
  const [batchHistory, setBatchHistory] = useState<BatchStatus[]>([]);
  const [batchHistoryLoading, setBatchHistoryLoading] = useState(false);
  const [batchHistoryFilter, setBatchHistoryFilter] = useState("all");
  const [selectedBatchDetail, setSelectedBatchDetail] = useState<BatchStatus | null>(null);
  const [batchDetailData, setBatchDetailData] = useState<any>(null);
  const [batchDetailLoading, setBatchDetailLoading] = useState(false);
  const [printRangeStart, setPrintRangeStart] = useState(1);
  const [printRangeEnd, setPrintRangeEnd] = useState(0);

  // Determine if current context is Inward or Outward based on tab
  const isInwardTab = tab === "inward";
  const isOutwardTab = tab === "outward";

  // Filter scans by tab context (not by scanMovementType dropdown)
  const displayedScans = recentScans.filter(s => {
    if (isInwardTab) {
      return s.movementType === "INWARD" || s.movementType === "RETURN_IN" || s.movementType === "ADJUSTMENT_IN";
    } else if (isOutwardTab) {
      return s.movementType === "OUTWARD" || s.movementType === "RETURN_OUT" || s.movementType === "ADJUSTMENT_OUT";
    }
    return false;
  });

  // Auto-set movement type when switching tabs
  useEffect(() => {
    if (tab === "inward") {
      setScanMovementType("INWARD");
    } else if (tab === "outward") {
      setScanMovementType("OUTWARD");
    }
  }, [tab]);

  const reload = useCallback(async () => {
    try {
      const [i, c, m] = await Promise.all([api("/items"), api("/customers"), api("/movements")]);
      setItems(i); setCustomers(c); setMovements(m);
    } catch {}
  }, []);

  useEffect(() => {
    Promise.all([fetch("/api/auth/me").then(r => r.json()), api("/items"), api("/customers"), api("/movements"), api("/settings").catch(() => ({}))])
      .then(([u, i, c, m, s]) => { if (u.user) setUser(u.user); setItems(i); setCustomers(c); setMovements(m); if (s && Object.keys(s).length) setBrand(prev => ({ ...prev, ...s })); })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (user?.role === "ADMIN") api("/users").then(setAppUsers).catch(() => {});
  }, [user]);

  useEffect(() => {
    if (tab === "reports" && reportTab !== "stock") {
      setReportLoading(true);
      api(`/movements?type=${reportTab}&limit=1000`).then(setReportData).catch(() => {}).finally(() => setReportLoading(false));
    }
  }, [tab, reportTab]);

  useEffect(() => {
    if (tab === "batch-history") {
      setBatchHistoryLoading(true);
      api("/batch-status")
        .then(setBatchHistory)
        .catch(() => flash("Failed to load batch history"))
        .finally(() => setBatchHistoryLoading(false));
    }
  }, [tab]);

  useEffect(() => {
    if (selectedBatchDetail) {
      setBatchDetailLoading(true);
      api(`/batch-details?batch_id=${selectedBatchDetail.id}`)
        .then((data) => {
          setBatchDetailData(data);
          setPrintRangeEnd(data.batch.quantity_generated);
        })
        .catch(() => flash("Failed to load batch details"))
        .finally(() => setBatchDetailLoading(false));
    }
  }, [selectedBatchDetail]);

  const flash = (msg: string) => { setNotice(msg); setTimeout(() => setNotice(""), 5000); };
  const logout = async () => { await fetch("/api/auth/logout", { method: "POST" }); router.push("/login"); router.refresh(); };

  const handleBarcodeScanned = async (barcode: string) => {
    const trimmedBarcode = barcode.trim();

    // First, try to validate batch barcode
    try {
      const batchValidation = await api("/barcodes/validate", {
        method: "POST",
        body: JSON.stringify({ barcode: trimmedBarcode })
      });

      // Batch barcode found and valid
      if (batchValidation.valid) {
        try {
          // Create movement for this batch barcode
          const movementResponse = await api("/movements", {
            method: "POST",
            body: JSON.stringify({
              item_id: batchValidation.item_id,
              customer_id: null,
              movement_type: scanMovementType,
              quantity: 1,
              reference_number: `Batch: ${batchValidation.batch_reference}, Unit: ${batchValidation.unit_number}`,
              notes: `Batch scan - Unit ${batchValidation.unit_number}/${batchValidation.batch_reference}`
            })
          });

          // Mark barcode as scanned
          await api("/barcodes/mark-scanned", {
            method: "POST",
            body: JSON.stringify({
              barcode_id: batchValidation.barcode_id,
              movement_id: movementResponse.id,
              user_id: user?.id
            })
          });

          const newScan: RecentScan = {
            id: movementResponse.id,
            name: `${batchValidation.item_name} (Unit ${batchValidation.unit_number})`,
            barcode: trimmedBarcode,
            timestamp: new Date(),
            saved: true,
            movementType: scanMovementType
          };

          setRecentScans(prev => [newScan, ...prev].slice(0, 50));
          flash(`✓ ${batchValidation.item_name} - Unit ${batchValidation.unit_number} scanned`);
          await reload();
          setScanInput("");
          if (scanRef.current) scanRef.current.focus();
          return;
        } catch (err) {
          const errorMsg = err instanceof Error ? err.message : "Failed to save scan";
          flash(`❌ ${errorMsg}`);
          setScanInput("");
          if (scanRef.current) scanRef.current.focus();
          return;
        }
      }
    } catch (batchErr: any) {
      // Batch barcode not found or already scanned
      if (batchErr.message?.includes("ALREADY_SCANNED")) {
        flash(`❌ Barcode already scanned! Previously scanned by ${batchErr.details?.scanned_by || "user"} on ${new Date(batchErr.details?.scanned_at).toLocaleString()}`);
        setScanInput("");
        if (scanRef.current) scanRef.current.focus();
        return;
      }

      // If not a batch barcode, fall back to regular item barcode
    }

    // Fallback: check regular item barcodes
    const item = items.find(i => i.barcode.toLowerCase() === trimmedBarcode.toLowerCase());

    if (!item) {
      flash(`❌ Barcode not found: ${barcode}`);
      setScanInput("");
      return;
    }

    try {
      const response = await api("/movements", {
        method: "POST",
        body: JSON.stringify({
          item_id: item.item_id,
          customer_id: null,
          movement_type: scanMovementType,
          quantity: 1,
          reference_number: null,
          notes: `Quick scan - ${new Date().toLocaleTimeString()}`
        })
      });

      const newScan: RecentScan = {
        id: response.id,
        name: item.name,
        barcode: item.barcode,
        timestamp: new Date(),
        saved: true,
        movementType: scanMovementType
      };

      setRecentScans(prev => [newScan, ...prev].slice(0, 50));
      flash(`✓ ${item.name} saved`);
      await reload();
    } catch (err) {
      const errorMsg = err instanceof Error ? err.message : "Save failed";
      const failedScan: RecentScan = {
        id: Date.now().toString(),
        name: item.name,
        barcode: item.barcode,
        timestamp: new Date(),
        saved: false,
        movementType: scanMovementType
      };
      setRecentScans(prev => [failedScan, ...prev].slice(0, 50));
      flash(`❌ ${errorMsg}`);
    }

    setScanInput("");
    if (scanRef.current) scanRef.current.focus();
  };

  const low = useMemo(() => items.filter(i => i.available_quantity <= i.reorder_level), [items]);
  const stockTotal = useMemo(() => items.reduce((a, i) => a + i.available_quantity, 0), [items]);
  const todayMoves = useMemo(() => { const d = new Date().toISOString().slice(0, 10); return movements.filter(m => m.occurred_at?.slice(0, 10) === d); }, [movements]);
  const recent = useMemo(() => movements.slice(0, 6), [movements]);
  const categories = useMemo(() => [...new Set(items.map(i => i.category).filter(Boolean))].sort(), [items]);
  const recorderNames = useMemo(() => [...new Set(movements.map(m => m.recorded_by_name).filter(Boolean) as string[])].sort(), [movements]);
  const stockReport = useMemo(() => filterCategory && tab === "reports" && reportTab === "stock" ? items.filter(i => i.category === filterCategory) : items, [items, tab, reportTab, filterCategory]);

  const findScanned = () => {
    const item = items.find(i => i.barcode.toLowerCase() === barcode.trim().toLowerCase());
    if (item) { flash(`${item.name} found (${item.available_quantity} ${item.unit} available).`); setTab("outward"); }
    else flash("Barcode not found. Create the item first.");
  };

  const addItem = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault(); const f = new FormData(e.currentTarget); const form = e.currentTarget;
    const itemName = String(f.get("name")).trim();
    if (items.some(i => i.name.toLowerCase() === itemName.toLowerCase())) {
      return flash(`Item "${itemName}" already exists. Use a different name or edit the existing item.`);
    }
    try {
      await api("/items", { method: "POST", body: JSON.stringify({ barcode: f.get("barcode"), sku: f.get("sku"), name: f.get("name"), category: f.get("category"), unit: f.get("unit"), reorder_level: f.get("reorder_level") }) });
      form.reset(); await reload(); flash("Item added successfully.");
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to add item."); }
  };

  const updateItem = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault(); if (!editItem) return; const f = new FormData(e.currentTarget);
    try {
      await api(`/items/${editItem.item_id}`, { method: "PUT", body: JSON.stringify({ barcode: f.get("barcode"), sku: f.get("sku"), name: f.get("name"), category: f.get("category"), unit: f.get("unit"), reorder_level: f.get("reorder_level") }) });
      setEditItem(null); await reload(); flash("Item updated.");
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to update."); }
  };

  const deleteItem = async (item: Item) => {
    if (!confirm(`Delete "${item.name}"? This cannot be undone.`)) return;
    try { await api(`/items/${item.item_id}`, { method: "DELETE" }); await reload(); flash(`${item.name} deleted.`); }
    catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to delete."); }
  };

  const addCustomer = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault(); const f = new FormData(e.currentTarget); const form = e.currentTarget;
    try {
      await api("/customers", { method: "POST", body: JSON.stringify({ name: f.get("name"), phone: f.get("phone"), email: f.get("email"), address: f.get("address") }) });
      form.reset(); await reload(); flash("Customer added.");
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to add customer."); }
  };

  const updateCustomer = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault(); if (!editCustomer) return; const f = new FormData(e.currentTarget);
    try {
      await api(`/customers/${editCustomer.id}`, { method: "PUT", body: JSON.stringify({ name: f.get("name"), phone: f.get("phone"), email: f.get("email"), address: f.get("address") }) });
      setEditCustomer(null); await reload(); flash("Customer updated.");
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to update."); }
  };

  const deleteCustomer = async (c: Customer) => {
    if (!confirm(`Delete "${c.name}"?`)) return;
    try { await api(`/customers/${c.id}`, { method: "DELETE" }); await reload(); flash(`${c.name} deleted.`); }
    catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to delete."); }
  };

  const recordMovement = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault(); const f = new FormData(e.currentTarget); const form = e.currentTarget;
    try {
      const mt = String(f.get("movement_type"));
      await api("/movements", { method: "POST", body: JSON.stringify({ item_id: f.get("item_id"), customer_id: f.get("customer_id") || null, movement_type: mt, quantity: Number(f.get("quantity")), reference_number: f.get("reference"), notes: f.get("notes") }) });
      form.reset(); await reload();
      flash(`${mt.replace("_", " ")} recorded.`);
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to record."); }
  };

  const addUser = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault(); const f = new FormData(e.currentTarget); const form = e.currentTarget;
    try {
      await api("/users", { method: "POST", body: JSON.stringify({ full_name: f.get("full_name"), email: f.get("email"), password: f.get("password"), role: f.get("role") }) });
      form.reset(); const u = await api("/users"); setAppUsers(u); flash("User created.");
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to create user."); }
  };

  const updateUserSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault(); if (!editUser) return; const f = new FormData(e.currentTarget);
    try {
      const body: Record<string, unknown> = { full_name: f.get("full_name"), email: f.get("email"), role: f.get("role") };
      const pw = String(f.get("password") || "");
      if (pw) body.password = pw;
      await api(`/users/${editUser.id}`, { method: "PUT", body: JSON.stringify(body) });
      setEditUser(null); const u = await api("/users"); setAppUsers(u); flash("User updated.");
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to update user."); }
  };

  const toggleUser = async (u: AppUser) => {
    if (u.id === user?.id) return flash("You cannot deactivate yourself.");
    try {
      await api(`/users/${u.id}`, { method: "PUT", body: JSON.stringify({ is_active: !u.is_active }) });
      const list = await api("/users"); setAppUsers(list); flash(`${u.full_name} ${u.is_active ? "deactivated" : "activated"}.`);
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed."); }
  };

  const saveBranding = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault(); const f = new FormData(e.currentTarget);
    const data: BrandSettings = { company_name: String(f.get("company_name") || ""), tagline: String(f.get("tagline") || ""), logo_icon: String(f.get("logo_icon") || "▦"), address: String(f.get("address") || ""), phone: String(f.get("phone") || ""), email: String(f.get("email") || "") };
    try {
      await api("/settings", { method: "PUT", body: JSON.stringify(data) });
      setBrand(data); flash("Branding saved successfully.");
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to save branding."); }
  };

  const generateBarcode = () => {
    const mfBarcodes = items.filter(i => i.barcode.match(/^MF-\d+$/)).map(i => parseInt(i.barcode.match(/\d+/)![0]));
    let num = Math.max(0, ...mfBarcodes) + 1;
    let barcode = `MF-${String(num).padStart(5, "0")}`;
    while (items.some(i => i.barcode === barcode)) {
      num++;
      barcode = `MF-${String(num).padStart(5, "0")}`;
    }
    return barcode;
  };

  const generateBatch = async () => {
    if (!batchItem || !batchReference || batchQuantity <= 0) {
      flash("Please fill all fields");
      return;
    }

    setBatchGenerating(true);
    try {
      const response = await api("/batches", {
        method: "POST",
        body: JSON.stringify({
          item_id: batchItem.item_id,
          batch_reference: batchReference,
          quantity: batchQuantity,
          barcode_prefix: batchPrefix || undefined
        })
      });

      flash(`✓ Generated ${response.total_generated_full} barcodes for batch ${batchReference}`);
      setBatchResult(response);
      setBatchReference("");
      setBatchQuantity(100);
      setBatchPrefix("");
    } catch (err: unknown) {
      flash(err instanceof Error ? err.message : "Failed to generate batch");
    } finally {
      setBatchGenerating(false);
    }
  };

  const exportBatchAsCSV = async (batch: BatchStatus) => {
    try {
      const response = await fetch(`/api/batch-export-csv?batch_id=${batch.id}&user_id=${user?.id}`);
      if (!response.ok) throw new Error("Export failed");
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `${batch.batch_reference}.csv`;
      a.click();
      window.URL.revokeObjectURL(url);
      flash(`✓ Exported ${batch.batch_reference} as CSV`);
    } catch (err) {
      flash(err instanceof Error ? err.message : "Failed to export CSV");
    }
  };

  const reprintBatch = async (batch: BatchStatus) => {
    try {
      // Fetch barcode data for the batch
      const response = await api(`/batch-details?batch_id=${batch.id}`);
      if (!response || !response.barcodes) throw new Error("No barcode data found");

      const batchData = {
        batch_reference: batch.batch_reference,
        item_name: batch.item_name,
        total_generated: batch.quantity_generated,
        barcodes: response.barcodes
      };

      // Use the existing printBatchLabels function
      printBatchLabels(batchData);

      // Log the print action
      await api("/batch-history", {
        method: "POST",
        body: JSON.stringify({
          batch_id: batch.id,
          action: "PRINTED",
          printed_quantity: batch.quantity_generated,
          user_id: user?.id
        })
      });

      flash(`✓ Reprinted ${batch.batch_reference}`);
    } catch (err) {
      flash(err instanceof Error ? err.message : "Failed to reprint batch");
    }
  };

  const printBatchRange = async () => {
    if (!selectedBatchDetail || !batchDetailData) return;

    const start = Math.max(1, Math.min(printRangeStart, selectedBatchDetail.quantity_generated));
    const end = Math.max(start, Math.min(printRangeEnd, selectedBatchDetail.quantity_generated));
    const rangeQty = end - start + 1;

    if (rangeQty <= 0) {
      flash("Invalid range selected");
      return;
    }

    try {
      // Filter barcodes for the selected range
      const rangeBarcodes = batchDetailData.batch.barcodes?.filter((b: any) => b.unit_number >= start && b.unit_number <= end) || [];

      if (rangeBarcodes.length === 0) {
        flash("No barcodes found for selected range");
        return;
      }

      const batchData = {
        batch_reference: `${selectedBatchDetail.batch_reference} (Units ${start}-${end})`,
        item_name: selectedBatchDetail.item_name,
        total_generated: rangeBarcodes.length,
        barcodes: rangeBarcodes
      };

      printBatchLabels(batchData);

      await api("/batch-history", {
        method: "POST",
        body: JSON.stringify({
          batch_id: selectedBatchDetail.id,
          action: "PARTIAL_PRINT",
          printed_quantity: rangeQty,
          user_id: user?.id
        })
      });

      flash(`✓ Printed ${rangeQty} barcodes (${start}-${end})`);
    } catch (err) {
      flash(err instanceof Error ? err.message : "Failed to print range");
    }
  };

  const toggleLabel = (id: string) => {
    setSelectedLabels(prev => { const next = { ...prev }; if (next[id]) delete next[id]; else next[id] = 1; return next; });
  };
  const setLabelCount = (id: string, qty: number) => {
    setSelectedLabels(prev => ({ ...prev, [id]: Math.max(1, qty) }));
  };
  const selectAllLabels = () => {
    if (Object.keys(selectedLabels).length === items.length) setSelectedLabels({});
    else setSelectedLabels(Object.fromEntries(items.map(i => [i.item_id, selectedLabels[i.item_id] || 1])));
  };

  const printBulkLabels = () => {
    const selected = items.filter(i => selectedLabels[i.item_id]);
    if (!selected.length) return flash("Select at least one item to print labels.");
    const sizes = { small: { w: 145, h: 95, bw: 1.2, bh: 30, fs: 9, cols: 5 }, medium: { w: 200, h: 120, bw: 1.5, bh: 40, fs: 11, cols: 3 }, large: { w: 280, h: 160, bw: 2, bh: 55, fs: 13, cols: 2 } };
    const sz = sizes[labelSize];
    const w = window.open("", "_blank"); if (!w) return;
    w.document.write(`<html><head><title>Barcode Labels</title><style>
      *{box-sizing:border-box;margin:0;padding:0}
      body{font-family:Arial,sans-serif}
      .grid{display:flex;flex-wrap:wrap;padding:8px}
      .label{width:${sz.w}px;height:${sz.h}px;border:1px dashed #ccc;padding:6px;text-align:center;display:flex;flex-direction:column;justify-content:center;align-items:center;page-break-inside:avoid;overflow:hidden}
      .label h4{font-size:${sz.fs}px;margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
      .label p{font-size:${Math.max(sz.fs - 3, 7)}px;color:#666;margin:0 0 3px}
      @media print{.label{border:none}.no-print{display:none !important}}
    </style></head><body>
    <div class="no-print" style="padding:12px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center">
      <b>Barcode Labels — ${selected.reduce((s, i) => s + (selectedLabels[i.item_id] || 1), 0)} labels</b>
      <button onclick="window.print()" style="background:#006b5f;color:#fff;border:0;border-radius:8px;padding:10px 24px;font-weight:700;font-size:14px;cursor:pointer">Print</button>
    </div>
    <div class="grid" id="labels"></div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3/dist/JsBarcode.all.min.js"><\/script>
    <script>
      const items = ${JSON.stringify(selected.map(i => ({ barcode: i.barcode, name: i.name, category: i.category, unit: i.unit, qty: selectedLabels[i.item_id] || 1 })))};
      const grid = document.getElementById("labels");
      items.forEach(item => {
        for (let q = 0; q < item.qty; q++) {
          const div = document.createElement("div"); div.className = "label";
          div.innerHTML = '<h4>' + item.name + '</h4><p>' + item.category + ' · ' + item.unit + '</p><svg></svg>';
          grid.appendChild(div);
          JsBarcode(div.querySelector("svg"), item.barcode, {format:"CODE128",width:${sz.bw},height:${sz.bh},displayValue:true,fontSize:${sz.fs},margin:2,textMargin:1});
        }
      });
    <\/script></body></html>`);
    w.document.close();
  };

  const exportCSV = () => {
    const headers = ["Date", "Type", "Item", "Barcode", "Qty", "Unit", "Customer", "Reference", "Notes", "Recorded by"];
    const rows = movements.map(m => [m.occurred_at, m.movement_type, m.item_name, m.item_barcode, m.quantity, m.item_unit, m.customer_name || "", m.reference_number || "", m.notes || "", m.recorded_by_name || ""]);
    const csv = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(",")).join("\n");
    const blob = new Blob(["﻿" + csv], { type: "text/csv;charset=utf-8" });
    const a = document.createElement("a"); a.href = URL.createObjectURL(blob); a.download = "stock-movements.csv"; a.click();
  };

  const applyReportFilters = async () => {
    if (reportTab === "stock") return;
    setReportLoading(true);
    try {
      const params = new URLSearchParams({ type: reportTab, limit: "1000" });
      if (dateFrom) params.set("from", dateFrom);
      if (dateTo) params.set("to", dateTo);
      if (filterUser) params.set("recorded_by", filterUser);
      if (filterCategory) params.set("category", filterCategory);
      const data = await api(`/movements?${params}`);
      setReportData(data);
    } catch (err: unknown) { flash(err instanceof Error ? err.message : "Failed to load report."); }
    finally { setReportLoading(false); }
  };

  const clearReportFilters = () => {
    setDateFrom(""); setDateTo(""); setFilterUser(""); setFilterCategory("");
    if (reportTab !== "stock") {
      setReportLoading(true);
      api(`/movements?type=${reportTab}&limit=1000`).then(setReportData).catch(() => {}).finally(() => setReportLoading(false));
    }
  };

  const printReport = () => {
    const title = reportTab === "stock" ? "Stock Report" : `${reportTab === "inward" ? "Inward" : "Outward"} Report`;
    const dateRange = dateFrom || dateTo ? `${dateFrom || "..."} to ${dateTo || "..."}` : "All dates";
    let tableHTML = "";
    if (reportTab === "stock") {
      const data = stockReport;
      tableHTML = `<table><thead><tr><th>Item</th><th>Barcode</th><th>Category</th><th>Unit</th><th>Available</th><th>Reorder Level</th><th>Status</th></tr></thead><tbody>`;
      data.forEach(i => {
        const low = i.available_quantity <= i.reorder_level;
        tableHTML += `<tr><td>${i.name}</td><td>${i.barcode}</td><td>${i.category}</td><td>${i.unit}</td><td>${i.available_quantity}</td><td>${i.reorder_level}</td><td class="${low ? "low" : ""}">${low ? "LOW" : "OK"}</td></tr>`;
      });
      tableHTML += `</tbody><tfoot><tr><td colspan="4"><b>Total: ${data.length} items</b></td><td><b>${Math.round(data.reduce((s, i) => s + i.available_quantity, 0))}</b></td><td colspan="2"></td></tr></tfoot></table>`;
    } else {
      tableHTML = `<table><thead><tr><th>Date</th><th>Type</th><th>Item</th><th>Category</th><th>Qty</th><th>Customer</th><th>Reference</th><th>Recorded by</th></tr></thead><tbody>`;
      reportData.forEach(m => {
        tableHTML += `<tr><td>${new Date(m.occurred_at).toLocaleString("en-IN", { dateStyle: "medium", timeStyle: "short" })}</td><td>${m.movement_type.replace("_", " ")}</td><td>${m.item_name}</td><td>${m.item_category || ""}</td><td>${m.quantity} ${m.item_unit}</td><td>${m.customer_name || "—"}</td><td>${m.reference_number || "—"}</td><td>${m.recorded_by_name || "—"}</td></tr>`;
      });
      const total = reportData.reduce((s, m) => s + m.quantity, 0);
      tableHTML += `</tbody><tfoot><tr><td colspan="4"><b>Total: ${reportData.length} records</b></td><td><b>${Math.round(total * 100) / 100}</b></td><td colspan="3"></td></tr></tfoot></table>`;
    }
    const w = window.open("", "_blank"); if (!w) return;
    const co = brand.company_name || "MaterialFlow";
    const coInfo = [brand.address, brand.phone, brand.email].filter(Boolean).join(" · ");
    w.document.write(`<html><head><title>${title} - ${co}</title><style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,sans-serif;padding:24px}.co{font-size:22px;font-weight:800;margin-bottom:2px}.co-info{color:#888;font-size:11px;margin-bottom:14px}h1{font-size:18px;margin-bottom:4px;border-bottom:2px solid #333;padding-bottom:6px}.meta{color:#666;font-size:12px;margin:8px 0 16px}table{width:100%;border-collapse:collapse;font-size:12px}th{text-align:left;border-bottom:2px solid #333;padding:8px 6px;font-size:11px;text-transform:uppercase}td{padding:7px 6px;border-bottom:1px solid #ddd}tfoot td{border-top:2px solid #333;border-bottom:none;padding-top:10px}.low{color:red;font-weight:bold}.no-print{padding:8px 0;margin-bottom:16px}@media print{.no-print{display:none}}</style></head><body><div class="no-print"><button onclick="window.print()" style="background:#006b5f;color:#fff;border:0;border-radius:8px;padding:10px 24px;font-weight:700;cursor:pointer">Print</button></div><p class="co">${co}</p>${coInfo ? `<p class="co-info">${coInfo}</p>` : ""}<h1>${title}</h1><p class="meta">Generated: ${new Date().toLocaleString("en-IN")}${reportTab !== "stock" ? ` | Period: ${dateRange}` : ""}${filterCategory ? ` | Category: ${filterCategory}` : ""}${filterUser ? ` | User: ${filterUser}` : ""}</p>${tableHTML}</body></html>`);
    w.document.close();
  };

  const exportReportCSV = () => {
    if (reportTab === "stock") {
      const data = stockReport;
      const headers = ["Item", "Barcode", "SKU", "Category", "Unit", "Available Qty", "Reorder Level", "Status"];
      const rows = data.map(i => [i.name, i.barcode, i.sku || "", i.category, i.unit, i.available_quantity, i.reorder_level, i.available_quantity <= i.reorder_level ? "LOW" : "OK"]);
      const csv = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(",")).join("\n");
      const blob = new Blob(["﻿" + csv], { type: "text/csv;charset=utf-8" });
      const a = document.createElement("a"); a.href = URL.createObjectURL(blob); a.download = `stock-report-${new Date().toISOString().slice(0, 10)}.csv`; a.click();
    } else {
      const headers = ["Date", "Type", "Item", "Category", "Qty", "Unit", "Customer", "Reference", "Notes", "Recorded by"];
      const rows = reportData.map(m => [new Date(m.occurred_at).toLocaleString("en-IN"), m.movement_type, m.item_name, m.item_category || "", m.quantity, m.item_unit || "", m.customer_name || "", m.reference_number || "", m.notes || "", m.recorded_by_name || ""]);
      const csv = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(",")).join("\n");
      const blob = new Blob(["﻿" + csv], { type: "text/csv;charset=utf-8" });
      const a = document.createElement("a"); a.href = URL.createObjectURL(blob); a.download = `${reportTab}-report-${new Date().toISOString().slice(0, 10)}.csv`; a.click();
    }
  };

  const filtered = useMemo(() => {
    if (!search) return { items, customers, movements };
    const s = search.toLowerCase();
    return {
      items: items.filter(i => i.name.toLowerCase().includes(s) || i.barcode.toLowerCase().includes(s) || i.category.toLowerCase().includes(s)),
      customers: customers.filter(c => c.name.toLowerCase().includes(s) || c.phone?.toLowerCase().includes(s) || c.email?.toLowerCase().includes(s)),
      movements: movements.filter(m => m.item_name.toLowerCase().includes(s) || m.movement_type.toLowerCase().includes(s) || m.reference_number?.toLowerCase().includes(s) || m.customer_name?.toLowerCase().includes(s)),
    };
  }, [search, items, customers, movements]);

  const isAdmin = user?.role === "ADMIN";
  const canEdit = user?.role === "ADMIN" || user?.role === "MANAGER" || user?.role === "STOREKEEPER";
  const tabs: [string, string, string][] = [
    ["dashboard", "dashboard", "Dashboard"],
    ["items", "inventory_2", "Items"],
    ["customers", "group", "Customers"],
    ["inward", "move_to_inbox", "Inward"],
    ["outward", "outbox", "Outward"],
    ["stock", "menu_book", "Stock Ledger"],
    ["reports", "assessment", "Reports"],
    ["batch-history", "history", "Batch History"],
  ];
  if (isAdmin) tabs.push(["settings", "settings", "Settings"]);

  if (loading) return <div className="loading">Loading MaterialFlow...</div>;

  return <div className="app-shell">
    {/* Mobile top bar */}
    <header className="topbar">
      <button className="topbar-btn" onClick={() => setDrawerOpen(!drawerOpen)}>
        <span className="material-symbols-outlined">menu</span>
      </button>
      <h1 className="topbar-title">{brand.company_name || "MaterialFlow"}</h1>
      <div className="topbar-avatar">{user?.fullName?.[0] || "?"}</div>
    </header>

    {/* Drawer overlay */}
    {drawerOpen && <div className="drawer-overlay" onClick={() => setDrawerOpen(false)} />}

    {/* Sidebar */}
    <aside className={`sidebar${drawerOpen ? " open" : ""}`}>
      <div className="sidebar-header">
        <div className="sidebar-logo-icon">{brand.logo_icon || "▦"}</div>
        <div>
          <div className="sidebar-name">{brand.company_name || "MaterialFlow"}</div>
          <div className="sidebar-tagline">{brand.tagline || "BARCODE INVENTORY"}</div>
        </div>
      </div>
      <nav className="sidebar-nav">
        {tabs.map(([id, icon, label]) => (
          <button key={id} className={tab === id ? "sidebar-link active" : "sidebar-link"} onClick={() => { setTab(id); setSearch(""); setDrawerOpen(false); }}>
            <span className="material-symbols-outlined">{icon}</span>
            <span>{label}</span>
          </button>
        ))}
      </nav>
      <div className="sidebar-footer">
        <button className="sidebar-link" onClick={() => { logout(); setDrawerOpen(false); }}>
          <span className="material-symbols-outlined">logout</span>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    {/* Main content */}
    <main className="main-content">
      <header className="content-header">
        <div className="header-left">
          <p className="eyebrow">MATERIAL MANAGEMENT</p>
          <h1>{tab === "dashboard" ? `Welcome, ${user?.fullName || "User"}` : tabs.find(t => t[0] === tab)?.[2] || tab}</h1>
        </div>
        <div className="header-right">
          <div className="scanner">
            <span className="material-symbols-outlined">qr_code_scanner</span>
            <input ref={scanRef} value={barcode} onChange={e => setBarcode(e.target.value)} onKeyDown={e => e.key === "Enter" && findScanned()} placeholder="Scan barcode or type SKU" />
            <button onClick={findScanned}>Scan</button>
          </div>
          {user && <div className="user-bar">
            <div className="user-info"><b>{user.fullName}</b><small>{user.role}</small></div>
            <button className="logout-btn" onClick={logout}><span className="material-symbols-outlined">logout</span></button>
          </div>}
        </div>
      </header>

      {notice && <p className="notice">{notice}</p>}

      {tab !== "dashboard" && tab !== "inward" && tab !== "outward" && tab !== "labels" && tab !== "reports" && tab !== "settings" && <div className="search-bar"><input value={search} onChange={e => setSearch(e.target.value)} placeholder={`Search ${tab}...`} className="search-input" />{search && <button className="link" onClick={() => setSearch("")}>Clear</button>}</div>}

      {/* DASHBOARD */}
      {tab === "dashboard" && <>
        <div className="stats">
          <Card label="Total items" value={items.length} icon="inventory_2" />
          <Card label="Units in stock" value={Math.round(stockTotal)} icon="widgets" />
          <Card label="Low-stock alerts" value={low.length} icon="warning" warning />
          <Card label="Today's movements" value={todayMoves.length} icon="sync_alt" />
        </div>
        <div className="grid two">
          <Panel title="Low stock" action={() => setTab("items")} actionLabel="View items">
            {low.length ? <table><thead><tr><th>Item</th><th>Available</th><th>Reorder level</th></tr></thead><tbody>{low.map(i => <tr key={i.item_id}><td><b>{i.name}</b><small>{i.barcode}</small></td><td className="danger">{i.available_quantity} {i.unit}</td><td>{i.reorder_level} {i.unit}</td></tr>)}</tbody></table> : <Empty text="All items are above reorder level." />}
          </Panel>
          <Panel title="Recent activity">
            {recent.length ? <Activity moves={recent} /> : <Empty text="No transactions yet." />}
          </Panel>
        </div>
      </>}

      {/* ITEMS */}
      {tab === "items" && <div className="grid form-grid">
        {canEdit && <Panel title="Add item"><form onSubmit={addItem}>
          <Field label="Item name *" name="name" required />
          <label>Barcode<span className="required"> *</span><div className="input-with-btn"><input name="barcode" placeholder="e.g. MAT-10004" required /><button type="button" className="gen-btn" onClick={e => { const inp = e.currentTarget.previousElementSibling as HTMLInputElement; inp.value = generateBarcode(); }}>Generate</button></div></label>
          <Field label="SKU (optional)" name="sku" />
          <Field label="Category *" name="category" />
          <div className="split"><Select label="Unit *" name="unit" options={["Nos", "Kg", "Meters", "Liters", "Boxes", "Pairs"]} /><Field label="Reorder level *" name="reorder_level" type="number" /></div>
          <button className="primary">Save item</button>
        </form></Panel>}
        <Panel title={`Item catalogue (${filtered.items.length})`}>
          <table><thead><tr><th>Item</th><th>Barcode</th><th>Stock</th>{canEdit && <th>Actions</th>}</tr></thead>
          <tbody>{filtered.items.map(i => <tr key={i.item_id}>
            <td><b>{i.name}</b><small>{i.category}</small></td>
            <td><code>{i.barcode}</code>{i.sku && <small>{i.sku}</small>}</td>
            <td className={i.available_quantity <= i.reorder_level ? "danger" : ""}>{i.available_quantity} {i.unit}</td>
            {canEdit && <td className="actions"><button className="act" onClick={() => { setBatchItem(i); setShowBatchDialog(true); }} title="Generate batch"><span className="material-symbols-outlined">qr_code_2</span></button><button className="act" onClick={() => setEditItem(i)} title="Edit"><span className="material-symbols-outlined">edit</span></button><button className="act danger-act" onClick={() => deleteItem(i)} title="Delete"><span className="material-symbols-outlined">delete</span></button></td>}
          </tr>)}</tbody></table>
          {!filtered.items.length && <Empty text="No items found." />}
        </Panel>
      </div>}

      {/* CUSTOMERS */}
      {tab === "customers" && <div className="grid form-grid">
        {canEdit && <Panel title="Add customer"><form onSubmit={addCustomer}>
          <Field label="Customer / company name *" name="name" required />
          <Field label="Phone number" name="phone" placeholder="+91 ..." />
          <Field label="Email" name="email" type="email" />
          <Field label="Address" name="address" />
          <button className="primary">Save customer</button>
        </form></Panel>}
        <Panel title={`Customers (${filtered.customers.length})`}>
          <table><thead><tr><th>Name</th><th>Phone</th><th>Email</th>{canEdit && <th>Actions</th>}</tr></thead>
          <tbody>{filtered.customers.map(c => <tr key={c.id}>
            <td><b>{c.name}</b>{c.address && <small>{c.address}</small>}</td>
            <td>{c.phone || "—"}</td><td>{c.email || "—"}</td>
            {canEdit && <td className="actions"><button className="act" onClick={() => setEditCustomer(c)} title="Edit"><span className="material-symbols-outlined">edit</span></button><button className="act danger-act" onClick={() => deleteCustomer(c)} title="Delete"><span className="material-symbols-outlined">delete</span></button></td>}
          </tr>)}</tbody></table>
          {!filtered.customers.length && <Empty text="No customers found." />}
        </Panel>
      </div>}

      {/* INWARD */}
      {tab === "inward" && <div className="transaction">
        <Panel title="Record inward"><p className="muted">Scan barcodes to quickly add materials to stock. Each scan records 1 unit.</p>
          <div className="barcode-scanner-section">
            <label>
              Movement type<span className="required"> *</span>
              <select value={scanMovementType} onChange={e => setScanMovementType(e.target.value as "INWARD" | "OUTWARD")}>
                <option value="INWARD">INWARD</option>
                <option value="RETURN_IN">RETURN_IN</option>
                <option value="ADJUSTMENT_IN">ADJUSTMENT_IN</option>
              </select>
            </label>
            <label>
              📱 Barcode Scanner<span className="required"> *</span>
              <input
                ref={scanRef}
                type="text"
                className="scanner-input"
                placeholder="Scan barcode or enter code..."
                value={scanInput}
                onChange={e => setScanInput(e.target.value)}
                onKeyDown={e => {
                  if (e.key === "Enter") {
                    e.preventDefault();
                    handleBarcodeScanned(scanInput);
                  }
                }}
                autoComplete="off"
                autoFocus
              />
            </label>
          </div>

          {displayedScans.length > 0 && (
            <div className="scanned-items-section">
              <h3>✓ Recent Scans</h3>
              <table className="scanned-items-table">
                <thead>
                  <tr>
                    <th>Time</th>
                    <th>Item Name</th>
                    <th>Barcode</th>
                    <th>Qty</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {displayedScans.map(scan => (
                    <tr key={`${scan.id}-${scan.timestamp.getTime()}`} className={!scan.saved ? "scan-failed" : ""}>
                      <td className="time-col">{scan.timestamp.toLocaleTimeString()}</td>
                      <td>{scan.name}</td>
                      <td className="barcode-col">{scan.barcode}</td>
                      <td className="qty-col">1</td>
                      <td className="status-col">{scan.saved ? <span className="scan-status success">✓</span> : <span className="scan-status failed">✗</span>}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              <div className="scan-summary">Total: {displayedScans.length} scans | {displayedScans.filter(s => s.saved).length} saved | {displayedScans.filter(s => !s.saved).length} failed</div>
            </div>
          )}

          <div className="scan-actions">
            <button className="link" onClick={() => setRecentScans(prev => prev.filter(s => !(s.movementType === "INWARD" || s.movementType === "RETURN_IN" || s.movementType === "ADJUSTMENT_IN")))}>Clear List</button>
            <button className="link" onClick={() => setTab("dashboard")}>End Session</button>
          </div>
        </Panel>
        <aside className="hint"><b>Quick Scanning</b><p>Focus the scanner input and scan barcodes. Each scan automatically records 1 unit and appears in the list below.</p></aside>
      </div>}

      {/* OUTWARD */}
      {tab === "outward" && <div className="transaction">
        <Panel title="Record outward"><p className="muted">Scan barcodes to quickly issue materials. Each scan records 1 unit.</p>
          <div className="barcode-scanner-section">
            <label>
              Movement type<span className="required"> *</span>
              <select value={scanMovementType} onChange={e => setScanMovementType(e.target.value as "INWARD" | "OUTWARD")}>
                <option value="OUTWARD">OUTWARD</option>
                <option value="RETURN_OUT">RETURN_OUT</option>
                <option value="ADJUSTMENT_OUT">ADJUSTMENT_OUT</option>
              </select>
            </label>
            <label>
              📱 Barcode Scanner<span className="required"> *</span>
              <input
                ref={scanRef}
                type="text"
                className="scanner-input"
                placeholder="Scan barcode or enter code..."
                value={scanInput}
                onChange={e => setScanInput(e.target.value)}
                onKeyDown={e => {
                  if (e.key === "Enter") {
                    e.preventDefault();
                    handleBarcodeScanned(scanInput);
                  }
                }}
                autoComplete="off"
                autoFocus
              />
            </label>
          </div>

          {displayedScans.length > 0 && (
            <div className="scanned-items-section">
              <h3>✓ Recent Scans</h3>
              <table className="scanned-items-table">
                <thead>
                  <tr>
                    <th>Time</th>
                    <th>Item Name</th>
                    <th>Barcode</th>
                    <th>Qty</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {displayedScans.map(scan => (
                    <tr key={`${scan.id}-${scan.timestamp.getTime()}`} className={!scan.saved ? "scan-failed" : ""}>
                      <td className="time-col">{scan.timestamp.toLocaleTimeString()}</td>
                      <td>{scan.name}</td>
                      <td className="barcode-col">{scan.barcode}</td>
                      <td className="qty-col">1</td>
                      <td className="status-col">{scan.saved ? <span className="scan-status success">✓</span> : <span className="scan-status failed">✗</span>}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              <div className="scan-summary">Total: {displayedScans.length} scans | {displayedScans.filter(s => s.saved).length} saved | {displayedScans.filter(s => !s.saved).length} failed</div>
            </div>
          )}

          <div className="scan-actions">
            <button className="link" onClick={() => setRecentScans(prev => prev.filter(s => !(s.movementType === "OUTWARD" || s.movementType === "RETURN_OUT" || s.movementType === "ADJUSTMENT_OUT")))}>Clear List</button>
            <button className="link" onClick={() => setTab("dashboard")}>End Session</button>
          </div>
        </Panel>
        <aside className="hint"><b>Quick Scanning</b><p>Focus the scanner input and scan barcodes. Each scan automatically records 1 unit and appears in the list below.</p></aside>
      </div>}

      {/* STOCK LEDGER */}
      {tab === "stock" && <Panel title="Stock ledger">
        <div className="panel-toolbar"><button className="link" onClick={exportCSV}>Export CSV</button></div>
        <table><thead><tr><th>Date & time</th><th>Type</th><th>Item</th><th>Qty</th><th>Customer</th><th>Reference</th><th>Recorded by</th></tr></thead>
        <tbody>{filtered.movements.map(m => <tr key={m.id}>
          <td>{new Date(m.occurred_at).toLocaleString("en-IN", { dateStyle: "medium", timeStyle: "short" })}</td>
          <td><span className={`badge ${m.movement_type.includes("IN") || m.movement_type === "INWARD" ? "in" : "out"}`}>{m.movement_type.replace("_", " ")}</span></td>
          <td><b>{m.item_name}</b></td>
          <td>{m.movement_type.includes("IN") || m.movement_type === "INWARD" ? "+" : "−"}{m.quantity} {m.item_unit}</td>
          <td>{m.customer_name || "—"}</td>
          <td>{m.reference_number || "—"}</td>
          <td>{m.recorded_by_name || "—"}</td>
        </tr>)}</tbody></table>
        {!filtered.movements.length && <Empty text="No stock movements recorded yet." />}
      </Panel>}

      {/* REPORTS */}
      {tab === "reports" && <div>
        <div className="report-tabs">
          {(["inward", "outward", "stock"] as const).map(rt => <button key={rt} className={reportTab === rt ? "report-tab active" : "report-tab"} onClick={() => setReportTab(rt)}>{rt === "inward" ? "Inward Report" : rt === "outward" ? "Outward Report" : "Stock Report"}</button>)}
        </div>
        <div className="report-filters">
          {reportTab !== "stock" && <>
            <label className="filter-field">From<input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} /></label>
            <label className="filter-field">To<input type="date" value={dateTo} onChange={e => setDateTo(e.target.value)} /></label>
            <label className="filter-field">User<select value={filterUser} onChange={e => setFilterUser(e.target.value)}><option value="">All users</option>{recorderNames.map(n => <option key={n} value={n}>{n}</option>)}</select></label>
          </>}
          <label className="filter-field">Category<select value={filterCategory} onChange={e => setFilterCategory(e.target.value)}><option value="">All categories</option>{categories.map(c => <option key={c} value={c}>{c}</option>)}</select></label>
          {reportTab !== "stock" && <button className="primary filter-apply" onClick={applyReportFilters}>Apply</button>}
          {(dateFrom || dateTo || filterUser || filterCategory) && <button className="link" onClick={clearReportFilters}>Clear</button>}
          <div className="filter-actions"><button className="link" onClick={printReport}>Print</button><button className="link" onClick={exportReportCSV}>Export CSV</button></div>
        </div>

        {reportTab !== "stock" ? <Panel title={`${reportTab === "inward" ? "Inward" : "Outward"} Report${reportData.length ? ` (${reportData.length})` : ""}`}>
          {reportLoading ? <div className="empty">Loading report...</div> : <>
            <table><thead><tr><th>Date</th><th>Type</th><th>Item</th><th>Category</th><th>Qty</th><th>Customer</th><th>Reference</th><th>Recorded by</th></tr></thead>
            <tbody>{reportData.map(m => <tr key={m.id}>
              <td>{new Date(m.occurred_at).toLocaleString("en-IN", { dateStyle: "medium", timeStyle: "short" })}</td>
              <td><span className={`badge ${m.movement_type.includes("IN") || m.movement_type === "INWARD" ? "in" : "out"}`}>{m.movement_type.replace("_", " ")}</span></td>
              <td><b>{m.item_name}</b></td>
              <td>{m.item_category || "—"}</td>
              <td>{m.quantity} {m.item_unit}</td>
              <td>{m.customer_name || "—"}</td>
              <td>{m.reference_number || "—"}</td>
              <td>{m.recorded_by_name || "—"}</td>
            </tr>)}</tbody>
            {reportData.length > 0 && <tfoot><tr><td colSpan={4}><b>Total: {reportData.length} records</b></td><td><b>{Math.round(reportData.reduce((s, m) => s + m.quantity, 0) * 100) / 100}</b></td><td colSpan={3}></td></tr></tfoot>}
            </table>
            {!reportData.length && <Empty text="No records found for the selected filters." />}
          </>}
        </Panel>

        : <Panel title={`Stock Report (${stockReport.length} items)`}>
          <table><thead><tr><th>Item</th><th>Barcode</th><th>Category</th><th>Unit</th><th>Available</th><th>Reorder Level</th><th>Status</th></tr></thead>
          <tbody>{stockReport.map(i => <tr key={i.item_id}>
            <td><b>{i.name}</b>{i.sku && <small>{i.sku}</small>}</td>
            <td><code>{i.barcode}</code></td>
            <td>{i.category}</td>
            <td>{i.unit}</td>
            <td className={i.available_quantity <= i.reorder_level ? "danger" : ""}>{i.available_quantity}</td>
            <td>{i.reorder_level}</td>
            <td>{i.available_quantity <= i.reorder_level ? <span className="badge out">LOW</span> : <span className="badge in">OK</span>}</td>
          </tr>)}</tbody>
          {stockReport.length > 0 && <tfoot><tr><td colSpan={4}><b>Total: {stockReport.length} items</b></td><td><b>{Math.round(stockReport.reduce((s, i) => s + i.available_quantity, 0))}</b></td><td colSpan={2}></td></tr></tfoot>}
          </table>
          {!stockReport.length && <Empty text="No items found for the selected category." />}
        </Panel>}
      </div>}

      {/* SETTINGS (admin) */}
      {tab === "settings" && isAdmin && <div>
        <div className="report-tabs">
          <button className={settingsTab === "profile" ? "report-tab active" : "report-tab"} onClick={() => setSettingsTab("profile")}>Profile & Branding</button>
          <button className={settingsTab === "users" ? "report-tab active" : "report-tab"} onClick={() => setSettingsTab("users")}>User Management</button>
        </div>

        {settingsTab === "profile" && <div className="grid form-grid">
          <Panel title="Company branding">
            <form key={JSON.stringify(brand)} onSubmit={saveBranding}>
              <label>Logo icon<span className="required"> *</span><div className="split" style={{gridTemplateColumns:"80px 1fr"}}><input name="logo_icon" defaultValue={brand.logo_icon} placeholder="▦" style={{textAlign:"center",fontSize:20}} /><span style={{color:"var(--outline)",fontSize:12,alignSelf:"center"}}>Emoji or character for sidebar</span></div></label>
              <Field label="Company name *" name="company_name" required defaultValue={brand.company_name} />
              <Field label="Tagline" name="tagline" defaultValue={brand.tagline} placeholder="e.g. MATERIAL MANAGEMENT" />
              <Field label="Address" name="address" defaultValue={brand.address} placeholder="Company address" />
              <div className="split" style={{gridTemplateColumns:"1fr 1fr"}}>
                <Field label="Phone" name="phone" defaultValue={brand.phone} placeholder="+91 ..." />
                <Field label="Email" name="email" type="email" defaultValue={brand.email} />
              </div>
              <button className="primary">Save branding</button>
            </form>
          </Panel>
          <div>
            <div className="brand-preview-card">
              <p className="eyebrow">SIDEBAR PREVIEW</p>
              <div className="brand-preview-inner">
                <span className="brand-preview-icon">{brand.logo_icon || "▦"}</span>
                <div><strong>{brand.company_name || "MaterialFlow"}</strong><small>{brand.tagline || "BARCODE INVENTORY"}</small></div>
              </div>
            </div>
            <aside className="hint" style={{marginTop:16}}>
              <b>Branding settings</b>
              <p>Your company name and logo appear in the sidebar and on printed reports and exports.</p>
            </aside>
          </div>
        </div>}

        {settingsTab === "users" && <div className="grid form-grid">
          <Panel title="Add user"><form onSubmit={addUser}>
            <Field label="Full name *" name="full_name" required />
            <Field label="Email *" name="email" type="email" required />
            <Field label="Password *" name="password" type="password" required />
            <Select label="Role *" name="role" options={["STOREKEEPER", "MANAGER", "ADMIN", "VIEWER"]} />
            <button className="primary">Create user</button>
          </form></Panel>
          <Panel title={`Users (${appUsers.length})`}>
            <table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>{appUsers.map(u => <tr key={u.id} className={u.is_active ? "" : "row-inactive"}>
              <td><b>{u.full_name}</b></td><td>{u.email}</td><td><span className="badge role">{u.role}</span></td>
              <td>{u.is_active ? <span className="status-on">Active</span> : <span className="status-off">Inactive</span>}</td>
              <td className="actions">
                <button className="act" onClick={() => setEditUser(u)} title="Edit"><span className="material-symbols-outlined">edit</span></button>
                <button className="act" onClick={() => toggleUser(u)} title={u.is_active ? "Deactivate" : "Activate"}><span className="material-symbols-outlined">{u.is_active ? "person_off" : "person"}</span></button>
              </td>
            </tr>)}</tbody></table>
          </Panel>
        </div>}
      </div>}

      {/* BATCH HISTORY */}
      {tab === "batch-history" && <div style={{ padding: "20px" }}>
        <div style={{ marginBottom: "20px", display: "flex", gap: "10px", flexWrap: "wrap" }}>
          <h1 style={{ flex: "1", minWidth: "200px", margin: "0" }}>Batch History</h1>
          <div style={{ display: "flex", gap: "8px" }}>
            <select value={batchHistoryFilter} onChange={(e) => setBatchHistoryFilter(e.target.value)} style={{ padding: "8px", borderRadius: "4px", border: "1px solid var(--outline)" }}>
              <option value="all">All Batches</option>
              <option value="created">Just Created</option>
              <option value="partially">Partially Printed</option>
              <option value="fully">Fully Printed</option>
            </select>
          </div>
        </div>

        {batchHistoryLoading ? (
          <div style={{ textAlign: "center", padding: "40px", color: "var(--on-surface-variant)" }}>Loading batch history...</div>
        ) : batchHistory.length === 0 ? (
          <div style={{ textAlign: "center", padding: "40px", color: "var(--on-surface-variant)" }}>No batches found</div>
        ) : (
          <div style={{ overflowX: "auto" }}>
            <table style={{ width: "100%", borderCollapse: "collapse" }}>
              <thead>
                <tr style={{ backgroundColor: "var(--surface-variant)", borderBottom: "2px solid var(--outline)" }}>
                  <th style={{ padding: "12px", textAlign: "left" }}>Batch Reference</th>
                  <th style={{ padding: "12px", textAlign: "left" }}>Item</th>
                  <th style={{ padding: "12px", textAlign: "center" }}>Total / Generated</th>
                  <th style={{ padding: "12px", textAlign: "center" }}>Printed</th>
                  <th style={{ padding: "12px", textAlign: "center" }}>Scanned</th>
                  <th style={{ padding: "12px", textAlign: "center" }}>Status</th>
                  <th style={{ padding: "12px", textAlign: "left" }}>Last Printed</th>
                  <th style={{ padding: "12px", textAlign: "center" }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {batchHistory.filter(b => {
                  if (batchHistoryFilter === "created") return b.status_detail === "CREATED";
                  if (batchHistoryFilter === "partially") return b.status_detail === "PARTIALLY_PRINTED";
                  if (batchHistoryFilter === "fully") return b.status_detail === "FULLY_PRINTED";
                  return true;
                }).map(batch => (
                  <tr key={batch.id} style={{ borderBottom: "1px solid var(--outline)" }}>
                    <td style={{ padding: "12px", fontWeight: "600" }}>{batch.batch_reference}</td>
                    <td style={{ padding: "12px" }}>{batch.item_name}</td>
                    <td style={{ padding: "12px", textAlign: "center" }}>{batch.quantity_generated} / {batch.quantity_total}</td>
                    <td style={{ padding: "12px", textAlign: "center" }}>
                      <span style={{ backgroundColor: "var(--secondary-container)", color: "var(--on-secondary-container)", padding: "4px 8px", borderRadius: "12px", fontSize: "12px" }}>
                        {batch.total_printed || 0}
                      </span>
                    </td>
                    <td style={{ padding: "12px", textAlign: "center" }}>
                      <span style={{ color: "var(--on-surface-variant)", fontSize: "12px" }}>
                        {batch.scanned_count} / {batch.total_barcodes}
                      </span>
                    </td>
                    <td style={{ padding: "12px", textAlign: "center" }}>
                      <span style={{
                        padding: "4px 8px",
                        borderRadius: "12px",
                        fontSize: "12px",
                        backgroundColor: batch.status_detail === "FULLY_PRINTED" ? "var(--primary)" : batch.status_detail === "PARTIALLY_PRINTED" ? "var(--tertiary-container)" : "var(--surface-variant)",
                        color: batch.status_detail === "FULLY_PRINTED" ? "white" : "var(--on-surface-variant)"
                      }}>
                        {batch.status_detail || "CREATED"}
                      </span>
                    </td>
                    <td style={{ padding: "12px", fontSize: "12px", color: "var(--on-surface-variant)" }}>
                      {batch.last_printed_at ? new Date(batch.last_printed_at).toLocaleDateString() + " " + new Date(batch.last_printed_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) : "—"}
                    </td>
                    <td style={{ padding: "12px", textAlign: "center", display: "flex", gap: "8px", justifyContent: "center" }}>
                      <button onClick={() => exportBatchAsCSV(batch)} style={{ background: "none", border: "none", cursor: "pointer", color: "var(--primary)", padding: "4px 8px" }} title="Export as CSV">
                        <span className="material-symbols-outlined" style={{ fontSize: "20px" }}>download</span>
                      </button>
                      <button onClick={() => reprintBatch(batch)} style={{ background: "none", border: "none", cursor: "pointer", color: "var(--tertiary)", padding: "4px 8px" }} title="Reprint batch">
                        <span className="material-symbols-outlined" style={{ fontSize: "20px" }}>print</span>
                      </button>
                      <button onClick={() => setSelectedBatchDetail(batch)} style={{ background: "none", border: "none", cursor: "pointer", color: "var(--secondary)", padding: "4px 8px" }} title="View details">
                        <span className="material-symbols-outlined" style={{ fontSize: "20px" }}>info</span>
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>}
    </main>

    {/* Bottom navigation (mobile) */}
    <nav className="bottom-nav">
      <button className={tab === "dashboard" ? "bnav-item active" : "bnav-item"} onClick={() => { setTab("dashboard"); setDrawerOpen(false); }}>
        <span className="material-symbols-outlined">home</span>
        <span>Home</span>
      </button>
      <button className="bnav-item" onClick={() => { scanRef.current?.focus(); setDrawerOpen(false); }}>
        <span className="material-symbols-outlined">qr_code_scanner</span>
        <span>Scan</span>
      </button>
      <button className={tab === "items" ? "bnav-item active" : "bnav-item"} onClick={() => { setTab("items"); setDrawerOpen(false); }}>
        <span className="material-symbols-outlined">inventory</span>
        <span>Inventory</span>
      </button>
      <button className="bnav-item" onClick={() => setDrawerOpen(true)}>
        <span className="material-symbols-outlined">more_horiz</span>
        <span>More</span>
      </button>
    </nav>

    {/* MODALS */}
    <Modal open={!!editItem} onClose={() => setEditItem(null)} title="Edit item">
      {editItem && <form onSubmit={updateItem}>
        <Field label="Item name" name="name" required defaultValue={editItem.name} />
        <Field label="Barcode" name="barcode" required defaultValue={editItem.barcode} />
        <Field label="SKU (optional)" name="sku" defaultValue={editItem.sku || ""} />
        <Field label="Category" name="category" defaultValue={editItem.category} />
        <div className="split"><Select label="Unit" name="unit" options={["Nos", "Kg", "Meters", "Liters", "Boxes", "Pairs"]} defaultValue={editItem.unit} /><Field label="Reorder level" name="reorder_level" type="number" defaultValue={editItem.reorder_level} /></div>
        <button className="primary">Update item</button>
      </form>}
    </Modal>

    <Modal open={!!editCustomer} onClose={() => setEditCustomer(null)} title="Edit customer">
      {editCustomer && <form onSubmit={updateCustomer}>
        <Field label="Customer name" name="name" required defaultValue={editCustomer.name} />
        <Field label="Phone" name="phone" defaultValue={editCustomer.phone || ""} />
        <Field label="Email" name="email" type="email" defaultValue={editCustomer.email || ""} />
        <Field label="Address" name="address" defaultValue={editCustomer.address || ""} />
        <button className="primary">Update customer</button>
      </form>}
    </Modal>

    <Modal open={!!editUser} onClose={() => setEditUser(null)} title="Edit user">
      {editUser && <form onSubmit={updateUserSubmit}>
        <Field label="Full name" name="full_name" required defaultValue={editUser.full_name} />
        <Field label="Email" name="email" type="email" required defaultValue={editUser.email} />
        <Select label="Role" name="role" options={["STOREKEEPER", "MANAGER", "ADMIN", "VIEWER"]} defaultValue={editUser.role} />
        <Field label="New password (leave blank to keep)" name="password" type="password" />
        <button className="primary">Update user</button>
      </form>}
    </Modal>


    <Modal open={showBatchDialog} onClose={() => { setShowBatchDialog(false); setBatchItem(null); setBatchReference(""); setBatchQuantity(1); setBatchPrefix(""); setBatchResult(null); }} title={batchResult ? "Batch generated" : "Generate batch barcodes"}>
      {batchResult ? (
        <div>
          <div style={{ marginBottom: "20px" }}>
            <p><strong>Batch reference:</strong> {batchResult.batch_reference}</p>
            <p><strong>Item:</strong> {batchResult.item_name}</p>
            <p><strong>Total generated:</strong> {batchResult.total_generated} barcodes</p>
          </div>
          <div style={{ marginBottom: "16px" }}>
            <p style={{ fontSize: "12px", color: "var(--on-surface-variant)" }}>Preview: showing first 10 of {batchResult.barcodes.length} barcodes</p>
          </div>
          <div style={{ maxHeight: "300px", overflowY: "auto", border: "1px solid var(--outline)", borderRadius: "8px", padding: "12px", marginBottom: "20px" }}>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(120px, 1fr))", gap: "8px" }}>
              {batchResult.barcodes.slice(0, 10).map(b => <div key={b.unit_number} style={{ padding: "8px", border: "1px solid var(--outline-variant)", borderRadius: "4px", fontSize: "12px", textAlign: "center" }}><div>{b.barcode}</div><small>Unit {b.unit_number}</small></div>)}
            </div>
          </div>
          {batchResult.barcodes.length > 10 && <div style={{ marginBottom: "20px", padding: "12px", backgroundColor: "var(--surface-variant)", borderRadius: "8px", fontSize: "12px", textAlign: "center" }}>
            <p>✓ All {batchResult.total_generated} barcodes ready to print</p>
          </div>}
          <div style={{ display: "flex", gap: "8px" }}>
            <button className="primary" onClick={() => printBatchLabels(batchResult)}><span className="material-symbols-outlined" style={{ marginRight: "8px" }}>print</span>Print labels</button>
            <button onClick={() => { setShowBatchDialog(false); setBatchItem(null); setBatchReference(""); setBatchQuantity(100); setBatchPrefix(""); setBatchResult(null); }}>Close</button>
          </div>
        </div>
      ) : (
        batchItem && <form onSubmit={(e) => { e.preventDefault(); generateBatch(); }}>
          <div className="form-group"><label>Item name<span className="required">*</span></label><input type="text" value={batchItem.name} disabled readOnly /></div>
          <div className="form-group"><label>Batch reference<span className="required">*</span></label><input type="text" required placeholder="e.g., BATCH-2026-JAN-001" value={batchReference} onChange={(e) => setBatchReference(e.target.value)} /></div>
          <div className="form-group"><label>Quantity<span className="required">*</span></label><input type="number" required min="1" max="10000" placeholder="e.g., 100" value={batchQuantity} onChange={(e) => setBatchQuantity(parseInt(e.target.value) || 1)} /></div>
          <div className="form-group"><label>Barcode prefix (optional)</label><input type="text" placeholder={`Default: MF-${batchItem.name.replace(/\s+/g, "-").toUpperCase()}-`} value={batchPrefix} onChange={(e) => setBatchPrefix(e.target.value)} /></div>
          <button className="primary" disabled={batchGenerating}>{batchGenerating ? "Generating..." : "Generate batch"}</button>
        </form>
      )}
    </Modal>

    {/* BATCH DETAILS MODAL */}
    <Modal open={!!selectedBatchDetail} onClose={() => { setSelectedBatchDetail(null); setBatchDetailData(null); }} title="Batch Details">
      {selectedBatchDetail && batchDetailLoading ? (
        <div style={{ textAlign: "center", padding: "40px", color: "var(--on-surface-variant)" }}>Loading batch details...</div>
      ) : selectedBatchDetail && batchDetailData ? (
        <div>
          {/* Summary Section */}
          <div style={{ marginBottom: "24px", padding: "16px", backgroundColor: "var(--surface-variant)", borderRadius: "8px" }}>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "16px" }}>
              <div>
                <p style={{ fontSize: "12px", color: "var(--on-surface-variant)", margin: "0 0 4px 0" }}>Batch Reference</p>
                <p style={{ fontSize: "16px", fontWeight: "600", margin: "0" }}>{selectedBatchDetail.batch_reference}</p>
              </div>
              <div>
                <p style={{ fontSize: "12px", color: "var(--on-surface-variant)", margin: "0 0 4px 0" }}>Item</p>
                <p style={{ fontSize: "16px", fontWeight: "600", margin: "0" }}>{selectedBatchDetail.item_name}</p>
              </div>
              <div>
                <p style={{ fontSize: "12px", color: "var(--on-surface-variant)", margin: "0 0 4px 0" }}>Generated</p>
                <p style={{ fontSize: "16px", fontWeight: "600", margin: "0" }}>{selectedBatchDetail.quantity_generated} / {selectedBatchDetail.quantity_total}</p>
              </div>
              <div>
                <p style={{ fontSize: "12px", color: "var(--on-surface-variant)", margin: "0 0 4px 0" }}>Status</p>
                <p style={{ fontSize: "16px", fontWeight: "600", margin: "0", color: selectedBatchDetail.status_detail === "FULLY_PRINTED" ? "var(--primary)" : "var(--on-surface-variant)" }}>{selectedBatchDetail.status_detail}</p>
              </div>
            </div>
          </div>

          {/* Scan Statistics */}
          <div style={{ marginBottom: "24px" }}>
            <h3 style={{ fontSize: "14px", fontWeight: "600", margin: "0 0 12px 0" }}>Scan Status</h3>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: "12px" }}>
              <div style={{ padding: "12px", border: "1px solid var(--outline)", borderRadius: "8px", textAlign: "center" }}>
                <p style={{ fontSize: "12px", color: "var(--on-surface-variant)", margin: "0 0 4px 0" }}>Total</p>
                <p style={{ fontSize: "18px", fontWeight: "600", margin: "0" }}>{batchDetailData.scan_stats.total}</p>
              </div>
              <div style={{ padding: "12px", border: "1px solid var(--outline)", borderRadius: "8px", textAlign: "center", borderColor: "var(--primary)" }}>
                <p style={{ fontSize: "12px", color: "var(--primary)", margin: "0 0 4px 0" }}>Scanned</p>
                <p style={{ fontSize: "18px", fontWeight: "600", margin: "0", color: "var(--primary)" }}>{batchDetailData.scan_stats.scanned}</p>
              </div>
              <div style={{ padding: "12px", border: "1px solid var(--outline)", borderRadius: "8px", textAlign: "center", borderColor: "var(--tertiary-container)" }}>
                <p style={{ fontSize: "12px", color: "var(--on-surface-variant)", margin: "0 0 4px 0" }}>Unscanned</p>
                <p style={{ fontSize: "18px", fontWeight: "600", margin: "0", color: "var(--tertiary)" }}>{batchDetailData.scan_stats.unscanned}</p>
              </div>
            </div>
          </div>

          {/* Print Range Section */}
          <div style={{ marginBottom: "24px", padding: "16px", backgroundColor: "var(--surface-variant)", borderRadius: "8px" }}>
            <h3 style={{ fontSize: "14px", fontWeight: "600", margin: "0 0 12px 0" }}>Print Range</h3>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "12px", marginBottom: "12px" }}>
              <div>
                <label style={{ display: "block", fontSize: "12px", marginBottom: "4px" }}>From Unit</label>
                <input type="number" min="1" max={selectedBatchDetail.quantity_generated} value={printRangeStart} onChange={(e) => setPrintRangeStart(Math.max(1, parseInt(e.target.value) || 1))} style={{ width: "100%", padding: "8px", borderRadius: "4px", border: "1px solid var(--outline)" }} />
              </div>
              <div>
                <label style={{ display: "block", fontSize: "12px", marginBottom: "4px" }}>To Unit</label>
                <input type="number" min={printRangeStart} max={selectedBatchDetail.quantity_generated} value={printRangeEnd} onChange={(e) => setPrintRangeEnd(Math.min(selectedBatchDetail.quantity_generated, parseInt(e.target.value) || selectedBatchDetail.quantity_generated))} style={{ width: "100%", padding: "8px", borderRadius: "4px", border: "1px solid var(--outline)" }} />
              </div>
            </div>
            <p style={{ fontSize: "12px", color: "var(--on-surface-variant)", margin: "0 0 12px 0" }}>Total: {Math.max(0, printRangeEnd - printRangeStart + 1)} barcodes</p>
            <button onClick={printBatchRange} style={{ width: "100%", padding: "10px", backgroundColor: "var(--primary)", color: "white", border: "none", borderRadius: "4px", fontWeight: "600", cursor: "pointer" }}>Print Selected Range</button>
          </div>

          {/* Print History */}
          {batchDetailData.print_history.length > 0 && (
            <div style={{ marginBottom: "24px" }}>
              <h3 style={{ fontSize: "14px", fontWeight: "600", margin: "0 0 12px 0" }}>Print History</h3>
              <div style={{ maxHeight: "150px", overflowY: "auto" }}>
                {batchDetailData.print_history.map((print: any, idx: number) => (
                  <div key={idx} style={{ padding: "8px", borderBottom: "1px solid var(--outline)", fontSize: "12px" }}>
                    <div style={{ display: "flex", justifyContent: "space-between" }}>
                      <span style={{ fontWeight: "500" }}>{print.printed_by_name || "—"}</span>
                      <span style={{ color: "var(--on-surface-variant)" }}>{print.printed_quantity} barcodes</span>
                    </div>
                    <small style={{ color: "var(--on-surface-variant)" }}>{new Date(print.created_at).toLocaleString()}</small>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Export History */}
          {batchDetailData.export_history.length > 0 && (
            <div>
              <h3 style={{ fontSize: "14px", fontWeight: "600", margin: "0 0 12px 0" }}>Export History</h3>
              <div style={{ maxHeight: "100px", overflowY: "auto" }}>
                {batchDetailData.export_history.map((exp: any, idx: number) => (
                  <div key={idx} style={{ padding: "8px", borderBottom: "1px solid var(--outline)", fontSize: "12px" }}>
                    <div style={{ display: "flex", justifyContent: "space-between" }}>
                      <span style={{ fontWeight: "500" }}>{exp.export_format}</span>
                      <span style={{ color: "var(--on-surface-variant)" }}>{exp.exported_by_name || "—"}</span>
                    </div>
                    <small style={{ color: "var(--on-surface-variant)" }}>{new Date(exp.created_at).toLocaleString()}</small>
                  </div>
                ))}
              </div>
            </div>
          )}

          <div style={{ marginTop: "20px", display: "flex", gap: "8px" }}>
            <button onClick={() => { setSelectedBatchDetail(null); setBatchDetailData(null); }} style={{ flex: 1, padding: "10px", backgroundColor: "var(--surface-variant)", border: "none", borderRadius: "4px", fontWeight: "600", cursor: "pointer" }}>Close</button>
          </div>
        </div>
      ) : null}
    </Modal>
  </div>;
}

function Card({ label, value, icon, warning }: { label: string; value: number; icon: string; warning?: boolean }) {
  return <div className={warning ? "stat warning" : "stat"}>
    <div className="stat-icon"><span className="material-symbols-outlined">{icon}</span></div>
    <div><p>{label}</p><strong>{value}</strong></div>
  </div>;
}
function Panel({ title, children, action, actionLabel }: { title: string; children: React.ReactNode; action?: () => void; actionLabel?: string }) {
  return <article className="panel"><div className="panel-head"><h2>{title}</h2>{action && <button className="link" onClick={action}>{actionLabel}</button>}</div>{children}</article>;
}
function Field({ label, name, type = "text", required, placeholder, defaultValue }: { label: string; name: string; type?: string; required?: boolean; placeholder?: string; defaultValue?: string | number }) {
  const labelText = label.endsWith(' *') ? label.slice(0, -2) : label;
  const hasRequired = label.endsWith(' *');
  return <label>{labelText}{hasRequired && <span className="required"> *</span>}<input name={name} type={type} required={required} placeholder={placeholder} defaultValue={defaultValue} /></label>;
}
function Select({ label, name, options, defaultValue }: { label: string; name: string; options: string[]; defaultValue?: string }) {
  const labelText = label.endsWith(' *') ? label.slice(0, -2) : label;
  const hasRequired = label.endsWith(' *');
  return <label>{labelText}{hasRequired && <span className="required"> *</span>}<select name={name} defaultValue={defaultValue}>{options.map(x => <option key={x}>{x}</option>)}</select></label>;
}
function Empty({ text }: { text: string }) { return <div className="empty">{text}</div>; }
function Activity({ moves }: { moves: Movement[] }) {
  return <div className="activity">{moves.map(m => {
    const isIn = m.movement_type.includes("IN") || m.movement_type === "INWARD";
    return <div key={m.id}>
      <span className={`round ${isIn ? "in" : "out"}`}>
        <span className="material-symbols-outlined">{isIn ? "south_west" : "north_east"}</span>
      </span>
      <p><b>{m.movement_type.replace("_", " ")}</b> · {m.item_name}
        <small>{new Date(m.occurred_at).toLocaleString("en-IN", { dateStyle: "medium", timeStyle: "short" })} · {m.quantity} {m.item_unit}</small>
      </p>
    </div>;
  })}</div>;
}
function ItemSelect({ items }: { items: Item[] }) {
  return <label>Item<span className="required"> *</span><select name="item_id" required><option value="">Select item</option>{items.map(i => <option value={i.item_id} key={i.item_id}>{i.name} — {i.barcode} ({i.available_quantity} {i.unit})</option>)}</select></label>;
}
function Modal({ open, onClose, title, children }: { open: boolean; onClose: () => void; title: string; children: React.ReactNode }) {
  if (!open) return null;
  return <div className="overlay" onClick={onClose}><div className="modal" onClick={e => e.stopPropagation()}><div className="modal-head"><h2>{title}</h2><button className="close-btn" onClick={onClose}><span className="material-symbols-outlined">close</span></button></div>{children}</div></div>;
}
function BarcodePreview({ value }: { value: string }) {
  const svgRef = useRef<SVGSVGElement>(null);
  useEffect(() => {
    if (svgRef.current) JsBarcode(svgRef.current, value, { format: "CODE128", width: 1, height: 28, displayValue: false, margin: 0 });
  }, [value]);
  return <svg ref={svgRef} className="barcode-preview"></svg>;
}
function printBatchLabels(batchResult: { batch_reference: string; item_name: string; total_generated: number; barcodes: Array<{ barcode: string; unit_number: number }> }) {
  const w = window.open("", "_blank"); if (!w) return;
  const labelsPerPage = 63;
  const totalPages = Math.ceil(batchResult.total_generated / labelsPerPage);

  w.document.write(`<html><head><title>${batchResult.batch_reference} Labels</title><style>
    @page{size:A4;margin:5mm}
    *{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,sans-serif;margin:0;background:#fff}
    .header{padding:6px 8px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:10px}
    .page{display:grid;grid-template-columns:repeat(7,1fr);gap:0;padding:0;page-break-after:always;width:100%;background:#fff}
    .label{width:100%;aspect-ratio:1/1;border:1.5px solid #000;padding:2px;text-align:center;display:flex;flex-direction:column;justify-content:space-evenly;align-items:center;page-break-inside:avoid;overflow:hidden;font-family:Arial,sans-serif;background:#fff}
    .label-name{font-size:13px;font-weight:700;line-height:1.05;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:98%}
    .label-unit{font-size:11px;color:#222;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:98%;font-weight:700}
    .label svg{width:94%;height:38px;max-width:96%;flex-shrink:0}
    .barcode-text{font-size:11px;color:#000;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:98%;letter-spacing:0.2px;font-weight:800;font-family:'Courier New',monospace}
    .page-info{font-size:6px;color:#999;text-align:right;grid-column:1/-1;padding:2px 8px;border-top:1px solid #ddd;background:#f5f5f5}
    .no-print{display:none}@media print{.no-print{display:none!important}.header{display:none!important}.page-info{display:none!important}}
  </style></head><body>
  <div class="header no-print">
    <div><b>${batchResult.total_generated} labels — ${batchResult.batch_reference}</b><br><small>${batchResult.item_name} | ${totalPages} page${totalPages>1?'s':''} | 30×30mm</small></div>
    <button onclick="window.print()" style="background:#006b5f;color:#fff;border:0;border-radius:3px;padding:5px 10px;font-weight:700;font-size:10px;cursor:pointer">Print</button>
  </div>
  <div id="pages"></div>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3/dist/JsBarcode.all.min.js"><\/script>
  <script>
    const barcodes = ${JSON.stringify(batchResult.barcodes)};
    let labelCount = 0;

    for(let page = 0; page < ${totalPages}; page++) {
      const pageDiv = document.createElement("div");
      pageDiv.className = "page";

      for(let i = 0; i < ${labelsPerPage}; i++) {
        if(labelCount < ${batchResult.total_generated}) {
          const b = barcodes[labelCount];
          const div = document.createElement("div");
          div.className = "label";
          div.innerHTML = '<div class="label-name">${batchResult.item_name}</div><div class="label-unit">Unit ' + b.unit_number + '</div><svg></svg><div class="barcode-text">' + b.barcode + '</div>';
          pageDiv.appendChild(div);
          JsBarcode(div.querySelector("svg"), b.barcode, {format:"CODE128", width:1.1, height:36, displayValue:false, margin:0, textMargin:0});
          labelCount++;
        }
      }

      const pageInfo = document.createElement("div");
      pageInfo.className = "page-info";
      pageInfo.textContent = "Page " + (page+1) + " of ${totalPages} | 30×30mm labels | 7 columns × 9 rows";
      pageDiv.appendChild(pageInfo);
      document.getElementById("pages").appendChild(pageDiv);
    }
  <\/script></body></html>`);
  w.document.close();
}

function BarcodeLabel({ item, qty, onQtyChange, labelSize, onSizeChange, onClose }: { item: Item; qty: number; onQtyChange: (n: number) => void; labelSize: string; onSizeChange: (s: "small" | "medium" | "large") => void; onClose: () => void }) {
  const svgRef = useRef<SVGSVGElement>(null);
  useEffect(() => {
    if (svgRef.current) JsBarcode(svgRef.current, item.barcode, { format: "CODE128", width: 2, height: 55, displayValue: true, fontSize: 13, margin: 8, textMargin: 2 });
  }, [item.barcode]);
  const printLabel = () => {
    const svg = svgRef.current; if (!svg) return;
    const sizes: Record<string, { w: number; h: number; bw: number; bh: number; fs: number }> = { small: { w: 145, h: 95, bw: 1.2, bh: 30, fs: 9 }, medium: { w: 200, h: 120, bw: 1.5, bh: 40, fs: 11 }, large: { w: 280, h: 160, bw: 2, bh: 55, fs: 13 } };
    const sz = sizes[labelSize] || sizes.medium;
    const w = window.open("", "_blank"); if (!w) return;
    w.document.write(`<html><head><title>${item.name} Labels</title><style>
      *{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,sans-serif}
      .grid{display:flex;flex-wrap:wrap;padding:8px}
      .label{width:${sz.w}px;height:${sz.h}px;border:1px dashed #ccc;padding:6px;text-align:center;display:flex;flex-direction:column;justify-content:center;align-items:center;page-break-inside:avoid;overflow:hidden}
      .label h4{font-size:${sz.fs}px;margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
      .label p{font-size:${Math.max(sz.fs - 3, 7)}px;color:#666;margin:0 0 3px}
      .no-print{padding:12px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center}
      @media print{.label{border:none}.no-print{display:none !important}}
    </style></head><body>
    <div class="no-print"><b>${qty} label${qty > 1 ? "s" : ""} — ${item.name}</b>
    <button onclick="window.print()" style="background:#006b5f;color:#fff;border:0;border-radius:8px;padding:10px 24px;font-weight:700;font-size:14px;cursor:pointer">Print</button></div>
    <div class="grid" id="labels"></div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3/dist/JsBarcode.all.min.js"><\/script>
    <script>
      for(let q=0;q<${qty};q++){
        const div=document.createElement("div");div.className="label";
        div.innerHTML='<h4>${item.name}</h4><p>${item.category} · ${item.unit}</p><svg></svg>';
        document.getElementById("labels").appendChild(div);
        JsBarcode(div.querySelector("svg"),"${item.barcode}",{format:"CODE128",width:${sz.bw},height:${sz.bh},displayValue:true,fontSize:${sz.fs},margin:2,textMargin:1});
      }
    <\/script></body></html>`);
    w.document.close();
  };
  return <div className="barcode-content">
    <h3>{item.name}</h3><p className="muted">{item.category} · {item.unit}</p>
    <svg ref={svgRef}></svg>
    <div className="barcode-controls">
      <label className="inline-label">Copies<input type="number" min={1} max={100} value={qty} onChange={e => onQtyChange(Math.max(1, Number(e.target.value)))} className="qty-input" /></label>
      <label className="inline-label">Size<select value={labelSize} onChange={e => onSizeChange(e.target.value as "small" | "medium" | "large")}><option value="small">Small</option><option value="medium">Medium</option><option value="large">Large</option></select></label>
    </div>
    <div className="barcode-actions"><button className="primary" onClick={printLabel}>Print {qty} label{qty > 1 ? "s" : ""}</button><button className="logout-btn" onClick={onClose}>Close</button></div>
  </div>;
}
