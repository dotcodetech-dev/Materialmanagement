<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="grid form-grid">
  <article class="panel">
    <div class="panel-head"><h2>Edit item</h2><a class="link" href="<?= base_url('items') ?>">Back to items</a></div>
    <form method="post" action="<?= base_url('items/' . $item['id'] . '/edit') ?>">
      <?= csrf_field() ?>
      <label>Item name<span class="required"> *</span>
        <input name="name" value="<?= esc(old('name', $item['name'])) ?>" required>
      </label>
      <label>Barcode<span class="required"> *</span>
        <input name="barcode" value="<?= esc(old('barcode', $item['barcode'])) ?>" required>
      </label>
      <label>SKU (optional)
        <input name="sku" value="<?= esc(old('sku', $item['sku'] ?? '')) ?>">
      </label>
      <label>Category<span class="required"> *</span>
        <input name="category" value="<?= esc(old('category', $item['category'])) ?>" required>
      </label>
      <div class="split">
        <label>Unit<span class="required"> *</span>
          <select name="unit">
            <?php foreach ($units as $u): ?>
              <option<?= old('unit', $item['unit']) === $u ? ' selected' : '' ?>><?= esc($u) ?></option>
            <?php endforeach ?>
          </select>
        </label>
        <label>Reorder level<span class="required"> *</span>
          <input name="reorder_level" type="number" step="any" min="0" value="<?= esc(old('reorder_level', $item['reorder_level'] + 0)) ?>" required>
        </label>
      </div>
      <button class="primary">Save changes</button>
    </form>
  </article>
</div>

<?= $this->endSection() ?>
