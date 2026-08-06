<?php
$brand   = mf_settings();
$navItems = [
    ['/', 'dashboard', 'Dashboard', null],
    ['/items', 'inventory_2', 'Items', null],
    ['/customers', 'group', 'Customers', null],
    ['/inward', 'move_to_inbox', 'Inward', 'editor'],
    ['/outward', 'outbox', 'Outward', 'editor'],
    ['/ledger', 'menu_book', 'Stock Ledger', null],
    ['/reports', 'assessment', 'Reports', null],
    ['/batches', 'history', 'Batch History', null],
];
$role    = session('role');
$isAdmin = $role === 'ADMIN';
$canEdit = in_array($role, ['ADMIN', 'MANAGER', 'STOREKEEPER'], true);
if ($isAdmin) {
    $navItems[] = ['/settings', 'settings', 'Settings', null];
}
$currentPath = '/' . trim(service('request')->getUri()->getPath(), '/');
$notice      = session()->getFlashdata('notice');
$error       = session()->getFlashdata('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <title><?= esc($title ?? 'MaterialFlow') ?> — <?= esc($brand['company_name']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
</head>
<body>
<div class="app-shell">
  <header class="topbar">
    <button class="topbar-btn" id="drawerToggle" type="button">
      <span class="material-symbols-outlined">menu</span>
    </button>
    <h1 class="topbar-title"><?= esc($brand['company_name']) ?></h1>
    <div class="topbar-avatar"><?= esc(mb_substr(session('full_name') ?? '?', 0, 1)) ?></div>
  </header>

  <div class="drawer-overlay" id="drawerOverlay" hidden></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo-icon"><?= esc($brand['logo_icon']) ?></div>
      <div>
        <div class="sidebar-name"><?= esc($brand['company_name']) ?></div>
        <div class="sidebar-tagline"><?= esc($brand['tagline']) ?></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($navItems as [$href, $icon, $label, $need]): ?>
        <?php if ($need === 'editor' && ! $canEdit) { continue; } ?>
        <a class="sidebar-link<?= $currentPath === $href ? ' active' : '' ?>" href="<?= base_url(ltrim($href, '/')) ?: base_url() ?>">
          <span class="material-symbols-outlined"><?= $icon ?></span>
          <span><?= esc($label) ?></span>
        </a>
      <?php endforeach ?>
    </nav>
    <div class="sidebar-footer">
      <a class="sidebar-link" href="<?= base_url('logout') ?>">
        <span class="material-symbols-outlined">logout</span>
        <span>Logout</span>
      </a>
    </div>
  </aside>

  <main class="main-content">
    <header class="content-header">
      <div class="header-left">
        <p class="eyebrow">MATERIAL MANAGEMENT</p>
        <h1><?= esc($heading ?? $title ?? 'MaterialFlow') ?></h1>
      </div>
      <div class="header-right">
        <form class="scanner" method="get" action="<?= base_url('items') ?>">
          <span class="material-symbols-outlined">qr_code_scanner</span>
          <input name="q" placeholder="Scan barcode or type SKU" autocomplete="off">
          <button type="submit">Scan</button>
        </form>
        <div class="user-bar">
          <div class="user-info"><b><?= esc(session('full_name')) ?></b><small><?= esc($role) ?></small></div>
          <a class="logout-btn" href="<?= base_url('logout') ?>" title="Logout"><span class="material-symbols-outlined">logout</span></a>
        </div>
      </div>
    </header>

    <?php if ($notice): ?><p class="notice">✓ <?= esc($notice) ?></p><?php endif ?>
    <?php if ($error): ?><p class="notice">❌ <?= esc($error) ?></p><?php endif ?>
    <p class="notice" id="jsNotice" hidden></p>

    <?= $this->renderSection('content') ?>
  </main>

  <nav class="bottom-nav">
    <a class="bnav-item<?= $currentPath === '/' ? ' active' : '' ?>" href="<?= base_url() ?>">
      <span class="material-symbols-outlined">home</span><span>Home</span>
    </a>
    <?php if ($canEdit): ?>
    <a class="bnav-item" href="<?= base_url('inward') ?>">
      <span class="material-symbols-outlined">qr_code_scanner</span><span>Scan</span>
    </a>
    <?php endif ?>
    <a class="bnav-item<?= $currentPath === '/items' ? ' active' : '' ?>" href="<?= base_url('items') ?>">
      <span class="material-symbols-outlined">inventory</span><span>Inventory</span>
    </a>
    <button class="bnav-item" id="drawerToggle2" type="button">
      <span class="material-symbols-outlined">more_horiz</span><span>More</span>
    </button>
  </nav>
</div>

<script src="<?= base_url('js/app.js') ?>"></script>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
