<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="report-tabs">
  <button type="button" class="report-tab active" id="tabProfile">Profile &amp; Branding</button>
  <button type="button" class="report-tab" id="tabUsers">User Management</button>
</div>

<!-- Profile & Branding -->
<div id="panelProfile">
  <div class="grid form-grid">
    <article class="panel">
      <div class="panel-head"><h2>Company profile</h2></div>
      <form method="post" action="<?= base_url('settings') ?>">
        <?= csrf_field() ?>
        <label>Logo icon
          <input name="logo_icon" value="<?= esc($brand['logo_icon']) ?>" maxlength="4" style="text-align:center">
        </label>
        <small class="muted">Emoji or character for sidebar</small>
        <label>Company name<span class="required"> *</span>
          <input name="company_name" value="<?= esc($brand['company_name']) ?>" required>
        </label>
        <label>Tagline
          <input name="tagline" value="<?= esc($brand['tagline']) ?>">
        </label>
        <label>Address
          <input name="address" value="<?= esc($brand['address']) ?>">
        </label>
        <div class="split">
          <label>Phone
            <input name="phone" value="<?= esc($brand['phone']) ?>">
          </label>
          <label>Email
            <input name="email" type="email" value="<?= esc($brand['email']) ?>">
          </label>
        </div>
        <button class="primary">Save branding</button>
      </form>
    </article>

    <article class="panel">
      <div class="panel-head"><h2>Preview</h2></div>
      <div class="brand-preview-card">
        <div class="brand-preview-inner">
          <span class="brand-preview-icon"><?= esc($brand['logo_icon']) ?></span>
          <div>
            <strong><?= esc($brand['company_name']) ?></strong>
            <small><?= esc($brand['tagline']) ?></small>
          </div>
        </div>
      </div>
      <p class="muted" style="margin-top:10px">Branding appears in the sidebar and on printed reports and exports.</p>
    </article>
  </div>
</div>

<!-- User Management -->
<div id="panelUsers" hidden>
  <div class="grid form-grid">
    <article class="panel">
      <div class="panel-head"><h2>Add user</h2></div>
      <form method="post" action="<?= base_url('settings/users') ?>">
        <?= csrf_field() ?>
        <label>Full name<span class="required"> *</span>
          <input name="full_name" required>
        </label>
        <label>Email<span class="required"> *</span>
          <input name="email" type="email" required>
        </label>
        <label>Password<span class="required"> *</span>
          <input name="password" type="password" required autocomplete="new-password">
        </label>
        <label>Role
          <select name="role">
            <?php foreach (['STOREKEEPER', 'MANAGER', 'ADMIN', 'STAFF', 'VIEWER'] as $r): ?>
              <option><?= esc($r) ?></option>
            <?php endforeach ?>
          </select>
        </label>
        <button class="primary">Create user</button>
      </form>
    </article>

    <article class="panel">
      <div class="panel-head"><h2>Users (<?= count($users) ?>)</h2></div>
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr class="<?= $u['is_active'] ? '' : 'row-inactive' ?>">
            <td><b><?= esc($u['full_name']) ?></b></td>
            <td><?= esc($u['email']) ?></td>
            <td><span class="badge role"><?= esc($u['role']) ?></span></td>
            <td><span class="<?= $u['is_active'] ? 'status-on' : 'status-off' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="actions">
              <button type="button" class="act js-edit-user" title="Edit"
                      data-id="<?= esc($u['id'], 'attr') ?>"
                      data-name="<?= esc($u['full_name'], 'attr') ?>"
                      data-email="<?= esc($u['email'], 'attr') ?>"
                      data-role="<?= esc($u['role'], 'attr') ?>">
                <span class="material-symbols-outlined">edit</span>
              </button>
              <?php if ($u['id'] !== session('user_id')): ?>
                <?php if ($u['is_active']): ?>
                <form method="post" action="<?= base_url('settings/users/' . $u['id'] . '/delete') ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <button class="act danger-act" title="Deactivate"><span class="material-symbols-outlined">person_off</span></button>
                </form>
                <?php else: ?>
                <form method="post" action="<?= base_url('settings/users/' . $u['id']) ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="is_active" value="1">
                  <button class="act" title="Reactivate"><span class="material-symbols-outlined">person</span></button>
                </form>
                <?php endif ?>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </article>
  </div>
</div>

<!-- Edit user modal -->
<div class="overlay" id="editUserModal" hidden>
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h2>Edit user</h2>
      <button type="button" class="close-btn" onclick="closeModal('editUserModal')"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="post" id="editUserForm" action="">
      <?= csrf_field() ?>
      <label>Full name
        <input name="full_name" id="euName">
      </label>
      <label>Email
        <input name="email" type="email" id="euEmail">
      </label>
      <label>Role
        <select name="role" id="euRole">
          <?php foreach ($roles as $r): ?>
            <option><?= esc($r) ?></option>
          <?php endforeach ?>
        </select>
      </label>
      <label>New password (leave blank to keep current)
        <input name="password" type="password" autocomplete="new-password">
      </label>
      <button class="primary">Save changes</button>
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script>
(function () {
  "use strict";
  const tabP = document.getElementById("tabProfile"), tabU = document.getElementById("tabUsers");
  const panP = document.getElementById("panelProfile"), panU = document.getElementById("panelUsers");
  tabP.addEventListener("click", () => { tabP.classList.add("active"); tabU.classList.remove("active"); panP.hidden = false; panU.hidden = true; });
  tabU.addEventListener("click", () => { tabU.classList.add("active"); tabP.classList.remove("active"); panU.hidden = false; panP.hidden = true; });

  document.querySelectorAll(".js-edit-user").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.getElementById("editUserForm").action = "<?= base_url('settings/users') ?>/" + btn.dataset.id;
      document.getElementById("euName").value = btn.dataset.name;
      document.getElementById("euEmail").value = btn.dataset.email;
      document.getElementById("euRole").value = btn.dataset.role;
      openModal("editUserModal");
    });
  });
})();
</script>
<?= $this->endSection() ?>
