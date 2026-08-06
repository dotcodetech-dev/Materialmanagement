<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AVEON INFOTECH — Software Development, Mobile Apps, College / School / Hostel Management, GST Flow, NAAC / IQAC</title>
  <?= view('partials/seo') ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('css/styles.css') ?>">
</head>
<body>
<!-- Visually-hidden SEO block: crawlers see it, users don't. -->
<div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">
  <h1>AVEON INFOTECH</h1>
  <p>AVEON INFOTECH offers Software Development, Mobile app development, College Management, School Management, Hostel Management, Aveon GST Flow, Shallon Management, and NAAC / IQAC Software.</p>
  <ul>
    <li>Software Development</li>
    <li>Mobile app development</li>
    <li>College management</li>
    <li>School management</li>
    <li>Hostel Management</li>
    <li>Aveon GST Flow</li>
    <li>Shallon Management</li>
    <li>NAAC / IQAC Software</li>
  </ul>
</div>
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
