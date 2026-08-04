<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="search-bar">
  <input class="search-input" data-filter-table="customersTable" placeholder="Search customers...">
</div>

<div class="grid form-grid">
  <?php if ($canEdit): ?>
  <article class="panel">
    <div class="panel-head"><h2>Add customer</h2></div>
    <form method="post" action="<?= base_url('customers/new') ?>">
      <?= csrf_field() ?>
      <label>Customer / company name<span class="required"> *</span>
        <input name="name" value="<?= esc(old('name', '')) ?>" required>
      </label>
      <label>Phone number
        <input name="phone" value="<?= esc(old('phone', '')) ?>" placeholder="+91 ...">
      </label>
      <label>Email
        <input name="email" type="email" value="<?= esc(old('email', '')) ?>">
      </label>
      <label>Address
        <input name="address" value="<?= esc(old('address', '')) ?>">
      </label>
      <button class="primary">Save customer</button>
    </form>
  </article>
  <?php endif ?>

  <article class="panel">
    <div class="panel-head"><h2>Customers (<?= count($customers) ?>)</h2></div>
    <table id="customersTable">
      <thead><tr><th>Name</th><th>Phone</th><th>Email</th><?php if ($canEdit): ?><th>Actions</th><?php endif ?></tr></thead>
      <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><b><?= esc($c['name']) ?></b><?php if ($c['address']): ?><small><?= esc($c['address']) ?></small><?php endif ?></td>
          <td><?= esc($c['phone'] ?? '—') ?: '—' ?></td>
          <td><?= esc($c['email'] ?? '—') ?: '—' ?></td>
          <?php if ($canEdit): ?>
          <td class="actions">
            <a class="act" href="<?= base_url('customers/' . $c['id'] . '/edit') ?>" title="Edit">
              <span class="material-symbols-outlined">edit</span>
            </a>
            <form method="post" action="<?= base_url('customers/' . $c['id'] . '/delete') ?>" style="display:inline"
                  onsubmit="return confirm('Delete customer &quot;<?= esc($c['name'], 'attr') ?>&quot;?')">
              <?= csrf_field() ?>
              <button class="act danger-act" title="Delete"><span class="material-symbols-outlined">delete</span></button>
            </form>
          </td>
          <?php endif ?>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php if ($customers === []): ?><div class="empty">No customers found.</div><?php endif ?>
  </article>
</div>

<?= $this->endSection() ?>
