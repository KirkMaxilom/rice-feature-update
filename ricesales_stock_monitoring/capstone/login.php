<?php
declare(strict_types=1);

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

header(
  "Content-Security-Policy: ".
  "default-src 'self'; ".
  "img-src 'self' data:; ".
  "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; ".
  "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;"
);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);

session_start();

function h(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if (isset($_SESSION['user_id'])) {
  $role = strtolower($_SESSION['role'] ?? '');
  if ($role === 'admin')   { header("Location: admin/dashboard.php"); exit; }
  if ($role === 'cashier') { header("Location: cashier/dashboard.php"); exit; }
  if ($role === 'owner')   { header("Location: owner/dashboard.php"); exit; }
  header("Location: logout.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | Rice Inventory Control System</title>

<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0f172a">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<link href="css/login.css" rel="stylesheet">
</head>

<body>
<div class="container-fluid">
<div class="row min-vh-100">

  <!-- LOGIN PANEL -->
  <div class="col-md-6 d-flex align-items-center">
    <div class="w-100 px-3 px-md-5">
      <div class="login-panel">

        <img src="assets/logo.jpg" alt="Logo" style="height:42px;" class="mb-4">

        <h2 class="fw-bold mb-1">Welcome back!</h2>
        <p class="text-muted mb-3">Login to Rice Inventory Control System</p>

        <div class="mb-3">
          <span class="hint-badge"><i class="fa-solid fa-user-shield"></i> Admin</span>
          <span class="hint-badge"><i class="fa-solid fa-cash-register"></i> Cashier</span>
          <span class="hint-badge"><i class="fa-solid fa-user-tie"></i> Owner</span>
        </div>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="auth.php" class="needs-validation" novalidate id="loginForm">

          <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">

          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" id="username"
                   required autocomplete="username">
            <div class="invalid-feedback">Username required</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" name="password"
                     id="password" required autocomplete="current-password">
              <button type="button" class="input-group-text bg-white" id="togglePwdBtn">
                <i class="fa fa-eye" id="eyeIcon"></i>
              </button>
              <div class="invalid-feedback">Password required</div>
            </div>
          </div>

          <div class="d-flex justify-content-between mb-4">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" id="remember" name="remember">
              <label class="form-check-label" for="remember">Remember me</label>
            </div>
          </div>

          <button type="submit" class="btn btn-dark w-100" id="loginBtn">
            Login
          </button>
        </form>

        <p class="text-muted small mt-4">🔒 Rice Control System</p>

      </div>
    </div>
  </div>

  <!-- IMAGE PANEL -->
  <div class="col-md-6 hero-image d-none d-md-block"></div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/login.js?v=1"></script>
</body>
</html>
