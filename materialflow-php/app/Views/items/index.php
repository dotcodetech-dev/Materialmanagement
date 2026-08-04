<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="search-bar">
  <input class="search-input" data-filter-table="itemsTable" value="<?= esc($q) ?>" placeholder="Search items...">
</div>

<div class="grid form-grid">
  <?php if ($canEdit): ?>
  <article class="panel">
    <div class="panel-head"><h2>Add item</h2></div>
    <form method="post" action="<?= base_url('items/new') ?>">
      <?= csrf_field() ?>
      <label>Item name<span class="required"> *</span>
        <input name="name" value="<?= esc(old('name', '')) ?>" required>
      </label>
      <label>Barcode<span class="required"> *</span>
        <div class="input-with-btn">
          <input name="barcode" id="barcodeInput" value="<?= esc(old('barcode', '')) ?>" placeholder="e.g. MF-10004" required>
          <button type="button" class="gen-btn" id="genBarcodeBtn">Generate</button>
        </div>
      </label>
      <label>SKU (optional)
        <input name="sku" value="<?= esc(old('sku', '')) ?>">
      </label>
      <label>Category<span class="required"> *</span>
        <input name="category" value="<?= esc(old('category', '')) ?>" required>
      </label>
      <div class="split">
        <label>Unit<span class="required"> *</span>
          <select name="unit">
            <?php foreach ($units as $u): ?>
              <option<?= old('unit') === $u ? ' selected' : '' ?>><?= esc($u) ?></option>
            <?php endforeach ?>
          </select>
        </label>
        <label>Reorder level<span class="required"> *</span>
          <input name="reorder_level" type="number" step="any" min="0" value="<?= esc(old('reorder_level', '0')) ?>" required>
        </label>
      </div>
      <button class="primary">Save item</button>
    </form>
  </article>
  <?php endif ?>

  <article class="panel">
    <div class="panel-head"><h2>Item catalogue (<?= count($items) ?>)</h2></div>
    <table id="itemsTable">
      <thead><tr><th>Item</th><th>Barcode</th><th>Stock</th><?php if ($canEdit): ?><th>Actions</th><?php endif ?></tr></thead>
      <tbody>
      <?php foreach ($items as $i): ?>
        <tr>
          <td><b><?= esc($i['name']) ?></b><small><?= esc($i['category']) ?></small></td>
          <td><code><?= esc($i['barcode']) ?></code><?php if ($i['sku']): ?><small><?= esc($i['sku']) ?></small><?php endif ?></td>
          <td class="<?= $i['available_quantity'] <= $i['reorder_level'] ? 'danger' : '' ?>"><?= $i['available_quantity'] + 0 ?> <?= esc($i['unit']) ?></td>
          <?php if ($canEdit): ?>
          <td class="actions">
            <button type="button" class="act js-batch-btn" title="Generate batch"
                    data-item-id="<?= esc($i['item_id'], 'attr') ?>"
                    data-item-name="<?= esc($i['name'], 'attr') ?>"
                    data-item-barcode="<?= esc($i['barcode'], 'attr') ?>">
              <span class="material-symbols-outlined">qr_code_2</span>
            </button>
            <a class="act" href="<?= base_url('items/' . $i['item_id'] . '/edit') ?>" title="Edit">
              <span class="material-symbols-outlined">edit</span>
            </a>
            <form method="post" action="<?= base_url('items/' . $i['item_id'] . '/delete') ?>" style="display:inline"
                  onsubmit="return confirm('Delete item &quot;<?= esc($i['name'], 'attr') ?>&quot;? Its movement history is kept.')">
              <?= csrf_field() ?>
              <button class="act danger-act" title="Delete"><span class="material-symbols-outlined">delete</span></button>
            </form>
          </td>
          <?php endif ?>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php if ($items === []): ?><div class="empty">No items found.</div><?php endif ?>
  </article>
</div>

<?= view('batches/_generate_modal') ?>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/items.js') ?>"></script>
<script src="<?= base_url('js/batches.js') ?>"></script>
<?= $this->endSection() ?>
