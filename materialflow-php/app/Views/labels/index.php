<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<article class="panel">
  <div class="panel-head">
    <h2>Bulk label printing</h2>
  </div>
  <p class="muted">Select items, set copies per item, choose a size, then print. Labels use CODE128 barcodes.</p>

  <div class="label-toolbar" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:12px 0">
    <button type="button" class="link" id="selectAllBtn">Select all</button>
    <label class="inline-label">Label size
      <select id="labelSize">
        <option value="small">Small (145×95)</option>
        <option value="medium" selected>Medium (200×120)</option>
        <option value="large">Large (280×160)</option>
      </select>
    </label>
    <span class="muted" id="labelCount">0 labels selected</span>
    <button type="button" class="primary" id="printLabelsBtn">Print labels</button>
  </div>

  <div class="search-bar">
    <input class="search-input" data-filter-table="labelsTable" placeholder="Search items...">
  </div>

  <table id="labelsTable">
    <thead><tr><th></th><th>Item</th><th>Barcode</th><th>Copies</th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
      <tr>
        <td><input type="checkbox" class="label-check" value="<?= esc($i['item_id'], 'attr') ?>"></td>
        <td><b><?= esc($i['name']) ?></b><small><?= esc($i['category']) ?> · <?= esc($i['unit']) ?></small></td>
        <td><code><?= esc($i['barcode']) ?></code></td>
        <td><input type="number" class="qty-input label-qty" value="1" min="1" max="100" style="width:70px"></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <?php if ($items === []): ?><div class="empty">No items yet — add items first.</div><?php endif ?>
</article>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/labels.js') ?>"></script>
<?= $this->endSection() ?>
