<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="grid form-grid">
  <article class="panel">
    <div class="panel-head"><h2>Record movement</h2><a class="link" href="<?= base_url('ledger') ?>">View ledger</a></div>
    <form method="post" action="<?= base_url('movements/new') ?>">
      <?= csrf_field() ?>
      <label>Item<span class="required"> *</span>
        <select name="item_id" required>
          <option value="">Select item</option>
          <?php foreach ($items as $i): ?>
            <option value="<?= esc($i['item_id'], 'attr') ?>"<?= old('item_id') === $i['item_id'] ? ' selected' : '' ?>>
              <?= esc($i['name']) ?> — <?= esc($i['barcode']) ?> (<?= $i['available_quantity'] + 0 ?> <?= esc($i['unit']) ?>)
            </option>
          <?php endforeach ?>
        </select>
      </label>
      <label>Movement type<span class="required"> *</span>
        <select name="movement_type">
          <?php foreach ($types as $t): ?>
            <option value="<?= esc($t, 'attr') ?>"<?= old('movement_type') === $t ? ' selected' : '' ?>><?= esc($t) ?></option>
          <?php endforeach ?>
        </select>
      </label>
      <label>Customer / supplier
        <select name="customer_id">
          <option value="">None</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= esc($c['id'], 'attr') ?>"<?= old('customer_id') === $c['id'] ? ' selected' : '' ?>><?= esc($c['name']) ?></option>
          <?php endforeach ?>
        </select>
      </label>
      <div class="split">
        <label>Quantity<span class="required"> *</span>
          <input name="quantity" type="number" step="any" min="0.001" value="<?= esc(old('quantity', '1')) ?>" required>
        </label>
        <label>Reference number
          <input name="reference_number" value="<?= esc(old('reference_number', '')) ?>" placeholder="PO / DC / Invoice no.">
        </label>
      </div>
      <label>Notes
        <input name="notes" value="<?= esc(old('notes', '')) ?>">
      </label>
      <button class="primary">Save movement</button>
    </form>
  </article>

  <aside class="hint">
    <b>Manual entry</b>
    <p>Use this form for movements that need a customer, a quantity other than 1, or a reference number. For fast unit-by-unit entry, use the Inward / Outward scan screens.</p>
  </aside>
</div>

<?= $this->endSection() ?>
