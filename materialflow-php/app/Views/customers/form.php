<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="grid form-grid">
  <article class="panel">
    <div class="panel-head"><h2>Edit customer</h2><a class="link" href="<?= base_url('customers') ?>">Back to customers</a></div>
    <form method="post" action="<?= base_url('customers/' . $customer['id'] . '/edit') ?>">
      <?= csrf_field() ?>
      <label>Customer / company name<span class="required"> *</span>
        <input name="name" value="<?= esc(old('name', $customer['name'])) ?>" required>
      </label>
      <label>Phone number
        <input name="phone" value="<?= esc(old('phone', $customer['phone'] ?? '')) ?>">
      </label>
      <label>Email
        <input name="email" type="email" value="<?= esc(old('email', $customer['email'] ?? '')) ?>">
      </label>
      <label>Address
        <input name="address" value="<?= esc(old('address', $customer['address'] ?? '')) ?>">
      </label>
      <button class="primary">Save changes</button>
    </form>
  </article>
</div>

<?= $this->endSection() ?>
