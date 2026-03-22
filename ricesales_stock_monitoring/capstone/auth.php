<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'secure'   => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);

session_start();
require_once 'config/db.php';

function redirect_login(string $msg, string $type='error'): void {
  $_SESSION['flash_'.$type] = $msg;
  header("Location: login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: login.php");
  exit;
}

$csrf_post = (string)($_POST['csrf_token'] ?? '');
$csrf_sess = (string)($_SESSION['csrf_token'] ?? '');
if ($csrf_post === '' || $csrf_sess === '' || !hash_equals($csrf_sess, $csrf_post)) {
  redirect_login("Invalid request. Please refresh and try again.");
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$remember = isset($_POST['remember']);

if ($username === '' || $password === '') {
  redirect_login("Please enter username and password.");
}

$now = time();
$_SESSION['login_fail_count'] = (int)($_SESSION['login_fail_count'] ?? 0);
$_SESSION['login_lock_until'] = (int)($_SESSION['login_lock_until'] ?? 0);

if ($now < $_SESSION['login_lock_until']) {
  $remain = $_SESSION['login_lock_until'] - $now;
  redirect_login("Too many attempts. Try again in {$remain} seconds.");
}

$stmt = $conn->prepare("SELECT user_id, username, password, role, status FROM users WHERE username=? LIMIT 1");
if (!$stmt) {
  redirect_login("Server error. Please try again.");
}
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  // fail count
  $_SESSION['login_fail_count']++;
  if ($_SESSION['login_fail_count'] >= 5) {
    $_SESSION['login_lock_until'] = $now + 60; // 1 minute lock
    $_SESSION['login_fail_count'] = 0;
  }
  redirect_login("Invalid username or password.");
}

if (isset($user['status']) && strtolower((string)$user['status']) !== 'active') {
  redirect_login("Account is not active. Please contact admin.");
}

$stored = (string)($user['password'] ?? '');
$isValid = password_verify($password, $stored);

// Temporary fallback for plaintext (capstone migration)
if (!$isValid && $stored !== '' && hash_equals($stored, $password)) {
  $isValid = true;

  // OPTIONAL: auto-upgrade plaintext to hashed
  $newHash = password_hash($password, PASSWORD_DEFAULT);
  $u = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
  if ($u) {
    $uid = (int)$user['user_id'];
    $u->bind_param("si", $newHash, $uid);
    $u->execute();
    $u->close();
  }
}

if (!$isValid) {
  $_SESSION['login_fail_count']++;
  if ($_SESSION['login_fail_count'] >= 5) {
    $_SESSION['login_lock_until'] = $now + 60; // 1 minute lock
    $_SESSION['login_fail_count'] = 0;
  }
  redirect_login("Invalid username or password.");
}

// Success: reset counters
$_SESSION['login_fail_count'] = 0;
$_SESSION['login_lock_until'] = 0;

session_regenerate_id(true);

$_SESSION['user_id']  = (int)$user['user_id'];
$_SESSION['username'] = (string)$user['username'];
$_SESSION['role']     = (string)$user['role'];

if ($remember) {
  $token = bin2hex(random_bytes(32));

  // Set cookie with security flags
  setcookie("remember_token", $token, [
    'expires'  => time() + (86400 * 30),
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);

  // Keep in session (optional)
  $_SESSION['remember_token'] = $token;
} else {
  setcookie("remember_token", "", [
    'expires'  => time() - 3600,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  unset($_SESSION['remember_token']);
}

$device = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';

// If behind proxy you can check HTTP_X_FORWARDED_FOR, but be careful trusting it
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';

$logStmt = $conn->prepare("INSERT INTO login_logs (user_id, login_time, device_info, ip_address)
                           VALUES (?, NOW(), ?, ?)");
if ($logStmt) {
  $uid = $_SESSION['user_id'];
  $logStmt->bind_param("iss", $uid, $device, $ip);
  $logStmt->execute();
  $logStmt->close();
}

$role = strtolower(trim((string)$user['role']));

if ($role === 'admin')   { header("Location: admin/dashboard.php"); exit; }
if ($role === 'cashier') { header("Location: cashier/dashboard.php"); exit; }
if ($role === 'owner')   { header("Location: owner/dashboard.php"); exit; }

redirect_login("Role not recognized. Contact admin.");
