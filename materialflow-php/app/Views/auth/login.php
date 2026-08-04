<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in — MaterialFlow</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
</head>
<body>
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <span class="login-icon">▦</span>
        <div>
          <h1>MaterialFlow</h1>
          <p>BARCODE INVENTORY</p>
        </div>
      </div>

      <h2>Sign in to your account</h2>

      <?php if (! empty($error)): ?>
        <div class="login-error"><?= esc($error) ?></div>
      <?php endif ?>

      <form method="post" action="<?= url_to('Auth::attempt') ?>">
        <?= csrf_field() ?>
        <label>
          Email address
          <input name="email" type="email" value="<?= esc(old('email', '')) ?>" autocomplete="email" required autofocus placeholder="you@company.com">
        </label>
        <label>
          Password
          <input name="password" type="password" autocomplete="current-password" required placeholder="Enter your password">
        </label>
        <button class="primary login-btn" type="submit">Sign in</button>
      </form>

      <p class="login-footer">Contact your administrator if you don't have an account.</p>
    </div>
  </div>
</body>
</html>
